<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: IMPORT.PHP
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

if (ini_get('safe_mode') != '1') {
    set_time_limit(0);
}

/* system config */
require_once '../../../includes/config.inc.php';
require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
/*require_once( RL_LIBS . 'system.lib.php' );*/

$reefless->loadClass('MultiFieldAP', null, 'multiField');

$languages = $rlLang->getLanguagesList();

$limit = $_SESSION['mf_import']['per_run'];
$start = (int) $_GET['index'];

$parents = $_SESSION['mf_import']['parents'];
$parent = $_SESSION['mf_import']['parents'][0];
$next_parent = $_SESSION['mf_import']['parents'][1];

if ($parent) {
    $table = $_SESSION['mf_import']['table'];
    $parent_id = $_SESSION['mf_import']['parent_id'];
    $one_ignore = $_SESSION['mf_import']['one_ignore'];
    $top_key = $_SESSION['mf_import']['top_key'];

    $available_rows = $_SESSION['mf_import']['available_rows'];

    if (!$_SESSION['mf_import']['available_sub_rows'][$parent]) {
        $_SESSION['mf_import']['available_sub_rows'][$parent] = getFData(
            array('table' => $table, 'getcount' => 'true', 'parent' => $parent)
        );
    }

    if ($next_parent) {
        if (!$_SESSION['mf_import']['available_sub_rows'][$next_parent]) {
            $_SESSION['mf_import']['available_sub_rows'][$next_parent] = getFData(
                array('table' => $table, 'getcount' => 'true', 'parent' => $next_parent)
            );
        }
    }

    if (count($parents) == 1 && !$one_ignore && $_SESSION['mf_import']['total'] == 1) {
        $without_parent = true;
        //import childs of one
        $data = getFData(
            array('parent' => $parent, 'table' => $table, 'including_childs' => true, 'start' => $start, 'limit' => $limit)
        );
    } else {
        //import all
        $data = getFData(
            array('parent' => $parent, 'table' => $table, 'including_childs' => true, 'including_parent' => true, 'start' => $start, 'limit' => $limit)
        );
    }

    importData($data, $parent_id, $top_key, null, null, $without_parent, $parent);

    /** data importing **/
    $new_parent = false;

    if (count($data) < $limit) {
        array_shift($_SESSION['mf_import']['parents']);
        $new_parent = true;

        if ($next_parent) {
            $items['sub_count'] = $_SESSION['mf_import']['available_sub_rows'][$next_parent];
            unset($_SESSION['mf_import']['available_sub_rows']);
        }
    } else {
        $items['sub_count'] = (int) $_SESSION['mf_import']['available_sub_rows'][$parent];
    }

    $items['count'] = $available_rows;
    $items['current'] = $available_rows - count($parents) + 1;
    $items['current_text'] = ucwords(str_replace("_", " ", $parent));

    $items['index'] = $new_parent ? 0 : $start + $limit;
    $items['limit'] = $limit;

} else {
    $items['finish'] = true;

    $reefless->loadClass('Notice');
    $rlNotice->saveNotice($lang['mf_import_completed']);

    $reefless->loadClass('Actions');
    $reefless->loadClass('Cache');

    $rlMultiFieldAP->afterRemoteImport($_SESSION['mf_import']['table'], $_SESSION['mf_import']['top_key']);

    unset($_SESSION['mf_import']);

    $rlCache->updateSubmitForms();
}

echo json_encode($items);
exit;

