<?php


/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_PLANS.INC.PHP
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

    /* load system lib */
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        if ($field == 'Status') {
            $reefless->loadClass('Listings');
            $rlListings->listingStatusControl(array('Plan_ID' => $id), $value);
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        //unset image/video unlim trigger if you change the option value directly from the grid
        if ($field == 'Image') {
            $updateData['fields']['Image_unlim'] = '0';
        } elseif ($field == 'Video') {
            $updateData['fields']['Video_unlim'] = '0';
        }

        $rlHook->load('apExtListingPlansUpdate');

        $rlActions->updateOne($updateData, 'listing_plans');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name`, `T3`.`Plan_ID` AS `Subscription` ";
    $sql .= "FROM `{db_prefix}listing_plans` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('listing_plans+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T3` ON `T1`.`ID` = `T3`.`Plan_ID` AND `T3`.`Service` = 'listing' AND `T3`.`Status` = 'active' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($sort) {
        switch ($sort) {
            case 'name':
                $sortField = "`T2`.`Value`";
                break;

            case 'Type_name':
                $sortField = "`T1`.`Type`";
                break;

            default:
                $sortField = "`T1`.`{$sort}`";
                break;
        }
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtListingPlansSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Type_name'] = $GLOBALS['lang'][$data[$key]['Type'] . '_plan'];
        $data[$key]['Featured'] = $data[$key]['Featured'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Subscription'] = $value['Subscription'] ? $GLOBALS['lang']['yes'] : $GLOBALS['lang']['no'];
    }

    $rlHook->load('apExtListingPlansData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpListingPlansTop');

    /* clear ajax cache */
    if (!$_POST) {
        unset($_SESSION['categories']);
    }

    $reefless->loadClass('Account');
    $reefless->loadClass('Categories');
    $reefless->loadClass('Listings');
    $reefless->loadClass('Payment');
    $reefless->loadClass('Subscription');

    $account_types = $rlAccount->getAccountTypes();
    $rlSmarty->assign_by_ref('account_types', $account_types);

    // get subscription option
    $subscription_options = $rlSubscription->getPlanOptions();
    $rlSmarty->assign_by_ref('subscription_options', $subscription_options);

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_plan'] : $lang['edit_plan'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        $reefless->loadClass('Categories');

        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        /* get categories/section */
        $sections = $rlCategories->getCatTree(0, false, true);
        $rlSmarty->assign_by_ref('sections', $sections);

        $p_key = $rlValid->xSql($_GET['plan']);

        // get current plan info
        if ($p_key) {
            $plan_info = $rlDb->fetch('*', array('Key' => $p_key), "AND `Status` <> 'trash'", null, 'listing_plans', 'row');
            $rlSmarty->assign_by_ref('plan_info', $plan_info);
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['key'] = $plan_info['Key'];
            $_POST['type'] = $plan_info['Type'];
            $_POST['cat_sticky'] = $plan_info['Sticky'];
            $_POST['color'] = $plan_info['Color'];
            $_POST['price'] = $plan_info['Price'];
            $_POST['listing_period'] = $plan_info['Listing_period'];
            $_POST['plan_period'] = $plan_info['Plan_period'];
            $_POST['images'] = $plan_info['Image'];
            $_POST['images_unlimited'] = $plan_info['Image_unlim'];
            $_POST['video'] = $plan_info['Video'];
            $_POST['video_unlimited'] = $plan_info['Video_unlim'];
            $_POST['listing_number'] = $plan_info['Listing_number'];
            $_POST['status'] = $plan_info['Status'];
            $_POST['subcategories'] = $plan_info['Subcategories'];
            $_POST['limit'] = $plan_info['Limit'];
            $_POST['cross'] = $plan_info['Cross'];
            $_POST['account_type'] = explode(',', $plan_info['Allow_for']);
            $_POST['featured'] = $plan_info['Featured'];
            $_POST['advanced_mode'] = $plan_info['Advanced_mode'];
            $_POST['fa_standard'] = $plan_info['Standard_listings'];
            $_POST['fa_featured'] = $plan_info['Featured_listings'];
            $_POST['categories'] = explode(',', $plan_info['Category_ID']);

            // get names
            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'listing_plans+name+' . $p_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $pKey => $pVal) {
                $_POST['name'][$names[$pKey]['Code']] = $names[$pKey]['Value'];
            }

            // get description
            $descriptions = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'listing_plans+des+' . $p_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($descriptions as $pKey => $pVal) {
                $_POST['description'][$descriptions[$pKey]['Code']] = $descriptions[$pKey]['Value'];
            }

            // get subscription options
            $subscription_info = $rlSubscription->getPlan('listing', $plan_info['ID']);
            if ($subscription_info) {
                $_POST['subscription'] = $subscription_info['Status'] == 'active' ? 1 : 0;
                $_POST['period'] = $subscription_info['Period'];
                $_POST['period_total'] = $subscription_info['Period_total'];
                foreach ($subscription_info as $sKey => $sValue) {
                    if (substr_count($sKey, 'sop') > 0) {
                        $_POST['sop'][$sKey] = $sValue;
                    }
                }
            }

            $rlHook->load('apPhpListingPlansPost');
        }

        /* get parent points */
        if ($_POST['categories']) {
            $rlCategories->parentPoints($_POST['categories']);
        }

        if (isset($_POST['submit'])) {
            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $_SESSION['categories'] = $_POST['categories'];

            $f_key = $_POST['key'];

            /* check key exist (in add mode only) */
            if ($_GET['action'] == 'add') {
                /* check key */
                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(
                    array('Key', 'Status'),
                    array('Key' => $f_key),
                    null,
                    null,
                    'listing_plans',
                    'row'
                );

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_key_exist']);

                    if ($exist_key['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = 'key';
                }
            }

            $f_key = $rlValid->str2key($f_key);

            /* check name */
            $f_name = $_POST['name'];
            $f_description = $_POST['description'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            /* check type */
            $f_type = $_POST['type'];

            if (empty($f_type)) {
                $errors[] = $lang['notice_no_type_chose'];
                $error_fields[] = 'type';
            }

            /* check listing period */
            $f_listing_period = $_POST['listing_period'];

            if ($f_listing_period == '') {
                $errors[] = str_replace('{field}', '<b>"' . $lang['listing_live_for'] . '</b>"', $lang['notice_field_empty']);
                $error_fields[] = 'listing_period';
            }

            /* check plan period */
            $f_plan_period = $_POST['plan_period'];

            if ($f_plan_period == '' && $f_type == 'package') {
                $errors[] = str_replace('{field}', '<b>"' . $lang['plan_live_for'] . '</b>"', $lang['notice_field_empty']);
                $error_fields[] = 'plan_period';
            }

            /* check featured settings */
            $f_advanced_mode = $_POST['advanced_mode'];
            $f_featured = $_POST['featured'];
            $f_standard_listings = $_POST['fa_standard'];
            $f_featured_listings = $_POST['fa_featured'];

            if ($f_type == 'package' && $f_featured && $f_advanced_mode && ($f_standard_listings == '' || $f_featured_listings == '')) {
                $errors[] = str_replace('{field}', '<b>"' . $lang['featured_type_standard'] . '</b>"', $lang['notice_field_empty']);
                $errors[] = str_replace('{field}', '<b>"' . $lang['featured_type_featured'] . '</b>"', $lang['notice_field_empty']);
                $error_fields[] = 'fa_standard';
                $error_fields[] = 'fa_featured';
            } elseif ($f_type != 'package') {
                $_POST['advanced_mode'] = false;
                $_POST['fa_standard'] = false;
                $_POST['fa_featured'] = false;
            }

            /* check listing number | package, non advanced mode */
            $f_listing_number = $_POST['listing_number'];

            if ($f_listing_number == '' && $f_type == 'package' && !$f_advanced_mode) {
                $errors[] = str_replace('{field}', '<b>"' . $lang['listing_number'] . '</b>"', $lang['notice_field_empty']);
                $error_fields[] = 'listing_number';
            }
            // check subscription options
            if ($_POST['subscription']) {
                if (!$_POST['period']) {
                    $errors[] = str_replace('{field}', '<b>"' . $lang['subscription_period'] . '</b>"', $lang['notice_field_empty']);
                    $error_fields[] = 'period';
                }
            }

            $rlHook->load('apPhpListingPlansValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* cleat ajax cache */
                unset($_SESSION['categories']);

                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}listing_plans`");

                    // write main plan information
                    $data = array(
                        'Key'               => $f_key,
                        'Type'              => $f_type,
                        'Category_ID'       => $_POST['categories'] ? implode(',', $_POST['categories']) : '',
                        'Allow_for'         => $_POST['account_type'] ? implode(',', $_POST['account_type']) : '',
                        'Limit'             => $f_type != 'package' ? (int) $_POST['limit'] : '',
                        'Cross'             => (int) $_POST['cross'],
                        'Color'             => $_POST['color'],
                        'Price'             => (double) $_POST['price'],
                        'Listing_period'    => (int) $_POST['listing_period'],
                        'Plan_period'       => (int) $_POST['plan_period'],
                        'Image'             => (int) $_POST['images'],
                        'Image_unlim'       => (int) $_POST['images_unlimited'],
                        'Video'             => (int) $_POST['video'],
                        'Video_unlim'       => (int) $_POST['video_unlimited'],
                        'Status'            => $_POST['status'],
                        'Position'          => $position['max'] + 1,
                        'Sticky'            => empty($_POST['cat_sticky']) ? 0 : 1,
                        'Subcategories'     => empty($_POST['subcategories']) ? 0 : 1,
                        'Featured'          => (int) $_POST['featured'],
                        'Advanced_mode'     => $_POST['featured'] ? (int) $_POST['advanced_mode'] : 0,
                        'Standard_listings' => (int) $_POST['fa_standard'],
                        'Featured_listings' => (int) $_POST['fa_featured'],
                    );

                    if ($f_type == 'package') {
                        $data['Listing_number'] = $_POST['listing_number'];
                    }

                    $rlHook->load('apPhpListingPlansBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'listing_plans')) {
                        // get plan ID
                        $plan_id = $rlDb->insertID();

                        $rlHook->load('apPhpListingPlansAfterAdd');

                        // write name's phrases
                        foreach ($allLangs as $key => $value) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'listing_plans+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            if (!empty($f_description[$allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'listing_plans+des+' . $f_key,
                                    'Value'  => $f_description[$allLangs[$key]['Code']],
                                );
                            }
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['plan_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new lisitng plan (MYSQL problems)", E_USER_WARNING);
                        $rlDebug->logger("Can't add new lisitng plan (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Status'            => $_POST['status'],
                            'Type'              => $f_type,
                            'Category_ID'       => $_POST['categories'] ? implode(',', $_POST['categories']) : '',
                            'Allow_for'         => $_POST['account_type'] ? implode(',', $_POST['account_type']) : '',
                            'Limit'             => $f_type != 'package' ? (int) $_POST['limit'] : '',
                            'Cross'             => (int) $_POST['cross'],
                            'Color'             => $_POST['color'],
                            'Price'             => (double) $_POST['price'],
                            'Listing_period'    => (int) $_POST['listing_period'],
                            'Plan_period'       => (int) $_POST['plan_period'],
                            'Image'             => (int) $_POST['images'],
                            'Image_unlim'       => (int) $_POST['images_unlimited'],
                            'Video'             => (int) $_POST['video'],
                            'Video_unlim'       => (int) $_POST['video_unlimited'],
                            'Sticky'            => empty($_POST['cat_sticky']) ? 0 : 1,
                            'Subcategories'     => empty($_POST['subcategories']) ? 0 : 1,
                            'Featured'          => (int) $_POST['featured'],
                            'Advanced_mode'     => $_POST['featured'] ? (int) $_POST['advanced_mode'] : 0,
                            'Standard_listings' => (int) $_POST['fa_standard'],
                            'Featured_listings' => (int) $_POST['fa_featured'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    if ($f_type == 'package') {
                        $update_date['fields']['Listing_number'] = $_POST['listing_number'];
                    }

                    $rlHook->load('apPhpListingPlansBeforeEdit');

                    /* update plan */
                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'listing_plans');
                    $plan_id = $plan_info['ID'];

                    $rlHook->load('apPhpListingPlansAfterEdit');

                    /* remove crossed */
                    if ($plan_info['Cross'] && !(int) $_POST['cross']) {
                        $rlDb->setTable('listings');
                        if ($crossed_listings = $rlDb->fetch(array('Crossed'), array('Plan_ID' => $plan_info['ID']), "AND `Crossed` <> ''")) {
                            foreach ($crossed_listings as $crossed_listing) {
                                foreach (explode(',', $crossed_listing['Crossed']) as $crossed_category_id) {
                                    $rlCategories->listingsDecrease($crossed_category_id);
                                }
                            }
                        }

                        $sql = "UPDATE `{db_prefix}listings` SET `Crossed` = '' WHERE `Plan_ID` = '{$plan_info['ID']}' AND `Crossed` <> ''";
                        $rlDb->query($sql);
                    }

                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'listing_plans+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit names
                            $update_phrases = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'listing_plans+name+' . $f_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_phrases, 'lang_keys');
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'listing_plans+name+' . $f_key,
                                'Value'  => $_POST['name'][$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }

                        // edit description's values
                        $c_query = $rlDb->fetch(array('ID'), array('Key' => 'listing_plans+des+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                        if (!empty($c_query)) {
                            if (!empty($_POST['description'][$allLangs[$key]['Code']])) {
                                $lang_keys_des[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'listing_plans+des+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => $_POST['description'][$allLangs[$key]['Code']],
                                    ),
                                );
                            } else {
                                $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'listing_plans+des+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                            }
                        } else {
                            if (!empty($f_description[$allLangs[$key]['Code']])) {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'listing_plans+des+' . $f_key,
                                    'Value'  => $f_description[$allLangs[$key]['Code']],
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        }

                        // update
                        if ($lang_keys_des) {
                            $GLOBALS['rlActions']->update($lang_keys_des, 'lang_keys');
                        }
                    }

                    $message = $lang['plan_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    // save subscription options
                    $rlSubscription->savePlanOptions('listing', $plan_id, (double) $_POST['price']);

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
        $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
        $rlXajax->registerFunction(array('openTree', $rlCategories, 'ajaxOpenTree'));
    }

    $rlHook->load('apPhpListingPlansBottom');

    $reefless->loadClass('Plan');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deletePlan', $rlPlan, 'ajaxDeletePlan'));
    $rlXajax->registerFunction(array('prepareDeleting', $rlPlan, 'ajaxPrepareDeleting'));
}
