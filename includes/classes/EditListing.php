<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: EDITLISTING.PHP
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

namespace Flynax\Classes;

use Flynax\Utils\Category;
use Flynax\Utils\Util;

/**
 * @since 4.6.0
 */
class EditListing extends ManageListing
{
    /**
     * Initialize the add listing process
     *
     * @param array $page_info    - current page information array
     * @param array $account_info - current account information array
     * @param array $errors       - controller errors
     */
    public function init(&$page_info = null, &$account_info = null, &$errors = null)
    {
        global $rlSmarty, $lang, $config, $rlDb;

        // Unset unnecessary steps
        unset(
            $this->steps['category'],
            $this->steps['plan']
        );

        // Add final step
        $this->steps['done'] = array(
            'name' => $lang['reg_done'],
            'path' => 'done',
        );

        // Initialize model
        parent::init();

        // Get requested listing data
        $this->getListingData($account_info, $errors);

        // Stop the process if any error exists
        if ($errors) {
            $rlSmarty->assign('no_access', true);
            return;
        }

         // Define allowed types
        $allowed_type_keys = $account_info['Abilities'];

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpEditListingInitBeforeTypes', $this, $allowed_type_keys, $page_info, $account_info, $errors);

        // Adapt listing types array
        $allowed_types = $GLOBALS['rlListingTypes']->adaptTypes($allowed_type_keys);
        $rlSmarty->assign_by_ref('allowed_types', $allowed_types);

        // Define single category mode
        $this->defineSingleCategory($allowed_types);

        // Define current category
        $this->category = Category::getCategory($this->newCategoryID ?: $this->listingData['Category_ID']);

        // Fetch plans
        $this->fetchPlans($account_info);

        // Existing membership plan mode
        $this->existingMembershipHandler($account_info);

        // Register object in Smarty
        $rlSmarty->assign_by_ref('manageListing', $this);

        // Define related listing type
        if ($this->category) {
            $this->listingType = $GLOBALS['rlListingTypes']->types[$this->category['Type']];
            $rlSmarty->assign_by_ref('listing_type', $this->listingType);
        }

        // Add bread crumbs
        $this->breadCrumbs();

        // Define current plan
        $this->planID = $this->listingData['Plan_ID'];

        // Location finder fallback: TODO remove
        if ($GLOBALS['aHooks']['locationFinder']) {
            $GLOBALS['listing_type']['Location_finder'] = true;
        }

        /**
         * @since 4.6.0 - all parameters
         */
        $GLOBALS['rlHook']->load('editListingSteps', $this, $page_info, $account_info, $errors);

        // Unset photo step depending of plan
        if ($plan_info = $this->plans[$this->planID]) {
            if (
                ((!$plan_info['Image'] && !$plan_info['Image_unlim']) || !$this->listingType['Photo'])
                && ((!$plan_info['Video'] && !$plan_info['Video_unlim']) || !$this->listingType['Video'])
            ) {
                unset($this->steps['photo']);
            }
        }
    }

    /**
     * Modify main bread crumbs, add My Listings page as a parent page.
     */
    private function breadCrumbs()
    {
        global $bread_crumbs, $lang;

        $my_page_key = $GLOBALS['config']['one_my_listings_page'] ? 'my_all_ads' : $this->listingType['My_key'];

        if (!$my_page_key) {
            return;
        }

        $last = array_pop($bread_crumbs);
        $bread_crumbs[] = array(
            'name'  => $lang['pages+name+' . $my_page_key],
            'title' => $lang['pages+title+' . $my_page_key],
            'path'  => $GLOBALS['pages'][$my_page_key],
        );
        $bread_crumbs[] = $last;
    }

