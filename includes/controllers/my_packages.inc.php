<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MY_PACKAGES.INC.PHP
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

$reefless->loadClass('Subscription'); // >= v4.4

$sql = "SELECT `T1`.`Listings_remains`, `T1`.`Standard_remains`, `T1`.`Featured_remains`, `T1`.`Date`, `T1`.`IP`, `T1`.`ID`, `T1`.`Plan_ID`, ";
$sql .= "`T2`.`Key`, `T2`.`Featured`, `T2`.`Advanced_mode`, `T2`.`Standard_listings`, `T2`.`Featured_listings`, `T2`.`Price`, `T2`.`Type`, `T2`.`Color`, ";
$sql .= "`T2`.`Listing_period`, `T2`.`Plan_period`, `T2`.`Image`, `T2`.`Video`, `T2`.`Listing_number`, `T2`.`Status`, ";
$sql .= "`T2`.`Image_unlim`, `T2`.`Video_unlim`, ";
$sql .= "IF (`T2`.`Plan_period` = 0, 'unlimited', UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY))) AS `Exp_date`, ";
$sql .= "IF (`T2`.`Plan_period` > 0 AND UNIX_TIMESTAMP(DATE_ADD(`T1`.`Date`, INTERVAL `T2`.`Plan_period` DAY)) < UNIX_TIMESTAMP(NOW()), 'expired', 'active') AS `Exp_status`, ";
// subscription plan (>= v4.4)
$sql .= "`T3`.`Status` AS `Subscription`, `T3`.`ID` AS `Subscription_ID`, `T3`.`Service` AS `Subscription_service` ";
$sql .= "FROM `{db_prefix}listing_packages` AS `T1` ";
$sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
// subscription plan (>= v4.4)
$sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `T3` ON `T1`.`Plan_ID` = `T3`.`Plan_ID` AND `T3`.`Service` = 'listing' AND `T3`.`Status` = 'active' ";
$sql .= "WHERE `T1`.`Account_ID` = '{$account_info['ID']}' AND `T1`.`Type` = 'package' ";

$rlHook->load('myPackagesSql', $sql);

$sql .= "ORDER BY `T1`.`ID` DESC";

$packages = $rlDb->getAll($sql);
$packages = $rlLang->replaceLangKeys($packages, 'listing_plans', array('name', 'des'));

foreach ($packages as $key => $value) {
    $used_plans_id[] = $value['Plan_ID'];
    $packages_tmp[$value['ID']] = $value;
}
$packages = $packages_tmp;
unset($packages_tmp);

$rlSmarty->assign_by_ref('packages', $packages);
$rlSmarty->assign_by_ref('used_plans_id', $used_plans_id);

$reefless->loadClass('Notice');

$rlHook->load('phpMyPackagesTop');

if (isset($_GET['completed'])) {
    $rlNotice->saveNotice($lang['notice_package_payment_completed']);
} elseif (isset($_GET['canceled'])) {
    $rlNotice->saveNotice($lang['notice_package_payment_canceled'], 'alert');
}

