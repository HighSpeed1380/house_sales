<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CONTROL.INC.PHP
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

// Include PSR-4 autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once RL_CLASSES . 'rlDb.class.php';
require_once RL_CLASSES . 'reefless.class.php';

$rlDb = new rlDb();
$reefless = new reefless();

if (!defined('CRON_FILE')) {
    $reefless->sessionStart();
}

/* load classes */
$reefless->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
$reefless->loadClass('Debug');
$reefless->loadClass('Config');
$reefless->loadClass('Lang');
$reefless->loadClass('Valid');
$reefless->loadClass('Hook');
$reefless->loadClass('Listings');
$reefless->loadClass('Categories');
$reefless->loadClass('Cache');

if (!defined('CRON_FILE') && !defined('AJAX_FILE')) {
    /* load ajax library */
    require_once RL_LIBS . 'ajax' . RL_DS . 'xajax_core' . RL_DS . 'xajax.inc.php';

    $rlXajax = new xajax();
    $_response = new xajaxResponse();
    $GLOBALS['_response'] = $_response;

    $rlXajax->configure('javascript URI', RL_URL_HOME . 'libs/ajax/');
    $rlXajax->configure('debug', RL_AJAX_DEBUG);

    $rlXajax->setCharEncoding('UTF-8');
    /* ajax library end */
}

// define template core support
define('TPL_CORE', is_dir(RL_ROOT . 'templates' . RL_DS . 'template_core'));

// load system configurations
$config = $rlConfig->allConfig();

// load classes which require configs
$reefless->loadClass('Static');

/* load smarty library */
require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
$reefless->loadClass('Smarty');

/**
 * Load active plugins
 * @since 4.5.1
 **/
$plugins = $rlCommon->getInstalledPluginsList();
$GLOBALS['plugins'] = &$plugins;
$rlSmarty->assign('plugins', $plugins);

/* assign configs to template */
$rlSmarty->assign_by_ref('config', $config);
$rlSmarty->assign_by_ref('domain_info', $domain_info);

// Validate POST data
$reefless->validatePOST();

/* utf8 library functions */
function loadUTF8functions()
{
    $names = func_get_args();

    if (empty($names)) {
        return false;
    }

    foreach ($names as $name) {
        if (file_exists(RL_LIBS . 'utf8' . RL_DS . 'utils' . RL_DS . $name . '.php')) {
            require_once RL_LIBS . 'utf8' . RL_DS . 'utils' . RL_DS . $name . '.php';
        }
    }
}

if (!function_exists('hash_equals')) {
    function hash_equals($str1, $str2)
    {
        if (strlen($str1) != strlen($str2)) {
            return false;
        } else {
            $res = $str1 ^ $str2;
            $ret = 0;
            for ($i = strlen($res) - 1; $i >= 0; $i--) {
                $ret |= ord($res[$i]);
            }

            return !$ret;
        }
    }
}
