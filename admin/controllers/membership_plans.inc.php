<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MEMBERSHIP_PLANS.INC.PHP
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

// ext js action
if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    // load system lib
    require_once RL_LIBS . 'system.lib.php';

    // data update
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

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

        //unset image/video unlim trigger if you change the option value directly from the grid
        if ($field == 'Image') {
            $updateData['fields']['Image_unlim'] = '0';
        } elseif ($field == 'Video') {
            $updateData['fields']['Video_unlim'] = '0';
        }

        $rlHook->load('apExtMembershipPlansUpdate');
        $rlActions->updateOne($updateData, 'membership_plans');
        exit;
    }

    // data read
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name`, `T3`.`Plan_ID` AS `Subscription` ";
    $sql .= "FROM `{db_prefix}membership_plans` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('membership_plans+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T3` ON `T1`.`ID` = `T3`.`Plan_ID` AND `T3`.`Service` = 'membership' AND `T3`.`Status` = 'active' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    if ($sort) {
        switch ($sort) {
            case 'name':
                $sortField = "`T2`.`Value`";
                break;

            default:
                $sortField = "`T1`.`{$sort}`";
                break;
        }
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtMembershipPlansSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Subscription'] = $value['Subscription'] ? $GLOBALS['lang']['yes'] : $GLOBALS['lang']['no'];
    }

    $rlHook->load('apExtMembershipPlansData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} else {
    $rlHook->load('apPhpMembershipPlansTop');

    $reefless->loadClass('MembershipPlansAdmin', 'admin');
    $reefless->loadClass('Account');
    $reefless->loadClass('Payment');
    $reefless->loadClass('Subscription');

    $account_types = $rlAccount->getAccountTypes('visitor');
    $rlSmarty->assign_by_ref('account_types', $account_types);
    $available_account_types = $rlMembershipPlansAdmin->getAccountTypes('ID');
    $rlSmarty->assign_by_ref('available_account_types', $available_account_types);

    // assing get all languages to smarty
    $rlSmarty->assign_by_ref('allLangs', $GLOBALS['languages']);

    $services = $rlMembershipPlansAdmin->getServices();
    $rlSmarty->assign_by_ref('membership_services', $services);

    // get subscription option
    $subscription_options = $rlSubscription->getPlanOptions();
    $rlSmarty->assign_by_ref('subscription_options', $subscription_options);

    // additional bread crumb step
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_membership_plan'] : $lang['edit_membership_plan'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // assign all languages
        $rlSmarty->assign_by_ref('allLangs', $GLOBALS['languages']);

        $p_key = $rlValid->xSql($_GET['plan']);

        // get current plan info
        if ($p_key) {
            $plan_info = $rlDb->fetch('*', array('Key' => $p_key), "AND `Status` <> 'trash'", null, 'membership_plans', 'row');
            $rlSmarty->assign_by_ref('plan_info', $plan_info);
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $rlMembershipPlansAdmin->simulatePost($plan_info);
            $rlHook->load('apPhpMembershipPlansPost');
        }

        if (isset($_POST['submit'])) {
            // check name
            $f_name = $_POST['name'];
            $f_description = $_POST['description'];

            foreach ($GLOBALS['languages'] as $lkey => $lval) {
                if (empty($f_name[$lval['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$lval['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }
                $f_names[$lval['Code']] = $f_name[$lval['Code']];
            }

            // check plan period
            if ($_POST['plan_period'] == '') {
                $errors[] = str_replace('{field}', '<b>"' . $lang['plan_live_for'] . '</b>"', $lang['notice_field_empty']);
                $error_fields[] = 'plan_period';
            }

            // check listing number | package, non advanced mode
            $service_add_listing = 0;
            foreach ($services as $sKey => $sVal) {
                if ($sVal['Key'] == 'add_listing') {
                    $service_add_listing = $sVal['ID'];
                }
            }
            if ($_POST['listing_number'] == '' && in_array($service_add_listing, (array) $_POST['services'])) {
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

            $rlHook->load('apPhpMembershipPlansValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                // add/edit action
                if ($_GET['action'] == 'add') {
                    if ($action = $rlMembershipPlansAdmin->add($_POST)) {
                        $message = $lang['plan_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new membership plan (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new membership plan (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    if ($action = $rlMembershipPlansAdmin->edit($_POST, $p_key)) {
                        $message = $lang['plan_edited'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't update membership plan (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't update membership plan (MYSQL problems)");
                    }
                }

                if ($action) {
                    // save subscription options
                    $rlSubscription->savePlanOptions('membership', $rlMembershipPlansAdmin->getPlanID(), (double) $_POST['price']);

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    $rlHook->load('apPhpMembershipPlansBottom');

    // register ajax methods
    $rlXajax->registerFunction(array('deletePlan', $rlMembershipPlansAdmin, 'ajaxDeletePlan'));
    $rlXajax->registerFunction(array('prepareDeleting', $rlMembershipPlansAdmin, 'ajaxPrepareDeleting'));
}
