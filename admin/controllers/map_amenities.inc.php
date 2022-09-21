<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MAP_AMENITIES.INC.PHP
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

        if ($field == 'Default') {
            $value = ($value == 'true') ? '1' : '0';
        }

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtMapAmenitiesUpdate');

        $rlActions->updateOne($updateData, 'map_amenities');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $condition = "WHERE `Status` <> 'trash' ORDER BY `Key` ASC";
    $rlHook->load('apExtMapAmenitiesSql');

    $rlDb->setTable('map_amenities');
    $data = $rlDb->fetch('*', null, $condition, array($start, $limit));
    $data = $rlLang->replaceLangKeys($data, 'map_amenities', array('name'), RL_LANG_CODE, 'admin');
    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        $data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
        $data[$key]['Default'] = (bool) $data[$key]['Default'];
    }

    $rlHook->load('apExtMapAmenitiesData');

    $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}map_amenities` WHERE `Status` <> 'trash'");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    /* get all languages */
    $allLangs = $GLOBALS['languages'];
    $rlSmarty->assign_by_ref('allLangs', $allLangs);

    /* register ajax methods */
    $rlXajax->registerFunction(array('addAmenity', $rlAdmin, 'ajaxAddAmenity'));
    $rlXajax->registerFunction(array('editAmenity', $rlAdmin, 'ajaxEditAmenity'));
    $rlXajax->registerFunction(array('deleteAmenity', $rlAdmin, 'ajaxDeleteAmenity'));

    $rlHook->load('apPhpMapAmenitiesTop');
}
