<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: BLOCKS.INC.PHP
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
        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];

        switch ($field) {
            // update related listing type options
            case 'Side':
                $block_key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'blocks');
                $block_data = explode('_', $block_key);

                if (in_array($block_data[0], array('ltcategories', 'ltcb'))) {
                    $update_field = $block_data[0] == 'ltcategories' ? 'Cat_position' : 'Ablock_position';
                    $update_type = array(
                        'fields' => array($update_field => $value),
                        'where'  => array('Key' => $block_data[1]),
                    );
                    $rlDb->updateOne($update_type, 'listing_types');
                }
                break;
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtBlocksUpdate');

        $rlDb->updateOne($updateData, 'blocks');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.`ID`, `T1`.`Status`,`T1`.`Position`, `T1`.`Tpl`, `T1`.`Side`, `T1`.`Key`, ";
    $sql .= "`T1`.`Type`, `T1`.`Header`, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}blocks` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('blocks+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    $rlHook->load('apExtBlocksModifyWhere', $sql);

    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtBlocksSql');

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Tpl'] = $data[$key]['Tpl'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Header'] = $data[$key]['Header'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Side'] = $GLOBALS['lang'][$data[$key]['Side']];
        $data[$key]['Type'] = $GLOBALS['lang'][$data[$key]['Type']];

        if (strpos($value['Key'], 'ltsb_') === 0 || strpos($value['Key'], 'ltcategories_') === 0 || strpos($value['Key'], 'ltcb_') === 0) {
            $listing_type = str_replace(array('ltsb_', 'ltcategories_', 'ltcb_'), '', $value['Key']);
            $data[$key]['name'] .= ' (' . $rlListingTypes->types[$listing_type]['name'] . ')';
        }
    }

    $rlHook->load('apExtBlocksData');

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    $b_key = $rlValid->xSql($_GET['block']);
    $ltma_block = !is_numeric(strpos($b_key, 'ltma_'));

    $display = array(
        'pages'      => $ltma_block,
        'categories' => $ltma_block,
    );

    $rlHook->load('apPhpBlocksTop');

    $reefless->loadClass('Categories');
    $reefless->loadClass('AccountTypes');

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_block'] : $lang['edit_block'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // get current block info
        $block_info = $rlDb->fetch('*', array('Key' => $b_key), "AND `Status` <> 'trash'", null, 'blocks', 'row');
        $rlSmarty->assign_by_ref('block', $block_info);

        // Remove banners positions for category and featured account boxes
        if ($GLOBALS['l_block_excluded']
            && preg_match('/^(' . implode('|', $GLOBALS['l_block_excluded']) . ')/', $block_info['Key'])
        ) {
            unset($l_block_sides['header_banner'], $l_block_sides['integrated_banner']);
        }

        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        // get categories/section
        if ($display['categories'] && !in_array($block_info['Key'], $rlAccountTypes->getSystemBoxes())) {
            $sections = $rlCategories->getCatTree(0, false, true);
            $rlSmarty->assign_by_ref('sections', $sections);
        }

        // get pages list
        if ($display['pages']) {
            $where = "AND `Status` = 'active' AND `Controller` != 'search_map' ";

            if (in_array($block_info['Key'], $rlAccountTypes->getSystemBoxes())) {
                $where .= "AND `Controller` = 'account_type' ";
            }

            if (in_array($block_info['Key'], ['account_page_location', 'account_page_info'])) {
                 $where .= "OR `Key` = 'my_messages' ";
            }

            $rlHook->load('apPhpBlocksGetPageWhere', $where, $block_info);

            $where .= "ORDER BY `Key`";
            $pages_list = $rlDb->fetch(array('ID', 'Key'), array('Tpl' => 1), $where, null, 'pages');
            $pages_list = $rlLang->replaceLangKeys($pages_list, 'pages', array('name'), RL_LANG_CODE, 'admin');
            $rlSmarty->assign_by_ref('pages_list', $pages_list);
        }

        $allowPageSticky = true;
        if (in_array($block_info['Key'], $rlAccountTypes->getSystemBoxes())
            || in_array($block_info['Key'], ['account_page_location', 'account_page_info'])
        ) {
            $allowPageSticky = false;
        }

        $rlSmarty->assign_by_ref('allowPageSticky', $allowPageSticky);

        // clear cache
        if (!$_POST['submit'] && !$_POST['xjxfun']) {
            unset($_SESSION['categories']);
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            unset($_SESSION['categories']);

            $_POST['key'] = $block_info['Key'];
            $_POST['status'] = $block_info['Status'];
            $_POST['side'] = $block_info['Side'];
            $_POST['tpl'] = $block_info['Tpl'];
            $_POST['header'] = $block_info['Header'];
            $_POST['type'] = $block_info['Type'];

            if ($block_info['Type'] == 'html') {
                $content = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'blocks+content+' . $block_info['Key']), "AND `Status` <> 'trash'", null, 'lang_keys');

                foreach ($content as $cKey => $cVal) {
                    $_POST['html_content_' . $content[$cKey]['Code']] = $content[$cKey]['Value'];
                }
            } else {
                $_POST['content'] = $block_info['Content'];
            }

            $_POST['type'] = $block_info['Type'];

            if ($display['pages']) {
                $_POST['show_on_all'] = $block_info['Sticky'];

                $m_pages = explode(',', $block_info['Page_ID']);
                foreach ($m_pages as $page_id) {
                    $_POST['pages'][$page_id] = $page_id;
                }
                unset($m_pages);
            }

            if ($display['categories']) {
                $_POST['cat_sticky'] = $block_info['Cat_sticky'];
                $_POST['subcategories'] = $block_info['Subcategories'];
                $_POST['categories'] = explode(',', $block_info['Category_ID']);
            }

            // get names
            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'blocks+name+' . $b_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $nKey => $nVal) {
                $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }

            $rlHook->load('apPhpBlocksPost');
        }

        // get parent points
        if ($display['categories'] && $_POST['categories']) {
            $rlCategories->parentPoints($_POST['categories']);
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            $f_key = $_POST['key'];

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $_SESSION['categories'] = $_POST['categories'];

            /* check key exist (in add mode only) */
            if ($_GET['action'] == 'add') {
                /* check key */
                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                if (strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                    $error_fields[] = 'key';
                }

                $exist_key = $rlDb->fetch(array('Key', 'Status'), array('Key' => $f_key), null, null, 'blocks', 'row');

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"" . $f_key . "\"</b>", $lang['notice_block_exist']);

                    if ($exist_key['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = 'key';
                }
            }

            $f_key = $rlValid->str2key($f_key);

            /* check name */
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['name'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "name[{$lval['Code']}]";
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            /* check side */
            $f_side = $_POST['side'];

            if (empty($f_side)) {
                $errors[] = str_replace('{field}', "<b>\"" . $lang['block_side'] . "\"</b>", $lang['notice_select_empty']);
                $error_fields[] = 'side';
            }

            if (!$block_info['Readonly']) {
                /* check type */
                $f_type = $_POST['type'];

                if (empty($f_type)) {
                    $errors[] = str_replace('{field}', "<b>\"" . $lang['block_type'] . "\"</b>", $lang['notice_select_empty']);
                    $error_fields[] = 'type';
                }

                /* check content */
                $f_content = $_POST['content'];

                if ($f_type == 'html') {
                    foreach ($allLangs as $lkey => $lval) {
                        if (empty($_POST['html_content_' . $allLangs[$lkey]['Code']])) {
                            $errors[] = str_replace('{field}', "<b>" . $lang['content'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                            $error_fields[] = 'html_content_' . $lval['Code'];
                        }
                    }
                } else {
                    if (empty($f_content)) {
                        $errors[] = str_replace('{field}', "<b>\"" . $lang['content'] . "\"</b>", $lang['notice_field_empty']);
                        $error_fields[] = 'content';
                    }
                }
            }

            $rlHook->load('apPhpBlocksValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`");

                    // write main, block information
                    $data = array(
                        'Key'           => $f_key,
                        'Status'        => $_POST['status'],
                        'Position'      => $position['max'] + 1,
                        'Side'          => $f_side,
                        'Type'          => $f_type,
                        'Tpl'           => $_POST['tpl'],
                        'Header'        => $_POST['header'],
                        'Page_ID'       => implode(',', $_POST['pages']),
                        'Category_ID'   => $_POST['cat_sticky'] ? '' : implode(',', $_POST['categories']),
                        'Subcategories' => empty($_POST['subcategories']) ? 0 : 1,
                        'Sticky'        => empty($_POST['show_on_all']) ? 0 : 1,
                        'Cat_sticky'    => empty($_POST['cat_sticky']) ? 0 : 1,
                    );

                    if ($f_type != 'html') {
                        $data['Content'] = $f_content;
                    }

                    $rlHook->load('apPhpBlocksBeforeAdd');

                    if ($action = $rlDb->insertOne($data, 'blocks')) {
                        $rlHook->load('apPhpBlocksAfterAdd');

                        // write name's phrases
                        foreach ($allLangs as $language) {
                            $lang_keys[] = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'box',
                                'Status'     => 'active',
                                'Key'        => 'blocks+name+' . $f_key,
                                'Value'      => $f_name[$language['Code']],
                                'Target_key' => $f_key,
                            );

                            // add content for html block
                            if ($f_type == 'html') {
                                $lang_keys[] = array(
                                    'Code'       => $language['Code'],
                                    'Module'     => 'box',
                                    'Status'     => 'active',
                                    'Key'        => 'blocks+content+' . $f_key,
                                    'Value'      => $_POST['html_content_' . $language['Code']],
                                    'Target_key' => $f_key,
                                );
                            }
                        }

                        $rlDb->insert($lang_keys, 'lang_keys');

                        $message = $lang['block_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new block (MYSQL problems)", E_USER_WARNING);
                        $rlDebug->logger("Can't add new block (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_data = array(
                        'fields' => array(
                            'Status' => $_POST['status'],
                            'Side'   => $f_side,
                            'Tpl'    => $_POST['tpl'],
                            'Header' => $_POST['header'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    if ($display['pages']) {
                        $update_data['fields']['Page_ID'] = implode(',', $_POST['pages']);
                        $update_data['fields']['Sticky'] = empty($_POST['show_on_all']) ? 0 : 1;
                    }

                    if ($display['categories']) {
                        $categories_ids = $_POST['cats_sticky']
                            ? ''
                            : ($_POST['categories'] ? implode(',', $_POST['categories']) : '');
                        $update_data['fields']['Category_ID'] = $categories_ids;
                        $update_data['fields']['Subcategories'] = empty($_POST['subcategories']) ? 0 : 1;
                        $update_data['fields']['Cat_sticky'] = empty($_POST['cat_sticky']) ? 0 : 1;
                    }

                    if (!$block_info['Readonly']) {
                        $update_data['fields']['Type'] = $f_type;
                        $update_data['fields']['Content'] = $f_content;
                    }

                    $rlHook->load('apPhpBlocksBeforeEdit');

                    $action = $rlDb->updateOne($update_data, 'blocks');

                    $rlHook->load('apPhpBlocksAfterEdit');

                    /* update related listing type options */
                    if ($action) {
                        $block_data = explode('_', $f_key);

                        if (in_array($block_data[0], array('ltcategories', 'ltcb'))) {
                            $update_field = $block_data[0] == 'ltcategories' ? 'Cat_position' : 'Ablock_position';
                            $update_type = array(
                                'fields' => array($update_field => $f_side),
                                'where'  => array('Key' => $block_data[1]),
                            );
                            $rlDb->updateOne($update_type, 'listing_types');
                        }
                    }
                    /* update related listing type options end */

                    foreach ($allLangs as $language) {
                        $condition = "`Key` = 'blocks+name+{$f_key}' AND `Code` = '{$language['Code']}'";
                        $value     = $_POST['name'][$language['Code']];

                        if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                            // edit name's values
                            $update_names = array(
                                'fields' => array(
                                    'Value'      => $value,
                                    'Module'     => 'box',
                                    'Target_key' => $f_key,
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'blocks+name+' . $f_key,
                                ),
                            );

                            if ($value != $rlDb->getOne('Value', $condition, 'lang_keys')) {
                                $update_names['fields']['Modified'] = '1';
                            }

                            // update
                            $rlDb->updateOne($update_names, 'lang_keys');
                        } else {
                            // insert names
                            $insert_names = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'box',
                                'Key'        => 'blocks+name+' . $f_key,
                                'Value'      => $value,
                                'Target_key' => $f_key,
                            );

                            // insert
                            $rlDb->insertOne($insert_names, 'lang_keys');
                        }

                        if ($f_type == 'html') {
                            $condition = "`Key` = 'blocks+content+{$f_key}' AND `Code` = '{$language['Code']}'";
                            $value     = $_POST['html_content_' . $language['Code']];

                            if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                                $lang_keys_content = array(
                                    'fields' => array(
                                        'Value'      => $value,
                                        'Module'     => 'box',
                                        'Target_key' => $f_key,

                                    ),
                                    'where' => array(
                                        'Code'   => $language['Code'],
                                        'Key'    => 'blocks+content+' . $f_key,
                                    )
                                );

                                if ($value != $rlDb->getOne('Value', $condition, 'lang_keys')) {
                                    $lang_keys_content['fields']['Modified'] = '1';
                                }

                                // update
                                $rlDb->updateOne($lang_keys_content, 'lang_keys');
                            } else {
                                // insert content
                                $insert_content = array(
                                    'Code'       => $language['Code'],
                                    'Module'     => 'box',
                                    'Key'        => 'blocks+content+' . $f_key,
                                    'Value'      => $value,
                                    'Target_key' => $f_key,
                                );

                                // insert
                                $rlDb->insertOne($insert_content, 'lang_keys');
                            }
                        }
                    }

                    $message = $lang['block_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    unset($_SESSION['categories']);

                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
        $rlXajax->registerFunction(array('getCatLevel', $rlCategories, 'ajaxGetCatLevel'));
        $rlXajax->registerFunction(array('openTree', $rlCategories, 'ajaxOpenTree'));
    }

    $reefless->loadClass('Admin', 'admin');

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteBlock', $rlAdmin, 'ajaxDeleteBlock'));
}