function importData($data = false, $parent_id = 0, $top_key = false, $level = 0, $max_position = 1, $without_parent = false, $parentk = false)
{
    global $parents, $rlDb, $config, $rlMultiFieldAP;

    $path_field = $config['mf_multilingual_path'] ? 'Path_' . $config['lang'] : 'Path';

    foreach ($data as $key => $value) {
        $item_key = $top_key . '_' . $value->Key;
        $update_phrases = $_SESSION['mf_import']['update_phrases'];

        if ($update_phrases) {
            foreach ($GLOBALS['languages'] as $language) {
                $lang_table = 'multi_formats_lang_' . $language['Code'];
                $name_key = 'Name_' . $language['Code'];

                if ($value->$name_key && $rlDb->getOne('Value', "`Key` = '{$item_key}'", $lang_table) == $value->Name) {
                    $update = array(
                        'fields' => ['Value' => $value->$name_key],
                        'where' => ['Key' => $item_key]
                    );
                    $rlDb->updateOne($update, $lang_table);
                }
            }
        } else {
            if ($without_parent && $parentk == $value->Parent_key) {
                $parent_key = $top_key;
            } else {
                $parent_key = $top_key . '_' . $value->Parent_key;
            }

            if ($parent_id && $parent_key == $top_key . '_') {
                $parent['ID'] = $parent_id;
                $parent['Level'] = 0;
            } elseif ($_SESSION['mf_parent_ids'][$parent_key]) {
                $parent = $_SESSION['mf_parent_ids'][$parent_key];
            } else {
                $parent = $rlMultiFieldAP->getFormatData(null, $parent_key);

                if (!$parent['ID']) {
                    continue;
                }

                $parent['Level'] = $rlMultiFieldAP->getLevel($parent['ID']);

                if ($_SESSION['mf_parent_ids'] && count($_SESSION['mf_parent_ids']) > 1000) {
                    unset($_SESSION['mf_parent_ids']);
                }

                $parent = $_SESSION['mf_parent_ids'][$parent_key] = $parent;
            }

            if ($parent) {
                $prev_level = $level;
                $level = $parent['Level'] + 1;

                if ($level != $prev_level) {
                    $max_position = $rlDb->getOne("Position", "`Parent_ID` = " . $parent['ID'] . " ORDER BY `Position` DESC", "multi_formats");
                }

                $max_position = is_numeric($max_position) ? $max_position : 0;

                $parent_ids = $parent['ID'];
                if ($parent['Parent_IDs']) {
                    $parent_ids .= ',' . $parent['Parent_IDs'];
                }

                $insert = array(
                    'Parent_ID' => $parent['ID'],
                    'Parent_IDs' => $parent_ids,
                    'Key' => $item_key,
                    'Status' => 'active',
                    'Position' => ++$max_position,
                );

                if ($_SESSION['mf_import']['geo_filter']) {
                    $insert[$path_field] = $GLOBALS['rlValid']->str2path($value->Name);

                    if ($config['mf_geo_subdomains_type'] == 'unique') {
                        $cnt = 0;
                        while ($rlDb->getOne('ID', "`{$path_field}` = '{$insert[$path_field]}'", 'multi_formats')) {
                            $parent_id   = $parent_item['ID'] ?: $parent['ID'];
                            $parent_item = $rlDb->fetch('*', array('ID' => $parent_id), null, null, 'multi_formats', 'row');

                            $insert[$path_field] = $parent_item[$path_field] . '-' . $insert[$path_field];

                            // Avoid looping
                            if ($cnt > 5) {
                                break;
                            }
                            $cnt++;
                        }
                        unset($parent_item);
                    } else {
                        $insert[$path_field] = $parent[$path_field] ? $parent[$path_field] . '/' . $insert[$path_field] : $insert[$path_field];
                    }
                }

                if ($rlDb->insertOne($insert, 'multi_formats')) {
                    foreach ($GLOBALS['languages'] as $language) {
                        $name_key = 'Name_' . $language['Code'];
                        $phrase = array(
                            'Key' => $insert['Key'],
                            'Value' => $value->$name_key ?: $value->Name
                        );
                        $rlDb->insertOne($phrase, 'multi_formats_lang_' . $language['Code']);
                    }
                }
            }
        }
    }
}

function getFData($params)
{
    global $reefless;

    set_time_limit(0);
    $reefless->time_limit = 0;

    $vps = "http://database.flynax.com/index.php?plugin=multiField";
    $vps .= "&domain={$GLOBALS['license_domain']}&license={$GLOBALS['license_number']}";

    foreach ($params as $k => $p) {
        $vps .= "&" . $k . "=" . $p;
    }

    $content = $reefless->getPageContent($vps);

    return json_decode($content);
}
