<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLMEMBERSHIPPLAN.CLASS.PHP
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

class rlMembershipPlan extends reefless
{
    const SERVICE_VIEW_PHOTOS = 'view_photos';
    const SERVICE_VIEW_CONTACTS = 'view_contacts';
    const SERVICE_CONTACT_OWNER = 'contact_owner';
    const SERVICE_FEATURED = 'featured';
    const SUBSCRIPTION_SERVICE_NAME = 'membership';

    /**
     * @var language class object
     **/
    protected $rlLang;

    /**
     * @var actions class object
     **/
    protected $rlActions;

    /**
     * @var $services
     */
    protected $services;

    /**
     * @var is contact allowed indicator
     */
    public $is_contact_allowed = false;

    /**
     * @var is send message allowed indicator
     */
    public $is_send_message_allowed = false;

    /**
     * class constructor
     *
     */
    public function __construct()
    {
        global $rlLang, $rlActions;

        if (!$rlActions) {
            $this->loadClass('Actions');
        }
        $this->rlActions = $GLOBALS['rlActions'];
        $this->rlLang = $GLOBALS['rlLang'];

        $this->isContactsAllow();
        $this->isSendMessage();
    }

    /**
     * Get plans by account type
     *
     * @since 4.7.1 - $service_id parameter added
     *
     * @param  string  $type       - account type key
     * @param  integer $service_id - service ID to filter plans by
     * @return array               - plans
     */
    public function getPlansByType($type = false, $service_id = null)
    {
        if (!$type) {
            return false;
        }

        $sql = "SELECT DISTINCT `T1`.*, `T2`.`Status` AS `Subscription`, `T2`.`Period` ";
        $sql .= "FROM `{db_prefix}membership_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` ";
        $sql .= "AND `T2`.`Service` = '" . self::SUBSCRIPTION_SERVICE_NAME . "' AND `T2`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND (FIND_IN_SET('{$type}', `Allow_for`) > 0 OR `Allow_for` = '') ";
        if ($service_id) {
            $sql .= "AND FIND_IN_SET({$service_id}, `T1`.`Services`) > 0 ";
        }
        $sql .= "ORDER BY `Position`";
        $plans = $this->getAll($sql, 'ID');

        if ($plans) {
            foreach ($plans as $key => $value) {
                if ($GLOBALS['rlAccount']->isLogin()) {
                    $where = [
                        'Account_ID' => $GLOBALS['account_info']['ID'],
                        'Plan_ID' => $value['ID'],
                        'Type' => 'account',
                    ];
                    $plan_using = $this->fetch(['Count_used'], $where, null, null, 'listing_packages', 'row');
                    $plans[$key]['Count_used'] = $plan_using['Count_used'] ?: 0;
                }

                if ($value['Services']) {
                    $service_ids = explode(',', $value['Services']);

                    $sql = "SELECT * FROM `{db_prefix}membership_services` ";
                    $sql .= "WHERE `ID` = '" . implode("' OR `ID` = '", $service_ids) . "' ORDER BY `Position` ASC";
                    $plans[$key]['Services'] = $this->getAll($sql, 'Key');
                }
            }
            $plans = $this->rlLang->replaceLangKeys($plans, 'membership_plans', array('name', 'des'));
        }

        return $plans;
    }

