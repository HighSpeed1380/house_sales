<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SAVED_SEARCH.INC.PHP
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

/* get account saved search */
$rlDb->setTable('saved_search');
$saved_search = $rlDb->fetch(
    array('ID', 'Listing_type', 'Content', 'Date', 'Status'),
    array('Account_ID' => $account_info['ID']),
    'ORDER BY `ID`'
);

$rlHook->load('savedSearchTop');

if (!empty($saved_search)) {
    $tmp_fields = $rlDb->fetch(
        array('Key', 'Type', 'Condition', 'Default'),
        array('Status' => 'active'),
        null,
        null,
        'listing_fields'
    );
    $tmp_fields = $rlLang->replaceLangKeys($tmp_fields, 'listing_fields', array('name'));

    $fields = array();
    foreach ($tmp_fields as $tmp_key => $tmp_field) {
        $fields[$tmp_field['Key']] = $tmp_field;
    }
    unset($tmp_fields);

    foreach ($saved_search as $key => $value) {
        $content     = unserialize($saved_search[$key]['Content']);
        $tmp_content = false;
        $step        = 0;

        foreach ($content as $cKey => $cVal) {
            if (isset($fields[$cKey])) {
                $tmp_content[$step]['Type'] = $fields[$cKey]['Type'];
                $tmp_content[$step]['Default'] = $fields[$cKey]['Default'];
                $tmp_content[$step]['Condition'] = $fields[$cKey]['Condition'];
                $tmp_content[$step]['name'] = $fields[$cKey]['name'];

                if ($fields[$cKey]['Type'] == 'number') {
                    $tmp_content[$step]['value'] = $content[$cKey];
                } elseif ($fields[$cKey]['Type'] == 'mixed') {
                    $tmp_content[$step]['value'] = $content[$cKey];
                    if (empty($fields[$cKey]['Condition'])) {
                        $tmp_content[$step]['value']['df'] = $lang['listing_fields+name+' . $content[$cKey]['df']];
                    } else {
                        $tmp_content[$step]['value']['df'] = $lang['data_formats+name+' . $content[$cKey]['df']];
                    }
                } elseif ($fields[$cKey]['Type'] == 'date') {
                    $tmp_content[$step]['value'] = $content[$cKey];
                } elseif ($fields[$cKey]['Type'] == 'price') {
                    if (!$system_currency) {
                        $reefless->loadClass('Categories');
                        $system_currency = $rlCategories->getDF('currency');
                    }

                    $tmp_content[$step]['value'] = $content[$cKey];
                    $tmp_content[$step]['value']['currency'] = !$content[$cKey]['currency'] && count($system_currency) == 1
                    ? $lang[$system_currency[0]['pName']]
                    : $lang['data_formats+name+' . $content[$cKey]['currency']];
                } elseif ($fields[$cKey]['Type'] == 'unit') {
                    $tmp_content[$step]['value'] = $content[$cKey];
                    $tmp_content[$step]['value']['unit'] = $lang['data_formats+name+' . $content[$cKey]['unit']];
                } elseif ($fields[$cKey]['Type'] == 'checkbox') {
                    $tmp_content[$step]['value'] = $rlCommon->adaptValue($fields[$cKey], implode(',', $content[$cKey]));
                } elseif ($fields[$cKey]['Key'] == 'Category_ID') {
                    $cat_name = $rlDb->fetch(array('Key'), array('ID' => $content[$cKey]), null, 1, 'categories', 'row');
                    $phraseKey = 'categories+name+' . $cat_name['Key'];
                    $tmp_content[$step]['value'] = $rlLang->getPhrase($phraseKey, RL_LANG_CODE, false, true);
                } elseif ($fields[$cKey]['Key'] == 'posted_by') {
                    $account_type = $rlAccount->getTypeDetails($cVal);
                    $tmp_content[$step]['value'] = $account_type['name'] ? $account_type['name'] : strtoupper($cVal);
                } else {
                    $tmp_content[$step]['value'] = $rlCommon->adaptValue($fields[$cKey], $content[$cKey]);
                }
            }
            $step++;
        }
        $saved_search[$key]['fields'] = $tmp_content;
        unset($tmp_content);
    }
    unset($fields, $content);

    $rlSmarty->assign_by_ref('saved_search', $saved_search);

    $reefless->loadClass('Search');
    $reefless->loadClass('Actions');

    $rlHook->load('savedSearchBottom');
}
