<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PAYMENT.INC.PHP
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

$reefless->loadClass('Payment');
$reefless->loadClass('Mail');

$rlHook->load('topPaymentPage');

$step = $_GET['rlVareables'];
$rlSmarty->assign('step', $step);

switch ($step) {
    case rlPayment::CHECKOUT_URL:
        // set referer
        if (!$rlPayment->getReferer() && $_SERVER['HTTP_REFERER']) {
            $rlPayment->setReferer($_SERVER['HTTP_REFERER']);
        }
        /* add bread crumbs item */
        $bc_last = array_pop($bread_crumbs);
        $bc_options = $rlPayment->getBreadCrumbs();

        if ($bc_options) {
            if (count($bc_options) > 1) {
                foreach ($bc_options as $bcKey => $bcValue) {
                    $bread_crumbs[] = $bcValue;
                }
            } else {
                $bread_crumbs[] = $bc_options[0];
            }
        }
        $bread_crumbs[] = $bc_last;

        $rlHook->load('breadCrumbsPaymentPage');

        $rlPayment->checkout($errors, true);
        $transaction = $rlPayment->getTransaction();
        $rlSmarty->assign_by_ref('transaction', $transaction);
        break;

    case rlPayment::POST_URL:
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'post' || strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
            $gateway = $rlValid->xSql($_GET['gateway']);

            if ($gateway) {
                $rlHook->load('postPayment');

                $rlPayment->setGateway($gateway);
                $gateway_info = $rlPayment->getGatewayDetails();
                $rlGateway = rlPayment::getInstanceGateway($gateway, $gateway_info['Plugin']);

                $rlHook->load('postPaymentGateway', $rlGateway);
                $rlGateway->callBack();
            }
        } else {
            $sError = true;
        }
        break;

    case rlPayment::SUCCESS_URL:
        $reefless->loadClass('Notice');

        $transaction = $rlPayment->getTransaction();
        $rlSmarty->assign_by_ref('transaction', $transaction);
        $rlNotice->saveNotice($GLOBALS['lang']['payment_completed']);

        break;

    case rlPayment::FAIL_URL:
        $transaction = $rlPayment->getTransaction();
        $rlSmarty->assign_by_ref('transaction', $transaction);

        $errors[] = $GLOBALS['lang']['payment_canceled'];
        break;

    default:
        $sError = true;
        break;
}

$rlHook->load('bottomPaymentPage');
