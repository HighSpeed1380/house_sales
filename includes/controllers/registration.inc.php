<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: REGISTRATION.INC.PHP
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

use Flynax\Classes\Agencies;

$agencies = new Agencies();

$reg_steps['done'] = array(
    'name' => $lang['reg_done'],
    'path' => 'done',
);

$rlAccount->initRegistrationSteps($reg_steps);
$reefless->loadClass('MembershipPlan');

$rlHook->load('phpRegistrationTop'); // >= 4.5.0

$show_step_caption = false;
$rlSmarty->assign_by_ref('show_step_caption', $show_step_caption);

// detect step from GET
$request = explode('/', $_GET['rlVareables']);
$request_step = array_pop($request);
$get_step = $request_step ? $request_step : $_GET['step'];

$cur_step = $rlAccount->stepByPath($reg_steps, $get_step);
$rlSmarty->assign_by_ref('cur_step', $cur_step);

/* disallow registration if user alrady logged in */
if (defined('IS_LOGIN') && (!$cur_step || !$_SESSION['registration'])) {
    $reefless->redirect(false, $reefless->getPageUrl('login'));
}

// get account types list
$account_types = $rlAccount->getAccountTypes('visitor');
$rlSmarty->assign_by_ref('account_types', $account_types);

$agentRegistration = false;
if ($_COOKIE['agencyInviteConfirmationKey']) {
    $invite = $agencies->setInviteKey($_COOKIE['agencyInviteConfirmationKey'])->getInviteInfo();
    $rlSmarty->assign_by_ref('agentInvite', $invite);

    $account_types = array_filter($account_types, function($type) {
        return $type['Agent'] === '1';
    });
    $agentRegistration = true;

    $page_info['title'] = $lang['agent_registration_title'];
}
$rlSmarty->assign_by_ref('agentRegistration', $agentRegistration);

//  get prev/next step
reset($reg_steps);
$tmp_steps = $reg_steps;
$cur_step_key = $cur_step ? $cur_step : 'profile';

if (((isset($_SESSION['registration']['plan']) && $_SESSION['registration']['plan']['Price'] <= 0) || !isset($_SESSION['registration']['plan'])) && !$_POST['plan']) {
    unset($reg_steps['checkout']);
}
// skip account step if not selected account type
if (!$_POST && !isset($_SESSION['registration']['profile']['type'])) {
    unset($reg_steps['account']);
}
// skip plan step
if ($config['membership_module']) {
    if (is_array($_SESSION['registration']['profile']['type'])
        && isset($_SESSION['registration']['profile']['type']['Key'])
    ) {
        $_SESSION['registration']['no_plan_step'] = false;
        if ($single_plan_id = $rlMembershipPlan->isSinglePlan($account_types[$_SESSION['registration']['profile']['type']]['Key'])) {
            unset($reg_steps['plan']);
            $_SESSION['registration']['no_plan_step'] = true;
        }
    }
    // save step for current listing
    if ($_SESSION['registration']['account_id'] && !in_array($cur_step, array('account', 'profile', 'done'))) {
        $update_step = array(
            'fields' => array(
                'Last_step' => $cur_step,
            ),
            'where'  => array(
                'ID' => $_SESSION['registration']['account_id'],
            ),
        );
        $rlActions->updateOne($update_step, 'accounts');
    }
}
if (!$_POST && !$cur_step_key) {
    unset($_SESSION['ses_registration_data'], $_SESSION['registration_captcha_passed']);
}

foreach ($tmp_steps as $t_key => $t_step) {
    if (isset($reg_steps[$t_key])) {
        if ($t_key != $cur_step_key) {
            next($reg_steps);
        } else {
            break;
        }
    }
}

$next_step = next($reg_steps);
prev($reg_steps);
$prev_step = prev($reg_steps);

$rlSmarty->assign('next_step', $next_step);
$rlSmarty->assign('prev_step', $prev_step);

$show_step_caption = true;
$rlSmarty->assign_by_ref('show_step_caption', $show_step_caption);

