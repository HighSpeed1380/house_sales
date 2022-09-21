<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PAYMENT_GATEWAYS.INC.PHP
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

        if ($field == 'Default' && $id) {
            $rlDb->query("UPDATE `{db_prefix}payment_gateways` SET `Default` = '0' WHERE `ID` != {$id}");
            $value = ($value == 'true') ? '1' : '0';
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtPaymentGatewaysUpdate'); // >= v4.4

        $rlActions->updateOne($updateData, 'payment_gateways');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}payment_gateways` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('payment_gateways+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    if ($sort) {
        switch ($sort) {
            case 'name':
                $sortField = "`T1`.`Value`";
                break;

            default:
                $sortField = "`T1`.`{$sort}`";
                break;
        }

        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }

    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtPaymentGatewaysSql'); // >= v4.4

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status_key'] = $data[$key]['Status'];
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Type'] = $GLOBALS['lang']['gateway_type_' . $data[$key]['Type']];
        $data[$key]['Recurring'] = $value['Recurring'] ? $GLOBALS['lang']['yes'] : $GLOBALS['lang']['no'];
        $data[$key]['Default'] = (bool) $value['Default'];
    }

    $rlHook->load('apExtPaymentGatewaysData'); // >= v4.4

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */
else {
    /* get account types */
    $reefless->loadClass('PaymentGateways', 'admin');

    /* get all languages */
    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    if (isset($_GET['action'])) {
        $bcAStep = $lang['edit_gateway'];

        $i_key = $rlValid->xSql($_GET['item']);

        $gateway_info = $rlPaymentGateways->get($i_key);
        $gateway_settings = $rlPaymentGateways->getSettings();

        $rlSmarty->assign_by_ref('gateway_info', $gateway_info);
        $rlSmarty->assign_by_ref('gateway_settings', $gateway_settings);

        if ($lang[$gateway_info['Key'] . '_notice']) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang[$gateway_info['Key'] . '_notice'], 'alerts');
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            // simulate post
            $_POST['key'] = $gateway_info['Key'];
            $_POST['position'] = $gateway_info['Position'];
            $_POST['status'] = $gateway_info['Status'];
            $_POST['recurring'] = $gateway_info['Recurring'];
            $_POST['recurring_editable'] = $gateway_info['Recurring_editable'];
            $_POST['default'] = $gateway_info['Default'];
            $_POST['type'] = $gateway_info['Type'];

            // get names
            $i_names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'payment_gateways+name+' . $i_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($i_names as $nKey => $nVal) {
                $_POST['name'][$nVal['Code']] = $nVal['Value'];
            }

            /**
             * @since 4.6.0
             */
            $rlHook->load('apPaymentGatewaysSimulatePost', $gateway_info, $gateway_settings, $i_key);

            // set page name
            $rlSmarty->assign_by_ref('cpTitle', $_POST['name'][RL_LANG_CODE]);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            // check name
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                }
                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            // check required options
            $required = explode(',', $gateway_info['Required_options']);
            if ($required) {
                foreach ($required as $rKey => $rVal) {
                    if (empty($rVal)) {
                        continue;
                    }
                    if (empty($_POST['post_config'][$rVal])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang[$rVal] . "</b>", $lang['notice_field_empty']);
                    }
                }
            }

            /**
             * @since 4.6.0
             */
            $rlHook->load('apPaymentGatewaysValidate', $errors, $i_key);

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                if ($_GET['action'] == 'edit') {
                    $update_data = array(
                        'fields' => array(
                            'Status' => $_POST['status'],
                            'Position' => (int) $_POST['position'],
                            'Recurring' => (int) $_POST['recurring'],
                        ),
                        'where' => array('Key' => $i_key),
                    );

                    if ($i_key == '2co') {
                        $update_data['fields']['Form_type'] = $_POST['post_config']['2co_method'] == 'simple' ? 'offsite' : 'custom';
                    }

                    /**
                     * @since 4.6.0 - Added $update_data, $i_key options
                     */
                    $rlHook->load('apPhpPaymentGatewaysBeforeEdit', $update_data, $i_key);

                    $action = $GLOBALS['rlActions']->updateOne($update_data, 'payment_gateways');

                    // update settings
                    $rlPaymentGateways->updateSettings($_POST['post_config']);

                    $rlHook->load('apPhpPaymentGatewaysAfterEdit'); // >= v4.4

                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'payment_gateways+name+{$i_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit names
                            $update_phrases = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']],
                                ),
                                'where' => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key' => 'payment_gateways+name+' . $i_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_phrases, 'lang_keys');
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code' => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key' => 'payment_gateways+name+' . $i_key,
                                'Value' => $_POST['name'][$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }
                    }

                    $message = $lang['payment_gateway_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $rlHook->load('apPhpPaymentGatewaysBeforeRedirect'); // >= v4.4

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    $rlHook->load('apPhpPaymentGatewaysBottom'); // >= v4.4
}
