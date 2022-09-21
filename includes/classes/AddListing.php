<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ADDLISTING.PHP
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
use Flynax\Utils\ListingMedia;
use Flynax\Utils\Util;
use Flynax\Utils\Valid;

class AddListing extends ManageListing
{
    /**
     * Is new membership account
     *
     * @var int
     */
    public $newMembership = false;

    /**
     * User category path prefix
     *
     * @var array
     */
    public $userCategoryPathPrefix = 'user-category-';

    /**
     * Force instance removal flag
     *
     * @var bool
     */
    public $forceInstanceRemoval = false;

    /**
     * Add Listing from category mode
     *
     * @var boolean
     */
    private $fromCategory = false;

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

        // Legacy add listing version and plugins compatibility support
        $this->legacySupport();

        // Add final step
        $this->steps['done'] = array(
            'name' => $lang['reg_done'],
            'path' => 'done',
        );

        // Initialize model
        parent::init();

        // Reset the instance if the user started the process over
        if (!isset($_GET['edit'])
            && $this->step == 'category'
            && ($page_info['prev'] && $page_info['prev'] != $page_info['Key'])
            && !isset($_GET['incomplete'])
        ) {
            $this->forceInstanceRemoval = true;
        }

        // Simulate visitor account
        $this->simulateVisitorAccount($account_info);

        // Define allowed types
        $allowed_type_keys = $account_info['Abilities'];

        // "Individual add listing page" mode
        if ($page_info['Key'] != 'add_listing') {
            $individual_type_key = substr($page_info['Key'], 3);

            if (in_array($individual_type_key, $allowed_type_keys)) {
                $allowed_type_keys = array($individual_type_key);
            } else {
                $sError = true;
            }
        }

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpAddListingInitBeforeTypes', $this, $allowed_type_keys, $page_info, $account_info, $errors);

        // Adapt listing types array
        $allowed_types = $GLOBALS['rlListingTypes']->adaptTypes($allowed_type_keys);
        $rlSmarty->assign_by_ref('allowed_types', $allowed_types);

        // Register object in Smarty
        $rlSmarty->assign_by_ref('manageListing', $this);

        // Restore process from incomplete listing
        if ($account_info) {
            $this->restoreFromIncompete($_GET['incomplete'], $account_info);
        }

        // "Add Listing from category" mode
        $this->setFromCategoryMode($this->singleStep && $this->step == 'plan');

        // Define single category mode and skip the "Category" step
        $this->defineSingleCategory($allowed_types);

        // Define selected category
        if ((!$this->singleStep && !isset($_GET['edit'])) || $this->fromCategory) {
            if (strpos($_GET['nvar_1'], $this->userCategoryPathPrefix) === 0 || isset($_GET[$this->userCategoryPathPrefix])) {
                $this->category = Category::getUserCategory($_GET['id'], $_GET['nvar_1'], $this->userCategoryPathPrefix);
            } else {
                /* Prevent problem with wrong detection of category in EDIT mode
                   Here listing type have data from previous category selection */
                if ($this->getPrevStep()['edit'] === true && $GLOBALS['listing_type']) {
                    unset($GLOBALS['listing_type']);
                }

                $this->category = Category::getCategory($_GET['id'], preg_replace('/\/([^\/]+)$/', '', $_GET['rlVareables']));
            }
        }

        // Define related listing type
        if ($this->category) {
            $this->listingType = $GLOBALS['rlListingTypes']->types[$this->category['Type']];
            $rlSmarty->assign_by_ref('listing_type', $this->listingType);

            if ($this->listingType && !$GLOBALS['listing_type']) {
                $this->legacySupport();
            }
        }

        // Single step mode
        $this->stepSingle();

        // Check account abilities
        $this->checkAccountAbilities($account_info, $errors);

        // Remove plan step if only one free plan allowed
        $this->singlePlanHandler($account_info);

        /**
         * @since 4.6.0 - all parameters
         */
        $GLOBALS['rlHook']->load('addListingSteps', $this, $page_info, $account_info, $errors);

        // Check if the listing belong to current user
        if ($this->listingID
            && (
                !$this->singleStep
                || ($this->singleStep && defined('IS_LOGIN'))
            )
        ) {
            $this->listingData = $rlDb->fetch('*', array('ID' => $this->listingID), null, 1, 'listings', 'row');

            // show login page if requested listing owner isn't logged in
            if ($this->listingData['Account_ID'] != $account_info['ID']) {
                $page_info['Controller'] = 'login';

                // print first message if there aren't ligin attempts
                if (empty($_SESSION['notice'])) {
                    $rlSmarty->assign('pAlert', $lang['edit_listing_wrong_owner']);
                }
            }
        }

        // Remove unnecessary steps depending of plan
        if ($plan_info = $this->plans[$this->planID]) {
            if (
                ((!$plan_info['Image'] && !$plan_info['Image_unlim']) || !$this->listingType['Photo'])
                && ((!$plan_info['Video'] && !$plan_info['Video_unlim']) || !$this->listingType['Video'])
            ) {
                unset($this->steps['photo']);
            }
        }

        // Unset checkout step
        if ($this->skipCheckout) {
            unset($this->steps['checkout']);
        }

        // Unset plans step
        if ($this->singlePlan) {
            unset($this->steps['plan']);
        }

        // Remove instance
        if ($this->forceInstanceRemoval) {
            parent::removeInstance();
            $this->redirectToStep('category');
            exit;
        }

