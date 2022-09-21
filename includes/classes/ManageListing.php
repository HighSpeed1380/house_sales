<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MANAGELISTING.PHP
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

use Flynax\Abstracts\AbstractSteps;
use Flynax\Utils\Category;

/**
 * @since 4.6.0
 */
class ManageListing extends AbstractSteps
{
    /**
     * Allowed plan type, "listing" or "account"
     *
     * @var string
     */
    public $planType = 'listing';

    /**
     * Selected category data
     *
     * @var array
     */
    public $category = false;

    /**
     * Type data of selected listing
     *
     * @var array
     */
    public $listingType = false;

    /**
     * Available plans
     *
     * @var array
     */
    public $plans = false;

    /**
     * Current listing ID
     *
     * @var int
     */
    public $listingID = false;

    /**
     * Current listing type: standard or featured
     *
     * @var string
     */
    public $adType = 'standard';

    /**
     * Current listing data
     *
     * @var array
     */
    public $listingData = false;

    /**
     * Form fields array
     *
     * @var array
     */
    public $formFields = [];

    /**
     * Prepare parent category keys and assign them in SMARTY
     */
    protected function prepareParentCategoryKeys()
    {
        if ($this->category['Parent_IDs']) {
            $parent_names = $GLOBALS['rlDb']->fetch(
                array('Key'),
                null,
                "WHERE `ID` IN (" . $this->category['Parent_IDs'] . ")
                ORDER BY FIND_IN_SET(`ID`, '" . $this->category['Parent_IDs'] . "') DESC",
                null,
                'categories'
            );
            $GLOBALS['rlSmarty']->assign_by_ref('parent_names', $parent_names);
        }
    }

