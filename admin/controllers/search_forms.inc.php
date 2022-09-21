<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SEARCH_FORMS.INC.PHP
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

// ext js action
if ($_GET['q'] == 'ext') {
    // system config
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';
    require_once RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';

    // date update
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

        if ($field == 'Status') {
            $cur_status = $rlDb->getOne('Status', "`ID` = '{$id}'", 'search_forms');

            if ($cur_status != $value) {
                $update_cache = true;
            }
        } elseif ($field == 'With_picture') {
            $cur_state = $rlDb->getOne('With_picture', "`ID` = '{$id}'", 'search_forms');

            if ($cur_state != $value) {
                $update_cache = true;
            }
        }

        $rlHook->load('apExtSearchFormsUpdate');

        $rlActions->updateOne($updateData, 'search_forms');

        if ($update_cache) {
            $rlCache->updateSearchForms();
            $rlCache->updateSearchFields();
        }

        exit;
    }

    // data read
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $langCode = $rlValid->xSql($_GET['lang_code']);
    $phrase = $rlValid->xSql($_GET['phrase']);

    $condition = "WHERE `Status` <> 'trash'";
    $rlHook->load('apExtSearchFormsSql');

    $rlDb->setTable('search_forms');
    $data = $rlDb->fetch('*', null, $condition, array($start, $limit));
    $data = $rlLang->replaceLangKeys($data, 'search_forms', array('name', 'des'), RL_LANG_CODE, 'admin');
    $rlDb->resetTable();

    // load listing types
    $reefless->loadClass('ListingTypes');

    // get forms
    foreach ($data as $key => $value) {
        $form_mode = $lang[$data[$key]['Mode']];
        if (in_array($value['Mode'], array('custom', 'in_category', 'on_map'))) {
            $form_mode = $lang[$value['Mode'] . '_form'];
        }

        if ($value['Mode'] == 'on_map' && !$tpl_settings['search_on_map_page']) {
            $data[$key]['Status'] = $value['Status'] = 'incompatible';
        }

        $data[$key]['Type'] = $rlListingTypes->types[$data[$key]['Type']]['name'];
        $data[$key]['Type'] .= $value['In_tab'] ? ' <b>' . $lang['in_tab'] . '</b>' : '';
        $data[$key]['Mode_key'] = $value['Mode'];
        $data[$key]['Mode'] = $form_mode;
        $data[$key]['Status_key'] = $value['Status'];
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Groups'] = $data[$key]['Groups'] ? $lang['yes'] : $lang['no'];
        $data[$key]['With_picture'] = $data[$key]['With_picture'] ? $lang['yes'] : $lang['no'];
        $data[$key]['no_groups'] = $value['In_tab'] || in_array($value['Mode'], array('quick', 'in_category')) ? 1 : 0;
    }

    $rlHook->load('apExtSearchFormsData');

    $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}search_forms` WHERE `Status` <> 'trash'");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
// ext js action end

else {
    // additional bread crumb step
    if ($_GET['action']) {
        if ($_GET['action'] == 'add') {
            $bcAStep = $lang['add_form'];
        } elseif ($_GET['action'] == 'edit') {
            $bcAStep = $lang['edit_form'];
        } elseif ($_GET['action'] == 'build') {
            $bcAStep = $lang['build_form'];
        }
    }

    $rlHook->load('apPhpSearchFormsTop');

    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        // get all languages
        $allLangs = $GLOBALS['languages'];
        $rlSmarty->assign_by_ref('allLangs', $allLangs);

        // form types
        $form_types = array(
            'custom'      => $lang['custom_form'],
            'system'      => $lang['system_form'],
            'in_category' => $lang['in_category_form'],
        );
        $rlSmarty->assign_by_ref('form_types', $form_types);

        if ($_GET['action'] == 'add') {
            unset($form_types['system']);
        }

        if ($_GET['action'] == 'edit') {
            $s_key = $rlValid->xSql($_GET['form']);

            // get current form info
            $form_info = $rlDb->fetch('*', array('Key' => $s_key), "AND `Status` <> 'trash'", null, 'search_forms', 'row');
            $rlSmarty->assign_by_ref('form_info', $form_info);

            $_POST['readonly'] = $form_info['Readonly'];
            $rlSmarty->assign('cpTitle', $lang['search_forms+name+' . $s_key]);

            // disable groups using
            if ($form_info['In_tab'] || $form_info['Mode'] == 'quick') {
                $rlSmarty->assign('no_groups', true);
            }
        }

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['key'] = $form_info['Key'];
            $_POST['status'] = $form_info['Status'];
            $_POST['type'] = $form_info['Type'];
            $_POST['groups'] = $form_info['Groups'];
            $_POST['with_picture'] = $form_info['With_picture'];
            $_POST['category_id'] = $form_info['Category_ID'];
            $_POST['subcategories'] = $form_info['Subcategories'];

            // get names
            $i_names = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'search_forms+name+' . $s_key), "AND `Status` <> 'trash'", null, 'lang_keys');
            foreach ($i_names as $nKey => $nVal) {
                $_POST['name'][$i_names[$nKey]['Code']] = $i_names[$nKey]['Value'];
            }

            // define form type
            $_POST['form_type'] = 'system';
            if (in_array($form_info['Mode'], array('custom', 'in_category'))) {
                $_POST['form_type'] = $form_info['Mode'];
            }

            $rlHook->load('apPhpSearchFormsPost');
        }

        if (isset($_POST['submit'])) {
            $errors = array();

            // load the utf8 lib
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

            $f_key = $_POST['key'];

            // check form type
            $form_mode = $_POST['form_type'];
            if (!$form_mode) {
                $errors[] = str_replace('{field}', '<b>"' . $lang['form_type'] . '"</b>', $lang['notice_field_empty']);
            }

            // in category mode
            if ($form_mode == 'in_category') {
                $category_id = (int) $_POST['category_id'];
                $subcategories = (int) $_POST['subcategories'];

                if (!$category_id) {
                    $errors[] = str_replace('{field}', '<b>"' . $lang['show_in_category'] . '"</b>', $lang['notice_field_empty']);
                } else {
                    $existing_form = $rlDb->fetch(array('Category_ID', 'Key'), array('Category_ID' => $category_id), null, 1, 'search_forms', 'row');

                    if ($_GET['action'] == 'add' && $existing_form) {
                        $existing_category_key = $rlDb->getOne('Key', "`ID` = {$existing_form['Category_ID']}", 'categories');

                        $error_phrase = str_replace('{name}', '<b>' . $lang['categories+name+' . $existing_category_key] . '</b>', $lang['error_search_form_for_category_exists']);
                        $url = RL_URL_HOME . ADMIN . '/index.php?controller=search_forms&action=build&form=' . $existing_form['Key'];
                        $build_link = '<a href="' . $url . '">$2</a>';
                        $errors[] = preg_replace('/(\[(.*?)\])/', $build_link, $error_phrase);
                    }
                }

                if ($_GET['action'] == 'add') {
                    $f_key = 'in_category_' . $category_id;
                }
            }

            // check key exist (in add mode only)
            if ($_GET['action'] == 'add') {
                if (!utf8_is_ascii($f_key)) {
                    $f_key = utf8_to_ascii($f_key);
                }

                if ($form_mode && strlen($f_key) < 3) {
                    $errors[] = $lang['incorrect_phrase_key'];
                }

                if ($form_mode != 'in_category') {
                    $exist_key = $rlDb->fetch(array('Key'), array('Key' => $f_key), null, null, 'search_forms');

                    if (!empty($exist_key)) {
                        $errors[] = str_replace('{key}', '<b>"' . $f_key . '"</b>', $lang['notice_form_key_exist']);
                    }
                }
            }

            $f_key = $rlValid->str2key($f_key);

            // check name
            $f_name = $_POST['name'];

            foreach ($allLangs as $lkey => $lval) {
                if (empty($f_name[$allLangs[$lkey]['Code']])) {
                    $errors[] = str_replace('{field}', '<b>' . $lang['name'] . '(' . $allLangs[$lkey]['name'] . ')</b>', $lang['notice_field_empty']);
                }

                $f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
            }

            // check type
            $f_type = $_POST['type'];
            if (empty($f_type)) {
                $errors[] = str_replace('{field}', '<b>"' . $lang['listing_type'] . '"</b>', $lang['notice_field_empty']);
            }

            $rlHook->load('apPhpSearchFormsValidate');

            if (!empty($errors)) {
                $rlSmarty->assign_by_ref('errors', $errors);
            } else {
                // add/edit action
                if ($_GET['action'] == 'add') {
                    // write main form information
                    $data = array(
                        'Key'          => $f_key,
                        'Status'       => $_POST['status'],
                        'Type'         => $f_type,
                        'Groups'       => (int) $_POST['groups'],
                        'With_picture' => (int) $_POST['with_picture'],
                        'Mode'         => $form_mode,
                    );

                    if ($form_mode == 'in_category') {
                        $data['Category_ID'] = $category_id;
                        $data['Subcategories'] = $subcategories;
                    }

                    $rlHook->load('apPhpSearchFormsBeforeAdd');

                    if ($action = $rlActions->insertOne($data, 'search_forms')) {
                        $rlHook->load('apPhpSearchFormsAfterAdd');

                        // write name's phrases
                        foreach ($allLangs as $key => $value) {
                            $lang_keys[] = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'search_forms+name+' . $f_key,
                                'Value'  => $f_name[$allLangs[$key]['Code']],
                            );
                        }

                        $rlActions->insert($lang_keys, 'lang_keys');

                        $message = $lang['form_added'];
                        $aUrl = array('controller' => $controller);
                    } else {
                        trigger_error("Can't add new search forms (MYSQL problems)", E_WARNING);
                        $rlDebug->logger("Can't add new search forms (MYSQL problems)");
                    }
                } elseif ($_GET['action'] == 'edit') {
                    $update_date = array(
                        'fields' => array(
                            'Status'       => $_POST['status'],
                            'Groups'       => (int) $_POST['groups'],
                            'With_picture' => (int) $_POST['with_picture'],
                        ),
                        'where'  => array('Key' => $f_key),
                    );

                    // change form key and form phrases in case of "in_category" mode and once the form related category changed
                    if ($form_mode == 'in_category' && $f_key != 'in_category_' . $category_id) {
                        $update_date['fields']['Key'] = 'in_category_' . $category_id;

                        $update_phrases_key = array(
                            'fields' => array(
                                'Key' => 'search_forms+name+in_category_' . $category_id,
                            ),
                            'where'  => array('Key' => 'search_forms+name+' . $f_key),
                        );
                        $rlActions->updateOne($update_phrases_key, 'lang_keys');
                    }

                    if ($form_mode == 'in_category') {
                        $update_date['fields']['Category_ID'] = $category_id;
                        $update_date['fields']['Subcategories'] = $subcategories;
                    }

                    if (!$form_info['Readonly']) {
                        $update_date['fields']['Type'] = $f_type;
                    }

                    $rlHook->load('apPhpSearchFormsBeforeEdit');

                    $action = $rlActions->updateOne($update_date, 'search_forms');

                    // update system cache
                    if ($form_info['Status'] != $_POST['status'] || $form_info['With_picture'] != $_POST['with_picture'] || $update_date['fields']['Type']) {
                        $rlCache->updateSearchForms();
                        $rlCache->updateSearchFields();
                    }

                    $rlHook->load('apPhpSearchFormsAfterEdit');

                    foreach ($allLangs as $key => $value) {
                        if ($rlDb->getOne('ID', "`Key` = 'search_forms+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys')) {
                            // edit names
                            $update_phrases = array(
                                'fields' => array(
                                    'Value' => $_POST['name'][$allLangs[$key]['Code']],
                                ),
                                'where'  => array(
                                    'Code' => $allLangs[$key]['Code'],
                                    'Key'  => 'search_forms+name+' . $f_key,
                                ),
                            );

                            // update
                            $rlActions->updateOne($update_phrases, 'lang_keys');
                        } else {
                            // insert names
                            $insert_phrases = array(
                                'Code'   => $allLangs[$key]['Code'],
                                'Module' => 'common',
                                'Key'    => 'search_forms+name+' . $f_key,
                                'Value'  => $_POST['name'][$allLangs[$key]['Code']],
                            );

                            // insert
                            $rlActions->insertOne($insert_phrases, 'lang_keys');
                        }
                    }

                    $message = $lang['form_edited'];
                    $aUrl = array("controller" => $controller);
                }

                if ($action) {
                    $reefless->loadClass('Notice');
                    $rlNotice->saveNotice($message);
                    $reefless->redirect($aUrl);
                }
            }
        }
    } elseif ($_GET['action'] == 'build') {
        $form_key = $rlValid->xSql($_GET['form']);

        $form = $rlDb->fetch('*', array('Key' => $form_key), null, 1, 'search_forms', 'row');

        if (!$form) {
            $sError = true;
        } else {
            $form = $rlLang->replaceLangKeys($form, 'search_forms', array('name'), RL_LANG_CODE, 'admin');
            $rlSmarty->assign_by_ref('form_info', $form);

            // add custom page title
            $rlSmarty->assign_by_ref('cpTitle', $form['name']);

            $reefless->loadClass('Builder', 'admin');
            $rlBuilder->rlBuildTable = 'search_forms_relations';

            // get relations
            $relations = $rlBuilder->getRelations($form['ID']);

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

            if ($form['Groups']) {
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
            }

            if ($form['In_tab'] && $no_key = $rlDb->getOne('Arrange_field', "`Key` = '{$form['Type']}'", 'listing_types')) {
                $add_where = " AND `Key` <> '{$no_key}'";
            }

            $rlHook->load('apPhpSearchFormsBuildFields', $add_where); //>= 4.4.0

            // get listing fields
            $where = "WHERE `Status` <> 'trash' AND `Type` NOT IN ('textarea', 'file', 'image', 'accept') ";

            if ($form['Mode'] == 'on_map') {
                $where .= "AND `Type` <> 'checkbox' AND `Map` = '0' AND `Key` NOT REGEXP '_level[0-9]|zip|postal' ";
            }

            $where .= $add_where;

            // display system fields only for myads forms
            if ($form['Mode'] != 'myads') {
                $where .= "AND `ID` > 0 ";
            }

            $fields = $rlDb->fetch(array('ID', 'Key', 'Type', 'Status'), null, $where, null, 'listing_fields');
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

            // register ajax methods
            $rlXajax->registerFunction(array('buildForm', $rlBuilder, 'ajaxBuildForm'));

            $rlHook->load('apPhpSearchFormsBuild');
        }
    }

    // register ajax methods
    $rlXajax->registerFunction(array('deleteSearchForm', $rlAdmin, 'ajaxDeleteSearchForm'));

    $rlHook->load('apPhpSearchFormsBottom');
}
