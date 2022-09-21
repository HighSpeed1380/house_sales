<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_FIELDS.INC.PHP
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

        $rlHook->load('apExtListingFieldsUpdate');

        $rlActions->updateOne($updateData, 'listing_fields');

        /* update cache */
        $reefless->loadClass('Cache');
        $rlCache->updateForms();

        exit;
    }

    /* prepare arrange fields */
    foreach ($rlListingTypes->types as $lt) {
        if ($lt['Arrange_field']) {
            $arrange_keys[$lt['Arrange_field']] = $lt['name'];
        }
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $langCode = $rlValid->xSql($_GET['lang_code']);
    $phrase = $rlValid->xSql($_GET['phrase']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.`ID`, `T1`.`Key`, `T1`.`Type`, `T1`.`Required`, `T1`.`Add_page`, `T1`.`Details_page`, `T1`.`Map`, `T1`.`Status`, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}listing_fields` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('listing_fields+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T1`.`ID` > 0 ";

    if ($_GET['action'] == 'search') {
        $search_fields = array('Name', 'Type', 'Required', 'Map', 'Status');

        foreach ($search_fields as $item) {
            if ($_GET[$item] != '') {
                $s_value = $rlValid->xSql($_GET[$item]);
                $sql .= $item == 'Name' ? "AND `T2`.`Value` LIKE '%{$s_value}%' " : "AND `T1`.`{$item}` = '{$s_value}' ";
            }
        }
    }

    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtListingFieldsSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        $data[$key]['Type'] = $l_types[$data[$key]['Type']];
        $data[$key]['Required'] = $data[$key]['Required'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Map'] = $data[$key]['Map'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Add_page'] = $data[$key]['Add_page'] ? true : false;
        $data[$key]['Arrange'] = isset($arrange_keys[$value['Key']]) ? $arrange_keys[$value['Key']] : false;
    }

    $rlHook->load('apExtListingFieldsData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpListingFieldsTop');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_field'] : $lang['edit_field'];
    }

    /* get all languages */
    $allLangs = $rlLang->getLanguagesList('all');
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    /* get all data formats */
    $bind_data_formats = $rlDb->fetch(array('Key', 'ID'), array('Parent_ID' => '0', 'Status' => 'active'), "AND `Key` <> 'currency' ORDER BY `Position`", null, 'data_formats');
    $bind_data_formats = $rlLang->replaceLangKeys($bind_data_formats, 'data_formats', 'name', RL_LANG_CODE, 'admin');

    $rlSmarty->assign_by_ref('data_formats', $bind_data_formats);

    /* load fields class */
    $reefless->loadClass('Fields', 'admin');
    $rlFields->table = 'listing_fields';
    $rlFields->source_table = 'listings';
    $rlSmarty->assign('grid_key', 'listingFieldsGrid');

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        $rlSmarty->assign('agreement_pages', Util::getMinorPages());

        if ($_GET['action'] == 'edit') {
            /* system fields */
            $sys_fields = array('posted_by', 'Category_ID');
            $rlSmarty->assign('sys_fields', $sys_fields);

            $e_key = $rlValid->xSql($_GET['field']);

            // get current field information
            $field_info = $rlDb->fetch('*', array('Key' => $e_key), "AND `Status` <> 'trash'", null, $rlFields->table, 'row');
            $rlSmarty->assign_by_ref('field_info', $field_info);

            if (empty($field_info)) {
                $errors[] = $lang['notice_field_not_found'];
            } else {
                if (!$_POST['fromPost']) {
                    $rlFields->simulatePost($e_key, $field_info);

                    $rlHook->load('apPhpListingFieldsTop');
                }
            }
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_type = $_POST['type'];

            /* check key */
            $f_key = $rlValid->xSql($_POST['key']);

            if (!utf8_is_ascii($f_key)) {
                $f_key = utf8_to_ascii($f_key);
            }

            if (strlen($f_key) < 3 || $f_key == 'name') {
                $errors[] = $lang['incorrect_phrase_key'];
                $error_fields[] = 'key';
            }

            $f_key = $_GET['action'] == 'add' ? $rlValid->str2key($f_key) : $rlValid->xSql($f_key);

            /* check key exist (in add mode only) */
            if ($_GET['action'] == 'add' && $f_key) {
                $exist_key = $rlDb->getRow("SHOW FIELDS FROM `" . RL_DBPREFIX . $rlFields->source_table . "` WHERE `Field` LIKE '{$f_key}'");

                if ($exist_key) {
                    $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_field_exist']);
                    $error_fields[] = 'key';
                }
            }

            /* check name */
            foreach ($allLangs as $lang_item) {
                if (empty($_POST['name'][$lang_item['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$lang_item['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = 'name[' . $lang_item['Code'] . ']';
                }
            }

            /* check field type */
            if (empty($f_type)) {
                $errors[] = $lang['notice_type_empty'];
                $error_fields[] = 'type';
            }

            /*________________ check type's fields ________________*/

            /* select/mixed type */
            if ($_POST['data_format'] || $_POST['mixed_data_format']) {
                $f_data['data_format'] = $_POST['data_format'];
                $f_data['mixed_data_format'] = $_POST['mixed_data_format'];
            }

            /* check mixed type */
            if ($f_type == 'mixed') {
                if (!$_POST['mixed'][1][$config['lang']] && !$_POST['data_format'] && !$_POST['mixed_data_format']) {
                    $errors[] = $lang['notice_mixed_df_empty'];
                }
            }

            /* check date type */
            if ($f_type == 'date' && empty($_POST['date']['mode'])) {
                $errors[] = $lang['notice_mode_not_chose'];
            }

            /* check file type */
            if ($f_type == 'file' && empty($_POST['file']['type'])) {
                $errors[] = $lang['notice_type_empty'];
            }

            // check agreement field
            if ($f_type == 'accept' && empty($_POST['accept_page'])) {
                $errors[] = str_replace('{field}', "<b>{$lang['agreement_page']}</b>", $lang['notice_field_empty']);
                $error_fields[] = 'accept_page';
            }

            /*______________ check type's fields end ______________*/

            $rlHook->load('apPhpListingFieldsValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    $rlHook->load('apPhpListingFieldsBeforeAdd');

                    $action = $rlFields->createField($f_type, $f_key, $allLangs);
                    $new_field_id = $rlFields->addID;

                    $rlHook->load('apPhpListingFieldsAfterAdd');

                    $message = $lang['field_added'];
                    $aUrl = array("controller" => $controller, "action" => "add");
                } elseif ($_GET['action'] == 'edit') {
                    $rlHook->load('apPhpListingFieldsBeforeEdit');

                    $action = $rlFields->editField($f_type, $e_key, $allLangs);

                    $rlHook->load('apPhpListingFieldsAfterEdit');

                    $message = $lang['field_edited'];
                    $aUrl = array("controller" => $controller);

                    /* update cache */
                    $rlCache->updateForms();
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    $rlHook->load('apPhpListingFieldsBottom');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteLField', $rlFields, 'ajaxDeleteLField'));
}
