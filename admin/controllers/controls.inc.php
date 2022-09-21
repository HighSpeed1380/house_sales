<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CONTROLS.INC.PHP
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

$reefless->loadClass('Controls', 'admin');

/* register ajax methods */
$rlXajax->registerFunction(array('recountListings', $rlControls, 'ajaxRecountListings'));
$rlXajax->registerFunction(array('rebuildCatLevels', $rlControls, 'ajaxRebuildCatLevels'));
$rlXajax->registerFunction(array('reorderFields', $rlControls, 'ajaxReorderFields'));
$rlXajax->registerFunction(array('updateCache', $rlControls, 'ajaxUpdateCache'));
$rlXajax->registerFunction(array('recountListingsMP', $rlControls, 'ajaxRecountListingsMP'));

$rlHook->load('apPhpControlsBottom');
