<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PROFILE.INC.PHP
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

$reefless->loadClass('Mail');
$reefless->loadClass('Notice');
$reefless->loadClass('Account');
$reefless->loadClass('Actions');
$reefless->loadClass('MembershipPlan');
$reefless->loadClass('Subscription');

// Register CSS
$rlStatic->addHeaderCss(RL_TPL_BASE . 'controllers/profile/profile.css', $page_info['Controller']);

// redirect to register account process
if (isset($_GET['incomplete'])) {
    $id = (int) $_GET['incomplete'];
    $step = $_GET['step'];

    $reg_steps['done'] = array(
        'name' => $lang['reg_done'],
        'path' => 'done',
    );
    $rlAccount->initRegistrationSteps($reg_steps);

    $account = $rlDb->fetch('*', array('ID' => $id), null, 1, 'accounts', 'row');
    $account_type = $rlAccount->getAccountType($account['Type']);
    $_SESSION['registration']['plan'] = $_SESSION['registration']['plan'] = $rlMembershipPlan->getPlan($account['Plan_ID']);
    $_SESSION['registration']['account_id'] = $id;
    $_SESSION['registration']['profile'] = array(
        'username' => $account['Username'],
        'mail'     => $account['Mail'],
        'type'     => $account_type['ID'],
    );

    $reefless->redirect(null, $reefless->getPageUrl('registration', array('step' => $reg_steps[$step]['path'])));
    exit;
}

/* register ajax methods */
$rlXajax->registerFunction(array('changePass', $rlAccount, 'ajaxChangePass'));