    /**
     * bet plan by ID
     *
     * @param integer $id
     * @return array
     */
    public function getPlan($id = false, $using = false, $account_info = false)
    {
        $id = (int) $id;

        if (!$id) {
            return array();
        }

        $sql = "SELECT `T1`.*, `T2`.`Status` AS `Subscription`, `T2`.`Period` ";
        $sql .= "FROM `{db_prefix}membership_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Service` = '" . self::SUBSCRIPTION_SERVICE_NAME . "' AND `T2`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`ID` = '{$id}' AND `T1`.`Status` = 'active' ";
        $sql .= "LIMIT 1";

        $plan = $this->getRow($sql);

        if ($plan) {
            $plan['name'] = $GLOBALS['lang']['membership_plans+name+' . $plan['Key']];
            if ($plan['Services']) {
                $service_ids = explode(',', $plan['Services']);

                $sql = "SELECT * FROM `{db_prefix}membership_services` WHERE `ID` = '" . implode("' OR `ID` = '", $service_ids) . "' ORDER BY `Position` ASC";
                $plan['Services'] = $this->getAll($sql, 'Key');

                if ($plan['Services']) {
                    $plan['Services'] = $this->rlLang->replaceLangKeys($plan['Services'], 'membership_services', array('name'));
                }
            }

            if ($using && $account_info) {
                $plan_using = $this->fetch('*', array('Account_ID' => $account_info['ID'], 'Plan_ID' => $account_info['Plan_ID'], 'Type' => 'account'), null, null, 'listing_packages', 'row');

                if ($plan_using) {
                    $plan['Listings_remains'] = (int) $plan_using['Listings_remains'];
                    $plan['Standard_remains'] = (int) $plan_using['Standard_remains'];
                    $plan['Featured_remains'] = (int) $plan_using['Featured_remains'];
                } else {
                    $plan['Listings_remains'] = (int) $plan['Listing_number'];
                    $plan['Standard_remains'] = (int) $plan['Standard_listings'];
                    $plan['Featured_remains'] = (int) $plan['Featured_listings'];
                }
                $plan['Count_used'] = (int) $plan_using['Count_used'];
            }
        }
        return $plan;
    }

    /**
     * get plan details by current account
     *
     * @return array
     */
    public function getPlanByProfile(&$account_info)
    {
        $account_info_update = $account_info;
        if (isset($_GET['completed'])) {
            $account_info_update = $this->fetch('*', array('ID' => $account_info['ID']), null, 1, 'accounts', 'row');
        }
        $sql = "SELECT `T1`.*, `T2`.`Period`, `T3`.`Subscription_ID` AS `Subscription`, `T3`.`ID` AS `Subscription_ID`, `T3`.`Service` AS `Subscription_service`, ";
        $sql .= "DATE_ADD('{$account_info_update['Pay_date']}', INTERVAL `T1`.`Plan_period` DAY) AS `Plan_expire` ";
        $sql .= "FROM `{db_prefix}membership_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Service` = '" . self::SUBSCRIPTION_SERVICE_NAME . "' AND `T2`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `T3` ON
                `T1`.`ID` = `T3`.`Plan_ID` AND
                `T3`.`Item_ID` = '{$account_info_update['ID']}' AND
                `T3`.`Service` = '" . self::SUBSCRIPTION_SERVICE_NAME . "' AND
                `T3`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`ID` = '{$account_info_update['Plan_ID']}' AND `T1`.`Status` = 'active' LIMIT 1";

        $plan = $this->getRow($sql);

        if ($plan) {
            $plan['name'] = $GLOBALS['lang']['membership_plans+name+' . $plan['Key']];
            if ($plan['Services']) {
                $service_ids = explode(',', $plan['Services']);

                $sql = "SELECT * FROM `{db_prefix}membership_services` WHERE `ID` = '" . implode("' OR `ID` = '", $service_ids) . "' ORDER BY `Position` ASC";
                $plan['Services'] = $this->getAll($sql, 'Key');

                if ($plan['Services']) {
                    $plan['Services'] = $this->rlLang->replaceLangKeys($plan['Services'], 'membership_services', array('name'));
                }
            }
        }
        if ($account_info_update && $plan) {
            $plan_using = $this->fetch('*', array('Account_ID' => $account_info_update['ID'], 'Plan_ID' => $account_info_update['Plan_ID'], 'Type' => 'account'), null, null, 'listing_packages', 'row');

            if ($plan_using) {
                $plan['Listings_remains'] = (int) $plan_using['Listings_remains'];
                $plan['Standard_remains'] = (int) $plan_using['Standard_remains'];
                $plan['Featured_remains'] = (int) $plan_using['Featured_remains'];
                $plan['Count_used'] = (int) $plan_using['Count_used'];
            } else {
                $plan['Listings_remains'] = (int) $plan['Listing_number'];
                $plan['Standard_remains'] = (int) $plan['Standard_listings'];
                $plan['Featured_remains'] = (int) $plan['Featured_listings'];
            }
        }
        if (isset($_GET['completed'])) {
            $account_info['plan'] = $_SESSION['account']['plan'] = $plan;

            $expiration_date = strtotime($account_info_update['Pay_date']) + ((int)$plan['Plan_period'] * 86400);
            $account_info['Status'] = time() > $expiration_date && $plan['Plan_period'] > 0 ? 'expired' : $account_info_update['Status'];
            $account_info['Plan_ID'] = $_SESSION['account']['Plan_ID'] = $plan['ID'];
        }

        $plan['standard_disabled'] = !$plan['Standard_remains'] && $plan['Standard_listings'];
        $plan['featured_disabled'] = !$plan['Featured_remains'] && $plan['Featured_listings'];

        return $plan;
    }

