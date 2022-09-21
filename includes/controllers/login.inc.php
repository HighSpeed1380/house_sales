<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LOGIN.INC.PHP
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
use Flynax\Utils\Util;

if ($_COOKIE['agencyInviteConfirmationKey']) {
    $agencies = new Agencies();
    $invite = $agencies->setInviteKey($_COOKIE['agencyInviteConfirmationKey'])->getInviteInfo();
}

if (!defined('IS_LOGIN')) {
    // Clear saved referer if the referer changed
    if ($_SESSION['login_referer'] && $page_info['prev'] != $page_info['Key']) {
        unset($_SESSION['login_referer']);
    }

    // Save alert if user log in via save alert form
    if ($_POST['alert_type']) {
        $_SESSION['saveAlertType'] = $_POST['alert_type'];
    }

    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $remember_me = $_POST['remember_me'];

        if (true === $res = $rlAccount->login($username, $password, false, $remember_me)) {
            // Remove logout handler from URL for logged user
            if (false !== strpos($_SERVER['HTTP_REFERER'], '?logout')) {
                $_SESSION['remove_logout_handler'] = true;
            }

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['notice_logged_in']);

            $rlHook->load('loginSuccess');

            if ($account_info && $invite && $account_info['ID'] === $invite['Agent_ID']) {
                $agencies->acceptInvite()->removeInviteKey();
                $rlNotice->saveNotice($lang['agency_invite_accepted']);
            }

            if ($_SESSION['saveAlertType']) {
                $reefless->loadClass('Search');
                $result = $GLOBALS['rlSearch']->ajaxSaveSearch(
                    $_SESSION['saveAlertType'],
                    $account_info['ID'],
                    $_SESSION['post_form_key']
                );

                $rlNotice->saveNotice(
                    $result['message'],
                    $result['status'] === 'OK' ? 'notice' : 'error'
                );

                unset($_SESSION['saveAlertType']);

                Util::redirect($GLOBALS['reefless']->getPageUrl('saved_search'));
            } elseif ($_SESSION['login_referer']) {
                $referer = $_SESSION['login_referer'];
                unset($_SESSION['login_referer']);
                $reefless->redirect(null, $referer);
            } else {
                if ($page_info['prev'] && in_array($page_info['prev'], array('login', 'remind', 'registration'))) {
                    if ($account_info['Lang'] && $account_info['Lang'] != $config['lang']) {
                        $url = RL_URL_HOME . $account_info['Lang'] . "/";
                    } else {
                        $url = SEO_BASE;
                    }

                    $url .= $config['mod_rewrite'] ? $pages['login'] . '.html' : '?page=' . $pages['login'];

                    Util::redirect($url);
                } elseif ($account_info['Lang'] && $account_info['Lang'] != RL_LANG_CODE && $languages[$account_info['Lang']]) {
                    $reefless->referer(null, RL_LANG_CODE, $account_info['Lang']);
                } else {
                    $reefless->referer();
                }
            }
        } else {
            // save referer
            if (!$_SESSION['login_referer'] && is_numeric(strpos($_SERVER['HTTP_REFERER'], RL_URL_HOME))) {
                $_SESSION['login_referer'] = $_SERVER['HTTP_REFERER'];
            }

            // login page mode
            if ($page_info['prev'] == 'login') {
                if ($rlAccount->messageType == 'error') {
                    $rlSmarty->assign_by_ref('errors', $res);
                } else {
                    $rlSmarty->assign_by_ref('pAlert', $res[0]);
                }
            }
            // remote pages mode
            else {
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($res, 'error');

                $url = SEO_BASE;
                $url .= $config['mod_rewrite'] ? $pages['login'] . '.html' : '?page=' . $pages['login'];
                $reefless->redirect(null, $url);
            }
        }
    }
} else {
    if (isset($_GET['action']) && $_GET['action'] == 'logout') {
        $rlAccount->logOut();
    }
    $page_info['name'] = $lang['blocks+name+account_area'];
}
