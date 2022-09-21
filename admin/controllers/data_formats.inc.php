<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: DATA_FORMATS.INC.PHP
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

        if ($field == 'Default') {
            $parent = $rlDb->getOne('Parent_ID', '`ID`=' . $id, 'data_formats');

            $uncheckall = "UPDATE `{db_prefix}data_formats` SET `Default` = '0' WHERE `Parent_ID`='" . $parent . "' AND `ID` !='" . $id . "'";
            $rlDb->query($uncheckall);

            $value = ($value == 'true') ? '1' : '0';
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtDataFormatsUpdate');

        $rlActions->updateOne($updateData, 'data_formats');

        $rlCache->updateDataFormats();
        $rlCache->updateForms();

        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);
    $format = $rlValid->xSql($_GET['format']);

    $parent = 0;
    if ($format) {
        $parent = $rlDb->getOne('ID', "`Key` = '{$format}'", 'data_formats');
    }

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.`ID`, `T1`.`Position`, `T1`.`Order_type`, `T1`.`Key`, `T1`.`Status`, `T1`.`Default`, `T1`.`Rate`, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}data_formats` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('data_formats+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T1`.`Parent_ID` = {$parent} ";
    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start},{$limit}";

    $rlHook->load('apExtDataFormatsSql');

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
        $data[$key]['Order_type'] = $lang[$value['Order_type'] . '_order'];
        $data[$key]['Default'] = (bool) $value['Default'];
        $data[$key]['Rate'] = $value['Rate'] == 1 ? $value['Rate'] . " (" . $lang['base_unit'] . ")" : $value['Rate'];
    }

    $rlHook->load('apExtDataFormatsData');

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    /* additional bread crumb step */
    switch ($_GET['action']) {
        case 'add':
            $bcAStep = $lang['add_format'];
            break;
        case 'edit':
            $bcAStep = $lang['edit_format'];
            break;
    }

    if ($_GET['mode'] == 'manage') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $c_format = $_GET['format'];
        $bcAStep = $lang['data_formats+name+' . $c_format];

        /* get format info */
        $format_info = $rlDb->fetch(array('Order_type', 'Conversion'), array('Key' => $c_format), null, 1, 'data_formats', 'row');
        $rlSmarty->assign_by_ref('format_info', $format_info);
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $key = $rlValid->xSql($_GET['format']);

            // get current section info
            $item_info = $rlDb->fetch('*', array('Key' => $key), "AND `Status` <> 'trash'", null, 'data_formats', 'row');

            $_POST['key'] = $item_info['Key'];
            $_POST['status'] = $item_info['Status'];
            $_POST['order_type'] = $item_info['Order_type'];
            $_POST['conversion'] = $item_info['Conversion'];

            // get names
            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'data_formats+name+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $nKey => $nVal) {
                $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }

            $rlHook->load('apPhpDataFormatsPost');
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $_POST['key'];
            $f_key = $rlValid->str2key($f_key);

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
                    'data_formats',
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

            /* check name */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }
            }

            $rlHook->load('apPhpDataFormatsValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}data_formats`");

                    // write main section information
                    $data = array(
                        'Key'        => $f_key,
                        'Status'     => $_POST['status'],
                        'Order_type' => $_POST['order_type'],
                        'Conversion' => $_POST['conversion'],
                        'Parent_ID'  => 0,
                    );

                    $rlHook->load('apPhpDataFormatsBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'data_formats')) {
                        $rlHook->load('apPhpDataFormatsAfterAdd');

                        // write name's phrases
                        foreach ($allLangs as $key => $value) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'data_formats+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['notice_format_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new data format (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new data format (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Order_type' => $_POST['order_type'],
                            'Conversion' => $_POST['conversion'],
                            'Status'     => $_POST['status'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    $rlHook->load('apPhpDataFormatsBeforeEdit');

                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'data_formats');

                    $rlHook->load('apPhpDataFormatsAfterEdit');

                    // edit name's values
                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'data_formats+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // update
                            $lang_keys_name = array(
                                'fields' => array(
                                    'Value' => $f_name[$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'data_formats+name+' . $f_key,
                                ),
                            );

                            $rlActions->updateOne($lang_keys_name, 'lang_keys');
                        } else {
                            // insert
                            $insert_phrase = array(
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                                'Key'    => 'data_formats+name+' . $f_key,
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                            );

                            $rlActions->insertOne($insert_phrase, 'lang_keys');
                        }
                    }

                    $message = $lang['notice_format_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                } else {
                    trigger_error("Can't edit datafomats (MYSQL problems)", E_WARNING);
                    $rlDebug->logger("Can't edit datafomats (MYSQL problems)");
                }
            }
        }
    }

    $rlHook->load('apPhpDataFormatsBottom');

    $reefless->loadClass('Formats', 'admin');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteFormat', $rlFormats, 'ajaxDeleteFormat'));
    $rlXajax->registerFunction(array('deleteItem', $rlFormats, 'ajaxDeleteItem'));
    $rlXajax->registerFunction(array('addItem', $rlFormats, 'ajaxAddItem'));
    $rlXajax->registerFunction(array('editItem', $rlFormats, 'ajaxEditItem'));
    $rlXajax->registerFunction(array('prepareEdit', $rlFormats, 'ajaxPrepareEdit'));
    $rlXajax->registerFunction(array('dfItemsMassActions', $rlFormats, 'ajaxDfItemsMassActions'));
}