    /**
     * POST listing data simulation
     */
    protected function simulateListingInPost($hook_name = 'addListingPostSimulation')
    {
        global $reefless, $config;

        if (!$this->listingID) {
            return;
        }

        // emulate existing data if user get a error about not filled data
        if ($this->formFields && $this->listingData) {
            foreach ($this->formFields as &$field) {
                switch ($field['Type']) {
                    case 'image':
                        $_POST['f_sys_exist'][$field['Key']] = $this->listingData[$field['Key']];
                        break;
                }
            }
        }

        if (isset($_POST['from_post'])) {
            return;
        }

        // Restore ad type in MP mode
        $this->simulatePost('ad_type');

        $listing = &$this->listingData;

        // main form data simulation
        foreach ($this->formFields as &$field) {
            if ($listing[$field['Key']] == '') {
                continue;
            }

            switch ($field['Type']) {
                case 'mixed':
                    $value = $listing[$field['Key']] ? explode('|', $listing[$field['Key']]) : '';

                    $_POST['f'][$field['Key']]['value'] = $value[0];
                    $_POST['f'][$field['Key']]['df'] = $value[1];
                    break;

                case 'date':
                    if ($field['Default'] == 'single') {
                        $_POST['f'][$field['Key']] = $listing[$field['Key']];
                    } elseif ($field['Default'] == 'multi') {
                        $_POST['f'][$field['Key']]['from'] = $listing[$field['Key']];
                        $_POST['f'][$field['Key']]['to'] = $listing[$field['Key'] . '_multi'];
                    }
                    break;

                case 'phone':
                    $_POST['f'][$field['Key']] = $reefless->parsePhone($listing[$field['Key']]);
                    break;

                case 'price':
                    $price = $listing[$field['Key']] ? explode('|', $listing[$field['Key']]) : '';

                    if ($config['price_separator'] != '.') {
                        $price[0] = str_replace('.', $config['price_separator'], $price[0]);
                    }

                    $_POST['f'][$field['Key']]['value'] = $price[0];
                    $_POST['f'][$field['Key']]['currency'] = $price[1];
                    break;

                case 'unit':
                    $unit = $listing[$field['Key']] ? explode('|', $listing[$field['Key']]) : '';

                    $_POST['f'][$field['Key']]['value'] = $unit[0];
                    $_POST['f'][$field['Key']]['unit'] = $unit[1];
                    break;

                case 'checkbox':
                    $ch_items = null;
                    $ch_items = explode(',', $listing[$field['Key']]);

                    $_POST['f'][$field['Key']] = $ch_items;
                    unset($ch_items);
                    break;

                case 'textarea':
                    $listing[$field['Key']] = htmlspecialchars_decode($listing[$field['Key']]);

                case 'text':
                    if ($field['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $_POST['f'][$field['Key']] = $reefless->parseMultilingual($listing[$field['Key']]);
                    } else {
                        $_POST['f'][$field['Key']] = $listing[$field['Key']];
                    }
                    break;

                default:
                    $_POST['f'][$field['Key']] = $listing[$field['Key']];
                    break;
            }
        }

        /**
         * @since 4.6.0 $this parameter
         */
        $GLOBALS['rlHook']->load($hook_name, $this);
    }

    /**
     * Simulate visitor account data
     *
     * @param array &$account_info - user account information
     */
    protected function simulateVisitorAccount(&$account_info)
    {
        if (!$account_info['Username'] && $GLOBALS['config']['add_listing_without_reg'] && !defined('IS_LOGIN')) {
            // Disable page login
            $GLOBALS['page_info']['Login'] = 0;

            // Visitor account type simulation
            $account_type_details = $GLOBALS['rlAccount']->getAccountType('visitor');
            $account_info['Type_ID'] = $account_type_details['ID'];
            $account_info['Type'] = $account_type_details['Key'];
            $account_info['ID'] = -1;
            $account_info['Abilities'] = $account_type_details['Abilities'];

            $_SESSION['account'] = $account_info;
        }
    }

    /**
     * Ajax actions handler
     *
     * @param  string $action        - ajax action name
     * @param  array  &$data         - ajax request data
     * @param  array &$account_info  - user account information
     * @return array                 - results array
     */
    public function ajaxAction($action, &$data = null, &$account_info = null)
    {
        global $rlSmarty, $page_info, $rlHook;

        $results = array();

        $rlHook->load('ajaxManageListingActionTop', $this, $action, $account_info, $data);

        switch ($action) {
            case 'select_category':
                // Load system libs
                require_once RL_LIBS . 'system.lib.php';

                // Simulate page info
                $page_info = $GLOBALS['rlDb']->fetch(
                    '*',
                    array('Key' => $this->pageKey),
                    null, 1, 'pages', 'row'
                );

                $rlHook->load('pageinfoArea');

                // Assign page keys array
                $rlSmarty->assign_by_ref('pages', $GLOBALS['pages']);

                // Initialize parent
                parent::init();

                // Simulate visitor account
                $this->simulateVisitorAccount($account_info);

                // Register object in Smarty
                $rlSmarty->assign_by_ref('manageListing', $this);

                // Get category info
                if (intval($data['is_user_category'])) {
                    $this->category = Category::getUserCategory($data['category_id']);
                } else {
                    $this->category = Category::getCategory($data['category_id']);
                }

                // Save new category ID
                $this->newCategoryID = $data['category_id'];

                // Get listing type
                $this->listingType = $GLOBALS['rlListingTypes']->types[$this->category['Type']];
                $rlSmarty->assign_by_ref('listing_type', $this->listingType);

                // Legacy add listing version and plugins compatibility support
                $this->legacySupport();

                // Assign listing type data
                $results['listing_type'] = array(
                    'Photo'          => $this->listingType['Photo'],
                    'Photo_required' => $this->listingType['Photo_required'],
                    'Video'          => $this->listingType['Video'],
                    'Key'            => $this->listingType['Key'],
                );

                // Remove plan step if only one free plan allowed
                $this->singlePlanHandler($account_info);

                // Existing membership plan mode
                $this->existingMembershipHandler($account_info);

                // Initiate plan step
                $this->fetchPlans($account_info);

                // Assign plans data
                $results['single_plan'] = $this->singlePlan;
                $results['plans'] = array_values($this->plans);

                // Create blank listing to allow media to be assigned to it
                $this->createBlankListing($account_info);

                // Assign listing id
                $results['listing_id'] = $this->listingID;

                // Initiate form step
                $this->stepForm();

                // Enable pre ajax support in SMARTY
                $rlSmarty->preAjaxSupport();

                // Assign language list to SMARTY
                $rlSmarty->assign_by_ref('languages', $GLOBALS['languages']);

                // Assign form data
                $tpl = FL_TPL_CONTROLLER_DIR . 'add_listing' . RL_DS . 'step_form.tpl';
                $results['form'] = $rlSmarty->fetch($tpl, null, null, false);

                // Prepare auth form content
                $this->prepareQuickTypes();

                // get a list with agreement fields
                if ($account_info['Type'] == 'visitor') {
                    $rlSmarty->assign('agreement_fields', $GLOBALS['rlAccount']->getAgreementFields());
                }

                $tpl = FL_TPL_CONTROLLER_DIR . 'add_listing' . RL_DS . 'auth_form.tpl';
                $results['auth'] = $rlSmarty->fetch($tpl, null, null, false);

                // Enable pre ajax support in SMARTY
                $rlSmarty->postAjaxSupport($results, $page_info, $tpl);
                break;

            case 'select_plan':
                // Save new plan ID
                $_POST['plan'] = $this->planID = (int) $_REQUEST['plan_id'];
                $this->savePost('plan');

                // Get plan info
                $plan_info = $this->plans[$this->planID];

                // Reset options depends of the plan selected
                if (!$plan_info['Advanced_mode']) {
                    $this->adType = 'standard';
                }
                if (!$plan_info['Subscription']
                    || $plan_info['Price'] <= 0
                    || $plan_info['Listings_remains']) {
                    $this->removePost('subscription');
                }

                // Return plan ID to avoid error response
                $results = $this->planID;
                break;

            case 'change_ad_type':
                $_POST['ad_type'] = $this->adType = $_REQUEST['ad_type'] == 'featured'
                ? 'featured'
                : 'standard'; // Some kind of validation
                $this->savePost('ad_type');

                $results = $this->adType;
                break;

            case 'change_subscription':
                if ($_REQUEST['subscription']) {
                    $_POST['subscription'] = $this->planID;
                    $this->savePost('subscription');
                } else {
                    $this->removePost('subscription');
                }

                $results = $this->postData['subscription'];
                break;

            case 'get_plans_chart':
                // Initialize parent
                parent::init();

                // Register object in Smarty
                $rlSmarty->assign_by_ref('manageListing', $this);

                // Run step plan method
                $this->stepPlan();

                // Enable pre ajax support in SMARTY
                $rlSmarty->preAjaxSupport();

                // Assign listing type
                $rlSmarty->assign_by_ref('listing_type', $this->listingType);

                // Assign form data
                $tpl = FL_TPL_CONTROLLER_DIR . 'add_listing' . RL_DS . 'step_plan.tpl';
                $results['html'] = $rlSmarty->fetch($tpl, null, null, false);

                // Enable pre ajax support in SMARTY
                $rlSmarty->postAjaxSupport($results, $page_info, $tpl);
                break;

            default:
                $GLOBALS['rlDebug']->logger('addListing::ajaxAction(), no action specified.');
                $results = false;
                break;
        }

        return $results;
    }

    /**
     * Single plan mode handler
     *
     * @param array &$account_info - user account information
     */
    public function singlePlanHandler(&$account_info)
    {
        // Return if the data already defined
        if (!$this->steps['plan']
            || $this->singlePlan
            || !in_array($this->step, array('category', 'plan'))) {
            return;
        }

        if ($this->planType == 'account') {
            $plan_table = 'membership_plans';
            $match_count = "COUNT(*) AS `match_count`,";
            $add_where = "AND FIND_IN_SET('1', `Services`) > 0 ";
        } else {
            $add_select = $this->category['ID'] ? "OR FIND_IN_SET('{$this->category['ID']}', `Category_ID`) > 0 " : '';
            $plan_table = 'listing_plans';
            $match_count = "SUM(IF(`Sticky` = '1' {$add_select}, 1, 0)) AS `match_count`,";
            $add_where = "AND `Type` != 'featured' ";
        }

        $sql = "SELECT *, COUNT(*) AS `count`, {$match_count} SUM(IF(`Price` > 0, 0, 1)) AS `free` ";
        $sql .= "FROM `" . RL_DBPREFIX . $plan_table . "` ";
        $sql .= "WHERE `Status` = 'active' ";
        $sql .= $add_where;

        if ($account_info && $account_info['Type']) {
            $sql .= "AND (
                FIND_IN_SET('{$account_info['Type']}', `Allow_for`) > 0
                OR `Allow_for` = ''
            )";
        }

        $plan = $GLOBALS['rlDb']->getRow($sql);

        if (
            $plan['count'] == '1'
            && $plan['count'] == $plan['match_count']
            && $plan['free'] == 1
        ) {
            $plan_usage = '';

            if ($account_info && $account_info['ID']) {
                $plan_usage = $GLOBALS['rlDb']->getOne(
                    'Listings_remains',
                    "`Account_ID` = {$account_info['ID']} AND `Plan_ID` = {$plan['ID']} AND `Type` = 'limited'",
                    'listing_packages'
                );
            }

            // Single exceeded limited plan flow
            if ($account_info['ID'] > 0
                && $plan['Limit'] > 0
                && $plan_usage === '0'
            ) {
                return;
            }

            $this->singlePlan = true;
            $this->skipCheckout = true;
            $this->planID = $plan['ID'];
            $this->plans = array($plan['ID'] => $plan);

            unset($this->steps['plan']);
        }
    }

