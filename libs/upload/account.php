<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ACCOUNT.PHP
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

/* load configs */
include_once( dirname(__FILE__) . "/../../includes/config.inc.php");

/* system controller */
require_once( RL_INC . 'control.inc.php' );

/* load system configurations */
$config = $rlConfig -> allConfig();
$rlSmarty -> assign_by_ref('config', $config);
$GLOBALS['config'] = $config;

$reefless -> loadClass('Account');

$add_photo_path = $rlDb -> getOne('Path', "`Key` = 'add_photo'", 'pages');
$listing_id = is_numeric(strpos($_SERVER['HTTP_REFERER'], $add_photo_path)) ? (int)$_SESSION['add_photo']['listing_id'] : (int)$_SESSION['add_listing']['listing_id'];
$account_info = $_SESSION['account'];
$upload_controller = 'account.php';

if ( !$listing_id )
    exit;

if ( !$rlAccount -> isLogin() )
    exit;

if ( $account_info['ID'] != $rlDb -> getOne('Account_ID', "`ID` = '{$listing_id}'", 'listings') )
    exit;

$reefless -> loadClass('Json');
    
include_once(RL_LIBS .'upload'. RL_DS . 'upload.php');