if ($_GET['renew']) {
    /* add bread crumbs item */
    $bread_crumbs[] = array(
        'name' => $lang['renew'] . ' ' . $pack_info['name'],
    );

    $page_info['name'] = $lang['renew_package'];

    $rlXajax->registerFunction(array('cancelSubscription', $rlSubscription, 'ajaxCancelSubscription')); // >= v4.4

    $renew_id = (int) $_GET['renew'];
    $pack_info = $packages[$renew_id];

    if (!$pack_info) {
        $errors[] = $lang['renew_package_not_owner'];
    }

    $rlHook->load('phpMyPackagesRenewValidate');

    /* free package mode */
    if ($pack_info['Price'] <= 0 && !$errors) {
        /* renew free package */
        $rlListings->upgradePackage($renew_id, $pack_info['Plan_ID'], $account_info['ID'], 'free', 'free', 0, true);

        /* save notice */
        $reefless->loadClass('Notice');
        $rlNotice->saveNotice($lang['package_renewed']);

        /* redirect */
        $reefless->redirect(null, $reefless->getPageUrl('my_packages'));
    }

    if (!$errors) {
        $rlHook->load('phpMyPackagesRenewPreAction');

        $rlSmarty->assign_by_ref('renew_id', $renew_id);
        $rlSmarty->assign_by_ref('pack_info', $pack_info);

        if (!$rlPayment->isPrepare()) {
            //$rlPayment->clear();

            $return_url = $reefless->getPageUrl('my_packages') . ($config['mod_rewrite'] ? '?' : '&');
            $cancel_url = $return_url . 'canceled';
            $success_url = $return_url . 'completed';

            // set payment options
            $rlPayment->setOption('service', $pack_info['Type']);
            $rlPayment->setOption('total', $pack_info['Price']);
            $rlPayment->setOption('plan_id', $pack_info['Plan_ID']);
            $rlPayment->setOption('item_id', $renew_id);
            $rlPayment->setOption('item_name', $pack_info['name'] . " ({$lang['package_plan']})");
            $rlPayment->setOption('plan_key', 'listing_plans+name+' . $pack_info['Key']);
            $rlPayment->setOption('account_id', $account_info['ID']);
            $rlPayment->setOption('callback_class', 'rlListings');
            $rlPayment->setOption('callback_method', 'upgradePackage');
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);

            // set recurring option
            if ($pack_info['Subscription'] && $_POST['subscription'] == $pack_info['ID']) {
                $rlPayment->enableRecurring();
            }

            // set bread crumbs
            $rlPayment->setBreadCrumbs(array(
                'name' => $lang['pages+name+my_packages'],
                'title' => $lang['pages+title+my_packages'],
                'path' => $pages['my_packages'],
            ));

            $rlPayment->init($errors);
        } else {
            $rlPayment->checkout($errors, true);
        }
    }
} elseif ($_GET['nvar_1'] == 'purchase' || isset($_GET['purchase'])) {
    unset($_SESSION['complete_payment']);

    /* add bread crumbs item */
    $bread_crumbs[] = array(
        'name' => $lang['purchase_new_package'],
    );
    $page_info['name'] = $lang['purchase_new_package'];

    $rlSmarty->assign('purchase', true);

    $reefless->loadClass('Plan');

    /* get available plans */
    $available_packages = $rlPlan->getPlans('package', true);
    foreach ($available_packages as $key => $value) {
        $available_packages_tmp[$value['ID']] = $value;
    }
    $available_packages = $available_packages_tmp;
    unset($available_packages_tmp);
    $rlSmarty->assign_by_ref('available_packages', $available_packages);

    if ($_POST['action'] == 'submit') {
        $plan_id = $_POST['plan'];
        if (!$plan_id) {
            $errors[] = $lang['no_plan_chose'];
        }

        if ($used_plans_id && in_array($plan_id, $used_plans_id)) {
            $errors[] = $lang['duplicate_package_purchase_error'];
        }

        $rlHook->load('phpMyPackagesPurchaseValidate');

        if (!$errors) {
            $plan_info = $available_packages[$plan_id];

            $rlHook->load('phpMyPackagesPurchasePreAction');

            // paid plan way
            if ($plan_info['Price'] > 0) {
                $rlPayment->clear();
                $rlPayment->setRedirect();

                $return_url = $reefless->getPageUrl('my_packages') . ($config['mod_rewrite'] ? '?' : '&');
                $cancel_url = $return_url . 'canceled';
                $success_url = $return_url . 'completed';

                // set payment options
                $rlPayment->setOption('service', $plan_info['Type']);
                $rlPayment->setOption('total', $plan_info['Price']);
                $rlPayment->setOption('plan_id', $plan_info['ID']);
                $rlPayment->setOption('item_id', $plan_id);
                $rlPayment->setOption('item_name', $plan_info['name'] . " ({$lang['package_plan']})");
                $rlPayment->setOption('plan_key', 'listing_plans+name+' . $plan_info['Key']);
                $rlPayment->setOption('account_id', $account_info['ID']);
                $rlPayment->setOption('callback_class', 'rlListings');
                $rlPayment->setOption('callback_method', 'purchasePackage');
                $rlPayment->setOption('cancel_url', $cancel_url);
                $rlPayment->setOption('success_url', $success_url);

                // set recurring option
                if ($plan_info['Subscription'] && $_POST['subscription'] == $plan_info['ID']) {
                    $rlPayment->enableRecurring();
                }

                // set bread crumbs
                $rlPayment->setBreadCrumbs(array(
                    'name' => $lang['pages+name+my_packages'],
                    'title' => $lang['pages+title+my_packages'],
                    'path' => $pages['my_packages'],
                ));

                $rlPayment->init($errors);
            } else {
                /* purchace free package */
                $rlListings->purchasePackage($plan_id, $plan_id, $account_info['ID'], 'free', 'free', 0, true);

                /* save notice */
                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['free_package_purchase_notice']);

                /* redirect */
                $reefless->redirect(null, $reefless->getPageUrl('my_packages'));
            }
        }
    }
}

$rlHook->load('phpMyPackagesBottom');

$rlXajax->registerFunction(array('cancelSubscription', $rlSubscription, 'ajaxCancelSubscription')); // >= v4.4