        // Single step mode
        if ($this->singleStep) {
            if (
                ($_POST['from_post'] && $_POST['step'] == 'form')
                || isset($_GET['edit'])
            ) {
                $_POST['step'] = 'form'; // Simulate form step to allow controler validate the form
                $this->stepForm();
            }
        }
        // Multiple steps mode
        else {
            // disable h1
            $rlSmarty->assign('no_h1', true);

            // add key to the step
            foreach ($this->steps as $key => &$step) {
                $step['key'] = $key;
            }

            // Return user to the necessary step if some required data arn't available
            if ($this->step != 'category' && !$this->category) {
                $this->redirectToStep('category');
                exit;
            } elseif (!in_array($this->step, array('category', 'plan')) && !$this->planID) {
                $this->redirectToStep('plan', $this->extendUrl());
                exit;
            } elseif (!in_array($this->step, array('category', 'plan', 'form')) && !$this->listingID) {
                $this->redirectToStep('form', $this->extendUrl());
                exit;
            }
        }
    }

    /**
     * Set the "step_single.tpl" template instead of the original first
     */
    public function processTplStep()
    {
        if ($this->singleStep) {
            reset($this->steps);
            $first_key = key($this->steps);

            if ($this->step == $first_key) {
                // Save current step pointer
                $step = $this->step;

                // Simulate single
                $this->step = 'single';
                parent::processTplStep();

                // Reset step pointer
                $this->step = $step;

                return;
            }
        }

        parent::processTplStep();
    }

    /**
     * Extend step URL with selected category part
     *
     * @param  boolean $backward - backword direction
     * @return boolean           - success status
     */
    protected function extendUrl($backward = false)
    {
        if ($this->singleStep) {
            return false;
        }

        // prevent first step in backwards
        if ($backward) {
            $prev_step = $this->getPrevStep();
            if (!$prev_step['path']) {
                return false;
            }
        }

        // build exnension
        if ($GLOBALS['config']['mod_rewrite']) {
            $extend = array(
                'type' => 'path',
                'data' => $this->category['Path'],
            );
        } else {
            $extend = array(
                'key'   => 'id',
                'value' => $this->category['ID'],
            );
        }

        return $extend;
    }

    /**
     * Parent method extended by extendUrl()
     */
    public function redirectToNextStep($extend = null)
    {
        parent::redirectToNextStep($this->extendUrl());
    }

    /**
     * Parent method improved by "edit" postfix and extendUrl()
     */
    public function buildPrevStepURL($aParams, $extend = null)
    {
        // add edit if the prev step is first
        $prev_step = $this->getPrevStep();
        if ($prev_step['edit']) {
            $edit = $GLOBALS['config']['mod_rewrite'] ? '?' : '&';
            $edit .= 'edit';
        }

        return parent::buildPrevStepURL($aParams, $this->extendUrl(true)) . $edit;
    }

    /**
     * Parent method extended by extendUrl()
     */
    public function buildNextStepURL($extend = null)
    {
        return parent::buildNextStepURL($this->extendUrl());
    }

    /**
     * Parent method extended by extendUrl()
     */
    public function buildFormAction($aParams)
    {
        $aParams['extend'] = $this->extendUrl();

        return parent::buildFormAction($aParams);
    }

    /**
     * Restore incomplete listing process
     *
     * @param integer $listing_id   - listing ID to restore from
     * @param array   $account_info - logged in account data
     */
    private function restoreFromIncompete($listing_id, $account_info)
    {
        $listing_id = (int) $listing_id;

        if (!$listing_id) {
            return;
        }

        if (!$account_info) {
            trigger_error('Unable to restore listing from incomplete, no $account_info data provided', E_USER_ERROR);
        }

        $listing_data = $GLOBALS['rlDb']->fetch(
            '*',
            array('ID' => $listing_id, 'Account_ID' => $account_info['ID']),
            null, 1, 'listings', 'row'
        );

        if (!$listing_data) {
            return;
        }

        if (!array_key_exists($listing_data['Last_step'], $this->steps)) {
            $this->step = 'form';
        }

        $this->step = $this->singleStep ? 'category' : $listing_data['Last_step'];

        $this->listingID = $listing_id;
        $this->listingData = $listing_data;
        $this->category = Category::getCategory($listing_data['Category_ID']);
        $this->listingType = $GLOBALS['rlListingTypes']->types[$this->category['Type']];

        $this->planID = $listing_data['Plan_ID'];
        $this->planType = $listing_data['Plan_type'];
        $this->adType = $listing_data['Last_type'];

        $_POST['plan'] = $listing_data['Plan_ID'];
        $_POST['ad_type'] = $listing_data['Last_type'];
        $_POST['crossed_categories'] = $listing_data['Crossed'];

        $this->savePost(array('plan', 'ad_type', 'crossed_categories'));

        $this->existingMembershipHandler($account_info);

        $this->stepPlan();
        $this->stepForm();
        $this->stepPhoto();

        $_GET['edit'] = true;

        if ($this->singleStep) {
            // Assign parent category keys to SMARTY
            $this->prepareParentCategoryKeys();
        } else {
            $this->redirectToStep($this->step, $this->extendUrl());
        }
    }

    /**
     * User authorization on the form step
     *
     * @param array &$account_info - user account information
     * @param array &$errors       - global errors
     * @param array &$error_fields - global error fields
     */
    private function auth(&$account_info, &$errors, &$error_fields)
    {
        global $config, $lang, $rlAccount, $rlSmarty, $rlDb, $rlHook, $rlActions, $account_info, $rlAccountTypes;

        if ($config['add_listing_without_reg'] && !defined('IS_LOGIN')) {
            $quick_auth = false;

            $login_data    = $_POST['login'];
            $register_data = $_POST['register'];
            $accept_fields = $_POST['profile']['accept'];

            if ($register_data['email'] && !isset($register_data['name'])) {
                $exp_email = explode('@', $register_data['email']);
                $register_data['name'] = $rlAccount->makeUsernameUnique($exp_email[0]);
            }

            // Login
            if ($login_data['username'] && $login_data['password']) {
                $quick_auth = true;

                if (true === $response = $rlAccount->login($login_data['username'], $login_data['password'])) {
                    $account_info = $_SESSION['account'];

                    $rlSmarty->assign('isLogin', $account_info['Full_name']);
                    define('IS_LOGIN', true);

                    // Get actual plan info
                    $plan_info = $this->getPlanInfo($account_info['ID']);

                    // Reset new membership flag because existing account logged in
                    $this->newMembership = $account_info['Payment_status'] == 'paid' ? false : true;

                    // Existing membership plan mode
                    $this->existingMembershipHandler($account_info);

                    // Existing/free package mode
                    $this->existingPackageHandler($plan_info);

                    // Assign blank listing to logged in user
                    $this->reAssignBlankListing($account_info);

                    // Re-fetch plans
                    $this->fetchPlans($account_info);
                } else {
                    $errors = array_merge($errors, $response);
                    $error_fields .= 'login[username],login[password],';
                }
            }
            // Register
            elseif ($register_data['name'] && $register_data['email']) {
                $quick_auth = true;

                // Validate email
                if (!Valid::isEmail($register_data['email'])) {
                    $errors[] = $lang['notice_bad_email'];
                    $error_fields .= 'register[email],';
                }
                // Check for duplicate email
                elseif ($rlDb->getOne('ID', "`Mail` = '{$register_data['email']}' AND `Status` <> 'trash'", 'accounts')) {
                    $errors[] = str_replace('{email}', $register_data['email'], $lang['notice_account_email_exist']);
                    $error_fields .= 'register[email],';
                }

                // get first proper account type
                if (!$register_data['type'] && $this->listingType['Key']) {
                    foreach ($rlAccountTypes->types as $at_key => $atype) {
                        if ($at_key != 'visitor'
                            && $atype['Quick_registration'] = '1'
                            && in_array($this->listingType['Key'], explode(',', $atype['Abilities']))
                        ) {
                            $register_data['type'] = $atype['ID'];
                            break;
                        }
                    }
                }

                // check accepted agreement fields
                if ($register_data['type']) {
                    // get key of selected account type
                    $selected_atype = '';
                    foreach ($rlAccountTypes->types as $at_key => $atype) {
                        if ($atype['ID'] == $register_data['type']) {
                            $selected_atype = $atype['Key'];
                            break;
                        }
                    }

                    if ($selected_atype) {
                        foreach ($rlAccount->getAgreementFields($selected_atype, true) as $ag_field_key => $ag_field) {
                            if (!isset($accept_fields[$ag_field_key])) {
                                $errors[] = str_replace(
                                    '{field}',
                                    $lang['pages+name+' . $ag_field['Default']],
                                    $lang['notice_field_not_accepted']
                                );
                                $error_fields .= "register[type]";
                            }
                        }
                    }
                }

                $rlHook->load('phpAddListingQuickRegistrationValidate', $register_data, $errors, $error_fields);

                if (!$errors) {
                    // Get current plan info
                    $plan_info = $this->plans[$this->planID];

                    // Create new account
                    $membership_plan_id = $this->planType == 'account' ? $this->planID : false;

                    if ($new_account = $rlAccount->quickRegistration(
                        $register_data['name'],
                        $register_data['email'],
                        $membership_plan_id,
                        $register_data['type'],
                        $this->listingType['Key'])
                    ) {
                        if ($this->planType == 'account') {
                            $plan_using = array(
                                'Account_ID'       => $new_account[2],
                                'Plan_ID'          => $membership_plan_id,
                                'Listings_remains' => $plan_info['Listing_number'],
                                'Standard_remains' => $plan_info['Standard_listings'],
                                'Featured_remains' => $plan_info['Featured_listings'],
                                'Type'             => 'account',
                                'Date'             => 'NOW()',
                                'IP'               => Util::getClientIP(),
                            );

                            if ($plan_info['Limit'] > 0) {
                                $plan_using['Count_used'] = 1;
                            }

                            $rlActions->insertOne($plan_using, 'listing_packages');
                        }

                        $rlAccount->login($new_account[0], $new_account[1]);

                        $account_info = $_SESSION['account'];
                        $rlSmarty->assign('isLogin', $account_info['Full_name']);
                        define('IS_LOGIN', true);

                        $rlHook->load('phpAddListingAfterQuickRegistration', $new_account, $register_data); // >= v4.5

                        // Assign blank listing to logged in user
                        $this->reAssignBlankListing($account_info);

                        // Re-fetch plans
                        $this->fetchPlans($account_info);

                        // Send login details to user
                        $GLOBALS['reefless']->loadClass('Mail');

                        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('quick_account_created');
                        $find = array('{login}', '{password}', '{name}');
                        $replace = array($new_account[0], $new_account[1], $account_info['Full_name']);

                        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                        $GLOBALS['rlMail']->send($mail_tpl, $register_data['email']);
                    }
                }
            }

            if ($quick_auth) {
                $rlSmarty->assign('account_info', $account_info);
            } else {
                $errors[] = $lang['quick_signup_fail'];
            }
        }
    }

    /**
     * Re-assign blank listing owner from anonym (-1) to current account ID
     *
     * @param array &$account_info - user account information
     */
    private function reAssignBlankListing(&$account_info)
    {
        if ($this->singleStep
            && $_COOKIE['incomplete_listing_hash']
            && $this->listingID
        ) {
            // Re-assign user
            $update = array(
                'fields' => array(
                    'Account_ID'  => $account_info['ID'],
                    'Loc_address' => '',
                ),
                'where'  => array(
                    'Loc_address' => $_COOKIE['incomplete_listing_hash'],
                ),
            );

            $GLOBALS['rlDb']->update($update, 'listings');

            // Remove saved cookie
            $GLOBALS['reefless']->eraseCookie('incomplete_listing_hash');
        }
    }

    /**
     * Single step handler
     */
    public function stepSingle()
    {
        global $account_info, $errors;

        if (!$this->singleStep) {
            return;
        }

        // Reset the step to initial step
        if ($this->step != 'category' && !$this->listingID) {
            $this->step = 'category';
        }

        if ($this->fromCategory) {
            // Simulate visitor account
            $this->simulateVisitorAccount($account_info);

            // Create blank listing to allow media to be assigned to it
            $this->createBlankListing($account_info);

            // Check account abilities
            $this->checkAccountAbilities($account_info, $errors);
        }

        if ($_POST['from_post'] || isset($_GET['edit'])) {
            // Assign parent category keys to SMARTY
            $this->prepareParentCategoryKeys();

            // Run default controllers for steps
            $this->stepCategory();

            // Save current step pointer in POST
            $post_step = $_POST['step'];

            if (!$this->singlePlan) {
                $_POST['step'] = 'plan'; // Simulate plan step to allow controler validate the plan
                $this->stepPlan();
            }

            if ($this->steps['photo']) {
                $_POST['step'] = 'photo'; // Simulate photo step to allow controler validate the media
                $this->stepPhoto();
            }

            // Reset step pointer in POST
            $_POST['step'] = $post_step;
        }

        // Location finder fallback: TODO remove
        if ($GLOBALS['aHooks']['locationFinder']) {
            $GLOBALS['listing_type']['Location_finder'] = true;
        }
    }

    /**
     * "Select Category" step handler
     */
    public function stepCategory()
    {
        global $rlSmarty, $rlCategories, $page_info, $account_info, $sError, $config;

        parent::step();

        $GLOBALS['rlHook']->load('addListingGetCats');

        // Existing membership plan mode
        $this->existingMembershipHandler($account_info);

        // Remove unnecessary steps
        if (!$this->singleStep) {
            unset($this->steps['photo'], $this->steps['checkout']);
        }
    }

    /**
     * Plan step handler
     */
    public function stepPlan()
    {
        global $account_info, $rlSmarty, $errors, $error_fields, $lang, $rlDb;

        // Redirect to the next step in single plan mode
        if (!$this->singleStep && $this->singlePlan) {
            $this->redirectToStep('form', $this->extendUrl());
        }

        parent::step();

        // Remove unnecessary steps
        if (!$this->planID) {
            unset($this->steps['photo'], $this->steps['checkout']);
        }

        // Return data to post
        $this->simulatePost(array('ad_type', 'subscription', 'plan'));

        // Prepare plans
        $this->fetchPlans($account_info);

        // No plans error
        if (empty($this->plans)) {
            $GLOBALS['rlDebug']->logger("There are not plans related to '{$this->category['name']}' category.");

            $error = $lang[($this->planType == 'account'
                ? 'notice_no_membership_plans_related'
                : 'notice_no_plans_related')];

            $GLOBALS['reefless']->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($error, 'error');

            $this->redirectToPrevStep();
        }

        // No related form data error
        if (!Category::buildForm(
            $this->category,
            $this->listingType
        )) {
            $errors[] = $lang['notice_no_fields_related'];
            $rlSmarty->assign('no_access', true);

            $GLOBALS['rlDebug']->logger("There are not fields related to '{$this->category['name']}' category.");
        }

        // On form submit
        if ($_POST['step'] == 'plan'
            && !$this->fromCategory
            && !$rlSmarty->_tpl_vars['no_access']
        ) {
            // Save plan from post
            $this->savePost(array('plan', 'ad_type'));

            // Save plan ID in the instance
            $this->planID = (int) $this->postData['plan'];

            $plan_info = $this->plans[$this->planID];

            // Save featured status
            if ($this->planType == 'listing') {
                if (
                    ($plan_info['Featured'] && !$plan_info['Advanced_mode'])
                    || (
                        $plan_info['Featured']
                        && $plan_info['Advanced_mode']
                        && $this->postData['ad_type'] == 'featured'
                    )
                ) {
                    $this->adType = 'featured';
                } else {
                    $this->adType = 'standard';
                }
            }

            // Validate plan id
            if (!$this->planID) {
                $phrase_key = $this->Plan_type == 'account'
                ? 'notice_membership_plan_does_not_chose'
                : 'notice_listing_plan_does_not_chose';
                $errors[] = $lang[$phrase_key];
                $error_fields .= 'plan,';
            }
            // Validate plan data
            elseif (!$plan_info) {
                $errors[] = $lang['notice_listing_plan_unavailable'];
            }
            // Check plan using data
            elseif ($plan_info['Limit'] > 0 && $plan_info['Using'] == 0 && $plan_info['Using'] != '') {
                $errors[] = $lang['plan_limit_using_hack'];
            }

            // Check exceeded package usage
            $this->existingPackageValidator($plan_info, $errors);

            // Check exceeded limited package usage
            $this->limitedPackageValidator($plan_info, $errors);

            // Manage subscription option
            if ($_POST['subscription'] == $this->planID) {
                $this->savePost('subscription');
            }
            if (isset($this->postData['subscription']) && !$_POST['subscription']) {
                $this->removePost('subscription');
            }

            if (!$errors) {
                // Save new MS status
                if ($this->planType == 'account') {
                    $this->newMembership = true;
                }

                // Existing/free package mode
                $this->existingPackageHandler($plan_info);

                // Membership plan handler
                $this->membershipHandler($plan_info);

                // Redirect to the next step
                $this->redirectToNextStep();
            }
        }
    }

    /**
     * Form step handler
     */
    public function stepForm()
    {
        parent::step();

        global $reefless, $rlListings, $rlAccount, $rlCommon, $errors, $error_fields,
        $rlSmarty, $lang, $account_info, $config, $rlDb, $rlHook;

        // Get and assign plan info to SMARTY
        $plan_info = $this->plans[$this->planID];
        $rlSmarty->assign('plan_info', $plan_info); // Re-assign possible in existingMembershipHandler() method

        // Get current membership plans usage data
        if ($account_info['Plan_ID']) {
            $plan_using = $GLOBALS['rlMembershipPlan']->getUsingPlan($account_info['Plan_ID'], $account_info['ID']);
            $rlSmarty->assign('plan_using', $plan_using); // Re-assign possible in existingMembershipHandler() method
        }

        // Prepare quick types for the auth form
        $this->prepareQuickTypes();

        // Build form
        $form = Category::buildForm(
            $this->category,
            $this->listingType,
            $this->formFields
        );
        $rlSmarty->assign_by_ref('form', $form);

        // assign account address on map fields to smarty
        $rlAccount->accountAddressAssign();

        // simulate listing data in post if listing already saved
        $this->simulateListingInPost();

        // get a list with agreement fields
        $rlSmarty->assign('agreement_fields', $GLOBALS['rlAccount']->getAgreementFields());

        if ($_POST['step'] == 'form'
            && !$this->fromCategory
            && !$rlSmarty->_tpl_vars['no_access']
        ) {
            $data = &$_POST['f'];

            // Authorization handler
            $this->auth($account_info, $errors, $error_fields);

            // Update plan info afte auth()
            $plan_info = $this->plans[$this->planID];

            // Existing package validator
            $this->existingPackageValidator($plan_info, $errors);

            // Check exceeded limited package usage
            $this->limitedPackageValidator($plan_info, $errors);

            // Check account abilities
            $this->checkAccountAbilities($account_info, $errors);

            // ad type validation in membership mode
            if ($this->planType == 'account') {
                if ($plan_info['Advanced_mode']) {
                    $this->savePost('ad_type');

                    if (empty($this->postData['ad_type'])) {
                        $errors[] = str_replace('{field}', $lang['listing_type'], $lang['notice_select_empty']);
                    } else {
                        $prefix = ucfirst($this->postData['ad_type']);
                        if ($plan_info[$prefix . '_remains'] > 0
                            || $plan_info[$prefix . '_listings'] == 0
                            || (!isset($plan_info[$prefix . '_remains']))
                        ) {
                            $this->adType = $this->postData['ad_type'];
                        } else {
                            unset($this->postData['ad_type']);
                            $errors[] = $lang['listing_type_not_available'];
                        }
                    }
                } else {
                    if ($plan_info['Featured_listing']) {
                        $this->adType = 'featured';
                    }
                }
            }

            // check security image code
            if ($config['security_img_add_listing']
                && ($_POST['security_code'] != $_SESSION['ses_security_code'] || !$_POST['security_code'])) {
                $errors[] = $lang['security_code_incorrect'];
            }

            // Save crossed categories
            $this->savePost('crossed_categories');

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
            $rlHook->load('addListingFormDataChecking', $this, $data, $errors, $error_fields);

            if (!$errors) {
                // Reset blank listing hash and re-assign account
                $this->reAssignBlankListing($account_info);

                $reefless->loadClass('Listings');

                $info = array();

                /**
                 * @since 4.6.0 - All parameters
                 */
                $rlHook->load('addListingAdditionalInfo', $this, $info, $data, $plan_info);

                $info['Plan_ID']   = $this->planID;
                $info['Plan_type'] = $this->planType;
                $info['Last_type'] = $this->adType;
                $info['Last_step'] = 'form';
                $info['Date'] = 'NOW()';
                $info['Category_ID'] = $this->category['ID'];
                $info['Crossed'] = $this->postData['crossed_categories'];

                // copy account address to listing according to mapping in admin panel
                $rlAccount->accountAddressAdd($data);

                // edit tmp listing mode
                if ($this->listingID) {
                    $rlListings->edit($this->listingID, $info, $data, $this->formFields, $plan_info);

                    /**
                     * @since 4.7.2 - Hook renamed from "afterListingEdit" to "afterListingUpdate"
                     *              - To prevent use 1 hook in 2 classes (AddListing & EditListing)
                     * @since 4.6.0 - All parameters
                     */
                    $rlHook->load('afterListingUpdate', $this, $info, $data, $plan_info);
                }
                // add tmp listing mode
                else {
                    // prepare system listing data
                    $info['Account_ID'] = $account_info['ID'];
                    $info['Status'] = 'incomplete';

                    if ($this->planType == 'account') {
                        $info['Pay_date'] = strtotime($account_info['Pay_date']) > 0
                        ? $account_info['Pay_date']
                        : '';
                    }

                    // create listing
                    if ($rlListings->create($info, $data, $this->formFields, $plan_info)) {
                        $this->listingID = $rlListings->id;

                        /**
                         * @since 4.6.0 - All parameters
                         */
                        $rlHook->load('afterListingCreate', $this, $info, $data, $plan_info);
                    }
                }

                // Update user category details
                if ($this->category['user_category_id']) {
                    $update = array(
                        'fields' => array(
                            'Account_ID' => $account_info['ID'],
                            'Listing_ID' => $this->listingID
                        ),
                        'where' => array('ID' => $this->category['user_category_id'])
                    );
                    $rlDb->update($update, 'tmp_categories');
                }

                // Redirect to the next step
                if ($this->singleStep) {
                    if (isset($_GET['edit']) || $this->fromCategory) {
                        return;
                    }

                    $step_before_redirect = $this->step;
                    $this->step = 'form'; // Simulate the form step to process proper redirection
                }

                $this->redirectToNextStep();

                // Restore initial step
                if ($this->singleStep && $step_before_redirect) {
                    $this->step = $step_before_redirect;
                }
            }
        }

        // Prepare crossed categories data
        $this->prepareCrossedCategories($plan_info, $account_info);
    }

    /**
     * Media step handler
     */
    public function stepPhoto()
    {
        parent::step();

        global $rlSmarty, $lang, $errors;

        $this->saveStepPointer();

        $plan_info = $this->plans[$this->planID];
        $GLOBALS['rlSmarty']->assign_by_ref('plan_info', $plan_info);

        $is_picture = true;
        $is_video = true;

        if (!$this->listingType['Photo'] || (!$plan_info['Image'] && !$plan_info['Image_unlim'])) {
            $is_picture = false;
        }

        if (!$this->listingType['Video'] || (!$plan_info['Video'] && !$plan_info['Video_unlim'])) {
            $is_video = false;
        }

        if (!$is_picture && !$is_video && !$this->singleStep) {
            $this->redirectToStep('form', $this->extendUrl());
            exit;
        }

        $rlSmarty->assign_by_ref('is_picture', $is_picture);
        $rlSmarty->assign_by_ref('is_video', $is_video);

        /**
         * @since 4.6.0 - All parameters
         */
        $GLOBALS['rlHook']->load('addListingStepMedia', $this, $is_picture, $is_video);

        if ($_POST['step'] == 'photo' && !$this->fromCategory) {
            // Check for exeeded pictures limit
            if (!$plan_info['Image_unlim']) {
                $sql = "
                    SELECT COUNT(*) AS `Count`
                    FROM `{db_prefix}listing_photos`
                    WHERE `Listing_ID` = {$this->listingID} AND `Type` = 'picture'
                ";
                $pictures = $GLOBALS['rlDb']->getRow($sql, 'Count');

                if ($pictures > $plan_info['Image']) {
                    $errors[] = str_replace(
                        array('{plan}', '{count}'),
                        array($plan_info['name'], $plan_info['Image']),
                        $lang['no_more_photos']
                    );
                }
            }

            // Check for exeeded videos limit
            if (!$plan_info['Video_unlim']) {
                $sql = "
                    SELECT COUNT(*) AS `Count`
                    FROM `{db_prefix}listing_photos`
                    WHERE `Listing_ID` = {$this->listingID} AND `Type` = 'video'
                ";
                $videos = $GLOBALS['rlDb']->getRow($sql, 'Count');

                if ($videos > $plan_info['Video']) {
                    $errors[] = str_replace(
                        array('{plan}', '{count}'),
                        array($plan_info['name'], $plan_info['Video']),
                        $lang['no_more_videos']
                    );
                }
            }

            // Check for required photos
            if ($this->listingType['Photo_required'] && $is_picture) {
                // Get pictures count if it's still unavailable
                if ($plan_info['Image_unlim']) {
                    $sql = "
                        SELECT COUNT(*) AS `Count`
                        FROM `{db_prefix}listing_photos`
                        WHERE `Listing_ID` = {$this->listingID} AND `Type` = 'picture'
                    ";
                    $pictures = $GLOBALS['rlDb']->getRow($sql, 'Count');
                }

                if (!$pictures) {
                    $errors[] = $lang['no_photo_uploaded'];
                }
            }

            if (!$errors) {
                // Redirect to the next step
                $this->redirectToNextStep();
            }
        }
    }

    /**
     * Update listing stat data on the "Done" step
     *
     * @param array $plan_info - current listing plan details
     */
    public function updateListing($plan_info)
    {
        $is_free = false;

        // Define is listing is free
        if (
            $plan_info['Price'] <= 0
            || ($plan_info['Price'] > 0
                && (
                    (
                        $this->planType == 'listing'
                        && $plan_info['Package_ID']
                        && $plan_info['Listings_remains'] > 0
                    )
                    || (
                        $this->planType == 'account'
                        && ($plan_info['Listings_remains'] > 0 || $plan_info['Listings_number'] == 0)
                    )
                )
            )
        ) {
            $is_free = true;

            // Redirect to the form if done step was initiated not by script
            if (!$this->listingData['Plan_ID']) {
                $this->redirectToStep('category');
                exit;
            }
        }
        // Checking for paid listing payment status
        elseif (strtotime($this->listingData['Pay_date']) === false) {
            if ($this->singleStep) {
                $this->redirectToStep('category');
            } else {
                $this->redirectToStep($this->listingData['Last_step'], $this->extendUrl());
            }
            exit;
        }

        // Change listing status
        $update = array(
            'fields' => array(
                'Last_step'     => '',
                'Last_type'     => '',
                'Cron'          => '0',
                'Cron_notified' => '0',
                'Cron_featured' => '0',
            ),
            'where'  => array(
                'ID' => $this->listingID,
            ),
        );

        if ($is_free) {
            // Define featured status
            if (($plan_info['Featured'] || $plan_info['Featured_listing'])
                && (!$plan_info['Advanced_mode']
                    || ($plan_info['Advanced_mode'] && $this->adType == 'featured')
                )
            ) {
                $featured = true;
            }

            $update['fields']['Status'] = $GLOBALS['config']['listing_auto_approval'] ? 'active' : 'pending';
            $update['fields']['Pay_date'] = 'NOW()';
            $update['fields']['Featured_ID'] = $featured ? $plan_info['ID'] : 0;
            $update['fields']['Featured_date'] = $featured ? 'NOW()' : 'NULL';
        }

        $GLOBALS['rlDb']->update($update, 'listings');

        /**
         * @since 4.7.2 - Hook moved after code "rlDb->update()"
         * @since 4.6.0 - All parameters
         */
        $GLOBALS['rlHook']->load('afterListingDone', $this, $update, $is_free);
    }

    /**
     * Update account if the free membership plan was selected
     *
     * @param array $plan_info - current listing plan details
     */
    public function updateAccount(&$plan_info)
    {
        global $account_info;

        // Return if it's not a membership plab mode
        if ($this->planType != 'account') {
            return;
        }

        // Return if plan isn't free or current account already has a plan assigned
        if ($plan_info['Price'] > 0 || $account_info['Plan_ID']) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Account');
        $GLOBALS['rlAccount']->upgrade($account_info['ID'], $plan_info['ID'], false, false);
        $account_info['Plan_ID'] = $plan_info['ID'];
    }

    /**
     * Membership plan handler
     *
     * @param array $plan_info - plan data
     */
    public function membershipHandler(&$plan_info)
    {
        global $account_info;

        if ($this->planType == 'listing') {
            return;
        }

        // Check for free plan
        if ($plan_info['Price'] <= 0) {
            $this->skipCheckout = true;
            unset($this->steps['checkout']);
        } else {
            $this->skipCheckout = false;
        }

        // Update plan info
        if ($account_info['plan']
            && $account_info['plan']['ID'] != $plan_info['ID']) {
            $plan = &$_SESSION['account']['plan'];
            $plan = array_merge($account_info['plan'], $plan_info);

            // Update remains
            if ($plan['Advanced_mode']) {
                $plan['Standard_remains'] = $plan['Standard_listings'];
                $plan['Featured_remains'] = $plan['Featured_listings'];
            } else {
                $plan['Standard_remains'] = $plan['Featured_remains'] = 0;
            }

            // Update account plan
            $_SESSION['account']['Plan_ID'] = $plan_info['ID'];
        }
    }

    /**
     * Update package status usage
     *
     * It handles existing pachage usage, insert newly free package and
     * Limited listing plans.
     *
     * @param  array $plan_info - plan data
     * @return string           - paid status
     */
    public function updateListingPackageUsage(&$plan_info)
    {
        global $rlDb, $account_info, $reefless, $lang;

        $paid_status = false;

        // Existing package mode
        if ($plan_info['Type'] == 'package' && $plan_info['Package_ID']) {
            if ($plan_info['Listings_remains'] > 0) {
                $update = array(
                    'fields' => array(
                        'Listings_remains' => $plan_info['Listings_remains'] - 1,
                    ),
                    'where'  => array(
                        'ID' => $plan_info['Package_ID'],
                    ),
                );

                $prefix = ucfirst($this->adType);
                if ($plan_info[$prefix . '_listings'] > 0) {
                    $update['fields'][$prefix . '_remains'] = $plan_info[$prefix . '_remains'] - 1;
                }

                $rlDb->update($update, 'listing_packages');
            }

            // Set paid status
            $paid_status = $lang['purchased_packages'];
        }
        // Newly free package mode
        elseif ($plan_info['Type'] == 'package' && !$plan_info['Package_ID'] && $plan_info['Price'] <= 0) {
            // Remove existing used-up package
            $rlDb->delete(
                array(
                    'Account_ID' => $account_info['ID'],
                    'Plan_ID' => $plan_info['ID'],
                    'Listings_remains' => '0',
                    'Standard_remains' => '0',
                    'Featured_remains' => '0',
                    'Type' => 'package',
                ),
                'listing_packages'
            );

            $insert = array(
                'Account_ID'       => $account_info['ID'],
                'Plan_ID'          => $plan_info['ID'],
                'Listings_remains' => $plan_info['Listing_number'] == 0 ? 0 : $plan_info['Listing_number'] - 1,
                'Standard_remains' => $plan_info['Standard_listings'],
                'Featured_remains' => $plan_info['Featured_listings'],
                'Type'             => 'package',
                'Date'             => 'NOW()',
                'IP'               => $reefless->getClientIpAddress(),
            );

            if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $this->adType == 'standard') {
                $insert['Standard_remains']--;
            }

            if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $this->adType == 'featured') {
                $insert['Featured_remains']--;
            }

            $rlDb->insert($insert, 'listing_packages');

            // Set paid status
            $paid_status = $lang['package_plan'] . '(' . $lang['free'] . ')';
        }
        // Limited listing mode
        elseif ($plan_info['Type'] == 'listing' && $plan_info['Limit'] > 0) {
            $usage_key = $plan_info['Using'] ? 'Using' : 'Limit';
            $plan_usage_insert = array(
                'Account_ID'       => $account_info['ID'],
                'Plan_ID'          => $plan_info['ID'],
                'Listings_remains' => $plan_info[$usage_key] - 1,
                'Type'             => 'limited',
                'Date'             => 'NOW()',
                'IP'               => $reefless->getClientIpAddress(),
            );

            $rlDb->insert($plan_usage_insert, 'listing_packages');
        }

        return $paid_status;
    }

    /**
     * Send notification e-mail to the listing owner
     */
    public function notifyOwner()
    {
        global $config, $reefless, $account_info;

        $reefless->loadClass('Mail');

        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate(
            $config['listing_auto_approval']
            ? 'free_active_listing_created'
            : 'free_approval_listing_created'
        );

        if ($config['listing_auto_approval']) {
            $link = $reefless->getListingUrl(intval($this->listingID));
        } else {
            $myPageKey = $config['one_my_listings_page'] ? 'my_all_ads' : 'my_' . $this->listingType['Key'];
            $link      = $reefless->getPageUrl($myPageKey);
        }

        $mail_tpl['body'] = str_replace(
            array('{username}', '{link}'),
            array(
                $account_info['Username'],
                '<a href="' . $link . '">' . $link . '</a>',
            ),
            $mail_tpl['body']
        );
        $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
    }

    /**
     * Send notification email to Administrator
     * @param string $paid_status - current listing payment status
     */
    public function notifyAdmin($paid_status = null)
    {
        global $config, $reefless, $account_info, $lang;

        $reefless->loadClass('Mail');

        $listing_title = $GLOBALS['rlListings']->getListingTitle(
            $this->category['ID'],
            $this->listingData,
            $this->listingType['Key']
        );
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('admin_listing_added');

        $find = array('{username}', '{link}', '{date}', '{status}', '{paid}');
        $replace = array(
            $account_info['Username'],
            '<a href="' . RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=view&id=' . $this->listingID . '">' . $listing_title . '</a>',
            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            $lang[$config['listing_auto_approval'] ? 'active' : 'pending'],
            $paid_status,
        );
        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

        if ($config['listing_auto_approval']) {
            $mail_tpl['body'] = preg_replace('/\{if activation is enabled\}(.*)\{\/if\}/', '', $mail_tpl['body']);
        } else {
            $hash = md5($GLOBALS['rlDb']->getOne('Date', "`ID` = '{$this->listingID}'", 'listings'));
            $activation_link = RL_URL_HOME . ADMIN . '/index.php?controller=listings&action=remote_activation&id=' . $this->listingID . '&hash=' . $hash;
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';
            $mail_tpl['body'] = preg_replace(
                '/(\{if activation is enabled\})(.*)(\{activation_link\})(.*)(\{\/if\})/',
                '$2 ' . $activation_link . ' $4',
                $mail_tpl['body']
            );
        }
        $GLOBALS['rlMail']->send($mail_tpl, $config['notifications_email']);
    }

    /**
     * Recount listings in the related category
     *
     * @param array &$plan_info - plan information
     */
    public function recountListings(&$plan_info)
    {
        global $rlCategories, $account_info;

        if ($GLOBALS['config']['listing_auto_approval']) {
            $rlCategories->listingsIncrease($this->category['ID']);
            $rlCategories->accountListingsIncrease($account_info['ID']);

            // Crossed categories mode
            if ($plan_info['Cross'] > 0 && $this->postData['crossed_categories']) {
                foreach (explode(',', $this->postData['crossed_categories']) as $crossed_category) {
                    $rlCategories->listingsIncrease($crossed_category);
                }
            }
        }
    }

    /**
     * Done step handler
     */
    public function stepDone()
    {
        // Update listing data in the instance
        $this->listingData = $GLOBALS['rlDb']->fetch(
            '*',
            array('ID' => $this->listingID),
            null, 1, 'listings', 'row'
        );

        // Legacy add listing version and plugins compatibility support
        $this->legacySupport();

        // Get actual plan info
        $plan_info = $this->getPlanInfo($GLOBALS['account_info']['ID']);

        // Update listing photo names
        if ($this->singleStep) {
            ListingMedia::updateNames($this->listingID);
        }

        // Update listing
        $this->updateListing($plan_info);

        // Update account
        $this->updateAccount($plan_info);

        // Set default paid status
        $paid_status = $GLOBALS['lang'][$plan_info['Price'] ? 'paid' : 'free'];

        // Update plan usage by type
        switch ($this->planType) {
            case 'listing':
                if ($set_status = $this->updateListingPackageUsage($this->plans[$this->planID])) {
                    $paid_status = $set_status;
                }
                break;

            case 'account':
                $GLOBALS['rlMembershipPlan']->updatePlanUsing($this->adType);
                break;
        }

        // Send message to listing owner
        $this->notifyOwner();

        // Send notification to Administrator
        $this->notifyAdmin($paid_status);

        // Recount listings in related category
        $this->recountListings($plan_info);

        // Force instance removal
        $this->forceInstanceRemoval = true;
    }

    /**
     * Existing listing package handler
     *
     * @param array $plan_info - plan data
     */
    public function existingPackageHandler(&$plan_info)
    {
        if ($this->planType == 'account') {
            return;
        }

        if ((!$plan_info['Package_ID'] && $plan_info['Price'] <= 0)
            || ($plan_info['Package_ID']
                && ($plan_info['Listings_remains'] > 0
                    || $plan_info['Listing_number'] == 0)
            )
        ) {
            $this->skipCheckout = true;
            unset($this->steps['checkout']);
        } else {
            $this->skipCheckout = false;
        }
    }

    /**
     * Existing listing package validator
     *
     * @param array $plan_info - plan data
     * @param array &$errors   - globals errors
     */
    public function existingPackageValidator(&$plan_info, &$errors = array())
    {
        if (!$plan_info['Package_ID']) {
            return;
        }

        global $lang;

        if ($plan_info['Advanced_mode']) {
            $option = ucfirst($this->postData['ad_type']);

            if (!$this->postData['ad_type']) {
                $errors[] = $lang['feature_mode_caption_error'];
            } elseif (
                $plan_info['Package_ID']
                && $plan_info[$option . '_remains'] <= 0
                && $plan_info[$option . '_listings'] > 0
            ) {
                $errors[] = $lang['feature_mode_access_hack'];
            }
        } else {
            if ($plan_info['Listings_remains'] <= 0
                && $plan_info['Listing_number'] > 0
            ) {
                $errors[] = $lang['feature_mode_access_hack'];
            }
        }
    }

    /**
     * Display error, reset user plan selection and disable singlePlan mode
     *
     * @since 4.8.1
     *
     * @param array &$plan_info - Selected plan data
     * @param array &$errors    - System errors array
     */
    public function limitedPackageValidator(&$plan_info, &$errors = array())
    {
        if ($plan_info['Limit'] > 0 && $plan_info['plan_disabled']) {
            $errors[] = $GLOBALS['lang']['plan_limit_using_deny'];

            // Force single plan mode re-check
            $this->singlePlan = false;

            // Reset selected plan ID
            unset($this->planID);
        }
    }

    /**
     * Create blank incomplete listing entry in database
     *
     * @param array &$account_info - user account information
     */
    protected function createBlankListing(&$account_info)
    {
        global $rlDb;

        // Prepare data
        $fields = array(
            'Category_ID' => $this->category['ID'],
            'Account_ID'  => $account_info['ID'] ?: -1,
            'Plan_ID'     => $this->planID,
            'Date'        => 'NOW()',
            'Plan_type'   => $this->planType,
            'Status'      => 'incomplete',
            'Last_step'   => 'form',
        );

        // Get existing incomplete listings by hash
        if ($hash = $_COOKIE['incomplete_listing_hash']) {
            $this->listingID = $rlDb->getOne('ID', "`Loc_address` = '{$hash}'", 'listings');
            $update = array(
                'fields' => $fields,
                'where'  => array('Loc_address' => $hash),
            );
            $rlDb->update($update, 'listings');
        }

        // Listing already created, return
        if ($this->listingID) {
            return;
        }

        $hash = $GLOBALS['reefless']->generateHash();
        $fields['Loc_address'] = $hash;

        // Insert listing
        $rlDb->insert($fields, 'listings');
        $this->listingID = $rlDb->insertID();

        // Save hash in cookie
        $period = 60 * 60 * 24 * 14; // Two weeks
        $GLOBALS['reefless']->createCookie('incomplete_listing_hash', $hash, time() + $period);
    }

    /**
     * Validate account abilities
     *
     * @param array &$account_info - user account information
     * @param array &$errors       - global errors
     */
    private function checkAccountAbilities(&$account_info, &$errors)
    {
        global $lang, $rlSmarty;

        // prepare account abilities
        if (empty($account_info['Abilities'])) {
            $errors[] = $lang['add_listing_deny'];
            $rlSmarty->assign('no_access', true);
        } elseif (is_string($account_info['Abilities'])) {
            $account_info['Abilities'] = explode(',', $account_info['Abilities']);
        }

        // check account type permissions
        if (!$errors
            && ($this->step != 'category' || $this->fromCategory)
            && (!in_array($this->category['Type'], $account_info['Abilities'])
                || $this->listingType['Admin_only']
            )
        ) {
            $errors[] = str_replace(
                '{category_type}',
                $lang['listing_types+name+' . $this->category['Type']],
                $lang['add_listing_type_deny']
            );

            $rlSmarty->assign('no_access', true);
        }
    }

    /**
     * Get plan info depending of logged in account user ID and plan type
     *
     * @param  int $account_id - logged in user account ID
     * @return array           - plan data
     */
    private function getPlanInfo($account_id)
    {
        if (!$account_id) {
            trigger_error('Unable to get get plan info, no $account_id parameter provided', E_USER_ERROR);
            return;
        }

        if ($this->planType == 'account') {
            if ($GLOBALS['account_info']['plan']) {
                $plan = $GLOBALS['account_info']['plan'];
            } else {
                $plan = $this->plans[$this->planID];
            }
        } else {
            $GLOBALS['reefless']->loadClass('Plan');
            $plan = $GLOBALS['rlPlan']->getPlan($this->planID, $account_id);
        }

        return $plan;
    }

    /**
     * Save the current step pointer in listing to allow "restoreFromIncomplete" tool to
     * return user to the last step he ended.
     */
    protected function saveStepPointer()
    {
        if (!$this->listingID) {
            return;
        }

        $update = array(
            'fields' => array('Last_step' => $this->step),
            'where'  => array('ID' => $this->listingID),
        );
        $GLOBALS['rlDb']->update($update, 'listings');
    }

    /**
     * Define single category and simulate "fromCategory" mode
     *
     * @since 4.8.1
     *
     * @param  array &$allowedTypes - Allowed listing types data
     */
    protected function defineSingleCategory(&$allowedTypes)
    {
        if ($category = parent::defineSingleCategory($allowedTypes)) {
            $_GET['id'] = $category['ID'];

            if (!$this->singleStep) {
                unset($this->steps['category']);
            }

            if (!$_POST['step'] && $this->step == 'category') {
                $this->setFromCategoryMode(true);

                if (!$this->singleStep) {
                    $next = $this->getNextStep();
                    $this->step = array_search($next, $this->steps);
                }
            }
        }
    }

    /**
     * Set "From Category" mode
     *
     * @since 4.8.1
     *
     * @param boolean $enable - Enable or disable the mode
     */
    protected function setFromCategoryMode($enable = false)
    {
        if ($enable) {
            $this->fromCategory = true;

            // Simulate post data
            $_POST['from_post'] = true;
            $_POST['step'] = 'form';
        } else {
            $this->fromCategory = false;
        }
    }
}
