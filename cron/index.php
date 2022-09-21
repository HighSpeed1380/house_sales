<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: INDEX.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

define('CRON_FILE', true);

set_time_limit(0);

/* load configs */
require_once dirname(__DIR__) . '/includes/config.inc.php';
require_once RL_INC . 'control.inc.php';

$statistics = array();

/* load system configurations */
$config = $rlConfig->allConfig();
$rlSmarty->assign_by_ref('config', $config);
$GLOBALS['config'] = $config;

// load system libs
require_once RL_LIBS . 'system.lib.php';

define('RL_LANG_CODE', $config['lang']);
$languages = $rlLang->getLanguagesList();
define('RL_LANG_DIR', $languages[RL_LANG_CODE]['Direction']);

// set timezone
$reefless->setTimeZone();
$reefless->setLocalization();

$date_format = $rlDb->fetch(array('Date_format'), array('Code' => $config['lang']), null, 1, 'languages', 'row');
$date_format = str_replace('%', '', $date_format['Date_format']);

$lang = $rlLang->getLangBySide('frontEnd', $config['lang']);

$reefless->loadClass('Mail');
$reefless->loadClass('Listings');
$reefless->loadClass('Categories');
$reefless->loadClass('Actions');
$reefless->loadClass('ListingTypes', null, false, true);
$reefless->loadClass('Cache');
$reefless->loadClass('Account');

if ($config['membership_module']) {
    $reefless->loadClass('MembershipPlan');
    $reefless->loadClass('Admin', 'admin');
}

if (file_exists(RL_CLASSES . 'rlEscort.class.php')) {
    $reefless->loadClass('Escort');
}

/* get page paths */
$rlDb->setTable('pages');
$pages_tmp = $rlDb->fetch(array('Key', 'Path'));
foreach ($pages_tmp as $page_tmp) {
    $pages[$page_tmp['Key']] = $page_tmp['Path'];
}
unset($pages_tmp);

/* listing expiration: changing status of listing to expired, send expiration email */
$actions[] = 'expired';

/* sending pre expiration listing notification, send pre expiration email  */
$actions[] = 'pre_expired';

/* listing featured status expiration */
$actions[] = 'featured';

/* listing featured status pre expiration */
$actions[] = 'pre_featured';

/* this action performed 2 times, first run send notification to user. 2nd run deleting listings */
$actions[] = 'check_incompleted';

if ($config['cron_expired_remove']) {
    $actions[] = 'delete_expired_notify';
    $actions[] = 'delete_expired';
}

foreach ($languages as $key => $language) {
    $mail_expired[$key] = $rlMail->getEmailTemplate('cron_listing_expired2', $language['Code']);
    $mail_pre_expired[$key] = $rlMail->getEmailTemplate('cron_listing_pre_expired2', $language['Code']);
    $mail_featured[$key] = $rlMail->getEmailTemplate('cron_featured_status_expired2', $language['Code']);
    $mail_pre_featured[$key] = $rlMail->getEmailTemplate('cron_featured_status_pre_expired2', $language['Code']);
    $mail_check_incompleted[$key] = $rlMail->getEmailTemplate('cron_incomplete_listing2', $language['Code']);
}

foreach ($actions as $ak => $action) {
    $tosend = performAction($action); // do action: database fields update, collecting emails etc.
    if ($tosend) {
        sendEmails($tosend, $action); //send emails
    }
}

/**
 * Perform action
 *
 * @param string $action
 */
