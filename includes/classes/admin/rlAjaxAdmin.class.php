<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLAJAXADMIN.CLASS.PHP
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

class rlAjaxAdmin extends reefless
{
    /**
     * class constructor
     **/
    public function __construct()
    {
        require_once RL_CLASSES . "rlSecurity.class.php";
    }

    /**
     * check admin panel logining
     *
     * @package ajax
     *
     * @param mixed $user - admin username
     * @param MD5 $pass - admin user password in HEX format
     * @param varchar $lang - language inerface
     *
     **/
    public function ajaxLogIn($user = null, $pass = null, $language = null)
    {
        global $_response, $config, $lang, $rlActions, $reefless;

        /* login attempts control - error and exit */
        if ($reefless->attemptsLeft <= 0 && $config['security_login_attempt_admin_module']) {
            $msg = str_replace('{period}', '<b>' . $config['security_login_attempt_admin_period'] . '</b>', $lang['login_attempt_error']);
            $_response->script("
                $('#logo').next().fadeOut('normal', function(){
                    $(this).remove();
                    var msg = '<div class=\"error hide\"><div class=\"inner\"><div class=\"icon\"></div>{$msg}</div></div>';
                    $('#logo').after(msg).next().fadeIn();
                });
            ");

            return $_response;
            exit;
        }

        $_response->setCharacterEncoding('UTF-8');

        $GLOBALS['rlValid']->sql($user);
        $user_info = $this->fetch('*', array('User' => $user, 'Status' => 'active'), null, null, 'admins', 'row');

        if (FLSecurity::verifyPassword($pass, $user_info['Pass'])) {
            if ($new_hash = FLSecurity::rehashIfNecessary($user_info['Pass'], $pass)) {
                $sql = "UPDATE `{db_prefix}admins` SET `Pass` = '{$new_hash}' WHERE `ID` = '{$user_info['ID']}' LIMIT 1";
                if ($this->query($sql)) {
                    $user_info['Pass'] = $new_hash;
                }
            }
        } else {
            unset($user_info);
        }

        /* login attempts control - save attempts */
        if ($config['security_login_attempt_admin_module']) {
            $insert = array(
                'IP'        => $this->getClientIpAddress(),
                'Date'      => 'NOW()',
                'Status'    => !empty($user_info) ? 'success' : 'fail',
                'Interface' => 'admin',
                'Username'  => $user,
            );

            $rlActions->insertOne($insert, 'login_attempts');
            $reefless->loginAttempt(true);
        }

        if (!empty($user_info)) {
            $GLOBALS['rlAdmin']->LogIn($user_info);

            $query_string = $_SESSION['query_string'] ? '?' . $_SESSION['query_string'] : '';
            $pos = strpos($_SESSION['query_string'], 'session_expired');

            if ($pos !== false) {
                $query_string = '?' . substr($_SESSION['query_string'], 0, $pos);
            }

            $query_string = $query_string ? $query_string . '&language=' . $language : '?language=' . $language;
            $_response->redirect(RL_URL_HOME . ADMIN . '/index.php' . $query_string);
        } else {
            //set message
            $message = $lang['rl_logging_error'];

            /* login attempts control - show warning */
            if ($config['security_login_attempt_admin_module']) {
                if ($reefless->attempts > 0 && $reefless->attemptsLeft > 0) {
                    $message .= '<br />' . $reefless->attemptsMessage;
                } elseif ($reefless->attemptsLeft <= 0) {
                    $msg = str_replace('{period}', '<b>' . $config['security_login_attempt_admin_period'] . '</b>', $lang['login_attempt_error']);
                    $_response->script("
                        $('#logo').next().fadeOut('normal', function(){
                            $(this).remove();
                            var msg = '<div class=\"error hide\"><div class=\"inner\"><div class=\"icon\"></div>{$msg}</div></div>';
                            $('#logo').after(msg).next().fadeIn();
                        });
                    ");
                    return $_response;
                }
            }
            $_response->script("fail_alert('#login_notify', '{$message}')");

            //hide loading
            $_response->script("$('#login_button').val('{$lang['login']}')");
        }

        return $_response;
    }

    /**
     * administrator log out
     *
     * @package ajax
     *
     **/
    public function ajaxLogOut($user = null, $pass = null, $lang = null)
    {
        global $_response;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlAdmin']->LogOut();
        $_response->redirect(RL_URL_HOME . ADMIN . '/');

        return $_response;
    }
}
