<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ACCOUNT_TYPES.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $type_info = $rlDb->fetch(array("Status", "Key"), array("ID" => $id), null, null, "account_types", "row");
        $rlHook->load('apExtAccountTypesUpdate');

        $rlActions->updateOne($updateData, 'account_types');

        /* activate/deactivate related items */
        if ($field == 'Status' && $type_info['Status'] != $value) {
            $sql = "UPDATE `{db_prefix}accounts` SET `Status` = '{$value}' ";
            $sql .= "WHERE `Type` = '{$type_info['Key']}' ";
            $sql .= "AND `Status` != 'incomplete' AND `Status` != 'pending' ";
            $rlDb->query($sql);

            $reefless->loadClass('Listings');
            $rlListings->listingStatusControl(array("Account_type" => $type_info['Key']), $value);
        }
        /* activate/deactivate related items end */

        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, ";
    $sql .= "(SELECT COUNT(`ID`) FROM `{db_prefix}accounts` WHERE `Type` = `T1`.`Key` AND `Status` <> 'trash') AS `Accounts_count` ";
    $sql .= "FROM `{db_prefix}account_types` AS `T1` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ORDER BY `T1`.`Position` ";
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtAccountTypesSql');

    $data  = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $data = $rlLang->replaceLangKeys($data, 'account_types', array('name'), RL_LANG_CODE, 'admin');

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
    }

    $rlHook->load('apExtAccountTypesData');

    $output['total'] = $count['count'];
    $output['data']  = $data;

    echo json_encode($output);
}
/* ext js action end */
else {
    $reefless->loadClass('Account');
    $reefless->loadClass('Listings');
    $reefless->loadClass('AccountTypes');

    $rlHook->load('apPhpAccountTypesTop');

    /* additional bread crumb step */
    if ($_GET['action']) {
        switch ($_GET['action']) {
            case 'add':
                $bcAStep = $lang['add_type'];
                break;

            case 'edit':
                $bcAStep = $lang['edit_type'];
                break;

            case 'build':
                $bcAStep = $lang['build_register_form'];
                break;
        }
    } else {
        $rlXajax->registerFunction(array('preAccountTypeDelete', $rlAdmin, 'ajaxPreAccountTypeDelete'));
        $rlXajax->registerFunction(array('deleteAccountType', $rlAdmin, 'ajaxDeleteAccountType'));

        /* get accounts types */
        $available_account_types = $rlAccount->getAccountTypes('visitor');
        $rlSmarty->assign_by_ref('available_account_types', $available_account_types);
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // Get account thumbnail setting phrases
        $rlDb->outputRowsMap = ['Key', 'Value'];
        $config_phrases = $rlDb->fetch(
            ['Key', 'Value'],
            ['Code' => RL_LANG_CODE, 'Module' => 'admin', 'Target_key' => 'settings'],
            "AND `Key` LIKE 'config+name+account_thumb%'",
            null, 'lang_keys'
        );
        $lang = array_merge($lang, (array) $config_phrases);

        /* type settings */
        $account_settings = array(
            array(
                'key'  => 'email_confirmation',
                'name' => $lang['account_type_email_confirmation'],
            ),
            array(
                'key'  => 'admin_confirmation',
                'name' => $lang['account_type_admin_confirmation'],
            ),
            array(
                'key'  => 'auto_login',
                'name' => $lang['account_type_auto_login'],
            ),
        );
        $rlSmarty->assign_by_ref('account_settings', $account_settings);

        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit') {
            $i_key = $rlValid->xSql($_GET['type']);

            // get current account type info
            $item_info = $rlDb->fetch('*', array('Key' => $i_key), "AND `Status` <> 'trash'", null, 'account_types', 'row');

            $fields = $rlAccount->getFields($item_info['ID']);
            $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name'), RL_LANG_CODE, 'admin');
            $rlSmarty->assign('fields', $fields);

            /* get required fields for "Alphabet search priority field" option */
            $sql = "SELECT `T1`.`Field_ID`, `T1`.`Category_ID` AS `Type_ID`, `T2`.`Required`, `T2`.`Key` ";
            $sql .= "FROM `{db_prefix}account_submit_form` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}account_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = {$item_info['ID']} AND `T2`.`Required` = '1' ";
            $sql .= "ORDER BY `T1`.`Position`";

            $alphabetic_fields = $rlDb->getAll($sql, 'Key');
            $rlSmarty->assign_by_ref('alphabetic_fields', $alphabetic_fields);

            /* get available account types */
            $account_types = $rlAccount->getAccountTypes('visitor');

            /* check value of "Quick registration" option in other account types */
            $allow_change_quick_registration = $rlAccount->checkAbilityDisablingType($item_info, $account_types);
            $rlSmarty->assign('allow_change_quick_registration', $allow_change_quick_registration);

            /* check abilities by listing types for all account types */
            foreach ($rlListingTypes->types as &$l_type) {
                $count_allowed_atypes = 0;

                foreach ($account_types as $a_type) {
                    if (in_array($l_type['Key'], explode(',', $a_type['Abilities']))
                        && $a_type['Quick_registration']
                        && $a_type['Key'] != $item_info['Key']
                    ) {
                        $count_allowed_atypes++;
                    }
                }

                $l_type['Deny_uncheck_ability'] = $count_allowed_atypes ? true : false;
            }
        }

        $meta_fields = ['account_meta_title', 'account_meta_h1', 'account_meta_description', 'account_meta_keywords'];
        $rlSmarty->assign_by_ref('meta_fields', $meta_fields);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['key'] = $item_info['Key'];
            $_POST['page'] = $item_info['Page'];
            $_POST['own_location'] = $item_info['Own_location'];
            $_POST['email_confirmation'] = $item_info['Email_confirmation'];
            $_POST['admin_confirmation'] = $item_info['Admin_confirmation'];
            $_POST['auto_login'] = $item_info['Auto_login'];
            $_POST['status'] = $item_info['Status'];
            $_POST['abilities'] = explode(',', $item_info['Abilities']);
            $_POST['featured_blocks'] = $item_info['Featured_blocks'];
            $_POST['alphabetic_field'] = $item_info['Alphabetic_field'];
            $_POST['quick_registration'] = $item_info['Quick_registration'];
            $_POST['dimensions'] = array(
                'width'  => $item_info['Thumb_width'],
                'height' => $item_info['Thumb_height'],
            );
            $_POST['agency'] = $item_info['Agency'];
            $_POST['agent']  = $item_info['Agent'];

            if ($item_info['Agency']) {
                $_POST['isAllowDisableAgency'] = !(bool) $rlDb->getRow(
                    "SELECT `T1`.`ID` FROM `{db_prefix}accounts` AS `T1`
                     LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T2`.`Key` = `T1`.`Type`
                     WHERE `T1`.`Agency_ID` <> 0",
                    'ID'
                );
            } else if ($item_info['Agent']) {
                $_POST['isAllowDisableAgent'] = !(bool) $rlDb->getRow(
                    "SELECT `T1`.`ID` FROM `{db_prefix}agency_invites` AS `T1`
                     LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Agent_ID`
                     WHERE `T1`.`Status` = 'accepted' AND `T2`.`Type` = '{$item_info['Key']}' ",
                    'ID'
                );
            }

            // get names
            $i_names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'account_types+name+' . $i_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($i_names as $nKey => $nVal) {
                $_POST['name'][$nVal['Code']] = $nVal['Value'];
            }

            // get desc
            $i_desc = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'account_types+desc+' . $i_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($i_desc as $nKey => $nVal) {
                $_POST['description_' . $nVal['Code']] = $nVal['Value'];
            }

            // Simulate account meta data
            foreach ($meta_fields as $meta_field) {
                $meta_items = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'account_types+' . $meta_field . '+' . $i_key), "AND `Status` <> 'trash'", null, 'lang_keys');
                foreach ($meta_items as $meta_item) {
                    $_POST[$meta_field][$meta_item['Code']] = $meta_item['Value'];
                }
            }

            $rlHook->load('apPhpAccountTypesPost');
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $_POST['key'];

            /* check key exist (in add mode only) */
            if ($_GET['action'] == 'add') {
                /* check key */
                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                } elseif (substr($f_key, 0, 3) == 'at_') {
                    $errors[] = $lang['at_key_incorrect'];
                }

                $exist_key = $rlDb->fetch(array('Key'), array('Key' => $f_key), null, null, 'account_types');

                if (!empty($exist_key)) {
                    $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_account_type_key_exist']);
                }
            }

            $f_key = $rlValid->str2key($f_key);

            /* check name */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            if ($f_key != 'visitor') {
                // check width of thumbnail
                $thumb_width = (int) $_POST['dimensions']['width'];
                if ($thumb_width < 100 || $thumb_width > 260) {
                    $errors[] = $lang['thumb_width_desc'];
                }

                // check height of thumbnail
                $thumb_height = (int) $_POST['dimensions']['height'];
                if ($thumb_height < 100 || $thumb_height > 260) {
                    $errors[] = $lang['thumb_height_desc'];
                }
            }

            $rlHook->load('apPhpAccountTypesValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}account_types`");

                    // write main type information
                    $data = array(
                        'Key'                => $f_key,
                        'Status'             => $_POST['status'] != '' ? $_POST['status'] : 'active',
                        'Abilities'          => $_POST['abilities'] ? implode(',', $_POST['abilities']) : '',
                        'Page'               => (int) $_POST['page'],
                        'Own_location'       => (int) $_POST['own_location'],
                        'Email_confirmation' => (int) $_POST['email_confirmation'],
                        'Admin_confirmation' => (int) $_POST['admin_confirmation'],
                        'Auto_login'         => (int) $_POST['auto_login'],
                        'Position'           => $position['max'] + 1,
                        'Alphabetic_field'   => $_POST['alphabetic_field'],
                        'Quick_registration' => (int) $_POST['quick_registration'],
                        'Thumb_width'        => $thumb_width,
                        'Thumb_height'       => $thumb_height,
                        'Agency'             => (int) $_POST['agency'],
                        'Agent'              => (int) $_POST['agent'],
                    );

                    if ($config['membership_module']) {
                        $data['Featured_blocks'] = (int) $_POST['featured_blocks'];
                        $data['Ablock_position'] = 'left';
                    }

                    $rlHook->load('apPhpAccountTypesBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'account_types')) {
                        $rlHook->load('apPhpAccountTypesAfterAdd');

                        // add enum option to listing plans table
                        $rlActions->enumAdd('listing_plans', 'Allow_for', $f_key);

                        foreach ($allLangs as $language) {
                            // write name's phrases
                            $lang_keys[] = array(
                                'Code'   => $language['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'account_types+name+' . $f_key,
                                'Value'  => $f_name[$language['Code']],
                            );

                            // save description
                            $lang_keys[] = array(
                                'Code'   => $language['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'account_types+desc+' . $f_key,
                                'Value'  => $_POST['description_' . $language['Code']],
                            );

                            // Save meta fields data
                            foreach ($meta_fields as $meta_field) {
                                if (!empty($_POST[$meta_field][$language['Code']])) {
                                    $lang_keys[] = array(
                                        'Code'       => $language['Code'],
                                        'Module'     => 'common',
                                        'Status'     => 'active',
                                        'Key'        => 'account_types+' . $meta_field . '+' . $f_key,
                                        'Value'      => trim($_POST[$meta_field][$language['Code']]),
                                        'Target_key' => 'account_type'
                                    );
                                }
                            }

                            if ($_POST['featured_blocks']) {
                                // featured listings block names
                                $lang_keys[] = array(
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'blocks+name+atfb_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$language['Code']], $lang['featured_block_pattern']),
                                );
                            }

                            if ($_POST['page']) {
                                // individual page names
                                $lang_keys[] = array(
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+name+at_' . $f_key,
                                    'Value'  => $f_name[$language['Code']] . ' ' . $lang['accounts'],
                                );

                                // individual page titles
                                $lang_keys[] = array(
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+title+at_' . $f_key,
                                    'Value'  => $f_name[$language['Code']] . ' ' . $lang['accounts'],
                                );
                            }
                        }

                        // creat individual page
                        if ($_POST['page']) {
                            $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                            $individual_page = array(
                                'Parent_ID'  => 0,
                                'Page_type'  => 'system',
                                'Login'      => 0,
                                'Key'        => 'at_' . $f_key,
                                'Position'   => $page_position['max'] + 1,
                                'Path'       => $rlValid->str2path($f_key) . '-accounts',
                                'Controller' => 'account_type',
                                'Tpl'        => 1,
                                'Menus'      => 1,
                                'Modified'   => 'NOW()',
                                'Status'     => 'active',
                                'Readonly'   => 1,
                            );
                            $rlActions->insertOne($individual_page, 'pages');
                            $page_id = $rlDb->insertID();

                            $rlAccountTypes->addSystemBoxesToPage($page_id);
                        }

                        // create featured account box
                        if ($_POST['featured_blocks']) {
                            $f_block_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                            $featured_block = array(
                                'Page_ID'  => $page_id ? $page_id : 1,
                                'Sticky'   => 0,
                                'Key'      => 'atfb_' . $f_key,
                                'Position' => $f_block_position['max'] + 1,
                                'Side'     => 'left',
                                'Type'     => 'smarty',
                                'Content'  => '{include file=\'blocks\'|cat:$smarty.const.RL_DS|cat:\'featured_accounts.tpl\' accounts=$featured_' . $f_key . ' type=\'' . $f_key . '\'}',
                                'Tpl'      => 1,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($featured_block, 'blocks');
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['account_type_added'];
                        $aUrl = array('controller' => $controller, 'request' => 'build', 'key' => $f_key);
                    } else {
                        trigger_error("Can't add new account type (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new account type (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Status'             => $_POST['status'],
                            'Abilities'          => implode(',', $_POST['abilities']),
                            'Page'               => (int) $_POST['page'],
                            'Own_location'       => (int) $_POST['own_location'],
                            'Email_confirmation'  => (int) $_POST['email_confirmation'],
                            'Admin_confirmation'  => (int) $_POST['admin_confirmation'],
                            'Auto_login'         => (int) $_POST['auto_login'],
                            'Alphabetic_field'    => $_POST['alphabetic_field'],
                            'Quick_registration' => (int) $_POST['quick_registration'],
                            'Thumb_width'        => $thumb_width,
                            'Thumb_height'       => $thumb_height,
                            'Agency'             => (int) $_POST['agency'],
                            'Agent'              => (int) $_POST['agent'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    if ($config['membership_module']) {
                        $update_date['fields']['Featured_blocks'] = (int) $_POST['featured_blocks'];
                    }

                    $rlHook->load('apPhpAccountTypesBeforeEdit');

                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'account_types');

                    $rlHook->load('apPhpAccountTypesAfterEdit');

                    /* activate/deactivate related items */
                    if ($item_info['Status'] != $_POST['status']) {
                        $sql = "UPDATE `{db_prefix}accounts` SET `Status` = '{$_POST['status']}' ";
                        $sql .= "WHERE `Type` = '{$item_info['Key']}' ";
                        $sql .= "AND `Status` != 'incomplete' AND `Status` != 'pending' ";
                        $rlDb->query($sql);

                        $reefless->loadClass('Listings');
                        $rlListings->listingStatusControl(array("Account_type" => $item_info['Key']), $_POST['status']);
                    }
                    /* activate/deactivate related items end */

                    // Remove exists declined/pending invites if the account type is not the Agency anymore
                    if ($item_info['Agency'] && (int) $_POST['agency'] === 0) {
                        $rlDb->query(
                            "DELETE `T1` FROM `{db_prefix}agency_invites` AS `T1`
                             INNER JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Agency_ID`
                             WHERE `T2`.`Type` = '{$item_info['Key']}'"
                        );
                    }

                    // Remove exists invites if the account type is not the Agent anymore
                    if ($item_info['Agent'] && (int) $_POST['agent'] === 0) {
                        $rlDb->query(
                            "DELETE `T1` FROM `{db_prefix}agency_invites` AS `T1`
                             INNER JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Agent_ID`
                             WHERE `T2`.`Type` = '{$item_info['Key']}'"
                        );
                    }

                    foreach ($allLangs as $language) {
                        if ($rlDb->getOne('ID', "`Key` = 'account_types+name+{$f_key}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                            // edit names
                            $update_phrases = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$language['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'account_types+name+' . $f_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_phrases, 'lang_keys');
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $language['Code'],
                                'Module' => 'common',
                                'Key'    => 'account_types+name+' . $f_key,
                                'Value'  => $_POST['name'][$language['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'account_types+desc+{$f_key}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                            // edit descriptions
                            $update_phrases = array(
                                'fields' => array(
                                    'Value' => $_POST['description_' . $language['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'account_types+desc+' . $f_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_phrases, 'lang_keys');
                        } else {
                            // insert description
                            $insert_phrases = array(
                                'Code'   => $language['Code'],
                                'Module' => 'common',
                                'Key'    => 'account_types+desc+' . $f_key,
                                'Value'  => $_POST['description_' . $language['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }

                        foreach ($meta_fields as $meta_field) {
                            if (!empty($_POST[$meta_field][$language['Code']])) {
                                $meta_exists = $rlDb->fetch(array('ID'), array('Key' => 'account_types+' . $meta_field . '+' . $f_key, 'Code' => $language['Code']), null, null, 'lang_keys', 'row');
                                if (!empty($meta_exists)) {
                                    $update_phrases = array(
                                        'where'  => array(
                                            'Code' => $language['Code'],
                                            'Key'  => 'account_types+' . $meta_field . '+' . $f_key,
                                        ),
                                        'fields' => array(
                                            'Value'      => trim($_POST[$meta_field][$language['Code']]),
                                            'Target_key' => 'account_type'
                                        ),
                                    );

                                    $rlActions->updateOne($update_phrases, 'lang_keys');
                                } else {
                                    $lang_keys_des = array(
                                        'Code'       => $language['Code'],
                                        'Module'     => 'common',
                                        'Status'     => 'active',
                                        'Key'        => 'account_types+' . $meta_field . '+' . $f_key,
                                        'Value'      => trim($_POST[$meta_field][$language['Code']]),
                                        'Target_key' => 'account_type'
                                    );

                                    $rlActions->insertOne($lang_keys_des, 'lang_keys');
                                }
                            } else {
                                $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'account_types+{$meta_field}+{$f_key}' AND `Code` = '{$language['Code']}'");
                            }
                        }
                    }

                    /* individual page tracking */
                    if ($item_info['Page'] && !(int) $_POST['page']) {
                        // suspend page
                        $suspend_page = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'at_' . $f_key,
                            ),
                        );
                        $rlActions->updateOne($suspend_page, 'pages');

                        // suspend phrases
                        $suspend_phrases[] = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'pages+name+at_' . $f_key,
                            ),
                        );

                        $suspend_phrases[] = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'pages+title+at_' . $f_key,
                            ),
                        );
                        $rlActions->update($suspend_phrases, 'lang_keys');

                        $rlCache->updateStatistics();
                    } else if (!$item_info['Page'] && (int) $_POST['page']) {
                        if (!$rlDb->getOne('ID', "`Key` = 'at_{$f_key}'", 'pages')) {
                            // create page
                            $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                            $individual_page = array(
                                'Parent_ID'  => 0,
                                'Page_type'  => 'system',
                                'Login'      => 0,
                                'Key'        => 'at_' . $f_key,
                                'Position'   => $page_position['max'] + 1,
                                'Path'       => $rlValid->str2path($f_key) . '-accounts',
                                'Controller' => 'account_type',
                                'Tpl'        => 1,
                                'Menus'      => 1,
                                'Modified'   => 'NOW()',
                                'Status'     => 'active',
                                'Readonly'   => 1,
                            );
                            $rlActions->insertOne($individual_page, 'pages');
                            $page_id = $rlDb->insertID();

                            // add phrases
                            foreach ($allLangs as $key => $value) {
                                // individual page names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+name+at_' . $f_key,
                                    'Value'  => $f_name[$allLangs[$key]['Code']] . ' ' . $lang['accounts'],
                                );

                                // individual page titles
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+title+at_' . $f_key,
                                    'Value'  => $f_name[$allLangs[$key]['Code']] . ' ' . $lang['accounts'],
                                );
                            }
                            $rlActions->insert($lang_keys, 'lang_keys');
                        }
                        // activate page
                        else {
                            $activate_page = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'at_' . $f_key,
                                ),
                            );
                            $rlActions->updateOne($activate_page, 'pages');

                            // activate phrases
                            $activate_phrases[] = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'pages+name+at_' . $f_key,
                                ),
                            );

                            $activate_phrases[] = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'pages+title+at_' . $f_key,
                                ),
                            );
                            $rlActions->update($activate_phrases, 'lang_keys');
                        }

                        $rlCache->updateStatistics();
                    }
                    /* individual page tracking end */

                    // featued block tracker
                    if ($config['membership_module']) {
                        $reefless->loadClass('ListingTypes');
                        if ($item_info['Featured_blocks'] && !(int) $_POST['featured_blocks']) {
                            // suspend featured boxes
                            $rlListingTypes->apBlocksTracker(array(
                                'key'     => $f_key,
                                'prefix'  => 'atfb_',
                                'suspend' => true,
                            ));
                        } elseif (!$item_info['Featured_blocks'] && (int) $_POST['featured_blocks']) {
                            // create || activate featured box
                            $rlListingTypes->apBlocksTracker(array(
                                'key'              => $f_key,
                                'prefix'           => 'atfb_',
                                'page_ids'         => $page_id ? $page_id : 1,
                                'Content'          => '{include file=\'blocks\'|cat:$smarty.const.RL_DS|cat:\'featured_accounts.tpl\' accounts=$featured_' . $f_key . ' type=\'' . $f_key . '\'}',
                                'box_name_pattern' => 'featured_block_pattern',
                            ));
                        }
                    }

                    $message = $lang['account_type_edited'];
                    $aUrl = array("controller" => $controller);

                    // tracking of changes of thumbnails size
                    if ($item_info['Key'] != 'visitor'
                        && ($item_info['Thumb_width'] != $thumb_width || $item_info['Thumb_height'] != $thumb_height)
                    ) {
                        $aUrl['rebuild_pictures'] = $item_info['Key'];
                    }
                }

                if ($action) {
                    $rlHook->load('apPhpAccountTypesBeforeRedirect'); // >= v4.3

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    } elseif ($_GET['action'] == 'build') {
        $reefless->loadClass('Builder', 'admin');
        $rlXajax->registerFunction(array('buildForm', $rlBuilder, 'ajaxBuildForm'));

        $type_key = $rlValid->xSql($_GET['key']);
        $form_type = $_GET['form'];

        if (!$type_key || !$form_type) {
            $errors[] = 'FORM BUILDER ERROR: Bad request, please contact software support.';
        } else {
            /* get current account type info */
            $type_info = $rlDb->fetch(array('ID', 'Key'), array('Key' => $type_key), "AND `Status` <> 'trash'", null, 'account_types', 'row');
            $type_info = $rlLang->replaceLangKeys($type_info, 'account_types', array('name'), RL_LANG_CODE, 'admin');
            $rlSmarty->assign_by_ref('category_info', $type_info);

            $rlSmarty->assign('cpTitle', $type_info['name']);

            switch ($form_type) {
                case 'reg_form':
                    $rlBuilder->rlBuildTable = 'account_submit_form';
                    $rlBuilder->rlBuildField = 'Field_ID';

                    /* additional bread crumb step */
                    $bcAStep = $lang['build_register_form'];
                    break;

                case 'short_form':
                    $rlBuilder->rlBuildTable = 'account_short_form';
                    $rlBuilder->rlBuildField = 'Field_ID';

                    /* additional bread crumb step */
                    $bcAStep = $lang['account_short_form_builder'];
                    break;

                case 'search_form':
                    $rlBuilder->rlBuildTable = 'account_search_relations';
                    $rlBuilder->rlBuildField = 'Field_ID';

                    /* additional bread crumb step */
                    $bcAStep = $lang['search_form_builder'];
                    break;
            }

            $rlHook->load('apPhpAccountTypesBuildSwitch');

            /* get available fields for current type */
            $avail_fields = $rlDb->fetch(array('Group_ID', 'Field_ID'), array('Category_ID' => $type_info['ID']), null, null, 'account_search_relations');

            foreach ($avail_fields as $aKey => $aVal) {
                if ($avail_fields[$aKey]['Group_ID']) {
                    $tmp_fields = explode(',', $avail_fields[$aKey]['Fields']);
                    foreach ($tmp_fields as $tmpKey => $tmpVal) {
                        if (!empty($tmpVal)) {
                            $a_fields .= "`ID` = '{$tmpVal}' OR ";
                        }
                    }
                } else {
                    $f = (int) $avail_fields[$aKey]['Fields'];
                    $a_fields .= "`ID` = '{$f}' OR ";
                }
            }
            $a_fields = substr($a_fields, 0, -4);

            /* get form fields for current type */
            $relations = $rlBuilder->getFormRelations($type_info['ID'], 'account_fields');
            $rlSmarty->assign_by_ref('relations', $relations);

            foreach ($relations as $rKey => $rValue) {
                $no_groups[] = $relations[$rKey]['Key'];

                $f_fields = $relations[$rKey]['Fields'];

                if ($relations[$rKey]['Group_ID']) {
                    foreach ($f_fields as $fKey => $fValue) {
                        $no_fields[] = $f_fields[$fKey]['Key'];
                    }
                } else {
                    $no_fields[] = $relations[$rKey]['Fields']['Key'];
                }
            }

            $exclude_types = null;

            if ($form_type != 'reg_form') {
                $exclude_types = "WHERE `Status` <> 'trash' AND `Type` NOT IN ('textarea', 'file', 'image', 'accept') ";
            } else {
                $exclude_types = "WHERE `Type` <> 'accept' OR (`Type` = 'accept' AND `Opt1` = '0') ";
            }

            $fields = $rlDb->fetch(array('ID', 'Key', 'Type', 'Status'), null, $exclude_types, null, 'account_fields');
            $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name'), RL_LANG_CODE, 'admin');

            // hide already using fields
            if (!empty($no_fields)) {
                foreach ($fields as $fKey => $fVal) {
                    if (false !== array_search($fields[$fKey]['Key'], $no_fields)) {
                        $fields[$fKey]['hidden'] = true;
                    }
                }
            }

            $rlSmarty->assign_by_ref('fields', $fields);
        }
    }

    $rlHook->load('apPhpAccountTypesBottom');
}