    /**
     * Existing membership plan handler
     *
     * @param array &$account_info - user account information
     */
    protected function existingMembershipHandler(&$account_info)
    {
        if ($this->planType == 'account'
            && !$this->newMembership
            && $account_info['Plan_ID']
        ) {
            $plan_using = $GLOBALS['rlMembershipPlan']->getUsingPlan(
                $account_info['Plan_ID'],
                $account_info['ID']
            );
            $GLOBALS['rlSmarty']->assign('plan_using', $plan_using);

            if ($account_info['Payment_status'] == 'paid'
                && (
                    $account_info['plan']['Listing_number'] == 0
                    || ($account_info['plan']['Listing_number'] > 0
                        && $plan_using['Listings_remains'] > 0)
                    || ($account_info['plan']['Listing_number'] > 0
                        && $account_info['plan']['Advanced_mode']
                        && ($account_info['plan']['Standard_listings'] == 0
                            || $account_info['plan']['Featured_listings'] == 0)
                    )
                )
            ) {
                $GLOBALS['rlMembershipPlan']->fixAvailability($account_info);

                $this->singlePlan = true;
                $this->skipCheckout = true;
                $this->planID = $account_info['Plan_ID'];
                $this->plans = array(
                    $account_info['Plan_ID'] => $account_info['plan'],
                );

                $GLOBALS['rlSmarty']->assign('plan_info', $account_info['plan']);

                // Reset featured status
                if (!$account_info['plan']['Featured_listing']
                    || ($account_info['plan']['Advanced_mode']
                        && $account_info['plan']['Featured_remains'] <= 0)
                ) {
                    $_POST['ad_type'] = $this->adType = $this->postData['ad_type'] = 'standard';
                }

                // Unset steps
                unset($this->steps['plan'], $this->steps['checkout']);
            }
        }
    }

