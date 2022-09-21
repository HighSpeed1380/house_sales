<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_GROUPS.INC.PHP
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

        $rlHook->load('apExtListingGroupsUpdate');

        $rlActions->updateOne($updateData, 'listing_groups');

        /* update cache */
        $reefless->loadClass('Cache');
        $rlCache->updateForms();

        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $rlHook->load('apExtListingGroupsSql');

    $rlDb->setTable('listing_groups');
    $data = $rlDb->fetch('*', null, "WHERE `Status` <> 'trash' ORDER BY `Key` ASC", array($start, $limit));
    $data = $rlLang->replaceLangKeys($data, 'listing_groups', array('name'), RL_LANG_CODE, 'admin');
    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        $data[$key]['Display'] = $data[$key]['Display'] ? $GLOBALS['lang']['yes'] : $GLOBALS['lang']['no'];
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
    }

    $rlHook->load('apExtListingGroupsData');

    $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}listing_groups` WHERE `Status` <> 'trash'");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpListingGroupsTop');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_group'] : $lang['edit_group'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $s_key = $rlValid->xSql($_GET['group']);

            // get current group info
            $group_info = $rlDb->fetch('*', array('Key' => $s_key), "AND `Status` <> 'trash'", null, 'listing_groups', 'row');

            $_POST['key'] = $group_info['Key'];
            $_POST['status'] = $group_info['Status'];
            $_POST['display'] = $group_info['Display'];

            // get names
            $s_names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'listing_groups+name+' . $s_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($s_names as $nKey => $nVal) {
                $_POST['name'][$s_names[$nKey]['Code']] = $s_names[$nKey]['Value'];
            }

            $rlHook->load('apPhpListingGroupsPost');
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
                    $error_fields[] = 'key';
                }

                $f_key = $rlValid->str2key($f_key);

                $exist_key = $rlDb->fetch(
                    array('Key', 'Status'),
                    array('Key' => $f_key),
                    null,
                    null,
                    'listing_groups',
                    'row'
                );

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_group_exist']);

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
                    $error_fields[] = 'name[' . $lval['Code'] . ']';
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            $rlHook->load('apPhpListingGroupsValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // write main group information
                    $data = array(
                        'Key'     => $f_key,
                        'Status'  => $_POST['status'],
                        'Display' => $_POST['display'],
                    );

                    $rlHook->load('apPhpListingGroupsBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'listing_groups')) {
                        $rlHook->load('apPhpListingGroupsAfterAdd');

                        // write name's phrases
                        foreach ($allLangs as $key => $value) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'listing_groups+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['group_added'];
                        $aUrl = array("controller" => $controller, "action" => "add");
                    } else {
                        trigger_error("Can't add new lisitng field's group (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new lisitng field's group (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array('Status' => $_POST['status'], 'Display' => $_POST['display']),
                        'where'  => array('Key' => $f_key),
                    );

                    $rlHook->load('apPhpListingGroupsBeforeEdit');

                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'listing_groups');

                    $rlHook->load('apPhpListingGroupsAfterEdit');

                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'listing_groups+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit names
                            $update_names = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'listing_groups+name+' . $f_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_names, 'lang_keys');
                        } else {
                            // insert names
                            $insert_names = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'listing_groups+name+' . $f_key,
                                'Value'  => $_POST['name'][$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_names, 'lang_keys');
                        }
                    }

                    /* update cache */
                    $rlCache->updateForms();

                    $message = $lang['group_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    $rlHook->load('apPhpListingGroupsBottom');

    $reefless->loadClass('Categories');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteFGroup', $rlCategories, 'ajaxDeleteFGroup'));
}