function performAction($action)
{
    global $rlDb, $rlCategories, $rlListings, $config, $statistics, $lang, $reefless, $rlLang;

    $limit  = $GLOBALS['config']['listings_number'];
    $select = '';
    $join   = '';
    $where  = '';

    switch ($action) {
        case "expired":
            $join = "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Plan_ID` = `T4`.`ID`";

            $where .= "(";
            $where .= " TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) > `T4`.`Listing_period` * 24 "; //round to hour
            $where .= " AND `T4`.`Listing_period` != 0 ) ";
            $where .= "AND `T1`.`Status` <> 'expired' AND `T1`.`Status` <> 'incomplete' ";

            $action_update['Pay_date'] = '';
            $action_update['Status'] = 'expired';
            $action_update['Cron_notified'] = '0';
            break;

        case "pre_expired";
            $join = "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Plan_ID` = `T4`.`ID`";
            $select = "`T4`.`Listing_period`, `T4`.`Listing_period` - TIMESTAMPDIFF(DAY, `T1`.`Pay_date`, NOW()) AS `Pre_days`";

            $where = "(";
            $where .= " TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) > ((`T4`.`Listing_period` - " . $config['pre_days'] . ") * 24) ";
            $where .= " AND `T4`.`Listing_period` != 0 ";
            $where .= ") ";
            $where .= "AND `T1`.`Cron_notified` != '1' ";
            $where .= "AND `T1`.`Status` <> 'expired' AND `T1`.`Status` <> 'incomplete' ";
            $action_update['Cron_notified'] = '1';
            break;

        case "featured":
            $join = "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Featured_ID` = `T4`.`ID`";
            $select = "`T4`.`Listing_period`, UNIX_TIMESTAMP(`T1`.`Featured_date`) AS `Featured_date_unix`";

            $where = "UNIX_TIMESTAMP(DATE_ADD(`T1`.`Featured_date`, INTERVAL `T4`.`Listing_period` DAY)) < UNIX_TIMESTAMP(NOW()) ";
            $where .= "AND  `T4`.`Listing_period` != 0 ";
            $where .= "AND `T1`.`Featured_date` IS NOT NULL ";
            $where .= "AND `T1`.`Status` <> 'expired' AND `T1`.`Status` <> 'incomplete' ";
            $where .= "AND (`T4`.`Type` = 'featured' OR `T4`.`Featured` = '1') ";

            $action_update['Featured_date'] = '';
            break;

        case "pre_featured":
            $join = "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Featured_ID` = `T4`.`ID`";
            $select = "`T4`.`Listing_period`, UNIX_TIMESTAMP(`T1`.`Featured_date`) AS `Featured_date_unix`, `T4`.`Listing_period` - TIMESTAMPDIFF(DAY, `T1`.`Featured_date`, NOW()) AS `Pre_days`";

            $where = "UNIX_TIMESTAMP(DATE_ADD(`T1`.`Featured_date`, INTERVAL ";
            $where .= "(`T4`.`Listing_period`- '" . $config['pre_days'] . "') DAY)) < UNIX_TIMESTAMP(NOW()) ";
            $where .= "AND  `T4`.`Listing_period` != 0 ";
            $where .= "AND `T1`.`Featured_date` IS NOT NULL AND `T1`.`Cron_featured` != '1' ";
            $where .= "AND `T1`.`Status` <> 'expired' AND `T1`.`Status` <> 'incomplete' ";
            $where .= "AND (`T4`.`Type` = 'featured'  OR `T4`.`Featured` = '1') ";

            $action_update['Cron_featured'] = '1';
            break;

        /* incomplete listings checking */
        case "check_incompleted":
            $notify_days = $config['cron_incompl_listings_notify_days'];
            if (empty($notify_days)) {
                $notify_days = 1;
            }

            $select = "UNIX_TIMESTAMP(`T1`.`Date`) AS `Date_unix`";
            $select .= ", DATEDIFF(NOW(), `T1`.`Date`) as `Date_diff`";

            $where = "UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL {$notify_days} DAY)) < UNIX_TIMESTAMP(NOW()) ";
            $where .= "AND `T1`.`Status` = 'incomplete' ";

            $action_update['Last_show'] = 'NOW()';
            $action_update['Cron_notified'] = '1';
            break;
        case "delete_expired_notify":
            $where = "`T1`.`Status` = 'expired' AND `T1`.`Cron_notified` != '1' ";
            $action_update['Cron_notified'] = '1';
            break;
        case "delete_expired":
            $where = "`T1`.`Status` = 'expired' AND `T1`.`Cron_notified` = '1' ";

            $where .= "AND `T1`.`Loc_address` != '' AND DATE_ADD(FROM_UNIXTIME(`T1`.`Loc_address`), ";
            $where .= "INTERVAL " . $config['cron_expired_listing_days'] . " DAY) < NOW() ";
            break;
    }

    $first = true;
    $listings = [];
    while (count($listings) == $limit || $first) {
        $first = false;
        $send_mail = true;

        $sql = "SELECT `T1`.*, `T2`.`Lang`, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Pay_date`, `T3`.`Type` AS `Listing_type`, ";
        $sql .= $select ? $select . ", " : "";
        $sql .= "`T2`.`Mail`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Username`, `T3`.`Path` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= $join;
        $sql .= " WHERE `T1`.`Status` <> 'trash' ";
        $sql .= $where ? "AND " . $where : "";

        $sql .= "GROUP BY `T1`.`ID` ";

        if ($action != 'check_incompleted') {
            $sql .= "LIMIT 0, {$limit}";
        }

        $listings = $rlDb->getAll($sql);

        foreach ($listings as $key => $listing) {
            $listing_type = $GLOBALS['rlListingTypes']->types[$listing['Listing_type']];
            $listing_title = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing_type['Key']);

            $update[$key]['fields'] = $action_update;
            $update[$key]['where']['ID'] = $listing['ID'];

            if ($action == 'expired') {
                $rlCategories->listingsDecrease($listings[$key]['Category_ID']);
                $rlCategories->accountListingsDecrease($listings[$key]['Account_ID']);

                if (!empty($listing['Crossed'])) {
                    $crossed_cats = explode(',', trim($listing['Crossed'], ','));
                    foreach ($crossed_cats as $crossed_cat_id) {
                        $rlCategories->listingsDecrease($crossed_cat_id);
                    }
                }
            }

            if ($action == 'delete_expired_notify') {
                $update[$key]['fields']['Loc_address'] = time(); //save current time to calculate remove date
            } elseif ($action == 'delete_expired') {
                $rlListings->deleteListingData($listing['ID']);
                $rlDb->query("DELETE FROM `{db_prefix}listings` WHERE `ID` = {$listing['ID']} LIMIT 1");

                $statistics['expired_listings_removed']++;
            }

            if ($action == 'check_incompleted') {
                if ($listing['Cron_notified']) {
                    if ($listing['Date_unix'] + ($config['cron_incomplete_listing_days'] * 86400) <= time()) {
                        $rlListings->deleteListingData($listing['ID']);
                        $rlDb->query("DELETE FROM `{db_prefix}listings` WHERE `ID` = {$listing['ID']} LIMIT 1");

                        $statistics['incomplete_listings_removed']++;
                    } elseif ($listing['Account_ID'] > 0) {
                        $statistics['incomplete_listings_waiting']++;
                    }
                    $send_mail = false;
                } elseif ($listing['Account_ID'] > 0) {
                    $statistics['incomplete_listings_notified']++;
                }

                if ($listing['Account_ID'] > 0) {
                    $statistics['check_incompleted']++;
                }
            }

            if ($send_mail) {
                //collect emails to send them later
                $username = trim(
                    $listing['First_name'] || $listing['Last_name']
                    ? $listing['First_name'] . ' ' . $listing['Last_name']
                    : $listing['Username']
                );

                $update_link = RL_URL_HOME;
                $details_link = $reefless->getListingUrl((int) $listing['ID'], $listing['Lang']);

                switch ($action) {
                    case "pre_expired":
                        $expire_date = $listing['Pay_date'] + ($listing['Listing_period'] * 86400);
                        $expire_date = date(str_replace('b', 'M', $GLOBALS['date_format']), $expire_date);

                        $afind[] = '{expire_date}';
                        $areplace[] = $expire_date;
                    case "expired":
                        $update_link = $reefless->getPageUrl('upgrade_listing', '', $listing['Lang']);
                        $update_link .= ($config['mod_rewrite'] ? '?' : '&') . ('id=' . $listing['ID']);

                        $renew_phrase = $listing['Lang']
                        ? $rlLang->getPhrase(array('key' => 'renew', 'lang' => $listing['Lang']))
                        : $lang['renew'];
                        $update_text = $renew_phrase;

                        if (!$config['ld_keep_alive'] && $action == 'expired') {
                            $edit_listing_phrase = $listing['Lang']
                            ? $rlLang->getPhrase(array('key' => 'edit_listing', 'lang' => $listing['Lang']))
                            : $lang['edit_listing'];

                            $listing_title .= ' (' . $edit_listing_phrase . ')';

                            $details_link = $reefless->getPageUrl('edit_listing', '', $listing['Lang']);
                            $details_link .= $config['mod_rewrite'] ? '?' : '&';
                            $details_link .= 'id=' . $listing['ID'];
                        }

                        $afind[] = '{remove_days}';
                        $areplace[] = $config['cron_expired_listing_days'];
                        break;
                    case "featured":
                    case "pre_featured":
                        $expire_date = $listing['Featured_date_unix'] + ($listing['Listing_period'] * 86400);
                        $expire_date = date(str_replace('b', 'M', $GLOBALS['date_format']), $expire_date);

                        $afind[] = '{expire_date}';
                        $areplace[] = $expire_date;

                        $update_link = $GLOBALS['reefless']->getPageUrl('upgrade_listing', '', $listing['Lang']);
                        $update_link = $config['mod_rewrite']
                        ? (str_replace('.html', '/featured.html', $update_link) . '?')
                        : ($update_link . '&');
                        $update_link .= 'id=' . $listing['ID'];

                        $update_phrase = $listing['Lang']
                        ? $rlLang->getPhrase(array('key' => 'update', 'lang' => $listing['Lang']))
                        : $lang['update'];
                        $update_text = $update_phrase;
                        break;
                    case "check_incompleted":
                        $hash = $reefless->generateHash(10);

                        $update[$key]['fields']['Loc_address'] = md5($hash);
                        $update[$key]['where']['ID'] = $listing['ID'];

                        $details_link = $reefless->getPageUrl(
                            'add_listing',
                            '',
                            $listing['Lang']
                        );
                        $details_link .= $config['mod_rewrite'] ? '?' : '&';
                        $details_link .= 'incomplete=' . $listing['ID'];

                        $update_link = $reefless->getPageUrl('listing_remove', '', $listing['Lang']);
                        $update_link .= $config['mod_rewrite'] ? '?' : '&';
                        $update_link .= 'id=' . $listing['ID'] . '&hash=' . $hash;

                        $remove_phrase = $listing['Lang']
                        ? $rlLang->getPhrase(array('key' => 'remove', 'lang' => $listing['Lang']))
                        : $lang['remove'];
                        $update_text = $remove_phrase;

                        $afind[] = '{number_days}';

                        $days_before_removing = $config['cron_incomplete_listing_days'] - $listing['Date_diff'];
                        $days_before_removing = $days_before_removing > 0 ? $days_before_removing : 1;

                        $areplace[] = $days_before_removing;

                        break;
                }

                if ($update_text) {
                    $afind[] = '{update_text}';
                    $areplace[] = $update_text;
                }

                $tosend[$listing['Mail']]['title'][] = $listing_title;
                $tosend[$listing['Mail']]['update_links'][] = $update_link;
                $tosend[$listing['Mail']]['details_links'][] = $details_link;
                $tosend[$listing['Mail']]['expire_date'][] = $expire_date;
                $tosend[$listing['Mail']]['username'] = trim($username);
                $tosend[$listing['Mail']]['user_lang'] = $listing['Lang'];
                $tosend[$listing['Mail']]['account_id'] = $listing['Account_ID'];
                $tosend[$listing['Mail']]['pre_days'] = (int) $listing['Pre_days'];
                $tosend[$listing['Mail']]['action'] = $action;

                $tosend[$listing['Mail']]['afind'] = $afind;
                $tosend[$listing['Mail']]['areplace'] = $areplace;
            }
        }

        if ($action != 'check_incompleted') {
            $statistics[$action] += count($listings);
        }

        if (!empty($update)) {
            $rlDb->update($update, 'listings');
            unset($update);
        }
    }

    return $tosend;
}

