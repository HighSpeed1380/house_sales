<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: INDEX.PHP
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

use Flynax\Utils\Valid;

/* system config */
require_once '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.inc.php';

/* system controller */
require_once RL_ADMIN_CONTROL . 'admin.control.inc.php';

/* print system information */
if (isset($_GET['system_info']) && $rlAdmin->isLogin()) {
    phpinfo();
    exit;
}

$reefless->baseUrlRedirect(true);

$rlHook->load('apBoot');

/* system configurations load */
$config = $rlConfig->allConfig();
$rlSmarty->assign_by_ref('config', $config);

/* load cache handler */
$reefless->loadClass('Cache');

/* load template settings */
$st_path = RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';
if (is_readable($st_path)) {
    require_once $st_path;
}

// Define controller
$controller = $_GET['controller'];

if (empty($controller)) {
    $controller = 'home';
}

// Define site languages
$rlDb->setTable('languages');
$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($_GET['language']);
$rlLang->modifyLanguagesList($languages);

// Get phrases and js phrase keys
$js_keys = [];
$lang = $rlLang->getAdminPhrases(RL_LANG_CODE, 'active', $controller, $js_keys);
$rlSmarty->assign_by_ref('js_keys', $js_keys);
$rlSmarty->assign_by_ref('lang', $lang);

// Get controller data
$cInfo = $rlAdmin->getController($controller);

/* load system lib */
require_once RL_LIBS . 'system.lib.php';

/* set timezone */
$reefless->setTimeZone();

/* get all pages keys/paths */
$pages = $GLOBALS['pages'] = $rlAdmin->getAllPages();
$rlSmarty->assign_by_ref('pages', $pages);

/* assign base path */
$rlSmarty->assign('rlBase', RL_URL_HOME . ADMIN . '/');
$rlSmarty->assign('rlBaseC', RL_URL_HOME . ADMIN . '/index.php?controller=' . $_GET['controller'] . '&amp;');
$rlSmarty->assign('rlTplBase', RL_URL_HOME . ADMIN . '/');
define('RL_TPL_BASE', RL_URL_HOME . ADMIN . '/');

// add custom box side
if ($tpl_settings['long_top_block']) {
    $l_block_sides['long_top'] = $lang['long_top'];
}

/* check admin user authorization */
if (!$rlAdmin->isLogin()) {
    /* login attempts control */
    $reefless->loginAttempt(true);

    // select all languages
    $rlSmarty->assign_by_ref('languages', $languages);
    $rlSmarty->assign('langCount', count($languages));

    /* ajax process request / get javascripts */
    $rlXajax->processRequest();

    $ajax_javascripts = $rlXajax->getJavascript();

    /* assign ajax javascripts */
    $rlSmarty->assign_by_ref('ajaxJavascripts', $ajax_javascripts);

    $rlSmarty->display('login.tpl');
    $_SESSION['query_string'] = $_SERVER['QUERY_STRING'];

    $rlHook->load('apNotLogin');

    // close the connection with a database
    $rlDb->connectionClose(true);

    exit;
}

/* load listing types */
$reefless->loadClass('ListingTypes');

if (!$_REQUEST['xjxfun']) {
    /* load the main menu */
    $mMenuItems = $rlAdmin->getMainMenuItems();
    $rlSmarty->assign_by_ref('mMenuItems', $mMenuItems);

    $mMenu_controllers = $rlAdmin->mMenu_controllers;
    $rlSmarty->assign_by_ref('mMenu_controllers', $mMenu_controllers);

    $menu_icons = array(
        'common'     => -97,
        'finances'   => -228,
        'listings'   => -116,
        'categories' => -135,
        'plugins'    => -154,
        'forms'      => -173,
        'account'    => -192,
        'content'    => -211,
    );
    $rlSmarty->assign_by_ref('menu_icons', $menu_icons);

    /* check admin expire time */
    if (!isset($_POST['xjxfun'])) {
        $ses_exp = session_cache_expire() - 5;
        if (isset($_SESSION['admin_expire_time']) && $_SERVER['REQUEST_TIME'] - $_SESSION['admin_expire_time'] > $ses_exp * 60) {
            session_destroy();

            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $reefless->redirect(null, $redirect_url);
        } else {
            $_SESSION['admin_expire_time'] = $_SERVER['REQUEST_TIME'];
        }
    }

    /* encode double quotes when saving data */
    if (isset($_POST['submit'])) {
        $rlValid->quotes($_POST['name']);
        $rlValid->quotes($_POST['title']);
        $rlValid->quotes($_POST['h1_heading']);
    }
    /* encode double quotes when saving data end */
}

/* check new messages */
$rlAdmin->checkNewMessages();

