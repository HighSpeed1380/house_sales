<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: EMAIL_TEMPLATES.INC.PHP
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

        $rlHook->load('apExtEmailTemplatesUpdate');

        $rlActions->updateOne($updateData, 'email_templates');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `subject` ";
    $sql .= "FROM `{db_prefix}email_templates` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('email_templates+subject+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($sort) {
        switch ($sort) {
            case 'subject':
                $sortField = "`T2`.`Value`";
                break;

            default:
                $sortField = "`T1`.`{$sort}`";
                break;
        }
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtEmailTemplatesSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
        $data[$key]['Type'] = $value['Type'] == 'plain' ? $lang['plain_text'] : $lang['html_code'];
    }

    $rlHook->load('apExtEmailTemplatesData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpEmailTemplatesTop');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_template'] : $lang['edit_template'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $t_key = $rlValid->xSql($_GET['tpl']);

            if (in_array($t_key, array('contact_owner', 'contact_owner_user', 'tell_friend', 'contact_us'))) {
                $l_email_variables[] = '{from_mail}';
            }

            // get current template info
            $tpl_info = $rlDb->fetch('*', array('Key' => $t_key), null, null, 'email_templates', 'row');

            $_POST['key'] = $tpl_info['Key'];
            $_POST['type'] = $tpl_info['Type'];
            $_POST['status'] = $tpl_info['Status'];

            // get subjects
            $subject = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'email_templates+subject+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($subject as $nKey => $nVal) {
                $_POST['name'][$subject[$nKey]['Code']] = $subject[$nKey]['Value'];
            }

            // get bodies
            $bodies = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'email_templates+body+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($bodies as $nKey => $nVal) {
                $_POST['description'][$bodies[$nKey]['Code']] = $bodies[$nKey]['Value'];
            }

            $rlHook->load('apPhpEmailTemplatesPost');
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
                    $error_fields[] = "key";
                }

                $exist_key = $rlDb->fetch(array('Key'), array('Key' => $f_key), null, null, 'email_templates');

                if (!empty($exist_key)) {
                    $errors[] = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_template_exist']);
                    $error_fields[] = "key";
                }
            }

            $f_key = $rlValid->str2key($f_key);

            /* check subject */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['subject'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }
            }

            /* check email body */
            $f_description = $_POST['description'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_description[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['content'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "description[{$lval['Code']}]";
                }
            }

            $rlHook->load('apPhpEmailTemplatesValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}email_templates`");

                    // write main template information
                    $data = array(
                        'Key'      => $f_key,
                        'Status'   => $_POST['status'],
                        'Type'     => $_POST['type'],
                        'Position' => $position['max'] + 1,
                    );

                    $rlHook->load('apPhpEmailTemplatesBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'email_templates')) {
                        $rlHook->load('apPhpEmailTemplatesAfterAdd');

                        // write name's phrases
                        foreach ($allLangs as $language) {
                            $lang_keys[] = array(
                                'Code'   => $language['Code'],
                                'Module' => 'email_tpl',
                                'Status' => 'active',
                                'Key'    => 'email_templates+subject+' . $f_key,
                                'Value'  => $f_name[$language['Code']],
                            );

                            $description_value = $_POST['description'][$language['Code']];
                            if ($_POST['type'] == 'html') {
                                $description_value = str_replace(array('<br />', '<br/>', '<br>'), '', $description_value);
                            }
                            $lang_keys[] = array(
                                'Code'   => $language['Code'],
                                'Module' => 'email_tpl',
                                'Status' => 'active',
                                'Key'    => 'email_templates+body+' . $f_key,
                                'Value'  => $description_value,
                            );
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['template_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new lisitng section (MYSQL problems)", E_USER_WARNING);
                        $rlDebug->logger("Can't add new lisitng section (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Status' => $_POST['status'],
                            'Type'   => $_POST['type'],
                        ),
                        'where'  => array(
                            'Key' => $f_key,
                        ),
                    );

                    $rlHook->load('apPhpEmailTemplatesBeforeEdit');

                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'email_templates');

                    $rlHook->load('apPhpEmailTemplatesAfterEdit');

                    foreach ($allLangs as $language) {
                        $condition = "`Key` = 'email_templates+subject+{$f_key}' AND `Code` = '{$language['Code']}'";
                        $value     = $_POST['name'][$language['Code']];

                        if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                            // edit subjects
                            $update_subject = array(
                                'fields' => array(
                                    'Value' => $value,
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'email_templates+subject+' . $f_key,
                                ),
                            );

                            if ($value != $rlDb->getOne('Value', $condition, 'lang_keys')) {
                                $update_subject['fields']['Modified'] = '1';
                            }

                            // update
                            $GLOBALS['rlActions']->updateOne($update_subject, 'lang_keys');
                        } else {
                            // insert subjects
                            $insert_subject = array(
                                'Value'  => $value,
                                'Code'   => $language['Code'],
                                'Key'    => 'email_templates+subject+' . $f_key,
                                'Module' => 'email_tpl',
                            );

                            // insert
                            $rlActions->insertOne($insert_subject, 'lang_keys');
                        }

                        $condition   = "`Key` = 'email_templates+body+{$f_key}' AND `Code` = '{$language['Code']}'";
                        $description = $_POST['description'][$language['Code']];

                        if ($_POST['type'] == 'html') {
                            $description = str_replace(array('<br />', '<br/>', '<br>'), '', $description);
                        }

                        if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                            // edit bodies
                            $update_body = array(
                                'fields' => array(
                                    'Value' => $description,
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'email_templates+body+' . $f_key,
                                ),
                            );

                            if ($description != $rlDb->getOne('Value', $condition, 'lang_keys')) {
                                $update_body['fields']['Modified'] = '1';
                            }

                            // update
                            $GLOBALS['rlActions']->updateOne($update_body, 'lang_keys');
                        } else {
                            // insert body
                            $insert_body = array(
                                'Value'  => $description,
                                'Code'   => $language['Code'],
                                'Key'    => 'email_templates+body+' . $f_key,
                                'Module' => 'email_tpl',
                            );

                            // insert
                            $rlActions->insertOne($insert_body, 'lang_keys');
                        }
                    }

                    $message = $lang['template_edited'];
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

    $rlHook->load('apPhpEmailTemplatesBottom');
}