/**
 * Sending of emails
 *
 * @param array  $tosend - Array with emails information
 * @param string $action - Performed action
 */
function sendEmails($tosend, $action)
{
    global $rlMail, $config, $lang, $rlLang;

    foreach ($tosend as $email => $row) {
        $email_tpl = $GLOBALS['mail_' . $action][$row['user_lang'] ? $row['user_lang'] : $config['lang']];

        $expire_date_phrase = $row['user_lang']
        ? $rlLang->getPhrase(array('key' => 'expire_date', 'lang' => $row['user_lang']))
        : $lang['expire_date'];

        $renew_phrase_key = $row['action'] == 'check_incompleted' ? 'remove' : 'renew';
        $renew_phrase = $row['user_lang']
        ? $rlLang->getPhrase(array('key' => $renew_phrase_key, 'lang' => $row['user_lang']))
        : $lang[$renew_phrase_key];

        $view_details_phrase = $row['user_lang']
        ? $rlLang->getPhrase(array('key' => 'view_details', 'lang' => $row['user_lang']))
        : $lang['view_details'];

        if ($email_tpl && $email_tpl['body'] && $email_tpl['subject']) {
            $current = count($row['details_links']) > 1 ? "many" : "one";
            $other = $current == "many" ? "one" : "many";

            /* remove other case, e.g: if many listings remove one listing case completely  */
            $email_tpl['body'] = preg_replace('/\{if ' . $other . '\}(((?!\{\/if\}).)*){\/if\}/smi', '', $email_tpl['body']);
            $email_tpl['subject'] = preg_replace('/\{if ' . $other . '\}(((?!\{\/if\}).)*){\/if\}/smi', '', $email_tpl['subject']);

            /* replace actual condition, leave only text itself */
            $email_tpl['body'] = preg_replace('/\{if ' . $current . '\}(((?!\{\/if\}).)*){\/if\}/smi', '$1', $email_tpl['body']);
            $email_tpl['subject'] = preg_replace('/\{if ' . $current . '\}(((?!\{\/if\}).)*){\/if\}/smi', '$1', $email_tpl['subject']);

            if ($current == "many") {
                $links_table = "<table>";

                // table heading
                $links_table .= '<tr><td><b>' . $view_details_phrase . '</b></td>';
                $links_table .= '<td style="padding-left:20px"><b>' . $renew_phrase . '</b></td>';

                if ($row['expire_date'][0]) {
                    $links_table .= '<td style="padding-left:20px"><b>' . $expire_date_phrase . '</b><td>';
                }

                // data rows
                foreach ($row['details_links'] as $key => $link) {
                    $links_table .= '<tr><td><a href="' . $link . '">' . $row['title'][$key] . '</a></td>';
                    $links_table .= '<td style="padding-left:20px"><a href="' . $row['update_links'][$key];
                    $links_table .= '">' . $row['update_links'][$key] . '</a></td>';
                    if ($row['expire_date'][$key]) {
                        $links_table .= '<td style="padding-left:20px">' . ($row['expire_date'][$key] ?: '') . '</td></tr>';
                    }
                }

                $links_table .= "</table>";
            } else {
                $update_link = '<a href="' . $row['update_links'][0] . '">{update_text}</a>';
                $details_link = '<a href="' . $row['details_links'][0] . '">';
                $row_title = is_array($row['title']) ? $row['title'][0] : $row['title'];
                $details_link .= ($row_title ?: $row['details_links'][0]) . '</a>';
            }

            $username = $row['username'];
            $pre_days = $row['pre_days'] ?: $config['pre_days'];

            $find = array(
                '{name}',
                '{renew_link}',
                '{details_link}',
                '{links}',
                '{days}',
                '{listing_title}',
                '{complete_link}',
                '{delete_link}',
            );
            $replace = array(
                trim($username),
                $update_link,
                $details_link,
                $links_table,
                $pre_days,
                $row_title ?: $row['title'],
                $details_link,
                $update_link,
            );

            foreach ($row['afind'] as $afk => $afr) {
                $find[] = $afr;
                $replace[] = $row['areplace'][$afk];
            }

            $email_tpl['body'] = str_replace($find, $replace, $email_tpl['body']);
            $email_tpl['subject'] = str_replace($find, $replace, $email_tpl['subject']);

            $rlMail->send($email_tpl, $email);
        }
    }
}