    /**
     * get all plan services
     * @return array
     *
     */
    public function getServices()
    {
        if ($this->services) {
            return $this->services;
        }

        $sql = "SELECT * FROM `{db_prefix}membership_services` WHERE `Status` = 'active'";
        $this->services = $this->getAll($sql, 'Key');

        if ($this->services) {
            $this->services = $this->rlLang->replaceLangKeys($this->services, 'membership_services', array('name'));
        }

        return $this->services;
    }

    /**
     * get all active plans
     *
     * @return array
     */
    public function getPlans()
    {
        $sql = "SELECT DISTINCT `T1`.* ";
        $sql .= "FROM `{db_prefix}membership_plans` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position`";
        $plans = $this->getAll($sql, 'ID');

        if ($plans) {
            $plans = $this->rlLang->replaceLangKeys($plans, 'membership_plans', array('name', 'des'));
            foreach ($plans as $key => $value) {
                if ($GLOBALS['rlAccount']->isLogin()) {
                    $where = [
                        'Account_ID' => $GLOBALS['account_info']['ID'],
                        'Plan_ID' => $value['ID'],
                        'Type' => 'account',
                    ];
                    $plan_using = $this->fetch('*', $where, null, null, 'listing_packages', 'row');

                    $plans[$key]['Count_used'] = $plan_using['Count_used'] ?: 0;
                }

                if ($value['Services']) {
                    $service_ids = explode(',', $value['Services']);

                    $sql = "SELECT * FROM `{db_prefix}membership_services` WHERE `ID` = '" . implode("' OR `ID` = '", $service_ids) . "' ORDER BY `Position` ASC";
                    $plans[$key]['Services'] = $this->getAll($sql);
                }
            }
        }

        return $plans;
    }

    /**
     * skip registration step
     *
     * @param mixed $current_step
     */
    public function skipRegistrationStep(&$current_step, $step = '')
    {
        global $reg_steps;

        if (!$current_step || !$reg_steps) {
            return;
        }
        $keys = array_keys($reg_steps);
        foreach ($keys as $key => $value) {
            if ($value == $step) {
                $index = $key;
                break;
            }
        }
        $next_key = $keys[$index + 1];
        $current_step = $reg_steps[$next_key];
    }

    /**
     * add registration step
     *
     * @param array $current_step
     * @param string $step
     * @return null
     */
    public function addRegistrationStep(&$current_step, $step = '')
    {
        global $tmp_steps;

        if (!$current_step || !$tmp_steps) {
            return;
        }
        if (isset($tmp_steps[$step])) {
            $current_step = $tmp_steps[$step];
        }
    }

    /**
     * check service
     *
     * @param string $key
     *
     * @return boolean
     */
    public function isServiceActive($key)
    {
        $this->getServices();
        if (isset($this->services[$key])) {
            return true;
        }
        return false;
    }

    /**
     * check if allowed photos
     *
     * @param array $account - lisitng details
     *
     * @return boolean
     */
    public function isPhotoAllow(&$listing)
    {
        global $account_info;

        $allow_photos = false;

        if (!$GLOBALS['config']['membership_module'] || !$this->isServiceActive('view_photos') || $account_info['ID'] == $listing['Account_ID']) {
            $allow_photos = true;
        } elseif ($GLOBALS['rlAccount']->isLogin() && $account_info['Payment_status'] == 'paid' && isset($account_info['plan']['Services']['view_photos'])) {
            $allow_photos = true;
        }

        return $allow_photos;
    }