if (defined('IS_LOGIN')) {
    if ($_REQUEST['info'] == 'membership') {
        if (isset($_GET['completed'])) {
            $rlNotice->saveNotice($lang['profile_upgrade_success']);
        }
        if (isset($_GET['canceled'])) {
            $errors[] = $lang['profile_upgrade_fail'];
        }
    }

    $step = $rlValid->xSql($_GET['rlVareables']);
    $step = $step ?: $_GET['step'];
    $rlSmarty->assign_by_ref('step', $step);

    if ($config['membership_module']) {
        $membership_plan = $rlMembershipPlan->getPlanByProfile($account_info);
        $rlSmarty->assign_by_ref('membership_plan', $membership_plan);

        // get total membership plans
        if ($step != 'purchase') {
            $sql = "SELECT * FROM `{db_prefix}membership_plans` ";
            $sql .= "WHERE `Status` = 'active' ";
            $sql .= "AND (FIND_IN_SET('{$account_info['Type']}', `Allow_for`) > 0 OR `Allow_for` = '')";
            $ms_plans = $rlDb->getAll($sql);

            if ($ms_plans) {
                foreach ($ms_plans as $key => $value) {
                    $where = [
                        'Account_ID' => $account_info['ID'],
                        'Plan_ID' => $value['ID'],
                        'Type' => 'account',
                    ];
                    $plan_using = $rlDb->fetch(['Count_used'], $where, null, null, 'listing_packages', 'row');

                    if (($value['Limit'] > 0 && $value['Limit'] <= $plan_using['Count_used'])
                        || $value['ID'] == $account_info['Plan_ID']
                    ) {
                        unset($ms_plans[$key]);
                    }
                }
            }

            $rlSmarty->assign('ms_plans_total', count($ms_plans));
        }
    }

    if ($step == 'renew') {
        $rlHook->load('phpProfileMembershipValidate');
        // check limited plans using
        if ($rlMembershipPlan->isLimitExceeded($membership_plan, $account_info['ID'])) {
            $errors[] = $lang['plan_limit_using_hack'];
        }
        if (!$errors) {
            $cancel_url = SEO_BASE;
            $cancel_url .= $config['mod_rewrite']
            ? $page_info['Path'] . '.html?info=membership&canceled'
            : '?page=' . $page_info['Path'] . '&canceled&info=membership&';

            $success_url = SEO_BASE;
            $success_url .= $config['mod_rewrite']
            ? $page_info['Path'] . '.html?info=membership&completed'
            : '?page=' . $page_info['Path'] . '&completed&info=membership&';

            if ($membership_plan['Price'] <= 0) {
                $rlAccount->upgrade($account_info['ID'], $membership_plan['ID']);
                $reefless->redirect(false, $success_url);
            }
            if (!$rlPayment->isPrepare() || $membership_plan['ID'] != $rlPayment->getOption('plan_id')) {
                $rlPayment->clear();

                // set payment options
                $rlPayment->setOption('service', 'membership');
                $rlPayment->setOption('total', $membership_plan['Price']);
                $rlPayment->setOption('plan_id', $membership_plan['ID']);
                $rlPayment->setOption('item_id', $account_info['ID']);
                $rlPayment->setOption('item_name', $lang['membership_plans+name+' . $membership_plan['Key']]);
                $rlPayment->setOption('plan_key', 'membership_plans+name+' . $membership_plan['Key']);
                $rlPayment->setOption('account_id', $account_info['ID']);
                $rlPayment->setOption('callback_class', 'rlAccount');
                $rlPayment->setOption('callback_method', 'upgrade');
                $rlPayment->setOption('cancel_url', $cancel_url);
                $rlPayment->setOption('success_url', $success_url);

                // set recurring option
                if ($membership_plan['Subscription'] && $_POST['subscription'] == $membership_plan['ID']) {
                    $rlPayment->enableRecurring();
                }

                $rlPayment->init($errors);
            } else {
                $rlPayment->checkout($errors, true);
            }
        }
    } elseif ($step == 'purchase') {
        /* add bread crumbs item */
        $bread_crumbs[] = array(
            'name' => $lang['purchase_membership_plan'],
        );

        $page_info['name'] = $lang['select_plan'];

        $account_types = $rlAccount->getAccountTypes('visitor');
        $rlSmarty->assign_by_ref('account_types', $account_types);

        if ($_POST['form']) {
            $plan_id = (int) $_POST['plan'];

            if (!$plan_id) {
                $errors[] = $lang['notice_listing_plan_does_not_chose'];
            }

            if (!$errors) {
                $cancel_url = SEO_BASE;
                $cancel_url .= $config['mod_rewrite']
                ? $page_info['Path'] . '.html?info=membership&canceled'
                : '?page=' . $page_info['Path'] . '&canceled&info=membership';

                $success_url = SEO_BASE;
                $success_url .= $config['mod_rewrite']
                ? $page_info['Path'] . '.html?info=membership&completed'
                : '?page=' . $page_info['Path'] . '&completed&info=membership';

                $plan_info = $rlMembershipPlan->getPlan($plan_id);

                // check limited plans using
                if ($rlMembershipPlan->isLimitExceeded($plan_info, $account_info['ID'])) {
                    $errors[] = $lang['plan_limit_using_hack'];
                }

                if ($plan_info['Price'] <= 0) {
                    $rlAccount->upgrade($account_info['ID'], $plan_info['ID']);
                    $reefless->redirect(false, $success_url);
                    exit;
                }

                if (!$rlPayment->isPrepare() || $rlPayment->getOption('plan_id') != $plan_info['ID']) {
                    $rlPayment->clear();
                    $rlPayment->setRedirect();

                    // set payment options
                    $rlPayment->setOption('service', 'membership');
                    $rlPayment->setOption('total', $plan_info['Price']);
                    $rlPayment->setOption('plan_id', $plan_info['ID']);
                    $rlPayment->setOption('item_id', $account_info['ID']);
                    $rlPayment->setOption('item_name', $plan_info['name']);
                    $rlPayment->setOption('plan_key', 'membership_plans+name+' . $plan_info['Key']);
                    $rlPayment->setOption('account_id', $account_info['ID']);
                    $rlPayment->setOption('callback_class', 'rlAccount');
                    $rlPayment->setOption('callback_method', 'upgrade');
                    $rlPayment->setOption('cancel_url', $cancel_url);
                    $rlPayment->setOption('success_url', $success_url);

                    // set recurring option
                    if ($membership_plan['Subscription'] && $_POST['subscription'] == $plan_info['ID']) {
                        $rlPayment->enableRecurring();
                    }

                    // set bread crumbs
                    $rlPayment->setBreadCrumbs(array(
                        'name'  => $lang['pages+name+my_profile'],
                        'title' => $lang['pages+name+my_profile'],
                        'path'  => $pages['my_profile'],
                    ));

                    $rlPayment->init($errors);
                } else {
                    $rlPayment->checkout($errors, true);
                }
            }
        }

        // get membership plans
        $plans = $rlMembershipPlan->getPlansByType($account_types[$account_info['Type_ID']]['Key']);
        $rlSmarty->assign_by_ref('plans', $plans);

        // remove my profile box if we have more than 3 plans
        if (count($plans) > 3) {
            unset($blocks['my_profile_sidebar']);
            $rlCommon->defineBlocksExist($blocks);
        }
    }

    /* confirm e-mail change request */
    if ($_GET['key']) {
        $confirm_key = $_GET['key'];
        $confirm = $rlDb->getOne('ID', "`Confirm_code` = '{$confirm_key}' AND `Mail_tmp` <> '' AND `ID` = '{$account_info['ID']}'", 'accounts');

        if ($confirm) {
            $update_sql = "UPDATE `{db_prefix}accounts` SET `Mail` = `Mail_tmp`, `Mail_tmp` = '', `Confirm_code` = '' WHERE `ID` = '{$account_info['ID']}' LIMIT 1";
            $rlDb->query($update_sql);

            $account_info['Mail'] = $rlDb->getOne('Mail', "`ID` = '{$account_info['ID']}'", 'accounts');
            $_SESSION['account']['Mail'] = $account_info['Mail'];

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['account_edit_email_confirmed']);
            $reefless->redirect(null, $reefless->getPageUrl('my_profile'));
        } else {
            $errors[] = $lang['account_edit_email_confirmation_fail'];
        }
    }

    /* populate tabs */
    $tabs = array(
        'profile'  => array(
            'key'  => 'profile',
            'name' => $lang['profile_information'],
        ),
        'account'  => array(
            'key'  => 'account',
            'name' => $lang['account_information'],
        ),
        'password' => array(
            'key'  => 'password',
            'name' => $lang['manage_password'],
        ),
    );

    if ($_REQUEST['info'] == 'account') {
        $tabs['account']['active'] = true;
    } else {
        $tabs['profile']['active'] = true;
    }
    if ($config['membership_module']) {
        $tabs['membership'] = array(
            'key'  => 'membership',
            'name' => $lang['account_membership'],
        );
    }

    $rlSmarty->assign_by_ref('tabs', $tabs);

    // Get domain name and scheme
    $domain = $domain_info['host'];
    $scheme = $domain_info['scheme'];

    $rlSmarty->assign_by_ref('scheme', $scheme);
    $rlSmarty->assign_by_ref('domain', $domain);

    /* get account inforamtion */
    $profile_info = $rlAccount->getProfile((int) $account_info['ID'], true);
    $rlSmarty->assign_by_ref('profile_info', $profile_info);

    // get a list with agreement fields
    $rlSmarty->assign('agreement_fields', $rlAccount->getAgreementFields());

    if (empty($profile_info['Fields'])) {
        unset($tabs['account']);
    }

    /* simulate post data */
    if (!$_POST['fromPost_profile']) {
        $_POST['profile']['mail'] = $profile_info['Mail'];
        $_POST['profile']['display_email'] = $profile_info['Display_email'];
        $_POST['profile']['location'] = $profile_info['Own_address'];

        foreach ($rlAccount->getAgreementFields() as $ag_field) {
            $_POST['profile']['accept'][$ag_field['Key']] = 1;
        }
    }
    if (!$_POST['fromPost_account']) {
        foreach ($profile_info['Fields'] as $key => $value) {
            switch ($value['Type']) {
                case 'phone':
                    $_POST['account'][$value['Key']] = $value['value'];

                    break;

                case 'checkbox':
                    $_POST['account'][$value['Key']] = explode(',', $profile_info[$value['Key']]);

                    break;
                case 'date':
                    if ($value['Default'] == 'multi') {
                        $_POST['account'][$value['Key']]['from'] = $profile_info[$value['Key']];
                        $_POST['account'][$value['Key']]['to'] = $profile_info[$value['Key'] . "_multi"];
                        break;
                    }
                case 'text':
                case 'textarea':
                    if ($value['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $_POST['account'][$value['Key']] = $reefless->parseMultilingual($profile_info[$value['Key']]);
                    } else {
                        $_POST['account'][$value['Key']] = $profile_info[$value['Key']];
                    }

                    break;
                case 'mixed':
                    $df_item = false;
                    $df_item = explode('|', $profile_info[$value['Key']]);

                    $_POST['account'][$value['Key']]['value'] = $df_item[0];
                    $_POST['account'][$value['Key']]['df'] = $df_item[1];
                    break;
                default:
                    $_POST['account'][$value['Key']] = $profile_info[$value['Key']];

                    break;
            }
        }
    } else {
        // emulate existing data if user get a error about not filled data
        foreach ($profile_info['Fields'] as $key => $value) {
            switch ($value['Type']) {
                case 'image':
                    $_POST['account_sys_exist'][$value['Key']] = $profile_info[$value['Key']];
                    break;
            }
        }
    }

    $reefless->loadClass('Categories');

    $rlHook->load('profileController');

    /* edit profile */
    if ($_POST['info'] == 'profile') {
        /* check profiles form fields */
        $profile_data = $_POST['profile'];

        // check e-mail
        $e_mail_error = false;

        if (!$rlValid->isEmail($profile_data['mail'])) {
            $errors[] = $lang['notice_bad_email'];
            $error_fields .= 'profile[mail]';
            $e_mail_error = true;
        }

        // check dublicate e-mail
        $email_exist = $rlDb->getOne('Mail', "`Mail` = '{$profile_data['mail']}' AND `ID` <> '{$account_info['ID']}'", 'accounts');

        if ($email_exist) {
            $errors[] = str_replace('{email}', '<span class="field_error">"' . $profile_data['mail'] . '"</span>', $lang['notice_account_email_exist']);
            $error_fields .= 'profile[mail]';
            $e_mail_error = true;
        }

        // edit email handler
        if ($config['account_edit_email_confirmation'] && $account_info['Mail'] != $profile_data['mail'] && !$e_mail_error) {
            // save new e-mail as temporary
            $rlDb->query("UPDATE `{db_prefix}accounts` SET `Mail_tmp` = '{$profile_data['mail']}' WHERE `ID` = '{$account_info['ID']}' LIMIT 1");
            $rlAccount->sendEditEmailNotification($account_info['ID'], $profile_data['mail']);

            $profile_data['mail'] = $profile_info['Mail'];
        }

        // validate personal address
        if ($account_info['Own_location']) {
            $location = trim($profile_data['location']);
            $wildcard_deny = explode(',', $config['account_wildcard_deny']);
            $rlDb->setTable('pages');
            $deny_pages_tmp = $rlDb->fetch(array('Path'), null, "WHERE `Path` <> ''");
            foreach ($deny_pages_tmp as $deny_page) {
                $wildcard_deny[] = $deny_page['Path'];
            }
            unset($deny_pages_tmp);

            $wildcard_deny[] = RL_ADMIN;

            preg_match('/[\W]+/', $location, $matches);

            if (empty($location) || !empty($matches)) {
                $errors[] = $lang['personal_address_error'];
                $error_fields .= 'profile[location]';
            } else if (strlen($location) < 3) {
                $errors[] = $lang['personal_address_length_error'];
                $error_fields .= 'profile[location]';
            }
            /* check for uniqueness */
            else if (in_array($location, $wildcard_deny)
                || $rlDb->getOne('ID', "`Own_address` = '{$location}' AND `ID` != {$account_info['ID']}", 'accounts')) {
                $errors[] = $lang['personal_address_in_use'];
                $error_fields .= 'profile[location]';
            }
        }

        $rlHook->load('profileEditProfileValidate');

        if (empty($errors)) {
            if ($rlAccount->editProfile($profile_data)) {
                $rlHook->load('profileEditProfileDone');

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['notice_profile_edited']);
                $reefless->refresh();
            }
        }
    }

    if ($_POST['info'] == 'account') {
        $account_data = $_POST['account'];

        /* check account form fields */
        if ($account_data) {
            if ($back_errors = $rlCommon->checkDynamicForm($account_data, $profile_info['Fields'], 'account')) {
                foreach ($back_errors as $error) {
                    $errors[] = $error;
                    $rlSmarty->assign('fixed_message', true);
                }

                if ($rlCommon->error_fields) {
                    $error_fields = $rlCommon->error_fields;
                    $rlCommon->error_fields = false;
                }
            } else {
                if ($rlAccount->editAccount($account_data, $profile_info['Fields'])) {
                    $rlHook->load('profileEditAccountValidate');

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($lang['notice_account_edited']);
                    $aUrl = array('info' => 'account');
                    $reefless->redirect($aUrl);
                }
            }
        }
    }
}
