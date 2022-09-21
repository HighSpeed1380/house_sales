<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_DETAILS.INC.PHP
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

$reefless->loadClass('Listings');
$reefless->loadClass('MembershipPlan');

// Get listing info
$sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type`, `T2`.`Key` AS `Cat_key`, `T2`.`Type` AS `Cat_type`,";
$sql .= "`T2`.`Path` AS `Cat_path`, `T2`.`Parent_keys`, `T2`.`Parent_IDs`, `T2`.`Parent_ID`, ";

if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
    $sql .= 'IF(`T2`.`Path_' . RL_LANG_CODE . "` <> '', ";
    $sql .= '`T2`.`Path_' . RL_LANG_CODE . '`, `T2`.`Path`) AS `Path`, ';
} else {
    $sql .= '`T2`.`Path`, ';
}

if ($config['membership_module']) {
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image`, `T3`.`Image`) AS `Image`, ";
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image_unlim`, `T3`.`Image_unlim`) AS `Image_unlim`, ";
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video`, `T3`.`Video`) AS `Video`, ";
    $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video_unlim`, `T3`.`Video_unlim`) AS `Video_unlim`, ";
} else {
    $sql .= "`T3`.`Image`, `T3`.`Image_unlim`, `T3`.`Video`, `T3`.`Video_unlim`, ";
}

$sql .= "CONCAT('categories+name+', `T2`.`Key`) AS `Category_pName`, ";
$sql .= "IF ( UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T3`.`Listing_period` DAY)) <= UNIX_TIMESTAMP(NOW()) AND `T3`.`Listing_period` > 0, 1, 0) AS `Listing_expired` ";
$sql .= "FROM `{db_prefix}listings` AS `T1` ";
$sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
$sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
$sql .= "LEFT JOIN `{db_prefix}accounts` AS `T5` ON `T1`.`Account_ID` = `T5`.`ID` ";

if ($GLOBALS['config']['membership_module']) {
    $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
}
$sql .= "WHERE `T1`.`ID` = '{$listing_id}' AND `T2`.`Status` = 'active' AND `T5`.`Status` = 'active' ";

if (!$config['ld_keep_alive']) {
    $sql .= "AND `T1`.`Status` = 'active' ";
}

$rlHook->load('listingDetailsSql', $sql);

$sql .= "LIMIT 1";

$listing_data = $rlDb->getRow($sql);
$rlSmarty->assign_by_ref('listing_data', $listing_data);

// define access to photos
$allow_photos = $rlMembershipPlan->isPhotoAllow($listing_data);

if (($listing_data['Status'] != 'active' || $listing_data['Listing_expired']) && $config['ld_keep_alive']) {
    $page_info['Listing_details_inactive'] = true;
    foreach (explode(",", $config['ld_keep_hiddenfields']) as $key => $unset) {
        unset($listing_data[$unset]);
    }

    if ($tpl_settings['type'] == 'responsive_42') {
        unset($blocks['get_more_details']);
    }
}

/* define listing type */
$listing_type = $rlListingTypes->types[$listing_data['Listing_type']];
$rlSmarty->assign_by_ref('listing_type', $listing_type);

/* get listing title */
$listing_title = $rlListings->getListingTitle(
    $listing_data['Category_ID'],
    $listing_data,
    $listing_type['Key'],
    false,
    $listing_data['Parent_IDs']
);

/* validate listing url */
if ($config['mod_rewrite'] && $listing_data) {
    $rlListings->originalUrlRedirect('listing', $listing_data);
}

// get "Login" parameter of "View Details" page
$page_info['Login'] = $rlDb->getOne('Login', "`Key` = 'view_details'", 'pages');

