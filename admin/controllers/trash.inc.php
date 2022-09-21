<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: TRASH.INC.PHP
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

        $rlHook->load('apExtTrashUpdate');

        $rlActions->updateOne($updateData, 'trash_box');
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    if ($sort && $sortDir) {
        $sorting = "ORDER BY `{$sort}` {$sortDir}";
    }

    $where = array();
    $rlHook->load('apExtAccountFieldsSql');

    $rlDb->setTable('trash_box');
    $data = $rlDb->fetch('*', $where, $sorting, array($start, $limit));
    $rlDb->resetTable();

    foreach ($data as $key => $value) {
        /* define admin */
        $admin_info = $rlDb->fetch(array('User', 'Name'), array('ID' => $data[$key]['Admin_ID']), null, 1, 'admins', 'row');
        if (empty($admin_info)) {
            $data[$key]['Admin'] = $lang['owner'];
        } else {
            $data[$key]['Admin'] = empty($admin_info['Name']) ? $admin_info['User'] : $admin_info['Name'];
        }

        $table = $data[$key]['Zones'];
        if (false !== strpos($table, ',')) {
            $tables = explode(',', $table);
            $table = $tables[0];

            $data[$key]['Zones'] = $table;
        }

        /* define item */
        if (!empty($data[$key]['Criterion'])) {
            $item_info = $rlDb->fetch('*', null, "WHERE {$data[$key]['Criterion']}", 1, $table, 'row');

            switch ($table) {
                case 'admins':
                    $item = $item_info['User'];
                    break;

                case 'accounts':
                    $item = $item_info['Username'];
                    break;

                case 'listings':
                    $reefless->loadClass('Common');
                    $reefless->loadClass('Listings');
                    $listing_type = $rlDb->getOne('Type', "`ID` = '{$item_info['Category_ID']}'", 'categories');

                    $item = '#' . $item_info['ID'] . ' | <b>' . $rlListings->getListingTitle($item_info['Category_ID'], $item_info, $listing_type) . '</b>';
                    break;

                case 'news':
                    $phrase = $rlDb->fetch(array('Value'), array('Key' => $table . '+title+' . $item_info['ID']), null, 1, 'lang_keys', 'row');
                    $item = $phrase['Value'];
                    break;

                case 'contacts':
                    $item = $lang['from'] . ': <b>' . $item_info['Name'] . '</b>, message: ' . substr($item_info['Message'], 0, 60) . '...';
                    break;

                case 'categories':
                    $phrase = $rlDb->fetch(array('Value'), array('Key' => $table . '+name+' . $item_info['Key']), null, 1, 'lang_keys', 'row');
                    $item = $phrase['Value'];
                    break;

                case 'tmp_categories':
                    $item = $item_info['Name'];
                    $data[$key]['Zones'] = $lang['admin_controllers+name+custom_categories'];
                    break;

                case 'transactions':
                    $plan_info = $rlDb->fetch(array('Key', 'Type'), array('ID' => $item_info['Plan_ID']), null, 1, 'listing_plans', 'row');
                    $plan_type = $rlDb->fetch(array('Value'), array('Key' => $plan_info['Type'] . '_plan'), null, 1, 'lang_keys', 'row');
                    $plan_name = $rlDb->fetch(array('Value'), array('Key' => 'listing_plans+name+' . $plan_info['Key']), null, 1, 'lang_keys', 'row');
                    $item = $plan_type['Value'] . ' <b>(' . $lang['plan'] . ': ' . $plan_name['Value'] . ')</b>';
                    break;

                default:
                    $item_name = $item_info['Key'];
                    $item = $lang[$table . '+name+' . $item_name];
                    break;
            };

            $data[$key]['Item'] = $item;
        } else {
            $data[$key]['Item'] = $lang['na'];
        }
    }

    $rlHook->load('apExtTrashData');

    $count = $rlDb->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}trash_box`");

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */
else {
    if ($config['trash']) {
        /* register ajax methods */
        $rlXajax->registerFunction(array('restoreTrashItem', $rlAdmin, 'ajaxRestoreTrashItem'));
        $rlXajax->registerFunction(array('deleteTrashItem', $rlAdmin, 'ajaxDeleteTrashItem'));
        $rlXajax->registerFunction(array('clearTrash', $rlAdmin, 'ajaxClearTrash'));
        $rlXajax->registerFunction(array('massActions', $rlAdmin, 'ajaxTrashMassActions'));
    } else {
        $link = '<a class="dark_13" href="' . RL_URL_HOME . ADMIN . '/index.php?controller=settings">$1</a>';
        $alerts[] = preg_replace('/\[(.*?)\]/', $link, $lang['trash_box_desabled']);
        $rlSmarty->assign_by_ref('alerts', $alerts);
    }

    $rlHook->load('apPhpTrashBottom');
}