    /**
     * Get current listing data from database
     *
     * @param array $account_info - current account information array
     * @param array $errors       - controller global errors
     */
    private function getListingData(&$account_info, &$errors = null)
    {
        global $rlDb;

        $this->listingID = (int) $_GET['id'];

        // Get data
        $sql = "
            SELECT `T1`.*, `T2`.`Cross` AS `Plan_crossed`, `T2`.`Key` AS `Plan_key`,
            `T3`.`Type` AS `Listing_type`
            FROM `{db_prefix}listings` AS `T1`
            LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID`
            LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID`
            WHERE `T1`.`ID` = {$this->listingID} LIMIT 1
        ";

        $this->listingData = $rlDb->getRow($sql);

        // Check owner
        if (!$this->listingData
            || $this->listingData['Account_ID'] != $account_info['ID']) {
            $errors[] = $GLOBALS['lang']['edit_listing_wrong_owner'];
        }

        // Set plan type
        $this->planType = $this->listingData['Plan_type'];
    }

    /**
     * Plan step handler
     * @return [type] [description]
     */
    public function stepPlan()
    {
        // Return data to post
        $this->simulatePost(array('ad_type', 'subscription', 'plan'));

        // Prepare plans
        $this->fetchPlans($GLOBALS['account_info']);
    }

    /**
     * Form step handler
     */
    public function stepForm()
    {
        parent::step();

        global $reefless, $rlListings, $rlAccountTypes, $rlAccount, $rlCommon, $errors,
        $error_fields, $rlSmarty, $lang, $account_info, $config, $rlDb, $rlHook;

        // Legacy add listing version and plugins compatibility support
        $this->legacySupport();

        // Get and assign plan info to SMARTY
        $plan_info = $this->plans[$this->planID];
        $rlSmarty->assign('plan_info', $plan_info);

        // Build form
        $form = Category::buildForm(
            $this->category,
            $this->listingType,
            $this->formFields
        );
        $rlSmarty->assign_by_ref('form', $form);

        // Sssign account address on map fields to smarty
        $rlAccount->accountAddressAssign();

        // Assign parent category keys to SMARTY
        $this->prepareParentCategoryKeys();

        // Simulate listing data in post if listing already saved
        $this->simulateListingInPost('editListingPostSimulation');

        // Crossed categories handler
        $this->simulateCrossedCategoriesInPost();

        if ($_POST['step'] == 'form') {
            $data = &$_POST['f'];
            $info = array();

            // Save post data
            $this->savePost(array('plan', 'ad_type', 'crossed_categories'));

            // Checkout step handler
            $this->isCheckout($info, $plan_info);

            // Validate selected plan
            $this->validatePlan($errors, $error_fields);

            // Check form fields
            if ($form_errors = $rlCommon->checkDynamicForm($data, $this->formFields)) {
                $errors = array_merge($errors, $form_errors);
                $rlSmarty->assign('fixed_message', true);

                if ($rlCommon->error_fields) {
                    $error_fields .= $rlCommon->error_fields;
                    $rlCommon->error_fields = false;
                }
            }

            /**
             * @since 4.6.0 - All parameters
             */
            $rlHook->load('editListingDataChecking', $this, $data, $errors, $error_fields);

            if (!$errors) {
                $reefless->loadClass('Listings');

                if (!$config['edit_listing_auto_approval']) {
                    $info['Status'] = 'pending';
                }

                if ($this->newCategoryID) {
                    $info['Category_ID'] = $this->newCategoryID;
                }

                $info['Crossed'] = $this->postData['crossed_categories'];

                /**
                 * @since 4.6.0 - All parameters
                 */
                $rlHook->load('editListingAdditionalInfo', $this, $data, $info);

                // copy account address to listing
                $rlAccount->accountAddressAdd($data);

                if ($rlListings->edit($this->listingID, $info, $data, $this->formFields, $plan_info)) {
                    /**
                     * @since 4.6.0 - All parameters
                     */
                    $rlHook->load('afterListingEdit', $this, $info, $data);

                    $this->changeCategoryHandler();
                    $this->notifyPendingStatus($account_info);
                    $this->recountListings($account_info, $plan_info);
                }

                $this->redirectToNextStep();
            }
        }

        // Prepare crossed categories data
        $this->prepareCrossedCategories($plan_info, $account_info);
    }

