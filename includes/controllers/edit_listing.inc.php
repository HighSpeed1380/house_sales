<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: EDIT_LISTING.INC.PHP
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

use Flynax\Classes\EditListing;

if (isset($_REQUEST['xjxfun'])) {
    die('xajax restricted in "add_listing" controller');
}

$errors = array();

/**
 * @since 4.6.0
 */
$rlHook->load('editListingTop', $errors, $steps);

if ($_GET['id']) {
    // Register CSS
    $rlStatic->addHeaderCss(RL_TPL_BASE . 'controllers/add_listing/add_listing.css', $page_info['Controller']);

    // Remove instance
    if (!$_POST['from_post']
        && !array_key_exists($_GET['nvar_1'], $steps)
        && !$_GET['step']
        && !isset($_GET['edit'])
    ) {
        EditListing::removeInstance();
    }

    // Get/create editListing instance
    $editListing = EditListing::getInstance();

    /**
     * @since 4.6.0
     */
    $rlHook->load('editListingBeforeInit', $editListing);

    // Set default config
    $edit_listing_config = [
        'singleStep' => true,
        'controller' => 'edit_listing',
        'pageKey'    => $page_info['Key'],
        'steps'      => &$steps,
    ];
    $editListing->setConfig($edit_listing_config);

    // Initialize
    $editListing->init($page_info, $account_info, $errors);

    // Process step
    $editListing->processStep();

    /**
     * @since 4.6.0
     */
    $rlHook->load('editListingBottom', $editListing);

    // Save instance
    EditListing::saveInstance($editListing);
} else {
    $sError = true;
}
