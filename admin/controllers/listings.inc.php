<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTINGS.INC.PHP
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
use Flynax\Utils\Category;
use Flynax\Utils\Util;

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');
        $reefless->loadClass('Categories');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Account');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        if (!$id) {
            exit;
        }

        /* get listing info before update */
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
        $listing_info = $rlDb->getRow($sql);

        /* get account info */
        $account_info = $rlAccount->getProfile((int) $listing_info['Account_ID']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );
        if ($listing_info['Plan_type'] != 'account') {
            if (!$listing_info['Payed'] && $field == 'Status' && $value == 'active') {
                $updateData['fields']['Pay_date'] = 'NOW()';
                $listing_info['Payed'] = time();
            } elseif ($listing_info['Plan_price'] > 0 && $listing_info['Status'] == 'pending' && $field == 'Status' && $value == 'active') {
                $updateData['fields']['Pay_date'] = 'NOW()';
                $listing_info['Payed'] = time();
            }
        } else {
            if ($account_info['Pay_date'] != '0000-00-00 00:00:00') {
                $updateData['fields']['Pay_date'] = $account_info['Pay_date'];
            }
        }
        // Upgrade account
        if ($listing_info['Plan_type'] == 'account' && $account_info['Plan_ID'] && $_GET['membership_upgarde']) {
            $rlAccount->upgrade($account_info['ID'], $account_info['Plan_ID'], true);
        }

        $rlHook->load('apExtListingsUpdate');

        $rlActions->updateOne($updateData, 'listings');

        switch ($field) {
            case 'Status':
                /* inform listing owner about status changing */
                $reefless->loadClass('Mail');
                $mail_tpl = $rlMail->getEmailTemplate($value == 'active' ? 'listing_activated' : 'listing_deactivated', $account_info['Lang']);

                $category = $rlCategories->getCategory($listing_info['Category_ID']);

                /* generate link */
                if ($value == 'active' && $listing_info['Status'] != 'active') {
                    $allow_send = true;

                    /* increase listings counter */
                    if (!empty($listing_info['Payed'])) {
                        $rlCategories->listingsIncrease($listing_info['Category_ID'], $listing_info['Listing_type']);
                        $rlCategories->accountListingsIncrease($listing_info['Account_ID']);

                        /* crossed listings count control */
                        if (!empty($listing_info['Crossed'])) {
                            $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                            foreach ($crossed_cats as $crossed_cat_id) {
                                $rlCategories->listingsIncrease($crossed_cat_id);
                            }
                        }
                    }

                    /* clear cache */
                    $updateData['fields']['Last_step'] = '';
                } elseif ($value != 'active' && $listing_info['Status'] == 'active') {
                    $allow_send = true;

                    /* deincrease listings counter */
                    if (!empty($listing_info['Payed'])) {
                        $rlCategories->listingsDecrease($listing_info['Category_ID'], $listing_info['Listing_type']);
                        $rlCategories->accountListingsDecrease($listing_info['Account_ID']);

                        /* crossed listings count control */
                        if (!empty($listing_info['Crossed'])) {
                            $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                            foreach ($crossed_cats as $crossed_cat_id) {
                                $rlCategories->listingsDecrease($crossed_cat_id);
                            }
                        }
                    }
                }

                if ($allow_send) {
                    $link             = $reefless->getListingUrl($listing_info, $account_info['Lang']);
                    $listing_title    = $rlListings->getListingTitle(
                        $listing_info['Category_ID'],
                        $listing_info,
                        $listing_info['Listing_type'],
                        $account_info['Lang']
                    );

                    $mail_tpl['body'] = str_replace(
                        array('{name}', '{link}'),
                        array($account_info['Full_name'], "<a href=\"{$link}\">{$listing_title}</a>"),
                        $mail_tpl['body']
                    );

                    $rlMail->send($mail_tpl, $account_info['Mail']);
                }
                break;

            case 'Pay_date':
                $period = $listing_info['Listing_period'] * 86400;

                if ($listing_info['Status'] == 'active') {
                    if ((strtotime($value) + $period > time()) && ($listing_info['Pay_date'] + $period <= time())) {
                        //if listing is active and stays active not necessary to recount

                        // $reefless->loadClass('Categories');
                        // $rlCategories->listingsIncrease($listing_info['Category_ID'], $listing_info['Listing_type']);

                        // if (!empty($listing_info['Crossed'])) {
                        //  $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                        //  foreach ($crossed_cats as $crossed_cat_id) {
                        //      $rlCategories -> listingsIncrease($crossed_cat_id);
                        //  }
                        // }
                    } else {
                        // if status is active and admin set pay date in past make listing status expired
                        $reefless->loadClass('Categories');
                        $rlCategories->listingsDecrease($listing_info['Category_ID'], $listing_info['Listing_type']);

                        if (!empty($listing_info['Crossed'])) {
                            $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                            foreach ($crossed_cats as $crossed_cat_id) {
                                $rlCategories->listingsDecrease($crossed_cat_id);
                            }
                        }

                        $customUpdate = array(
                            'fields' => array(
                                'Status' => 'expired',
                            ),
                            'where'  => array(
                                'ID' => $id,
                            ),
                        );
                    }
                } elseif ($listing_info['Status'] == 'expired' && (strtotime($value) + $period > time()) && ($listing_info['Pay_date'] + $period <= time())) {
                    // if status was 'expired' and admin renews pay date make listing status active
                    $reefless->loadClass('Categories');
                    $rlCategories->listingsIncrease($listing_info['Category_ID'], $listing_info['Listing_type']);
                    $rlCategories->accountListingsIncrease($listing_info['Account_ID']);

                    if (!empty($listing_info['Crossed'])) {
                        $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                        foreach ($crossed_cats as $crossed_cat_id) {
                            $rlCategories->listingsIncrease($crossed_cat_id);
                        }
                    }

                    $customUpdate = array(
                        'fields' => array(
                            'Status' => 'active',
                        ),
                        'where'  => array(
                            'ID' => $id,
                        ),
                    );
                }

                if ($customUpdate) {
                    $rlActions->updateOne($customUpdate, 'listings');
                }

                if ($listing_info['Featured'] && !$listing_info['Advanced_mode']) {
                    $customUpdate = array(
                        'fields' => array(
                            'Featured_ID'   => $listing_info['Plan_ID'],
                            'Featured_date' => 'NOW()',
                        ),
                        'where'  => array(
                            'ID' => $id,
                        ),
                    );
                    $rlActions->updateOne($customUpdate, 'listings');
                }
                break;

            case 'Plan_ID':
                $sql = "SELECT `Type`, `Cross`, `Featured`, `Advanced_mode` FROM `{db_prefix}listing_plans` WHERE `ID` = '{$value}'";
                $new_plan_info = $rlDb->getRow($sql);

                if (!$new_plan_info['Featured']) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_date` = '', `Featured_ID` = '' WHERE `ID` = {$id}";
                } elseif ($new_plan_info['Featured'] && $listing_info['Featured']) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_ID` = '{$value}' WHERE `ID` = {$id}";
                } elseif ($new_plan_info['Featured'] && !$listing_info['Featured']) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_date` = NOW(), `Featured_ID` = '{$value}' WHERE `ID` = {$id}";
                }

                if ($sql) {
                    $rlDb->query($sql);
                }

                if (!$new_plan_info['Cross'] && $listing_info['Cross']) {
                    $current_crossed = explode(',', $listing_info['Crossed']);
                    foreach ($current_crossed as $incrace_cc) {
                        $rlCategories->listingsDecrease($incrace_cc);
                    }
                    $sql = "UPDATE `{db_prefix}listings` SET `Crossed` = '' WHERE `ID` = '{$id}' LIMIT 1";
                    $rlDb->query($sql);
                } else if ($new_plan_info['Cross'] && !$listing_info['Cross']) {
                    $current_crossed = explode(',', $listing_info['Crossed']);
                    foreach ($current_crossed as $incrace_cc) {
                        $rlCategories->listingsIncrease($incrace_cc);
                    }
                }

                break;
        }

        $rlHook->load('apExtListingsAfterUpdate'); // > 4.1.0

        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $category_id = (int) $_GET['category_id'];

    /* run filters */
    $filters = array(
        'f_Type'         => true,
        'f_Category_ID'  => true,
        'f_Pay_date'     => true,
        'f_Plan_ID'      => true,
        'f_Status'       => true,
        'f_listing_id'   => true,
        'f_Account'      => true,
        'f_name'         => true,
        'f_email'        => true,
        'f_account_type' => true,
    );

    $rlHook->load('apExtListingsFilters');

    $where = '';
    foreach ($_GET as $filter => $val) {
        if (array_key_exists($filter, $filters)) {
            $filter_field = explode('f_', $filter);

            switch ($filter_field[1]) {
                case 'Type':
                    $where .= "`T3`.`Type` = '{$_GET[$filter]}' AND ";

                    break;

                case 'Pay_date':
                    $cond = $_GET[$filter] == 'payed' ? '<>' : '=';
                    $where .= "UNIX_TIMESTAMP(`T1`.`" . $filter_field[1] . "`) " . $cond . " 0 AND ";

                    break;

                case 'Account':
                    $account  = $rlDb->fetch('*', ['Username' => $_GET[$filter]], null, null, 'accounts', 'row');
                    $agencies = new Agencies();

                    if ($account && $agencies->isAgency($account)) {
                        $agencies->addSqlConditionGetListings(
                            $where,
                            $account['ID'],
                            ['startAnd' => false, 'endAnd' => true]
                        );
                    } else {
                        $where .= "`T2`.`Username` = '" . $_GET[$filter] . "' AND ";
                    }
                    break;

                case 'Category_ID':
                    $where .= "(`T1`.`{$filter_field[1]}` = '{$_GET[$filter]}' OR FIND_IN_SET('{$_GET[$filter]}', `T3`.`Parent_IDs`) > 0)  AND ";

                    break;

                case 'email':
                    $where .= "`T2`.`Mail` = '{$_GET[$filter]}' AND ";

                    break;

                case 'account_type':
                    $where .= "`T2`.`Type` = '{$_GET[$filter]}' AND ";

                    break;

                case 'listing_id':
                    $where .= "`T1`.`ID` = '{$_GET[$filter]}' AND ";

                    break;

                case 'name':
                    $words = explode(' ', $_GET[$filter]);
                    $where .= "(CONCAT_WS(' ', `T2`.`First_name`, `T2`.`Last_name`) LIKE '%" . implode("%' OR CONCAT_WS(' ', `T2`.`First_name`, `T2`.`Last_name`) LIKE '%", $words) . "%') AND ";
                    break;

                default:
                    if ($filter_field[1] == 'Status' && $_GET[$filter] == 'new') {
                        $new_period = empty($config['new_period']) ? 1 : $config['new_period'];
                        $new_period = $new_period * 86400;

                        $where .= "(UNIX_TIMESTAMP(`T1`.`Date`) + {$new_period}) >= UNIX_TIMESTAMP(NOW()) AND ";
                    } else {
                        $where .= isset($filters[$filter]['tb']) ? "`{$filters[$filter]['tb']}`." : "`T1`.";
                        $where .= "`" . $filter_field[1] . "` = '" . $_GET[$filter] . "' AND ";
                    }

                    break;
            }
        }
    }

    $allow_tmp_categories = 0;
    foreach ($rlListingTypes->types as $ltype) {
        if ($ltype['Cat_custom_adding']) {
            $allow_tmp_categories = 1;
        }
    }

    if (!empty($where)) {
        $where = 'AND ' . substr($where, 0, -4);
    }

    $transfer_fields = [
        'ID', 'Status', 'title', 'Type', 'Cat_title', 'Plan_ID', 'Featured_ID', 'Username', 'Account_ID', 'Plan_type',
        'Pay_date', 'Featured_date', 'Expired_date', 'Featured_expired_date', 'Date', 'Allow_photo', 'Allow_video',
    ];

    $sql = "SELECT SQL_CALC_FOUND_ROWS ";
    $sql .= "`T1`.*, `T2`.`Username`, `T3`.`Key` AS `Category_key`, `T3`.`Type` AS `Listing_type`, ";
    $sql .= "IF( (TIMESTAMPDIFF(HOUR, `T1`.`Featured_date`, NOW()) >= `T5`.`Listing_period` * 24) AND `T5`.`Listing_period` != 0, 1, 0) AS `Featured_expired`, ";

    if ($config['membership_module']) {
        $sql .= "IF (`T1`.`Plan_type` = 'listing',
            IF ((TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) >= `T4`.`Listing_period` * 24) AND `T4`.`Listing_period` != 0, 'expired', `T1`.`Status`),
            IF ((TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) >= `T7`.`Plan_period` * 24) AND `T7`.`Plan_period` != 0, 'expired', `T1`.`Status`)) as `Status`,";

        $sql .= "IF (`T1`.`Plan_type` = 'listing',
            IF( (TIMESTAMPDIFF(HOUR, `T1`.`Featured_date`, NOW()) >= `T5`.`Listing_period` * 24) AND `T5`.`Listing_period` != 0, 1, 0),
            IF( (TIMESTAMPDIFF(HOUR, `T1`.`Featured_date`, NOW()) >= `T8`.`Plan_period` * 24) AND `T8`.`Plan_period` != 0, 1, 0)) as `Featured_expired`,";
    }

    if ($config['membership_module']) {
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Key`, `T7`.`Key`) AS `Plan_key`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Price`, `T7`.`Price`) AS `Plan_price`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Listing_period`, `T7`.`Plan_period`) AS `Listing_period`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Image`, `T7`.`Image`) AS `Plan_image`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Image_unlim`, `T7`.`Image_unlim`) AS `Image_unlim`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Video`, `T7`.`Video`) AS `Plan_video`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T4`.`Video_unlim`, `T7`.`Video_unlim`) AS `Video_unlim`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'listing', `T5`.`Key`, `T8`.`Key`) AS `Featured_plan_key`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Pay_date`, INTERVAL `T7`.`Plan_period` DAY), DATE_ADD(`T1`.`Pay_date`, INTERVAL `T4`.`Listing_period` DAY)) AS `Expired_date`, ";
        $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Featured_date`, INTERVAL `T8`.`Plan_period` DAY), DATE_ADD(`T1`.`Featured_date`, INTERVAL `T5`.`Listing_period` DAY)) AS `Featured_expired_date`, ";
    } else {
        $sql .= "`T4`.`Key` AS `Plan_key`, `T4`.`Price` AS `Plan_price`, `T4`.`Listing_period`, `T4`.`Image` AS `Plan_image`, ";
        $sql .= "`T4`.`Image_unlim`, `T4`.`Video` AS `Plan_video`, `T4`.`Video_unlim`, `T5`.`Key` AS `Featured_plan_key`, ";
        $sql .= "DATE_ADD(`T1`.`Pay_date`, INTERVAL `T4`.`Listing_period` DAY) AS `Expired_date`, ";
        $sql .= "DATE_ADD(`T1`.`Featured_date`, INTERVAL `T5`.`Listing_period` DAY) AS `Featured_expired_date`, ";
    }

    $sql .= "IF(UNIX_TIMESTAMP(`T1`.`Pay_date`) = 0, 0, `T1`.`Pay_date`) AS `Pay_date` ";
    if ($allow_tmp_categories) {
        $sql .= ", `T6`.`Name` AS `Tmp_name` ";
    }
    $sql .= "FROM `{db_prefix}listings` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T4` ON `T1`.`Plan_ID` = `T4`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T5` ON `T1`.`Featured_ID` = `T5`.`ID` ";
    if ($config['membership_module']) {
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T8` ON `T1`.`Featured_ID` = `T8`.`ID` ";
    }

    if ($allow_tmp_categories) {
        $sql .= "LEFT JOIN `{db_prefix}tmp_categories` AS `T6` ON `T1`.`ID` = `T6`.`Listing_ID` ";
    }

    $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T1`.`Account_ID` > 0 {$where} AND ";
    $sql .= "IF(`T1`.`Status` = 'incomplete', `T1`.`Main_photo` OR `T1`.`Last_step` = 'checkout' OR `T1`.`Last_step` = 'photo', 1) ";

    $sql .= "ORDER BY `Date` DESC ";
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtListingsSql');

    $data  = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $reefless->loadClass('Listings');
    $reefless->loadClass('Common');

    $lang = array_merge($lang, $rlLang->getLangBySide('category'));

    foreach ($data as $key => $value) {
        $plan_name = $value['Plan_type'] == 'listing' ? $lang['listing_plans+name+' . $value['Plan_key']] : $lang['membership_plans+name+' . $value['Plan_key']];

        $listingTitle = $rlListings->getListingTitle($data[$key]['Category_ID'], $data[$key], $value['Listing_type']);

        /* collapsible row data */
        $src = empty($data[$key]['Main_photo']) ? RL_URL_HOME . 'templates/' . $config['template'] . '/img/no-picture.png' : RL_FILES_URL . $data[$key]['Main_photo'];
        $data[$key]['thumbnail'] = '<img style="border: 2px white solid;" alt="' . $listingTitle . '" title="' . $listingTitle . '" src="' . $src . '" />';

        $data[$key]['Allow_photo'] = ($value['Plan_image'] > 0 || $value['Image_unlim']) && $rlListingTypes->types[$value['Listing_type']]['Photo'] ? 1 : 0;
        $data[$key]['Allow_video'] = ($value['Plan_video'] > 0 || $value['Video_unlim']) && $rlListingTypes->types[$value['Listing_type']]['Video'] ? 1 : 0;
        $crossed = '';
        if ($_GET['f_Category_ID'] && in_array($_GET['f_Category_ID'], explode(',', $value['Crossed']))) {
            $crossed = ' <b>(' . $lang['crossed'] . ')</b>';
        }

        $data[$key]['data'] = $data[$key]['ID'];
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
        $data[$key]['Status_value'] = $value['Status'];
        $data[$key]['title'] = $listingTitle . $crossed;

        // Add titles of the listing type and all parent/child categories
        $data[$key]['Type'] = $rlListingTypes->types[$data[$key]['Listing_type']]['name'];
        $categoryBreadCrumbs = $rlCategories->getBreadCrumbs(
            $value['Category_ID'],
            null,
            $rlListingTypes->types[$value['Listing_type']]
        );

        foreach (array_reverse($categoryBreadCrumbs) as $categoryBreadCrumb) {
            $data[$key]['Type'] .= '/' . $categoryBreadCrumb['name'];
        }
        unset($categoryBreadCrumbs, $categoryBreadCrumb);

        $data[$key]['Type_key'] = $value['Listing_type'];
        $data[$key]['Cat_ID'] = $value['Category_ID'];
        $data[$key]['Cat_custom'] = $value['Tmp_name'] ? 1 : 0;
        $data[$key]['Username'] = empty($data[$key]['Account_ID']) ? $lang['administrator'] : $data[$key]['Username'];

        /* populate fields */
        $fields = $rlListings->getFormFields($value['Category_ID'], 'short_forms', $value['Listing_type']);

        $fields_html = '';

        if ($fields) {
            $fields_html = '<div style="margin: 0 0 0 10px"><table>';
            foreach ($fields as $fKey => $fValue) {
                if ($first) {
                    $html_value = $rlCommon->adaptValue(
                        $fValue,
                        $value[$fKey],
                        'listing',
                        $value['ID'],
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        $data[$key]['Type_key']
                    );
                } else {
                    if ($field['Condition'] == 'isUrl' || $field['Condition'] == 'isEmail') {
                        $html_value = $listings[$key][$item];
                    } else {
                        $html_value = $rlCommon->adaptValue(
                            $fValue,
                            $value[$fKey],
                            'listing',
                            $value['ID'],
                            null,
                            null,
                            null,
                            null,
                            null,
                            null,
                            $data[$key]['Type_key']
                        );
                    }
                }

                if ($html_value != '') {
                    $fields_html .= '<tr><td style="padding: 0 5px 4px;">' . $fValue['name'] . ':</td><td><b>' . $html_value . '</b></td></tr>';
                }
                $first++;
            }
            if ($data[$key]['Crossed']) {
                $crossed_details = '<ul class="ext_listing_info_list">';
                foreach (explode(',', $data[$key]['Crossed']) as $crossed_category_id) {
                    $category_info = $rlDb->fetch(array('Key'), array('ID' => $crossed_category_id), null, 1, 'categories', 'row');
                    $crossed_details .= '<li><a target="_blank" href="' . RL_URL_HOME . ADMIN . '/index.php?controller=browse&amp;id=' . $crossed_category_id . '">' . $lang['categories+name+' . $category_info['Key']] . '</a></li>';
                }
                $crossed_details .= '</ul>';

                $fields_html .= '<tr><td style="padding: 0 5px 4px;">' . $lang['crossed_categories'] . ':</td><td>' . $crossed_details . '</td></tr>';
            }

            $rlHook->load('apExtListingsDataMiddle');

            if (($data[$key]['Allow_photo'] || $data[$key]['Allow_video']) && $_SESSION['sessAdmin']['rights']['listings']['edit'] == 'edit') {
                $fields_html .= '<tr><td colspan="2" style="padding: 0 0 4px 5px;"><a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;action=photos&amp;id=' . $value['ID'] . '">' . $lang['manage_photos'] . '</a> (' . $value['Photos_count'] . ')</td></tr>';
            }

            $rlHook->load('apExtListingsDataBottom');

            $fields_html .= '</table></div>';
        }

        $data[$key]['fields'] = $fields_html;

        /* plan tooltip generation */
        $price = empty($data[$key]['Plan_price']) ? '<span style=color:#3cb524;>' . $lang['free'] . '</span>' : $data[$key]['Plan_price'];

        if (!empty($data[$key]['Plan_ID'])) {
            $plan_info = "
            <table class='info'>
            <tr><td>{$lang['plan_type']}:</td><td> <b>{$lang[($value['Plan_type'] == 'account' ? 'membership_type' : 'listing')]}</b><br /></td></tr>
            <tr><td>{$lang['price']}:</td><td> <b>{$price}</b><br /></td></tr>
            <tr><td>{$lang['days']}:</td><td> <b>{$data[$key]['Listing_period']}</b></td></tr>";
            if ($value['Plan_type'] == 'account') {
                $plan_info .= "<tr><td>{$lang['plan']}:</td><td> <b>{$plan_name}</b></td></tr>";
            }
            if ($value['Image_unlim'] && $rlListingTypes->types[$value['Listing_type']]['Photo']) {
                $plan_info_photo = $lang['unlimited'];
            } else if ($value['Plan_image'] > 0 && $rlListingTypes->types[$value['Listing_type']]['Photo']) {
                $plan_info_photo = $value['Plan_image'];
            } else {
                $plan_info_photo = $lang['not_available'];
            }
            $plan_info .= "<tr><td>{$lang['images']}:</td><td> <b>{$plan_info_photo}</b></td></tr>";

            if ($value['Video_unlim'] && $rlListingTypes->types[$value['Listing_type']]['Video']) {
                $plan_info_video = $lang['unlimited'];
            } else if ($value['Plan_video'] > 0 && $rlListingTypes->types[$value['Listing_type']]['Video']) {
                $plan_info_video = $value['Plan_video'];
            } else {
                $plan_info_video = $lang['not_available'];
            }
            $plan_info .= "<tr><td>{$lang['video']}:</td><td> <b>{$plan_info_video}</b></td></tr>";
            if (!empty($data[$key]['Featured_ID']) && $value['Plan_type'] != 'account') {
                $featured_expired = $data[$key]['Featured_expired'] ? '  <b>(' . $lang['expired'] . ')</b>' : '';
                $featured_pay_status = !empty($data[$key]['Featured_date']) ? $lang['payed'] : $lang['not_payed'];
                $plan_info .= "
                    <tr><td colspan='2'><span class=delete>{$lang['featured']}</span>" . $featured_expired . "</td></tr>

                    <tr><td>{$lang['plan']}:</td><td> <b>{$plan_name}</b></td></tr>
                ";
            }
            $plan_info .= "</table>";

            $data[$key]['Plan_name'] = $plan_name;
            $data[$key]['Plan_info'] = $plan_info;
        } else {
            $data[$key]['Plan_ID'] = '';
        }

        foreach ($value as $tr_field => $tr_value) {
            if (!in_array($tr_field, $transfer_fields)) {
                unset($data[$key][$tr_field]);
            }
        }

        $rlHook->load('apExtListingsData');
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;
    unset($data);

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpListingsTop');

    /* remote listing activation */
    if ($_GET['action'] == 'remote_activation' && $_GET['id'] && $_GET['hash']) {
        $remote_listing_id = (int) $_GET['id'];
        $remote_hash = $_GET['hash'];

        $sql = "SELECT `ID` FROM `{db_prefix}listings` WHERE `ID` = '{$remote_listing_id}' AND MD5(`Date`) = '{$remote_hash}' AND `Status` <> 'active' LIMIT 1";
        $remote_activation_info = $rlDb->getRow($sql);

        if ($remote_activation_info['ID'] == $remote_listing_id) {
            $activation_update = array(
                'fields' => array('Status' => 'active'),
                'where'  => array('ID' => $remote_listing_id),
            );

            $rlHook->load('apPhpListingsBeforeActivate');

            if ($rlActions->updateOne($activation_update, 'listings')) {
                $reefless->loadClass('Mail');
                $reefless->loadClass('Listings');
                $reefless->loadClass('Account');
                $reefless->loadClass('Common');

                $rlHook->load('apPhpListingsAfterActivate');

                /* get listing info */
                $sql = "SELECT `T1`.*, UNIX_TIMESTAMP(`T1`.`Pay_date`) AS `Payed`, `T1`.`Crossed`, `T1`.`Status`, ";
                $sql .= "`T1`.`Plan_ID`, `T3`.`Listing_period`, `T3`.`Type` AS `Plan_type`, `T3`.`Featured`, `T3`.`Advanced_mode`, `T4`.`Type` AS `Listing_type`, ";
                $sql .= "`T3`.`Cross` ";
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "RIGHT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
                $sql .= "RIGHT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
                $sql .= "WHERE `T1`.`ID` = '{$remote_listing_id}'";
                $listing_info = $rlDb->getRow($sql);

                /* get account info */
                $account_info = $rlAccount->getProfile((int) $listing_info['Account_ID']);

                $mail_tpl = $rlMail->getEmailTemplate('listing_activated', $account_info['Lang']);

                $reefless->loadClass('Categories');
                $category = $rlCategories->getCategory($listing_info['Category_ID']);

                /* increase listings counter */
                if (!empty($listing_info['Payed'])) {
                    $rlCategories->listingsIncrease($listing_info['Category_ID']);
                    $rlCategories->accountListingsIncrease($listing_info['Account_ID']);

                    /* crossed listings count control */
                    if (!empty($listing_info['Crossed'])) {
                        $crossed_cats = explode(',', trim($listing_info['Crossed'], ','));
                        foreach ($crossed_cats as $crossed_cat_id) {
                            $rlCategories->listingsIncrease($crossed_cat_id);
                        }
                    }
                }

                $listing_title = $rlListings->getListingTitle($listing_info['Category_ID'], $listing_info, $listing_info['Listing_type']);
                $link = $reefless->url('listing', $listing_info, $account_info['Lang']);

                $mail_tpl['body'] = str_replace(
                    array('{name}', '{link}'),
                    array($account_info['Full_name'], '<a href="' . $link . '">' . $link . '</a>'),
                    $mail_tpl['body']
                );
                $rlMail->send($mail_tpl, $account_info['Mail']);

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['notice_remote_activation_activated']);
            }
        } else {
            $reefless->loadClass('Notice');
            $errors[] = $lang['notice_remote_activation_deny'];

            $rlSmarty->assign_by_ref('errors', $errors);
        }
    } else {
        /* assign languages list */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        /* track referent controller */
        if ($cInfo['prev'] == 'browse') {
            $_SESSION['listings_redirect_mode'] = 'browse';
            $_SESSION['listings_redirect_ID'] = $_GET['category'];
        } elseif (!in_array($cInfo['prev'], array('browse', 'listings'))) {
            unset($_SESSION['listings_redirect_mode'], $_SESSION['listings_redirect_ID']);
        }

        if (!in_array($_GET['action'], array('photos', 'view'))) {
            $reefless->loadClass('ListingsAdmin', 'admin');
        }

        $reefless->loadClass('Categories');
        $reefless->loadClass('Plan');
        $reefless->loadClass('MembershipPlansAdmin', 'admin');
        $reefless->loadClass('Common');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Account');

        // get categories
        $sections = $rlCategories->getCatTree(0, false, true, true);
        $rlSmarty->assign_by_ref('sections', $sections);

        /* add new listing */
        $category_id = (int) $_GET['category'];

        if ($_GET['action'] == 'add') {
            if (!$category_id) {
                $reefless->redirect(array('controller' => $controller));
            }

            /* get current category information */
            $category = $rlCategories->getCategory($category_id);
            $rlSmarty->assign_by_ref('category', $category);
            $rlSmarty->assign_by_ref('category_id', $category_id);

            /* get posting type of listing */
            $listing_type = $rlListingTypes->types[$category['Type']];
            $rlSmarty->assign_by_ref('listing_type', $listing_type);

            /* change page title */
            $bcAStep[] = array('name' => $lang['add_listing']);
            $rlSmarty->assign('cpTitle', $category['name']);

            if ($category === false) {
                /* system error */
                trigger_error("Admin Panel | Can't load add listing page, category information missed", E_WARNING);
                $rlDebug->logger("Admin Panel | Can't load add listing page, category information missed");
                $sError = true;
            } else {
                $form = Category::buildForm(
                    $category,
                    $listing_type,
                    $rlCategories->fields
                );
                $rlSmarty->assign_by_ref('form', $form);

                if (empty($form)) {
                    // system error
                    trigger_error("Admin Panel | Can't load add listing page, form information missed", E_WARNING);
                    $rlDebug->logger("Admin Panel | Can't load add listing page, form information missed");

                    $link = RL_URL_HOME . ADMIN . '/index.php?controller=categories&amp;action=build&form=submit_form&amp;key=' . $category['Key'];

                    $message = str_replace('{category}', $category['name'], $lang['submit_form_empty']);
                    $message = preg_replace('/(\[(\pL*)\])/u', '<a href="' . $link . '">$2</a>', $message);
                    $rlSmarty->assign('alerts', $message);
                    $rlSmarty->assign('deny', true);
                } else {
                    /* get listing plans for current user type */
                    $plans = $rlPlan->getPlanByCategory($category_id);
                    $rlSmarty->assign_by_ref('plans', $plans);

                    /* listing adding */
                    if ($_POST['action'] == 'add') {
                        /* load fields list */
                        if (!$category_fields) {
                            $category_fields = $rlCategories->fields;
                        }

                        if (!empty($category_fields)) {
                            $data = $_POST['f'];
                        }

                        /* check owner */
                        $account_id = (int) $_POST['account_id'];
                        if (!$account_id) {
                            $errors[] = $lang['listing_owner_does_not_set'];
                            $error_fields[] = 'account_id';
                        } else {
                            // get account info
                            $account_info = $rlAccount->getProfile($account_id);
                            $rlSmarty->assign_by_ref('requested_username', $account_info['Username']);

                            if ($config['membership_module'] && !$config['allow_listing_plans']) {
                                if (isset($account_info['Plan_ID'])) {
                                    $plan_id = $account_info['Plan_ID'];
                                    $plan_info = $rlMembershipPlansAdmin->getAccountPlanInfo((int) $account_id);
                                    if ($plan_info['Advanced_mode']) {
                                        if ($plan_info[ucfirst($_POST['listing_type']) . '_listings'] > 0 && $plan_info[ucfirst($_POST['listing_type']) . '_remains'] <= 0) {
                                            $errors[] = $lang['listing_limit_exceeded_admin'];
                                            unset($_POST['listing_type']);
                                        }
                                    } else {
                                        if ($plan_info['Listing_number'] > 0 && $plan_info['Listing_remains'] <= 0) {
                                            $errors[] = $lang['listing_limit_exceeded_admin'];
                                        }
                                    }
                                }
                            }
                        }

                        /* check listing plans for current user type */
                        if (!$plan_id) {
                            $plan_id = (int) $data['l_plan'];
                            $plan_info = $plans[$plan_id];
                        }

                        if ($_POST['crossed_categories']) {
                            $crossed = $_POST['crossed_categories'];

                            $rlSmarty->assign_by_ref('crossed', $_POST['crossed_categories']);
                            $rlCategories->parentPoints($crossed);

                            $_SESSION['add_listing']['crossed_done'] = (int) $_POST['crossed_done'];
                        }

                        /* check advanced featured mode */
                        if ($plan_info['Featured'] && $plan_info['Advanced_mode']) {
                            $rest_option = $data['listing_type'] == 'standard' ? 'Featured' : 'Standard';

                            if (!$_POST['listing_type']) {
                                $errors[] = $lang['feature_mode_caption_error'];
                            } elseif ($plan_info['Package_ID'] && ($plan_info[ucfirst($data['listing_type']) . '_remains'] <= 0 && $plan_info[ucfirst($data['listing_type']) . '_listings'] > 0) && ($plan_info[$rest_option . '_remains'] > 0 || $plan_info[$rest_option . '_listings'] == 0)) {
                                $errors[] = $lang['feature_mode_access_hack'];
                            }
                        }

                        // check form fields
                        if ($data) {
                            if ($back = $rlCommon->checkDynamicForm($data, $category_fields, 'f', true)) {
                                foreach ($back as $error) {
                                    $errors[] = $error;
                                }
                            }
                        }

                        $rlHook->load('apPhpListingsValidate');

                        if ($errors) {
                            $rlSmarty->assign_by_ref('errors', $errors);
                        } else {
                            $reefless->loadClass('Actions');
                            $reefless->loadClass('Resize');

                            // copy account address to listing according to mapping in admin panel
                            $rlAccount->accountAddressAdd($data, $account_id);

                            $status = $_POST['status'];

                            /* prepare system listing data */
                            $info['Category_ID'] = $category['ID'];
                            $info['Account_ID'] = $account_id;
                            $info['Status'] = $status;
                            $info['Pay_date'] = 'NOW()';
                            $info['Date'] = 'NOW()';
                            $info['Plan_type'] = $config['membership_module'] && !$config['allow_listing_plans'] ? 'account' : 'listing';

                            if ($plan_info['Cross']) {
                                $info['crossed'] = implode(',', $_POST['crossed_categories']);
                            }
                            /* prepare system listing data end */

                            $rlHook->load('apPhpListingsBeforeAdd');

                            if ($rlListings->create($info, $data, $category_fields, $plan_info)) {
                                $reefless->loadClass('Notice');
                                $listing_id = $rlDb->insertID();

                                if ($info['Plan_type'] == 'account') {
                                    $rlMembershipPlansAdmin->handleAddListing($account_info, $plan_info, $_POST['listing_type']);
                                }

                                $rlHook->load('apPhpListingsAfterAdd');

                                /* increase listings counter */
                                if ($status == 'active') {
                                    $rlCategories->listingsIncrease($category['ID'], $listing_type['Key']);
                                    $rlCategories->accountListingsIncrease($account_id);

                                    /* crossed categories handler */
                                    if ($plan_info['Cross'] > 0 && !empty($_POST['crossed_categories'])) {
                                        foreach ($_POST['crossed_categories'] as $incrace_cc) {
                                            $rlCategories->listingsIncrease($incrace_cc, $listing_type['Key']);
                                        }
                                    }
                                }

                                $reefless->loadClass('Mail');

                                /* send message for listing owner */
                                $listing_title = $rlListings->getListingTitle($category['ID'], $data, $listing_type['Key']);
                                $link = $reefless->url('listing', $listing_id);

                                $mail_tpl = $rlMail->getEmailTemplate(
                                    $status == 'active'
                                    ? 'free_active_listing_created'
                                    : 'free_approval_listing_created',
                                    $account_info['Lang']
                                );
                                $mail_tpl['body'] = str_replace(
                                    array('{name}', '{link}'),
                                    array($account_info['Full_name'], '<a href="' . $link . '">' . $link . '</a>'),
                                    $mail_tpl['body']
                                );

                                $rlMail->send($mail_tpl, $account_info['Mail']);

                                $rlNotice->saveNotice($lang['notice_listing_added']);
                                if ($_SESSION['listings_redirect_mode']) {
                                    $aUrl = array("controller" => "browse", "id" => $_SESSION['listings_redirect_ID']);
                                } else {
                                    $aUrl = array("controller" => $controller);
                                }

                                $reefless->redirect($aUrl);
                            }
                        }
                    }
                }
                /* add listing end */
            }
        } elseif ($_GET['action'] == 'edit') {
            $listing_id = (int) $_GET['id'];

            /* get listing info */
            $sql = "SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T3`.`Type` AS `Listing_type` ";
            if ($config['membership_module'] && !$config['allow_listing_plans']) {
                $sql .= ", `T4`.`Username` ";
            }
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
            if ($config['membership_module'] && !$config['allow_listing_plans']) {
                $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T1`.`Account_ID` = `T4`.`ID` ";
            }
            $sql .= "WHERE `T1`.`ID` = '{$listing_id}' LIMIT 1";
            $listing = $rlDb->getRow($sql);
            $rlSmarty->assign_by_ref('listing_info', $listing);

            /* get listing form */
            if ($listing) {
                /* change page title */
                $listing_title = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);
                $bcAStep[] = array('name' => $lang['edit_listing']);
                $rlSmarty->assign('cpTitle', $listing_title);

                $df = $rlCategories->getDF();
                $rlSmarty->assign_by_ref('df', $df);

                /* get current listing category information */
                $category = $rlCategories->getCategory($listing['Category_ID']);
                $rlSmarty->assign_by_ref('category', $category);
                $rlSmarty->assign_by_ref('category_id', $listing['Category_ID']);

                /* get listing plans for current user type */
                if ($listing['Plan_type'] == 'account') {
                    $plans = $rlMembershipPlansAdmin->getPlans(false);
                } else {
                    $plans = $rlPlan->getPlanByCategory($category['ID']);
                }
                $rlSmarty->assign_by_ref('plans', $plans);

                if ($listing['Plan_ID']) {
                    $plan_info = $plans[$listing['Plan_ID']];
                    $rlSmarty->assign_by_ref('plan_info', $plan_info);
                }

                if ($category === false) {
                    /* system error */
                    trigger_error("Admin Panel | Can't load edit listing page, category information missed", E_WARNING);
                    $rlDebug->logger("Admin Panel | Can't load edit listing page, category information missed");
                    $sError = true;
                } else {
                    $form = Category::buildForm(
                        $category,
                        $rlListingTypes->types[$listing['Listing_type']],
                        $rlCategories->fields
                    );
                    $rlSmarty->assign_by_ref('form', $form);

                    if (empty($form)) {
                        /* system error */
                        trigger_error("Admin Panel | Can't load edit listing page, form information missed", E_WARNING);
                        $rlDebug->logger("Admin Panel | Can't load edit listing page, form information missed");

                        $errors[] = $lang['edit_listing_no_form_fields_error'];
                    } else {
                        $listing_fields = $rlCategories->fields;
                        $account_id = (int) $listing['Account_ID'];

                        $reefless->loadClass("Categories");

                        if ($listing['Plan_crossed']) {
                            $crossed = !empty($_POST['crossed_categories']) ? implode(',', $_POST['crossed_categories']) : $listing['Crossed'];
                            $rlSmarty->assign_by_ref('exp_cats', $crossed);

                            $rlSmarty->assign('pCats', explode(',', $crossed));

                            $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
                            $rlXajax->registerFunction(array('openTree', $rlCategories, 'ajaxOpenTree'));

                            $_SESSION['add_listing']['crossed_done'] = (int) $_POST['crossed_done'];
                        }

                        if (!isset($_POST['fromPost'])) {
                            /* set crossed categoris to post */
                            if (strpos($listing['Crossed'], ',') !== false && !empty($listing['Crossed'])) {
                                $_POST['crossed_categories'] = explode(',', $listing['Crossed']);
                            } elseif (strpos($listing['Crossed'], ',') === false && !empty($listing['Crossed'])) {
                                $_POST['crossed_categories'] = array($listing['Crossed']);
                            } else {
                                $_POST['crossed_categories'] = 0;
                            }
                            $_POST['crossed_done'] = $_SESSION['add_listing']['crossed_done'] = 1;

                            /* POST simulation */
                            $_POST['f']['l_plan'] = $listing['Plan_ID'];

                            foreach ($listing_fields as $key => $value) {
                                if ($listing[$listing_fields[$key]['Key']] != '') {
                                    switch ($listing_fields[$key]['Type']) {
                                        case 'mixed':
                                            $df_item = false;
                                            $df_item = explode('|', $listing[$listing_fields[$key]['Key']]);

                                            $_POST['f'][$listing_fields[$key]['Key']]['value'] = $df_item[0];
                                            $_POST['f'][$listing_fields[$key]['Key']]['df'] = $df_item[1];
                                            break;

                                        case 'date':
                                            if ($listing_fields[$key]['Default'] == 'single') {
                                                $_POST['f'][$listing_fields[$key]['Key']] = $listing[$listing_fields[$key]['Key']];
                                            } elseif ($listing_fields[$key]['Default'] == 'multi') {
                                                $_POST['f'][$listing_fields[$key]['Key']]['from'] = $listing[$listing_fields[$key]['Key']];
                                                $_POST['f'][$listing_fields[$key]['Key']]['to'] = $listing[$listing_fields[$key]['Key'] . '_multi'];
                                            }
                                            break;

                                        case 'phone':
                                            $_POST['f'][$listing_fields[$key]['Key']] = $reefless->parsePhone($listing[$listing_fields[$key]['Key']]);
                                            break;

                                        case 'price':
                                            $price = false;
                                            $price = explode('|', $listing[$listing_fields[$key]['Key']]);

                                            $_POST['f'][$listing_fields[$key]['Key']]['value'] = $price[0];
                                            $_POST['f'][$listing_fields[$key]['Key']]['currency'] = $price[1];
                                            break;

                                        case 'unit':
                                            $unit = false;
                                            $unit = explode('|', $listing[$listing_fields[$key]['Key']]);

                                            $_POST['f'][$listing_fields[$key]['Key']]['value'] = $unit[0];
                                            $_POST['f'][$listing_fields[$key]['Key']]['unit'] = $unit[1];
                                            break;

                                        case 'checkbox':
                                            $ch_items = null;
                                            $ch_items = explode(',', $listing[$listing_fields[$key]['Key']]);

                                            $_POST['f'][$listing_fields[$key]['Key']] = $ch_items;
                                            unset($ch_items);
                                            break;

                                        default:
                                            if (in_array($value['Type'], array('text', 'textarea')) && (($listing_fields[$key]['Multilingual'] && count($GLOBALS['languages']) > 1) || (bool) preg_match('/\{\|[\w]{2}\|\}/', $listing[$listing_fields[$key]['Key']]))) {
                                                $_POST['f'][$listing_fields[$key]['Key']] = $reefless->parseMultilingual($listing[$listing_fields[$key]['Key']]);
                                            } else {
                                                $_POST['f'][$listing_fields[$key]['Key']] = $listing[$listing_fields[$key]['Key']];
                                            }
                                            break;
                                    }
                                }
                            }

                            $_POST['status'] = $listing['Status'];
                            $_POST['account_id'] = $account_id;
                            $_POST['f']['l_plan'] = $listing['Plan_ID'];

                            if ($plan_info['Advanced_mode']) {
                                $_POST['listing_type'] = $listing['Featured_ID'] ? 'featured' : 'standard';
                            }

                            $rlHook->load('apPhpListingsPost');
                        } else {
                            // emulate existing data if user get a error about not filled data
                            if ($listing && $listing_fields) {
                                foreach ($listing_fields as $key => $value) {
                                    if ($listing[$listing_fields[$key]['Key']] != '') {
                                        switch ($listing_fields[$key]['Type']) {
                                            case 'image':
                                                $_POST['f_sys_exist'][$listing_fields[$key]['Key']] = $listing[$listing_fields[$key]['Key']];
                                                break;
                                        }
                                    }
                                }
                            }
                        }

                        if ($_POST['crossed_categories']) {
                            $crossed = $_POST['crossed_categories'];

                            $rlSmarty->assign_by_ref('crossed', $_POST['crossed_categories']);
                            $rlCategories->parentPoints($crossed);
                        }

                        /* get owner username */
                        if ($config['membership_module'] && !$config['allow_listing_plans'] && !$_POST['action']) {
                            $requested_username = $listing['Username'];
                        } else {
                            $requested_username = $rlDb->getOne('Username', "`ID` = " . $account_id, 'accounts');
                        }
                        $rlSmarty->assign_by_ref('requested_username', $requested_username);

                        /* listing editing */
                        if ($_POST['action'] == 'edit') {
                            $data = $_POST['f'];

                            if ($_POST['account_id']) {
                                $account_id = (int) $_POST['account_id'];

                                // get account details
                                $account_info = $rlAccount->getProfile($account_id);

                                if ($listing['Account_ID'] != $account_id) {
                                    if ($config['membership_module'] && !$config['allow_listing_plans']) {
                                        if (isset($account_info['Plan_ID'])) {
                                            $plan_id = $account_info['Plan_ID'];
                                            $plan_info = $rlMembershipPlansAdmin->getAccountPlanInfo((int) $account_id);
                                            if ($plan_info['Advanced_mode']) {
                                                if ($plan_info[ucfirst($_POST['listing_type']) . '_listings'] > 0 && $plan_info[ucfirst($_POST['listing_type']) . '_remains'] <= 0) {
                                                    $errors[] = $lang['listing_limit_exceeded_admin'];
                                                    unset($_POST['listing_type']);
                                                }
                                            } else {
                                                if ($plan_info['Listing_number'] > 0 && $plan_info['Listing_remains'] <= 0) {
                                                    $errors[] = $lang['listing_limit_exceeded_admin'];
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            // get plan info
                            $plan_id = $listing['Plan_type'] == 'account' ? $account_info['Plan_ID'] : (int) $data['l_plan'];
                            $plan_info = $plans[$plan_id];

                            if ($plan_id != $listing['Plan_ID']) {
                                $info['Pay_date'] = 'NOW()';
                                if ($plan_info['Featured']) {
                                    $info['Featured_date'] = 'NOW()';
                                    $info['Featured_ID'] = $plan_info['ID'];
                                }
                            }

                            /* clear featured status if package type changed to standard */
                            if ($plan_info['Type'] == 'package' && $listing['Featured_ID'] && $_POST['listing_type'] == 'standard') {
                                $info['Featured_date'] = '';
                                $info['Featured_ID'] = '';
                            }
                            /* clear featured status if package type changed to standard end */

                            // set featured status
                            if ($plan_info['Type'] == 'package' && !$listing['Featured_ID'] && $_POST['listing_type'] == 'featured') {
                                $info['Featured_date'] = 'NOW()';
                                $info['Featured_ID'] = $plan_info['ID'];
                            }

                            /* check owner */
                            if (!$account_id) {
                                $errors[] = $lang['listing_owner_does_not_set'];
                                $error_fields[] = 'account_id';
                            }

                            // check form fields
                            if (!empty($data)) {
                                if ($back = $rlCommon->checkDynamicForm($data, $listing_fields, 'f', true)) {
                                    foreach ($back as $error) {
                                        $errors[] = $error;
                                    }
                                }
                            }

                            $rlHook->load('apPhpListingsValidate');

                            if (!empty($errors)) {
                                $rlSmarty->assign_by_ref('errors', $errors);
                            } else {
                                $reefless->loadClass('Actions');
                                $reefless->loadClass('Resize');

                                $rlAccount->accountAddressAdd($data, $account_id);

                                $info['Status'] = $_POST['status'];
                                $info['Account_ID'] = $account_id;
                                $info['Plan_ID'] = $plan_id;

                                if ($plan_info['Cross']) {
                                    $info['Crossed'] = implode(',', $_POST['crossed_categories']);
                                }

                                $rlHook->load('apPhpListingsBeforeEdit');

                                if ($rlListings->edit($listing_id, $info, $data, $listing_fields, $plan_info)) {
                                    $rlHook->load('apPhpListingsAfterEdit');

                                    if ($listing['Account_ID'] != $account_id) {
                                        $rlCategories->accountListingsDecrease($listing['Account_ID']);
                                        $rlCategories->accountListingsIncrease($account_id);
                                        $rlMembershipPlansAdmin->handleEditListing($listing, $plan_info, $listing['Account_ID'], $account_id, $_POST['listing_type']);
                                    }

                                    if ($_POST['status'] == 'active' && $listing['Status'] != 'active') {
                                        if (in_array($listing['Status'], ['pending', 'incomplete'])) {
                                            $sql = "UPDATE `{db_prefix}listings` SET `Pay_date` = NOW() WHERE `ID` = {$listing_id}";
                                            $rlDb->query($sql);
                                        }
                                        $rlCategories->listingsIncrease($listing['Category_ID'], $listing_type['Key']);
                                        $rlCategories->accountListingsIncrease($listing['Account_ID']);
                                        $send_confirmation = true;
                                    } elseif ($_POST['status'] != 'active' && $listing['Status'] == 'active') {
                                        $rlCategories->listingsDecrease($listing['Category_ID'], $listing_type['Key']);
                                        $rlCategories->accountListingsDecrease($listing['Account_ID']);
                                        $send_confirmation = true;
                                    }

                                    /* crossed categories handler */
                                    if ($listing['Crossed']) {
                                        $current_crossed = explode(',', $listing['Crossed']);
                                        foreach ($current_crossed as $incrace_cc) {
                                            $rlCategories->listingsDecrease($incrace_cc, $listing_type['Key']);
                                        }
                                    }

                                    if ($plan_info['Cross'] > 0 && !empty($_POST['crossed_categories'])) {
                                        foreach ($_POST['crossed_categories'] as $incrace_cc) {
                                            $rlCategories->listingsIncrease($incrace_cc, $listing_type['Key']);
                                        }
                                    }

                                    /* send notification to listing owner */
                                    if ($send_confirmation) {
                                        /* get account info */
                                        $reefless->loadClass('Account');
                                        $account_info = $rlAccount->getProfile((int) $listing['Account_ID']);

                                        $reefless->loadClass('Mail');
                                        $mail_tpl = $rlMail->getEmailTemplate($_POST['status'] == 'active' ? 'listing_activated' : 'listing_deactivated', $account_info['Lang']);

                                        $link = $reefless->url('listing', $listing, $account_info['Lang']);

                                        $mail_tpl['body'] = str_replace(
                                            array('{name}', '{link}'),
                                            array($account_info['Full_name'], '<a href="' . $link . '">' . $link . '</a>'),
                                            $mail_tpl['body']
                                        );
                                        $rlMail->send($mail_tpl, $account_info['Mail']);
                                    }

                                    $reefless->loadClass('Notice');
                                    $rlNotice->saveNotice($lang['notice_listing_edited']);

                                    if ($_SESSION['listings_redirect_mode']) {
                                        $aUrl = array("controller" => "browse", "id" => $_SESSION['listings_redirect_ID']);
                                    } else {
                                        $aUrl = array("controller" => $controller);
                                    }
                                    $reefless->redirect($aUrl);
                                }
                            }
                        }
                        /* edit listing end */
                    }
                }
            } else {
                /* system error */
                trigger_error("Admin Panel | Can't load edit listing page, listing information missed", E_WARNING);
                $rlDebug->logger("Admin Panel | Can't load edit listing page, listing information missed");
                $sError = true;
            }
        } elseif ($_GET['action'] == 'photos') {
            $reefless->loadClass('Listings');
            $reefless->loadClass('Crop');
            $reefless->loadClass('Resize');

            $id = $_SESSION['admin_transfer']['listing_id'] = (int) $_GET['id'];

            $bcAStep[] = array(
                'name' => $lang['manage_photos'],
            );

            /* get listing info */
            $listing = $rlListings->getShortDetails($id, $plan_info = true);
            $rlSmarty->assign_by_ref('listing', $listing);
            $photos_allow = $listing['Plan_image'];

            /* define listing type */
            $listing_type = $rlListingTypes->types[$listing['Listing_type']];
            $rlSmarty->assign_by_ref('listing_type', $listing_type);

            /* simulate plan_info variable */
            $plan_info = array(
                'Image_unlim' => $listing['Image_unlim'],
                'Image'       => $listing['Plan_image'],
            );
            $rlSmarty->assign_by_ref('plan_info', $plan_info);

            $rlSmarty->assign_by_ref('allowed_photos', $plan_info['Image']);

            $rlXajax->registerFunction(array('reorderPhoto', $rlListings, 'ajaxReorderPhoto'));

            $max_file_size = (int) str_replace('M', '', ini_get('upload_max_filesize'));
            $rlSmarty->assign_by_ref('max_file_size', $max_file_size);

            $rlHook->load('apPhpListingsPhotos');
        } elseif ($_GET['action'] == 'video') {
            $reefless->loadClass('Listings');
            $reefless->loadClass('Crop');
            $reefless->loadClass('Resize');

            $id = (int) $_GET['id'];

            $bcAStep[] = array(
                'name' => $lang['manage_video'],
            );

            /* get listing info */
            $listing = $rlListings->getShortDetails($id, $plan_info = true);

            if (empty($id) || empty($listing)) {
                $sError = true;
            } elseif (!$listing['Plan_video'] && !$listing['Video_unlim']) {
                $alerts[] = $lang['no_video_allowed'];
                $rlSmarty->assign_by_ref('alerts', $alerts);
            } else {
                $rlSmarty->assign_by_ref('listing', $listing);

                /* get listing video */
                $rlDb->setTable('listing_photos');
                $videos = $rlDb->fetch(
                    array('ID', 'Photo', 'Thumbnail', 'Original'),
                    array('Listing_ID' => $id, 'Type' => 'Video'),
                    "ORDER BY `Position`"
                );
                $rlSmarty->assign_by_ref('videos', $videos);

                foreach ($videos as &$video) {
                    $video['Type'] = $video['Original'] == 'youtube' ? 'youtube' : 'local';
                    $video['Video'] = $video['Original'];
                    $video['Preview'] = $video['Original'] == 'youtube' ? $video['Photo'] : $video['Thumbnail'];
                }

                $video_allow = $listing['Plan_video'] - count($videos);
                $video_allow = $video_allow > 0 ? $video_allow : 0;

                $rlSmarty->assign_by_ref('video_allow', $video_allow);

                $max_file_size = ini_get('upload_max_filesize');
                $rlSmarty->assign_by_ref('max_file_size', $max_file_size);

                if ($_POST['upload']) {
                    if ($rlListings->uploadVideo($_POST['type'], $_POST['type'] == 'youtube' ? $_POST['youtube_embed'] : $_FILES, $id)) {
                        $reefless->loadClass('Notice');
                        $rlNotice->saveNotice($lang['uploading_completed']);
                        $aUrl = array(
                            'controller' => $controller,
                            'action'     => 'video',
                            'id'         => $_GET['id'],
                        );

                        $reefless->redirect($aUrl);
                    } else {
                        $rlSmarty->assign_by_ref('errors', $errors);
                    }
                }

                // Show error if video size is too big
                if (error_get_last()['type'] === 2
                    && $_SERVER['CONTENT_LENGTH']
                    && intval($_SERVER['CONTENT_LENGTH']) > Util::getMaxFileUploadSize()
                ) {
                    $rlNotice->saveNotice(
                        str_replace(
                            '{limit}',
                            Util::getMaxFileUploadSize() / (1024 * 1024),
                            $lang['error_maxFileSize']
                        ),
                        'errors'
                    );
                }

                $rlXajax->registerFunction(array('deleteVideo', $rlListingsAdmin, 'ajaxDelVideoFileAP'));
                $rlXajax->registerFunction(array('reorderVideo', $rlListings, 'ajaxReorderVideo'));
            }

            $rlHook->load('apPhpListingsVideo');
        } elseif ($_GET['action'] == 'view') {
            $reefless->loadClass('Listings');
            $reefless->loadClass('Account');
            $reefless->loadClass('Message');

            $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwnerAP'));

            /* populate tabs */
            $tabs = array(
                'listing' => array(
                    'key'  => 'listing',
                    'name' => $lang['listing'],
                ),
                'seller'  => array(
                    'key'  => 'seller',
                    'name' => $lang['seller_info'],
                ),
                'video'   => array(
                    'key'  => 'video',
                    'name' => $lang['video'],
                )
            );

            $rlSmarty->assign_by_ref('tabs', $tabs);

            $listing_id = (int) $_GET['id'];

            /* get listing info */
            $sql = "SELECT `T1`.*, `T2`.`Path`, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Category_key`, ";
            $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = '{$listing_id}' AND `T5`.`Status` = 'active' ";

            $rlHook->load('apListingDetailsSql', $sql, $listing_id); // >= v4.3

            $sql .= "LIMIT 1";

            $listing_data = $rlDb->getRow($sql);
            $listing_data['category_name'] = $lang['categories+name+' . $listing_data['Category_key']];

            $rlSmarty->assign_by_ref('listing_data', $listing_data);

            /* define listing type */
            $listing_type = $rlListingTypes->types[$listing_data['Listing_type']];
            $rlSmarty->assign_by_ref('listing_type', $listing_type);

            $bcAStep[] = array('name' => $lang['view_details']);

            $rlHook->load('apListingDetailsTop'); // >= v4.3

            /* build listing structure */
            $category_id = $listing_data['Category_ID'];
            $listing = $rlListings->getListingDetails($category_id, $listing_data, $listing_type);
            $rlSmarty->assign('listing', $listing);

            /* build location fields */
            $fields_list = $rlListings->fieldsList;

            $location = false;
            foreach ($fields_list as $key => $value) {
                if ($fields_list[$key]['Map'] && !empty($listing_data[$fields_list[$key]['Key']])) {
                    $location['search'] .= $value['value'] . ', ';
                    $location['show'] .= $lang[$value['pName']] . ': <b>' . $value['value'] . '<\/b><br />';
                }
            }
            if (!empty($location)) {
                $location['search'] = substr($location['search'], 0, -2);
            }

            if ($listing_data['Loc_latitude'] && $listing_data['Loc_longitude']) {
                $location['direct'] = $listing_data['Loc_latitude'] . ',' . $listing_data['Loc_longitude'];
            }
            $rlSmarty->assign_by_ref('location', $location);

            /* get listing title */
            $listing_title = $rlListings->getListingTitle($category_id, $listing_data, $listing_type['Key']);
            $rlSmarty->assign('cpTitle', $listing_title);

            /* get listing photos */
            $photos = $rlDb->fetch('*',
                array(
                    'Listing_ID' => $listing_id,
                    'Status'     => 'active',
                    'Type'       => 'picture',
                ),
                "AND `Thumbnail` <> '' AND `Photo` <> '' ORDER BY `Position`",
                $listing_data['Image'],
                'listing_photos'
            );
            $rlSmarty->assign_by_ref('photos', $photos);

            /* get listing video */
            $videos = $rlDb->fetch(
                array('ID', 'Original', 'Thumbnail', 'Photo'),
                array(
                    'Listing_ID' => $listing_id,
                    'Type'       => 'Video',
                ),
                "ORDER BY `Position`",
                $listing_data['Image'],
                'listing_photos'
            );

            foreach ($videos as &$video) {
                $video['Type'] = $video['Original'] == 'youtube' ? 'youtube' : 'local';
                $video['Video'] = $video['Original'];
                $video['Preview'] = $video['Original'] == 'youtube' ? $video['Photo'] : $video['Thumbnail'];
            }

            $rlSmarty->assign_by_ref('videos', $videos);

            /* get seller information */
            $seller_info = $rlAccount->getProfile((int) $listing_data['Account_ID']);
            $rlSmarty->assign_by_ref('seller_info', $seller_info);

            /* get amenties */
            if ($config['map_amenities']) {
                $rlDb->setTable('map_amenities');
                $amenities = $rlDb->fetch(array('Key', 'Default'), array('Status' => 'active'), "ORDER BY `Position`");
                $amenities = $rlLang->replaceLangKeys($amenities, 'map_amenities', array('name'));
                $rlSmarty->assign_by_ref('amenities', $amenities);
            }

            if (empty($videos) || !$listing_type['Video'] || ($listing_data['Video'] == 0 && !$listing_data['Video_unlim'])) {
                unset($tabs['video']);
            }
            if (!$config['map_module'] || !$location) {
                unset($tabs['map']);
            }

            $rlHook->load('apPhpListingsView');
        } else {
            /* get plans */
            $plans = $rlPlan->getPlans(array('listing', 'package', 'featured_direct'));
            $rlSmarty->assign_by_ref('plans', $plans);

            /* get featured plans */
            $featured_plans = $rlPlan->getPlans('featured');
            $rlSmarty->assign_by_ref('featured_plans', $featured_plans);

            /* get account types */
            $reefless->loadClass('Account');
            $account_types = $rlAccount->getAccountTypes('visitor');
            $rlSmarty->assign_by_ref('account_types', $account_types);

            foreach ($plans as $pk => $plan) {
                $filter_plans[$plan['ID']] = $plan;
            }

            $filters = array(
                'Type'        => array('phrase' => $lang['listing_type'], 'items' => $rlListingTypes->types),
                'Category_ID' => array('phrase' => $lang['category'], 'items' => null),
                'Plan_ID'     => array('phrase' => $lang['plan'], 'items' => $filter_plans),
                'Status'      => array('phrase' => $lang['status'], 'items' => array(
                    'new'        => $lang['new'],
                    'active'     => $lang['active'],
                    'approval'   => $lang['approval'],
                    'pending'    => $lang['pending'],
                    'incomplete' => $lang['incomplete'],
                    'expired'    => $lang['expired'],
                ),
                ),
                'Pay_date'    => array('phrase' => $lang['pay_status'], 'items' => array(
                    'payed'     => $lang['payed'],
                    'not_payed' => $lang['not_payed'],
                ),
                ),
            );
            $rlSmarty->assign_by_ref('filters', $filters);

            /* define remote status request */
            if (in_array($_GET['status'], array('new', 'approval', 'active', 'pending', 'incomplete', 'expired'))) {
                $rlSmarty->assign_by_ref('status', $_GET['status']);
            }
        }

        /* register ajax methods */
        $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
        $rlXajax->registerFunction(array('openTree', $rlCategories, 'ajaxOpenTree'));
        $rlXajax->registerFunction(array('massActions', $rlListingsAdmin, 'ajaxMassActions'));
        $rlXajax->registerFunction(array('deleteListing', $rlListingsAdmin, 'ajaxDeleteListingAdmin'));
        $rlXajax->registerFunction(array('makeFeatured', $rlListingsAdmin, 'ajaxMakeFeatured'));
        $rlXajax->registerFunction(array('annulFeatured', $rlListingsAdmin, 'ajaxAnnulFeatured'));
        $rlXajax->registerFunction(array('moveListing', $rlListingsAdmin, 'ajaxMoveListing'));
        $rlXajax->registerFunction(array('deleteListingFile', $rlListings, 'ajaxDeleteListingFile'));
        $rlXajax->registerFunction(array('checkMemebershipPlan', $rlMembershipPlansAdmin, 'ajaxCheckMemebershipPlan'));

        $rlHook->load('apPhpListingsBottom');
    }
}