    /**
     * Define is checkout step required
     *
     * @param  array &$info     - common listing data
     * @param  array $page_info - current page information array
     */
    private function isCheckout(&$info, &$plan_info)
    {
        $skip = false;

        // Skip in membership mode
        if ($this->planType == 'account') {
            $skip = true;
        }

        $new_plan_id = $this->postData['plan'];

        // Listing plan validation
        if ($this->planType == 'listing') {
            if ($this->planID == $new_plan_id) {
                $skip = true;
            } else {
                $plan_info = $this->plans[$new_plan_id];

                // New free plan or existing package mode
                if (
                    (
                        !$plan_info['Package_ID']
                        && intval($plan_info['Price']) <= 0
                    )
                    || (
                        $plan_info['Package_ID']
                        && $plan_info['Listings_remains'] > 0
                    )
                ) {
                    $skip = true;

                    $this->planID = $new_plan_id;
                    $info['Plan_ID'] = $new_plan_id;

                    if ($plan_info['Featured']) {
                        $info['Featured_ID'] = $new_plan_id;
                    }
                }
            }
        }

        // Skip checkout
        if ($skip) {
            unset($this->steps['checkout']);
        }
    }

    /**
     * Done step handler
     */
    public function stepDone()
    {
        global $reefless;

        $reefless->loadClass('Notice');
        $GLOBALS['rlNotice']->saveNotice($GLOBALS['lang']['notice_listing_edited']);

        // Build redirect url
        $page_key = $GLOBALS['config']['one_my_listings_page']
        ? 'my_all_ads'
        : $this->listingType['My_key'];
        $url = $reefless->getPageUrl($page_key);

        // Remove instance
        parent::removeInstance();

        // Redirect
        Util::redirect($url);
    }

    /**
     * Build additional url get params
     *
     * @return string - url get params
     */
    public function buildGetParam()
    {
        $get = $GLOBALS['config']['mod_rewrite'] ? '?' : '&';
        $get .= 'id=' . $this->listingID;

        return $get;
    }

    /**
     * Parent method extended by "id" postfix
     */
    public function buildPrevStepURL($aParams, $extend = null)
    {
        return parent::buildPrevStepURL($aParams) . $this->buildGetParam();
    }

    /**
     * Parent method extended by buildGetParam()
     */
    public function buildNextStepURL($extend = null)
    {
        return parent::buildNextStepURL() . $this->buildGetParam();
    }

    /**
     * Parent method extended by buildGetParam()
     */
    public function buildFormAction($aParams)
    {
        return parent::buildFormAction($aParams) . $this->buildGetParam();
    }

    /**
     * Parent method extended by buildGetParam()
     */
    public function redirectToNextStep($extend = null)
    {
        if ($GLOBALS['config']['mod_rewrite']) {
            $extend = array(
                'type' => 'param',
                'data' => $this->buildGetParam(),
            );
        } else {
            $extend = array(
                'key'   => 'id',
                'value' => $this->listingID,
            );
        }

        parent::redirectToNextStep($extend);
    }

    /**
     * Simulate crossed categories in POST
     */
    private function simulateCrossedCategoriesInPost()
    {
        if (isset($_POST['from_post'])) {
            return;
        }

        $_POST['crossed_categories'] = $this->listingData['Crossed'];
        $this->savePost('crossed_categories');
    }

    /**
     * Recount related listing categories counters
     *
     * @param array &$account_info - user account information
     * @param array $plan_info     - plan data
     */
    private function recountListings(&$account_info, &$plan_info)
    {
        global $rlCategories;

        // Crossed category related
        $post_crossed = &$this->postData['crossed_categories'];

        if ($post_crossed && $this->listingData['Crossed'] != implode(',', $post_crossed)) {
            if ($this->listingData['Crossed']) {
                foreach (explode(',', $this->listingData['Crossed']) as $id) {
                    $rlCategories->listingsDecrease($id);
                }
            }

            if ($GLOBALS['config']['edit_listing_auto_approval']) {
                if ($plan_info['Cross'] > 0 && !empty($post_crossed)) {
                    foreach ($post_crossed as $id) {
                        $rlCategories->listingsIncrease($id);
                    }
                }
            }
        }

        // Main listing category related
        if (!$GLOBALS['config']['edit_listing_auto_approval']) {
            $rlCategories->listingsDecrease($this->category['ID']);
            $rlCategories->accountListingsDecrease($account_info['ID']);
        }
    }

