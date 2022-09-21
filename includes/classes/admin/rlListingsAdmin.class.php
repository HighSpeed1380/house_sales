<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLLISTINGSADMIN.CLASS.PHP
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

class rlListingsAdmin extends reefless
{
    /**
     * @var calculate items
     **/
    public $calc;

    /**
     * @var listing fields list (view listing details mode)
     **/
    public $fieldsList;

    /**
     * delete listing
     *
     * @package xAjax
     *
     * @param string $id  - listing field
     * @param string $reason  - reason message
     *
     **/
    public function ajaxDeleteListingAdmin($id, $reason = false)
    {
        global $_response, $config, $lang, $pages, $listing;

        if (!$id) {
            return false;
        }

        $this->loadClass('Listings');

        /* get data for email before listing deleted */
        $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = {$id}";
        $listing = $this->getRow($sql);
        $listing_title = $GLOBALS['rlListings']->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);

        $account_info = $GLOBALS['rlAccount']->getProfile((int) $listing['Account_ID']);
        /* get data for email before listing deleted end */

        $GLOBALS['rlListings']->deleteListing($id);
        $del_action = $GLOBALS['rlActions']->action;

        /* send notification to the owner */
        $this->loadClass('Mail');
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('listing_removed_by_admin', $account_info['Lang']);

        $link = $this->getPageUrl('contact_us', '', $account_info['Lang']);

        $find = array(
            '{name}',
            '{listing_title}',
            '{reason}',
        );

        $no_reason_specified_phrase = $GLOBALS['rlLang']->getPhrase(
            array('key' => 'no_reason_specified', 'lang' => $account_info['Lang'])
        );

        $replace = array(
            $account_info['Full_name'],
            $listing_title,
            $reason ?: ($no_reason_specified_phrase ?: $lang['no_reason_specified']),
        );

        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        $mail_tpl['body'] = preg_replace('/\[(.*)\]/', '<a href="' . $link . '">$1</a>', $mail_tpl['body']);

