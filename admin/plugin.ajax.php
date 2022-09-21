<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PLUGIN.AJAX.PHP
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

define('SKIP_HOOKS', true);

require '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.inc.php';
require 'controllers' . RL_DS . 'ext_header.inc.php';

$reefless->loadClass('Plugin', 'admin');
$reefless->loadClass('Actions');

set_time_limit(0);

$languages = $rlLang->getLanguagesList();
$item = $_REQUEST['item'];

switch ($item) {
    case 'install':
        $out = $rlPlugin->ajaxInstall($_REQUEST['key'], $_REQUEST['remote']);
        break;

    case 'update':
        $out = $rlPlugin->ajaxUpdate($_REQUEST['key'], $_REQUEST['remote']);
        break;

    case 'remoteInstall':
        $out = $rlPlugin->ajaxRemoteInstall($_REQUEST['key']);
        break;

    case 'remoteUpdate':
        $out = $rlPlugin->ajaxRemoteUpdate($_REQUEST['key']);
        break;
}

$rlDb->connectionClose();

echo json_encode($out);