    /**
     * Category change handler
     * Fires once the user change the listing category
     */
    private function changeCategoryHandler()
    {
        global $rlCategories, $rlDb;

        if (!$this->newCategoryID
            || $this->listingData['Category_ID'] == $this->newCategoryID
            || $rlDb->getOne('Lock', "`ID` = {$this->newCategoryID}", 'categories')) {
            return;
        }

        $rlCategories->listingsDecrease($this->listingData['Category_ID']);
        $rlCategories->listingsIncrease($this->newCategoryID);
    }

    /**
     * Notify administrator and listing owner about pending
     * status after listing edit
     *
     * @param array &$account_info - user account information
     */
    private function notifyPendingStatus(&$account_info)
    {
        global $config, $reefless, $rlMail;

        $listing_data_before_update = $this->listingData;

        $this->getListingData($account_info);

        if ($config['edit_listing_auto_approval']
            || serialize($listing_data_before_update) == serialize($this->listingData)) {
            return;
        }

        // Save flag
        $this->pendingAfterEdit = false;

        $reefless->loadClass('Mail');

        // Notify administrator
        $mail_tpl = $rlMail->getEmailTemplate('admin_listing_edited');

        $link = $reefless->url('listing', $this->listingID);
        $activation_url = RL_URL_HOME
        . ADMIN
        . '/index.php?controller=listings&action=remote_activation&id='
        . $this->listingID
        . '&hash=' . md5($this->listingData['Date']);
        $activation_link = '<a href="' . $activation_url . '">' . $activation_url . '</a>';

        $listingTitle = $GLOBALS['rlListings']->getListingTitle($this->listingData['Category_ID'], $this->listingData);

        $details_url = RL_URL_HOME
        . ADMIN
        . '/index.php?controller=listings&action=view&id='
        . $this->listingID;
        $details_link = '<a href="' . $details_url . '">' . $listingTitle . '</a>';

        $replace = array(
            $account_info['Full_name'],
            $details_link,
            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            $GLOBALS['lang']['suspended'],
            $activation_link,
        );
        $mail_tpl['body'] = str_replace(
            array('{name}', '{link}', '{date}', '{status}', '{activation_link}'),
            $replace,
            $mail_tpl['body']
        );
        $rlMail->send($mail_tpl, $config['notifications_email']);

        // Notify listing owner
        $mail_tpl = $rlMail->getEmailTemplate('edit_listing_pending');
        $mail_tpl['body'] = preg_replace(
            '/\[(.+)\]/',
            '<a href="' . $link . '">$1</a>',
            $mail_tpl['body']
        );
        $mail_tpl['body'] = str_replace(
            '{name}',
            $account_info['Full_name'],
            $mail_tpl['body']
        );
        $rlMail->send($mail_tpl, $account_info['Mail']);
    }

    /**
     * Validate selected plan
     * @param array &$errors       - global errors
     * @param array &$error_fields - global error fields
     */
    private function validatePlan(&$errors, &$error_fields)
    {
        global $lang;

        // No plan error
        if (!$this->postData['plan']) {
            $phrase_key = $this->Plan_type == 'account'
            ? 'notice_membership_plan_does_not_chose'
            : 'notice_listing_plan_does_not_chose';
            $errors[] = $lang[$phrase_key];
            $error_fields .= 'plan,';
        } else if ($this->postData['plan'] != $this->planID) {
            $plan_info = $this->plans[$this->postData['plan']];

            // Check plan using data
            if ($plan_info['Limit'] > 0 && $plan_info['Using'] == 0 && $plan_info['Using'] != '') {
                $errors[] = $lang['plan_limit_using_hack'];
            }
        }
    }

    /**
     * Mock method
     */
    protected function saveStepPointer()
    {
        // No need to save step pointer in Edit Listing mode
    }

    /**
     * Mock method
     */
    protected function extendUrl()
    {
        // No need to extend url in Edit Listing mode
    }

    /**
     * Mock method
     */
    protected function createBlankListing(&$account_info)
    {
        // No need to create blank listing in Edit Listing mode
    }
}
