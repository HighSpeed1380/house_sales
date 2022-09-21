<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CATEGORIES.INC.PHP
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

        /* get category details before update */
        $category_info = $rlDb->fetch(array('Status', 'Type'), array('ID' => $id), null, 1, 'categories', 'row');

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtCategoriesUpdate');

        $rlActions->updateOne($updateData, 'categories');

        // Change status handler
        if ($field == 'Status' && $value != $category_info['Status']) {
            $reefless->loadClass('Listings');
            $rlListings->listingStatusControl(array('Category_ID' => $id), $value);

            // Update single category flag
            $rlListingTypes->updateSingleID($rlDb->getOne('Type', "`ID` = {$id}", 'categories'));
        }

        // Update category cache
        $rlCache->updateCategories();
        exit;
    }

    /* load listing types */
    $reefless->loadClass('Common');

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $langCode = $rlValid->xSql($_GET['lang_code']);
    $phrase = $rlValid->xSql($_GET['phrase']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Key` AS `Parent_key`, `T3`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}categories` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T3` ON CONCAT('categories+name+',`T1`.`Key`) = `T3`.`Key` ";
    $sql .= "AND `T3`.`Code` = '" . RL_LANG_CODE . "' AND `Module` = 'category' ";
    $sql .= "WHERE `T1`.`Status` <> 'Trash' ";

    if ($_GET['action'] == 'search') {
        $search_fields = array('Name', 'Type', 'Lock', 'Parent_ID', 'Status');

        foreach ($search_fields as $item) {
            if ($_GET[$item] != '') {
                $s_value = $rlValid->xSql($_GET[$item]);
                switch ($item) {
                    case 'Name':
                        $sql .= "AND `T3`.`Value` LIKE '%{$s_value}%' ";
                        break;

                    case 'Parent_ID':
                        $sql .= "AND FIND_IN_SET('{$s_value}', `T1`.`Parent_IDs`) > 0 ";
                        break;

                    default:
                        $sql .= "AND `T1`.`{$item}` = '{$s_value}' ";
                        break;
                }
            }
        }
    }

    if ($sort) {
        switch ($sort) {
            case 'name':
                $sortField = "`T3`.`Value`";
                break;

            case 'Parent':
                $sortField = "`T2`.`Key`";
                break;

            default:
                $sortField = "`T1`.`{$sort}`";
        }

        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtCategoriesSql');

    $data = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as &$item) {
        if ($item['ID'] == $rlListingTypes->types[$item['Type']]['Cat_general_cat']) {
            $item['name'] = $item['name'] . ' <span>(' . $lang['general_category'] . ')</span>';
        }

        $item['Parent'] = $item['Parent_key'] ?
        $rlLang->replaceLangKeys('categories+name+' . $item['Parent_key'], 'categories')
        : $lang['no_parent'];
        $item['Type']   = $rlListingTypes->types[$item['Type']]['name'];
        $item['Status'] = $GLOBALS['lang'][$item['Status']];
        $item['Lock']   = $item['Lock'] ? $lang['yes'] : $lang['no'];
    }

    $rlHook->load('apExtCategoriesData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $rlHook->load('apPhpCategoriesTop');

    /* track referent controller */
    if ($cInfo['prev'] == 'browse') {
        $_SESSION['categories_redirect_mode'] = 'browse';
        $_SESSION['categories_redirect_ID'] = $_GET['redirect_id'];
    } elseif (!in_array($cInfo['prev'], array('browse', 'categories'))) {
        unset($_SESSION['categories_redirect_mode'], $_SESSION['categories_redirect_ID']);
    }

    /* additional bread crumb step */
    if ($_GET['action']) {
        if ($_GET['action'] == 'add') {
            $bcAStep = $lang['add_category'];
        } elseif ($_GET['action'] == 'edit') {
            $bcAStep = $lang['edit_category'];
        }
    }

    $reefless->loadClass('Categories');

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = &$GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        if ($_GET['action'] == 'edit') {
            $t_key = $rlValid->xSql($_GET['key']);

            // get current category info
            $category_info = $rlDb->fetch('*', array('Key' => $t_key), "AND `Status` <> 'trash'", null, 'categories', 'row');
            $rlSmarty->assign('cpTitle', $lang['categories+name+' . $t_key]);

            $deny_tree_categories[] = $category_info['ID'];
            $rlSmarty->assign_by_ref('deny_tree_categories', $deny_tree_categories);

            if (!$category_info) {
                $sError = true;
            }
            //get available for the category fields
            $reefless->loadClass('Builder', 'admin');
            $fields = $rlBuilder->getAvailableFields($category_info['ID']);
            // add Category_ID field
            $fields[] = 88;
            $add_cond = "AND(`ID` = '" . implode("' OR `ID` = '", $fields) . "') ";

            $fields = $rlDb->fetch(array('ID', 'Key', 'Type', 'Status'), null, "WHERE `Status` <> 'trash' {$add_cond}", null, 'listing_fields');
            $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', array('name'), RL_LANG_CODE, 'admin');

            $rlSmarty->assign('fields', $fields);
        }

        /* define listing type */
        if ($_POST['type']) {
            $listing_type = $rlListingTypes->types[$_POST['type']];
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['key']                   = $category_info['Key'];
            $_POST['status']                = $category_info['Status'];
            $_POST['type']                  = $category_info['Type'];
            $_POST['lock']                  = $category_info['Lock'];
            $_POST['parent_id']             = $category_info['Parent_ID'];
            $_POST['allow_children']        = $category_info['Add'];
            $_POST['subcategories']         = $category_info['Add_sub'];
            $_POST['path'][$config['lang']] = $category_info['Path'];

            if ($config['multilingual_paths']) {
                $select = [];

                foreach ($allLangs as $langKey => $langData) {
                    if ($langKey === $config['lang']) {
                        continue;
                    }

                    $select[] = "Path_{$langKey}";
                    $_POST['path'][str_replace('Path_', '', $langKey)] = $category_info["Path_{$langKey}"];

                }

                $multilingualLTPage = $rlDb->fetch(
                    $select,
                    ['Key' => "lt_{$category_info['Type']}"],
                    null, null, 'pages', 'row'
                );

                $rlSmarty->assign_by_ref('multilingualLTPage', $multilingualLTPage);
            }

            // get names
            $t_names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+name+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($t_names as $nKey => $nVal) {
                $_POST['name'][$t_names[$nKey]['Code']] = $t_names[$nKey]['Value'];
            }

            // get titles
            $t_titles = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+title+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($t_titles as $nKey => $nVal) {
                $_POST['title'][$t_titles[$nKey]['Code']] = $t_titles[$nKey]['Value'];
            }

            // get h1
            $t_h1_headings = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+h1+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($t_h1_headings as $nKey => $nVal) {
                $_POST['h1_heading'][$t_h1_headings[$nKey]['Code']] = $t_h1_headings[$nKey]['Value'];
            }

            // get descriptions
            $descriptions = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+des+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($descriptions as $nKey => $nVal) {
                $_POST['description_' . $descriptions[$nKey]['Code']] = $descriptions[$nKey]['Value'];
            }

            // get meta description
            $meta_description = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+meta_description+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_description as $nKey => $nVal) {
                $_POST['meta_description'][$meta_description[$nKey]['Code']] = $meta_description[$nKey]['Value'];
            }

            // get meta keywords
            $meta_keywords = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+meta_keywords+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_keywords as $nKey => $nVal) {
                $_POST['meta_keywords'][$meta_keywords[$nKey]['Code']] = $meta_keywords[$nKey]['Value'];
            }

            // get listing meta description
            $meta_description = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+listing_meta_description+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_description as $nKey => $nVal) {
                $_POST['listing_meta_description'][$meta_description[$nKey]['Code']] = $meta_description[$nKey]['Value'];
            }

            // get listing meta keywords
            $meta_keywords = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+listing_meta_keywords+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_keywords as $nKey => $nVal) {
                $_POST['listing_meta_keywords'][$meta_keywords[$nKey]['Code']] = $meta_keywords[$nKey]['Value'];
            }
            // get listing meta title
            $meta_title = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'categories+listing_meta_title+' . $t_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_title as $nKey => $nVal) {
                $_POST['listing_meta_title'][$meta_title[$nKey]['Code']] = $meta_title[$nKey]['Value'];
            }

            /* get related categories */
            if (!empty($category_info['Parent_ID'])) {
                $rlSmarty->assign_by_ref('parent_id', $category_info['Parent_ID']);
            }

            if ($rlListingTypes->types[$category_info['Type']]['Links_type'] == 'subdomain') {
                $rlSmarty->assign_by_ref('abase', preg_replace('#http(s)?://(www.)?#', "http$1://" . $pages['lt_' . $category_info['Type']] . ".", RL_URL_HOME));
            }

            $rlHook->load('apPhpCategoriesPost');
        }

        // get parent category data
        if ($_GET['parent_id']) {
            $get_parent_id = (int) $_GET['parent_id'];
            $request_parent_type = $rlDb->getOne('Type', "`ID` = '{$get_parent_id}'", 'categories');

            $_POST['type'] = $request_parent_type;
        }

        // get categories related to selected type
        if ($_POST['type']) {
            $parent_cats_list = $rlCategories->getCatTree(0, $_POST['type']);
            $rlSmarty->assign_by_ref('categories', $parent_cats_list);
            $rlSmarty->assign_by_ref('type', $_POST['type']);
        }

        /* get parent categories */
        if ($_REQUEST['parent_id'] || $_POST['parent_id']) {
            $parent_id = $_REQUEST['parent_id'] ? (int) $_REQUEST['parent_id'] : $_POST['parent_id'];
            $rlCategories->parentPoints(array($parent_id));
            $rlSmarty->assign_by_ref('parent_id', $parent_id);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $rlValid->xSql($_POST['key']);
            $orig_key = $f_key;

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

                $exist_key = $rlDb->fetch(array('Key', 'Status'), array('Key' => $f_key), null, 1, 'categories', 'row');

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_category_exist']);
                    if ($exist_key['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $error_fields[] = 'key';
                    $errors[] = $exist_error;
                }
            }

            /* check type */
            $f_type = $_POST['type'];

            if (empty($f_type)) {
                $errors[] = $lang['notice_type_empty'];
                $error_fields[] = 'type';
            }

            // Check path
            $f_path = $_POST['path'][$config['lang']];

            if ($config['multilingual_paths']) {
                $multiPaths = [];

                foreach ($_POST['path'] as $langKey => $pathValue) {
                    if ($langKey === $config['lang'] || $pathValue === '') {
                        continue;
                    }

                    $multiPaths[$langKey] = $rlValid->str2multiPath($pathValue);
                }
            }

            // utf validation
            if (!utf8_is_ascii($f_path) && !empty($f_path) && $config['url_transliteration']) {
                $f_path = utf8_to_ascii($f_path);
            }

            // generate path if it is empty
            $replace_mode = $_GET['action'] == 'add' ? false : true;
            $f_path = empty($f_path) ? $rlValid->str2path($orig_key) : $rlValid->str2path($f_path, $replace_mode);

            $f_relate_path = $rlCategories->getCatPath($parent_id);
            $f_relate_path = $f_relate_path ? $f_relate_path . '/' : '';
            $real_path     = $f_relate_path . $f_path;

            /* check for existing path */
            $exist_path_sql = "SELECT `T1`.`Path`, `T1`.`Status` ";
            $exist_path_sql .= "FROM `{db_prefix}categories` AS `T1` ";
            $exist_path_sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
            $exist_path_sql .= "WHERE `T1`.`Path` = '{$real_path}' ";

            // check in it's category listing type
            if ($listing_type['Links_type'] != 'short') {
                $exist_path_sql .= "AND `T2`.`Key` = '{$f_type}' ";
            }
            // allow similar path for other listing type
            else {
                $exist_path_sql .= "AND `T2`.`Links_type` = 'short' ";
            }

            if ($_GET['action'] == 'edit') {
                $exist_path_sql .= "AND `T1`.`Key` <> '{$f_key}'";
            }

            $exist_path = $rlDb->getRow($exist_path_sql);

            if (!$exist_path) {
                $exist_path = $rlDb->fetch(array('Path', 'Status'), array('Path' => $f_path), "AND `Key` LIKE 'lt_%'", 1, 'pages', 'row');
            }

            if ($exist_path) {
                $errors[] = str_replace('{path}', "<b>" . $f_path . "</b>", $lang[$exist_path['Status'] == 'trash' ? 'notice_path_exist_droped' : 'notice_path_exist']);
                $error_fields[] = 'path';
            }
            /* check for existing path end */

            // validate for path edge
            preg_match('/\-[0-9]+$/', $f_path, $matches);
            if (!empty($matches)) {
                $errors[] = $lang['category_url_listing_logic'];
                $error_fields[] = "path";
                $_POST['path'] = $f_path;
            }

            if (!$errors && $config['multilingual_paths'] && $multiPaths) {
                foreach ($multiPaths as $langKey => $multiPath) {
                    $realPath = $f_relate_path . $multiPath;

                    // Check for existing path
                    $existPathSql = "SELECT `T1`.`ID` FROM `{db_prefix}categories` AS `T1` ";
                    $existPathSql .= "LEFT JOIN `{db_prefix}listing_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
                    $existPathSql .= "WHERE ";
                    $additionalSQL = '';

                    foreach ($languages as $languageKey => $languageData) {
                        if ($languageKey === $config['lang']) {
                            continue;
                        }

                        $additionalSQL = $additionalSQL
                            ? $additionalSQL . " OR `Path_{$languageKey}` = '{$realPath}'"
                            : "`Path_{$languageKey}` = '{$realPath}'";
                    }

                    $existPathSql .= '(' . $additionalSQL . ')';
                    $existPathSql .= " AND `T1`.`Key` <> '{$f_key}'";

                    // Check in it's category listing type
                    if ($listing_type['Links_type'] != 'short') {
                        $existPathSql .= " AND `T2`.`Key` = '{$f_type}'";
                    }
                    // allow similar path for other listing type
                    else {
                        $existPathSql .= " AND `T2`.`Links_type` = 'short'";
                    }

                    $existPath = $rlDb->getRow($existPathSql);

                    if (!$existPath) {
                        $existPath = $rlDb->fetch(
                            ['Path', 'Status'],
                            ['Path' => $multiPath],
                            "AND `Key` LIKE 'lt_%'",
                            1, 'pages', 'row'
                        );
                    }

                    if (!empty($existPath)) {
                        $errors[]       = str_replace('{path}', "<b>\"{$multiPath}\"</b>", $lang['notice_path_exist']);
                        $error_fields[] = "path[{$langKey}]";
                    }

                    // Validate for path edge
                    preg_match('/\-[0-9]+$/', $multiPath, $matches);

                    if (!empty($matches)) {
                        $errors[]                = $lang['category_url_listing_logic'];
                        $error_fields[]          = "path[{$langKey}]";
                        $_POST['path'][$langKey] = $multiPath;
                    }
                }
            }

            $original_path = explode('/', trim($category_info['Path'], '/'));
            $original_path_tail = array_pop($original_path);

            /* edit path handler */
            if ($_GET['action'] == 'edit' && (($original_path_tail != $f_path) || ($parent_id != $category_info['Parent_ID']))) {
                // check for slashes in path
                if (is_numeric(strpos($f_path, '/'))) {
                    $errors[] = $lang['remove_slashes_warning'];
                    $error_fields[] = 'path';
                }

                if (!$errors) {
                    if ($parent_id != $category_info['Parent_ID']) {
                        $replace_path = $f_relate_path . $f_path;
                    } else {
                        $replace_path = $original_path ? implode('/', $original_path) . '/' . $f_path : $f_path;
                    }
                    $replace_path_sql = "UPDATE `{db_prefix}categories` ";
                    $replace_path_sql .= "SET `Path` = REPLACE(`Path`, '{$category_info['Path']}', '{$replace_path}') ";
                    $replace_path_sql .= "WHERE `Path` LIKE '{$category_info['Path']}%' AND `Type` = '{$f_type}'";
                    $rlDb->query($replace_path_sql);
                }
            }

            // glue path with parent category path if needs
            $f_path = $f_relate_path . $f_path;

            /* build tree */
            $f_tree = $rlDb->getOne('Tree', "`ID` = '{$parent_id}'", 'categories');

            /* check names */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$lval['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$lval['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }

                $f_names[$lval['Code']] = $f_name[$lval['Code']];
            }

            $f_title = $_POST['title'];
            $f_h1 = $_POST['h1_heading'];

            $rlHook->load('apPhpCategoriesDataValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                $level = $_POST['parent_id'] ? $rlDb->getOne('Level', "`ID` = '{$_POST['parent_id']}'", 'categories') + 1 : 0;
                $parent_id = $parent_id ? $parent_id : 0;

                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}categories` WHERE `Parent_ID` = {$parent_id}");
                    $position = $position['max'] + 1;

                    if ($parent_id) {
                        $parent_ids[] = $parent_id;
                        if ($parents = $rlCategories->getParentIDs($parent_id)) {
                            $parent_ids = array_merge($parents, $parent_ids);
                        }
                        $parent_ids = implode(',', $parent_ids);

                        $sql = "SELECT GROUP_CONCAT(`Key`) as `Keys` FROM `{db_prefix}categories` ";
                        $sql .= "WHERE FIND_IN_SET(`ID`, '" . $parent_ids . "') ";

                        $parent_keys = $rlDb->getRow($sql, 'Keys');
                    }

                    // write main category information
                    $data = array(
                        'Key'         => $f_key,
                        'Path'        => $f_path,
                        'Status'      => $_POST['status'],
                        'Type'        => $_POST['type'],
                        'Lock'        => $_POST['lock'],
                        'Parent_ID'   => $parent_id,
                        'Parent_IDs'  => $parent_ids,
                        'Parent_keys' => $parent_keys,
                        'Position'    => $position,
                        'Level'       => $level,
                        'Modified'    => 'NOW()',
                        'Tree'        => $f_tree . '.' . $position,
                    );

                    if ($config['multilingual_paths'] && $multiPaths) {
                        foreach ($allLangs as $langKey => $langData) {
                            if ($langKey === $config['lang']) {
                                continue;
                            }

                            $data["Path_{$langKey}"] = $multiPaths[$langKey];
                        }
                    }

                    if ($listing_type['Cat_custom_adding']) {
                        $data['Add'] = $_POST['allow_children'] ? 1 : 0;
                        $data['Add_sub'] = empty($_POST['subcategories']) ? 0 : 1;
                    }

                    $rlHook->load('apPhpCategoriesBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'categories')) {
                        $rlCache->updateCategories();
                        $category_id = $rlDb->insertID();

                        $rlHook->load('apPhpCategoriesAfterAdd');

                        // Update single category flag
                        $rlListingTypes->updateSingleID($f_type);

                        // save name's phrases
                        foreach ($allLangs as $key => $value) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'category',
                                'Status' => 'active',
                                'Key'    => 'categories+name+' . $f_key,
                                'Value'  => trim($f_name[$allLangs[$key]['Code']]),
                            );

                            if (!empty($f_title[$value['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+title+' . $f_key,
                                    'Value'  => trim($f_title[$value['Code']]),
                                );
                            }

                            if (!empty($f_h1[$value['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+h1+' . $f_key,
                                    'Value'  => trim($f_h1[$value['Code']]),
                                );
                            }

                            if (!empty($_POST['description_' . $allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+des+' . $f_key,
                                    'Value'  => trim($_POST['description_' . $allLangs[$key]['Code']]),
                                );
                            }

                            if (!empty($_POST['meta_description'][$allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+meta_description+' . $f_key,
                                    'Value'  => trim($_POST['meta_description'][$allLangs[$key]['Code']]),
                                );
                            }

                            if (!empty($_POST['meta_keywords'][$allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+meta_keywords+' . $f_key,
                                    'Value'  => trim($_POST['meta_keywords'][$allLangs[$key]['Code']]),
                                );
                            }
                            if (!empty($_POST['listing_meta_description'][$allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+listing_meta_description+' . $f_key,
                                    'Value'  => trim($_POST['listing_meta_description'][$allLangs[$key]['Code']]),
                                );
                            }

                            if (!empty($_POST['listing_meta_keywords'][$allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+listing_meta_keywords+' . $f_key,
                                    'Value'  => trim($_POST['listing_meta_keywords'][$allLangs[$key]['Code']]),
                                );
                            }
                            if (!empty($_POST['listing_meta_title'][$allLangs[$key]['Code']])) {
                                $lang_keys[] = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+listing_meta_title+' . $f_key,
                                    'Value'  => trim($_POST['listing_meta_title'][$allLangs[$key]['Code']]),
                                );
                            }
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['category_added'];
                        if ($_SESSION['categories_redirect_mode']) {
                            $aUrl = array("controller" => "browse", "id" => $_POST['parent_id']);
                        } else {
                            /* redirect to build form */
                            $reefless->loadClass('Builder', 'admin');
                            $relations = $rlBuilder->getRelations($category_id);

                            $aUrl = array("controller" => $controller);

                            if (empty($relations) && !$listing_type['Cat_general_cat']) {
                                $aUrl['request'] = 'build';
                                $aUrl['key'] = $f_key;
                            }
                        }
                    } else {
                        trigger_error("Can't add new lisitng type (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new lisitng type (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_data = array(
                        'fields' => array(
                            'Status'    => $_POST['status'],
                            'Path'      => $f_path,
                            'Type'      => $_POST['type'],
                            'Lock'      => $_POST['lock'],
                            'Parent_ID' => $_POST['parent_id'],
                            'Level'     => $level,
                            'Tree'      => $f_tree . '.' . $category_info['Position'],
                            'Modified'  => 'NOW()',
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    if ($config['multilingual_paths'] && $multiPaths) {
                        foreach ($allLangs as $langKey => $langData) {
                            if ($langKey === $config['lang']) {
                                continue;
                            }

                            $update_data['fields']["Path_{$langKey}"] = $multiPaths[$langKey]
                            ? $rlCategories->getCatPath($parent_id, $multiPaths[$langKey], $langKey)
                            : $f_path ;
                        }
                    }

                    if ($_POST['status'] != $category_info['Status']) {
                        $reefless->loadClass('Listings');
                        $rlListings->listingStatusControl(array('Category_ID' => $category_info['ID']), $_POST['status']);
                    }

                    if ($parent_id && $category_info['Parent_ID'] != $parent_id) {
                        $parent_ids[] = $parent_id;
                        if ($parents = $rlCategories->getParentIDs($parent_id)) {
                            $parent_ids = array_merge($parent_ids, $parents);
                        }

                        $sql = "SELECT `Key` FROM `{db_prefix}categories` ";
                        $sql .= "WHERE FIND_IN_SET(`ID`, '" . implode(",", $parent_ids) . "') ORDER BY `Level`";

                        $parent_keys = $rlDb->getAll($sql, array(null, 'Key'));
                        $parent_keys = implode(",", $parent_keys);

                        $update_data['fields']['Parent_IDs'] = implode(',', $parent_ids);
                        $update_data['fields']['Parent_keys'] = $parent_keys;
                    }

                    if ($_POST['type'] != $category_info['Type']) {
                        $sql = "UPDATE `{db_prefix}categories` SET `Type` = '{$_POST['type']}' ";
                        $sql .= "WHERE FIND_IN_SET({$category_info['ID']}, `Parent_IDs`) ";
                        $rlDb->query($sql);
                    }

                    if ($listing_type['Cat_custom_adding']) {
                        $update_data['fields']['Add'] = $_POST['allow_children'] ? 1 : 0;
                        $update_data['fields']['Add_sub'] = empty($_POST['subcategories']) ? 0 : 1;
                    }

                    $rlHook->load('apPhpCategoriesBeforeEdit');

                    $action = $rlActions->updateOne($update_data, 'categories');

                    $rlHook->load('apPhpCategoriesAfterEdit');

                    // Update single category flag
                    $rlListingTypes->updateSingleID($f_type);

                    $rlCache->updateCategories();

                    $category_id = $rlDb->getOne("ID", "`Key` = '{$f_key}'", 'categories');

                    foreach ($allLangs as $key => $value) {
                        /* update category name */
                        if ($rlDb->getOne('ID', "`Key` = 'categories+name+{$f_key}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                            // edit names
                            $lang_keys_name[] = array(
                                'where'  => array(
                                    'Code' => $value['Code'],
                                    'Key'  => 'categories+name+' . $f_key,
                                ),
                                'fields' => array(
                                    'Value' => $_POST['name'][$value['Code']],
                                ),
                            );
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $value['Code'],
                                'Module' => 'category',
                                'Key'    => 'categories+name+' . $f_key,
                                'Value'  => $_POST['name'][$value['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }

                        /* update category title */
                        if ($rlDb->getOne('ID', "`Key` = 'categories+title+{$f_key}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                            // edit names
                            $lang_keys_name[] = array(
                                'where'  => array(
                                    'Code' => $value['Code'],
                                    'Key'  => 'categories+title+' . $f_key,
                                ),
                                'fields' => array(
                                    'Value' => $_POST['title'][$value['Code']],
                                ),
                            );
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $value['Code'],
                                'Module' => 'category',
                                'Key'    => 'categories+title+' . $f_key,
                                'Value'  => $_POST['title'][$value['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }

                        /* update category h1 */
                        if ($rlDb->getOne('ID', "`Key` = 'categories+h1+{$f_key}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                            // edit names
                            $lang_keys_name[] = array(
                                'where'  => array(
                                    'Code' => $value['Code'],
                                    'Key'  => 'categories+h1+' . $f_key,
                                ),
                                'fields' => array(
                                    'Value' => $_POST['h1_heading'][$value['Code']],
                                ),
                            );
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $value['Code'],
                                'Module' => 'category',
                                'Key'    => 'categories+h1+' . $f_key,
                                'Value'  => $_POST['h1_heading'][$value['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }

                        /* update category description */
                        if (!empty($_POST['description_' . $allLangs[$key]['Code']])) {
                            $c_description = $rlDb->fetch(array('ID'), array('Key' => 'categories+des+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($c_description)) {
                                $lang_keys_name[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'categories+des+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => trim($_POST['description_' . $allLangs[$key]['Code']]),
                                    ),
                                );
                            } else {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+des+' . $f_key,
                                    'Value'  => trim($_POST['description_' . $allLangs[$key]['Code']]),
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        } else {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+des+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                        }

                        /* update category meta description */
                        if (!empty($_POST['meta_description'][$allLangs[$key]['Code']])) {
                            $meta_description = $rlDb->fetch(array('ID'), array('Key' => 'categories+meta_description+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($meta_description)) {
                                $lang_keys_name[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'categories+meta_description+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => trim($_POST['meta_description'][$allLangs[$key]['Code']]),
                                    ),
                                );
                            } else {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+meta_description+' . $f_key,
                                    'Value'  => trim($_POST['meta_description'][$allLangs[$key]['Code']]),
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        } else {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+meta_description+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                        }

                        /* update category meta keywords */
                        if (!empty($_POST['meta_keywords'][$allLangs[$key]['Code']])) {
                            $meta_keywords = $rlDb->fetch(array('ID'), array('Key' => 'categories+meta_keywords+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($meta_keywords)) {
                                $lang_keys_name[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'categories+meta_keywords+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => trim($_POST['meta_keywords'][$allLangs[$key]['Code']]),
                                    ),
                                );
                            } else {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+meta_keywords+' . $f_key,
                                    'Value'  => trim($_POST['meta_keywords'][$allLangs[$key]['Code']]),
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        } else {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+meta_keywords+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                        }

                        /* update category listings meta description */
                        if (!empty($_POST['listing_meta_description'][$allLangs[$key]['Code']])) {
                            $listing_meta_description = $rlDb->fetch(array('ID'), array('Key' => 'categories+listing_meta_description+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($listing_meta_description)) {
                                $lang_keys_name[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'categories+listing_meta_description+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => trim($_POST['listing_meta_description'][$allLangs[$key]['Code']]),
                                    ),
                                );
                            } else {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+listing_meta_description+' . $f_key,
                                    'Value'  => trim($_POST['listing_meta_description'][$allLangs[$key]['Code']]),
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        } else {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+listing_meta_description+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                        }

                        /* update category listings meta keywords */
                        if (!empty($_POST['listing_meta_keywords'][$allLangs[$key]['Code']])) {
                            $listing_meta_keywords = $rlDb->fetch(array('ID'), array('Key' => 'categories+listing_meta_keywords+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($listing_meta_keywords)) {
                                $lang_keys_name[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'categories+listing_meta_keywords+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => trim($_POST['listing_meta_keywords'][$allLangs[$key]['Code']]),
                                    ),
                                );
                            } else {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+listing_meta_keywords+' . $f_key,
                                    'Value'  => trim($_POST['listing_meta_keywords'][$allLangs[$key]['Code']]),
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        } else {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+listing_meta_keywords+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                        }

                        /* update category listings meta title */
                        if (!empty($_POST['listing_meta_title'][$allLangs[$key]['Code']])) {
                            $listing_meta_title = $rlDb->fetch(array('ID'), array('Key' => 'categories+listing_meta_title+' . $f_key, 'Code' => $allLangs[$key]['Code']), null, null, 'lang_keys', 'row');
                            if (!empty($listing_meta_title)) {
                                $lang_keys_name[] = array(
                                    'where'  => array(
                                        'Code' => $allLangs[$key]['Code'],
                                        'Key'  => 'categories+listing_meta_title+' . $f_key,
                                    ),
                                    'fields' => array(
                                        'Value' => trim($_POST['listing_meta_title'][$allLangs[$key]['Code']]),
                                    ),
                                );
                            } else {
                                $lang_keys_des = array(
                                    'Code'   => $allLangs[$key]['Code'],
                                    'Module' => 'category',
                                    'Status' => 'active',
                                    'Key'    => 'categories+listing_meta_title+' . $f_key,
                                    'Value'  => trim($_POST['listing_meta_title'][$allLangs[$key]['Code']]),
                                );

                                $rlActions->insertOne($lang_keys_des, 'lang_keys');
                            }
                        } else {
                            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+listing_meta_title+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'");
                        }
                    }

                    // update
                    $GLOBALS['rlActions']->update($lang_keys_name, 'lang_keys');

                    // Check necessary of updating  multilingual paths
                    $updateChildPaths = false;
                    foreach ($update_data['fields'] as $updateKey => $updateItem) {
                        if (false !== strpos($updateKey, 'Path_') && $updateItem != $category_info[$updateKey]) {
                            $updateChildPaths = true;
                        }
                    }

                    // Update multilingual paths for all child categories
                    if ($config['multilingual_paths'] && $multiPaths && $category_id && $updateChildPaths) {
                        \Flynax\Utils\Category::updateChildMultilingualPaths($category_id, $multiPaths);
                    }

                    $rlCache->updateCategories();

                    $message = $lang['category_edited'];

                    if ($_SESSION['categories_redirect_mode'] && $_SESSION['categories_redirect_ID']) {
                        $aUrl = array("controller" => "browse", "id" => $_SESSION['categories_redirect_ID']);
                    } else {
                        $aUrl = array("controller" => $controller);
                    }
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
        $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
    } elseif ($_GET['action'] == 'build') {
        $category_key = $rlValid->xSql($_GET['key']);
        $category_id = (int) $_GET['id'];

        /* get current category info */
        $category_info = $rlDb->fetch(array('ID', 'Key', 'Type'), array('Key' => $category_key), "OR `ID` = {$category_id} AND `Status` <> 'trash'", null, 'categories', 'row');
        $category_info = $rlLang->replaceLangKeys($category_info, 'categories', array('name'), RL_LANG_CODE, 'admin');
        $rlSmarty->assign_by_ref('category_info', $category_info);

        if (!$category_info) {
            $sError = true;
        } else {
            $rlSmarty->assign('cpTitle', $category_info['name']);

            $reefless->loadClass('Builder', 'admin');

            if ($_GET['form'] == 'submit_form') {
                /* update bread crumbs */
                $bcAStep[] = array(
                    'name' => $lang['submit_form_builder'],
                );

                $rlSmarty->assign('cpTitle', $category_info['name']);

                /* get relations */
                $relations = $rlBuilder->getRelations($category_info['ID']);
                $rlSmarty->assign_by_ref('relations', $relations);

                foreach ($relations as $rKey => $rValue) {
                    $no_groups[] = $relations[$rKey]['Key'];

                    $f_fields = $relations[$rKey]['Fields'];

                    if ($relations[$rKey]['Group_ID']) {
                        foreach ($f_fields as $fKey => $fValue) {
                            $no_fields[] = $f_fields[$fKey]['Key'];
                        }
                    } else {
                        $no_fields[] = $relations[$rKey]['Fields']['Key'];
                    }
                }

                $groups = $rlDb->fetch(array('ID', 'Key', 'Status'), null, "WHERE `Status` <> 'trash'", null, 'listing_groups');
                $groups = $rlLang->replaceLangKeys($groups, 'listing_groups', array('name'), RL_LANG_CODE, 'admin');

                // hide already using groups
                if (!empty($no_groups)) {
                    foreach ($groups as $grKey => $grVal) {
                        if (false !== array_search($groups[$grKey]['Key'], $no_groups)) {
                            $groups[$grKey]['hidden'] = true;
                        }
                    }
                }

                $rlSmarty->assign_by_ref('groups', $groups);

                // get listing fields
                $deny_fields = array('Category_ID', 'keyword_search', 'posted_by', 'sf_status', 'sf_active_till', 'sf_plan', 'sf_featured');
                $fields = $rlDb->fetch(array('ID', 'Key', 'Type', 'Status'), null, "WHERE `Status` <> 'trash' AND `Key` <> '" . implode("' AND `Key` <> '", $deny_fields) . "'", null, 'listing_fields');

                $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', array('name'), RL_LANG_CODE, 'admin');

                //filter fields with empty name
                foreach ($fields as &$field) {
                    if (!$field['name']) {
                        unset($field);
                    }
                }

                // hide already using fields
                if (!empty($no_fields)) {
                    foreach ($fields as $fKey => $fVal) {
                        if (false !== array_search($fields[$fKey]['Key'], $no_fields)) {
                            $fields[$fKey]['hidden'] = true;
                        }
                    }
                }

                $rlSmarty->assign_by_ref('fields', $fields);
            } else {
                $rlSmarty->assign('no_groups', true);

                switch ($_GET['form']) {
                    case 'short_form':
                        $rlBuilder->rlBuildTable = 'short_forms';
                        $rlBuilder->rlBuildField = 'Field_ID';

                        /* additional bread crumb step */
                        $bcAStep = $lang['short_form_builder'];
                        break;

                    case 'listing_title':
                        $rlBuilder->rlBuildTable = 'listing_titles';
                        $rlBuilder->rlBuildField = 'Field_ID';

                        /* additional bread crumb step */
                        $bcAStep = $lang['listing_title_builder'];
                        break;

                    case 'featured_form':
                        $rlBuilder->rlBuildTable = 'featured_form';
                        $rlBuilder->rlBuildField = 'Field_ID';

                        /* additional bread crumb step */
                        $bcAStep = $lang['featured_form_builder'];
                        break;

                    case 'sorting_form':
                        $rlBuilder->rlBuildTable = 'sorting_forms';
                        $rlBuilder->rlBuildField = 'Field_ID';

                        /* additional bread crumb step */
                        $bcAStep = $lang['sorting_form_builder'];
                        break;
                }

                /* get available fields for current category */
                $a_fields = $rlBuilder->getAvailableFields($category_info['ID']);

                /* get form fields for current category */
                if ($_GET['form'] == 'submit_form') {
                    $relations = $rlBuilder->getRelations($category_info['ID']);
                } else {
                    $relations = $rlBuilder->getFormRelations($category_info['ID']);
                }
                $rlSmarty->assign_by_ref('relations', $relations);

                foreach ($relations as $rKey => $rValue) {
                    $no_groups[] = $relations[$rKey]['Key'];

                    $f_fields = $relations[$rKey]['Fields'];

                    if ($relations[$rKey]['Group_ID']) {
                        foreach ($f_fields as $fKey => $fValue) {
                            $no_fields[] = $f_fields[$fKey]['Key'];
                        }
                    } else {
                        $no_fields[] = $relations[$rKey]['Fields']['Key'];
                    }
                }

                $a_fields[] = 88;

                /* get listing fields */
                if (!empty($a_fields)) {
                    $add_cond = "AND(`ID` = '" . implode("' OR `ID` = '", $a_fields) . "') ";

                    $fields = $rlDb->fetch(array('ID', 'Key', 'Type', 'Status'), null, "WHERE `Status` <> 'trash' {$add_cond}", null, 'listing_fields');
                    $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', array('name'), RL_LANG_CODE, 'admin');

                    // hide already using fields
                    if (!empty($no_fields)) {
                        foreach ($fields as $fKey => $fVal) {
                            if (false !== array_search($fields[$fKey]['Key'], $no_fields)) {
                                $fields[$fKey]['hidden'] = true;
                            }
                        }
                    }

                    $rlSmarty->assign_by_ref('fields', $fields);
                }
            }

            $rlHook->load('apPhpCategoriesBuild');

            /* register ajax methods */
            $rlXajax->registerFunction(array('buildForm', $rlBuilder, 'ajaxBuildForm'));
        }
    } else {
        $parent_cats_list = $rlCategories->getCategories();
        $rlSmarty->assign_by_ref('parent_cats_list', $parent_cats_list);
    }

    $rlHook->load('apPhpCategoriesBottom');

    $reefless->loadClass('Categories');
    $reefless->loadClass('Controls', 'admin');

    /* register ajax methods */
    $rlXajax->registerFunction(array('prepareDeleting', $rlCategories, 'ajaxPrepareDeleting'));
    $rlXajax->registerFunction(array('deleteCategory', $rlCategories, 'ajaxDeleteCategory'));
    $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
    $rlXajax->registerFunction(array('loadType', $rlCategories, 'ajaxLoadType'));
    $rlXajax->registerFunction(array('categoryMassActions', $rlCategories, 'ajaxCategoryMassActions'));
}