        $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);

        $_response->script("printMessage('notice', '{$lang['mass_listings_' . $del_action]}');");
        $_response->script("listingsGrid.reload();");

        return $_response;
    }

    /**
     * Mass actions with listings
     *
     * @package xAjax
     *
     * @param string $ids    - Listings ids
     * @param string $action - Mass action
     */
    public function ajaxMassActions($ids = '', $action = '')
    {
        global $_response, $lang, $rlListingTypes, $rlCache, $rlDb, $reefless;

        if (!$ids || !$action) {
            return $_response;
        }

        $GLOBALS['rlHook']->load('apPhpListingsMassActions', $ids, $action); //> 4.1.0

        $ids = explode('|', $ids);

        $reefless->loadClass('Mail');
        $reefless->loadClass('Categories');
        $reefless->loadClass('Account');
        $reefless->loadClass('Notice');
        $reefless->loadClass('Listings');

        if (in_array($action, array('activate', 'approve'))) {
            foreach ($ids as $id) {
                $id = (int) $id;

                $sql = "SELECT `T1`.*, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Payed`, `T2`.`Username`, `T2`.`Mail`, `T3`.`Type` AS `Listing_type`, `T3`.`Path` AS `Category_path` ";
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "RIGHT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
                $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
                $sql .= "WHERE `T1`.`ID` = {$id}";
                $listing_info = $rlDb->getRow($sql);
                $set_status = $action == 'activate' ? 'active' : 'approval';

                /* get account info */
                $owner_info = $GLOBALS['rlAccount']->getProfile((int) $listing_info['Account_ID']);

                if ($listing_info['Status'] == $set_status) {
                    continue;
                }

                $owners_info[$owner_info['Mail']] = $owner_info;
                $tosend[$owner_info['Mail']]['lang'] = $owner_info['Lang'];
                $listing_type = $rlListingTypes->types[$listing_info['Listing_type']];

                /* generate link */
                if ($action == 'activate') {
                    // Increase listings counter
                    if (!$GLOBALS['rlListings']->isActive($listing_info['ID'])) {
                        $GLOBALS['rlCategories']->listingsIncrease($listing_info['Category_ID'], false, $listing_info);
                        $GLOBALS['rlCategories']->accountListingsIncrease($listing_info['Account_ID']);
                        if (!empty($listing_info['Crossed'])) {
                            $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                            foreach ($crossed_cats as $crossed_cat_id) {
                                $GLOBALS['rlCategories']->listingsIncrease($crossed_cat_id);
                            }
                        }
                    }

                    $listing_title = $GLOBALS['rlListings']->getListingTitle(
                        $listing_info['Category_ID'],
                        $listing_info,
                        $listing_type['Key']
                    );

                    $link = $reefless->getListingUrl((int) $listing_info['ID'], $owner_info['Lang']);
                    $link = '<a href="' . $link . '">' . $listing_title . '</a>';

                    $tosend[$owner_info['Mail']]['links'][] = $link;
                } else {
                    // Decrease listings counter
                    if ($GLOBALS['rlListings']->isActive($listing_info['ID'])) {
                        $GLOBALS['rlCategories']->listingsDecrease($listing_info['Category_ID']);
                        $GLOBALS['rlCategories']->accountListingsDecrease($listing_info['Account_ID']);
                        if (!empty($listing_info['Crossed'])) {
                            $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                            foreach ($crossed_cats as $crossed_cat_id) {
                                $GLOBALS['rlCategories']->listingsDecrease($crossed_cat_id);
                            }
                        }
                    }
                    $tosend[$owner_info['Mail']]['count']++;
                }

                $success = $rlDb->query(
                    "UPDATE `{db_prefix}listings`
                     SET `Status` = '{$set_status}', `Pay_date` = NOW() WHERE `ID` = '{$listing_info['ID']}'"
                );
            }

            /* inform listing owner about status changing */
            foreach ($GLOBALS['languages'] as $language) {
                $mail_tpl[$language['Code']] = $GLOBALS['rlMail']->getEmailTemplate($action == 'activate' ? 'bulk_listing_activated' : 'bulk_listing_deactivated', $language['Code']);
            }

            foreach ($tosend as $email => $data) {
                $mail_tpl_copy = $mail_tpl[$data['lang']];

                if ($action == 'activate') {
                    $find = array('{name}', '{links}');
                    $replace = array($owners_info[$email]['Full_name'], implode('<br />', $data['links']));
                } else {
                    $find = array('{name}', '{count}');
                    $replace = array($owners_info[$email]['Full_name'], $data['count']);
                }

                $mail_tpl_copy['body'] = str_replace($find, $replace, $mail_tpl_copy['body']);
                $GLOBALS['rlMail']->send($mail_tpl_copy, $email);
            }

            if ($success) {
                $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");
            } else {
                trigger_error("Can not run mass action with listings (MySQL Fail). Action: {$action}", E_USER_ERROR);
                $GLOBALS['rlDebug']->logger("Can not run mass action with listings (MySQL Fail). Action: {$action}");
            }
        } elseif ($action == 'delete') {
            foreach ($ids as $id) {
                $GLOBALS['rlListings']->deleteListing($id, null, false);
            }

            $GLOBALS['rlCategories']->recountCategories();

            $rlDb->query(
                "UPDATE `{db_prefix}accounts` SET `Listings_count` = (
                    SELECT COUNT(*) FROM `{db_prefix}listings`
                    WHERE `Status` = 'active' AND `Account_ID` = `{db_prefix}accounts`.`ID`
                )"
            );

            $GLOBALS['rlListingTypes']->updateCountListings();
            $rlCache->updateCategories();
            $rlCache->updateListingStatistics();

            $del_action = $GLOBALS['rlActions']->action;
            $_response->script("printMessage('notice', '{$lang['mass_listings_' . $del_action]}')");
        } elseif ($action == 'renew') {
            $rlDb->query(
                "UPDATE `{db_prefix}listings`
                 SET `Pay_date` = NOW(), `Status` = 'active' WHERE FIND_IN_SET(`ID`, '" . implode(',', $ids) . "')"
            );

            $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");
        }

        $_response->script("listingsGrid.reload();");

        return $_response;
    }

    /**
     * make fetured
     *
     * @package xAjax
     *
     * @param string $ids  - listings ids
     * @param int $plan    - featured plan ID
     *
     **/
    public function ajaxMakeFeatured($ids = false, $plan = false)
    {
        global $_response, $controller, $lang, $pages, $rlListingTypes, $config, $rlSmarty, $rlValid;

        $rlValid->sql($ids);
        $ids = explode('|', $ids);
        $plan = (int) $plan;

        if (empty($ids) || empty($plan)) {
            return $_response;
        }

        $this->loadClass('Mail');
        $this->loadClass('Categories');
        $this->loadClass('Account');
        $this->loadClass('Listings');

        /* inform listing owner about status changing */
        foreach ($GLOBALS['languages'] as $language) {
            $mail_tpl[$language['Code']] = $GLOBALS['rlMail']->getEmailTemplate('listing_added_to_featured', $language['Code']);
        }

        $sql = "SELECT `T2`.*, `T1`.`Username`, `T1`.`First_name`, `T1`.`Last_name`, `T1`.`Mail` AS `Account_email`, `T4`.`Path` AS `category_path`, ";
        $sql .= "`T4`.`Type` AS `Listing_type`, `T4`.`ID` AS `Category_ID`, `T4`.`Path` AS `Category_path`, `T1`.`ID` AS `Account_ID` ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1`";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T2` ON `T1`.`ID` = `T2`.`Account_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T2`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T5` ON `T2`.`Featured_ID` = `T5`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T2`.`Category_ID` = `T4`.`ID` ";
        $sql .= "WHERE (`T2`.`ID` = '" . implode("' OR `T2`.`ID` = '", $ids) . "') AND ";
        $sql .= "(UNIX_TIMESTAMP(DATE_ADD(`T2`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) > UNIX_TIMESTAMP(NOW()) OR `T3`.`Listing_period` = 0) ";
        $sql .= "AND (`T2`.`Featured_date` IS NULL OR UNIX_TIMESTAMP(DATE_ADD(`T2`.`Featured_date`, INTERVAL `T3`.`Listing_period` DAY)) < UNIX_TIMESTAMP(NOW())) ";

        $accounts = $this->getAll($sql);

        foreach ($accounts as $key => $value) {
            $account_info = $GLOBALS['rlAccount']->getProfile((int) $value['Account_ID']);
            $mail_tpl_out = $mail_tpl[$account_info['Lang']];

            $listing_type = $rlListingTypes->types[$value['Listing_type']];
            $listing_title = $GLOBALS['rlListings']->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);

            $value['listing_title'] = $listing_title;
            $link = $this->url('listing', $value);
            $link = '<a href="' . $link . '">' . $listing_title . '</a>';

            $admin = $_SESSION['sessAdmin']['name'] ? $_SESSION['sessAdmin']['name'] : $_SESSION['sessAdmin']['user'];
            $date = date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT));

            $mail_tpl_out['body'] = str_replace(array('{name}', '{link}', '{admin}', '{date}'), array($account_info['Full_name'], $link, $admin, $date), $mail_tpl_out['body']);
            $GLOBALS['rlMail']->send($mail_tpl_out, $account_info['Mail']);
        }

        $this->query("UPDATE `{db_prefix}listings` SET `Featured_ID` = '{$plan}', `Featured_date` = NOW() WHERE `ID` = '" . implode("' OR `ID` = '", $ids) . "'");

        $_response->script("printMessage('notice', '{$lang['listing_made_featured']}');");

        $filter = $controller == 'browse' && isset($_GET['id']) ? "new Array('Category_ID||" . intval($_GET['id']) . "')" : '';

        $_response->script("listingsGrid.reload()");
        $_response->script("$('#make_featured').slideUp('fast');");

        return $_response;
    }

    /**
     * annul fetured
     *
     * @package xAjax
     *
     * @param string $ids - listings ids
     *
     **/
    public function ajaxAnnulFeatured($ids)
    {
        global $_response, $controller, $lang, $pages, $rlListingTypes, $config, $rlSmarty, $rlValid;

        $rlValid->sql($ids);
        $ids = explode('|', $ids);

        if (empty($ids)) {
            return $_response;
        }

        $this->loadClass('Mail');
        $this->loadClass('Categories');
        $this->loadClass('Account');
        $this->loadClass('Listings');

        /* inform listing owner about status changing */
        foreach ($GLOBALS['languages'] as $language) {
            $mail_tpl[$language['Code']] = $GLOBALS['rlMail']->getEmailTemplate('featured_listing_annulled', $language['Code']);
        }

        $sql = "SELECT `T2`.*, `T1`.`Username`, `T1`.`First_name`, `T1`.`Last_name`, `T1`.`Mail` AS `Account_email`, `T4`.`Path` AS `category_path`, ";
        $sql .= "`T4`.`Type` AS `Listing_type`, `T4`.`ID` AS `Category_ID`, `T4`.`Path` AS `Category_path`, `T1`.`ID` AS `Account_ID` ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1`";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T2` ON `T1`.`ID` = `T2`.`Account_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T2`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T5` ON `T2`.`Featured_ID` = `T5`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T2`.`Category_ID` = `T4`.`ID` ";
        $sql .= "WHERE (`T2`.`ID` = '" . implode("' OR `T2`.`ID` = '", $ids) . "') AND `T2`.`Featured_ID` <> '' AND ";
        $sql .= "UNIX_TIMESTAMP(DATE_ADD(`T2`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) > UNIX_TIMESTAMP(NOW()) AND ";
        $sql .= "UNIX_TIMESTAMP(DATE_ADD(`T2`.`Featured_date`, INTERVAL `T5`.`Listing_period` DAY)) > UNIX_TIMESTAMP(NOW()) ";

        $accounts = $this->getAll($sql);

        foreach ($accounts as $key => $value) {
            $account_info = $GLOBALS['rlAccount']->getProfile((int) $value['Account_ID']);
            $mail_tpl_out = $mail_tpl[$account_info['Code'] ?: $config['lang']];

            $listing_type = $rlListingTypes->types[$value['Listing_type']];
            $listing_title = $GLOBALS['rlListings']->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);

            $link = $this->url('listing', $value);
            $link = '<a href="' . $link . '">' . $listing_title . '</a>';

            $admin = $_SESSION['sessAdmin']['name'] ? $_SESSION['sessAdmin']['name'] : $_SESSION['sessAdmin']['user'];
            $date = date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT));

            $mail_tpl_out['body'] = str_replace(array('{name}', '{link}', '{admin}', '{date}'), array($account_info['Full_name'], $link, $admin, $date), $mail_tpl_out['body']);
            $GLOBALS['rlMail']->send($mail_tpl_out, $account_info['Mail']);
        }

        // update listings
        $this->query("UPDATE `{db_prefix}listings` SET `Featured_ID` = '', `Featured_date` = '' WHERE `ID` = '" . implode("' OR `ID` = '", $ids) . "'");

        if ($controller == 'browse' && isset($_GET['id'])) {
            $browse_id = (int) $_GET['id'];
            $_response->script("listingsGrid.filters = new Array(); listingsGrid.filters.push('Category_ID||{$browse_id}');");
        }

        $_response->script("
            listingsGrid.reload();
            printMessage('notice', '{$lang['listing_featured_annulled']}');
        ");

        return $_response;
    }

    /**
     * move listings
     *
     * @package xAjax
     *
     * @param string $ids   - listings ids
     * @param int $category - category ID
     *
     **/
    public function ajaxMoveListing($ids, $category)
    {
        global $_response, $controller, $lang;

        $moved = 0;
        $ids = explode('|', $ids);
        $category = (int) $category;

        if (empty($ids) || !$category) {
            return $_response;
        }

        $this->loadClass('Mail');
        foreach ($GLOBALS['languages'] as $language) {
            $mail_tpl_source[$language['Code']] = $GLOBALS['rlMail']->getEmailTemplate('listing_moved', $language['Code']);
        }

        $this->loadClass('Categories');
        $this->loadClass('Listings');

        foreach ($ids as $id) {
            $id = (int) $id;

            $sql = "SELECT `T1`.*, IF(UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) > UNIX_TIMESTAMP(NOW()), 1, 0) `Paid`, ";
            $sql .= "`T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Mail`, `T2`.`Lang` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = '{$id}'";
            $listing_info = $this->getRow($sql);

            $listing_info['Lang'] = $listing_info['Lang'] ?: RL_LANG_CODE;
            $mail_tpl             = $mail_tpl_source[$listing_info['Lang']];

            if ($category != $listing_info['Category_ID']) {
                // update listings
                $this->query("UPDATE `{db_prefix}listings` SET `Category_ID` = '{$category}' WHERE `ID` = '{$id}'");

                if ($GLOBALS['rlListings']->isActive($id)) {
                    $GLOBALS['rlCategories']->listingsDecrease($listing_info['Category_ID']);
                    $GLOBALS['rlCategories']->listingsIncrease($category);
                }

                $name = empty($listing_info['First_name']) && empty($listing_info['Last_name']) ? $listing_info['Username'] : $listing_info['First_name'] . ' ' . $listing_info['Last_name'];
                $listing_type = $this->getOne("Type", "`ID` = " . $category, "categories");

                $listing_title = $GLOBALS['rlListings']->getListingTitle($listing_info['Category_ID'], $listing_info, $listing_type);

                $category_info = $GLOBALS['rlCategories']->getCategory($category);

                if ($listing_info['Paid'] && $listing_info['Status'] == 'active') {
                    $cat_path = $this->getOne('Path', "`ID` = '{$category}'", 'categories');
                    $listing_info['Path'] = $cat_path;
                    $link = $this->url('listing', $listing_info, $listing_info['Lang']);
                } else {
                    $link = RL_URL_HOME . ($listing_info['Lang'] != $GLOBALS['config']['lang'] ? $listing_info['Lang'] . "/" : '');
                    $my_listings_path = $this->getOne('Path', "`Key` = 'my_" . $listing_type . "'", 'pages');
                    $link .= $GLOBALS['config']['mod_rewrite'] ? $my_listings_path . '.html' : 'index.php?page=' . $my_listings_path;
                }

                $link = '<a href="' . $link . '">' . $link . '</a>';

                $mail_tpl['body'] = str_replace(
                    array('{name}', '{listing_title}', '{category}', '{link}'),
                    array(trim($name), $listing_title, $category_info['name'], $link),
                    $mail_tpl['body']
                );

                $GLOBALS['rlMail']->send($mail_tpl, $listing_info['Mail']);

                $moved++;
            }
        }

        if ($moved > 0) {
            if ($controller == 'browse' && isset($_GET['id'])) {
                $_response->redirect(RL_URL_HOME . ADMIN . '/index.php?controller=browse&id=' . $_GET['id']);
                return $_response;
            }

            $_response->script("
                listingsGrid.reload();
                listingsGrid.checkboxColumn.clearSelections();
                printMessage('notice', '{$lang['listing_moved']}');
                $('#move_area').slideUp();

                $('#move_area a.button').text('{$lang['move']}');
                $('#move_area ul.select-category select:first').val('').trigger('change');
            ");
        } else {
            $_response->script("
                $('#move_area a.button').text('{$lang['move']}');
                printMessage('error', '{$lang['move_listing_failed']}');
            ");
        }
        $_response->script("if(typeof move_clicked !='undefined') { move_clicked = false; }");

        return $_response;
    }

    /**
     * change listing status
     *
     * @param integer $id
     * @param string $status
     * @param boolean $membership_upgarde - optional, if need upgrade membership plan
     *
     * @return boolean
     */
    public function changeListingStatus($id = 0, $status = '', $membership_upgarde = false)
    {
        $id = (int) $_REQUEST['id'];
        $status = $GLOBALS['rlValid']->xSql($status);

        if (!$id || !$status) {
            return false;
        }

        // get listing detais
        $sql = "SELECT `T1`.*, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Payed`, `T1`.`Crossed`, `T1`.`Status`, `T1`.`Plan_ID`, `T4`.`Type` AS `Listing_type`, `T4`.`Path`, ";
        if ($GLOBALS['config']['membership_module']) {
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T5`.`Plan_period`, `T3`.`Listing_period`) AS `Listing_period`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', 'account', `T3`.`Type`) AS `Plan_type`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T5`.`Price`, `T3`.`Price`) AS `Plan_price`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T5`.`Featured_listing`, `T3`.`Featured`) AS `Featured`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T5`.`Advanced_mode`, `T3`.`Advanced_mode`) AS `Advanced_mode`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', '', `T3`.`Cross`) AS `Cross` ";
        } else {
            $sql .= " `T3`.`Listing_period`, `T3`.`Type` AS `Plan_type`,`T3`.`Price` AS `Plan_price`, `T3`.`Featured`, `T3`.`Advanced_mode`, `T3`.`Cross` ";
        }
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T5` ON `T1`.`Plan_ID` = `T5`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$id}'";

        $listing_info = $this->getRow($sql);

        // get account info
        $account_info = $GLOBALS['rlAccount']->getProfile((int) $listing_info['Account_ID']);

        $updateData = array(
            'fields' => array(
                'Status' => $status,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        if ($GLOBALS['rlActions']->updateOne($updateData, 'listings')) {
            // upgarde account
            if ($listing_info['Plan_type'] == 'account' && $account_info['Plan_ID'] && $membership_upgarde) {
                $GLOBALS['rlAccount']->upgrade($account_info['ID'], $account_info['Plan_ID'], true);
            }
            return true;
        }

        return false;
    }

    /**
     * @since 4.5.0
     * delete file | ADMIN PANEL
     *
     * @package xAjax
     *
     * @param string $video_id - video id
     *
     **/
    public function ajaxDelVideoFileAP($video_id)
    {
        global $_response, $rlSmarty, $lang, $video_allow;

        $video_id = (int) $video_id;

        if (!$video_id) {
            return $_response;
        }

        $video = $this->fetch(array('Listing_ID', 'Original', 'Thumbnail'), array('ID' => $video_id), null, 1, 'listing_photos', 'row');

        $sql = "SELECT `T1`.`Account_ID`, `T2`.`Video`, `T2`.`Video_unlim` FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$video['Listing_ID']}' LIMIT 1";
        $listing_info = $this->getRow($sql);

        if ($video['Original'] != 'youtube') {
            unlink(RL_FILES . $video['Original']);
            unlink(RL_FILES . $video['Thumbnail']);
        }

        $this->query("DELETE FROM `{db_prefix}listing_photos` WHERE `ID` = '{$video_id}' LIMIT 1");
        $_response->script("$('#remove_{$video_id}').parent().fadeOut()");

        /* get listing video */
        $this->setTable('listing_photos');
        $videos = $this->fetch(
            array('ID', 'Photo', 'Thumbnail', 'Original'),
            array('Listing_ID' => $video['Listing_ID'], 'Type' => 'Video'),
            "ORDER BY `Position`"
        );
        $rlSmarty->assign_by_ref('videos', $videos);

        if (empty($videos)) {
            $_response->script("
                $('#video_area').html(\"<div class='grey_middle'>{$lang['no_video_uploaded']}</div>\");
            ");
        }
        if (count($videos) < $listing_info['Video'] && !$listing_info['Video_unlim']) {
            $_response->script("$('#protect').slideDown().prev().slideUp();");
        }
        $_response->script("printMessage('notice', '{$lang['item_deleted']}');");

        return $_response;
    }

    /**
     * @since 4.5.1
     *
     * Edit listing description
     *
     * @param int $id - picture id
     * @param int $listingID - listing id
     * @param string $description - listing description
     *
     **/
    public function editDescription($id = false, $listingID = false, $description = false)
    {
        if (!$id) {
            $GLOBALS['rlDebug']->logger('rlListingsAdmin::editDescription() failed, no picture ID specified');
        }

        if (!$listingID) {
            $GLOBALS['rlDebug']->logger('rlListingsAdmin::editDescription() failed, no listing ID specified');
        }

        $update = array(
            'fields' => array('Description' => $description),
            'where'  => array('Listing_ID' => $listingID, 'ID' => $id),
        );

        $GLOBALS['reefless']->loadClass('Actions');
        return $GLOBALS['rlActions']->updateOne($update, 'listing_photos');
    }
}
