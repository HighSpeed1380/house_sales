<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SAVED_SEARCHES.INC.PHP
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

        /* update cache */
        $reefless->loadClass('Cache');
        $rlCache->updateForms();

        $rlHook->load('apExtSavedSearchesUpdate');

        $rlActions->updateOne($updateData, 'saved_search');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Username` ";
    $sql .= "FROM `{db_prefix}saved_search` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtSavedSearchesSql');

    $data  = $rlDb->getAll($sql);
    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $lang[$value['Status']];
        $data[$key]['name'] = $lang['listing_types+name+' . $value['Listing_type']] . ' (' . $lang['search_forms+name+' . $value['Form_key']] . ')';
    }

    $rlHook->load('apExtSavedSearchesData');

    $output['total'] = $count['count'];
    $output['data']  = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    /* additional bread crumb step */
    if ($_GET['action'] == 'view') {
        $reefless->loadClass('Message');

        $rlXajax->registerFunction(array('contactOwner', $rlMessage, 'ajaxContactOwnerAP'));
        $rlXajax->registerFunction(array('checkSavedSearch', $rlAdmin, 'ajaxCheckSavedSearch'));

        $bcAStep = $lang['view_details'];
        $id = (int) $_GET['id'];

        /* get saved search details */
        $saved_search = $rlDb->fetch('*', array('ID' => $id), null, 1, 'saved_search', 'row');
        $saved_search['Content'] = unserialize($saved_search['Content']);

        if ($saved_search['Content']) {
            $tmp_fields = $rlDb->fetch(array('Key', 'Type', 'Condition', 'Default'), array('Status' => 'active'), null, null, 'listing_fields');
            $tmp_fields = $rlLang->replaceLangKeys($tmp_fields, 'listing_fields', array('name'));

            foreach ($tmp_fields as $k => $v) {
                $fields[$v['Key']] = $v;
            }
            unset($tmp_fields);

            foreach ($saved_search['Content'] as $cKey => $field) {
                $tmp_content = array();

                if (isset($fields[$cKey])) {
                    $tmp_content['Type'] = $fields[$cKey]['Type'];
                    $tmp_content['Default'] = $fields[$cKey]['Default'];
                    $tmp_content['Condition'] = $fields[$cKey]['Condition'];
                    $tmp_content['name'] = $fields[$cKey]['name'];

                    if ($fields[$cKey]['Type'] == 'mixed') {
                        $tmp_content['value'] = $field;
                        if (empty($fields[$cKey]['Condition'])) {
                            $tmp_content['value']['df'] = $lang['listing_fields+name+' . $field['df']];
                        } else {
                            $tmp_content['value']['df'] = $lang['data_formats+name+' . $field['df']];
                        }
                    } elseif ($fields[$cKey]['Type'] == 'date') {
                        $tmp_content['value'] = $field;
                    } elseif ($fields[$cKey]['Type'] == 'price') {
                        $tmp_content['value'] = $field;
                        $tmp_content['value']['currency'] = $lang['data_formats+name+' . $field['currency']];
                    } elseif ($fields[$cKey]['Type'] == 'unit') {
                        $tmp_content['value'] = $field;
                        $tmp_content['value']['unit'] = $lang['data_formats+name+' . $field];
                    } elseif ($fields[$cKey]['Type'] == 'checkbox') {
                        $tmp_content['value'] = $rlCommon->adaptValue($fields[$cKey], implode(',', $field));
                    } elseif ($fields[$cKey]['Key'] == 'Category_ID') {
                        $categoryKey = $rlDb->getOne('Key', "`ID` = {$field}", 'categories');
                        $tmp_content['value'] = $rlLang->getPhrase('categories+name+' . $categoryKey);
                    } else {
                        $tmp_content['value'] = $rlCommon->adaptValue($fields[$cKey], $field);
                    }
                }

                $saved_search['fields'][] = $tmp_content;
            }

            unset($tmp_content, $fields);
            $rlSmarty->assign_by_ref('saved_search', $saved_search);
        }

        /* get profile details */
        $reefless->loadClass('Account');
        $profile_data = $rlAccount->getProfile((int) $saved_search['Account_ID']);
        $rlSmarty->assign_by_ref('profile_data', $profile_data);
    }

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteSavedSearch', $rlAdmin, 'ajaxDeleteSavedSearch'));

    $rlHook->load('apPhpSavedSearchesBottom');
}