/* SAVED SEARCH CHECKING */
$sql = "SELECT `T1`.`ID`, `T1`.`Account_ID`, `T1`.`Form_key`, `T1`.`Listing_type`, `T1`.`Content`, `T1`.`Matches`, `T2`.`Lang` ";
$sql .= "FROM `{db_prefix}saved_search` AS `T1` ";
$sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Account_ID` ";
$sql .= "WHERE `T1`.`Cron` = '0' AND `T1`.`Status` = 'active' LIMIT {$config['searches_per_run']}";
$searches = $rlDb->getAll($sql);

if (empty($searches)) {
    $rlDb->query("UPDATE `{db_prefix}saved_search` SET `Cron` = '0'");
} else {
    /* prefere email notification template */
    foreach ($languages as $key => $language) {
        $saved_search_email[$language['Code']] = $rlMail->getEmailTemplate('cron_saved_search_match', $language['Code']);
    }

    $reefless->loadClass('Search');
    $reefless->loadClass('Common');

    foreach ($searches as $key => $search) {
        $rlSearch->getFields($search['Form_key'], $search['Listing_type']);

        $content = unserialize($search['Content']);

        $rlSearch->exclude = $search['Matches'];
        $matches = $rlSearch->search($content, $search['Listing_type'], 0, 20);
        $rlSearch->exclude = false;

        $checked_listings = $search['Matches'];
        $exploded_matches = explode(',', $checked_listings);

        $update[$key]['fields']['Cron'] = '1';

        if (!empty($matches)) {
            $account_info = $rlAccount->getProfile((int) $search['Account_ID']);
            $allow_send   = false;
            $links        = '';

            foreach ($matches as $match) {
                if (!in_array($match['ID'], $exploded_matches)) {
                    $listing_type = $rlListingTypes->types[$match['Listing_type']];

                    // send links to listings from other users only
                    if ($match['Account_ID'] != $account_info['ID']) {
                        $checked_listings .= (!empty($checked_listings) ? ',' : '') . $match['ID'];
                        $match_count++;
                        $allow_send = true;

                        $link  = $reefless->getListingUrl($match, $account_info['Lang']);
                        $links .= '<a href="' . $link . '">' . $match['listing_title'] . '</a><br />';
                    }
                }
            }

            if ($allow_send) {
                $update[$key]['fields']['Matches'] = $checked_listings;
                $count = $match_count;
                $copy_notify_email = $saved_search_email[$account_info['Lang'] ? $account_info['Lang'] : RL_LANG_CODE];
                $copy_notify_email['body'] = str_replace(
                    array('{name}', '{count}', '{links}'),
                    array($account_info['Full_name'], $count, $links),
                    $copy_notify_email['body']
                );
                $copy_notify_email['subject'] = str_replace(
                    array('{name}', '{count}'),
                    array($account_info['Full_name'], $count),
                    $copy_notify_email['subject']
                );
                $rlMail->send($copy_notify_email, $account_info['Mail']);

                // cron send save search
                $rlHook->load('cronSavedSearchNotify', $search, $checked_listings, $account_info);
            }
        }

        $update[$key]['fields']['Cron'] = '1';
        $update[$key]['fields']['Date'] = 'NOW()';
        $update[$key]['where']['ID'] = $search['ID'];

        unset($copy_notify_email, $content, $allow_send, $links, $match_count);
    }

    if ($update) {
        $reefless->loadClass('Actions');
        $rlActions->update($update, 'saved_search');
    }

    unset($update);
}

// prepare emails for expired and incomplete accounts
foreach ($languages as $key => $language) {
    if ($config['membership_module']) {
        $membership_expired_emails[$language['Code']] = $rlMail->getEmailTemplate(
            'cron_membership_expired',
            $language['Code']
        );
        $membership_pre_expired_emails[$language['Code']] = $rlMail->getEmailTemplate(
            'cron_membership_pre_expired',
            $language['Code']
        );
    }

    $mail_check_ac_incompleted[$language['Code']] = $rlMail->getEmailTemplate(
        'cron_incomplete_account',
        $language['Code']
    );
}

/* handle pre-expired accounts */
if ($config['membership_module']) {
    $sql = "SELECT `T1`.*, DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Plan_period` DAY) AS `Plan_expire`, ";
    $sql .= "`T2`.`Plan_period`, `T2`.`Plan_period` - TIMESTAMPDIFF(DAY, `T1`.`Pay_date`, NOW()) AS `Pre_days` ";
    $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID`";
    $sql .= "WHERE (";
    $sql .= " TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) > ((`T2`.`Plan_period` - " . $config['pre_days_membership'] . ") * 24) ";
    $sql .= " AND `T2`.`Plan_period` != 0 ";
    $sql .= ") ";
    $sql .= "AND `T1`.`Cron_notified` != '1' AND `T1`.`Status` = 'active' ";
    $sql .= "LIMIT {$config['accounts_number']} ";
    $pre_expired_accounts = $rlDb->getAll($sql);

    foreach ($pre_expired_accounts as $key => $account) {
        $mail_tpl = $account['Lang']
        ? $membership_pre_expired_emails[$account['Lang']]
        : $membership_pre_expired_emails[$config['lang']];

        $username = $account['First_name'] || $account['Last_name'] ?
        $account['First_name'] . ' ' . $account['Last_name']
        : $account['Username'];

        $renew_link = $reefless->getPageUrl('my_profile', ['renew']);

        $find = array(
            '{name}',
            '{renew_link}',
            '{days}',
            '{expire_date}',
        );
        $replace = array(
            trim($username),
            $renew_link,
            $account['Pre_days'],
            $account['Plan_expire'],
        );

        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        $mail_tpl['subject'] = str_replace($find, $replace, $mail_tpl['subject']);
        $rlMail->send($mail_tpl, $account['Mail']);

        $update['where']['ID'] = $account['ID'];
        $update['fields']['Cron_notified'] = '1';
        $rlDb->updateOne($update, 'accounts');
    }
}
/* handle pre-expired accounts end */

/* handle expired accounts */
if ($config['membership_module']) {
    $sql = "SELECT `T1`.* FROM `{db_prefix}accounts` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID`";
    $sql .= "WHERE (TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) > `T2`.`Plan_period` * 24 AND `T2`.`Plan_period` != 0) ";
    $sql .= "AND `T1`.`Status` = 'active' ";
    $sql .= "LIMIT {$config['accounts_number']} ";
    $expired_accounts = $rlDb->getAll($sql);

    foreach ($expired_accounts as $key => $account) {
        $mail_tpl = $account['Lang']
        ? $membership_expired_emails[$account['Lang']]
        : $membership_expired_emails[$config['lang']];

        $rlListings->listingStatusControl(array('Account_ID' => $account['ID']), 'expired');

        $username = $account['First_name'] || $account['Last_name'] ?
        $account['First_name'] . ' ' . $account['Last_name']
        : $account['Username'];

        $mail_tpl['body'] = str_replace('{name}', trim($username), $mail_tpl['body']);
        $rlMail->send($mail_tpl, $account['Mail']);

        $update['where']['ID']      = $account['ID'];
        $update['fields']['Status'] = 'expired';
        $rlDb->updateOne($update, 'accounts');
    }
}
/* handle expired accounts end */

