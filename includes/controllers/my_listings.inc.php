<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MY_LISTINGS.INC.PHP
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

if (defined('IS_LOGIN')) {
    $reefless->loadClass('Listings');
    $reefless->loadClass('Actions');
    $reefless->loadClass('Search');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteListing', $rlListings, 'ajaxDeleteListing'));

    /* define listings type */
    $l_type_key = substr($page_info['Key'], 3);
    $listings_type = $rlListingTypes->types[$l_type_key];

    if ($listings_type) {
        $rlSmarty->assign_by_ref('listings_type', $listings_type);
        $rlSmarty->assign('page_key', 'lt_' . $listings_type['Key']);
    }

    if ($config['one_my_listings_page']) {
        $search_forms = array();

        // get search forms
        foreach ($rlListingTypes->types as $lt_key => $ltype) {
            if ($ltype['Myads_search']) {
                if ($search_form = $rlSearch->buildSearch($lt_key . '_myads')) {
                    $search_forms[$lt_key] = $search_form;
                }

                unset($search_form);
            }
        }

        // define all available listing types & search forms
        $rlSmarty->assign_by_ref('listing_types', $rlListingTypes->types);
        $rlSmarty->assign_by_ref('search_forms', $search_forms);

        // save selected listing type in search
        if ($_POST['search_type'] || $_SESSION['search_type']) {
            if ($_POST['search_type']) {
                $_SESSION['search_type'] = $search_type = $_POST['search_type'];
            } else if ($_SESSION['search_type']
                && (isset($_GET[$search_results_url]) || $_GET['nvar_1'] == $search_results_url)
                && $_GET['pg']
            ) {
                $_POST['search_type'] = $search_type = $_SESSION['search_type'];
            }

            if ($_POST['post_form_key']) {
                $_SESSION['post_form_key'] = $_POST['post_form_key'];
            }

            $rlSmarty->assign_by_ref('selected_search_type', $search_type);
            $rlSmarty->assign('refine_search_form', true);
        }
    }

    $add_listing_href = $config['mod_rewrite'] ? SEO_BASE . $pages['add_listing'] . '.html' : RL_URL_HOME . 'index.php?page=' . $pages['add_listing'];
    $rlSmarty->assign_by_ref('add_listing_href', $add_listing_href);

    /* paging info */
    $pInfo['current'] = (int) $_GET['pg'];

    /* fields for sorting */
    $sorting = array(
        'date'        => array(
            'name'  => $lang['date'],
            'field' => "date",
            'Type'  => 'date',
        ),
        'category'    => array(
            'name'  => $lang['category'],
            'field' => 'Category_ID',
        ),
        'status'      => array(
            'name'  => $lang['status'],
            'field' => 'Status',
        ),
        'expire_date' => array(
            'name'  => $lang['expire_date'],
            'field' => 'Plan_expire',
        ),
    );
    $rlSmarty->assign_by_ref('sorting', $sorting);

    /* define sort field */
    $sort_by = empty($_GET['sort_by']) ? $_SESSION['ml_sort_by'] : $_GET['sort_by'];
    $sort_by = $sort_by ? $sort_by : 'date';
    if (!empty($sorting[$sort_by])) {
        $order_field = $sorting[$sort_by]['field'];
    }
    $_SESSION['ml_sort_by'] = $sort_by;
    $rlSmarty->assign_by_ref('sort_by', $sort_by);

    /* define sort type */
    $sort_type = empty($_GET['sort_type']) ? $_SESSION['ml_sort_type'] : $_GET['sort_type'];
    $sort_type = !$sort_type && $sort_by == 'date' ? 'desc' : $sort_type;
    $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
    $_SESSION['ml_sort_type'] = $sort_type;
    $rlSmarty->assign_by_ref('sort_type', $sort_type);

    $rlHook->load('myListingsPreSelect');

    if ($pInfo['current'] > 1) {
        $bc_page = str_replace('{page}', $pInfo['current'], $lang['title_page_part']);

        // add bread crumbs item
        $bread_crumbs[1]['title'] .= $bc_page;
    }

    $reefless->loadClass('Plan');
    $available_plans = $rlPlan->getPlanByCategory(0, $account_info['Type'], true);
    $rlSmarty->assign_by_ref('available_plans', $available_plans);

    if ($listings_type) {
        $listing_type_key = $listings_type['Key'];
    } else if ($l_type_key == 'all_ads') {
        $listing_type_key = 'all_ads';
    }

    // build search form
    if ($config['one_my_listings_page']
        && ($_POST['search_type']
            || ($_SESSION['search_type']
                && (isset($_GET[$search_results_url]) || $_GET['nvar_1'] == $search_results_url)))
    ) {
        $listing_type_key = $_POST['search_type'] ?: $_SESSION['search_type'];

        if ($_POST['post_form_key'] || $_SESSION['post_form_key']) {
            $form_key = $_POST['post_form_key'] ?: $_SESSION['post_form_key'];
        }
    } else {
        $form_key = $listing_type_key . '_myads';
    }

    $form = false;
    if (($block_keys && array_key_exists('ltma_' . $listing_type_key, $block_keys))
        || $config['one_my_listings_page']
    ) {
        if ($form = $rlSearch->buildSearch($form_key)) {
            if ($listings_type) {
                $rlSmarty->assign('listing_type', $listings_type);
            }

            $rlSmarty->assign('refine_search_form', $form);
        }

        $rlCommon->buildActiveTillPhrases();
    }

    // emulation
    if ($_SESSION[$listing_type_key . '_post'] && $_REQUEST['action'] != 'search') {
        $_POST = $_SESSION[$listing_type_key . '_post'];
    }

    /* search results mode */
    if ($_GET['nvar_1'] == $search_results_url ||
        $_GET['nvar_2'] == $search_results_url ||
        isset($_GET[$search_results_url])
    ) {
        // redirect to My ads page to reset search criteria when type wasn't selected
        if ($config['one_my_listings_page'] && $_POST['action'] == 'search' && !$_POST['search_type']) {
            $reefless->redirect(null, $reefless->getPageUrl('my_all_ads'));
        }

        $rlSmarty->assign('search_results_mode', true);

        $data = $_SESSION[$listing_type_key . '_post'] = $_REQUEST['f']
        ? $_REQUEST['f']
        : $_SESSION[$listing_type_key . '_post'];

        // re-assign POST for refine search block
        if ($_POST['f']) {
            $_POST = $_POST['f'];
        }

        $pInfo['current'] = (int) $_GET['pg'];
        $data['myads_controller'] = true;

        // get current search form
        $rlSearch->getFields($form_key, $listing_type_key);

        // load fields from "quick_" form if "my_" form is empty
        if (!$rlSearch->fields && $config['one_my_listings_page'] && $search_type) {
            $rlSearch->fields = true;
        }

        // get listings
        $listings = $rlSearch->search($data, $listing_type_key, $pInfo['current'], $config['listings_per_page']);
        $rlSmarty->assign_by_ref('listings', $listings);

        $pInfo['calc'] = $rlSearch->calc;
        $rlSmarty->assign('pInfo', $pInfo);

        if ($listings) {
            $page_info['name'] = str_replace('{number}', $pInfo['calc'], $lang['listings_found']);
        } elseif ($_GET['pg']) {
            Flynax\Utils\Util::redirect($reefless->getPageUrl($page_info['Key']));
        }

        $rlHook->load('phpMyAdsSearchMiddle');

        // add bread crumbs item
        $page_info['title'] = $sort_by
        ? str_replace('{field}', $sorting[$sort_by]['name'], $lang['search_results_sorting_mode'])
        : $lang['search_results'];

        if ($pInfo['current']) {
            $page_info['title'] .= str_replace('{page}', $pInfo['current'], $lang['title_page_part']);
        }

        $bread_crumbs[] = array(
            'title' => $page_info['title'],
            'name'  => $lang['search_results'],
        );

        // save current page number
        if ($_GET['pg']) {
            $_SESSION[$listing_type_key . '_pageNum'] = (int) $_GET['pg'];
        } else {
            unset($_SESSION[$listing_type_key . '_pageNum']);
        }
    }
    /* browse mode */
    else {
        // get my listings
        $listings = $rlListings->getMyListings($listing_type_key, $order_field, $sort_type, $pInfo['current'], $config['listings_per_page']);
        $rlSmarty->assign('listings', $listings);

        /* redirect to the first page if no listings found */
        if (!$listings && $_GET['pg']) {
            if ($config['mod_rewrite']) {
                $url = SEO_BASE . $page_info['Path'] . ".html";
            } else {
                $url = SEO_BASE . "?page=" . $page_info['Path'];
            }

            header('Location: ' . $url, true, 301);
            exit;
        }
        /* redirect to the first page end */

        $pInfo['calc'] = $rlListings->calc;
        $rlSmarty->assign('pInfo', $pInfo);

        // remove box if necessary
        if (!$form || empty($listings)) {
            $rlCommon->removeSearchInMyAdsBox($listing_type_key);

            // remove all search boxes if access is denied for this user
            if ($listing_type_key == 'all_ads'
                && (isset($account_info['Type'])
                    && in_array($account_info['Type_ID'], explode(',', $page_info['Deny']))
                )
            ) {
                $rlCommon->removeAllSearchInMyAdsBoxes();
            }
        }
    }
} else {
    $rlCommon->removeAllSearchInMyAdsBoxes();
}
