<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ADMINS.INC.PHP
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

        $rlHook->load('apExtAdminsUpdate');

        $rlActions->updateOne($updateData, 'admins');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $rlHook->load('apExtAdminsSql');

    $rlDb->setTable('admins');
    $data = $rlDb->fetch('*', null, "WHERE `Status` <> 'trash' ORDER BY `User`", array($start, $limit));
    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        unset($data[$key]['Pass']);
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
    }

    $rlHook->load('apExtAdminsData');

    $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}admins` WHERE `Status` <> 'trash'");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpAdminsTop');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_admin'] : $lang['edit_admin'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $admin_id = $rlValid->xSql($_GET['admin']);

            // get current admin info
            $admin_info = $rlDb->fetch('*', array('ID' => $admin_id), "AND `Status` <> 'trash'", null, 'admins', 'row');

            $_POST['login'] = $admin_info['User'];
            $_POST['name'] = $admin_info['Name'];
            $_POST['email'] = $admin_info['Email'];
            $_POST['status'] = $admin_info['Status'];
            $_POST['type'] = $admin_info['Type'];
            $_POST['rights'] = unserialize($admin_info['Rights']);

            $rlHook->load('apPhpAdminsPost');
        }

        if (isset($_POST['submit'])) {
            require_once RL_CLASSES . "rlSecurity.class.php";

            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_login = $_POST['login'];

            $f_pass = $_POST['password'];
            $f_pass_repeat = $_POST['password_repeat'];

            /* check login exist (in add mode only) */
            if ($_GET['action'] == 'add') {
                /* check name */
                if (!utf8_is_ascii($f_login)) {
                    $errors[] = $lang['incorrect_username'];
                }

                if (empty($f_login)) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['username'] . '</b>', $lang['notice_field_empty']);
                }

                /* check password */
                if (empty($f_pass)) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['password'] . '</b>', $lang['notice_field_empty']);
                }

                if (empty($f_pass_repeat)) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['password_repeat'] . '</b>', $lang['notice_field_empty']);
                }

                $exist_login = $rlDb->fetch(
                    array('User', 'Status'),
                    array('User' => $f_login),
                    null,
                    null,
                    'admins',
                    'row'
                );

                if (!empty($exist_login) && !empty($f_login)) {
                    $exist_error = str_replace('{username}', "<b>\"{$f_login}\"</b>", $lang['notice_admin_exist']);

                    if ($exist_login['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                }
            }

            if ($_GET['action'] == 'add') {
                if ($f_pass != $f_pass_repeat) {
                    $errors[] = $lang['notice_pass_bad'];
                }
            } elseif ($_GET['action'] == 'edit' && !empty($f_pass)) {
                if ($f_pass != $f_pass_repeat) {
                    $errors[] = $lang['notice_pass_bad'];
                }
            }

            /* check e-mail */
            $f_email = $_POST['email'];
            if (!empty($f_email) && !$rlValid->isEmail($f_email)) {
                $errors[] = str_replace('{field}', '<b>"' . $lang['mail'] . '"</b>', $lang['notice_field_incorrect']);
            }

            $where = "`Email` = '{$f_email}'";
            if ($_GET['action'] === 'edit') {
                $where .= " AND `User` <> '{$f_login}'";
            }

            // Check duplicate e-mail
            if (!empty($f_email) && $existingStatus = (string) $rlDb->getOne('Status', $where, 'admins')) {
                $exist_error = str_replace('{email}', "<b>{$f_email}</b>", $lang['notice_account_email_exist']);

                if ($existingStatus == 'trash') {
                    $exist_error .= " <b>({$lang['in_trash']})</b>";
                }

                $errors[] = $exist_error;
                $error_fields[] = 'email';
            }

            $rlHook->load('apPhpAdminsValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // write main section information
                    $data = array(
                        'User'   => $f_login,
                        'Pass'   => FLSecurity::cryptPassword($f_pass),
                        'Name'   => $_POST['name'],
                        'Email'  => $f_email,
                        'Status' => $_POST['status'],
                        'Type'   => $_POST['type'],
                        'Rights' => serialize($_POST['rights']),
                    );

                    $rlHook->load('apPhpAdminsBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'admins')) {
                        $rlHook->load('apPhpAdminsAfterAdd');

                        $message = $lang['admin_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new administrator (MYSQL problems)", E_USER_WARNING);
                        $rlDebug->logger("Can't add new administrator (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'User'   => $f_login,
                            'Name'   => $_POST['name'],
                            'Email'  => $f_email,
                            'Status' => $_POST['status'],
                            'Type'   => $_POST['type'],
                            'Rights' => serialize($_POST['rights']),
                        ),
                        'where'  => array('ID' => $_GET['admin']),
                    );

                    if ($_SESSION['sessAdmin']['user_id'] == $_GET['admin']) {
                        $_SESSION['sessAdmin']['rights'] = $_POST['rights'];
                        $_SESSION['sessAdmin']['type'] = $_POST['type'];
                    }

                    if (!empty($f_pass)) {
                        $update_date['fields']['Pass'] = FLSecurity::cryptPassword($f_pass);
                    }

                    $rlHook->load('apPhpAdminsBeforeEdit');

                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'admins');

                    $rlHook->load('apPhpAdminsAfterEdit');

                    $message = $lang['admin_edited'];
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

    $reefless->loadClass('Admin', 'admin');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteAdmin', $rlAdmin, 'ajaxDeleteAdmin'));
}
