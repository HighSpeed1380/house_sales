<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RECENTLY_ADDED.INC.PHP
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

use Flynax\Utils\Util;

$rlXajax->registerFunction(array('loadRecentlyAdded', $rlListings, 'ajaxloadRecentlyAdded'));

/* get requested type */
foreach ($rlListingTypes->types as $type) {
    $default_type = !$default_type ? $type['Key'] : $default_type;
    if (isset($_GET[$type['Key']]) || array_search($type['Key'], $_GET, true)) {
        $request_type = $type['Key'];
        break;
    }
}

$default = $_SESSION['recently_added_type'] && $rlListingTypes->types[$_SESSION['recently_added_type']] ? $_SESSION['recently_added_type'] : $default_type;
$requested_type = $request_type ? $request_type : $default;

if ($default_type == $default && $requested_type && $request_type && !$_GET['pg']) {
    Util::redirect($reefless->getPageUrl($page_info['Key']));
}

$_SESSION['recently_added_type'] = $requested_type;
$rlSmarty->assign_by_ref('requested_type', $requested_type);

$pInfo['current'] = (int) $_GET['pg'];

$page_info['title'] = str_replace(
    '{listing_type}',
    $rlListingTypes->types[$requested_type]['name'],
    $page_info['title']
);

/* get listings */
$listings = $rlListings->getRecentlyAdded($pInfo['current'], $config['listings_per_page'], $requested_type);
$rlSmarty->assign_by_ref('listings', $listings);

// do 301 redirect to the first page if no listings found for requested page
if (!$listings && $pInfo['current'] > 1 && $requested_type) {
    Util::redirect($reefless->getPageUrl($page_info['Key'], array('type' => $requested_type)), true, 302);
}

$pInfo['calc'] = $rlListings->calc;
$rlSmarty->assign_by_ref('pInfo', $pInfo);

/* build rss */
$rss = array(
    'title' => $lang['pages+name+listings'],
);
$rlSmarty->assign_by_ref('rss', $rss);

$rlHook->load('listingsBottom');
