<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ADD_LISTING.INC.PHP
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

use Flynax\Classes\AddListing;

if (isset($_REQUEST['xjxfun'])) {
    die('xajax restricted in "add_listing" controller');
}

$errors = array();
$no_access = false;

// Bot mode
if (true === IS_BOT) {
    $no_access = true;
    $page_info['Login'] = true;
    $page_info['Controller'] = 'login';
}

// Define plan type and print related error
if (!$plan_type = $rlMembershipPlan->defineAllowedPlanType()) {
    $rlMembershipPlan->handleRestrictedAccess($errors);
}

// Show error if "restore listing from incomplete" request came from not logged user
if ($_GET['incomplete'] && !defined('IS_LOGIN')) {
    $no_access = true;
    $errors[] = $lang['notice_should_login'];

    $page_info['Controller'] = 'login';
}

/**
 * @since 4.6.0 - all parameters
 */
$rlHook->load('addListingTop', $steps, $errors, $no_access, $plan_type);

$rlSmarty->assign('no_access', $no_access);

// Register CSS
$rlStatic->addHeaderCss(RL_TPL_BASE . 'controllers/add_listing/add_listing.css', $page_info['Controller']);

// Register JS
$rlStatic->addJs(RL_TPL_BASE . 'controllers/add_listing/manage_listing.js', $page_info['Controller']);

$get_step = $_GET['nvar_1'] ?: $_GET['step'];

// Remove instance
if ($config['add_listing_single_step']
    && !$_POST['from_post']
    && !array_key_exists($get_step, $steps)
    && $get_step != 'done'
    && !isset($_GET['edit'])
) {
    AddListing::removeInstance();
}

// Get/create addListing instance
$addListing = AddListing::getInstance();

/**
 * @since 4.6.0
 */
$rlHook->load('addListingBeforeInit', $addListing);

// Set default config
$add_listing_config = [
    'singleStep' => (bool) $config['add_listing_single_step'],
    'controller' => 'add_listing',
    'pageKey'    => $page_info['Key'],
    'steps'      => &$steps,
    'planType'   => $plan_type,
];
$addListing->setConfig($add_listing_config);

// Initialize
$addListing->init($page_info, $account_info, $errors);

// Process step
$addListing->processStep();

/**
 * @since 4.6.0 - $addListing
 */
$rlHook->load('addListingBottom', $addListing);

// Save instance
AddListing::saveInstance($addListing);
