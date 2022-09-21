<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PAGES.INC.PHP
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
        $key = $rlValid->xSql($_GET['key']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtPagesUpdate');

        $rlDb->updateOne($updateData, 'pages');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Value` AS `name` ";
    $sql .= "FROM `{db_prefix}pages` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('pages+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";

    // add search criteria
    if ($_GET['action'] == 'search') {
        foreach (array('Name', 'Page_type', 'Status') as $item) {
            if ($_GET[$item] && $s_value = $rlValid->xSql($_GET[$item])) {
                switch ($item) {
                    case 'Name':
                        $sql .= "AND `T2`.`Value` LIKE '%{$s_value}%' ";
                        break;

                    default:
                        $sql .= "AND `T1`.`{$item}` = '{$s_value}' ";
                        break;
                }
            }
        }
    }

    if ($sort) {
        $sortField = $sort == 'name' ? "`T2`.`Value`" : "`T1`.`{$sort}`";
        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtPagesSql');

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Page_type'] = $lang[$data[$key]['Page_type']];
        $data[$key]['Login'] = $data[$key]['Login'] ? $lang['yes'] : $lang['no'];
        $data[$key]['No_follow'] = $data[$key]['No_follow'] ? $lang['yes'] : $lang['no'];
        $data[$key]['Status'] = $lang[$data[$key]['Status']];
    }

    $rlHook->load('apExtPagesData');

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    /**
     * List of key of pages which cannot have multilingual paths
     * @since 4.9.0
     */
    $nonMultilingualPages = ['payment'];
    $rlSmarty->assign_by_ref('nonMultilingualPages', $nonMultilingualPages);

    $rlHook->load('apPhpPagesTop');

    /* get account types */
    $reefless->loadClass('Account');
    $account_types = $rlAccount->getAccountTypes();
    $rlSmarty->assign_by_ref('account_types', $account_types);

    /* additional bread crumb step */
    if ($_GET['action']) {
        $bcAStep = $_GET['action'] == 'add' ? $lang['add_page'] : $lang['edit_page'];
    }

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        /* get all languages */
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        /* get all pages */
        $all_pages = $rlDb->fetch(array('ID', 'Key'), array('Status' => 'active'), "AND `Key` <> 'home' ORDER BY `Key`", null, 'pages');
        $all_pages = $rlLang->replaceLangKeys($all_pages, 'pages', array('name'));
        $rlSmarty->assign_by_ref('all_pages', $all_pages);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $key = $rlValid->xSql($_GET['page']);

            // get current page info
            $info = $rlDb->fetch('*', array('Key' => $key), "AND `Status` <> 'trash'", null, 'pages', 'row');

            $_POST['key'] = $info['Key'];
            $_POST['status'] = $info['Status'];
            $_POST['login'] = $info['Login'];
            $_POST['page_type'] = $info['Page_type'];
            $_POST['deny'] = explode(',', $info['Deny']);
            $_POST['tpl'] = $info['Tpl'];
            $_POST['no_follow'] = $info['No_follow'];

            $aMenus = explode(',', $info['Menus']);
            foreach ($aMenus as $amKey => $amVal) {
                $_POST['menus'][$amVal] = $amVal;
            }

            // get names
            $names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+name+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($names as $nKey => $nVal) {
                $_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
            }

            // get titles
            $titles = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+title+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($titles as $tKey => $tVal) {
                $_POST['title'][$titles[$tKey]['Code']] = $titles[$tKey]['Value'];
            }

            // get h1 heading
            $h1_headings = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+h1+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($h1_headings as $tKey => $tVal) {
                $_POST['h1_heading'][$h1_headings[$tKey]['Code']] = $h1_headings[$tKey]['Value'];
            }

            // get meta description
            $meta_description = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+meta_description+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_description as $tKey => $tVal) {
                $_POST['meta_description'][$meta_description[$tKey]['Code']] = $meta_description[$tKey]['Value'];
            }

            // get meta keywords
            $meta_keywords = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+meta_keywords+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($meta_keywords as $tKey => $tVal) {
                $_POST['meta_keywords'][$meta_keywords[$tKey]['Code']] = $meta_keywords[$tKey]['Value'];
            }

            // content
            $content = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'pages+content+' . $key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($content as $cKey => $cVal) {
                $_POST['content_' . $content[$cKey]['Code']] = $content[$cKey]['Value'];
            }

            if ($info['Page_type'] == 'system') {
                $_POST['controller'] = $info['Controller'];
            } elseif ($info['Page_type'] == 'external') {
                $_POST['external_url'] = $info['Controller'];
            }

            $_POST['path'][$config['lang']] = $info['Path'];

            if ($config['multilingual_paths']) {
                foreach ($allLangs as $langKey => $langData) {
                    if ($langKey === $config['lang']) {
                        continue;
                    }

                    $_POST['path'][str_replace('Path_', '', $langKey)] = $info["Path_{$langKey}"];

                }
            }

            $rlHook->load('apPhpPagesPost');
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            /* load the utf8 lib */
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $_POST['key'];
            $f_page_type = $_POST['page_type'];
            $f_menus = $_POST['menus'];

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

                $exist_key = $rlDb->fetch(array('Key', 'Status'), array('Key' => $f_key), null, null, 'pages', 'row');

                if (!empty($exist_key)) {
                    $exist_error = str_replace('{key}', "<b>\"{$f_key}\"</b>", $lang['notice_page_exist']);

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

            /* check title */
            $f_title = $_POST['title'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_title[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', "<b>" . $lang['title'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    $error_fields[] = "title[{$lval['Code']}]";
                }

                $f_titles[$allLangs[$lkey]['Code']] = $f_title[$allLangs[$lkey]['Code']];
            }

            /* check h1 */
            $f_h1_heading = $_POST['h1_heading'];

            foreach ($allLangs as $lkey => $lval) {
                $f_h1_heading[$allLangs[$lkey]['Code']] = $f_h1_heading[$allLangs[$lkey]['Code']];
            }

            /* check path */
            if ($f_page_type != 'external') {
                $f_path = $_POST['path'][$config['lang']];

                if ($f_key != 'home') {
                    if (!utf8_is_ascii($f_path)) {
                        $f_path = utf8_to_ascii($f_path);
                    }

                    if (strlen($f_path) < 3) {
                        $errors[] = $lang['incorrect_page_address'];
                        $error_fields[] = "path";
                    }
                }

                $f_path = $rlValid->str2path($f_path);

                if ($config['multilingual_paths']) {
                    $multiPaths = [];

                    foreach ($_POST['path'] as $langKey => $pathValue) {
                        if ($langKey === $config['lang'] || $pathValue === '') {
                            continue;
                        }

                        $multiPaths[$langKey] = $rlValid->str2multiPath($pathValue);
                    }
                }

                $existPath = $rlDb->fetch(
                    ['Key', 'Status'],
                    ['Path' => $f_path],
                    "AND `Key` != '{$f_key}'",
                    null, 'pages', 'row'
                );

                if (!empty($existPath)) {
                    $exist_error = str_replace('{path}', "<b>\"" . $f_path . "\"</b>", $lang['notice_page_path_exist']);

                    if ($existPath['Status'] == 'trash') {
                        $exist_error .= " <b>(" . $lang['in_trash'] . ")</b>";
                    }

                    $errors[]       = $exist_error;
                    $error_fields[] = "path";
                } elseif (substr($f_key, 0, 3) == 'lt_') {
                    $existPath = $rlDb->fetch(array('Key'), array('Path' => $f_path), null, null, 'categories', 'row');

                    if (!empty($existPath)) {
                        $errors[] = str_replace('{path}', "<b>\"" . $f_path . "\"</b>", $lang['notice_page_path_exist']);
                        $error_fields[] = "path";
                    }
                }

                preg_match('/\-[0-9]+$/', $f_path, $matches);
                if (!empty($matches)) {
                    $errors[] = $lang['category_url_listing_logic'];
                    $error_fields[] = "path";
                }

                if (!$errors && $config['multilingual_paths'] && $multiPaths) {
                    foreach ($multiPaths as $langKey => $multiPath) {
                        $existError = false;

                        // Check for existing path
                        $existPathSql = "SELECT `T1`.`ID` FROM `{db_prefix}pages` AS `T1` ";
                        $existPathSql .= "WHERE ";
                        $additionalSQL = '';

                        foreach ($languages as $languageKey => $languageData) {
                            if ($languageKey === $config['lang']) {
                                continue;
                            }

                            $additionalSQL = $additionalSQL
                                ? $additionalSQL . " OR `Path_{$languageKey}` = '{$multiPath}'"
                                : "`Path_{$languageKey}` = '{$multiPath}'";
                        }

                        $existPathSql .= '(' . $additionalSQL . ')';
                        $existPathSql .= " AND `T1`.`Key` <> '{$f_key}'";
                        $existPath = $rlDb->getRow($existPathSql);

                        if (!empty($existPath)) {
                            $existError = str_replace(
                                '{path}',
                                "<b>\"{$multiPath}\"</b>",
                                $lang['notice_page_path_exist']
                            );

                            if ($existPath['Status'] === 'trash') {
                                $existError .= " <b>({$lang['in_trash']})</b>";
                            }

                            $errors[]       = $existError;
                            $error_fields[] = "path[{$langKey}]";
                        }

                        if (!$existError) {
                            $existPath = $rlDb->fetch(
                                ['Status'],
                                ['Path' => $multiPath],
                                "AND `Key` <> '{$f_key}'",
                                null, 'pages', 'row'
                            );
                        }

                        if (!empty($existPath)) {
                            $existError = str_replace(
                                '{path}',
                                "<b>\"{$multiPath}\"</b>",
                                $lang['notice_page_path_exist']
                            );

                            if ($existPath['Status'] === 'trash') {
                                $existError .= " <b>({$lang['in_trash']})</b>";
                            }

                            $errors[]       = $existError;
                            $error_fields[] = "path[{$langKey}]";
                        }

                        if (!$existError && substr($f_key, 0, 3) == 'lt_') {
                            $existPath = $rlDb->fetch(['Key'], ['Path' => $multiPath], null, null, 'categories', 'row');

                            if (!empty($existPath)) {
                                $existError     = true;
                                $errors[]       = str_replace(
                                    '{path}',
                                    "<b>\"{$multiPath}\"</b>",
                                    $lang['notice_page_path_exist']
                                );
                                $error_fields[] = "path[{$langKey}]";
                            }
                        }

                        preg_match('/\-[0-9]+$/', $multiPath, $matches);

                        if (!empty($matches)) {
                            $errors[]       = $lang['category_url_listing_logic'];
                            $error_fields[] = "path[{$langKey}]";
                        }
                    }
                }
            } else {
                $f_path = 'external';
            }

            /* check page type */
            if (empty($f_page_type)) {
                $errors[] = $lang['notice_no_type_chose'];
                $error_fields[] = "page_type";
            }

            if ($f_page_type == 'system') {
                $f_controller = $_POST['controller'];

                if ($_GET['action'] == 'edit') {
                    $info = $rlDb->fetch('*', array('Key' => $f_key), "AND `Status` <> 'trash'", null, 'pages', 'row');
                }

                if ($info['Plugin']) {
                    $inc_file = RL_PLUGINS . $info['Plugin'] . RL_DS . $f_controller . ".inc.php";
                    $tpl_controller_file = RL_PLUGINS . $info['Plugin'] . RL_DS . $f_controller . ".tpl";
                } else {
                    $inc_file = RL_CONTROL . $f_controller . '.inc.php';

                    $tpl_controller_dir = RL_ROOT . 'templates' . RL_DS . $config['template'];
                    $tpl_controller_dir .= RL_DS . 'controllers' . RL_DS . $f_controller . '/';

                    $tpl_controller_file = RL_ROOT . 'templates' . RL_DS . $config['template'];
                    $tpl_controller_file .= RL_DS . 'tpl' . RL_DS . 'controllers' . RL_DS;
                    $tpl_controller_file .= $f_controller . '.tpl';

                    $tpl_controller_file_core = RL_ROOT . 'templates' . RL_DS . 'template_core';
                    $tpl_controller_file_core .= RL_DS . 'tpl' . RL_DS . 'controllers' . RL_DS;
                    $tpl_controller_file_core .= $f_controller . '.tpl';

                    $tpl_controller_dir_core = RL_ROOT . 'templates' . RL_DS . 'template_core';
                    $tpl_controller_dir_core .= RL_DS . 'controllers' . RL_DS . $f_controller . '/';
                }

                $inc_file_n = str_replace(RL_DS, '/', substr($inc_file, strlen(RL_ROOT)));
                $tpl_file_n = str_replace(RL_DS, '/', substr($tpl_controller_file, strlen(RL_ROOT)));

                if (empty($f_controller)) {
                    $errors[] = str_replace('{field}', '<b>"' . $lang['page_controller'] . '"</b>', $lang['notice_field_empty']);
                } elseif (
                    !is_file($inc_file)
                    || (!is_file($tpl_controller_file)
                        && !is_file($tpl_controller_file_core)
                        && !is_dir($tpl_controller_dir)
                        && !is_dir($tpl_controller_dir_core)
                    )
                ) {
                    $errors[] = str_replace(array('{inc_file}', '{tpl_file}'), array($inc_file_n, $tpl_file_n), $lang['notice_controller_no_files']);
                    $_POST['controller'] = $rlDb->getOne("Controller", "`Key` = '" . $f_key . "'", "pages");
                }
            } elseif ($f_page_type == 'external') {
                $f_external = $_POST['external_url'];

                if (!$rlValid->isUrl($f_external)) {
                    $errors[] = str_replace('{field}', '<b>"' . $lang['external_url'] . '"</b>', $lang['notice_field_incorrect']);
                    $error_fields[] = "external";
                }
            } elseif ($f_page_type == 'static') {
                foreach ($allLangs as $lkey => $lval) {
                    if (empty($_POST['content_' . $allLangs[$lkey]['Code']])) {
                        $errors[] = str_replace('{field}', "<b>" . $lang['page_content'] . "({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
                    }

                    $f_content[$allLangs[$lkey]['Code']] = $_POST['content_' . $allLangs[$lkey]['Code']];
                }
            }

            $target_key = $f_page_type == 'static' ? 'static' : $f_controller;

            $rlHook->load('apPhpPagesValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                /* add/edit action */
                if ($_GET['action'] == 'add') {
                    // get max position
                    $position   = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages`");

                    // write main page information
                    $data = array(
                        'Key'       => $f_key,
                        'Status'    => $_POST['status'],
                        'Position'  => $position['max'] + 1,
                        'Page_type' => $f_page_type,
                        'Login'     => $_POST['login'],
                        'Path'      => $f_path,
                        'Tpl'       => $f_page_type == 'system' ? '1' : $_POST['tpl'],
                        'Menus'     => $f_menus ? implode(',', $f_menus) : '',
                        'Deny'      => $_POST['deny'] ? implode(',', $_POST['deny'])  : '',
                        'Modified'  => 'NOW()',
                        'No_follow' => $_POST['no_follow'],
                    );

                    if ($f_page_type == 'system') {
                        $data['Controller'] = $f_controller;
                    } elseif ($f_page_type == 'external') {
                        $data['Controller'] = $f_external;
                    } elseif ($f_page_type == 'static') {
                        $data['Controller'] = 'static';
                    }

                    if ($config['multilingual_paths'] && $multiPaths) {
                        foreach ($allLangs as $langKey => $langData) {
                            if ($langKey === $config['lang']) {
                                continue;
                            }

                            $data["Path_{$langKey}"] = $multiPaths[$langKey];
                        }
                    }

                    $rlHook->load('apPhpPagesBeforeAdd');

                    if ($action = $rlDb->insertOne($data, 'pages')) {
                        $rlHook->load('apPhpPagesAfterAdd');

                        // save phrases & multi path's
                        foreach ($allLangs as $key => $value) {
                            // save names
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'pages+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );

                            // save titles
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'frontEnd',
                                'Status' => 'active',
                                'Key'    => 'pages+title+' . $f_key,
                                'Value'  => $f_titles[$allLangs[$key]['Code']],
                            );

                            // save h1s
                            $lang_keys[] = array(
                                'Code'       => $allLangs[$key]['Code'],
                                'Module'     => 'frontEnd',
                                'Status'     => 'active',
                                'Key'        => 'pages+h1+' . $f_key,
                                'Value'      => $f_h1_heading[$allLangs[$key]['Code']],
                                'Target_key' => $target_key,
                            );

                            // save meta description
                            $lang_keys[] = array(
                                'Code'       => $allLangs[$key]['Code'],
                                'Module'     => 'frontEnd',
                                'Status'     => 'active',
                                'Key'        => 'pages+meta_description+' . $f_key,
                                'Value'      => $_POST['meta_description'][$allLangs[$key]['Code']],
                                'Target_key' => $target_key,
                            );

                            // save meta keywords
                            $lang_keys[] = array(
                                'Code'       => $allLangs[$key]['Code'],
                                'Module'     => 'frontEnd',
                                'Status'     => 'active',
                                'Key'        => 'pages+meta_keywords+' . $f_key,
                                'Value'      => $_POST['meta_keywords'][$allLangs[$key]['Code']],
                                'Target_key' => $target_key,
                            );

                            // save static content
                            if ($f_page_type == 'static') {
                                $lang_keys[] = array(
                                    'Code'       => $allLangs[$key]['Code'],
                                    'Module'     => 'common',
                                    'Status'     => 'active',
                                    'Key'        => 'pages+content+' . $f_key,
                                    'Value'      => $f_content[$allLangs[$key]['Code']],
                                    'Target_key' => $target_key,
                                );
                            }
                        }

                        $rlDb->insert($lang_keys, 'lang_keys');

                        $message = $lang['page_added'];
                        $aUrl = array("controller" => $controller);
                    } else {
                        trigger_error("Can't add new page (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new page (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    // Enable/Disable "design" option for page
                    if ($f_page_type == 'system') {
                        $f_tpl = in_array($f_key, ['rss_feed', 'print']) ? '0' : '1';
                    } else {
                        $f_tpl = $_POST['tpl'];
                    }

                    $update_data = array(
                        'fields' => array(
                            'Status'    => $_POST['status'],
                            'Page_type' => $f_page_type,
                            'Login'     => $_POST['login'],
                            'Path'      => $f_path,
                            'Tpl'       => $f_tpl,
                            'Menus'     => $f_menus ? implode(',', $f_menus) : '',
                            'Deny'      => $_POST['deny'] ? implode(',', $_POST['deny']) : '',
                            'Modified'  => 'NOW()',
                            'No_follow' => $_POST['no_follow'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    if ($f_page_type == 'system') {
                        $update_data['fields']['Controller'] = $f_controller;
                    } elseif ($f_page_type == 'external') {
                        $update_data['fields']['Controller'] = $f_external;
                    } elseif ($f_page_type == 'static') {
                        $update_data['fields']['Controller'] = 'static';
                    }

                    if ($config['multilingual_paths'] && $multiPaths) {
                        foreach ($allLangs as $langKey => $langData) {
                            if ($langKey === $config['lang']) {
                                continue;
                            }

                            $update_data['fields']["Path_{$langKey}"] = $multiPaths[$langKey];
                        }
                    }

                    $rlHook->load('apPhpPagesBeforeEdit');

                    $action = $rlDb->updateOne($update_data, 'pages');

                    $rlHook->load('apPhpPagesAfterEdit');

                    // edit name's values
                    foreach ($allLangs as $language) {
                        $condition = "`Key` = 'pages+name+{$f_key}' AND `Code` = '{$language['Code']}'";
                        $value     = $f_name[$language['Code']];

                        if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                            // edit name
                            $update_names = array(
                                'fields' => array(
                                    'Value' => $value,
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'pages+name+' . $f_key,
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
                                'Code'   => $language['Code'],
                                'Module' => 'common',
                                'Key'    => 'pages+name+' . $f_key,
                                'Value'  => $value,
                            );

                            // insert
                            $rlDb->insertOne($insert_names, 'lang_keys');
                        }

                        $condition = "`Key` = 'pages+title+{$f_key}' AND `Code` = '{$language['Code']}'";
                        $value     = $f_titles[$language['Code']];

                        if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                            // edit title
                            $update_titles = array(
                                'fields' => array(
                                    'Value'  => $value,
                                    'Module' => 'frontEnd'
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'pages+title+' . $f_key,
                                ),
                            );

                            if ($value != $rlDb->getOne('Value', $condition, 'lang_keys')) {
                                $update_titles['fields']['Modified'] = '1';
                            }

                            // update
                            $rlDb->updateOne($update_titles, 'lang_keys');
                        } else {
                            // insert titles
                            $insert_titles = array(
                                'Code'   => $language['Code'],
                                'Module' => 'frontEnd',
                                'Key'    => 'pages+title+' . $f_key,
                                'Value'  => $value,
                            );

                            // insert
                            $rlDb->insertOne($insert_titles, 'lang_keys');
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'pages+h1+{$f_key}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                            // edit h1
                            $update_h1s = array(
                                'fields' => array(
                                    'Value'      => $f_h1_heading[$language['Code']],
                                    'Module'     => 'frontEnd',
                                    'Target_key' => $target_key,
                                ),
                                'where'  => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'pages+h1+' . $f_key,
                                ),
                            );

                            // update
                            $rlDb->updateOne($update_h1s, 'lang_keys');
                        } else {
                            // insert h1
                            $insert_h1s = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'frontEnd',
                                'Key'        => 'pages+h1+' . $f_key,
                                'Value'      => $f_h1_heading[$language['Code']],
                                'Target_key' => $target_key,
                            );

                            // insert
                            $rlDb->insertOne($insert_h1s, 'lang_keys');
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'pages+meta_description+{$f_key}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                            $update_meta_description = array(
                                'fields' => array(
                                    'Value'      => $_POST['meta_description'][$language['Code']],
                                    'Module'     => 'frontEnd',
                                    'Target_key' => $target_key,
                                ),
                                'where' => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'pages+meta_description+' . $f_key,
                                )
                            );

                            // update
                            $rlDb->updateOne($update_meta_description, 'lang_keys');
                        } else {
                            $insert_meta_description = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'frontEnd',
                                'Key'        => 'pages+meta_description+' . $f_key,
                                'Value'      => $_POST['meta_description'][$language['Code']],
                                'Target_key' => $target_key,
                            );

                            // insert
                            $rlDb->insertOne($insert_meta_description, 'lang_keys');
                        }

                        if ($rlDb->getOne('ID', "`Key` = 'pages+meta_keywords+{$f_key}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                            $update_meta_keywords = array(
                                'fields' => array(
                                    'Value'      => $_POST['meta_keywords'][$language['Code']],
                                    'Module'     => 'frontEnd',
                                    'Target_key' => $target_key,
                                ),
                                'where' => array(
                                    'Code' => $language['Code'],
                                    'Key'  => 'pages+meta_keywords+' . $f_key,
                                )
                            );

                            // update
                            $rlDb->updateOne($update_meta_keywords, 'lang_keys');
                        } else {
                            $insert_meta_keywords = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'frontEnd',
                                'Key'        => 'pages+meta_keywords+' . $f_key,
                                'Value'      => $_POST['meta_keywords'][$language['Code']],
                                'Target_key' => $target_key,
                            );
                            // insert
                            $rlDb->insertOne($insert_meta_keywords, 'lang_keys');
                        }

                        if ($f_page_type == 'static') {
                            $condition = "`Key` = 'pages+content+{$f_key}' AND `Code` = '{$language['Code']}'";
                            $value     = $f_content[$language['Code']];

                            if ($rlDb->getOne('ID', $condition, 'lang_keys')) {
                                // edit content
                                $lang_keys_content = array(
                                    'fields' => array(
                                        'Value'      => $value,
                                        'Module'     => 'frontEnd',
                                        'Target_key' => $target_key,
                                    ),
                                    'where' => array(
                                        'Code' => $language['Code'],
                                        'Key'  => 'pages+content+' . $f_key,
                                    )
                                );

                                if (html_entity_decode($value) != $rlDb->getOne('Value', $condition, 'lang_keys')) {
                                    $lang_keys_content['fields']['Modified'] = '1';
                                }

                                // update
                                $rlDb->updateOne($lang_keys_content, 'lang_keys');
                            } else {
                                $lang_keys_content = array(
                                    'Code'       => $language['Code'],
                                    'Module'     => 'frontEnd',
                                    'Status'     => 'active',
                                    'Key'        => 'pages+content+' . $f_key,
                                    'Value'      => $value,
                                    'Target_key' => $target_key,
                                );
                                $rlDb->insertOne($lang_keys_content, 'lang_keys');
                            }
                        }
                    }

                    $message = $rlLang->getSystem('page_edited');
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    }

    /* register ajax methods */
    $rlXajax->registerFunction(array('deletePage', $rlAdmin, 'ajaxDeletePage'));

    $rlHook->load('apPhpPagesBottom');
}
