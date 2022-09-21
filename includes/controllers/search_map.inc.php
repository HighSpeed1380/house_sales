<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SEARCH_MAP.INC.PHP
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

$rlStatic->addHeaderCss(RL_TPL_BASE . 'controllers/search_map/search_map.css', $page_info['Controller']);

$reefless->loadClass('Search');

// get search forms
foreach ($rlListingTypes->types as $type_key => $listing_type) {
    if ($listing_type['On_map_search']) {
        if ($search_form = $rlSearch->buildSearch($type_key . '_on_map')) {
            // Remove address fields from the form
            foreach ($search_form as $key => $field) {
                if (is_numeric(strpos($field['Fields'][0]['Key'], 'address'))) {
                    unset($search_form[$key]);
                    break;
                }
            }

            $form_key = $type_key . '_on_map';
            $out_search_forms[$form_key]['data'] = $search_form;
            $out_search_forms[$form_key]['name'] = $lang['search_forms+name+'.$form_key];
            $out_search_forms[$form_key]['listing_type'] = $type_key;
        }
    }

    unset($search_form);
}

// prepare post/get
if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && is_array($_POST['f'])) {
    $form_data = $_POST['f'];
    unset($_POST['f']);
    $_POST = is_array($_POST) ? array_merge($_POST, $form_data) : $form_data;
} elseif (strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' && is_array($_GET['f'])) {
    $form_data = $_GET['f'];
    unset($_GET['f']);
    $_GET = is_array($_GET) ? array_merge($_GET, $form_data) : $form_data;
}

$rlSmarty->assign_by_ref('search_forms', $out_search_forms);

// Disable sidebar blocks
$blocks['left'] = false;

$rlSearch->defaultMapAddressAssign();
