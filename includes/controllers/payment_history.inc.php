<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PAYMENT_HISTORY.INC.PHP
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

if (!defined('IS_LOGIN')) {
    $sError = true;
} else {
    $rlHook->load('phpPaymentHistoryTop');

    $reefless->loadClass('Plan');

    $pInfo['current'] = (int) $_GET['pg'];
    $page = $pInfo['current'] ? $pInfo['current'] - 1 : 0;

    $from = intval($page * $config['transactions_per_page']);
    $limit = intval($config['transactions_per_page']);

    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT * ";
    $sql .= "FROM `{db_prefix}transactions` ";
    $sql .= "WHERE `Status` <> 'trash' AND `Account_ID` = '{$account_info['ID']}' ";

    $rlHook->load('paymentHistorySqlWhere', $sql);

    $sql .= "ORDER BY `Date` DESC LIMIT {$from}, {$limit}";
    $transactions = $rlDb->getAll($sql);

    $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");

    $pInfo['calc'] = $calc['calc'];
    $rlSmarty->assign_by_ref('pInfo', $pInfo);

    foreach ($transactions as $key => &$item) {
        if (in_array($item['Service'], $payment_services_multilang) && !empty($item['Plan_key'])) {
            $transactions[$key]['Item_name'] = '';
        }
        if ($item['Plan_key']) {
            $transactions[$key]['Plan_name'] = $GLOBALS['lang'][$item['Plan_key']];
        }
        if (array_key_exists($item['Service'], $l_plan_types)) {
            if (in_array($item['Service'], array('listing', 'featured'))) {
                $item_details = $rlListings->getListing($item['Item_ID'], true);

                if ($item_details) {
                    $transactions[$key]['link'] = $item_details ? $item_details['listing_link'] : false;
                }
            } else {
                $rlHook->load('phpPaymentHistoryDefault', $item);
            }
        } else {
            $rlHook->load('phpPaymentHistoryLoop', $item);
        }

        unset($plan_info, $item_details);
    }

    $rlHook->load('phpPaymentHistoryBottom');

    $rlSmarty->assign_by_ref('transactions', $transactions);

    if (empty($transactions) && $pInfo['current'] > 0) {
        $reefless->redirect(null, $reefless->getPageUrl('payment_history'));
    }
}
