<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ACCOUNTS.INC.PHP
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

use Flynax\Classes\Agencies;
use Flynax\Utils\Valid;

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    /* load system lib */
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = Valid::escape($_GET['type']);
        $field = Valid::escape($_GET['field']);
        $value = Valid::escape(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = Valid::escape($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtAccountsUpdate');

        if ($field == 'Status' && $id) {
            $reefless->loadClass('Account');
            $reefless->loadClass('Common');

            /* get account info */
            $account_info = $rlAccount->getProfile($id);

            if ($account_info['Status'] != $value) {
                if ($value == 'active') {
                    $updateData['fields']['Password_tmp'] = '';
                }

                /* inform user about status changing of her account */
                $reefless->loadClass('Mail');
                $mail_tpl = $rlMail->getEmailTemplate($value == 'active' ? 'account_activated' : 'account_deactivated', $account_info['Lang']);
                $mail_tpl['body'] = str_replace('{name}', $account_info['Full_name'], $mail_tpl['body']);
                $rlMail->send($mail_tpl, $account_info['Mail']);
            }
        }

        $rlActions->updateOne($updateData, 'accounts');

        // Manage status of account listings
        if ($field == 'Status' && $id) {
            $reefless->loadClass('Listings');
            $rlListings->listingStatusControl(array('Account_ID' => $id), $value);
        }

        exit;
    }

    $agencies = new Agencies();

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $search = Valid::escape($_GET['search']);
    $sort = $_GET['sort'] == 'Type_name' ? 'Type' : $_GET['sort'];
    $sort = Valid::escape($sort);
    $sortDir = Valid::escape($_GET['dir']);
    $date_from = Valid::escape($_GET['date_from']);
    $date_to = Valid::escape($_GET['date_to']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.`ID`, ";
    $sql .= "CONCAT(`T1`.`First_name`, ' ', `T1`.`Last_name`) AS `Name`, `T1`.`Username`, `T1`.`Type`, ";
    $sql .= "`T1`.`Status`, `T1`.`Mail`, `T1`.`Date`, `T1`.`Photo`, `T1`.`Plan_ID`, ";
    if ($GLOBALS['config']['membership_module']) {
        $sql .= "`T2`.`Key` AS `Plan_key`, `T2`.`Price`, `T2`.`Plan_period`, `T2`.`Listing_number`, ";
    }
    $sql .= "(SELECT COUNT(`ID`) FROM `{db_prefix}listings` WHERE `Account_ID` = `T1`.`ID` AND `Status` <> 'trash') AS `Listings_count`, ";
    $sql .= "(SELECT COUNT(`ID`) FROM `{db_prefix}tmp_categories` WHERE `Account_ID` = `T1`.`ID` AND `Status` <> 'trash') AS `Custom_categories_count` ";
    $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
    if ($GLOBALS['config']['membership_module']) {
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
    }
    $sql .= "WHERE 1 ";

    $username  = Valid::escape($_GET['username']);
    $first_name = Valid::escape($_GET['first_name']);
    $last_name = Valid::escape($_GET['last_name']);
    $email     = Valid::escape($_GET['email']);
    $agency    = (int) $_GET['agency'];

    if ($_GET['invites'] && $agency) {
        $invites = $agencies->getInvites($agency, [], ($start / $limit) + 1, $limit);

        $rlHook->load('apExtInvitesData', $agency, $invites);

        echo json_encode(['total' => $agencies->getCountInvites(), 'data' => $invites]);
        exit;
    }

    if ($search) {
        if (!empty($username)) {
            $sql .= " AND `T1`.`Username` LIKE '%{$username}%' ";
        }
        if (!empty($first_name)) {
            $sql .= " AND `T1`.`First_name` LIKE '%{$first_name}%' ";
        }
        if (!empty($last_name)) {
            $sql .= " AND `T1`.`Last_name` LIKE '%{$last_name}%' ";
        }
        if (!empty($email)) {
            $sql .= " AND `T1`.`Mail` LIKE '%{$email}%' ";
        }
        if (!empty($agency)) {
            $sql .= " AND `T1`.`Agency_ID` = {$agency} ";
        }
        if (!empty($_GET['account_type'])) {
            $sql .= " AND `T1`.`Type` = '{$_GET['account_type']}' ";
        }
        if (!empty($_GET['search_status'])) {
            $status = $_GET['search_status'];

            if (in_array($status, array('active', 'approval', 'pending', 'incomplete'))) {
                $sql .= " AND `T1`.`Status` = '{$status}' ";
            } elseif ($status == 'new') {
                $new_period = empty($config['new_period']) ? 1 : $config['new_period'];
                $sql .= " AND `T1`.`Status` <> 'trash' AND UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL {$new_period} DAY)) > UNIX_TIMESTAMP(NOW()) ";
            }
        }

        if (!empty($date_from)) {
            $sql .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Date`)) >= UNIX_TIMESTAMP('{$date_from}') ";
        }
        if (!empty($date_to)) {
            $sql .= "AND UNIX_TIMESTAMP(DATE(`T1`.`Date`)) <= UNIX_TIMESTAMP('{$date_to}') ";
        }
    }

    $sql .= "AND `T1`.`Status` <> 'trash' ORDER BY `T1`.`{$sort}` {$sortDir} LIMIT {$start}, {$limit}";

    $rlHook->load('apExtAccountsSql');

    $data = $rlDb->getAll($sql);

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");
    $count = $count['count'];

    foreach ($data as $key => $value) {
        if ($GLOBALS['config']['membership_module']) {
            $data[$key]['Plan_name'] = $GLOBALS['lang']['membership_plans+name+' . $value['Plan_key']];

            // plan info
            $price = ($config['system_currency_position'] == 'before' ? $config['system_currency'] : '') . $value['Price'] . ($config['system_currency_position'] == 'after' ? ' ' . $config['system_currency'] : '');
            $plan_info = "
                <table class='info'>
                <tr><td>{$lang['price']}:</td><td> <b>{$price}</b><br /></td></tr>
                <tr><td>{$lang['days']}:</td><td> <b>{$value['Plan_period']}</b></td></tr>
                <tr><td>{$lang['listing_number']}:</td><td> <b>{$value['Listing_number']}</b></td></tr>";

            $plan_info .= "</table>";

            $data[$key]['Plan_info'] = $plan_info;
        }
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
        $data[$key]['Type_name'] = $lang['account_types+name+' . $data[$key]['Type']];
        $name = trim($data[$key]['Name']);
        $data[$key]['Name'] = empty($name) ? $lang['not_available'] : $name;

        $src = $value['Photo'] ? RL_FILES_URL . $value['Photo'] : RL_URL_HOME . ADMIN . '/img/no-account.png';
        $data[$key]['thumbnail'] = '<img style="border: 2px white solid;width: 70px" alt="' . $listingTitle . '" title="' . $listingTitle . '" src="' . $src . '" />';

        if ($agencies->isAgency($value)) {
            $value['Agents_count']   = $agencies->getAgentsCount((int) $value['ID'], false);
            $value['Listings_count'] += $agencies->getAgentsListingsCount((int) $value['ID'], false);
        }

        $fields_html = '<div style="margin: 0 0 0 10px"><table>';

        // add listing count
        $fields_html .= '<tr><td style="padding: 0 5px 4px;">';
        $fields_html .= $lang['listings'] . ':</td><td><b><a href="';
        $fields_html .= RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=';
        $fields_html .= $value['ID'] . '#listings">' . $value['Listings_count'] . '</a></b></td></tr>';

        if (isset($value['Agents_count'])) {
            $fields_html .= '<tr><td style="padding: 0 5px 4px;">';
            $fields_html .= $lang['agents'] . ':</td><td><b><a href="';
            $fields_html .= RL_URL_HOME . ADMIN . '/index.php?controller=accounts&action=view&userid=';
            $fields_html .= $value['ID'] . '#agents">' . $value['Agents_count'] . '</a></b></td></tr>';
        }

        // add custom categories counter
        if ($value['Custom_categories_count']) {
            $fields_html .= '<tr><td style="padding: 0 5px 4px;">' . $lang['admin_controllers+name+custom_categories'] . ':</td><td><b><a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=custom_categories">' . $value['Custom_categories_count'] . '</a></b></td></tr>';
        }

        $fields_html .= '</table></div>';

        $data[$key]['fields'] = $fields_html;
    }

    $rlHook->load('apExtAccountsData');

    $output['total'] = $count;
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpAccountsTop');
    if ($config['membership_module']) {
        $reefless->loadClass('MembershipPlansAdmin', 'admin');
    }

    /* remote listing activation */
    if ($_GET['action'] == 'remote_activation' && $_GET['id'] && $_GET['hash'] && !$_POST['xjxfun']) {
        $reefless->loadClass('Account');

        $remote_id = (int) $_GET['id'];
        $remote_hash = $_GET['hash'];
        $remote_activation_info = $rlAccount->getProfile($remote_id);

        if ($remote_activation_info['ID'] == $remote_id) {
            $activation_update = array(
                'fields' => array('Status' => 'active'),
                'where'  => array('ID' => $remote_id),
            );

            if ($rlActions->updateOne($activation_update, 'accounts')) {
                $reefless->loadClass('Mail');
                $mail_tpl = $rlMail->getEmailTemplate('account_activated', $remote_activation_info['Lang']);
                $mail_tpl['body'] = str_replace('{name}', $remote_activation_info['Full_name'], $mail_tpl['body']);
                $rlMail->send($mail_tpl, $remote_activation_info['Mail']);

                $rlCache->updateStatistics();

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['notice_remote_activation_activated_account']);
            }
        } else {
            $reefless->loadClass('Notice');
            $errors[] = $lang['notice_remote_account_activation_deny'];

            $rlSmarty->assign_by_ref('errors', $errors);
        }
        unset($_GET['action']);
    } else {
        /* assing statuses */
        $statuses = array('new', 'active', 'pending', 'incomplete', 'approval');
        $rlSmarty->assign_by_ref('statuses', $statuses);

        /* assign languages list */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        /* additional bread crumb step */
        if ($_GET['action']) {
            switch ($_GET['action']) {
                case 'add':
                    $bcAStep = $lang['add_account'];
                    break;
                case 'edit':
                    $bcAStep = $lang['edit_account'];
                    break;
                case 'view':
                    $bcAStep = $lang['view_account'];
                    break;
            }
        }

        /* define RL_TPL_BASE */
        define('RL_TPL_BASE', RL_URL_HOME . ADMIN . '/');

        /* get account types */
        $reefless->loadClass('Account');
        $account_types = $rlAccount->getAccountTypes('visitor');
        $rlSmarty->assign_by_ref('account_types', $account_types);

        /**
         * @since v4.4
         */
        $rlHook->load('apPhpRegistrationBegin');

        if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
            $reefless->loadClass('Common');
            $reefless->loadClass('Categories');
            $reefless->loadClass('Mail');
            $reefless->loadClass('Resize');
            $reefless->loadClass('MembershipPlansAdmin', 'admin');

            if ($config['membership_module']) {
                $plans = $rlMembershipPlansAdmin->getPlans();
                $rlSmarty->assign_by_ref('plans', $plans);
            }

            // Get domain name and scheme
            $domain = $domain_info['host'];
            $scheme = $domain_info['scheme'];

            $rlSmarty->assign_by_ref('domain', $domain);
            $rlSmarty->assign_by_ref('scheme', $scheme);

            // link to add a membership plan
            $add_plan_link = RL_URL_HOME . ADMIN . '/index.php?controller=membership_plans&action=add';
            $rlSmarty->assign_by_ref('add_plan_link', $add_plan_link);

            $account_id = (int) $_GET['account'];

            // get current account info
            $account_info = $rlAccount->getProfile($account_id);
            $rlSmarty->assign_by_ref('aInfo', $account_info);

            // get a list with agreement fields
            $rlSmarty->assign('agreement_fields', $rlAccount->getAgreementFields());

            if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
                /* get account fields */
                $account_fields = $rlAccount->getFields($account_info['Account_type_ID']);
                $account_fields = $rlLang->replaceLangKeys($account_fields, 'account_fields', array('name', 'description'));
                $account_fields = $rlCommon->fieldValuesAdaptation($account_fields, 'account_fields');
                $rlSmarty->assign_by_ref('fields', $account_fields);

                if (!empty($account_fields)) {
                    foreach ($account_info as $i_index => $i_val) {
                        $search_fields[$i_index] = $i_index;
                    }

                    foreach ($account_fields as $key => $value) {
                        if ($account_info[$account_fields[$key]['Key']] != '') {
                            switch ($account_fields[$key]['Type']) {
                                case 'mixed':
                                    $df_item = false;
                                    $df_item = explode('|', $account_info[$account_fields[$key]['Key']]);

                                    $_POST['f'][$key]['value'] = $df_item[0];
                                    $_POST['f'][$key]['df'] = $df_item[1];
                                    break;

                                case 'date':
                                    if ($account_fields[$key]['Default'] == 'single') {
                                        $_POST['f'][$key] = $account_info[$search_fields[$account_fields[$key]['Key']]];
                                    } elseif ($account_fields[$key]['Default'] == 'multi') {
                                        $_POST['f'][$key]['from'] = $account_info[$account_fields[$key]['Key']];
                                        $_POST['f'][$key]['to'] = $account_info[$account_fields[$key]['Key'] . '_multi'];
                                    }
                                    break;

                                case 'phone':
                                    $_POST['f'][$key] = $reefless->parsePhone($account_info[$account_fields[$key]['Key']]);
                                    break;

                                case 'price':
                                    $price = false;
                                    $price = explode('|', $account_info[$account_fields[$key]['Key']]);

                                    $_POST['f'][$key]['value'] = $price[0];
                                    $_POST['f'][$key]['currency'] = $price[1];
                                    break;

                                case 'unit':
                                    $unit = false;
                                    $unit = explode('|', $account_info[$account_fields[$key]['Key']]);

                                    $_POST['f'][$key]['value'] = $unit[0];
                                    $_POST['f'][$key]['unit'] = $unit[1];
                                    break;

                                case 'checkbox':
                                    $ch_items = null;
                                    $ch_items = explode(',', $account_info[$account_fields[$key]['Key']]);

                                    $_POST['f'][$key] = $ch_items;
                                    unset($ch_items);
                                    break;

                                case 'accept':
                                    unset($account_fields[$key]);
                                    break;

                                case 'text':
                                case 'textarea':
                                    if ($account_fields[$key]['Multilingual'] && count($GLOBALS['languages']) > 1) {
                                        $_POST['f'][$key] = $reefless->parseMultilingual($account_info[$account_fields[$key]['Key']]);
                                    } else {
                                        $_POST['f'][$key] = $account_info[$account_fields[$key]['Key']];
                                    }
                                    break;

                                default:
                                    $_POST['f'][$key] = $account_info[$search_fields[$account_fields[$key]['Key']]];
                                    break;
                            }
                        }
                    }

                    $rlSmarty->assign_by_ref('fields', $account_fields);
                }

                if (!$_POST['fromPost']) {
                    $_POST['profile']['username'] = $account_info['Username'];
                    $_POST['profile']['mail'] = $account_info['Mail'];
                    $_POST['profile']['display_email'] = $account_info['Display_email'];
                    $_POST['profile']['first_name'] = $account_info['First_name'];
                    $_POST['profile']['last_name'] = $account_info['Last_name'];
                    $_POST['profile']['type'] = $account_info['Account_type_ID'];
                    $_POST['profile']['status'] = $account_info['Status'];
                    $_POST['profile']['lang'] = $account_info['Lang'];
                    $_POST['profile']['plan'] = $account_info['Plan_ID'];

                    foreach ($rlAccount->getAgreementFields() as $ag_field) {
                        $_POST['profile']['accept'][$ag_field['Key']] = 1;
                    }
                }

                $rlHook->load('apPhpAccountsPost');
            }

            if (isset($_POST['form_submit'])) {
                $errors = array();

                /* load the utf8 lib */
                loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

                $profile_data             = $_POST['profile'];
                $profile_data['username'] = trim($profile_data['username']);
                $account_data             = $_POST['f'];
                $selected_atype           = $account_types[$profile_data['type']]['Key'];

                /* get selected account type fields */
                $fields = $rlAccount->getFields($profile_data['type']);
                $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name', 'description'));
                $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');

                $fields = array_filter($fields, function ($item) {
                    if ($item['Type'] != 'accept') {
                        return $item;
                    }
                });

                $rlSmarty->assign_by_ref('fields', $fields);

                // emulate existing data if user get a error about not filled data
                if (!empty($fields) && $account_info) {
                    foreach ($account_info as $i_index => $i_val) {
                        $search_fields[$i_index] = $i_index;
                    }

                    foreach ($fields as $key => $value) {
                        if ($account_info[$fields[$key]['Key']] != '') {
                            switch ($fields[$key]['Type']) {
                                case 'image':
                                    $_POST['f_sys_exist'][$key] = $account_info[$search_fields[$fields[$key]['Key']]];
                                    break;
                            }
                        }
                    }
                }

                //check email
                if (!Valid::isEmail($profile_data['mail'])) {
                    $errors[] = $lang['notice_bad_email'];
                    $error_fields[] = 'profile[mail]';
                }

                // check dublicate e-mail
                if ($_GET['action'] == 'edit') {
                    $add_where = "AND `Mail` <> '{$account_info['Mail']}'";
                }

                $email_exist = $rlDb->fetch(
                    array('Mail', 'Status'),
                    array('Mail' => $profile_data['mail']),
                    $add_where,
                    null,
                    'accounts',
                    'row'
                );

                if (!empty($email_exist)) {
                    $exist_error = str_replace(
                        '{email}',
                        '<b>"' . $profile_data['mail'] . '"</b>',
                        $lang['notice_account_email_exist']
                    );

                    if ($email_exist['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = 'profile[mail]';
                }

                /* check type */
                if (empty($profile_data['type'])) {
                    $errors[] = $lang['notice_choose_account_type'];
                    $error_fields[] = 'profile[type]';
                }

                if ($config['account_login_mode'] == 'email') {
                    if ($_GET['action'] == 'add') {
                        $exp_email = explode('@', $profile_data['mail']);
                        $username = $rlAccount->makeUsernameUnique($exp_email[0]);
                        $profile_data['username'] = $username;
                    } else {
                        $profile_data['username'] = $account_info['Username'];
                    }
                } else {
                    // check username lenght
                    if (strlen($profile_data['username']) < 3) {
                        $errors[] = str_replace(
                            '{field}',
                            '<span class="field_error">"' . $lang['username'] . '"</span>',
                            $lang['notice_reg_length']
                        );

                        $error_fields[] = 'profile[username]';
                    }

                    // check account exist (in add mode only)
                    if ($_GET['action'] == 'add') {
                        $account_exist = $rlDb->fetch(
                            array('Username', 'Status'),
                            array('Username' => $profile_data['username']),
                            null,
                            null,
                            'accounts',
                            'row'
                        );

                        if (!empty($account_exist)) {
                            $exist_error = str_replace(
                                '{username}',
                                "<b>\"" . $profile_data['username'] . "\"</b>",
                                $lang['notice_account_exist']
                            );

                            if ($account_exist['Status'] == 'trash') {
                                $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                            }

                            $errors[]       = $exist_error;
                            $error_fields[] = 'profile[username]';
                        }

                        if (!$rlAccount->validateUsername($profile_data['username'])) {
                            $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['username'] . '"</span>', $lang['notice_field_not_valid']);
                            $error_fields[] = 'profile[username]';
                        }
                    }
                }

                /* check password */
                if ($_GET['action'] == 'add' || ($_GET['action'] == 'edit' && !empty($profile_data['password']))) {
                    if (empty($profile_data['password'])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['password'] . "</b>", $lang['notice_field_empty']);
                        $error_fields[] = 'profile[password]';
                    }
                    if (empty($profile_data['password_repeat'])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['password_repeat'] . "</b>", $lang['notice_field_empty']);
                        $error_fields[] = 'profile[password_repeat]';
                    }

                    /* check password's match */
                    if ($profile_data['password'] != $profile_data['password_repeat']) {
                        $errors[] = $lang['notice_pass_bad'];
                        $error_fields[] = 'profile[password]';
                    }
                }

                $location = $profile_data['location'];
                if ($account_types[$profile_data['type']]['Own_location']) {
                    /* validate */
                    $location = trim($location);
                    $wildcard_deny = explode(',', $config['account_wildcard_deny']);
                    $rlDb->setTable('pages');
                    $deny_pages_tmp = $rlDb->fetch(array('Path'), null, "WHERE `Path` <> ''");
                    foreach ($deny_pages_tmp as $deny_page) {
                        $wildcard_deny[] = $deny_page['Path'];
                    }
                    unset($deny_pages_tmp);

                    $all_prefix = !empty($lang['alphabet_characters']) ? explode(',', $lang['alphabet_characters']) : '';
                    $all_prefix = $all_prefix[0] ? strtolower($all_prefix[0]) : '';
                    $wildcard_deny = array_merge($wildcard_deny, array(ADMIN, $all_prefix));

                    preg_match('/[\W]+/', str_replace(array('-', '_'), '', $location), $matches);

                    $add_where = '';

                    if ($_GET['action'] == 'edit') {
                        $add_where = "AND `ID` <> '{$account_id}'";
                    }

                    if (empty($location) || !empty($matches)) {
                        $errors[] = $lang['personal_address_error'];
                        $error_fields[] = 'profile[location]';
                    } else if (strlen($location) < 3) {
                        $errors[] = $lang['personal_address_length_error'];
                        $error_fields[] = 'profile[location]';
                    }
                    /* check for uniqueness */
                    else if (in_array($location, $wildcard_deny)
                        || $address_exist = $rlDb->fetch(
                            array('ID', 'Status'),
                            array('Own_address' => $location),
                            $add_where,
                            null,
                            'accounts',
                            'row'
                        )
                    ) {
                        $exist_error = $lang['personal_address_in_use'];

                        if ($address_exist['Status'] == 'trash') {
                            $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                        }

                        $errors[]       = $exist_error;
                        $error_fields[] = 'profile[location]';
                    }
                }

                if ($config['membership_module']) {
                    $is_account_featured = false;
                    $plan_id = (int) $profile_data['plan'];
                    $plan = $plans[$plan_id];

                    if (isset($plan['Services']['featured'])) {
                        $is_account_featured = true;
                    }
                }

                // upload thumbnail of account
                if ($_FILES['thumbnail']['name'] && is_readable($_FILES['thumbnail']['tmp_name'])) {
                    // validate the type of image
                    if (!preg_match('/(\.|\/)(gif|jpe?g|png|webp)$/i', $_FILES['thumbnail']['name'])) {
                        $errors[] = Flynax\Utils\StringUtil::replaceAssoc(
                            $lang['error_wrong_file_type'],
                            array(
                                '{ext}'   => explode('.', strtolower($_FILES['thumbnail']['name']))[1],
                                '{types}' => 'gif/jpeg/png/webp',
                            )
                        );
                    } else {
                        (new Flynax\Classes\ProfileThumbnailUpload($account_info))->init();
                    }
                }

                // check accepted agreement fields
                if ($_GET['action'] == 'add' && $selected_atype) {
                    foreach ($rlAccount->getAgreementFields($selected_atype, true) as $ag_field_key => $ag_field) {
                        if (!$profile_data['accept'][$ag_field_key]) {
                            $errors[] = str_replace(
                                '{field}',
                                $lang['pages+name+' . $ag_field['Default']],
                                $lang['notice_field_not_accepted']
                            );
                            $error_fields[] = "profile[accept][{$ag_field_key}]";
                        }
                    }
                }

                if ($back_errors = $rlCommon->checkDynamicForm($account_data, $fields, 'f', true)) {
                    foreach ($back_errors as $error) {
                        $errors[] = $error;
                    }

                    if ($rlCommon->error_fields) {
                        $error_fields = $rlCommon->error_fields;
                        $rlCommon->error_fields = false;
                    }
                }

                $rlHook->load('apPhpAccountsValidate');

                if (!empty($errors)) {
                    $rlSmarty->assign_by_ref('errors', $errors);
                } else {
                    /* add/edit action */
                    if ($_GET['action'] == 'add') {
                        /* personal address handler */
                        $profile_data['location'] = trim($profile_data['location']);

                        if (!$account_types[$profile_data['type']]['Own_location']) {
                            /* load the utf8 lib */
                            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

                            $username = $profile_data['username'];
                            if (!utf8_is_ascii($username)) {
                                $username = utf8_to_ascii($username);
                            }

                            $profile_data['location'] = $rlSmarty->str2path($profile_data['username']);
                        }

                        $rlHook->load('apPhpAccountsBeforeAdd');

                        // save account details
                        if ($action = $rlAccount->registration($profile_data['type'], $profile_data, $account_data, $fields)) {
                            if ($config['membership_module'] && $profile_data['plan']) {
                                $plan_info = $rlMembershipPlansAdmin->getPlan((int) $profile_data['plan']);
                                if ($plan_info) {
                                    $plan_using_insert = array(
                                        'Account_ID'       => $_SESSION['registration']['account_id'],
                                        'Plan_ID'          => (int) $profile_data['plan'],
                                        'Listings_remains' => (int) $plan_info['Listing_number'],
                                        'Standard_remains' => (int) $plan_info['Standard_listings'],
                                        'Featured_remains' => (int) $plan_info['Featured_listings'],
                                        'Type'             => 'account',
                                    );

                                    if ($plan_info['Limit'] > 0) {
                                        $plan_using_insert['Count_used'] = 1;
                                    }
                                    $rlActions->insertOne($plan_using_insert, 'listing_packages');
                                }
                            }
                            $rlHook->load('apPhpAccountsAfterAdd');

                            $rlCache->updateStatistics();

                            $message = $lang['notice_reg_complete'];
                            $aUrl = array("controller" => $controller);
                        } else {
                            trigger_error("Can't add new account (MYSQL problems)", E_WARNING);
                            $rlDebug->logger("Can't add new account (MYSQL problems)");
                        }
                    } elseif ($_GET['action'] == 'edit') {
                        $update_data = array(
                            'mail'          => $profile_data['mail'],
                            'location'      => $profile_data['location'],
                            'display_email' => $profile_data['display_email'],
                            'type'          => $rlDb->getOne('Key', "`ID` = '{$profile_data['type']}'", 'account_types'),
                            'status'        => $profile_data['status'],
                            'lang'          => $profile_data['lang'],
                            'plan' =>       $profile_data['plan'],
                            'featured' =>   $is_account_featured,
                        );

                        if (!empty($profile_data['password'])) {
                            require_once RL_CLASSES . "rlSecurity.class.php";
                            $update_data['password'] = FLSecurity::cryptPassword($profile_data['password']);
                        }

                        $rlHook->load('apPhpAccountsBeforeEdit');

                        $action = $rlAccount->editProfile($update_data, (int) $_GET['account']);
                        $rlAccount->editAccount($account_data, $fields, (int) $_GET['account']);

                        $rlHook->load('apPhpAccountsAfterEdit');

                        $message = $lang['notice_account_edited'];
                        $aUrl = array(
                            'controller' => $controller,
                        );

                        /* inform user about status changing of her account */
                        if ($profile_data['status'] != $account_info['Status']) {
                            $reefless->loadClass('Mail');

                            $mail_tpl = $rlMail->getEmailTemplate($profile_data['status'] == 'active' ? 'account_activated' : 'account_deactivated', $profile_data['lang']);

                            $mail_tpl['body'] = str_replace('{name}', $account_info['Full_name'], $mail_tpl['body']);
                            $rlMail->send($mail_tpl, $account_info['Mail']);

                            /* deactivate account listings */
                            $reefless->loadClass('Listings');
                            $rlListings->listingStatusControl(array("Account_ID" => (int) $_GET['account']), $profile_data['status']);
                        }
                    }

                    if ($action) {
                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($message);
                        $reefless->redirect($aUrl);
                    }
                }
            }
            $rlXajax->registerFunction(array('getAccountFields', $rlAdmin, 'ajaxGetAccountFields'));
            $rlXajax->registerFunction(array('updateAccountFields', $rlAdmin, 'ajaxUpdateAccountFields'));
            $rlXajax->registerFunction(array('delAccountFile', $rlAccount, 'ajaxDelAccountFile'));
        } elseif ($_GET['action'] == 'view') {
            $account_id = (int) $_GET['userid'];

            $reefless->loadClass('Account');
            $reefless->loadClass('Message');
            $reefless->loadClass('Plan');

            $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwnerAP'));

            /* get categories */
            $sections = $rlCategories->getCatTree(0, false, true);
            $rlSmarty->assign_by_ref('sections', $sections);

            /* get seller information */
            $seller_info = $rlAccount->getProfile($account_id);
            $rlSmarty->assign_by_ref('seller_info', $seller_info);

            /* populate tabs */
            $tabs = array(
                'seller'   => array(
                    'key'  => 'seller',
                    'name' => $lang['account_information'],
                ),
                'listings' => array(
                    'key'  => 'listings',
                    'name' => $lang['account_listings'],
                )
            );

            if (isset($seller_info['Agents_count'])) {
                $tabs['listings']['name'] = $lang['agency_listings'];
                $tabs['agents']  = ['key' => 'agents', 'name' => $lang['agents']];
                $tabs['invites'] = ['key' => 'invites', 'name' => $lang['invites']];
            }

            $rlSmarty->assign_by_ref('tabs', $tabs);

            if (!$seller_info) {
                $rlSmarty->assign('alerts', array('Requested account not found'));
            } else {
                /* get amenties */
                if ($config['map_amenities']) {
                    $rlDb->setTable('map_amenities');
                    $amenities = $rlDb->fetch(array('Key', 'Default'), array('Status' => 'active'), "ORDER BY `Position`");
                    $amenities = $rlLang->replaceLangKeys($amenities, 'map_amenities', array('name'));
                    $rlSmarty->assign_by_ref('amenities', $amenities);
                }

                /* define fields for Google Map */
                $location = $rlAccount->mapLocation;
                if (!empty($location)) {
                    $rlSmarty->assign_by_ref('location', $location);
                }

                if (!$config['map_module'] || !$location) {
                    unset($tabs['map']);
                }

                /* get plans */
                $plans = $rlPlan->getPlans(array('listing', 'package', 'featured_direct'));
                $rlSmarty->assign_by_ref('plans', $plans);

                /* get featured plans */
                $featured_plans = $rlPlan->getPlans('featured');
                $rlSmarty->assign_by_ref('featured_plans', $featured_plans);
            }

            $rlHook->load('apPhpAccountsAfterView');

            $reefless->loadClass('ListingsAdmin', 'admin');

            /* register ajax methods */
            $rlXajax->registerFunction(array('massActions', $rlListingsAdmin, 'ajaxMassActions'));
            $rlXajax->registerFunction(array('makeFeatured', $rlListingsAdmin, 'ajaxMakeFeatured'));
            $rlXajax->registerFunction(array('annulFeatured', $rlListingsAdmin, 'ajaxAnnulFeatured'));
            $rlXajax->registerFunction(array('deleteListing', $rlListingsAdmin, 'ajaxDeleteListingAdmin'));
            $rlXajax->registerFunction(array('moveListing', $rlListingsAdmin, 'ajaxMoveListing'));
            $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
        } else {
            $rlXajax->registerFunction(array('massActions', $rlAccount, 'ajaxMassActions'));
            //$rlXajax -> registerFunction( array( 'delAccountFile', $rlAccount, 'ajaxDelAccountFile' ) );
        }

        /* register ajax methods */
        $rlXajax->registerFunction(array('deleteAccount', $rlAdmin, 'ajaxDeleteAccount'));
        $rlXajax->registerFunction(array('prepareDeleting', $rlAccount, 'ajaxPrepareDeleting'));
    }

    $rlHook->load('apPhpAccountsBottom');
}