    /**
     * check if "view contacts details" service allowed for logged in user
     *
     */
    public function isContactsAllow()
    {
        global $account_info;

        $allow_contacts = false;

        if (!$GLOBALS['config']['membership_module'] || !$this->isServiceActive('view_contacts')) {
            $allow_contacts = true;
        } elseif ($GLOBALS['rlAccount']->isLogin() && $account_info['Payment_status'] == 'paid' && isset($account_info['plan']['Services']['view_contacts'])) {
            $allow_contacts = true;
        }

        $this->is_contact_allowed = $allow_contacts;
        if (is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign_by_ref('allow_contacts', $this->is_contact_allowed);
        }
    }

    /**
     * check if allowed send message
     *
     * @return boolean
     */
    public function isSendMessage()
    {
        global $account_info;

        $allow_send_message = false;

        if (!$account_info && $_SESSION['account']) {
            $account_info = $_SESSION['account'];
        }

        if (!$GLOBALS['config']['membership_module'] || !$this->isServiceActive('contact_owner')) {
            $allow_send_message = true;
        } elseif ($GLOBALS['rlAccount']->isLogin() && $account_info['Payment_status'] == 'paid' && isset($account_info['plan']['Services']['contact_owner'])) {
            $allow_send_message = true;
        }

        $this->is_send_message_allowed = $allow_send_message;
        if (is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign_by_ref('allow_send_message', $this->is_send_message_allowed);
        }
    }

