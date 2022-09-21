<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: FIELD_BOUND_BOXES.INC.PHP
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

if ($_GET['q'] == 'ext') {
    require_once '../../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {
        $reefless->loadClass('FieldBoundBoxes', null, 'fieldBoundBoxes');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);
        $box = $rlValid->xSql($_GET['box']);

        if ($box && $id) {
            // Update box item
            $updateData = array(
                'fields' => array(
                    $field => $value,
                ),
                'where'  => array(
                    'ID' => $id,
                ),
            );

            $rlDb->updateOne($updateData, 'field_bound_items');
            $rlFieldBoundBoxes->updateBoxContent($box);
        } else {
            // Update box itself
            $key = $rlDb->getOne("Key", "`ID` = '{$id}'", "field_bound_boxes");

            if ($field == 'Status') {
                $rlFieldBoundBoxes->setStatus($value, $key);
            } else {
                $updateData = array(
                    'fields' => array(
                        $field => $value,
                    ),
                    'where'  => array(
                        'Key' => $key,
                    ),
                );

                $rlDb->updateOne($updateData, 'blocks');
            }
        }
    } else {
        $limit = (int) $_GET['limit'];
        $start = (int) $_GET['start'];
        $sort = $rlValid->xSql($_GET['sort']);
        $sortDir = $rlValid->xSql($_GET['dir']);
        $box = $rlValid->xSql($_GET['box']);

        if ($box) {
            $reefless->loadClass('FieldBoundBoxes', null, 'fieldBoundBoxes');

            // Get box items
            $box_info = $rlDb->fetch(['ID', 'Field_key'], ['Key' => $box], null, 1, 'field_bound_boxes', 'row');
            $format_key = $rlDb->getOne('Condition', "`Key` = '{$box_info['Field_key']}'", 'listing_fields');

            $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T3`.`Value` AS `name` ";
            $sql .= "FROM `{db_prefix}field_bound_items` AS `T1` ";
            if ($rlFieldBoundBoxes->isNewMultiField() && $rlFieldBoundBoxes->isFormatBelongToMultiField($format_key)) {
                $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_" . RL_LANG_CODE . "` AS `T3` ON `T1`.`Key` = `T3`.`Key` ";
            } else {
                $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T3` ON `T1`.`pName` = `T3`.`Key` ";
                $sql .= "AND `T3`.`Code` = '" . RL_LANG_CODE . "' ";
            }
            $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T1`.`Box_ID` = {$box_info['ID']} ";
            if ($sort) {
                $sortField = $sort == 'name' ? "`T3`.`Value`" : "`T1`.`{$sort}`";
                $sql .= "ORDER BY {$sortField} {$sortDir} ";
            }
            $sql .= "LIMIT {$start},{$limit}";

            $data = $rlDb->getAll($sql);
            $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

            foreach ($data as $key => $value) {
                $data[$key]['Status'] = $lang[$value['Status']];
            }
        } else {
            // Get boxes
            $sql = "SELECT SQL_CALC_FOUND_ROWS `T4`.`Value` AS `Field_name`, `T2`.*, ";
            $sql .= "`T3`.`Value` AS `name`, `T1`.`Key` AS `Key`, `T1`.`ID` AS `ID` ";
            $sql .= "FROM `{db_prefix}field_bound_boxes` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}blocks` AS `T2` ON `T1`.`Key` = `T2`.`Key` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T3` ON CONCAT('blocks+name+',`T2`.`Key`) = `T3`.`Key` ";
            $sql .= "AND `T3`.`Code` = '" . RL_LANG_CODE . "' ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T4` ";
            $sql .= "ON CONCAT('listing_fields+name+',`T1`.`Field_key`) = `T4`.`Key` ";
            $sql .= "AND `T4`.`Code` = '" . RL_LANG_CODE . "' ";
            $sql .= "WHERE `T1`.`Status` <> 'trash' ";
            if ($sort) {
                $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
                $sql .= "ORDER BY {$sortField} {$sortDir} ";
            }
            $sql .= "LIMIT {$start},{$limit}";

            $data = $rlDb->getAll($sql);
            $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

            foreach ($data as $key => $value) {
                $data[$key]['Status'] = $lang[$value['Status']];
                $data[$key]['Side'] = $lang[$value['Side']];
                $data[$key]['Tpl'] = $value['Tpl'] ? $lang['yes'] : $lang['no'];
            }
        }

        $output['total'] = $count['count'];
        $output['data'] = $data;

        echo json_encode($output);
    }
} else {
    $reefless->loadClass('FieldBoundBoxes', null, 'fieldBoundBoxes');

    if ($_GET['box']) {
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $bcAStep[] = array(
            'name' => $rlLang->getPhrase('blocks+name+' . $_GET['box'], null, null, true),
            'Controller' => $controller,
            'Vars' => 'box=' . $_GET['box']
        );

        $box_info = $rlDb->fetch('*', ['Key' => $_GET['box']], null, 1, 'field_bound_boxes', 'row');
        $rlSmarty->assign_by_ref('box_info', $box_info);

        $format_key = $rlDb->getOne('Condition', "`Key` = '{$box_info['Field_key']}'", 'listing_fields');

        if ($rlFieldBoundBoxes->isNewMultiField() && $rlFieldBoundBoxes->isFormatBelongToMultiField($format_key)) {
            $format_id = $rlDb->getOne('ID', "`Key` = '{$format_key}'", 'multi_formats');

            $rlSmarty->assign('format_id', $format_id);
            $rlSmarty->assign('box_multifield', true);
        }
    }

    if ($_GET['action'] == 'edit_item' && $_GET['item']) {
        $item_key = $_GET['item'];
        $box = $_GET['box'];

        $fbb_info = $rlDb->fetch(
            '*',
            array('Key' => $box),
            null,
            null,
            'field_bound_boxes',
            'row'
        );

        $item_info = $rlDb->fetch(
            '*',
            array('Key' => $item_key, 'Box_ID' => $fbb_info['ID']),
            null,
            null,
            'field_bound_items',
            'row'
        );

        $fb_page_info = $rlDb->fetch(
            array('Path'),
            array('Key' => $fbb_info['Key']),
            null,
            null,
            'pages',
            'row'
        );

        $field_condition = $rlDb->getOne("Condition", "`Key` = '" . $fbb_info['Field_key'] . "'", "listing_fields");
        $option_names = [];

        if ($rlFieldBoundBoxes->isNewMultiField() && $rlFieldBoundBoxes->isFormatBelongToMultiField($field_condition)) {
            foreach ($allLangs as $lang_item) {
                $option_names[$lang_item['Code']] = $rlDb->getOne('Value', "`Key` = '{$item_info['Key']}'", 'multi_formats_lang_' . $lang_item['Code']);
            }
        } else {
            foreach ($allLangs as $lang_item) {
                $option_names[$lang_item['Code']] = $rlLang->getPhrase($item_info['pName'], $lang_item['Code'], null, true);
            }
        }

        $bcAStep[] = array(
            'name' => $lang['edit_item'] . ' (' . $option_names[RL_LANG_CODE] . ')'
        );

        $rlSmarty->assign('option_names', $option_names);
        $rlSmarty->assign('fb_page_info', $fb_page_info);
        $rlSmarty->assign('fbb_info', $fbb_info);
        $rlSmarty->assign('item_id', $item_info['ID']);

        if (!$_POST['fromPost']) {
            $_POST['icon'] = $item_info['Icon'];
            $_POST['key'] = $item_key;
            $_POST['path'] = $item_info['Path'];
            $_POST['status'] = $item_info['Status'];

            // Get FBB item lang data
            foreach ($rlFieldBoundBoxes->lang_elements['field_bound_items'] as $area) {
                $names = $rlDb->fetch(
                    array('Code', 'Value'),
                    array('Key' => 'field_bound_items+' . $area . '+' . $item_key),
                    "AND `Status` <> 'trash'",
                    null,
                    'lang_keys'
                );

                foreach ($names as $name) {
                    if ($area == 'des') {
                        $_POST['description_' . $name['Code']] = $name['Value'];
                    } else {
                        $_POST[$area][$name['Code']] = $name['Value'];
                    }
                }
            }
        } else {
            $path = $_POST['path'];
            $path = $rlValid->str2path($path);

            $errors = array();

            if (!$path) {
                $errors[] = str_replace(
                    '{field}',
                    '<b>' . $lang['fb_item_path'] . '</b>',
                    $lang['notice_field_empty']
                );
                $error_fields[] = 'path';
            } else {
                $exist_path = $rlDb->fetch(
                    array('Key'),
                    array('Path' => $path, 'Box_ID' => $fbb_info['ID']),
                    null,
                    null,
                    'field_bound_items',
                    'row'
                );

                if (!empty($exist_path) && $exist_path['Key'] != $item_info['Key']) {
                    $errors[] = str_replace('{path}', "<b>\"{$path}\"</b>", $lang['notice_page_path_exist']);
                    $error_fields[] = 'path';
                }
            }

            if (!$errors) {
                $resize = false;

                if ($fbb_info['Style'] == 'responsive') {
                    $pic_width = 323;
                    $pic_height = $fbb_info['Orientation'] == 'portrait' ? 436 : 210;
                    $resize = true;
                } else {
                    $pic_width = $fbb_info['Icons_width'];
                    $pic_height = $fbb_info['Icons_height'];
                    $resize = (bool) $fbb_info['Resize_icons'];
                }

                if ($_FILES['icon']['tmp_name'] && in_array($fbb_info['Style'], ['text_pic', 'icon', 'responsive'])) {
                    if ($rlFieldBoundBoxes->isImage($_FILES['icon']['tmp_name'])) {
                        $reefless->loadClass('Actions');
                        $reefless->loadClass('Resize');
                        $reefless->loadClass('Crop');

                        $file_ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
                        $tmp_location = RL_UPLOAD . 'tmp_fb_icon_' . $item_key . '_' . mt_rand() . time() . '.' . $file_ext;

                        if (move_uploaded_file($_FILES['icon']['tmp_name'], $tmp_location)) {
                            $icon_dir = 'fieldBoundBoxes/' . $fbb_info['Key'] . '/';
                            $icon_name = $item_key . '_icon_' . mt_rand() . time() . '.' . $file_ext;
                            $icon_file_name = $icon_dir . $icon_name;

                            if (RL_DS != '/') {
                                $icon_dir = str_replace('/', RL_DS, $icon_dir);
                            }

                            $reefless->rlMkdir(RL_FILES . $icon_dir);

                            $icon_file = RL_FILES . $icon_file_name;

                            if ($resize) {
                                $rlCrop->loadImage($tmp_location);
                                $rlCrop->cropBySize($pic_width, $pic_height, ccCENTRE);
                                $rlCrop->saveImage($icon_file, $config['img_quality']);
                                $rlCrop->flushImages();

                                $GLOBALS['rlResize']->resize(
                                    $icon_file,
                                    $icon_file,
                                    'C',
                                    array($pic_width, $pic_height),
                                    null,
                                    false
                                );
                            } else {
                                copy($tmp_location, $icon_file);
                            }
                        }
                        unlink($tmp_location);

                        if (is_readable($icon_file)) {
                            if ($item_info['Icon']) {
                                unlink(RL_FILES . $item_info['Icon']);
                            }
                        } else {
                            $errors[] = $lang['can_not_read_file'];
                        }
                    } else {
                        $errors[] = $lang['not_image_file'];
                    }
                }

                if ($_POST['svg_icon']) {
                    if ($item_info['Icon'] && false === strpos($item_info['Icon'], 'svg_icons')) {
                        unlink(RL_FILES . $item_info['Icon']);
                    }

                    $icon_file_name = 'fieldBoundBoxes/svg_icons/' . $_POST['svg_icon'];
                }
            }

            if ($errors) {
                $rlSmarty->assign('error_fields', $error_fields);
                $rlSmarty->assign('errors', $errors);
            } else {
                $update = array(
                    'fields' => array(
                        'Status' => $_POST['status']
                    ),
                    'where' => array(
                        'ID' => $item_info['ID']
                    ),
                );
                if ($path != $item_info['Path']) {
                    $update['fields']['Path'] = $path;
                }
                if ($icon_file_name) {
                    $update['fields']['Icon'] = $icon_file_name;
                }

                $rlActions->updateOne($update, 'field_bound_items');

                $data['item'] = $_POST;
                unset($data['item']['fromPost']);
                unset($data['item']['Status']);

                // Transform item description to sub-array of item post stack
                foreach ($allLangs as $lang_item) {
                    $data['item']['des'][$lang_item['Code']] = $data['item']['description_' . $lang_item['Code']];
                    unset($data['item']['description_' . $lang_item['Code']]);
                }

                $rlFieldBoundBoxes->addEditLangData($item_info['Key'], $data, $_GET['action'], 'field_bound_items');
            }
        }

        if ($_POST['fromPost'] && !$errors) {
            $rlFieldBoundBoxes->updateBoxContent($fbb_info['Key']);

            $message = $lang['item_edited'];
            $aUrl = array('controller' => $controller, 'box' => $fbb_info['Key']);

            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($message);
            $reefless->redirect($aUrl);
        }
    } elseif ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        switch ($_GET['action']) {
            case 'add':
                $bcAStep = $lang['add_block'];
                break;
            case 'edit':
                $bcAStep = $lang['edit_block'] . " (" . $lang['blocks+name+' . $_GET['box']] . ')';
                break;
        }

        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        $sides = array(
            'top' => $lang['top'],
            'bottom' => $lang['bottom'],
            'left' => $lang['fbb_icon_left'],
            'right' => $lang['fbb_icon_right']
        );
        $rlSmarty->assign('sides', $sides);

        $fields = $rlDb->fetch(
            array('Key'),
            array('Status' => 'active', 'Type' => 'select'),
            "AND `Condition` != 'years' AND `Key` != 'Category_ID' AND `Key` NOT REGEXP 'level[0-9]' AND `ID` > 0",
            null,
            'listing_fields'
        );

        $fields = $rlLang->replaceLangKeys($fields, 'listing_fields', 'name', RL_LANG_CODE, 'admin');
        
        foreach ($fields as $field) {
            $sql = "SELECT `Code`, `Value` FROM `{db_prefix}lang_keys` ";
            $sql .= "WHERE `Key` = 'listing_fields+name+{$field['Key']}'";
            $field_names_tmp = $rlDb->getAll($sql);

            foreach ($field_names_tmp as $fName_tmp) {
                $field_names[$field['Key']][$fName_tmp['Code']] = $fName_tmp['Value'];
            }
        }

        $rlSmarty->assign_by_ref('fields_names', $field_names);
        $rlSmarty->assign_by_ref('fields', $fields);

        $sections = $rlCategories->getCatTree(0, false, true);
        $rlSmarty->assign_by_ref('sections', $sections);

        $pages = $rlDb->fetch(
            array('ID', 'Key'),
            array('Tpl' => 1, 'Fbb_hidden' => 0, 'Status' => 'active'),
            "ORDER BY `Key`",
            null,
            'pages'
        );
        $pages = $rlLang->replaceLangKeys($pages, 'pages', array('name'), RL_LANG_CODE, 'admin');
        $rlSmarty->assign_by_ref('pages', $pages);

        $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));

        if ($_GET['action'] == 'edit') {
            $key = $rlValid->xSql($_GET['box']);

            $fbb_info = $rlDb->fetch(
                '*',
                array('Key' => $key),
                "AND `Status` <> 'trash'",
                null,
                'field_bound_boxes',
                'row'
            );
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['sorting'] = $box_info['Sorting'];

            // Prepare Field Bound Box post for Edit page
            unset($fbb_info['Status'], $box_info['Sorting'], $fbb_info['ID']);
            $_POST['fbb'] = array_change_key_case($fbb_info);
            $_POST['single_line_counter'] = $fbb_info['Orientation'] == 'landscape' ? 0 : 1;

            // Prepare Website Box post for Edit page
            $box_info = $rlDb->fetch(
                '*',
                array('Key' => $key),
                "AND `Status` <> 'trash'",
                null,
                'blocks',
                'row'
            );

            $_POST['box']['status'] = $box_info['Status'];
            $_POST['box']['side'] = $box_info['Side'];
            $_POST['box']['tpl'] = $box_info['Tpl'];
            $_POST['box']['header'] = $box_info['Header'];
            $_POST['box']['show_on_all'] = $box_info['Sticky'];
            $_POST['box']['cat_sticky'] = $box_info['Cat_sticky'];
            $_POST['box']['subcategories'] = $box_info['Subcategories'];
            $_POST['categories'] = explode(',', $box_info['Category_ID']);

            $m_pages = explode(',', $box_info['Page_ID']);
            foreach ($m_pages as $page_id) {
                $_POST['box']['pages'][$page_id] = $page_id;
            }

            $names = $rlDb->fetch(
                array('Code', 'Value'),
                array('Key' => 'blocks+name+' . $key),
                "AND `Status` <> 'trash'",
                null,
                'lang_keys'
            );

            foreach ($names as $nKey => $nVal) {
                $_POST['box']['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }

            // Prepare Website Page post for Edit page
            $page_info = $rlDb->fetch(
                '*',
                array('Key' => $key),
                "AND `Status` <> 'trash'",
                null,
                'pages',
                'row'
            );
            $_POST['page']['path'][$config['lang']] = $page_info['Path'];

            if ($config['multilingual_paths']) {
                foreach ($allLangs as $language) {
                    if ($language['Code'] == $config['lang']) {
                        continue;
                    }

                    $_POST['page']['path'][$language['Code']] = $page_info['Path_' . $language['Code']];
                }
            }

            // Prepare FBB page lang post data
            foreach ($rlFieldBoundBoxes->lang_elements['pages'] as $area) {
                $names = $rlDb->fetch(
                    array('Code', 'Value'),
                    array('Key' => 'pages+' . $area . '+' . $key),
                    "AND `Status` <> 'trash'",
                    null,
                    'lang_keys'
                );

                foreach ($names as $name) {
                    $_POST['page'][$area][$name['Code']] = $name['Value'];
                }
            }

            // Prepare SEO defaults post lang data
            foreach ($rlFieldBoundBoxes->lang_elements['fbb_defaults'] as $area) {
                $defaults = $rlDb->fetch(
                    array('Code', 'Value'),
                    array('Key' => 'fbb_defaults+' . $area . '+' . $key),
                    "AND `Status` <> 'trash'",
                    null,
                    'lang_keys'
                );

                foreach ($defaults as $name) {
                    if ($area == 'des') {
                        $_POST['default_des_' . $name['Code']] = $name['Value'];
                    } else {
                        $_POST['defaults'][$area][$name['Code']] = $name['Value'];
                    }
                }
            }
            unset($_SESSION['categories']);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            $data = $_POST;

            // Autofill major SEO data if admin did not fill it in
            if ($_GET['action'] == 'add') {
                foreach ($allLangs as $lang_item) {
                    // Autofill meta title
                    if (!$data['defaults']['title'][$lang_item['Code']]) {
                        $data['defaults']['title'][$lang_item['Code']] = '{item} ' . $data['box']['name'][$lang_item['Code']];
                    }

                    // Autofill H1
                    if (!$data['defaults']['h1'][$lang_item['Code']]) {
                        $data['defaults']['h1'][$lang_item['Code']] = '{item} ' . $data['box']['name'][$lang_item['Code']];
                    }
                }
            }

            // Transform default description to sub-array of defaults post stack
            foreach ($allLangs as $lang_item) {
                $data['defaults']['des'][$lang_item['Code']] = $data['default_des_' . $lang_item['Code']];
                unset($data['default_des_' . $lang_item['Code']]);
            }

            if ($_GET['action'] == 'add') {
                $key = $rlFieldBoundBoxes->uniqKeyByName($data['box']['name'][RL_LANG_CODE], 'blocks', 'fbb_');
            } else {
                $key = $rlValid->xSql($_GET['box']);
            }

            $box_names = $data['box']['name'];
            foreach ($allLangs as $lang_item) {
                if (empty($box_names[$lang_item['Code']])) {
                    $errors[] = str_replace(
                        '{field}',
                        "<b>" . $lang['name'] . "({$lang_item['name']})</b>",
                        $lang['notice_field_empty']
                    );
                    $error_fields[] = "box[name][{$lang_item['Code']}]";
                }
            }
            
            if (in_array($data['fbb']['style'], ['text_pic', 'icon'])) {
                if ($data['fbb']['icons_width'] < 16) {
                    $error_fields[] = "fbb[icons_width]";
                }
                if ($data['fbb']['icons_height'] < 16) {
                    $error_fields[] = "fbb[icons_height]";
                }

                if ($data['fbb']['icons_width'] < 16 || $data['fbb']['icons_height'] < 16) {
                    $errors[] = $rlLang->getSystem('fbb_picture_side_small');
                }
            }

            if ($data['fbb']['style'] == 'text_pic') {
                $data['fbb']['Orientation'] = $_POST['single_line_counter'] ? 'portrait' : 'landscape';
            }

            if ($data['fbb']['parent_page']) {
                $page_name = $data['page']['name'];
                $page_title = $data['page']['title'];
                foreach ($allLangs as $lang_item) {
                    if (empty($page_name[$lang_item['Code']])) {
                        $errors[] = str_replace(
                            '{field}',
                            "<b>" . $lang['name'] . "({$lang_item['name']})</b>",
                            $lang['notice_field_empty']
                        );
                        $error_fields[] = "page[name][{$lang_item['Code']}]";
                    }

                    if (empty($page_title[$lang_item['Code']])) {
                        $errors[] = str_replace(
                            '{field}',
                            "<b>" . $lang['title'] . "({$lang_item['name']})</b>",
                            $lang['notice_field_empty']
                        );
                        $error_fields[] = "page[title][{$lang_item['Code']}]";
                    }
                }
            } else {
                foreach ($rlFieldBoundBoxes->lang_elements['pages'] as $area) {
                    // Unset useless data
                    unset($data['page'][$area]);

                    // Delete useless data
                    if ($_GET['action'] == 'edit') {
                        $rlDb->delete(['Key' => 'pages+' . $area . '+' . $key], 'lang_keys', null, null);
                    }
                }
            }

            if ($_GET['action'] == 'add') {
                $field_key = $data['fbb']['field_key'];
                if (empty($field_key)) {
                    $errors[] = str_replace('{field}', "<b>\"{$lang['fb_field']}\"</b>", $lang['notice_field_empty']);
                    $error_fields[] = "fbb[field_key]";
                }
            }

            $path = &$data['page']['path'][$config['lang']];

            $data['page']['path'][$config['lang']] = $path ?: $rlValid->str2path($data['box']['name'][RL_LANG_CODE]);

            if ($rlDb->getOne('Key', "`Path` = '{$path}' AND `Key` != '{$key}'", 'pages')) {
                $errors[] = str_replace('{path}', "<b>\"{$path}\"</b>", $lang['notice_page_path_exist']);
                $error_fields[] = "page[path][{$config['lang']}]";
            }

            if ($config['multilingual_paths']) {
                foreach ($allLangs as $language) {
                    if ($language['Code'] == $config['lang'] || !$data['page']['path'][$language['Code']]) {
                        continue;
                    }

                    if ($rlDb->getOne('Key', "`Path_{$language['Code']}` = '{$data['page']['path'][$language['Code']]}' AND `Key` != '{$key}'", 'pages')) {
                        $errors[] = str_replace('{path}', "<b>\"{$data['page']['path'][$language['Code']]}\"</b>", $lang['notice_page_path_exist']);
                        $error_fields[] = "page[path][{$language['Code']}]";
                    }
                }
            }

            $box_side = $data['box']['side'];
            if (empty($box_side)) {
                $errors[] = str_replace('{field}', "<b>\"{$lang['block_side']}\"</b>", $lang['notice_select_empty']);
                $error_fields[] = 'box[side]';
            }

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                $rlFieldBoundBoxes->addEditAction($data, $key, $_GET['action']);

                /* TODO remove media on style change or not? */
                $unlink_media = $fbb_info && $fbb_info['Style'] != 'text' && $data['fbb']['style'] == 'text';
                $rlFieldBoundBoxes->buildBoxItems($key, $_GET['action'], false); // Pass $unlink_media as the third param to remove media on box style change

                $added_item_message = $rlDb->getOne('Multiple_items', "`Key` = '{$key}'", 'field_bound_boxes')
                ? $rlLang->getSystem('fbb_box_added_multiple')
                : $lang['notice_item_added'];

                $message = $_GET['action'] == 'edit' ? $lang['notice_item_edited'] : $added_item_message;
                $aUrl = array('controller' => $controller);

                if ($_GET['action'] == 'add') {
                    $aUrl['box'] = $key;
                }

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($message);
                $reefless->redirect($aUrl);
            }
        }
    }
}
