<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LANGUAGES.INC.PHP
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

use Flynax\Utils\StringUtil;
use Flynax\Utils\Valid;

/* ext js action */
if ($_GET['q'] == 'ext_list') {
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

        $rlHook->load('apExtLanguagesUpdate');

        $rlActions->updateOne($updateData, 'languages');

        if ($config['multilingual_paths'] && $field === 'Status' && $value === 'approval') {
            $countActiveLanguages = (int) $rlDb->getRow(
                "SELECT COUNT(`ID`) FROM `{db_prefix}languages`
                    WHERE `Status` = 'active'",
                'COUNT(`ID`)'
            );

            if ($countActiveLanguages === 1) {
                $rlConfig->setConfig('multilingual_paths', '0');
            }
        }
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT COUNT(`T2`.`ID`) AS `Number`, `T1`.* FROM `{db_prefix}languages` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T1`.`Code` = `T2`.`Code` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T2`.`Module` <> 'email_tpl' AND `T2`.`Key` NOT LIKE 'data_formats+name+%' GROUP BY `T2`.`Code` ORDER BY `ID` ";
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtLanguagesSql');

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $is_current = $config['lang'] == $value['Code'] ? 'true' : 'false';
        $data[$key]['Data'] = $value['ID'] . '|' . $is_current;
        $data[$key]['Direction'] = $GLOBALS['lang'][$data[$key]['Direction'] . '_direction_title'];

        $data[$key]['name'] = $rlLang->getPhrase(array(
            'key'  => 'languages+name+' . $data[$key]['Key'],
            'lang' => $data[$key]['Code'],
        ));

        if ($value['Code'] == $config['lang']) {
            $data[$key]['name'] .= ' <b>(' . $lang['default'] . ')</b>';
        } else {
            $data[$key]['name'] .= ' | <a class="green_11_bg" href="javascript:void(0)" ';
            $data[$key]['name'] .= "onclick=\"apOfferComparePhrases('set_default', '{$value['Code']}');\">";
            $data[$key]['name'] .= "<b>{$lang['set_default']}</b></a>";
        }
    }

    $rlHook->load('apExtAccountFieldsData');

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} elseif ($_GET['q'] == 'ext') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    /* date update */

    if ($_REQUEST['action'] == 'update') {
        $reefless->loadClass('Actions');
        $type = $rlValid->xSql($_REQUEST['type']);
        $field = $rlValid->xSql($_REQUEST['field']);

        // Trim NL
        $value     = $_REQUEST['value'];
        $value     = trim($value, PHP_EOL);
        $id        = (int) $_REQUEST['id'];
        $lang_code = $rlValid->xSql($_REQUEST['lang_code']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        if ($field == 'Value') {
            $updateData['fields']['Modified'] = '1';
        }

        $rlHook->load('apExtPhrasesUpdate');

        $rlActions->updateOne($updateData, 'lang_keys');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sort = $sort ? $sort : 'Value';
    $sortDir = $rlValid->xSql($_GET['dir']);
    $sortDir = $sortDir ? $sortDir : 'ASC';

    $lang_id = (int) $_GET['lang_id'];
    $langCode = $lang_id ? $rlDb->getOne('Code', "`ID` = {$lang_id}", 'languages') : $rlValid->xSql($_GET['lang_code']);

    if (isset($_GET['action']) && $_GET['action'] == 'search') {
        $criteria = $_GET['criteria'];
        $exact_match = intval($_GET['exact_match']);
        $plugin = $rlValid->xSql($_GET['plugin']);
        $phrase = Valid::escape($_GET['phrase']);
        $phrase = StringUtil::replaceAssoc(
            $phrase,
            $exact_match ? array('’' => "\'", "`" => "\'") : array(' ' => '%', '’' => "\'", "`" => "\'")
        );
        $where = '1';

        if ($langCode != 'all') {
            $where = "`Code` = '{$langCode}' ";
        }

        // search in plugins
        if ($plugin) {
            $where .= " AND `Plugin` " . ($plugin == 'all' ? "<> ''" : "= '{$plugin}'");
        }

        $criteria_field = ($criteria == 'in_value') ? 'Value' : 'Key';
        $filter_value = $exact_match ? "= '{$phrase}'" : "LIKE '%{$phrase}%'";
        $concat_fields = '\'<span style="color: #596C27;"><b>\', `Code`, \'</b></span> | \', `Key`';

        $sql = "SELECT SQL_CALC_FOUND_ROWS `ID`, `Code`, `JS`, `Module`, CONCAT({$concat_fields}) AS `Key`, `Value` FROM `{db_prefix}lang_keys` ";
        $sql .= "WHERE {$where} AND `Status` = 'active' AND `Module` <> 'email_tpl' AND `Module` <> 'formats' ";
        $sql .= "AND `{$criteria_field}` {$filter_value} ORDER BY `{$sort}` {$sortDir} LIMIT {$start}, {$limit}";

        $rlHook->load('apExtPhrasesSearch');

        $lang_data = $rlDb->getAll($sql);
        $count_rows = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $lang_count['count'] = $count_rows['calc'];
    } else {
        $rlHook->load('apExtPhrasesSql');

        $rlDb->setTable('lang_keys');
        $lang_data = $rlDb->fetch(array('ID', 'Module', 'Key', 'JS', 'Value'), array('Code' => $langCode, 'Status' => 'active'), "AND `Module` <> 'email_tpl' AND `Module` <> 'formats' ORDER BY `{$sort}` {$sortDir}", array($start, $limit));
        $rlDb->resetTable();

        $lang_count = $rlDb->getRow("
            SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}lang_keys`
            WHERE `Code` = '{$langCode}' AND `Status` = 'active' AND `Module` <> 'email_tpl' AND`Module` <> 'formats'
        ");
    }

    foreach ($lang_data as $index => $item) {
        $lang_data[$index]['Module'] = $lang['module_' . $item['Module']];
        $lang_data[$index]['JS'] = $item['JS'] ? $lang['yes'] : $lang['no'];

        $rlHook->load('apExtPhrasesData');
    }

    $output['total'] = $lang_count['count'];
    $output['data'] = $lang_data;

    echo json_encode($output);
}
/* ext js action end */
elseif ($_GET['q'] == 'compare') {
    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';

    $lang_1 = $_SESSION['lang_1'];
    $lang_2 = $_SESSION['lang_2'];
    $compare_mode = $_SESSION['compare_mode'];

    if ($_GET['grid'] == 2) {
        $tmp = $lang_2;
        $lang_2 = $lang_1;
        $lang_1 = $tmp;
    }

    if ($_GET['action'] == 'update') {
        $reefless->loadClass('Actions');

        $type = $rlValid->xSql($_GET['type']);
        $field = $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $lang_code = $rlValid->xSql($_GET['lang_code']);

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtPhrasesCompareUpdate');

        $rlActions->updateOne($updateData, 'lang_keys');
    }

    $rlHook->load('apExtPhrasesCompareSql');

    if ($compare_mode == 'translation' || true) {
//set this to be the only method for now

        /* alternative comparing method (faster for translation method and big databases) */
        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* FROM `{db_prefix}lang_keys` AS `T1` ";
        $sql .= "LEFT OUTER JOIN `{db_prefix}lang_keys` AS `T2` ON `T1`.`Key` = `T2`.`Key` AND `T2`.`Code` = '{$lang_2}' ";

        if ($compare_mode == 'translation') {
            $sql .= "AND `T1`.`Value` = `T2`.`Value` ";
            $sql .= "WHERE `T1`.`Code` = '{$lang_1}' AND `T2`.`ID` is NOT null ";
        } else {
            $sql .= "WHERE `T1`.`Code` = '{$lang_1}' AND `T2`.`ID` is null ";
        }
        $sql .= "LIMIT {$start}, {$limit}";
        /*alternative comparing method end*/
    } else {
        /* primary comparing method */
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{db_prefix}lang_keys` ";
        $sql .= "WHERE `Code` = '{$lang_1}' AND ";
        if ($compare_mode == 'translation') {
            $sql .= "`Value` IN (SELECT `Value` FROM `{db_prefix}lang_keys` WHERE `Code` = '{$lang_2}' AND `Status` = 'active') ";
        } else {
            $sql .= "`Key` NOT IN (SELECT `Key` FROM `{db_prefix}lang_keys` WHERE `Code` = '{$lang_2}' AND `Status` = 'active') ";
        }
        $sql .= "AND `Status` = 'active' ";
        $sql .= "LIMIT {$start}, {$limit}";
        /* primary comparing method end */
    }

    $data = $rlDb->getAll($sql);

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $grid = (int) $_GET['grid'];

    foreach ($data as $index => $item) {
        $data[$index]['Module'] = $lang['module_' . $item['Module']];
    }

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
} elseif ($_GET['action'] == 'export') {
    $reefless->loadClass('AjaxLang', 'admin');
    $rlAjaxLang->exportLanguage((int) $_GET['lang']);
} else {
    /**
     * @since 4.8.1
     */
    $rlHook->load('apPhpLanguagesTop');

    /* clear cache */
    if (!isset($_REQUEST['compare']) && !$_POST['xjxfun']) {
        unset($_SESSION['compare_mode']);

        unset($_SESSION['compare_1']);
        unset($_SESSION['compare_2']);

        unset($_SESSION['source_1']);
        unset($_SESSION['source_2']);

        unset($_SESSION['lang_1']);
        unset($_SESSION['lang_2']);
    }

    /* get all system languages */
    $allLangs = $rlLang->getLanguagesList('all');
    $rlSmarty->assign_by_ref('allLangs', $allLangs);
    $rlSmarty->assign('langCount', count($allLangs));

    // get list of plugins which have phrases
    $sql = "SELECT `T1`.`Name`, `T1`.`Key` FROM `{db_prefix}plugins` AS `T1` ";
    $sql .= "WHERE `T1`.`Status` = 'active' AND (";
    $sql .= "SELECT `Key` FROM `{db_prefix}lang_keys` WHERE `Status` = 'active' AND `Plugin` = `T1`.`Key` LIMIT 1 ";
    $sql .= ") IS NOT NULL ";

    $plugins_list = $rlDb->getAll($sql);
    $rlSmarty->assign('plugins_list', $plugins_list);

    /* get lang for edit */
    if ($_GET['action'] == 'edit') {
        $bcAStep[] = array(
            'name' => $lang['edit'],
        );

        $edit_id = (int) $_GET['lang'];

        // get current language info
        $language = $rlDb->fetch('*', array('ID' => $edit_id), null, 1, 'languages', 'row');

        // count active languages
        $sql = "SELECT COUNT(*) AS `Count` FROM `{db_prefix}languages` WHERE `Status` = 'active'";
        $count_active_langs = (int) $rlDb->getRow($sql, 'Count');
        $rlSmarty->assign_by_ref('count_active_langs', $count_active_langs);

        if ($_GET['action'] == 'edit' && !$_POST['fromPost']) {
            $_POST['code'] = $language['Code'];
            $_POST['direction'] = $language['Direction'];
            $_POST['date_format'] = $language['Date_format'];
            $_POST['status'] = $language['Status'];
            $_POST['locale'] = $language['Locale'];

            // get names
            $l_name = $rlDb->fetch(array('Code', 'Value'), array('Key' => 'languages+name+' . $language['Key']), "AND `Status` <> 'trash'", 1, 'lang_keys', 'row');
            $_POST['name'] = $l_name['Value'];
        }

        /**
         * @since 4.8.1
         */
        $rlHook->load('apPhpLanguagesPost', $language);
    }

    if ($_POST['submit']) {
        /* check data */

        if (empty($_POST['name'])) {
            $errors[] = str_replace('{field}', "<b>\"{$lang['name']}\"</b>", $lang['notice_field_empty']);
        }

        if (empty($_POST['date_format'])) {
            $errors[] = str_replace('{field}', "<b>\"{$lang['date_format']}\"</b>", $lang['notice_field_empty']);
        }

        // Check the configuration of localization in PHP on server
        if ($_POST['date_format'] && $_POST['locale']) {
            if ((bool) preg_match('/%a|%A|%b|%B|%h/', $_POST['date_format'])) {
                setlocale(LC_TIME, $_POST['locale']);

                if ((bool) preg_match('/[\x00-\x1F\x7F-\xFF]/', strftime('%b'))) {
                    $errors[] = str_replace(
                        '{field}',
                        "<b>\"{$lang['date_format']}\"</b>",
                        $rlLang->getSystem('wrong_locale_configuration')
                    );
                }
            }
        }

        if (!empty($errors)) {
            $rlSmarty->assign_by_ref('errors', $errors);
        } else {
            $result = false;

            /* update general information */
            $updateLang['fields'] = array(
                'Date_format' => $_POST['date_format'],
                'Status'      => $_POST['status'] ?: 'active',
                'Direction'   => $_POST['direction'],
                'Locale'      => $_POST['locale'],
            );

            $updateLang['where'] = array(
                'Code' => $_POST['code'],
            );

            /**
             * @since 4.8.1
             */
            $rlHook->load('apPhpLanguagesBeforeEdit', $updateLang, $language);

            $result = $rlActions->updateOne($updateLang, 'languages');

            if ($rlDb->getOne('ID', "`Key` = 'languages+name+{$language['Key']}'", 'lang_keys')) {
                /* update phrase */
                $updatePhrase = array(
                    'fields' => array(
                        'Value' => $_POST['name'],
                    ),
                    'where'  => array(
                        'Key' => 'languages+name+' . $language['Key'],
                    ),
                );

                $result = $rlActions->updateOne($updatePhrase, 'lang_keys');
            } else {
                /* insert phrase */
                $insertPhrase = array(
                    'Key'    => 'languages+name+' . $language['Key'],
                    'Value'  => $_POST['name'],
                    'Module' => 'common',
                    'Code'   => $language['Code'],
                );

                $result = $rlActions->insertOne($insertPhrase, 'lang_keys');
            }

            /**
             * @since 4.8.1
             */
            $rlHook->load('apPhpLanguagesAfterEdit', $updateLang, $language, $result);

            if ($result) {
                $message = $lang['language_edited'];
                $aUrl = array("controller" => $controller);

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($message);
                $reefless->redirect($aUrl);
            } else {
                trigger_error("Can't edit language (MYSQL problems)", E_USER_WARNING);
                $rlDebug->logger("Can't edit language (MYSQL problems)");
            }
        }
    }

    if ($_POST['import']) {
        $dump_sours   = $_FILES['dump']['tmp_name'];
        $dump_file    = $_FILES['dump']['name'];
        $langCode     = '';
        $existLangKey = false;

        preg_match("/\(([A-Z]{2})\)(\.sql)$/", $dump_file, $matches);

        if (!empty($matches[1]) && strtolower($matches[2]) == '.sql') {
            if (is_readable($dump_sours)) {
                $dump_content     = fopen($dump_sours, "r");
                $rlDb->dieIfError = false;
                $langCode         = strtolower($matches[1]);

                if ($dump_content) {
                    // Check exist language
                    if ($existLangKey = $rlDb->getOne('Key', "LOWER(`Code`) = '{$langCode}'", 'languages')) {
                        $exist_lang_name = $rlDb->getOne(
                            'Value',
                            "`Key` = 'languages+name+{$existLangKey}'",
                            'lang_keys'
                        );
                        $errors[] = str_replace(
                            ['{language}', '{code}'],
                            [$exist_lang_name, $matches[1]],
                            $lang['import_language_already_exist']
                        );
                    } else {
                        /**
                         * @since 4.8.1
                         */
                        $rlHook->load('apPhpLanguagesBeforeImport', $langCode);

                        while ($query = fgets($dump_content, 10240)) {
                            $query = trim($query);
                            if ($query[0] == '#') {
                                continue;
                            }

                            if ($query[0] == '-') {
                                continue;
                            }

                            if ($query[strlen($query) - 1] == ';') {
                                $query_sql .= $query;
                            } else {
                                $query_sql .= $query;
                                continue;
                            }

                            if (!empty($query_sql) && empty($errors)) {
                                $query_sql = str_replace('{prefix}', RL_DBPREFIX, $query_sql);
                            }

                            $res = $rlDb->query($query_sql);
                            if (!$res && count($errors) < 5) {
                                $errors[] = $lang['can_not_run_sql_query'] . $rlDb->lastError();
                            }
                            unset($query_sql);
                        }

                        fclose($dump_content);

                        if (empty($errors)) {
                            // Import plugin phrases
                            $reefless->loadClass('Plugin', 'admin');
                            $rlDb->outputRowsMap = [false, 'Key'];

                            foreach ($rlDb->fetch(['Key'], null, null, null, 'plugins') as $plugin_key) {
                                $plugin_phrases = $rlDb->fetch(
                                    ['Key', 'Module', 'Value', 'JS', 'Target_key', 'Status', 'Plugin'],
                                    ['Code' => $config['lang'], 'Plugin' => $plugin_key],
                                    null, null, 'lang_keys'
                                );

                                $i18n_phrases = $rlPlugin->getLanguagePhrases($langCode, $plugin_key);

                                foreach ($plugin_phrases as $phrase) {
                                    $existPhrase = (bool) $rlDb->fetch(
                                        ['ID'],
                                        ['Key'  => $phrase['Key'], 'Code' => $langCode],
                                        null, 1, 'lang_keys', 'row'
                                    );

                                    if (!$existPhrase) {
                                        $phrase['Code']  = $langCode;
                                        $phrase['Value'] = $i18n_phrases[$phrase['Key']] ?: $phrase['Value'];

                                        $rlDb->insertOne($phrase, 'lang_keys');
                                    }
                                }
                            }

                            // Create multilingual fields
                            if ($config['multilingual_paths']) {
                                $where = "VARCHAR(255) NOT NULL DEFAULT '' AFTER `Path`";
                                $rlDb->addColumnToTable("Path_{$langCode}", $where, 'categories');
                                $rlDb->addColumnToTable("Path_{$langCode}", $where, 'pages');
                            }

                            $rlNotice->saveNotice($lang['new_language_imported']);
                            $aUrl = array("controller" => $controller);

                            /**
                             * @since 4.8.1
                             */
                            $rlHook->load('apPhpLanguageAfterImport', $langCode);

                            $reefless->redirect($aUrl);
                        } else {
                            $errors[] = $lang['dump_query_corrupt'];
                        }
                    }
                } else {
                    $errors[] = $lang['dump_has_not_content'];
                }
            } else {
                $errors[] = $lang['can_not_read_file'];
                trigger_error("Can not to read uploaded file | Language Import", E_USER_WARNING);
                $rlDebug->logger("Can not to read uploaded file | Language Import");
            }
        } else {
            $errors[] = $lang['incorrect_lang_dump'];
        }

        if (!empty($errors)) {
            if (!$existLangKey) {
                $rlDb->query("DELETE FROM `{db_prefix}languages` WHERE `Code` = '{$langCode}' LIMIT 1");
                $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Code` = '{$langCode}'");
            }

            $rlSmarty->assign_by_ref('errors', $errors);
        }
    } elseif (isset($_POST['compare'])) {
        $lang_1 = $_POST['lang_1'];
        $lang_2 = $_POST['lang_2'];

        /* additional bread crumb step */
        $bcAStep = $lang['languages_compare'];

        foreach ($allLangs as $lK => $lV) {
            $langs_info[$allLangs[$lK]['Code']] = $allLangs[$lK];
        }

        /* checking errors */
        if (empty($lang_1) || empty($lang_2)) {
            $errors[] = $lang['compare_empty_langs'];
        }

        if ($lang_1 == $lang_2 && !$errors) {
            $errors[] = $lang['compare_languages_same'];
        }

        if ((!array_key_exists($lang_1, $langs_info) || !array_key_exists($lang_2, $langs_info)) && !$errors) {
            $errors[] = $lang['system_error'];
            //trigger_error("Can not compare the languages, gets undefine language code", E_USER_NOTICE);
            $rlDebug->logger("Can not compare the languages, gets undefine language code");
        }

        if (!empty($errors)) {
            $rlSmarty->assign_by_ref('errors', $errors);
        } else {
            $_SESSION['lang_1'] = $lang_1;
            $_SESSION['lang_2'] = $lang_2;

            $compare_mode = $_SESSION['compare_mode'] = $_POST['compare_mode'];

            $compare_lang1 = array('Code' => $lang_1);
            $compare_lang2 = array('Code' => $lang_2);

            $compare_langs = array(0 => $lang_1, 1 => $lang_2);

            for ($i = 1; $i <= 2; $i++) {
                $lang_1 = $compare_langs[0];
                $lang_2 = $compare_langs[1];

                if ($compare_mode == 'translation' && false) {
//set this to be the only method for now
                    /* alternative method */
                    $sql = "SELECT COUNT(*) as `diff` FROM `{db_prefix}lang_keys` AS `T1` ";
                    $sql .= "LEFT OUTER JOIN `{db_prefix}lang_keys` AS `T2` ON `T1`.`Key` = `T2`.`Key` AND `T2`.`Code` = '{$lang_2}' ";

                    if ($compare_mode == 'translation') {
                        $sql .= "AND `T1`.`Value` = `T2`.`Value` ";
                        $sql .= "WHERE `T1`.`Code` = '{$lang_1}' AND `T2`.`ID` is NOT null ";
                    } else {
                        $sql .= "WHERE `T1`.`Code` = '{$lang_1}' AND `T2`.`ID` is null ";
                    }
                    /* alternative method end */
                } else {
                    /* primary comparing method */
                    $sql = "SELECT COUNT(*) as `diff` FROM `{db_prefix}lang_keys` ";
                    $sql .= "WHERE `Code` = '{$lang_1}' AND ";
                    if ($compare_mode == 'translation') {
                        $sql .= "`Value` IN (SELECT `Value` FROM `{db_prefix}lang_keys` WHERE `Code` = '{$lang_2}' AND `Status` = 'active') ";
                    } else {
                        $sql .= "`Key` NOT IN (SELECT `Key` FROM `{db_prefix}lang_keys` WHERE `Code` = '{$lang_2}' AND `Status` = 'active') ";
                    }
                    $sql .= "AND `Status` = 'active' ";
                    /* primary comparing method end */
                }

                $diff = $rlDb->getRow($sql);

                if ($i == 1) {
                    $compare_lang1['diff'] = (bool) $diff['diff'];
                } else {
                    $compare_lang2['diff'] = (bool) $diff['diff'];
                }

                array_reverse($compare_langs);
            }

            if (!$compare_lang1['diff'] && !$compare_lang2['diff']) {
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['compare_no_diff_found']);

                $aUrl = array("controller" => $controller);
                $reefless->redirect($aUrl);
            }

            $rlSmarty->assign_by_ref('compare_lang1', $compare_lang1);
            $rlSmarty->assign_by_ref('compare_lang2', $compare_lang2);
            $rlSmarty->assign_by_ref('langs_info', $langs_info);
        }
    }

    $rlHook->load('apPhpLanguagesBottom');

    /* load admin class */
    $reefless->loadClass('AjaxLang', 'admin');

    /* register ajax methods */
    $rlXajax->registerFunction(array('setDefault', $rlAjaxLang, 'ajaxSetDefault'));
    $rlXajax->registerFunction(array('deleteLang', $rlAjaxLang, 'ajaxDeleteLang'));
    $rlXajax->registerFunction(array('addLanguage', $rlAjaxLang, 'ajax_addLanguage'));
    $rlXajax->registerFunction(array('addPhrase', $rlAjaxLang, 'ajax_addPhrase'));
    $rlXajax->registerFunction(array('copyPhrases', $rlAjaxLang, 'ajaxCopyPhrases'));
    $rlXajax->registerFunction(array('massDelete', $rlAjaxLang, 'ajaxMassDelete'));
    $rlXajax->registerFunction(array('exportLanguage', $rlAjaxLang, 'ajaxExportLanguage'));
}