    /**
     * check if allowed add listings
     *
     * @return boolean
     */
    public function isAddListingAllow()
    {
        global $account_info;

        $plan_using = $this->getUsingPlan($account_info['Plan_ID'], $account_info['ID']);

        /**
         * @todo TMP solution to allow new users to add listings without MS plan
         */
        if (!$account_info['plan']) {
            return true;
        }

        if ($account_info['plan']['Services']['add_listing']
            && (
                $account_info['plan']['Listing_number'] == 0
                || ($account_info['plan']['Listing_number'] > 0 && $plan_using['Listings_remains'] > 0)
                || (
                    $account_info['plan']['Advanced_mode']
                    && ($account_info['plan']['Standard_listings'] == 0 || $account_info['plan']['Featured_listings'] == 0)
                )
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * set fake values for fields marked as "contact data"
     *
     * @param array $account - account fields
     *
     * @return boolean
     */
    public function fakeValues(&$fields)
    {
        if (is_array($fields)) {
            foreach ($fields as &$field) {
                if ($field['Contact'] && !$this->is_contact_allowed) {
                    $field['value'] = str_replace('{field}', $field['name'] ? $field['name'] : $GLOBALS['lang'][$field['pName']], $GLOBALS['lang']['fake_value']);
                    $field['fake'] = true;
                }
            }
        }
    }

    /**
     * set fake value for listing field marked as "contact data"
     *
     * @param array $field
     * @param mixed $value - listing value
     * @param string $form - type of listing form
     * @return string
     */
    public function fakeListingValue(&$field, $value = '', $form = '')
    {
        if (!$field || $value == '') {
            return false;
        }
        if ($field['Contact'] && !$this->is_contact_allowed) {
            if ($form == 'title_form') {
                return false;
            }
            $value = str_replace('{field}', $field['name'] ? $field['name'] : $GLOBALS['lang'][$field['pName']], $GLOBALS['lang']['fake_value']);
            if ($form == 'listing_form') {
                if ($GLOBALS['rlAccount']->isLogin()) {
                    $link = '<a href="' . $this->getPageUrl('my_profile') . '#membership">' . $GLOBALS['lang']['change_plan'] . '</a>';
                } else {
                    $link = '<a href="javascript://" class="login">' . $GLOBALS['lang']['sing_in'] . '</a>';
                }
                $field['fake'] = true;

                return $value . ', ' . str_replace('{link}', $link, $GLOBALS['lang']['fake_value_notice']);
            } else {
                return $value;
            }
        }
        return false;
    }

    /**
     * get detais of plan using
     *
     * @param integer $plan_id
     * @param integer $account_id
     * @return data
     */
    public function getUsingPlan($plan_id = false, $account_id = false)
    {
        if (!$plan_id || !$account_id) {
            return false;
        }
        $plan_id = (int) $plan_id;
        $account_id = (int) $account_id;

        return $this->getRow("SELECT * FROM `{db_prefix}listing_packages` WHERE `Plan_ID` = '{$plan_id}' AND `Type` = 'account' AND `Account_ID` = '{$account_id}' LIMIT 1");
    }

    /**
     * @deprecated 4.6.0 - Moved to Flynax\Classes\AddListing
     *
     * Check if available only one plan by account type
     * @param  string $type - type of account
     * @return mixed
     */
    public function isSinglePlan($type = '', $info = false)
    {
        if (!$type) {
            return false;
        }

        $sql = "SELECT *, COUNT(*) AS `count` ";
        $sql .= "FROM `{db_prefix}membership_plans` ";
        $sql .= "WHERE `Status` = 'active' AND FIND_IN_SET('{$type}', `Allow_for`) > 0";
        $plan_info = $this->getRow($sql);

        if ($plan_info['count'] == 1 && $plan_info['Price'] <= 0) {
            if ($info) {
                return $plan_info;
            }
            return $plan_info['ID'];
        }

        return false;
    }

    /**
     * control limit of using plan
     *
     * @param integer $plan_id
     * @param integer $account_id
     */
    public function controlLimitUsePlan($plan_id = 0, $account_id = 0)
    {
        if (!$plan_id) {
            return;
        }
        $plan = $this->getPlan($plan_id);
        if ($plan) {
            if ($plan['Limit'] > 0) {
                $sql = "UPDATE `{db_prefix}listing_packages` SET `Count_used` = `Count_used` - 1 WHERE `Account_ID` = '{$account_id}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
                $this->query($sql);
            }
        }
    }

    /**
     * check if limit of using plan is exceeded
     *
     * @param array $plan
     * @param array $account
     *
     * @return boolean
     */
    public function isLimitExceeded(&$plan, $account_id = 0)
    {
        if (!$plan) {
            return false;
        }
        $plan_using = $this->fetch('*', array('Account_ID' => (int) $account_id, 'Plan_ID' => (int) $plan['ID'], 'Type' => 'account'), null, null, 'listing_packages', 'row');
        if ($plan['Limit'] > 0) {
            if ($plan['Limit'] <= $plan_using['Count_used']) {
                return true;
            }
        }
        return false;
    }

    public function defineAllowedPlanType()
    {
        global $config, $rlAccount;

        // Default plan type is listings
        $plan_type = 'listing';

        // Return default plan type if the MS module disabled
        if (!$config['membership_module']) {
            return $plan_type;
        }

        // Define type
        if ($this->isServiceActive('add_listing')) {
            // Logged in user mode with "add listing" option enabled
            if ($rlAccount->isLogin()) {
                if ($this->isAddListingAllow()) {
                    $plan_type = 'account';
                } elseif ($config['allow_listing_plans']) {
                    $plan_type = 'listing';
                } else {
                    $plan_type = false;
                }
            }
            // Not logged in user mode
            else {
                if ($config['add_listing_without_reg']) {
                    $plan_type = 'account';
                }
                // Non access at all
                else {
                    $plan_type = false;
                }
            }
        } else {
            // Listings packages mode, if listings packages with membership plans allowed together
            if ($config['allow_listing_plans']) {
                $plan_type = 'listing';
            }
            // No access at all
            else {
                $plan_type = false;
            }
        }

        return $plan_type;
    }

    public function handleRestrictedAccess(&$errors)
    {
        global $reefless, $rlSmarty, $rlAccount, $lang, $config;

        if ($this->isServiceActive('add_listing')) {
            if ($rlAccount->isLogin()) {
                if (!$this->isAddListingAllow() && !$config['allow_listing_plans']) {
                    $phrase = $lang['membership_plan_not_allow_add_listing'];
                    $link = $reefless->getPageUrl('my_profile', array('purchase'));
                }
            }
        } elseif (!$config['allow_listing_plans']) {
            $errors[] = $lang['add_listing_not_allow'];
        }

        $rlSmarty->assign('phrase', $phrase);
        $rlSmarty->assign('link', $link);

        $GLOBALS['rlSmarty']->register_function('processStep', [$this, 'processTplStep']);
    }

    public function processTplStep()
    {
        $GLOBALS['rlSmarty']->display(FL_TPL_CONTROLLER_DIR . 'add_listing' . RL_DS . 'access_restricted.tpl');
    }

    /**
     * update plan using details
     *
     * @param string $ad_type - listing type option (standard or featured)
     * @return boolean
     */
    public function updatePlanUsing($ad_type = 'standard')
    {
        global $account_info, $rlDb, $reefless;

        if (!$account_info || !$account_info['Plan_ID']) {
            $GLOBALS['rlDebug']->logger("rlMembershipPlan::updatePlanUsing() failed, no account_info or account_info['Plan_ID'] is available in global");
            return;
        }

        $count_used = &$account_info['plan']['Count_used'];
        $listing_remains = &$account_info['plan']['Listings_remains'];
        $standard_remains = &$account_info['plan']['Standard_remains'];
        $featured_remains = &$account_info['plan']['Featured_remains'];

        // Get plan usage info
        $plan_usage = $rlDb->fetch('*',
            array(
                'Plan_ID'    => $account_info['Plan_ID'],
                'Type'       => 'account',
                'Account_ID' => $account_info['ID'],
            ),
            null, 1, 'listing_packages', 'row'
        );

        // Update mode
        if ($plan_usage['ID']) {
            $listing_remains = $plan_usage['Listings_remains'] - 1;

            $update = array(
                'fields' => array(
                    'Account_ID'       => $account_info['ID'],
                    'Listings_remains' => $listing_remains,
                    'Date'             => 'NOW()',
                    'IP'               => $reefless->getClientIpAddress(),
                ),
                'where'  => array(
                    'ID' => $plan_usage['ID'],
                ),
            );

            if ($account_info['plan']['Limit'] > 0) {
                $count_used = $plan_usage['Count_used'] + 1;
                $update['fields']['Count_used'] = $count_used;
            }

            if ($account_info['plan']['Advanced_mode']) {
                if ($ad_type == 'standard') {
                    $standard_remains = $plan_usage['Standard_remains'] - 1;
                    $update['fields']['Standard_remains'] = $standard_remains;
                }
                if ($ad_type == 'featured') {
                    $featured_remains = $plan_usage['Featured_remains'] - 1;
                    $update['fields']['Featured_remains'] = $featured_remains;
                }
            }

            $rlDb->update($update, 'listing_packages');
        }
        // Insert mode
        else {
            $listing_remains--;

            $insert = array(
                'Account_ID'       => (int) $account_info['ID'],
                'Plan_ID'          => (int) $account_info['Plan_ID'],
                'Listings_remains' => $listing_remains,
                'Standard_remains' => (int) $account_info['plan']['Standard_listings'],
                'Featured_remains' => (int) $account_info['plan']['Featured_listings'],
                'Type'             => 'account',
                'Date'             => 'NOW()',
                'IP'               => $reefless->getClientIpAddress(),
            );

            if ($account_info['plan']['Limit'] > 0) {
                $count_used = $insert['Count_used'] = 1;
            }

            if ($account_info['plan']['Advanced_mode']) {
                if ($ad_type == 'standard') {
                    $standard_remains--;
                    $insert['Standard_remains'] = $standard_remains;
                }
                if ($ad_type == 'featured') {
                    $featured_remains--;
                    $insert['Featured_remains'] = $featured_remains;
                }
            }

            $rlDb->insert($insert, 'listing_packages');
        }

        // Update usage details in session
        $sess_plan = &$_SESSION['account']['plan'];
        $sess_plan['Count_used'] = $count_used;
        $sess_plan['Listings_remains'] = $listing_remains;
        $sess_plan['Standard_remains'] = $standard_remains;
        $sess_plan['Featured_remains'] = $featured_remains;
    }

    /**
     * Fix plans availablity depending on Logged In user data
     *
     * @param  array $plans - Plans to fix
     */
    public function fixAvailability(&$account_info)
    {
        $account_info['plan']['standard_disabled'] = false;
        $account_info['plan']['featured_disabled'] = false;

        if (empty($account_info['plan']['Standard_remains'])
            && $account_info['plan']['Standard_listings'] != 0
        ) {
            $account_info['plan']['standard_disabled'] = true;
        }

        if (empty($account_info['plan']['Featured_remains'])
            && $account_info['plan']['Featured_listings'] != 0
        ) {
            $account_info['plan']['featured_disabled'] = true;
        }
    }
}
