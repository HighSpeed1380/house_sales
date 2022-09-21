<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MY_FAVORITE.INC.PHP
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

if (!$_POST['xjxfun']) {
    $rlAccount->synchronizeFavorites();
}

$reefless->loadClass('Listings');
$reefless->loadClass('Actions');

/* paging info */
$pInfo['current'] = (int) $_GET['pg'];

/* fields for sorting */
$sorting = array(
    'category' => array(
        'name'  => $lang['category'],
        'field' => 'Category_ID',
    ),
    'featured' => array(
        'name'  => $lang['featured'],
        'field' => 'Featured',
    ),
);
$rlSmarty->assign_by_ref('sorting', $sorting);

/* define sort field */
$sort_by = empty($_GET['sort_by']) ? $_SESSION['fl_sort_by'] : $_GET['sort_by'];
if (!empty($sorting[$sort_by])) {
    $order_field = $sorting[$sort_by]['field'];
}
$_SESSION['fl_sort_by'] = $sort_by;
$rlSmarty->assign_by_ref('sort_by', $sort_by);

/* define sort type */
$sort_type = empty($_GET['sort_type']) ? $_SESSION['fl_sort_type'] : $_GET['sort_type'];
$sort_type = ($sort_type == 'asc' || $sort_type == 'desc') ? $sort_type : false;
$_SESSION['fl_sort_type'] = $sort_type;
$rlSmarty->assign_by_ref('sort_type', $sort_type);

/* get listings */
$listings = $rlListings->getMyFavorite($order_field, $sort_type, $pInfo['current'], $config['listings_per_page']);
$rlSmarty->assign_by_ref('listings', $listings);

$pInfo['calc'] = $rlListings->calc;
$rlSmarty->assign_by_ref('pInfo', $pInfo);

$rlHook->load('myFavoriteBottom');
