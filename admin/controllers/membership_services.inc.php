<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MEMBERSHIP_SERVICES.INC.PHP
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

        $rlHook->load('apExtMembershipServicesUpdate');
        $rlActions->updateOne($updateData, 'membership_services');
        exit;
    }

    // data read
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}membership_services` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('membership_services+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
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

    $rlHook->load('apExtMembershipServicesSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
    }

    $rlHook->load('apExtMembershipServicesData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} else {
    $rlHook->load('apPhpMembershipServicesTop');
    $reefless->loadClass('MembershipPlansAdmin', 'admin');

    // additional bread crumb step
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_service'] : $lang['edit_service'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // assign all languages
        $rlSmarty->assign_by_ref('allLangs', $GLOBALS['languages']);

        $service_id = (int) $_GET['id'];

        // get current service info
        if ($service_id) {
            $service_info = $rlDb->fetch('*', array('ID' => $service_id), "AND `Status` <> 'trash'", null, 'membership_services', 'row');
            $rlSmarty->assign_by_ref('service_info', $service_info);
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $rlMembershipPlansAdmin->simulateServicePost($service_info);
            $rlHook->load('apPhpMembershipServicesPost');
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

            // check service price
            /*if ($_POST['price'] == '') {
            $errors[] = str_replace('{field}', '<b>"'. $lang['price'] .'</b>"', $lang['notice_field_empty']);
            $error_fields[] = 'price';
            }*/

            $rlHook->load('apPhpMembershipServicesValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                // add/edit action
                if ($_GET['action'] == 'add') {
                    if ($action = $rlMembershipPlansAdmin->addService($_POST)) {
                        $message = $lang['service_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new membership service (MYSQL problems)", E_USER_WARNING);
                        $rlDebug->logger("Can't add new membership service (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    if ($action = $rlMembershipPlansAdmin->editService($_POST)) {
                        $message = $lang['service_edited'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't update membership service (MYSQL problems)", E_USER_WARNING);
                        $rlDebug->logger("Can't update membership service (MYSQL problems)");
                    }
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    $rlHook->load('apPhpMembershipServicesBottom');

    // register ajax methods
    $rlXajax->registerFunction(array('deleteService', $rlMembershipPlansAdmin, 'ajaxDeleteService'));
}
