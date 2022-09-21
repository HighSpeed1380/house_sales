<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MULTI_FORMATS.INC.PHP
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
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = $rlValid->xSql($_GET['id']);
        $key = $rlValid->xSql($_GET['key']);

        if ($_REQUEST['parent']) {
            if ($field == 'Default') {
                $parent = $rlDb->getOne('Parent_ID', '`ID`=' . $id, 'multi_formats');

                $uncheckall = "
                    UPDATE `{db_prefix}multi_formats` SET `Default` = '0'
                    WHERE `Parent_ID` = {$parent} AND `Default` = '1'
                ";
                $rlDb->query($uncheckall);

                $value = ($value == 'true') ? '1' : '0';
            } elseif ($field == 'Status') {
                $sql = "
                    UPDATE `{db_prefix}multi_formats`
                    SET `Status` = '{$value}'
                    WHERE FIND_IN_SET('{$id}', `Parent_IDs`) > 0
                ";
                $rlDb->query($sql);
            }
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where' => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtDataFormatsUpdate');

        $rlDb->updateOne($updateData, 'multi_formats');

        if ($field == 'Status') {
            $reefless->loadClass('MultiFieldAP', null, 'multiField');
            $rlMultiFieldAP->saveFormatKeys();
            $rlMultiFieldAP->saveGeoFormatData();
        }

        $rlCache->updateDataFormats();
        $rlCache->updateForms();
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);
    $search_name = $_GET['action'] == 'search' ? $rlValid->xSql($_GET['Name']) : false;
    $parent = $rlValid->xSql($_GET['parent']);

    if (intval($parent)) {
        $parent_id = $parent;
        $parent_key = $rlDb->getOne('Key', "`ID` = {$parent_id}", 'multi_formats');
    } else {
        $parent_key = $parent;
        $parent_id = $rlDb->getOne('ID', "`Key` = '{$parent_key}'", 'multi_formats');
    }

    if ($parent) {
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Value` as `name` ";

        if ($config['mf_multilingual_path']) {
            $sql .= ", `T1`.`Path_{$config['lang']}` AS `Path`";
        }

        $sql .= ", `TLP`.`Value` AS `Parent_name`, `T1`.`Default` ";
        $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_" . RL_LANG_CODE . "` AS `T2` ON `T1`.`Key` = `T2`.`Key` ";
        $sql .= "LEFT JOIN `{db_prefix}multi_formats` AS `TP` ON `TP`.`ID` = `T1`.`Parent_ID` ";
        $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_" . RL_LANG_CODE . "` AS `TLP` ON `TLP`.`Key` = `TP`.`Key` ";

        $sql .= "WHERE 1 ";

        if ($_GET['Search_all_levels']) {
            $sql .= "AND `T1`.`Key` LIKE '{$parent_key}\_%' ";
        } else {
            $sql .= "AND `T1`.`Parent_ID` = {$parent_id} ";
        }

        if ($search_name) {
             $sql .= "AND `T2`.`Value` LIKE '{$search_name}%' ";
        }

        $sql .= "GROUP BY `T1`.`ID` ";
    } else {
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Value` as `name` ";
        $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('data_formats+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "'";
        $sql .= "AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `Parent_ID` = 0 ";
    }

    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }

    $sql .= "LIMIT {$start},{$limit}";

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
        $data[$key]['Default'] = (bool) $value['Default'];
    }

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    /**
     * Run update210 and update220 manually
     *
     * @todo - Remove this code when the xajax will removed from plugin update process
     */
    if (!isset($GLOBALS['config']['mf_format_keys'])) {
        $reefless->loadClass('MultiField', null, 'multiField');
        $rlMultiField->update210();
        $rlMultiField->update220();
    }

    $reefless->loadClass('MultiFieldAP', null, 'multiField');

    unset($_SESSION['mf_import']);

    if ($config['mf_geo_data_format']) {
        $geo_format_data = json_decode($config['mf_geo_data_format'], true);
        $rlSmarty->assign_by_ref('geo_format_data', $geo_format_data);
    }

    // Get all languages
    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        switch ($_GET['action']) {
            case 'add':
                $bcAStep = $lang['mf_add_item'];
                break;
            case 'edit':
                $bcAStep = $lang['mf_edit_item'] . " " . $lang['data_formats+name+' . $_GET['item']];
                break;
        }

        $f_key = $rlValid->xSql($_GET['item']);
        $item_info = $rlDb->fetch('*', array('Key' => $f_key), null, 1, 'multi_formats', 'row');
        $rlSmarty->assign_by_ref('item_info', $item_info);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['key'] = $f_key;
            $_POST['status'] = $item_info['Status'];
            $_POST['geo_filter'] = $item_info['Geo_filter'];
            $_POST['order_type'] = $rlDb->getOne('Order_type', "`Key` = '{$f_key}'", "data_formats");

            $names = $rlDb->fetch(
                array('Code', 'Value'),
                array('Key' => 'data_formats+name+' . $f_key),
                "AND `Status` <> 'trash'", null, 'lang_keys'
            );
            foreach ($names as $name) {
                $_POST['name'][$name['Code']] = $name['Value'];
            }
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            if ($_GET['action'] == 'add') {
                $f_key = $_POST['key'];
                $f_key = $rlValid->str2key($f_key);

                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                /* check key exist (in add mode only) */
                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(array('Key'), array('Key' => $f_key), null, null, 'data_formats');
                if (!empty($exist_key)) {
                    $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_key_exist']);
                    $error_fields[] = 'key';
                }
            }

            /* check names */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    $data = array(
                        'Key' => $f_key,
                        'Status' => $_POST['status'],
                        'Geo_filter' => $_POST['geo_filter'],
                        'Position' => $rlDb->getOne("Position", "1 ORDER BY `Position` DESC", "data_formats") + 1,
                    );

                    if ($action = $rlDb->insertOne($data, 'multi_formats')) {
                        $parent_id = $rlDb->insertID();

                        $rlMultiFieldAP->saveFormatKeys();

                        $format_insert = array(
                            'Key' => $f_key,
                            'Parent_ID' => 0,
                            'Position' => $rlDb->getOne('Position', "`Parent_ID` = 0 ORDER BY `Position` DESC", 'data_formats') + 1,
                            'Status' => 'active',
                            'Order_type' => $_POST['order_type']
                        );

                        if ($rlDb->insertOne($format_insert, 'data_formats')) {
                            foreach ($allLangs as $language) {
                                $lang_keys[] = array(
                                    'Code' => $language['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key' => 'data_formats+name+' . $f_key,
                                    'Value' => $f_name[$language['Code']],
                                );
                            }
                            $rlDb->insert($lang_keys, 'lang_keys');
                        }

                        $message = $lang['notice_item_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new data format (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new data format (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_data = array(
                        'fields' => array(
                            'Status' => $_POST['status'],
                            'Geo_filter' => isset($_POST['geo_filter']) ? $_POST['geo_filter'] : $item_info['Geo_filter'],
                        ),
                        'where' => array('Key' => $f_key)
                    );

                    $action = $rlDb->updateOne($update_data, 'multi_formats');

                    if ($item_info['Status'] != $_POST['status']) {
                        $rlMultiFieldAP->saveFormatKeys();
                    }

                    $update_data = array(
                        'fields' => array('Order_type' => $_POST['order_type']),
                        'where' => array('Key' => $f_key),
                    );

                    $rlDb->updateOne($update_data, 'data_formats');

                    foreach ($allLangs as $language) {
                        if ($rlDb->getOne('ID', "`Key` = 'data_formats+name+{$f_key}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                            // edit name's values
                            $update_names = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$language['Code']],
                                ),
                                'where' => array(
                                    'Code' => $language['Code'],
                                    'Key' => 'data_formats+name+' . $f_key,
                                ),
                            );

                            // update
                            $rlDb->updateOne($update_names, 'lang_keys');
                        } else {
                            // insert names
                            $insert_names = array(
                                'Code' => $language['Code'],
                                'Module' => 'common',
                                'Key' => 'data_formats+name+' . $f_key,
                                'Value' => $_POST['name'][$language['Code']],
                            );

                            // insert
                            $rlDb->insertOne($insert_names, 'lang_keys');
                        }
                    }

                    $message = $lang['notice_item_edited'];
                    $aUrl = array('controller' => $controller);
                }

                // Clear geo format data in config
                if (isset($_POST['geo_filter']) && !$_POST['geo_filter'] && $geo_format_data['Key'] == $f_key) {
                    $rlConfig->setConfig('mf_geo_data_format', '');
                }
                // Set get format data in config
                elseif ($_POST['geo_filter'] && !$geo_format_data) {
                    $rlMultiFieldAP->saveGeoFormatData();
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                } else {
                    trigger_error("Can't edit datafomat (MYSQL failed)", E_WARNING);
                    $rlDebug->logger("Can't edit datafomat (MYSQL failed)");
                }
            }
        }
    } else {
        if ($parent_id = intval($_GET['parent'])) {
            $parent_info = $rlMultiFieldAP->getFormatData($parent_id);
            $rlSmarty->assign_by_ref('parent_info', $parent_info);
        }

        $head_level_data = [];
        $rlSmarty->assign_by_ref('head_level_data', $head_level_data);

        // Append item bread crumbs
        if ($parent_id) {
            $allLangs = $GLOBALS['languages'];
            $rlSmarty->assign_by_ref('allLangs', $allLangs);

            $item_bread_crumbs = $rlMultiFieldAP->getBreadCrumbs($parent_id);

            if ($item_bread_crumbs) {
                $item_bread_crumbs = array_reverse($item_bread_crumbs);

                $head_level_data = $item_bread_crumbs[0];

                foreach ($item_bread_crumbs as $bKey => $bVal) {
                    $item_bread_crumbs[$bKey]['Controller'] = 'multi_formats';
                    $item_bread_crumbs[$bKey]['Vars'] = 'parent=' . $item_bread_crumbs[$bKey]['ID'];
                }

                $bcAStep = $item_bread_crumbs;
            }
        }

        // Get parent item path data
        if ($parent_info) {
            $parent_path = $parent_info;

            if ($config['mf_geo_subdomains_type'] == 'mixed' && $config['mf_geo_subdomains']) {
                foreach ($parent_path as $field_key => &$field_value) {
                    if (strpos($field_key, 'Path') === 0) {
                        if ($field_value) {
                            $path_exp = explode('/', $field_value);
                            $field_value = array(
                                'host' => array_shift($path_exp),
                                'dir'  => implode('/', $path_exp)
                            );
                        }
                    }
                }
            }

            $rlSmarty->assign_by_ref('parent_path', $parent_path);
        }

        $level = $rlMultiFieldAP->getLevel($parent_id);
        $rlSmarty->assign('level', $level);

        $order_type = $rlDb->getOne('Order_type', "`Key` = '{$head_level_data['Key']}'", 'data_formats');
        $rlSmarty->assign('order_type', $order_type);

        $rlSmarty->assign_by_ref('domain_info', $domain_info);

        // Get related fields data
        $sql = "SELECT * FROM `{db_prefix}listing_fields` WHERE `Condition` = '{$head_level_data['Key']}' AND `Key` ";
        $sql .= $level ? "REGEXP 'level{$level}'" : "NOT REGEXP 'level[0-9]'";
        $related_listing_fields = $rlDb->getAll($sql);
        $related_listing_fields = $rlLang->replaceLangKeys($related_listing_fields, 'listing_fields', array('name'));
        $rlSmarty->assign('related_listing_fields', $related_listing_fields);

        $sql = "SELECT * FROM `{db_prefix}account_fields` WHERE `Condition` = '{$head_level_data['Key']}' AND `Key` ";
        $sql .= $level ? "REGEXP 'level{$level}'" : "NOT REGEXP 'level[0-9]'";
        $related_account_fields = $rlDb->getAll($sql);
        $related_account_fields = $rlLang->replaceLangKeys($related_account_fields, 'account_fields', array('name'));
        $rlSmarty->assign('related_account_fields', $related_account_fields);
    }

    $rlXajax->registerFunction(array('deleteItem', $rlMultiFieldAP, 'ajaxDeleteItem'));
    $rlXajax->registerFunction(array('deleteFormat', $rlMultiFieldAP, 'ajaxDeleteFormat'));

    $rlXajax->registerFunction(array('listSources', $rlMultiFieldAP, 'ajaxListSources'));
    $rlXajax->registerFunction(array('expandSource', $rlMultiFieldAP, 'ajaxExpandSource'));
    $rlXajax->registerFunction(array('importSource', $rlMultiFieldAP, 'ajaxImportSource'));
}