if (empty($listing_id) || empty($listing_data) || ($listing_data['Status'] != 'active' && $listing_data['Account_ID'] != $account_info['ID'] && !$config['ld_keep_alive'])) {
    $sError = true;
} elseif ($listing_data['Listing_expired'] && !$config['ld_keep_alive']) {
    $errors[] = $lang['error_listing_expired'];
} else {
    if (($rlAccount->isLogin() && $page_info['Login']) || !$page_info['Login']) {
        $rlHook->load('listingDetailsTop');

        // count visit
        if ($config['count_listing_visits']) {
            register_shutdown_function(array($rlListings, 'countVisit'), $listing_data['ID']);
        }

        /* enable print page */
        $print = array(
            'item' => 'listing',
            'id'   => $listing_data['ID'],
        );
        $rlSmarty->assign_by_ref('print', $print);

        /* display add to favourite icon */
        $navIcons[] = '<a title="' . $lang['add_to_favorites'] . '" id="fav_' . $listing_data['ID'] . '" class="icon add_favorite" href="javascript:void(0)"> <span></span> </a>';

        /* add "back to search results" link | DEPRECATED FROM 4.1.0 > */
        if ($_SESSION['keyword_search_data']) {
            $navIcons = array_reverse($navIcons);
            $return_link = SEO_BASE;

            if ($_SESSION['keyword_search_pageNum'] > 1) {
                $paging = $config['mod_rewrite'] ? '/index' . $_SESSION['keyword_search_pageNum'] : '&amp;pg=' . $_SESSION['keyword_search_pageNum'];
            }

            $return_link .= $config['mod_rewrite'] ? $pages['search'] . $paging . '.html' : '?page=' . $pages['search'] . '&amp;' . $paging;
            $navIcons[] = '<a title="' . $lang['back_to_search_results'] . '" href="' . $return_link . '">&larr; ' . $lang['back_to_search_results'] . '</a>';
            $navIcons = array_reverse($navIcons);
        } elseif ($_SESSION[$listing_type['Key'] . '_post']) {
            $navIcons = array_reverse($navIcons);
            $return_link = SEO_BASE;

            if ($_SESSION[$listing_type['Key'] . '_advanced']) {
                $search_results_url = $config['mod_rewrite'] ? $advanced_search_url . '/' . $search_results_url : $advanced_search_url . '&amp;' . $search_results_url;
            }
            if ($_SESSION[$listing_type['Key'] . '_pageNum'] > 1) {
                $paging = $config['mod_rewrite'] ? '/index' . $_SESSION[$listing_type['Key'] . '_pageNum'] : '&amp;pg=' . $_SESSION[$listing_type['Key'] . '_pageNum'];
            }

            $return_link .= $config['mod_rewrite'] ? $page_info['Path'] . '/' . $search_results_url . $paging . '.html' : '?page=' . $page_info['Path'] . '&amp;' . $search_results_url . $paging;
            $navIcons[] = '<a title="' . $lang['back_to_search_results'] . '" href="' . $return_link . '">&larr; ' . $lang['back_to_search_results'] . '</a>';
            $navIcons = array_reverse($navIcons);
        }
        // DEPRECATED

        $rlSmarty->assign_by_ref('navIcons', $navIcons);

        // define "is owner"
        $rlSmarty->assign('is_owner', $account_info['ID'] == $listing_data['Account_ID']);

        /* build listing structure */
        $category_id = $listing_data['Category_ID'];
        $listing = $rlListings->getListingDetails($category_id, $listing_data, $listing_type);
        $rlSmarty->assign_by_ref('listing', $listing);

        /* get seller information */
        $seller_info = $rlAccount->getProfile((int) $listing_data['Account_ID']);
        $rlSmarty->assign_by_ref('seller_info', $seller_info);

        // re-assign is_contact_allowed value in case if the logged in user is owner of the listing
        if ($account_info['ID'] == $seller_info['ID']) {
            $rlMembershipPlan->is_contact_allowed = true;
        }

        // get short form details in case if own page option disabled
        $owner_short_details = $rlAccount->getShortDetails($seller_info, $seller_info['Account_type_ID'], true);
        if ($account_info['ID'] != $seller_info['ID']) {
            $rlMembershipPlan->fakeValues($owner_short_details);
        }
        $rlSmarty->assign_by_ref('owner_short_details', $owner_short_details);

        /* get location data for google map */
        $fields_list = $rlListings->fieldsList;

        $location = false;
        foreach ($fields_list as $key => $value) {
            if ($fields_list[$key]['Map'] && !empty($listing_data[$fields_list[$key]['Key']])) {
                $mValue = addslashes($value['value']);
                $location['search'] .= $mValue . ', ';
                $location['show'] .= $lang[$value['pName']] . ': <b>' . $mValue . '<\/b><br />';
                unset($mValue);
            }
        }
        if (!empty($location)) {
            $location['search'] = substr($location['search'], 0, -2);
        }
        if ($listing_data['Loc_latitude'] && $listing_data['Loc_longitude']) {
            $location['direct'] = $listing_data['Loc_latitude'] . ',' . $listing_data['Loc_longitude'];
        }
        $rlSmarty->assign_by_ref('location', $location);
        /* get location data for google map end */

        /* redefine page title/bread crumbs */
        $reefless->loadClass('Categories');

        $cat_bread_crumbs = $rlCategories->getBreadCrumbs($category_id, null, $listing_type);
        $cat_bread_crumbs = array_reverse($cat_bread_crumbs);

        if (!empty($cat_bread_crumbs)) {
            foreach ($cat_bread_crumbs as $bKey => $bVal) {
                $cat_bread_crumbs[$bKey]['path'] = $config['mod_rewrite'] ? $page_info['Path'] . '/' . $cat_bread_crumbs[$bKey]['Path'] : $page_info['Path'] . '&amp;category=' . $cat_bread_crumbs[$bKey]['ID'];

                $cat_bread_crumbs[$bKey]['title'] = $cat_bread_crumbs[$bKey]['name'];
                $cat_bread_crumbs[$bKey]['category'] = true;
                $bread_crumbs[] = $cat_bread_crumbs[$bKey];
            }
        }

        /**
         * @since 4.7.1
         */
        $rlHook->load('listingDetailsBeforeMetaData', $page_info, $listing, $listing_data);

        $bread_crumbs[] = array(
            'title' => $listing_title,
            'name'  => $lang['pages+name+view_details'],
        );

        $page_info['name']  = $listing_title;
        $page_info['title'] = $listing_title;

        $page_info['meta_description'] = $rlListings->replaceMetaFields($listing_data['Category_ID'], $listing_data, 'description');
        $page_info['meta_keywords'] = $rlListings->replaceMetaFields($listing_data['Category_ID'], $listing_data, 'keywords');
        $page_info['meta_title'] = $rlListings->replaceMetaFields($listing_data['Category_ID'], $listing_data, 'title');

        $photos_limit = $listing_data['Image_unlim'] ? true : $listing_data['Image'];
        $videos_limit = $listing_data['Video_unlim'] ? true : $listing_data['Video'];

        // Get listing media
        $media = Flynax\Utils\ListingMedia::get($listing_id, $photos_limit, $videos_limit, $listing_type);
        $rlSmarty->assign_by_ref('photos', $media);

        /* get amenties */
        if ($config['map_amenities']) {
            $rlDb->setTable('map_amenities');
            $amenities = $rlDb->fetch(array('Key', 'Default'), array('Status' => 'active'), "ORDER BY `Position`");
            $amenities = $rlLang->replaceLangKeys($amenities, 'map_amenities', array('name'));
            $rlSmarty->assign_by_ref('amenities', $amenities);
        }

        /* populate tabs */
        $tabs = array(
            'listing'     => array(
                'key'  => 'listing',
                'name' => $lang['listing'],
            ),
            'tell_friend' => array(
                'key'  => 'tell_friend',
                'name' => $lang['tell_friend'],
            ),
        );

        if ($page_info['Listing_details_inactive'] || !$config['tell_a_friend_tab']) {
            unset($tabs['tell_friend']);
        }
        $rlSmarty->assign_by_ref('tabs', $tabs);

        $reefless->loadClass('Message');

        // assign membership services to template
        $rlSmarty->assign_by_ref('allow_photos', $allow_photos);

        /* register ajax methods */
        $rlXajax->registerFunction(array('tellFriend', $rlListings, 'ajaxTellFriend'));
        $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwner'));

        $rlHook->load('listingDetailsBottom');

        $rlStatic->addHeaderCss(RL_TPL_BASE . 'components/call-owner/call-owner-buttons.css');
    } else {
        // remove box with contact seller form
        unset($blocks['get_more_details']);
        $rlCommon->defineBlocksExist($blocks);
    }
}
