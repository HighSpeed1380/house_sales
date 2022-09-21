<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_TYPE.INC.PHP
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

use \Flynax\Utils\Category;

// define membership services
if ($config['membership_module']) {
    $reefless->loadClass('MembershipPlan');
    $membership_services = $rlMembershipPlan->getServices();
    $rlSmarty->assign_by_ref('membership_services', $membership_services);

    if (!$rlAccount->isLogin() && isset($membership_services[rlMembershipPlan::SERVICE_VIEW_PHOTOS]) || $rlAccount->isLogin() && !$account_info['plan']['services'][rlMembershipPlan::SERVICE_VIEW_PHOTOS]) {
        $rlSmarty->assign(rlMembershipPlan::SERVICE_VIEW_PHOTOS, 1);
    }
}

$listing_type_key = str_replace('lt_', '', $page_info['Key']);

$listing_type = $rlListingTypes->types[$listing_type_key];
$rlSmarty->assign_by_ref('listing_type', $listing_type);

// re-define listing type variables
if (!$config['mod_rewrite'] && $listing_type['Submit_method'] == 'get') {
    $listing_type['Submit_method'] = 'post';
}

// refine block controller
$refine_block_controller = 'blocks' . RL_DS . 'refine_search.tpl';
$rlSmarty->assign_by_ref('refine_block_controller', $refine_block_controller);

// get listing ID
$listing_id = intval($config['mod_rewrite'] ? $_GET['listing_id'] : $_GET['id']);

// date field for sorting array, used two times
$date_field['date'] = array('Key' => 'date', 'Type' => 'date', 'name' => $lang['date']);