/* handle incomplete accounts */
$reefless->loadClass('Admin', 'admin');

$notify_days = $config['cron_incompl_accounts_notify_days'] ?: 1;
$statistics['check_ac_incompleted'] = 0;

$sql = 'SELECT `T1`.*, UNIX_TIMESTAMP(`T1`.`Date`) AS `Date_unix` ';
$sql .= 'FROM `{db_prefix}accounts` AS `T1` ';
$sql .= "WHERE UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL {$notify_days} DAY)) < UNIX_TIMESTAMP(NOW()) ";
$sql .= "AND `T1`.`Status` = 'incomplete' ";
$sql .= "LIMIT {$config['accounts_number']} ";
$incomplete_accounts = $rlDb->getAll($sql);

foreach ($incomplete_accounts as $account) {
    $statistics['check_ac_incompleted']++;

    if ($account['Cron_notified']) {
        if ($account['Date_unix'] + ($config['cron_incomplete_account_days'] * 86400) <= time()) {
            $sql = 'SELECT `T1`.`ID`, `T1`.`Category_ID`, `T1`.`Crossed`, `T2`.`Type` AS `Listing_type` ';
            $sql .= 'FROM `{db_prefix}listings` AS `T1` ';
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Account_ID` = {$account['ID']}";
            $ac_listings = $rlDb->getAll($sql);

            $rlAdmin->deleteAccountDetails($account['ID'], $ac_listings);
            $rlDb->query("DELETE FROM `{db_prefix}accounts` WHERE `ID` = {$account['ID']} LIMIT 1");

            $statistics['incomplete_accounts_removed']++;
        } else {
            $statistics['incomplete_accounts_waiting']++;
        }
    } else {
        $hash = $reefless->generateHash(10);

        if ($account['Last_step']) {
            $complete_link = $reefless->getPageUrl('my_profile', false, $account['Lang']);
            $complete_link .= $config['mod_rewrite'] ? '?' : '&';
            $complete_link .= 'incomplete=' . $account['ID'] . '&step=' . $account['Last_step'];
        } elseif ($account['Confirm_code']) {
            $complete_link = $reefless->getPageUrl('confirm', false, $account['Lang']);
            $complete_link .= $config['mod_rewrite'] ? '?' : '&';
            $complete_link .= 'key=' . $account['Confirm_code'];
        }

        $complete_link = "<a href=\"{$complete_link}\">{$complete_link}</a>";

        $delete_link = $reefless->getPageUrl('home', false, $account['Lang']);
        $delete_link .= $config['mod_rewrite'] ? '?' : '&';
        $delete_link .= 'remove-account&id=' . $account['ID'] . '&hash=' . base64_encode($hash);
        $delete_link = "<a href=\"{$delete_link}\">{$delete_link}</a>";

        $name = $account['First_name'] || $account['Last_name']
        ? trim($account['First_name'] . ' ' . $account['Last_name'])
        : $account['Username'];

        $find = array('{name}', '{complete_link}', '{delete_link}', '{number_days}');
        $replace = array($name, $complete_link, $delete_link, $config['cron_incomplete_account_days']);

        $mail_tpl = $mail_check_ac_incompleted[$account['Lang']];
        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

        $rlMail->send($mail_tpl, $account['Mail']);

        $update['fields']['Cron_notified'] = '1';
        $update['fields']['Loc_address'] = md5($hash);
        $update['where']['ID'] = $account['ID'];

        $rlDb->updateOne($update, 'accounts');

        $statistics['incomplete_accounts_notified']++;
    }
}
/* handle incomplete accounts end */

/* remove messages */
if ($config['cron_messages_remove']) {
    $rlDb->query("DELETE FROM `{db_prefix}messages` WHERE `Remove` = 'from,to'");
}

/* login attempts clear up */
if ($config['security_login_attempt_admin_module']) {
    $sql = "DELETE FROM `{db_prefix}login_attempts` WHERE `Interface` = 'admin' AND TIMESTAMPDIFF(MONTH, `Date`, NOW()) > 2";
    $rlDb->query($sql);
}

if ($config['security_login_attempt_user_module']) {
    $sql = "DELETE FROM `{db_prefix}login_attempts` WHERE `Interface` = 'user' AND TIMESTAMPDIFF(MONTH, `Date`, NOW()) > 2";
    $rlDb->query($sql);
}

/* clear expired auth data */
$rlDb->query("DELETE FROM `{db_prefix}auth_tokens` WHERE `Expires` < NOW()");

// Clear old listing show statistics
$rlDb->query("DELETE FROM `{db_prefix}listings_shows` WHERE UNIX_TIMESTAMP(DATE_ADD(`Date`, INTERVAL 7 DAY)) < UNIX_TIMESTAMP()");

/* run cron hooks */
$rlHook->load('cronAdditional');

/* save last run date */
$rlDb->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'cron_last_run'");

/* send notification to admin */
if ($config['listings_checking_notification']) {
    $notification_phrases['expired'] = 'Ads expired';
    $notification_phrases['pre_expired'] = 'Expiration notifications sent';
    $notification_phrases['featured'] = 'Featured ads expired';
    $notification_phrases['pre_featured'] = 'Featured expiration notifications sent';
    $notification_phrases['check_incompleted'] = 'Total incomplete ads';
    $notification_phrases['incomplete_listings_notified'] = 'Incomplete ads notified';
    $notification_phrases['incomplete_listings_removed'] = 'Incomplete ads removed';
    $notification_phrases['incomplete_listings_waiting'] = 'Incomplete ads waiting ' . $config['cron_incomplete_listing_days'] . ' days to be removed';
    $notification_phrases['delete_expired'] = 'Expired listings removed';
    $notification_phrases['delete_expired_notify'] = 'Expired listings waiting removal';

    $notification_phrases['check_ac_incompleted'] = 'Total incomplete accounts';
    $notification_phrases['incomplete_accounts_notified'] = 'Incomplete accounts notified';
    $notification_phrases['incomplete_accounts_removed'] = 'Incomplete accounts removed';
    $notification_phrases['incomplete_accounts_waiting'] = 'Incomplete accounts waiting ' . $config['cron_incomplete_listing_days'] . ' days to be removed';

    $admin_email['subject'] = "Cron Job Notification";
    $admin_email['body'] = "
    Cron Job notification at " . date(str_replace('b', 'M', $date_format)) . "<br /><br />";

    foreach ($statistics as $action => $count) {
        if ($notification_phrases[$action]) {
            $admin_email['body'] .= $notification_phrases[$action] . ": " . $count . "<br />";
        }
    }

    $rlMail->send($admin_email, $config['notifications_email']);

    if ($statistics) {
        $rlCache->updateStatistics();
    }
}
