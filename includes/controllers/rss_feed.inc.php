<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RSS_FEED.INC.PHP
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

Valid::revertQuotes($lang);
Valid::revertQuotes($config['site_name']);

$rlSmarty->register_function('str2path', [$rlSmarty, 'str2path']);
$rlSmarty->register_function('rlHook', [$rlHook, 'load']);

$reefless->loadClass('ListingTypes', null, false, true);
$reefless->loadClass('Valid');
$reefless->loadClass('Categories');
$reefless->loadClass('Listings');
$reefless->loadClass('Common');
$reefless->loadClass('MembershipPlan');

$item = $rlValid->xSql($_GET['nvar_1'] ? $_GET['nvar_1'] : $_GET['item']);
$id = $rlValid->xSql($_GET['nvar_2'] ? $_GET['nvar_2'] : $_GET['id']);

$rlSmarty->assign('site_name', $config['site_name']);
$rlSmarty->assign('rss_item', $item);

switch ($item) {
    case 'account-listings':
        $reefless->loadClass('Account');
        $account = $rlAccount->getProfile($id);

        if ($account) {
            $rss = array(
                'title'     => str_replace('{name}', $account['Full_name'], $lang['account_rss_feed_caption']),
                'path'      => $account['Personal_address'],
                'back_link' => $account['Personal_address'],
            );
            $rlSmarty->assign_by_ref('rss', $rss);

            $sorting['ID']['field'] = 'ID';
            $data = $rlListings->getListingsByAccount($account['ID'], 'ID', 'DESC', 0, $config['listings_per_rss']);
            $rlSmarty->assign_by_ref('listings', $data);
        } else {
            $sError = true;
        }

        break;

    case 'news':
        $reefless->loadClass('News');

        $news = $rlNews->get(false, true, $pInfo['current']);
        $rlSmarty->assign_by_ref('news', $news);

        $rss = array(
            'title'       => $lang['pages+name+' . $pages['news']],
            'description' => $lang['pages+meta_description+' . $pages['news']]
            ? $lang['pages+meta_description+' . $pages['news']]
            : $lang['pages+title+' . $pages['news']],
            'path'        => $pages['news'],
            'back_link'   => $reefless->getPageUrl('news'),
        );
        $rlSmarty->assign_by_ref('rss', $rss);

        break;

    case 'category':
        if (is_numeric($id)) {
            $category = $rlCategories->getCategory($id);
            if (!$category) {
                $sError = true;
            }
            $rlSmarty->assign_by_ref('category', $category);

            $back_link = $reefless->getCategoryUrl($category['ID']);
        } else {
            $listing_type = $rlListingTypes->types[$id];

            // simulate category
            if ($listing_type) {
                $category['ID'] = 0;
                $category['name'] = $listing_type['name'];

                $back_link = $reefless->getPageUrl($listing_type['Page_key']);
            }
        }

        $rss = array(
            'title'       => str_replace('&', '&amp;', $category['name']),
            'description' => $category['des'],
            'back_link'   => $back_link,
            'id'          => $category['ID'],
        );

        $rlSmarty->assign_by_ref('rss', $rss);

        $listings = $rlListings->getListings(
            $category['ID'], 
            false, 
            'ASC', 
            $pInfo['current'], 
            $config['listings_per_rss'], 
            $listing_type['Key']
        );
        $rlSmarty->assign_by_ref('listings', $listings);

        break;

    default:
        $rss = array(
            'title'       => $lang['pages+title+listings'] . ' | ' . $site_name,
            'description' => $lang['pages+meta_description+listings'],
            'back_link'   => SEO_BASE . $pages['listings'] . ".html",
        );

        $rlSmarty->assign_by_ref('rss', $rss);

        $listings = $rlListings->getRecentlyAdded(0, $config['listings_per_rss']);
        $rlSmarty->assign_by_ref('listings', $listings);

        break;
}

$rlHook->load('rssFeedBottom');

header("Content-Type: text/xml; charset=utf-8");
