<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PLANS_USING.INC.PHP
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

        $rlHook->load('apExtPlansUsingUpdate');

        $rlActions->updateOne($updateData, 'listing_packages');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $dir = $rlValid->xSql($_GET['dir']);

    if ($_GET['action'] == 'search') {
        if (!empty($_GET['Username'])) {
            $where .= "`T4`.`Username` = '" . $_GET['Username'] . "' AND ";
        }

        if (!empty($_GET['Plan_ID'])) {
            $where .= "`T1`.`Plan_ID` = '" . (int) $_GET['Plan_ID'] . "' AND ";
        }

        if (!empty($_GET['Type'])) {
            $where .= "`T1`.`Type` = '" . $_GET['Type'] . "' AND ";
        }

        if ($where) {
            $where = substr($where, 0, -4);
            $where = "WHERE {$where} ";
        }
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.`ID`, `T1`.`Account_ID`, `T1`.`Type`, ";
    $sql .= "`T1`.`Listings_remains`, `T1`.`Standard_remains`, `T1`.`Featured_remains`, ";
    if ($GLOBALS['config']['membership_module']) {
        $sql .= "IF (`T1`.`Type` = 'account', `T5`.`Key`, `T2`.`Key`) AS `Key`, ";
        $sql .= "IF (`T1`.`Type` = 'account', `T5`.`Price`, `T2`.`Price`) AS `Price`, ";
        $sql .= "IF (`T1`.`Type` = 'account', `T5`.`Advanced_mode`, `T2`.`Price`) AS `Advanced_mode`, ";
        $sql .= "IF (`T1`.`Type` = 'account', '', `T2`.`Limit`) AS `Limit`, ";
    } else {
        $sql .= "`T2`.`Key`, `T2`.`Price`, `T2`.`Limit`, `T2`.`Advanced_mode`, ";
    }
    $sql .= "`T4`.`Username`, `T1`.`Date` ";
    $sql .= "FROM `{db_prefix}listing_packages` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T2`.`ID` = `T1`.`Plan_ID` ";
    if ($GLOBALS['config']['membership_module']) {
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T5` ON `T5`.`ID` = `T1`.`Plan_ID` AND `T1`.`Type` = 'account' ";
    }
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T1`.`Account_ID` = `T4`.`ID` ";
    $sql .= "{$where} ORDER BY `T1`.`Date` LIMIT {$start}, {$limit}";

    $rlHook->load('apExtPlansUsingSql');

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Type_key'] = $value['Type'];
        if ($value['Type'] == 'account') {
            $data[$key]['Plan_name'] = $GLOBALS['lang']['membership_plans+name+' . $value['Key']];
            $data[$key]['Type'] = $GLOBALS['lang']['membership'];
        } else {
            $data[$key]['Plan_name'] = $GLOBALS['lang']['listing_plans+name+' . $value['Key']];
            $data[$key]['Type'] = $GLOBALS['lang'][$data[$key]['Type'] . '_plan'];
        }
    }

    $rlHook->load('apExtPlansUsingData');

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpPlansUsingTop');

    $reefless->loadClass('Plan');
    $reefless->loadClass('Account');

    if ($_GET['action'] == 'add') {
        $bcAStep = $lang['grant_plan'];

        $plans = $rlPlan->getPlans('package');
        $rlSmarty->assign_by_ref('plans', $plans);

        if ($_POST['submit']) {
            /* check account */
            $account_id = (int) $_POST['account_id'];
            if (!$account_id) {
                $errors[] = str_replace('{field}', "<b>" . $lang['username'] . "</b>", $lang['notice_field_empty']);
                $error_fields[] = 'account_id';
            } else {
                $account_data = $rlAccount->getProfile($account_id);
                $rlSmarty->assign_by_ref('account_data', $account_data);
            }

            /* check package */
            $package_id = (int) $_POST['package_id'];

            if (!$package_id) {
                $errors[] = str_replace('{field}', "<b>" . $lang['package_plan_short'] . "</b>", $lang['notice_field_empty']);
                $error_fields[] = 'package_id';
            }

            /* check plan availablity */
            if ($rlDb->getOne('ID', "`Account_ID` = '{$account_id}' AND `Plan_ID` = '{$package_id}'", 'listing_packages')) {
                $errors[] = str_replace('{username}', $account_data['Username'], $lang['plan_granted_in_use_notice']);
                $error_fields[] = 'package_id';
            }

            $rlHook->load('apPhpPlansUsingValidate');

            if ($errors) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* grant a plan */
                if ($rlPlan->grantPlan($account_id, $package_id)) {
                    $rlHook->load('apPhpPlansUsingAfterGrant');

                    /* send notification to user */
                    $reefless->loadClass('Mail');
                    $mail_tpl = $rlMail->getEmailTemplate('grant_plan', $account_data['Lang']);

                    if ($mail_tpl) {
                        $package_key = $rlDb->getOne('Key', "`ID` = '{$package_id}'", 'listing_plans');
                        $package_name = $lang['listing_plans+name+' . $package_key];
                        $package_desc = $lang['listing_plans+des+' . $package_key];

                        $mail_tpl['subject'] = str_replace('{plan_name}', $package_name, $mail_tpl['subject']);

                        $link = $reefless->getPageUrl('add_listing');
                        $link = '<a href="' . $link . '">' . $link . '</a>';

                        $find = array('{plan_name}', '{plan_description}', '{name}', '{add_listing}');
                        $replace = array($package_name, $package_desc, $account_data['Full_name'], $link);

                        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                        $rlMail->send($mail_tpl, $account_data['Mail']);
                    }

                    $rlNotice->saveNotice($lang['plan_granted_notice']);
                    $reefless->redirect(array("controller" => $controller));
                } else {
                    $rlDebug->logger('Can\'t grant a package, $rlPlan -> grantPlan() method fail');
                }
            }
        }
    } else {
        $plans = $rlPlan->getPlans();
        $rlSmarty->assign_by_ref('plans', $plans);

        /* register ajax methods */
        $rlXajax->registerFunction(array('deletePlanUsing', $rlAdmin, 'ajaxDeletePlanUsing'));
    }

    $rlHook->load('apPhpPlansUsingBottom');
}