if (!empty($listing_id)) {
    require_once RL_INC . 'controllers' . RL_DS . 'listing_details.inc.php';
    $page_info['Controller'] = 'listing_details';
} else {
    $rlHook->load('phpListingTypeTop'); // >= v4.3

    $post_form_key = $_SESSION['post_form_key'] = $_REQUEST['post_form_key'] ? $_REQUEST['post_form_key'] : $_SESSION['post_form_key'];
    $post_form_key = $post_form_key ? $post_form_key : $listing_type['Key'] . '_quick';

    // is requested form splited by tabs
    $tab_form = 0 === strpos($post_form_key, $listing_type['Key'] . '_tab') ? true : false;
    $form_key = $post_form_key;

    $reefless->loadClass('Listings');
    $reefless->loadClass('Search');

    // emulation
    if ($_SESSION[$listing_type_key . '_post'] && $_REQUEST['action'] != 'search') {
        $_POST = $_SESSION[$listing_type_key . '_post'];
    }

    // search results mode
    if (array_search($search_results_url, $_GET, true) || isset($_GET[$search_results_url])) {
        if ($config['mod_rewrite']) {
            $category = Category::getCategory(false, str_replace($search_results_url, '', $_GET['rlVareables']));
        } else {
            $category = Category::getCategory($_GET['category']);
        }
        $rlSmarty->assign_by_ref('category', $category);

        // get search form post data
        $rlSmarty->assign('search_results', true);
        $data = $_SESSION[$listing_type_key . '_post'] = $_REQUEST['f'] 
        ? $_REQUEST['f'] 
        : $_SESSION[$listing_type_key . '_post'];

        // re-assign POST || GET for refine search block (initiate selection data)
        if ($_POST['f'] || $_GET['f']) {
            if ($_POST['f']) {
                $_POST = $_POST['f'];
            } else if ($_GET['f']) {
                $_GET = $_GET['f'];
            }

            unset(
                $_SESSION['keyword_search'],
                $_SESSION['keyword_search_data'],
                $_SESSION['keyword_search_sort_by'],
                $_SESSION['keyword_search_sort_type']
            );
        }

        // sorting
        if ($_REQUEST['f']['sort_by'] && $_REQUEST['f']['sort_type']) {
            $data['sort_by'] = $_SESSION[$listing_type_key . '_sort_by'] = $_REQUEST['f']['sort_by'];
            $data['sort_type'] = $_SESSION[$listing_type_key . '_sort_type'] = $_REQUEST['f']['sort_type'];
        } else if ($_REQUEST['sort_by'] && $_REQUEST['sort_type']) {
            $data['sort_by'] = $_SESSION[$listing_type_key . '_sort_by'] = $_REQUEST['f']['sort_by'] = $_REQUEST['sort_by'];
            $data['sort_type'] = $_SESSION[$listing_type_key . '_sort_type'] = $_REQUEST['f']['sort_type'] = $_REQUEST['sort_type'];
        } else if ($_SESSION[$listing_type_key . '_sort_by'] && $_SESSION[$listing_type_key . '_sort_type']) {
            $data['sort_by'] = $_REQUEST['f']['sort_by'] = $_SESSION[$listing_type_key . '_sort_by'];
            $data['sort_type'] = $_REQUEST['f']['sort_type'] = $_SESSION[$listing_type_key . '_sort_type'];
        }

        $pInfo['current'] = (int) $_GET['pg'];

        // advanced search results emulation
        if (($_GET['nvar_1'] == $advanced_search_url || isset($_GET[$advanced_search_url])) && $listing_type['Advanced_search']) {
            $form_key = $listing_type['Key'] . '_advanced';

            // add bread crumbs item
            $bread_crumbs[] = array(
                'name'  => $lang['advanced_search'],
                'title' => $lang['back_to_advanced_search'],
                'path'  => $config['mod_rewrite'] ? $page_info['Path'] . '/' . $advanced_search_url : 'index.php?' . $advanced_search_url,
            );

            // emulation
            $rlSmarty->assign('advanced_mode', true);
            $concat = $config['mod_rewrite'] ? '/' : '&amp;';
            $search_results_url = $advanced_search_url . $concat . $search_results_url;

            $_SESSION[$listing_type['Key'] . '_advanced'] = true;
        }

        // get sorting fields
        $rlSearch->getFields($tab_form ? $post_form_key : $listing_type_key . '_quick', $listing_type_key);

        $sorting = is_array($rlSearch->fields) ? array_merge($date_field, $rlSearch->fields) : $date_field;
        unset($sorting['keyword_search']);
        $rlSmarty->assign_by_ref('sorting', $sorting);

        // define sort field
        $sort_by = empty($_REQUEST['f']['sort_by']) ? $_SESSION['search_sort_by'] : $_REQUEST['f']['sort_by'];
        $sort_by = $sort_by ? $sort_by : 'date';

        if (!empty($sorting[$sort_by])) {
            $order_field = $sorting[$sort_by]['Key'];
            $data['sort_by'] = $sort_by;
            $rlSmarty->assign_by_ref('sort_by', $sort_by);
        }

        // define sort type
        $sort_type = empty($_REQUEST['f']['sort_type']) ? $_SESSION['search_sort_type'] : $_REQUEST['f']['sort_type'];
        $sort_type = !$sort_type && $sort_by == 'date' ? 'desc' : $sort_type;

        if ($sort_type) {
            $data['sort_type'] = $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
            $rlSmarty->assign_by_ref('sort_type', $sort_type);
        }

        // get current search form
        $rlSearch->getFields($form_key ? $form_key : $post_form_key, $listing_type_key, $tab_form);

        if (!$rlSearch->fields) {
            $rlSearch->fields = $sorting;
        }

        $rlSearch->fields = array_merge($date_field, $rlSearch->fields);
        $rlSmarty->assign('fields_list', $sorting);

        // in category search mode
        if (strpos($form_key, 'in_category_') === 0) {
            $in_category_search_results = true;

            // change refine block name
            $blocks['ltsb_' . $listing_type_key]['name'] = $lang['search_forms+name+' . $form_key];

            // simulate category search
            $data['Category_ID'] = $data['Category_ID'] ?: $category['ID'];
            $rlSearch->fields['Category_ID'] = array(
                'Key'  => 'Category_ID',
                'Type' => 'select',
            );

            // build category bread crumbs
            $reefless->loadClass('Categories');
            $rlCategories->buildCategoryBreadCrumbs($bread_crumbs, $category['ID'], $listing_type);

            // enable "in category search" for template
            $rlSmarty->assign('in_category_search', true);
        }

        // get listings
        $listings = $rlSearch->search($data, $listing_type_key, $pInfo['current'], $config['listings_per_page']);
        $rlSmarty->assign_by_ref('listings', $listings);

        $pInfo['calc'] = $rlSearch->calc;
        $rlSmarty->assign_by_ref('pInfo', $pInfo);

        if ($listings) {
            $page_info['name'] = $page_info['h1'] = str_replace('{number}', $pInfo['calc'], $lang['listings_found']);
        } else {
            $add_page_key = $listing_type['Add_page'] && $pages['al_' . $listing_type['Key']]
            ? 'al_' . $listing_type['Key']
            : 'add_listing';

            $add_listing_link = $reefless->getPageUrl($add_page_key);
            $rlSmarty->assign('add_listing_link', $add_listing_link);

            if ($in_category_search_results) {
                $page_info['name'] = $category['name'];
            }
        }

        // do 301 redirect to the first page if no listings found for requested page
        if (!$listings && $pInfo['current'] > 1) {
            $reefless->redirect(null, $reefless->getPageUrl($listing_type['Page_key'], array($search_results_url)), 301);
        }

        $rlHook->load('searchMiddle');

        // Generate title and the latest bread crumbs item
        $page_info['title'] = str_replace('{field}', $sorting[$data['sort_by']]['name'], $lang['search_results_sorting_mode']);

        $bread_crumbs[] = array(
            'title' => $page_info['title'],
            'name'  => $lang['search_results'],
        );

        // get refine search form
        if (!$form = $rlSearch->buildSearch($form_key)) {
            // get quick search form if advanced is empty
            $form_key = str_replace('_advanced', '_quick', $form_key);
            $form = $rlSearch->buildSearch($form_key);
        }
        $rlSmarty->assign_by_ref('refine_search_form', $form);

        // save current page number
        if ($_GET['pg']) {
            $_SESSION[$listing_type['Key'] . '_pageNum'] = (int) $_GET['pg'];
        } else {
            unset($_SESSION[$listing_type['Key'] . '_pageNum']);
        }

        // unset refine search box on this page
        if ($blocks['ltpb_' . $listing_type_key]) {
            unset($blocks['ltpb_' . $listing_type_key]);
            $rlCommon->defineBlocksExist($blocks);
        }

        $reefless->eraseCookie('checkAlert');
    }
    // advanced search form build mode
    elseif (($_GET['nvar_1'] == $advanced_search_url || isset($_GET[$advanced_search_url])) && $listing_type['Advanced_search']) {
        // order field values imulation
        if ($_SESSION[$listing_type_key . '_sort_by']) {
            $_REQUEST['f']['sort_by'] = $_SESSION[$listing_type_key . '_sort_by'];
            $_REQUEST['f']['sort_type'] = $_SESSION[$listing_type_key . '_sort_type'];
        }

        $rlSmarty->assign('advanced_search', true);
        $form_key = $listing_type['Key'] . '_advanced';

        // get search forms
        $form = $rlSearch->buildSearch($form_key);
        $rlSmarty->assign_by_ref('search_form', $form);

        // get current search form
        $rlSearch->getFields($form_key, $listing_type_key);
        $rlSmarty->assign_by_ref('fields_list', $rlSearch->fields);

        // add bread crumbs item
        $bread_crumbs[] = array(
            'name' => $lang['advanced_search'],
        );

        $page_info['name'] = $lang['advanced_search'];

        $redefine_blocks = false;

        // unset refine search box on this page
        if ($blocks['ltsb_' . $listing_type_key]) {
            unset($blocks['ltsb_' . $listing_type_key]);
            $redefine_blocks = true;
        }

        // unset search box on this page
        if ($blocks['ltpb_' . $listing_type_key]) {
            unset($blocks['ltpb_' . $listing_type_key]);
            $redefine_blocks = true;
        }

        if ($redefine_blocks) {
            $rlCommon->defineBlocksExist($blocks);
        }

        $reefless->eraseCookie('checkAlert');
    }
    // browse/quick search mode
    else {
        $category = Category::getCurrentCategory();

        if ($category) {
            $rlSearch->buildInCategorySidebarForm($category);
            $rlListings->originalUrlRedirect('category', $category);
        }

        if (!$rlSmarty->get_template_vars('in_category_search') && $blocks['ltpb_' . $listing_type['Key']]) {
            if ($listing_type['Search_type']) {
                $rlSearch->getSideBarSearchForm();
            } else {
                unset($blocks['ltpb_' . $listing_type['Key']]);
                $rlCommon->defineBlocksExist($blocks);
            }
        }

        $rlHook->load('phpListingTypeBrowseQuickSearchMode');

        if ((($_GET['rlVareables'] && strlen($_GET['rlVareables']) > 2) || $_GET['category']) && !$category) {
            $sError = true;
        }

        $category['ID'] = empty($category) ? 0 : $category['ID'];
        $rlSmarty->assign_by_ref('category', $category);

        // get current category children
        if ($listing_type['Cat_position'] != 'hide' && $blocks['ltcategories_' . $listing_type_key]) {
            $reefless->loadClass('Categories');
            $categories = $rlCategories->getCategories($category['ID'], $listing_type_key, false, $listing_type['Cat_show_subcats']);
            $rlSmarty->assign_by_ref('categories', $categories);
        }

        // unset refine search box on this page
        if ($blocks['ltsb_' . $listing_type_key]) {
            unset($blocks['ltsb_' . $listing_type_key]);
            $rlCommon->defineBlocksExist($blocks);
        }

        if (is_numeric($category['ID'])) {
            // clear search cache
            unset($_SESSION[$listing_type['Key'] . '_post'], $_SESSION['keyword_search_data']);

            // get sorting form fields
            $alt_category_id = $category['ID'] <= 0 && $listing_type['Cat_general_cat']
            ? $listing_type['Cat_general_cat']
            : $category['ID'];

            if ($alt_category_id) {
                if (!$sorting = $rlListings->getFormFields($alt_category_id, 'sorting_forms', $listing_type_key)) {
                    $sorting = $rlListings->getFormFields($alt_category_id, 'short_forms', $listing_type_key);
                }
            } else {
                // get fields from quick search form
                if ($quick_search_form = $rlSearch->buildSearch($listing_type_key . '_quick')) {
                    foreach ($quick_search_form as $group) {
                        $g_fields = $group['Fields'];
                        $field_info = isset($g_fields[0]) && is_array($g_fields[0]) ? $g_fields[0] : array();

                        if ($field_info) {
                            unset($field_info['Values']);

                            $sorting[$field_info['Key']] = $field_info;
                            $sorting[$field_info['Key']]['name'] = $lang[$field_info['pName']];
                        }
                    }
                }
            }

            $sorting = is_array($sorting) ? array_merge($date_field, $sorting) : $date_field;
            unset($sorting['keyword_search']);
            $rlSmarty->assign_by_ref('sorting', $sorting);

            // define sort field
            $sort_by = empty($_GET['sort_by']) ? $_SESSION['browse_sort_by'] : $_GET['sort_by'];
            $sort_by = $sort_by ? $sort_by : 'date';
            if (!empty($sorting[$sort_by])) {
                $order_field = $sorting[$sort_by]['Key'];
            }

            $_SESSION['browse_sort_by'] = $sort_by;
            $rlSmarty->assign_by_ref('sort_by', $sort_by);

            // define sort type
            $sort_type = empty($_GET['sort_type']) ? $_SESSION['browse_sort_type'] : $_GET['sort_type'];
            $sort_type = !$sort_type && $sort_by == 'date' ? 'desc' : $sort_type;
            $_SESSION['browse_sort_type'] = $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
            $rlSmarty->assign_by_ref('sort_type', $sort_type);

            $pInfo['current'] = (int) $_GET['pg'];

            // get listings
            $category_id = $listing_type['Cat_single_ID'] ?: $category['ID'];
            $listings = $rlListings->getListings($category_id, $order_field, $sort_type, $pInfo['current'], $config['listings_per_page'], $listing_type['Key']);
            $rlSmarty->assign_by_ref('listings', $listings);

            // do 301 redirect to the first page if no listings found for requested page
            if (!$listings && $pInfo['current'] > 1) {
                $reefless->redirect(null, $reefless->getPageUrl($listing_type['Page_key']), 301);
            } elseif ($listings && $pInfo['current'] > 1) {
                if ($category['ID']) {
                    $url = $reefless->getCategoryUrl($category);
                } else {
                    $url = $reefless->getPageUrl($listing_type['Page_key']);
                }

                $page_info['canonical'] = $url;
            }

            $pInfo['calc'] = $rlListings->calc;
            $rlSmarty->assign_by_ref('pInfo', $pInfo);

            $rlHook->load('browseMiddle');

            if (!empty($listings)) {
                if ($category['ID'] > 0) {
                    // build rss
                    $rss = array(
                        'item'  => 'category',
                        'id'    => $category['ID'],
                        'title' => &$page_info['title'],
                    );

                    // enable print page
                    if ($pages['print']) {
                        $print = array(
                            'item' => 'browse',
                            'id'   => $category['ID'],
                        );
                    }
                } else {
                    // build rss
                    $rss = array(
                        'item'         => 'category',
                        'listing_type' => $listing_type['Key'],
                        'title'        => &$page_info['title'],
                    );

                    // enable print page
                    if ($pages['print']) {
                        $print = array(
                            'item'         => 'browse',
                            'listing_type' => $listing_type['Key'],
                        );
                    }
                }

                $rlSmarty->assign_by_ref('rss', $rss);
                $rlSmarty->assign_by_ref('print', $print);
            }

            // Add navigation icon
            if (!$category['Lock'] && !$listing_type['Admin_only']) {
                $add_page_key = $listing_type['Add_page'] && $pages['al_' . $listing_type['Key']]
                ? 'al_' . $listing_type['Key']
                : 'add_listing';

                if ($category['ID'] > 0) {
                    $add_listing_data = $config['mod_rewrite']
                    ? ['path' => $category['Path']]
                    : ['id' => $category['ID']];
                    $add_listing_data['step'] = $steps['plan']['path'];
                    $add_listing_link = $reefless->getPageUrl($add_page_key, $add_listing_data);

                    if ($tpl_settings['browse_add_listing_icon']) {
                        $navIcons[] = '<a class="post_ad" title="' . str_replace('{category}', $category['name'], $lang['add_listing_to']) . '" href="' . $add_listing_link . '"><span></span></a>';
                    }
                } else {
                    $add_listing_link = $reefless->getPageUrl($add_page_key);

                    if ($tpl_settings['browse_add_listing_icon']) {
                        $navIcons[] = '<a class="post_ad" title="' . $lang['add_listing'] . '" href="' . $add_listing_link . '"><span></span></a>';
                    }
                }

                $rlSmarty->assign('add_listing_link', $add_listing_link);
            }

            // build category bread crumbs
            $reefless->loadClass('Categories');
            $rlCategories->buildCategoryBreadCrumbs($bread_crumbs, $category['Parent_ID'], $listing_type);

            if ($category['ID'] > 0) {
                // set meta data
                $page_info['meta_description'] = $category['meta_description'] ?: $page_info['meta_description'];
                $page_info['meta_keywords']    = $category['meta_keywords'] ?: $page_info['meta_keywords'];
                $page_info['h1']               = $category['h1'] ?: $category['name'];
                $page_info['name']             = $category['name'];
                $page_info['title']            = $category['title'] ?: $category['name'];

                // add bread crumbs item
                $bread_crumbs[] = array(
                    'title'    => $page_info['title'],
                    'name'     => $page_info['name'],
                    'category' => true,
                );
            }

            if ($navIcons) {
                $rlSmarty->assign('navIcons', $navIcons);
            }

            $rlHook->load('browseBCArea');
        }
    }
}
