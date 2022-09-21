<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_TYPES.INC.PHP
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

/* ext js action */
if ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    /* date update */
    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtListingTypesUpdate');

        $rlActions->updateOne($updateData, 'listing_types');

        $type_key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'listing_types');

        if ($field == 'Status') {
            $reefless->loadClass('ListingTypes');
            $rlListingTypes->activateComponents($type_key, $value);

            /* update listing statistics */
            $rlListingTypes->get();
            $rlCache->updateStatistics();
        } elseif ($field == 'Admin_only') {
            $reefless->loadClass('ListingTypes');
            $rlListingTypes->adminOnly($type_key, $value ? 'trash' : 'active');
        }
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}listing_types` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('listing_types+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtListingTypesSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Admin_only'] = $data[$key]['Admin_only'] ? $lang['yes'] : $lang['no'];
    }

    $rlHook->load('apExtListingTypesData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpListingTypesTop');

    $reefless->loadClass('Categories');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_type'] : $lang['edit_type'];
    } else {
        $rlXajax->registerFunction(array('prepareDeleting', $rlListingTypes, 'ajaxPrepareDeleting'));
        $rlXajax->registerFunction(array('deleteListingType', $rlListingTypes, 'ajaxDeletingType'));
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get pages list */
        $pages = $rlDb->fetch(array('ID', 'Key'), array('Tpl' => 1), "AND `Status` = 'active' ORDER BY `Key`", null, 'pages');
        $pages = $rlLang->replaceLangKeys($pages, 'pages', array('name'), RL_LANG_CODE, 'admin');
        $rlSmarty->assign_by_ref('pages', $pages);

        /* get available fields for split */
        $sql = "SELECT DISTINCT `T1`.`Key`, `T1`.`Type`, IF(`T1`.`Type` = 'bool', '1,0', `Values`) AS `Values` FROM `{db_prefix}listing_fields` AS `T1` ";
        $sql .= "WHERE `T1`.`Type` IN ('radio', 'select', 'bool', 'checkbox') AND `T1`.`Status` = 'active' ";
        $sql .= "AND `T1`.`Key` <> 'year' AND `T1`.`Condition` = '' AND `T1`.`ID` > 0 ";
        $sql .= "AND ( ";
        $sql .= "(( LENGTH(`T1`.`Values`) - LENGTH(REPLACE(`T1`.`Values`, ',', '')) + 1) BETWEEN 2 AND 3 AND `T1`.`Values` <> '') OR ";
        $sql .= "`T1`.`Type` = 'bool' ";
        $sql .= ") ";
        $fields = $rlDb->getAll($sql);
        $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', 'name');
        foreach ($fields as $fField) {
            $tmpFields[$fField['Key']] = $fField;
        }
        $fields = $tmpFields;
        unset($tmpFields);

        $rlSmarty->assign_by_ref('fields', $fields);

        /* assign category order types */
        $category_order_types = array(
            array(
                'name' => $lang['order'],
                'key'  => 'position',
            ),
            array(
                'name' => $lang['alphabetic'],
                'key'  => 'alphabetic',
            ),
        );
        $rlSmarty->assign_by_ref('category_order_types', $category_order_types);

        /* assign search form types */
        $search_form_types = array(
            array(
                'name' => $lang['content_and_block'],
                'key'  => 'content_and_block',
            ),
            array(
                'name' => $lang['block_only'],
                'key'  => 'block_only',
            ),
        );
        $rlSmarty->assign_by_ref('search_form_types', $search_form_types);

        /* assign refine search types */
        $refine_search_types = array(
            array(
                'name' => 'POST',
                'key'  => 'post',
            ),
            array(
                'name' => 'GET',
                'key'  => 'get',
            ),
        );
        $rlSmarty->assign_by_ref('refine_search_types', $refine_search_types);

        // Remove banner positions
        unset($l_block_sides['header_banner'], $l_block_sides['integrated_banner']);

        /* assign cat positions */
        $c_block_sides = array_merge(array('hide' => $lang['hide']), $l_block_sides);
        $rlSmarty->assign_by_ref('cat_positions', $c_block_sides);

        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $p_key = $rlValid->xSql($_GET['key']);

        // get current listing type info
        if ($p_key) {
            $type_info = $rlDb->fetch('*', array('Key' => $p_key), null, null, 'listing_types', 'row');
        }

        if ($_GET['action'] == 'edit') {
            $rlSmarty->assign('cpTitle', $lang['listing_types+name+' . $type_info['Key']]);
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['key'] = $type_info['Key'];
            $_POST['add_page'] = $type_info['Add_page'];
            $_POST['photo'] = $type_info['Photo'];
            $_POST['photo_required'] = $type_info['Photo_required'];
            $_POST['video'] = $type_info['Video'];
            $_POST['statistics'] = $type_info['Statistics'];
            $_POST['admin'] = $type_info['Admin_only'];
            $_POST['show_cents'] = $type_info['Show_cents'];
            $_POST['links_type'] = $type_info['Links_type'];
            $_POST['color'] = $type_info['Color'];
            $_POST['general_cat'] = $type_info['Cat_general_cat'];
            $_POST['cat_position'] = $type_info['Cat_position'];
            $_POST['display_counter'] = $type_info['Cat_listing_counter'];
            $_POST['cat_hide_empty'] = $type_info['Cat_hide_empty'];
            $_POST['html_postfix'] = $type_info['Cat_postfix'];
            $_POST['category_order'] = $type_info['Cat_order_type'];
            $_POST['allow_subcategories'] = $type_info['Cat_custom_adding'];
            $_POST['display_subcategories'] = $type_info['Cat_show_subcats'];
            $_POST['ablock_pages'] = explode(',', $type_info['Ablock_pages']);
            $_POST['ablock_position'] = $type_info['Ablock_position'];
            $_POST['ablock_visible_number'] = $type_info['Ablock_visible_number'];
            $_POST['ablock_display_subcategories'] = $type_info['Ablock_show_subcats'];
            $_POST['ablock_subcategories_number'] = $type_info['Ablock_subcat_number'];
            $_POST['ablock_scrolling_in_box'] = $type_info['Ablock_scrolling'];
            $_POST['search_form'] = $type_info['Search'];
            $_POST['search_home'] = $type_info['Search_home'];
            $_POST['search_page'] = $type_info['Search_page'];
            $_POST['search_type'] = $type_info['Search_type'];
            $_POST['search_account'] = $type_info['Search_account'];
            $_POST['advanced_search'] = $type_info['Advanced_search'];
            $_POST['on_map_search'] = $type_info['On_map_search'];
            $_POST['myads_search'] = $type_info['Myads_search'];
            $_POST['refine_search_type'] = $type_info['Submit_method'];
            $_POST['featured_blocks'] = $type_info['Featured_blocks'];
            $_POST['arrange_field'] = $type_info['Arrange_field'];
            $_POST['is_arrange_search'] = $type_info['Arrange_search'];
            $_POST['is_arrange_featured'] = $type_info['Arrange_featured'];
            $_POST['search_multi_categories'] = $type_info['Search_multi_categories'];
            $_POST['search_multicat_levels'] = $type_info['Search_multicat_levels'];
            $_POST['search_multicat_phrases'] = $type_info['Search_multicat_phrases'];
            $_POST['status'] = $type_info['Status'];

            // get names
            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'listing_types+name+' . $p_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $pKey => $pVal) {
                $_POST['name'][$names[$pKey]['Code']] = $names[$pKey]['Value'];
            }

            // simulate arrange values
            $rlListingTypes->simulate($type_info['Arrange_field']);

            // simulate multicategory levels phrases
            if ($type_info['Search_multicat_phrases']) {
                $rlListingTypes->simulateMultiCategoryLevel($p_key, $allLangs, $type_info['Search_multicat_levels']);
            }

            $rlHook->load('apPhpListingTypesPost');
        }

        if (isset($_POST['submit'])) {
            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $_POST['key'];

            /* check key exist (in add mode only) */
            if ($_GET['action'] == 'add') {
                /* check key */
                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }
                $f_key = $rlValid->str2key($f_key);

                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(
                    array('Key', 'Status'),
                    array('Key' => $f_key),
                    null,
                    null,
                    'listing_types',
                    'row'
                );

                if (!$exist_key) {
                    $exist_key = $rlDb->fetch(
                        array('Key', 'Status'),
                        array('Key' => $f_key),
                        null,
                        null,
                        'categories',
                        'row'
                    );
                }

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_key_exist']);

                    if ($exist_key['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields   = array();
                    $error_fields[] = 'key';
                }
            }

            if ($_POST['links_type'] == 'subdomain' && $config['mf_geo_subdomains']) {
                $errors[] = $lang['lt_subdomain_denied'];
                $error_fields[] = 'links_type';
            }

            /* check name */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$allLangs[$lkey]['Code']}]";
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            if (!empty($_POST['ablock_pages']) && empty($_POST['ablock_position'])) {
                $errors[] = str_replace('{field}', "<b>" . $lang['position'] . "</b>", $lang['notice_field_empty']);
                $error_fields[] = 'ablock_position';
            }

            $rlHook->load('apPhpListingTypesValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                $myads_search = intval($_POST['myads_search']);
                $on_map_search = intval($_POST['on_map_search']);

                // disable individual add listing page if lt is admin only
                if ((int) $_POST['admin']) {
                    $_POST['add_page'] = "0";
                }

                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $order = $rlDb->getRow("SELECT MAX(`Order`) AS `max` FROM `{db_prefix}listing_types`");

                    // write main listing type information
                    $data = array(
                        'Key'                     => $f_key,
                        'Order'                   => $order['max'] + 1,
                        'Add_page'                => (int) $_POST['add_page'],
                        'Photo'                   => (int) $_POST['photo'],
                        'Photo_required'          => (int) $_POST['photo'] ? (int) $_POST['photo_required'] : 0,
                        'Video'                   => (int) $_POST['video'],
                        'Statistics'              => (int) $_POST['statistics'],
                        'Admin_only'              => (int) $_POST['admin'],
                        'Show_cents'              => (int) $_POST['show_cents'],
                        'Links_type'              => $_POST['links_type'],
                        'Cat_general_cat'         => (int) $_POST['general_cat'],
                        'Cat_position'            => $_POST['cat_position'],
                        'Cat_listing_counter'     => (int) $_POST['display_counter'],
                        'Cat_hide_empty'          => (int) $_POST['cat_hide_empty'],
                        'Cat_postfix'             => (int) $_POST['html_postfix'],
                        'Cat_order_type'          => $_POST['category_order'],
                        'Cat_custom_adding'       => (int) $_POST['allow_subcategories'],
                        'Cat_show_subcats'        => (int) $_POST['display_subcategories'],
                        'Ablock_pages'            => $_POST['ablock_pages'] ? implode(',', $_POST['ablock_pages']) : '',
                        'Ablock_position'         => $_POST['ablock_position'],
                        'Ablock_visible_number'   => (int) $_POST['ablock_visible_number'],
                        'Ablock_show_subcats'     => (int) $_POST['ablock_display_subcategories'],
                        'Ablock_subcat_number'    => (int) $_POST['ablock_subcategories_number'],
                        'Ablock_scrolling'        => (int) $_POST['ablock_scrolling_in_box'],
                        'Search'                  => (int) $_POST['search_form'],
                        'Search_home'             => (int) $_POST['search_home'],
                        'Search_page'             => (int) $_POST['search_page'],
                        'Search_type'             => (int) $_POST['search_type'],
                        'Search_account'             => (int) $_POST['search_account'],
                        'Advanced_search'         => (int) $_POST['advanced_search'],
                        'On_map_search'           => $on_map_search,
                        'Myads_search'            => $myads_search,
                        'Submit_method'           => $_POST['refine_search_type'],
                        'Featured_blocks'         => (int) $_POST['featured_blocks'],
                        'Arrange_field'           => $_POST['arrange_field'],
                        'Arrange_values'          => $fields[$_POST['arrange_field']]['Values'],
                        'Arrange_search'          => (int) $_POST['is_arrange_search'],
                        'Arrange_featured'        => (int) $_POST['is_arrange_featured'],
                        'Search_multi_categories' => (int) $_POST['search_multi_categories'],
                        'Search_multicat_levels'  => (int) $_POST['search_multicat_levels'],
                        'Search_multicat_phrases' => (int) $_POST['search_multicat_phrases'],
                        'Status'                  => $_POST['status'],
                    );

                    $update_cache_key = $f_key;

                    $rlHook->load('apPhpListingTypesBeforeAdd');

                    if ($tpl_settings['listing_type_color']) {
                        $data['Color'] = $_POST['color'];
                    }

                    if ($action = $rlActions->insertOne($data, 'listing_types')) {
                        $rlHook->load('apPhpListingTypesAfterAdd');

                        // add enum option to search form table
                        $rlActions->enumAdd('search_forms', 'Type', $f_key);
                        $rlActions->enumAdd('categories', 'Type', $f_key);
                        $rlActions->enumAdd('account_types', 'Abilities', $f_key);
                        $rlActions->enumAdd('saved_search', 'Listing_type', $f_key);

                        // allow all account types to add listing to new listing type
                        $rlDb->query("UPDATE `{db_prefix}account_types` SET `Abilities` = TRIM(BOTH ',' FROM CONCAT(`Abilities`, ',{$f_key}'))");

                        // write name's phrases
                        foreach ($allLangs as $key => $value) {
                            // listing type phrases
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'listing_types+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // individual page names
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+name+lt_' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // individual page titles
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+title+lt_' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // individual add listing page phrases
                            if ($_POST['add_page']) {
                                // individual page names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+name+al_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['add_listing_name_pattern']),
                                );

                                // individual page titles
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+title+al_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['add_listing_name_pattern']),
                                );
                            }

                            // my listing page phrases
                            if (!(int) $_POST['admin']) {
                                // my listings page names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+name+my_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['my_listings_pattern']),
                                );

                                // my listings page titles
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+title+my_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['my_listings_pattern']),
                                );
                            }

                            if (!empty($_POST['ablock_pages']) /*&& in_array($_POST['ablock_position'], array('top', 'bottom'))*/) {
                                // category block names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'blocks+name+ltcb_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['categories_block_pattern']),
                                );
                            }

                            if ($_POST['featured_blocks']) {
                                // featured listings block names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'blocks+name+ltfb_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['featured_block_pattern']),
                                );
                            }

                            if (!empty($_POST['search_form'])) {
                                // category search form names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'search_forms+name+' . $f_key . '_quick',
                                    'Value'  => $f_name[$allLangs[$key]['Code']],
                                );
                            }

                            if (!empty($_POST['advanced_search'])) {
                                // category search form names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'search_forms+name+' . $f_key . '_advanced',
                                    'Value'  => $f_name[$allLangs[$key]['Code']],
                                );
                            }

                            if ($on_map_search) {
                                // on map search form names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'search_forms+name+' . $f_key . '_on_map',
                                    'Value'  => $f_name[$allLangs[$key]['Code']],
                                );
                            }

                            if ($myads_search) {
                                // my ads search form names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'search_forms+name+' . $f_key . '_myads',
                                    'Value'  => $f_name[$allLangs[$key]['Code']],
                                );
                            }

                            if ($_POST['search_form']) {
                                // create search block names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'blocks+name+ltsb_' . $f_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['refine_search_pattern']),
                                );
                            }
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        // create individual page
                        $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                        $individual_page = array(
                            'Parent_ID'  => 0,
                            'Page_type'  => 'system',
                            'Login'      => 0,
                            'Key'        => 'lt_' . $f_key,
                            'Position'   => $page_position['max'] + 1,
                            'Path'       => $rlValid->str2path($f_key),
                            'Controller' => 'listing_type',
                            'Tpl'        => 1,
                            'Menus'      => 1,
                            'Modified'   => 'NOW()',
                            'Status'     => 'active',
                            'Readonly'   => 1,
                        );
                        $rlActions->insertOne($individual_page, 'pages');

                        $page_id = $rlDb->insertID();

                        // creat individual add listing page
                        if ($_POST['add_page']) {
                            $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                            $individual_page = array(
                                'Parent_ID'  => 0,
                                'Page_type'  => 'system',
                                'Login'      => 0,
                                'Key'        => 'al_' . $f_key,
                                'Position'   => $page_position['max'] + 1,
                                'Path'       => $rlValid->str2path(str_replace('{type}', $f_key, $lang['add_listing_path_pattern'])),
                                'Controller' => 'add_listing',
                                'Tpl'        => 1,
                                'Menus'      => 1,
                                'Modified'   => 'NOW()',
                                'Status'     => 'active',
                                'Readonly'   => 1,
                            );
                            $rlActions->insertOne($individual_page, 'pages');
                        }

                        $my_page_id = 0;

                        // create my listings page
                        if (!(int) $_POST['admin']) {
                            $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                            $my_page = array(
                                'Parent_ID'  => 0,
                                'Page_type'  => 'system',
                                'Login'      => 1,
                                'Key'        => 'my_' . $f_key,
                                'Position'   => $page_position['max'] + 1,
                                'Path'       => 'my-' . $rlValid->str2path($f_key),
                                'Controller' => 'my_listings',
                                'Tpl'        => 1,
                                'Menus'      => 2,
                                'Modified'   => 'NOW()',
                                'Status'     => $config['one_my_listings_page'] ? 'trash' : 'active',
                                'Readonly'   => 1,
                            );
                            $rlActions->insertOne($my_page, 'pages');

                            $my_page_id = $rlActions->insertID();
                        }

                        // create quick search form
                        if (!empty($_POST['search_form'])) {
                            $search_form = array(
                                'Key'      => $f_key . '_quick',
                                'Type'     => $f_key,
                                'Mode'     => 'quick',
                                'Groups'   => 0,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($search_form, 'search_forms');
                        }

                        // create advanced search form
                        if (!empty($_POST['advanced_search'])) {
                            $search_form = array(
                                'Key'      => $f_key . '_advanced',
                                'Type'     => $f_key,
                                'Mode'     => 'advanced',
                                'Groups'   => 1,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($search_form, 'search_forms');
                        }

                        // Create on map search form
                        if ($on_map_search) {
                            $on_map_form = array(
                                'Key'      => $f_key . '_on_map',
                                'Type'     => $f_key,
                                'Mode'     => 'on_map',
                                'Groups'   => 0,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlDb->insertOne($on_map_form, 'search_forms');
                        }

                        // create my ads search form
                        if ($myads_search) {
                            $myads_form = array(
                                'Key'      => $f_key . '_myads',
                                'Type'     => $f_key,
                                'Mode'     => 'myads',
                                'Groups'   => 0,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($myads_form, 'search_forms');
                        }

                        // create additional categories block
                        if (!empty($_POST['ablock_pages'])) {
                            $cat_block_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                            $category_block = array(
                                'Page_ID'  => implode(',', $_POST['ablock_pages']),
                                'Sticky'   => 0,
                                'Key'      => 'ltcb_' . $f_key,
                                'Position' => $cat_block_position['max'] + 1,
                                'Side'     => $_POST['ablock_position'],
                                'Type'     => 'smarty',
                                'Content'  => $f_key,
                                'Tpl'      => 1,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($category_block, 'blocks');
                        }

                        // create featured block
                        if ($_POST['featured_blocks']) {
                            $f_block_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                            $featured_block = array(
                                'Page_ID'  => $page_id ? $page_id : 1,
                                'Sticky'   => 0,
                                'Key'      => 'ltfb_' . $f_key,
                                'Position' => $f_block_position['max'] + 1,
                                'Side'     => 'left',
                                'Type'     => 'smarty',
                                'Content'  => '{include file=\'blocks\'|cat:$smarty.const.RL_DS|cat:\'featured.tpl\' listings=$featured_' . $f_key . ' type=\'' . $f_key . '\'}',
                                'Tpl'      => 1,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($featured_block, 'blocks');
                        }

                        // create search block
                        if ($_POST['search_form']) {
                            $s_block_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                            $search_block = array(
                                'Page_ID'  => $page_id ? $page_id : 1,
                                'Sticky'   => 0,
                                'Key'      => 'ltsb_' . $f_key,
                                'Position' => $f_block_position['max'] + 1,
                                'Side'     => 'left',
                                'Type'     => 'smarty',
                                'Content'  => '{include file=$refine_block_controller}',
                                'Tpl'      => 1,
                                'Status'   => 'active',
                                'Readonly' => 1,
                            );
                            $rlActions->insertOne($search_block, 'blocks');
                        }

                        // arrange type
                        $rlListingTypes->arrange($_POST['arrange_field']);

                        $message = $lang['listing_type_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new lisitng type (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new lisitng type (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Add_page'                => (int) $_POST['add_page'],
                            'Photo'                   => (int) $_POST['photo'],
                            'Photo_required'          => (int) $_POST['photo'] ? (int) $_POST['photo_required'] : 0,
                            'Video'                   => (int) $_POST['video'],
                            'Statistics'              => (int) $_POST['statistics'],
                            'Admin_only'              => (int) $_POST['admin'],
                            'Show_cents'              => (int) $_POST['show_cents'],
                            'Links_type'              => $_POST['links_type'],
                            'Cat_general_cat'         => (int) $_POST['general_cat'],
                            'Cat_position'            => $_POST['cat_position'],
                            'Cat_listing_counter'     => (int) $_POST['display_counter'],
                            'Cat_hide_empty'          => (int) $_POST['cat_hide_empty'],
                            'Cat_postfix'             => (int) $_POST['html_postfix'],
                            'Cat_order_type'          => $_POST['category_order'],
                            'Cat_custom_adding'       => (int) $_POST['allow_subcategories'],
                            'Cat_show_subcats'        => (int) $_POST['display_subcategories'],
                            'Ablock_pages'            => $_POST['ablock_pages'] ? implode(',', $_POST['ablock_pages']) : '',
                            'Ablock_position'         => $_POST['ablock_position'],
                            'Ablock_visible_number'   => (int) $_POST['ablock_visible_number'],
                            'Ablock_show_subcats'     => (int) $_POST['ablock_display_subcategories'],
                            'Ablock_subcat_number'    => (int) $_POST['ablock_subcategories_number'],
                            'Ablock_scrolling'        => (int) $_POST['ablock_scrolling_in_box'],
                            'Search'                  => (int) $_POST['search_form'],
                            'Search_home'             => (int) $_POST['search_home'],
                            'Search_page'             => (int) $_POST['search_page'],
                            'Search_type'             => (int) $_POST['search_type'],
                            'Search_account'             => (int) $_POST['search_account'],
                            'Advanced_search'         => (int) $_POST['advanced_search'],
                            'On_map_search'           => $on_map_search,
                            'Myads_search'            => $myads_search,
                            'Submit_method'           => $_POST['refine_search_type'],
                            'Featured_blocks'         => (int) $_POST['featured_blocks'],
                            'Arrange_field'           => $_POST['arrange_field'],
                            'Arrange_values'          => $fields[$_POST['arrange_field']]['Values'],
                            'Arrange_search'          => (int) $_POST['is_arrange_search'],
                            'Arrange_featured'        => (int) $_POST['is_arrange_featured'],
                            'Search_multi_categories' => (int) $_POST['search_multi_categories'],
                            'Search_multicat_levels'  => (int) $_POST['search_multicat_levels'],
                            'Search_multicat_phrases' => (int) $_POST['search_multicat_phrases'],
                            'Status'                  => $_POST['status'],
                        ),
                        'where'  => array('Key' => $p_key),
                    );

                    //set cat general only field to 1 if there is no other than general category relations, set 0 otherwise
                    $sql = "SELECT * FROM `{db_prefix}listing_relations` AS `T1` ";
                    $sql .= "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
                    $sql .= "WHERE `T2`.`Type` = '{$p_key}' AND `T1`.`Category_ID` != '" . $update_date['fields']['Cat_general_cat'] . "' ";
                    $update_date['fields']['Cat_general_only'] = $rlDb->getRow($sql) ? '0' : '1';

                    $update_cache_key = $p_key;

                    $rlHook->load('apPhpListingTypesBeforeEdit');

                    if ($tpl_settings['listing_type_color']) {
                        $update_date['fields']['Color'] = $_POST['color'];
                    }

                    $action = $GLOBALS['rlActions']->updateOne($update_date, 'listing_types');

                    $rlHook->load('apPhpListingTypesAfterEdit');

                    $page_id = $rlDb->getOne('ID', "`Key` = 'lt_{$p_key}'", 'pages');

                    // update additional categories block
                    $cat_block = array(
                        'fields' => array(
                            'Page_ID'    => $_POST['ablock_pages'] ? implode(',', $_POST['ablock_pages']) : '',
                            'Side'       => $_POST['ablock_position'],
                            'Cat_sticky' => $_POST['ablock_pages'] && in_array($page_id, $_POST['ablock_pages']) ? 1 : 0,
                        ),
                        'where'  => array(
                            'Key' => 'ltcb_' . $p_key,
                        ),
                    );
                    $rlActions->updateOne($cat_block, 'blocks');

                    /* change status tracking */
                    if ($_POST['status'] != $type_info['Status']) {
                        $rlListingTypes->activateComponents($p_key, $_POST['status']);
                    }

                    // Individual page tracking
                    // @todo 4.7.0 - This code create/update page of listing type for old websites
                    //              - It can be removed in future
                    if (!$rlDb->getOne('ID', "`Key` = 'lt_{$p_key}'", 'pages')) {
                        // create page
                        $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                        $individual_page = array(
                            'Parent_ID'  => 0,
                            'Page_type'  => 'system',
                            'Login'      => 0,
                            'Key'        => 'lt_' . $p_key,
                            'Position'   => $page_position['max'] + 1,
                            'Path'       => $rlValid->str2path($f_key),
                            'Controller' => 'listing_type',
                            'Tpl'        => 1,
                            'Menus'      => 1,
                            'Modified'   => 'NOW()',
                            'Status'     => 'active',
                            'Readonly'   => 1,
                        );
                        $rlActions->insertOne($individual_page, 'pages');
                        $page_id = $rlDb->insertID();

                        $rlActions->insertOne($category_insert, 'categories');

                        // add phrases
                        foreach ($allLangs as $key => $value) {
                            // individual page names
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+name+lt_' . $p_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // individual page titles
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+title+lt_' . $p_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );
                        }
                        $rlActions->insert($lang_keys, 'lang_keys');
                    }
                    // activate page
                    else {
                        $activate_page = array(
                            'fields' => array(
                                'Status' => 'active',
                            ),
                            'where'  => array(
                                'Key' => 'lt_' . $p_key,
                            ),
                        );
                        $rlActions->updateOne($activate_page, 'pages');
                        $page_id = $rlDb->getOne('ID', "`Key` = 'lt_{$p_key}'", 'pages');

                        // activate phrases
                        $activate_phrases[] = array(
                            'fields' => array(
                                'Status' => 'active',
                            ),
                            'where'  => array(
                                'Key' => 'pages+name+lt_' . $p_key,
                            ),
                        );

                        $activate_phrases[] = array(
                            'fields' => array(
                                'Status' => 'active',
                            ),
                            'where'  => array(
                                'Key' => 'pages+title+lt_' . $p_key,
                            ),
                        );
                        $rlActions->update($activate_phrases, 'lang_keys');
                    }
                    /* individual page tracking end */

                    /* individual add listing page tracking */
                    if ($type_info['Add_page'] && !(int) $_POST['add_page']) {
                        // suspend page
                        $suspend_page = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'al_' . $p_key,
                            ),
                        );
                        $rlActions->updateOne($suspend_page, 'pages');

                        // suspend phrases
                        $suspend_phrases[] = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'pages+name+al_' . $p_key,
                            ),
                        );

                        $suspend_phrases[] = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'pages+title+al_' . $p_key,
                            ),
                        );
                        $rlActions->update($suspend_phrases, 'lang_keys');
                    } else if (!$type_info['Add_page'] && (int) $_POST['add_page']) {
                        if (!$rlDb->getOne('ID', "`Key` = 'al_{$p_key}'", 'pages')) {
                            // create page
                            $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                            $individual_page = array(
                                'Parent_ID'  => 0,
                                'Page_type'  => 'system',
                                'Login'      => 0,
                                'Key'        => 'al_' . $p_key,
                                'Position'   => $page_position['max'] + 1,
                                'Path'       => $rlValid->str2path(str_replace('{type}', $p_key, $lang['add_listing_path_pattern'])),
                                'Controller' => 'add_listing',
                                'Tpl'        => 1,
                                'Menus'      => 1,
                                'Modified'   => 'NOW()',
                                'Status'     => 'active',
                                'Readonly'   => 1,
                            );

                            $rlActions->insertOne($individual_page, 'pages');
                            $page_id = $rlDb->insertID();

                            $rlActions->insertOne($category_insert, 'categories');

                            // add phrases
                            foreach ($allLangs as $key => $value) {
                                // individual page names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+name+al_' . $p_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['add_listing_name_pattern']),
                                );

                                // individual page titles
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+title+al_' . $p_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['add_listing_name_pattern']),
                                );
                            }
                            $rlActions->insert($lang_keys, 'lang_keys');
                        }
                        // activate page
                        else {
                            $activate_page = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'al_' . $p_key,
                                ),
                            );
                            $rlActions->updateOne($activate_page, 'pages');
                            $page_id = $rlDb->getOne('ID', "`Key` = 'al_{$p_key}'", 'pages');

                            // activate phrases
                            $activate_phrases[] = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'pages+name+al_' . $p_key,
                                ),
                            );

                            $activate_phrases[] = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'pages+title+al_' . $p_key,
                                ),
                            );
                            $rlActions->update($activate_phrases, 'lang_keys');
                        }
                    }
                    /* individual add listing page tracking end */

                    /* my listings page tracking */
                    if ($type_info['Admin_only'] != (int) $_POST['admin']) {
                        $rlListingTypes->adminOnly($p_key, (int) $_POST['admin'] ? 'trash' : 'active');
                    }

                    if (!$type_info['Admin_only'] && (int) $_POST['admin']) {
                        // suspend page
                        $suspend_page = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'my_' . $p_key,
                            ),
                        );
                        $rlActions->updateOne($suspend_page, 'pages');

                        // suspend phrases
                        $suspend_phrases[] = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'pages+name+my_' . $p_key,
                            ),
                        );

                        $suspend_phrases[] = array(
                            'fields' => array(
                                'Status' => 'trash',
                            ),
                            'where'  => array(
                                'Key' => 'pages+title+my_' . $p_key,
                            ),
                        );
                        $rlActions->update($suspend_phrases, 'lang_keys');
                    } else if ($type_info['Admin_only'] && !(int) $_POST['admin']) {
                        if (!$rlDb->getOne('ID', "`Key` = 'my_{$p_key}'", 'pages')) {
                            // create page
                            $page_position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                            $my_page = array(
                                'Parent_ID'  => 0,
                                'Page_type'  => 'system',
                                'Login'      => 1,
                                'Key'        => 'my_' . $p_key,
                                'Position'   => $page_position['max'] + 1,
                                'Path'       => 'my-' . $rlValid->str2path($f_key),
                                'Controller' => 'my_listings',
                                'Tpl'        => 1,
                                'Menus'      => 2,
                                'Modified'   => 'NOW()',
                                'Status'     => 'active',
                                'Readonly'   => 1,
                            );
                            $rlActions->insertOne($my_page, 'pages');

                            // add phrases
                            foreach ($allLangs as $key => $value) {
                                // my listings page names
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+name+my_' . $p_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['my_listings_pattern']),
                                );

                                // my listings page titles
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'common',
                                    'Status' => 'active',
                                    'Key'    => 'pages+title+my_' . $p_key,
                                    'Value'  => str_replace('{type}', $f_name[$allLangs[$key]['Code']], $lang['my_listings_pattern']),
                                );
                            }
                            $rlActions->insert($lang_keys, 'lang_keys');
                        }
                        // activate page
                        else {
                            $activate_page = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'my_' . $p_key,
                                ),
                            );
                            $rlActions->updateOne($activate_page, 'pages');

                            // activate phrases
                            $activate_phrases[] = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'pages+name+my_' . $p_key,
                                ),
                            );

                            $activate_phrases[] = array(
                                'fields' => array(
                                    'Status' => 'active',
                                ),
                                'where'  => array(
                                    'Key' => 'pages+title+my_' . $p_key,
                                ),
                            );
                            $rlActions->update($activate_phrases, 'lang_keys');
                        }
                    }
                    /* my listings page tracking end */

                    /* additional categories block tracker */
                    if (empty($type_info['Ablock_pages']) && !empty($_POST['ablock_pages'])) {
                        $rlListingTypes->apBlocksTracker(array(
                            'key'              => $p_key,
                            'prefix'           => 'ltcb_',
                            'page_ids'         => $_POST['ablock_pages'],
                            'Side'             => $_POST['ablock_position'],
                            'box_name_pattern' => 'categories_block_pattern',
                        ));
                    }
                    // suspend block
                    elseif (!empty($type_info['Ablock_pages']) && empty($_POST['ablock_pages']) && !in_array($_POST['ablock_position'], array('top', 'bottom'))) {
                        $rlListingTypes->apBlocksTracker(array(
                            'key'     => $p_key,
                            'prefix'  => 'ltcb_',
                            'suspend' => true,
                        ));
                    }

                    if ($_POST['ablock_pages'] && $type_info['Ablock_pages'] != implode(',', $_POST['ablock_pages'])) {
                        $update_block = array(
                            'fields' => array(
                                'Page_ID' => implode(',', $_POST['ablock_pages']),
                            ),
                            'where'  => array(
                                'Key' => 'ltcb_' . $p_key,
                            ),
                        );
                        $rlActions->updateOne($update_block, 'blocks');
                    }
                    /* additional categories block tracker end */

                    /* featued block tracker */
                    if ($type_info['Featured_blocks'] && !(int) $_POST['featured_blocks']) {
                        // suspend featured boxes
                        $rlListingTypes->apBlocksTracker(array(
                            'key'     => $p_key,
                            'prefix'  => 'ltfb_',
                            'suspend' => true,
                        ));
                    } elseif (!$type_info['Featured_blocks'] && (int) $_POST['featured_blocks']) {
                        // create || activate featured box
                        $rlListingTypes->apBlocksTracker(array(
                            'key'              => $p_key,
                            'prefix'           => 'ltfb_',
                            'page_ids'         => $page_id,
                            'Side'             => $_POST['ablock_position'],
                            'Content'          => '{include file=\'blocks\'|cat:$smarty.const.RL_DS|cat:\'featured.tpl\' listings=$featured_' . $p_key . ' type=\'' . $p_key . '\'}',
                            'box_name_pattern' => 'featured_block_pattern',
                        ));
                    }
                    /* featued block tracker end */

                    /* search block tracker */
                    if (($type_info['Search'] && !(int) $_POST['search_form'])) {
                        // suspend search blocks
                        $rlListingTypes->apBlocksTracker(array(
                            'key'     => $p_key,
                            'prefix'  => 'ltsb_',
                            'suspend' => true,
                        ));
                    } elseif ((!$type_info['Search'] && (int) $_POST['search_form'])) {
                        // create || activate search box
                        $rlListingTypes->apBlocksTracker(array(
                            'key'              => $p_key,
                            'prefix'           => 'ltsb_',
                            'page_ids'         => $page_id,
                            'Side'             => 'left',
                            'Content'          => '{include file=$refine_block_controller}',
                            'box_name_pattern' => 'refine_search_pattern',
                        ));
                    }
                    /* search block tracker end */

                    // quick search form tracker
                    $rlListingTypes->apSearchFormsTracker($type_info, 'Search', 'quick', array('Groups' => 0));

                    // advanced search form tracker
                    $rlListingTypes->apSearchFormsTracker($type_info, 'Advanced_search', 'advanced');

                    // on map search form tracker
                    $rlListingTypes->apSearchFormsTracker($type_info, 'On_map_search', 'on_map', array('Groups' => 0));

                    // my ads search form tracker
                    $ma_form_field = 'Myads_search';
                    $rlListingTypes->apSearchFormsTracker($type_info, $ma_form_field, 'myads', array('Groups' => 0));

                    // my ads box tracker
                    $suspend = (intval($type_info[$ma_form_field]) && !intval($_POST[strtolower($ma_form_field)]));
                    $my_page_id = $my_page_id ?: intval($rlDb->getOne('ID', "`Key` = 'my_{$p_key}'", 'pages'));

                    $rlListingTypes->apBlocksTracker(array(
                        'key'              => $p_key,
                        'prefix'           => 'ltma_',
                        'page_ids'         => $my_page_id,
                        'Side'             => 'left',
                        'Content'          => "{include file='blocks'|cat:\$smarty.const.RL_DS|cat:'refine_search.tpl'}",
                        'box_name_pattern' => 'myads_box_pattern',
                        'suspend'          => $suspend,
                    ));

                    // main category block handler
                    $suspend = ($type_info['Cat_position'] != 'hide' && $_POST['cat_position'] == 'hide');
                    $rlListingTypes->apBlocksTracker(array(
                        'key'              => $p_key,
                        'Cat_sticky'       => 0,
                        'page_ids'         => $page_id,
                        'Position'         => 0,
                        'Side'             => $_POST['cat_position'],
                        'Content'          => "{include file='blocks'|cat:\$smarty.const.RL_DS|cat:'categories.tpl'}",
                        'prefix'           => 'ltcategories_',
                        'box_name_pattern' => 'categories_block_pattern',
                        'suspend'          => $suspend,
                    ));

                    // type page search form handler
                    $suspend = (intval($type_info['Search_type']) && !intval($_POST[strtolower('Search_type')]));
                    $rlListingTypes->apBlocksTracker(array(
                        'key'              => $p_key,
                        'prefix'           => 'ltpb_',
                        'page_ids'         => $page_id,
                        'Side'             => 'left',
                        'Content'          => "{include file='blocks'|cat:\$smarty.const.RL_DS|cat:'side_bar_search.tpl'}",
                        'box_name_pattern' => 'listing_type_search_box_pattern',
                        'suspend'          => $suspend,
                    ));

                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'listing_types+name+{$p_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit names
                            $update_phrases = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'listing_types+name+' . $p_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_phrases, 'lang_keys');
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'listing_types+name+' . $p_key,
                                'Value'  => $_POST['name'][$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }
                    }

                    // arrange type
                    $rlListingTypes->arrange($_POST['arrange_field']);

                    // multi-categories
                    $rlListingTypes->multiCategoryLevel($p_key, $allLangs, $_POST['search_multicat_phrases'], $_POST['multicat_phrases']);

                    // replace box in home gallery if type doesn't support images anymore
                    if ($type_info['Photo'] && !$_POST['photo'] && $config['home_gallery_box'] == 'ltfb_' . $p_key) {
                        $rlDb->query(
                            "UPDATE `{db_prefix}config`
                            SET `Default` = (SELECT `Key` FROM `{db_prefix}blocks`
                                WHERE `Status` = 'active'
                                    AND (`Key` LIKE 'ltfb\_%' OR `Plugin` = 'listings_box')  LIMIT 1)
                            WHERE `Key` = 'home_gallery_box' LIMIT 1;"
                        );
                    }

                    $message = $lang['listing_type_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $rlListingTypes->get();

                    /* update cache */
                    $rlCache->updateStatistics($update_cache_key);
                    $rlCache->updateCategories();
                    $rlCache->updateSearchForms();
                    $rlCache->updateSearchFields();

                    /* redirect */
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    $rlHook->load('apPhpListingTypesBottom');
}
