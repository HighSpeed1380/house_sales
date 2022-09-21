<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: TRANSACTIONS.INC.PHP
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
        $field = $_GET['field'] == 'pStatus' ? 'Status' : $rlValid->xSql($_GET['field']);
        $value = $rlValid->xSql(nl2br($_GET['value']));
        $id = (int) $_GET['id'];
        $key = $rlValid->xSql($_GET['key']);

        // get transaction info
        $transaction = $rlDb->fetch('*', array('ID' => $id), null, null, 'transactions', 'row');

        $updateData = array(
            'fields' => array(
                $field => $value,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );

        $rlHook->load('apExtTransactionsUpdate');

        $action = $rlActions->updateOne($updateData, 'transactions');

        // send notification to user
        if ($action && $field == 'Status') {
            if ($value == 'paid' && $transaction['Status'] == 'unpaid') {
                $reefless->loadClass('Payment');
                $rlPayment->sendNotificationAfterPayment($id);
            }
        }
        exit;
    }

    /* data read */
    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];
    $sort = $rlValid->xSql($_GET['sort']);
    $sortDir = $rlValid->xSql($_GET['dir']);

    $search_fields = array(
        'username'     => '`T4`.`Username`',
        'name'         => "CONCAT_WS(' ', `T4`.`First_name`, `T4`.`Last_name`)",
        'email'        => '`T4`.`Mail`',
        'account_type' => '`T4`.`Type`',
        'item'         => '`T1`.`Service`',
        'txn_id'       => '`T1`.`Txn_ID`',
        'amount_from'  => '',
        'amount_to'    => '',
        'date_from'    => '',
        'date_to'      => '',
    );

    $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Key` AS `Plan_key`, `T2`.`Type` AS `Item`, `T4`.`Username`, ";
    $sql .= "CONCAT_WS(' ', `T4`.`First_name`, `T4`.`Last_name`) AS `Full_name` ";
    $sql .= "FROM `{db_prefix}transactions` AS `T1` ";
    $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}listings` AS `T3` ON `T1`.`Item_ID` = `T3`.`ID` ";
    $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T1`.`Account_ID` = `T4`.`ID` ";
    $sql .= "WHERE `T1`.`Status` <> 'trash' ";
    if ($_GET['search']) {
        foreach ($search_fields as $sf_key => $sf_field) {
            $field = $_GET[$sf_key];
            if (!empty($field)) {
                switch ($sf_key) {
                    case 'amount_from':
                        $amount_from = (int) $_GET['amount_from'];
                        $amount_to = (int) $_GET['amount_to'];
                        if ($amount_to) {
                            $sql .= "AND `T1`.`Total` BETWEEN $amount_from AND $amount_to ";
                        } else {
                            $sql .= "AND `T1`.`Total` >= $amount_from ";
                        }
                        break;
                    case 'amount_to':
                        $amount_from = (int) $_GET['amount_from'];
                        $amount_to = (int) $_GET['amount_to'];
                        if (!$amount_from) {
                            $sql .= "AND `T1`.`Total` =< $amount_to ";
                        }
                        break;

                    case 'date_from':
                        $date_from = $rlValid->xSql($_GET['date_from']);
                        $date_to = $rlValid->xSql($_GET['date_to']);
                        if ($date_to) {
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`Date`) BETWEEN UNIX_TIMESTAMP('$date_from') AND UNIX_TIMESTAMP('$date_to') ";
                        } else {
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`Date`) >= UNIX_TIMESTAMP('$date_from') ";
                        }
                        break;

                    case 'date_to':
                        $date_from = $rlValid->xSql($_GET['date_from']);
                        $date_to = $rlValid->xSql($_GET['date_to']);
                        if (!$date_from) {
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`Date`) <= UNIX_TIMESTAMP('$date_to') ";
                        }
                        break;

                    case 'name':
                        $words = explode(' ', $field);
                        $sql .= "AND ({$sf_field} LIKE '%" . implode("%' OR {$sf_field} LIKE '%", $words) . "%') ";
                        break;

                    default:
                        $sql .= "AND {$sf_field} = '{$field}' ";
                        break;
                }
            }
        }
    }

    if ($sort) {
        switch ($sort) {
            case 'Item':
                $sortField = "`T1`.`Service`";
                break;

            case 'Username':
                $sortField = "`T4`.`Username`";
                break;

            case 'pStatus':
                $sortField = "`T1`.`Status`";
                break;

            default:
                $sortField = "`T1`.`{$sort}`";
                break;
        }

        $sql .= "ORDER BY {$sortField} {$sortDir} ";
    }
    $sql .= "LIMIT {$start}, {$limit}";

    $rlHook->load('apExtTransactionsSql');

    $data = $rlDb->getAll($sql);

    $count = $rlDb->getRow("SELECT FOUND_ROWS() AS `count`");

    $rlHook->load('apExtTransactionsService', $payment_services_multilang);

    foreach ($data as $key => $value) {
        if (in_array($value['Service'], $payment_services_multilang) && !empty($value['Plan_key'])) {
            $data[$key]['Item'] = $lang[$value['Plan_key']];
        } else {
            $data[$key]['Item'] = $data[$key]['Item_name'];
        }
        $data[$key]['Status'] = $GLOBALS['lang'][$value['Status']];
        $data[$key]['Service'] = $GLOBALS['lang'][$value['Service'] == 'listing' || $value['Service'] == 'package' ? $value['Service'] . '_plan' : $value['Service']];
        $data[$key]['Gateway'] = $GLOBALS['lang']['payment_gateways+name+' . $value['Gateway']];

        /**
         * @since 4.6.0
         */
        $rlHook->load('apExtTransactionItem', $data[$key], $key, $value);
    }

    $rlHook->load('apExtTransactionsData');

    $output['total'] = $count['count'];
    $output['data'] = $data;

    echo json_encode($output);
}
/* ext js action end */

else {
    /* get account types */
    $reefless->loadClass('Account');
    $account_types = $rlAccount->getAccountTypes('visitor');
    $rlSmarty->assign_by_ref('account_types', $account_types);

    /* get possible service types */
    $rlDb->setTable('transactions');
    $items = $rlDb->fetch(array('Service'), null, "GROUP BY `Service`");
    $rlSmarty->assign_by_ref('items', $items);

    /* register ajax methods */
    $rlXajax->registerFunction(array('deleteTransaction', $rlAdmin, 'ajaxDeleteTransaction'));

    $rlHook->load('apPhpTransactionsBottom');
}