if ($_SESSION['sessAdmin']['type'] == 'super') {
    $_SESSION['sessAdmin']['rights'][$cInfo['Key']] = array(
        'add'    => 'add',
        'edit'   => 'edit',
        'delete' => 'delete',
    );
    $_SESSION['sessAdmin']['rights']['listings'] = array(
        'add'    => 'add',
        'edit'   => 'edit',
        'delete' => 'delete',
    );
    $_SESSION['sessAdmin']['rights']['categories'] = array(
        'add'    => 'add',
        'edit'   => 'edit',
        'delete' => 'delete',
    );
}

$action = $_GET['action'];
$rights = $_SESSION['sessAdmin']['rights'];

/**
 * @since 4.7.1 - Added $action, $rights parameters
 * @since 4.1.0
 */
$rlHook->load('apPhpIndexBeforeController', $action, $rights);

// define controller
if (($cInfo['Plugin'] && !isset($rights[$cInfo['Key']]) && $_SESSION['sessAdmin']['type'] == 'limited')
    || ($action == 'edit' && is_array($rights[$cInfo['Key']]) && $rights[$cInfo['Key']]['edit'] != 'edit')
    || ($action == 'add' && is_array($rights[$cInfo['Key']]) && $rights[$cInfo['Key']]['add'] != 'add')
) {
    $cInfo['Controller'] = '404';
    $rlSmarty->assign('errors', array(str_replace('{manager}', '<b>' . $cInfo['name'] . '</b>', $lang['admin_access_denied'])));
} elseif (($rights[$cInfo['Key']] || $_SESSION['sessAdmin']['type'] == 'super') || $controller == 'home') {
    $controlFile = $cInfo['Plugin'] ? RL_PLUGINS . $cInfo['Plugin'] . RL_DS . 'admin' . RL_DS . $controller . ".inc.php" : RL_ADMIN_CONTROL . $controller . ".inc.php";

    if (file_exists($controlFile)) {
        require_once $controlFile;

        if ($sError === true) {
            $cInfo['Controller'] = '404';
            $rlSmarty->assign('errors', array($GLOBALS['lang']['error_404']));
        }
    } else {
        $cInfo['Controller'] = '404';
        $rlSmarty->assign('errors', array($GLOBALS['lang']['error_404']));
    }

    $rlSmarty->assign_by_ref('errors', $errors);
} else {
    $cInfo['Controller'] = '404';
    $rlSmarty->assign('errors', array(str_replace('{manager}', '<b>' . $cInfo['name'] . '</b>', $lang['admin_access_denied'])));
}

if (!$_REQUEST['xjxfun']) {
    $extended_sections = array(
        'admins', 'languages', 'data_formats', 'listings', 'listing_fields', 'listing_types',
        'listing_sections', 'listing_groups', 'listing_plans', 'plans_using', 'categories',
        'all_accounts', 'account_types', 'map_amenities', 'account_fields', 'pages', 'news',
        'blocks', 'saved_searches', 'payment_gateways', 'membership_plans',
        'membership_services', 'subscriptions', 'slides',
    );
    $rlSmarty->assign_by_ref('extended_sections', $extended_sections);

    $extended_modes = array('add', 'edit', 'delete');
    $rlSmarty->assign_by_ref('extended_modes', $extended_modes);

    $rlSmarty->assign_by_ref('cInfo', $cInfo);
    $rlSmarty->assign_by_ref('aRights', $rights);
    $rlSmarty->assign_by_ref('cKey', $cInfo['Key']);

    /* load the bread crumbs */
    $breadCrumbs = $rlAdmin->getBreadCrumbs($cInfo['ID'], $bcAStep, array(), $cInfo['Plugin']);
    $rlSmarty->assign_by_ref('breadCrumbs', $breadCrumbs);

    /* assign error fields */
    $rlSmarty->assign_by_ref('error_fields', $error_fields);

    /* get notice */
    if (isset($_SESSION['admin_notice'])) {
        $pNotice = $_SESSION['admin_notice'];
        $pNoticeType = $_SESSION['admin_notice_type'];
        $rlSmarty->assign_by_ref($pNoticeType, $pNotice);
        $rlNotice->resetNotice();
    }
}

/* print total mysql queries execution time */
if (RL_DB_DEBUG) {
    echo '<br /><br />Total sql queries time: <b>' . $_SESSION['sql_debug_time'] . '</b>.<br />';
}

if (!$errors && ($_POST['action'] || $_POST['fromPost'])) {
    Valid::escapeQuotes($_POST);
}

$rlHook->load('apPhpIndexBottom');

/* ajax process request / get javascripts */
$rlXajax->processRequest();

$ajax_javascripts = $rlXajax->getJavascript();

/* assign ajax javascripts */
$rlSmarty->assign_by_ref('ajaxJavascripts', $ajax_javascripts);

if (!$_REQUEST['xjxfun']) {
    $rlSmarty->display('index.tpl');
}

// clear memory (will release ~ 2-3 or more megabytes of memory!)
$rlSmarty->clear_all_assign();

// close the connection with a database
$rlDb->connectionClose();