    /**
     * Prepare crossed categories data
     *
     * @param array $plan_info - plan data
     * @param array &$account_info - user account information
     */
    protected function prepareCrossedCategories(&$plan_info, &$account_info)
    {
        global $rlSmarty;

        if ($plan_info['Cross']) {
            // Define and assign in SMARTY allowed for crossing listing types
            $crossed_types = (!$GLOBALS['config']['crossed_categories_by_type']
                && in_array($this->listingType['Key'], $account_info['Abilities']))
            ? array($this->listingType['Key'])
            : $account_info['Abilities'];

            $allowed_types = $GLOBALS['rlListingTypes']->adaptTypes($crossed_types);
            $rlSmarty->assign_by_ref('crossed_types', $allowed_types);

            // Restore crossed categories in the post
            $this->simulatePost('crossed_categories');

            // Get crossed categories data
            if ($this->postData['crossed_categories']) {
                $crossed_categories = $GLOBALS['rlDb']->fetch(
                    array('ID', 'Key', 'Path'),
                    null,
                    "WHERE `ID` IN (" . $this->postData['crossed_categories'] . ")",
                    null,
                    'categories'
                );
                $rlSmarty->assign_by_ref('crossed_categories', $crossed_categories);
            }
        }
    }

    /**
     * Legacy add listing feature support for plugins
     *
     * @todo Remove after related plugins update
     */
    protected function legacySupport()
    {
        if ($this->planID) {
            global $plan_id, $plan_info;

            $plan_id = $this->planID;
            $plan_info = $this->plans[$this->planID];
        }

        if ($this->listingID) {
            global $listing_id;

            $listing_id = $this->listingID;
            $_SESSION['add_listing']['listing_id'] = $this->listingID;
        }

        if ($this->listingData) {
            global $listing_data;

            $listing_data = $this->listingData;
        }

        if ($this->listingType) {
            global $listing_type, $listing;

            $listing_type = $this->listingType;
            $listing = $this->listingData; // Location Finder fallback
        }
    }

    /**
     * Fetch plans by account type and assign them in SMARTY
     *
     * @param array &$account_info - user account information
     */
    protected function fetchPlans(&$account_info)
    {
        global $rlMembershipPlan;

        // fetch membership plans
        if ($this->planType == 'account') {
            if ($_SESSION['account'] && $account_info['Plan_ID']) {
                $planInfo = $rlMembershipPlan->getPlanByProfile($account_info);
                $this->plans[$planInfo['ID']] = $planInfo;
            } else {
                $this->plans = $rlMembershipPlan->getPlansByType($account_info['Type'], 1);
            }
        }
        // fetch listing plans
        else {
            $GLOBALS['reefless']->loadClass('Plan');
            $this->plans = $GLOBALS['rlPlan']->getPlanByCategory($this->category['ID'], $account_info['Type']);
        }
        $GLOBALS['rlSmarty']->assign_by_ref('plans', $this->plans);
    }

