<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: COMMON.INC.PHP
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

/* send headers */
header("Content-Type: text/html; charset=utf-8");

/* disable cache of content for IE and EDGE */
if (preg_match('/MSIE|Edge|rv:11/i', $_SERVER['HTTP_USER_AGENT'])) {
    header("Expires: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
} else {
    header("Cache-Control: store, no-cache, max-age=3600, must-revalidate");
}

// include abstract gateway class
require_once RL_CLASSES . 'rlGateway.class.php';

$reefless->loadClass('Common');
$reefless->loadClass('Payment');
$reefless->loadClass('MembershipPlan');

// add custom box side
if ($tpl_settings['long_top_block']) {
    $l_block_sides['long_top'] = $lang['long_top'];
}

// get membership services
$rlSmarty->assign_by_ref('membership_services', $rlMembershipPlan->getServices());

/* get listing type key (on listing type pages only) */
if (false !== strpos($page_info['Key'], 'lt_')) {
    $listing_type_key = str_replace('lt_', '', $page_info['Key']);
}

/* simulate category blocks */
$rlCommon->simulateCatBlocks();

/* load common components in non ajax mode */
if (!$_REQUEST['xjxfun']) {
    /* build menus */
    $rlCommon->buildMenus();

    $rlAccount->buildFeaturedBoxes();
    $rlAccount->visitorMessageHashHandler();

    /* get statistics block data */
    if ($block_keys['statistics']) {
        $rlListingTypes->statisticsBlock();
    }

    /* get bread crumbs */
    $bread_crumbs = $rlCommon->getBreadCrumbs($page_info);

    /* check messages */
    if ($account_info['ID']) {
        $message_info = $rlCommon->checkMessages();
        if (!empty($message_info)) {
            $rlSmarty->assign_by_ref('new_messages', $message_info);
        }
    }

    // manage template boxes
    $rlCommon->tplBlocks();
}

/* call special block hooks */
$rlHook->load('specialBlock');

if (in_array($page_info['Controller'], array('listing_type', 'search'))) {
    $rlCategories->buildConversionRates();
}

// set default grid mode
if (!$_COOKIE['grid_mode']) {
    $reefless->createCookie('grid_mode', $config['default_grid_in_tab'], time() + (365 * 86400));
    $_COOKIE['grid_mode'] = $config['default_grid_in_tab'];
}

// set text direction variables
$rlSmarty->assign('text_dir', RL_LANG_DIR == 'rtl' ? 'right' : 'left');
$rlSmarty->assign('text_dir_rev', RL_LANG_DIR == 'rtl' ? 'left' : 'right');
$rlSmarty->assign('upload_max_size', \Flynax\Utils\Util::getMaxFileUploadSize());

/* register blocks in smarty */
function smartyEval($param, $content, &$smarty)
{
    return $content;
}

function insert_eval($params, &$smarty)
{
    require_once RL_LIBS . 'smarty' . RL_DS . 'plugins' . RL_DS . 'function.eval.php';

    return smarty_function_eval(array("var" => $params['content']), $smarty);
}

$rlSmarty->register_block('eval', 'smartyEval', false);
$rlSmarty->registerFunctions();