$rlSmarty->assign_by_ref('reg_steps', $reg_steps);

if ($_SESSION['registr_account_type']) {
    $rlSmarty->assign_by_ref('registr_account_type', $_SESSION['registr_account_type']);
}

$rlHook->load('registrationBegin'); // >= v4.3

$reefless->loadClass('Categories');

// Get domain name and scheme
$domain = $domain_info['host'];
$domain = $config['account_wildcard'] ? ltrim($domain, 'www.') : $domain;
$scheme = $domain_info['scheme'];

$rlSmarty->assign_by_ref('scheme', $scheme);
$rlSmarty->assign_by_ref('domain', $domain);

if ($_SESSION['registration']['account_id']) {
    $account_id = (int) $_SESSION['registration']['account_id'];
    $account_tmp = $rlDb->getRow("SELECT * FROM `{db_prefix}accounts` WHERE `ID` = '{$account_id}' LIMIT 1");
}

// get a list with agreement fields
$rlSmarty->assign('agreement_fields', $rlAccount->getAgreementFields());

// steps handler
if (!$cur_step) {
    if (!isset($_GET['edit']) && !$_POST) {
        unset($_SESSION['registration_captcha_passed'], $_SESSION['registration']);
    }
    if (isset($_SESSION['registration']['profile']) && !$_POST['profile']) {
        $_POST['profile'] = $_SESSION['registration']['profile'];
    }
    if ($_POST['step']) {
        $profile_data             = $rlValid->xSql($_POST['profile']);
        $profile_data['username'] = trim($profile_data['username']);

        $username       = $profile_data['username'];
        $email          = $profile_data['mail'];
        $password       = $profile_data['password'];
        $type           = $profile_data['type'];
        $selected_atype = $account_types[$type];

        if (!$account_id) {
            // e-mail login mode
            if ($config['account_login_mode'] == 'email') {
                $exp_email = explode('@', $email);
                $username = $profile_data['username'] = $rlAccount->makeUsernameUnique($exp_email[0]);
            }
            // username login mode
            else {
                if (!$rlAccount->validateUsername($username)) {
                    $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['username'] . '"</span>', $lang['notice_field_not_valid']);
                    $error_fields = 'profile[username],';
                } else {
                    $rlValid->sql($username);
                    if ($rlDb->getOne('ID', "`Username` = '{$username}'", 'accounts')) {
                        $errors[] = str_replace('{username}', $username, $lang['notice_account_exist']);
                        $error_fields = 'profile[username]';
                    }
                }
            }

            // check email
            if (!$rlValid->isEmail($email)) {
                $errors[] = $lang['notice_bad_email'];
                $error_fields .= 'profile[mail],';
            }
            $rlValid->sql($email);

            if ($email) {
                $rlValid->sql($email);
                $exist = (bool) $rlActions->getOne('ID', "`Mail` = '{$email}'", 'accounts');
                $message = str_replace('{email}', $email, $lang['notice_account_email_exist']);

                $GLOBALS['rlHook']->load('phpAjaxValidateProfileEmail', $email, $message, $exist); // from v4.0.2

                if ($exist) {
                    $errors[] = $message;
                }
            }
        } else {
            $profile_data['username'] = $_SESSION['registration']['profile']['username'];
            $profile_data['mail'] = $_SESSION['registration']['profile']['mail'];
            if ($_SESSION['registration']['profile']['location']) {
                $profile_data['location'] = $_SESSION['registration']['profile']['location'];
            }
        }

        // check password
        if ($rlCommon->strLen($password, '<', 3)) {
            $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['password'] . '"</span>', $lang['notice_reg_length']);
            $error_fields .= 'profile[password],';
        }
        $rlValid->sql($password);

        // check password match
        if ($password != $profile_data['password_repeat']) {
            $errors[] = $lang['notice_pass_bad'];
            $error_fields = 'profile[password_repeat]';
        }

        // check type
        if (!$type) {
            $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['password'] . '"</span>', $lang['notice_choose_account_type']);
            $error_fields .= 'profile[type],';
        }
        $rlValid->sql($type);

        // check personal address
        if ($selected_atype['Own_location'] && (!$account_id || empty($_SESSION['registration']['profile']['location']))) {
            // validate
            $error = '';
            $rlAccount->validateUserLocation($profile_data['location'], $error, $errors_trigger, false);
            $GLOBALS['rlHook']->load('phpAjaxValidateProfileLocation', $profile_data['location'], $wildcard_deny, $errors_trigger);
            if ($error) {
                $errors[] = $error;
                $error_fields .= 'profile[location],';
            }
        }

        $GLOBALS['rlHook']->load('phpAjaxValidateProfile');

        // check security image code
        if ($config['security_img_registration'] && !$_SESSION['registration_captcha_passed']) {
            if ($_POST['security_code'] != $_SESSION['ses_security_code'] || empty($_SESSION['ses_security_code'])) {
                $errors[] = $lang['security_code_incorrect'];
                $error_fields .= 'security_code,';
            }
        }

        // check accepted agreement fields
        if ($selected_atype['Key']) {
            foreach ($rlAccount->getAgreementFields($selected_atype['Key'], true) as $ag_field_key => $ag_field) {
                if (!$profile_data['accept'][$ag_field_key]) {
                    $errors[] = str_replace(
                        '{field}',
                        $lang['pages+name+' . $ag_field['Default']],
                        $lang['notice_field_not_accepted']
                    );
                    $error_fields .= "profile[accept][{$ag_field_key}]";
                }
            }
        }

        if (!$errors) {
            $_SESSION['registration']['profile'] = $profile_data;

            $url = SEO_BASE;
            $url .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $next_step['path'] . '.html' : '?page=' . $page_info['Path'] . '&step=' . $next_step['path'];
            $reefless->redirect(null, $url);
        }
    }
} else {
    switch ($cur_step) {
        case 'account':
            $profile_data = $_SESSION['registration']['profile'];
            $type = $profile_data['type'];

            $fields = $rlAccount->getFields($type);
            $fields = $rlLang->replaceLangKeys($fields, 'account_fields', array('name', 'default', 'description'));
            $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');

            $rlSmarty->assign_by_ref('fields', $fields);

            // skip account details step
            if (count($fields) <= 0) {
                $_POST['step'] = 'accounts';
                $_SESSION['registration']['no_account_step'] = true;
            } elseif (isset($_SESSION['registration']['no_account_step'])) {
                $_SESSION['registration']['no_account_step'] = false;
            }

            if ($_POST['step']) {
                $account_data = $_POST['account'];
                foreach ($fields as $fk => $field) {
                    if (!isset($account_data[$field['Key']]) && $field['Add_page']) {
                        $account_data[$field['Key']] = "";
                    }
                }

                // check username
                $username = $profile_data['username'];

                if ($account_data) {
                    if ($back_errors = $rlCommon->checkDynamicForm($account_data, $fields, 'account')) {
                        foreach ($back_errors as $error) {
                            $errors[] = $error;
                            $rlSmarty->assign('fixed_message', true);
                        }

                        if ($rlCommon->error_fields) {
                            $error_fields = $rlCommon->error_fields;
                            $rlCommon->error_fields = false;
                        }
                    }
                }

                $rlHook->load('beforeRegister');

                if (!$errors) {
                    $reefless->loadClass('Actions');
                    $reefless->loadClass('Resize');
                    $reefless->loadClass('Mail');

                    $_SESSION['ses_registration_data'] = array('email' => $profile_data['mail']);

                    if (!$account_id) {
                        /* personal address handler */
                        $profile_data['location'] = trim($profile_data['location']);

                        if (!$profile_data['location']) {
                            $profile_data['location'] = $username;
                        }

                        if ($account_types[$profile_data['type']]['Own_location'] && !$profile_data['location']) {
                            /* load the utf8 lib */
                            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

                            if (!utf8_is_ascii($username)) {
                                $username = utf8_to_ascii($username);
                            }
                            $profile_data['location'] = $rlSmarty->str2path($username);
                        }

                        if ($result = $rlAccount->registration($type, $profile_data, $account_data, $fields)) {
                            $_SESSION['registration']['account_data'] = $account_data;

                            $rlHook->load('registerSuccess');

                            $_SESSION['registr_account_type'] = $profile_data['type'];

                            if ($account_types[$profile_data['type']]['Auto_login'] && !$account_types[$profile_data['type']]['Email_confirmation'] && !$account_types[$profile_data['type']]['Admin_confirmation']) {
                                $match_field = $config['account_login_mode'] == 'email' ? 'mail' : 'username';
                                $rlAccount->login($profile_data[$match_field], $profile_data['password']);
                            }
                        }
                    } else {
                        if ($result = $rlAccount->editAccount($account_data, $fields, $account_id)) {
                            $rlAccount->changeType($profile_data);
                            $_SESSION['registration']['account_data'] = $account_data;
                        }
                    }

                    if ($result) {
                        if ($_SESSION['registration']['no_plan_step']) {
                            $update = array(
                                'fields' => array(
                                    'Plan_ID' => $single_plan_id,
                                ),
                                'where'  => array(
                                    'ID' => (int) $_SESSION['registration']['account_id'],
                                ),
                            );

                            $rlActions->updateOne($update, 'accounts');
                            $_SESSION['registration']['plan'] = $rlMembershipPlan->getPlan($single_plan_id);

                            // skip plan checkout
                            if ($_SESSION['registration']['plan']['Price'] > 0) {
                                $rlMembershipPlan->addRegistrationStep($next_step, 'checkout');
                            }
                        }
                        $url = SEO_BASE;
                        $url .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $next_step['path'] . '.html' : '?page=' . $page_info['Path'] . '&step=' . $next_step['path'];
                        $reefless->redirect(null, $url);
                    }
                }
            } else {
                if (isset($_SESSION['registration']['account_data'])) {
                    $_POST['account'] = $_SESSION['registration']['account_data'];
                }
            }
            break;

        case 'plan':
            if ($_SESSION['registration']) {
                $plans = $rlMembershipPlan->getPlansByType($account_types[$_SESSION['registration']['profile']['type']]['Key']);
                $rlSmarty->assign_by_ref('plans', $plans);

                // check for available plans
                if (empty($plans)) {
                    $errors[] = $lang['notice_no_membership_plans_related'];
                    $rlSmarty->assign('no_access', true);
                }

                if ($_POST['step']) {
                    if (!$errors) {
                        $plan_id = (int) $_POST['plan'];

                        if (!$plan_id) {
                            $errors[] = $lang['notice_membership_plan_does_not_chose'];
                        }

                        if (!$errors) {
                            $update = array(
                                'fields' => array(
                                    'Plan_ID' => $plan_id,
                                ),
                                'where'  => array(
                                    'ID' => (int) $_SESSION['registration']['account_id'],
                                ),
                            );

                            $rlActions->updateOne($update, 'accounts');
                            $_SESSION['registration']['plan'] = $rlMembershipPlan->getPlan((int) $_POST['plan']);
                            // set subscription feature
                            if ($_POST['subscription']) {
                                $_SESSION['registration']['subscription'] = (int) $_POST['subscription'];
                            }

                            $rlHook->load('phpRegistrationStepPlan');

                            if ($_SESSION['registration']['plan']['Price'] <= 0 && $next_step['path'] == $reg_steps['checkout']['path']) {
                                $rlMembershipPlan->skipRegistrationStep($next_step, 'checkout');

                                $plan_info = $plans[$plan_id];

                                // Save free plan usage
                                $insert = array(
                                    'Account_ID'       => (int) $_SESSION['registration']['account_id'],
                                    'Plan_ID'          => $plan_id,
                                    'Listings_remains' => $plan_info['Listing_number'],
                                    'Standard_remains' => $plan_info['Standard_listings'],
                                    'Featured_remains' => $plan_info['Featured_listings'],
                                    'Type'             => 'account',
                                    'Date'             => 'NOW()',
                                    'IP'               => Flynax\Utils\Util::getClientIP(),
                                );

                                $rlDb->insert($insert, 'listing_packages');
                            }
                            $url = SEO_BASE;
                            $url .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $next_step['path'] . '.html' : '?page=' . $page_info['Path'] . '&step=' . $next_step['path'];
                            $reefless->redirect(null, $url);
                        }
                    }
                }
            } else {
                $sError = true;
            }
            break;

        case 'checkout':
            if (isset($_SESSION['registration']['plan'])) {
                $plan_info = $_SESSION['registration']['plan'];
            }
            if (!$rlPayment->isPrepare()) {
                // clear payment data
                $rlPayment->clear();

                // save payment details
                $cancel_url = SEO_BASE;
                $cancel_url .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $get_step . '.html?canceled' : '?page=' . $page_info['Path'] . '&step=' . $get_step . '&canceled';

                $success_url = SEO_BASE;
                $success_url .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $next_step['path'] . '.html' : '?page=' . $page_info['Path'] . '&step=' . $next_step['path'];

                // set payment options
                $rlPayment->setOption('service', 'membership');
                $rlPayment->setOption('total', $plan_info['Price']);
                $rlPayment->setOption('plan_id', $plan_info['ID']);
                $rlPayment->setOption('item_id', (int) $_SESSION['registration']['account_id']);
                $rlPayment->setOption('item_name', $GLOBALS['lang']['membership_plans+name+' . $plan_info['Key']]);
                $rlPayment->setOption('account_id', (int) $_SESSION['registration']['account_id']);
                $rlPayment->setOption('callback_class', 'rlAccount');
                $rlPayment->setOption('callback_method', 'upgrade');
                $rlPayment->setOption('cancel_url', $cancel_url);
                $rlPayment->setOption('success_url', $success_url);
                $rlPayment->setOption('params', 'new');

                // set recurring option
                if ($plan_info['Subscription'] && $_SESSION['registration']['subscription'] == $plan_info['ID']) {
                    $rlPayment->enableRecurring();
                }

                $rlHook->load('phpRegistrationStepCheckoutInit');

                $rlPayment->init($errors);
            } else {
                $rlPayment->checkout($errors, true);
            }
            break;

        case 'done':
            if ($config['membership_module']) {
                if (isset($_SESSION['registration']['plan']) && $_SESSION['registration']['plan']['Price'] <= 0) {
                    $rlAccount->upgrade($_SESSION['registration']['account_id'], $_SESSION['registration']['plan']['ID'], false, true);
                }
            }
            // send notification to user and admin
            if ($account_id && isset($_SESSION['registration'])) {
                $rlAccount->sendRegistrationNotification($account_tmp);
            }

            $account_type = $account_types[$_SESSION['registration']['profile']['type']];

            if (!$account_type['Email_confirmation'] && !$account_type['Admin_confirmation']) {
                $rlCache->updateStatistics();
            }

            $rlHook->load('registrationDone');
            unset($_SESSION['registration_captcha_passed'], $_SESSION['registration']);
            break;
    }

    $rlHook->load('phpRegistrationStep'); // >= 4.5.0
}

if ($reg_steps) {
    foreach ($reg_steps as $key => &$step) {
        $step['key'] = $key;
    }
}

$rlHook->load('phpRegistrationBottom'); // >= 4.5.0

// register ajax methods
$rlXajax->registerFunction(array('userExist', $rlAccount, 'ajaxUserExist'));
$rlXajax->registerFunction(array('emailExist', $rlAccount, 'ajaxEmailExist'));
$rlXajax->registerFunction(array('checkLocation', $rlAccount, 'ajaxCheckLocation'));
$rlXajax->registerFunction(array('validateProfile', $rlAccount, 'ajaxValidateProfile'));
$rlXajax->registerFunction(array('checkTypeFields', $rlAccount, 'ajaxCheckTypeFields'));
