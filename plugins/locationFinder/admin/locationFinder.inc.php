<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LOCATIONFINDER.INC.PHP
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

$rlHook->load('apPhpListingsTop');

switch ($_GET['action']) {
    case 'update':
        $bcAStep = $lang['locationFinder_import_update_database'];

        // Fix location finder database version
        if (!$config['locationFinder_db_version']) {
            $rows = $rlDb->getRow("SELECT COUNT(*) AS `Count` FROM `{db_prefix}geo_mapping`");
            if (round($rows['Count'], -3) == 121000) {
                $rlConfig->setConfig('locationFinder_db_version', '1.0');
                $config['locationFinder_db_version'] = '1.0';
            }
        }

        // Fix multifields database version
        if (isset($_GET['fix']) && !in_array($config['mf_db_version'], ['locations5', 'locations6'])) {
            $rlConfig->setConfig('mf_db_version', 'locations5');
            $config['mf_db_version'] = 'locations5';

            $aUrl = array('controller' => $controller, 'action' => 'update');
            $reefless->redirect($aUrl);
        }

        $rlSmarty->assign('db_update', true);
        $update_error = false;

        if (!in_array($config['mf_db_version'], ['locations5', 'locations6'])) {
            $update_error = true;
        }
        $rlSmarty->assign_by_ref('update_error', $update_error);
        break;

    case 'create':
        $reefless->loadClass('LocationFinderAP', null, 'locationFinder');

        if ($rlLocationFinderAP->createMainTable()) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['locationFinder_database_structure_created']);

            $aUrl = array('controller' => $controller);
            $reefless->redirect($aUrl);
        } else {
            $rlDebug->logger('Location Finder: Unable to create "geo_mapping" database.');
            $sError = true;
        }
        break;

    case 'structure':
        // Create `Place_ID` field
        $rlDb->addColumnToTable('Place_ID', "varchar(128) NOT NULL AFTER `Format_key`", 'geo_mapping');

        $reefless->loadClass('Notice');
        $rlNotice->saveNotice($lang['locationFinder_database_structure_updated']);

        $aUrl = array('controller' => $controller);
        $reefless->redirect($aUrl);
        break;

    default:
        $is_mapping_available = true;

        // No multifield case
        if (!$plugins['multiField']) {
            $is_mapping_available = false;
            $href = $rlSmarty->_tpl_vars['rlBase'] . 'index.php?controller=plugins';
            $mapping_error = preg_replace('/(\[(.*)?\])/', '<a href="' . $href . '">$2</a>', $lang['locationFinder_mapping_mf_inactive_error']);
        }
        // No table case
        elseif (!$rlDb->tableExists('geo_mapping')) {
            $is_mapping_available = false;
            $href = $rlSmarty->_tpl_vars['rlBase'] . 'index.php?controller=' . $_GET['controller'] . '&action=create';
            $mapping_error = preg_replace('/(\[(.*)?\])/', '<a href="' . $href . '">$2</a>', $lang['locationFinder_mapping_table_error']);
        }
        // DB version mismatch case
        else {
            if (!$rlDb->columnExists('Place_ID', 'geo_mapping')) {
                $is_mapping_available = false;
                $href = $rlSmarty->_tpl_vars['rlBase'] . 'index.php?controller=' . $_GET['controller'];

                $buttons = <<< HTML
                <div style="padding-top: 10px;"><a class="button" href="{$href}&action=update">{$lang['locationFinder_update_database']}</a>
                <span style="padding: 0 10px;">{$lang['or']}</span>
                <a class="button" href="{$href}&action=structure">{$lang['locationFinder_update_structure']}</a></div>
HTML;
                $mapping_error = $lang['locationFinder_mapping_table_mismatch'] . $buttons;
            }
        }

        $rlSmarty->assign_by_ref('is_mapping_available', $is_mapping_available);

        // Available mapping case
        if ($is_mapping_available) {
            $rlSmarty->assign('cpTitle', $lang['locationFinder_mapping_manager']);

            // Get related fields data
            foreach (array('country', 'state', 'city') as $field) {
                $field = 'locationFinder_mapping_' . $field;
                if ($config[$field] && $data = $rlDb->fetch(
                        array('Key', 'Type', 'Condition'),
                        array('Key' => $config[$field]),
                        null, 1, 'listing_fields', 'row')
                ) {
                    $data['pName'] = 'listing_fields+name+' . $config[$field];
                    $fields[] = $data;
                }
            }

            if ($fields) {
                $fields = $rlCommon->fieldValuesAdaptation($fields, 'listing_fields', 'listings'); // TODO
                $rlSmarty->assign_by_ref('fields', $fields);
            } else {
                $group_id = $rlDb->getOne('ID', "`Key` = 'locationFinder'", 'config_groups');
                $href = $rlSmarty->_tpl_vars['rlBase'] . 'index.php?controller=settings&group=' . $group_id;
                $rlSmarty->assign_by_ref('href', $href);
            }
        }
        // No mapping case
        else {
            $rlSmarty->assign_by_ref('mapping_error', $mapping_error);
        }
        break;
}