    /**
     * Checkout step handler
     */
    protected function stepCheckout()
    {
        parent::step();

        global $rlPayment, $rlSmarty, $errors, $account_info;

        $this->saveStepPointer();

        // Get listing title
        $listing_title = $GLOBALS['rlListings']->getListingTitle(
            $this->category['ID'],
            $this->listingData,
            $this->listingType['Key']
        );
        $rlSmarty->assign_by_ref('listing_title', $listing_title);

        // Get plan info
        $plan_info = $this->plans[$this->postData['plan']];
        $rlSmarty->assign_by_ref('plan_info', $plan_info);

        // Payment canceled
        if (isset($_GET['canceled'])) {
            $GLOBALS['reefless']->loadClass('Notice');
            $GLOBALS['rlNotice']->saveNotice($GLOBALS['rlLang']->getPhrase('payment_canceled'), 'alert');
        }

        // Proceed to the checkout
        if ($rlPayment->isPrepare()) {
            $rlPayment->checkout($errors, true);
        }
        // Prepare payment
        else {
            // Reset previous usage
            $rlPayment->clear();

            // save payment details
            $cancel_url = $this->buildStepURL(null, $this->step, $this->extendUrl());
            $cancel_url .= $GLOBALS['config']['mod_rewrite'] ? '?canceled' : '&canceled';
            $success_url = $this->buildNextStepURL();

            // set payment options
            $rlPayment->setOption('total', $plan_info['Price']);
            $rlPayment->setOption('plan_id', $plan_info['ID']);

            if ($this->planType == 'account') {
                $rlPayment->setOption('service', 'membership');
                $rlPayment->setOption('item_id', $account_info['ID']);
                $rlPayment->setOption('item_name', $GLOBALS['lang']['membership_plans+name+' . $plan_info['Key']]);
                $rlPayment->setOption('callback_class', 'rlAccount');
                $rlPayment->setOption('callback_method', 'upgrade');
            } else {
                $rlPayment->setOption('service', $plan_info['Type']);
                $rlPayment->setOption('item_id', $this->listingID);
                $rlPayment->setOption('item_name', $listing_title . ' (#' . $this->listingID . ')');
                $rlPayment->setOption('plan_key', 'listing_plans+name+' . $plan_info['Key']);
                $rlPayment->setOption('callback_class', 'rlListings');
                $rlPayment->setOption('callback_method', 'upgradeListing');
            }

            $rlPayment->setOption('account_id', $account_info['ID']);
            $rlPayment->setOption('cancel_url', $cancel_url);
            $rlPayment->setOption('success_url', $success_url);

            // set recurring option
            if ($plan_info['Subscription'] && $this->postData['subscription'] == $plan_info['ID']) {
                $rlPayment->enableRecurring();
            }

            // if select featured option
            if ($this->adType == 'featured') {
                $rlPayment->setOption('params', 'featured');
            }

            $rlPayment->init($errors);
        }
    }

    /**
     * Prepare the account types which have ability to add a listing in current listing type
     */
    protected function prepareQuickTypes()
    {
        global $rlAccountTypes;

        if (!defined('IS_LOGIN')) {
            foreach ($rlAccountTypes->types as &$account_type) {
                if ($account_type['Quick_registration']
                    && in_array($this->listingType['Key'], explode(',', $account_type['Abilities']))
                ) {
                    $quick_types[] = $account_type;
                }
            }
            $GLOBALS['rlSmarty']->assign_by_ref('quick_types', $quick_types);
        }
    }

    /**
     * Define single category mode
     *
     * @since 4.8.1
     *
     * @param  array &$allowedTypes - Allowed listing types keys to post a listing in
     * @return mixed                - Returns the single category data as array or false
     */
    protected function defineSingleCategory(&$allowedTypes)
    {
        if (count($allowedTypes) > 1) {
            return false;
        }

        $key = key($allowedTypes);

        if ($category = $allowedTypes[$key]['Single_category']) {
            $GLOBALS['rlSmarty']->assign('single_category_mode', true);
        }

        return $category;
    }
}
