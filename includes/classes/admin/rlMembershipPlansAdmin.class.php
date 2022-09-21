<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLMEMBERSHIPPLANSADMIN.CLASS.PHP
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

class rlMembershipPlansAdmin extends reefless
{
    /**
     * @var language class object
     **/
    protected $rlLang;

    /**
     * @var actions class object
     **/
    protected $rlActions;

    protected $languages;

    protected $_response;

    protected $plan_id;

    protected $service_id;

    /**
     * class constructor
     *
     */
    public function __construct()
    {
        global $rlLang, $rlActions, $_response;

        if (!$rlActions) {
            $this->loadClass('Actions');
        }
        $this->rlActions = $GLOBALS['rlActions'];
        $this->rlLang = $GLOBALS['rlLang'];
        $this->_response = $_response;
    }

    /**
     * create new membership plan
     *
     * @param array $plan
     * @return boolean
     */
    public function add($plan)
    {
        // get max position
        $position = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}membership_plans`");
        $advanced_mode = $plan['advanced_mode'] && $plan['featured_listing'] ? 1 : 0;

        // write main plan information
        $data = array(
            'Allow_for'         => $plan['account_type'] ? implode(',', $plan['account_type']) : '',
            'Color'             => $plan['color'],
            'Price'             => (double) $plan['price'],
            'Plan_period'       => (int) $plan['plan_period'],
            'Listing_number'    => (int) $plan['listing_number'],
            'Image'             => (int) $plan['images'],
            'Image_unlim'       => (int) $plan['images_unlimited'],
            'Video'             => (int) $plan['video'],
            'Video_unlim'       => (int) $plan['video_unlimited'],
            'Status'            => $plan['status'],
            'Position'          => $position['max'] + 1,
            'Services'          => $plan['services'] ? implode(',', $plan['services']) : '',
            'Featured_listing'  => $plan['featured_listing'] ? 1 : 0,
            'Advanced_mode'     => $advanced_mode,
            'Standard_listings' => $advanced_mode ? (int) $plan['standard_listings'] : 0,
            'Featured_listings' => $advanced_mode ? (int) $plan['featured_listings'] : 0,
            'Cross'             => (int) $plan['cross'],
            'Limit'             => (int) $plan['limit'],
        );

        /**
         * @since 4.6.0 - Added $data option
         */
        $GLOBALS['rlHook']->load('apPhpMembershipPlansBeforeAdd', $data, $plan);

        if ($action = $this->rlActions->insertOne($data, 'membership_plans')) {
            $plan_id = $this->insertID();
            $this->setPlanID($plan_id);

            $f_key = 'ms_plan_' . $plan_id;

            $f_name = $plan['name'];
            $f_description = $plan['description'];

            $GLOBALS['rlHook']->load('apPhpMembershipPlansAfterAdd', $f_key);

            $sql = "UPDATE `{db_prefix}membership_plans` SET `Key` = '{$f_key}' WHERE `ID` = '{$plan_id}' LIMIT 1";
            $this->query($sql);

            // write name's phrases
            foreach ($GLOBALS['languages'] as $key => $value) {
                $lang_keys[] = array(
                    'Code'   => $value['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => 'membership_plans+name+' . $f_key,
                    'Value'  => $f_name[$value['Code']],
                );

                if (!empty($f_description[$value['Code']])) {
                    $lang_keys[] = array(
                        'Code'   => $value['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => 'membership_plans+des+' . $f_key,
                        'Value'  => $f_description[$value['Code']],
                    );
                }
            }

            $this->rlActions->insert($lang_keys, 'lang_keys');
            return true;
        }

        return false;
    }

    /**
     * edit membership plan
     *
     * @param array $plan
     * @param string $f_key
     * @return boolean
     */
    public function edit($plan, $f_key)
    {
        $advanced_mode = $plan['advanced_mode'] && $plan['featured_listing'] ? 1 : 0;
        $update_plan = array(
            'fields' => array(
                'Status'            => $plan['status'],
                'Allow_for'         => $plan['account_type'] ? implode(',', $plan['account_type']) : '',
                'Color'             => $plan['color'],
                'Price'             => (double) $plan['price'],
                'Plan_period'       => (int) $plan['plan_period'],
                'Image'             => (int) $plan['images'],
                'Image_unlim'       => (int) $plan['images_unlimited'],
                'Video'             => (int) $plan['video'],
                'Video_unlim'       => (int) $plan['video_unlimited'],
                'Services'          => $plan['services'] ? implode(',', $plan['services']) : '',
                'Featured_listing'  => $plan['featured_listing'] ? 1 : 0,
                'Advanced_mode'     => $advanced_mode,
                'Listing_number'    => (int) $plan['listing_number'],
                'Standard_listings' => $advanced_mode ? (int) $plan['standard_listings'] : 0,
                'Featured_listings' => $advanced_mode ? (int) $plan['featured_listings'] : 0,
                'Cross'             => (int) $plan['cross'],
                'Limit'             => (int) $plan['limit'],
            ),
            'where'  => array('Key' => $f_key),
        );

        /**
         * @since 4.6.0 - Added $update_plan option
         */
        $GLOBALS['rlHook']->load('apPhpMembershipPlansBeforeEdit', $update_plan, $plan);

        if ($action = $this->rlActions->updateOne($update_plan, 'membership_plans')) {
            $this->setPlanID($this->getOne('ID', "`Key` = '{$f_key}'", 'membership_plans'));
            $GLOBALS['rlHook']->load('apPhpMembershipPlansAfterEdit', $f_key);

            foreach ($GLOBALS['languages'] as $key => $value) {
                if ($this->getOne('ID', "`Key` = 'membership_plans+name+{$f_key}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                    // edit names
                    $update_phrases = array(
                        'fields' => array(
                            'Value' => $plan['name'][$value['Code']],
                        ),
                        'where'  => array(
                            'Code' => $value['Code'],
                            'Key'  => 'membership_plans+name+' . $f_key,
                        ),
                    );

                    // update
                    $this->rlActions->updateOne($update_phrases, 'lang_keys');
                } else {
                    // insert names
                    $insert_phrases = array(
                        'Code'   => $value['Code'],
                        'Module' => 'common',
                        'Key'    => 'membership_plans+name+' . $f_key,
                        'Value'  => $plan['name'][$value['Code']],
                    );
                    // insert
                    $this->rlActions->insertOne($insert_phrases, 'lang_keys');
                }

                // edit description's values
                if ($this->getOne('ID', "`Key` = 'membership_plans+des+{$f_key}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                    $update_phrases = array(
                        'where'  => array(
                            'Code' => $value['Code'],
                            'Key'  => 'membership_plans+des+' . $f_key,
                        ),
                        'fields' => array(
                            'Value' => $plan['description'][$value['Code']],
                        ),
                    );
                    $this->rlActions->updateOne($update_phrases, 'lang_keys');
                } else {
                    $insert_phrases = array(
                        'Code'   => $value['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => 'membership_plans+des+' . $f_key,
                        'Value'  => $plan['description'][$value['Code']],
                    );

                    $this->rlActions->insertOne($insert_phrases, 'lang_keys');
                }
            }
            return true;
        }

        return false;
    }

    /**
     * ajax delete membership plan
     *
     * @param string $key
     * @param string $reason
     * @return xajaxResponse
     */
    public function ajaxDeletePlan($key = false, $reason = false)
    {
        global $pages;

        if (is_array($key)) {
            $replace = $key[1];
            $key = $key[0];
        }

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $this->_response->redirect($redirect_url);
        }

        $replace = (int) $replace;
        $GLOBALS['rlValid']->sql($key);

        $plan_info = $this->fetch('*', array('Key' => $key), null, 1, 'membership_plans', 'row');
        $id = (int) $plan_info['ID'];

        // set new plan ID for related accounts and listings
        if ($replace) {
            $this->query("UPDATE `{db_prefix}accounts` SET `Plan_ID` = '{$replace}' WHERE `Plan_ID` = '{$id}'");
            $this->query("UPDATE `{db_prefix}listings` SET `Plan_ID` = '{$replace}' WHERE `Plan_ID` = '{$id}'");
        }
        /* clear plan ID in related listings and deactivate them */
        else {
            $this->loadClass('Mail');
            $this->loadClass('Account');
            $this->loadClass('Listings');
            $this->loadClass('Categories');

            $accounts = $this->getAll("SELECT * FROM `{db_prefix}accounts` WHERE `Plan_ID` = '{$id}'");
            $mail_tpl_source = $GLOBALS['rlMail']->getEmailTemplate('membership_plan_removed');

            foreach ($accounts as $aKey => $aValue) {
                $aValue['Full_name'] = $aValue['First_name'] && $aValue['Last_name'] ? $aValue['First_name'] . ' ' . $aValue['Last_name'] : $aValue['Username'];

                // listings handler by plan and account
                $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` ";
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
                $sql .= "WHERE `Account_ID` = '{$aValue['ID']}' AND `T1`.`Plan_ID` = '{$id}'";
                $listings = $this->getAll($sql);

                foreach ($listings as $listing) {
                    if ($GLOBALS['rlListings']->isActive($listing['ID'])) {
                        /* decrease counters */
                        $GLOBALS['rlCategories']->listingsDecrease($listing['Category_ID']);
                        if ($listing['Crossed']) {
                            $crossed_categories = explode(',', $listing['Crossed']);
                            foreach ($crossed_categories as $crossed_category) {
                                $GLOBALS['rlCategories']->listingsDecrease($crossed_category);
                            }
                        }
                    }
                }

                $mail_tpl = $mail_tpl_source;

                $contact_link = RL_URL_HOME;
                $contact_link .= $GLOBALS['config']['mod_rewrite']
                    ? $pages['contact_us'] . '.html'
                    : '?page=' . $pages['contact_us'];

                $find = array('{name}', '{reason}');
                $replace = array($aValue['Full_name'], $reason);
                $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                $mail_tpl['body'] = preg_replace('/(\[(.+)\])/', '<a href="' . $contact_link . '">$2</a>', $mail_tpl['body']);

                $GLOBALS['rlMail']->send($mail_tpl, $aValue['Mail']);

                $this->query("UPDATE `{db_prefix}listings` SET `Plan_ID` = '' WHERE `Account_ID` = '{$aValue['ID']}' AND `Plan_ID` = '{$id}'");
            }
            $this->query("UPDATE `{db_prefix}accounts` SET `Plan_ID` = '' WHERE `Plan_ID` = '{$id}'");
        }

        /* packages handler */
        //$rlActions -> delete( array( 'Plan_ID' => $id ), array('listing_packages'));
        $this->query("DELETE FROM `{db_prefix}listing_packages` WHERE `Plan_ID` = '{$id}'");

        /* set phrase keys to remove/drop */
        $lang_keys = array(
            array('Key' => 'membership_plans+name+' . $key),
            array('Key' => 'membership_plans+des+' . $key),
        );

        // delete subscription plan (>= v4.4)
        $this->query("DELETE FROM `{db_prefix}subscription_plans` WHERE `Plan_ID` = '{$id}' AND `Service` = 'listing'");

        /* delete plan */
        $GLOBALS['rlActions']->delete(array('Key' => $key), array('membership_plans', 'lang_keys'), null, 1, $key, $lang_keys);
        $del_mode = $GLOBALS['rlActions']->action;

        $this->_response->script("
            MembershipPlansGrid.reload();
            printMessage('notice', '{$GLOBALS['lang']['plan_' . $del_mode]}');
            $('#delete_block').slideUp();
        ");

        return $this->_response;
    }

    /**
     * prepare plan to delete
     *
     * @return xajaxResponse
     */
    public function ajaxPrepareDeleting($id = false)
    {
        global $rlSmarty, $rlHook, $delete_details, $lang, $config;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $this->_response->redirect($redirect_url);
        }

        $id = (int) $id;

        if (!$id) {
            return $this->_response;
        }

        // get plan details
        $plan_details = $this->fetch('*', array('ID' => $id), null, 1, 'membership_plans', 'row');
        $plan_details['name'] = $lang['membership_plans+name+' . $plan_details['Key']];
        $rlSmarty->assign_by_ref('plan_details', $plan_details);

        // check accounts
        $accounts = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}accounts` WHERE `Plan_ID` = '{$id}' AND `Status` <> 'trash'");
        $delete_total_items = 0;

        $delete_details[] = array(
            'name'  => $lang['accounts'],
            'items' => $accounts['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=accounts&amp;plan_id=' . $id,
        );
        $delete_total_items += $accounts['Count'];

        // check packages in use
        $packages = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}listing_packages` WHERE `Plan_ID` = '{$id}' AND `Type` = 'account' AND `Listings_remains` > 0");

        $delete_details[] = array(
            'name'  => $lang['purchased_packages'],
            'items' => $packages['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=plans_using&amp;plan_id=' . $id,
        );
        $delete_total_items += $packages['Count'];

        $rlSmarty->assign_by_ref('delete_details', $delete_details);

        if ($delete_total_items) {
            // get plan for replace list
            $plans = $this->getPlans(false);
            $rlSmarty->assign_by_ref('plans', $plans);

            // open delete block
            $tpl = 'blocks' . RL_DS . 'delete_preparing_membership_plan.tpl';
            $this->_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $this->_response->script("
                $('input[name=new_account]').rlAutoComplete({add_id: true});
                $('#delete_block').slideDown();
            ");
        } else {
            $phrase = $config['trash'] ? $lang['notice_drop_membership_plan'] : $lang['notice_delete_membership_plan'];
            $this->_response->script("
                $('#delete_block').slideUp();
                flynax.confirm('{$phrase}', xajax_deletePlan, '{$plan_details['Key']}');
            ");
        }

        return $this->_response;
    }

    /**
     * delete membership service
     *
     * @param integer $id
     * @return xajaxResponse
     */
    public function ajaxDeleteService($id = false)
    {
        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $this->_response->redirect($redirect_url);
        }

        $id = (int) $id;

        if (!$id) {
            return $this->_response;
        }

        $id = (int) $id;

        $service = $this->getRow("SELECT * FROM `{db_prefix}membership_services` WHERE `ID` = '{$id}' LIMIT 1");

        if ($service) {
            /* set phrase keys to remove/drop */
            $lang_keys = array(
                array('Key' => 'membership_services+name+' . $service['Key']),
                array('Key' => 'membership_services+des+' . $service['Key']),
            );

            $GLOBALS['rlActions']->delete(array('ID' => $id), array('membership_services', 'lang_keys'), null, 1, $id, $lang_keys);

            $del_mode = $GLOBALS['rlActions']->action;

            $this->_response->script("
                membershipServicesGrid.reload();
                printMessage('notice', '{$GLOBALS['lang']['admin_' . $del_mode]}');
            ");
        }

        return $this->_response;
    }

    /**
     * simulate post data of plan
     *
     * @param array $plan_info
     */
    public function simulatePost(&$plan_info)
    {
        $this->setPlanID($plan_info['ID']);

        $_POST['key'] = $plan_info['Key'];
        $_POST['color'] = $plan_info['Color'];
        $_POST['price'] = $plan_info['Price'];
        $_POST['plan_period'] = $plan_info['Plan_period'];
        $_POST['images'] = $plan_info['Image'];
        $_POST['images_unlimited'] = $plan_info['Image_unlim'];
        $_POST['video'] = $plan_info['Video'];
        $_POST['video_unlimited'] = $plan_info['Video_unlim'];
        $_POST['listing_number'] = $plan_info['Listing_number'];
        $_POST['status'] = $plan_info['Status'];
        $_POST['cross'] = $plan_info['Cross'];
        $_POST['account_type'] = explode(',', $plan_info['Allow_for']);
        $_POST['services'] = explode(',', $plan_info['Services']);
        $_POST['featured_listing'] = $plan_info['Featured_listing'];
        $_POST['advanced_mode'] = $plan_info['Advanced_mode'];
        $_POST['standard_listings'] = $plan_info['Standard_listings'];
        $_POST['featured_listings'] = $plan_info['Featured_listings'];
        $_POST['limit'] = $plan_info['Limit'];

        // get names
        $names = $this->fetch(array('Code', 'Value'), array('Key' => 'membership_plans+name+' . $plan_info['Key']), "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($names as $pKey => $pVal) {
            $_POST['name'][$names[$pKey]['Code']] = $names[$pKey]['Value'];
        }

        // get description
        $descriptions = $this->fetch(array('Code', 'Value'), array('Key' => 'membership_plans+des+' . $plan_info['Key']), "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($descriptions as $pKey => $pVal) {
            $_POST['description'][$descriptions[$pKey]['Code']] = $descriptions[$pKey]['Value'];
        }

        // get subscription options
        $subscription_info = $GLOBALS['rlSubscription']->getPlan('membership', $plan_info['ID']);
        if ($subscription_info) {
            $_POST['subscription'] = $subscription_info['Status'] == 'active' ? 1 : 0;
            $_POST['period'] = $subscription_info['Period'];
            $_POST['period_total'] = $subscription_info['Period_total'];
            foreach ($subscription_info as $sKey => $sValue) {
                if (substr_count($sKey, 'sop') > 0) {
                    $_POST['sop'][$sKey] = $sValue;
                }
            }
        }
    }

    /**
     * get plan ID
     *
     * @return integer
     */
    public function getPlanID()
    {
        return $this->plan_id;
    }

    /**
     * set plan ID
     *
     * @param integer $id
     */
    public function setPlanID($id)
    {
        $this->plan_id = (int) $id;
    }

    /**
     * get service ID
     *
     * @return integer
     */
    public function getServiceID()
    {
        return $this->service_id;
    }

    /**
     * set service ID
     *
     * @param integer $id
     */
    public function setServiceID($id)
    {
        $this->service_id = (int) $id;
    }

    /**
     * simulate post data of service
     *
     * @param array $service_info
     */
    public function simulateServicePost(&$service_info)
    {
        $this->setServiceID($service_info['ID']);

        $_POST['key'] = $service_info['Key'];
        $_POST['position'] = $service_info['Position'];

        // get names
        $names = $this->fetch(array('Code', 'Value'), array('Key' => 'membership_services+name+' . $service_info['Key']), "AND `Status` <> 'trash'", null, 'lang_keys');
        foreach ($names as $pKey => $pVal) {
            $_POST['name'][$names[$pKey]['Code']] = $names[$pKey]['Value'];
        }
    }

    /**
     * create a new membeship service
     *
     * @param array $service
     * @return boolean
     */
    public function addService($service = array())
    {
        // get max position
        $position = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}membership_services`");

        // write main plan information
        $data = array(
            'Status'   => $service['status'],
            'Position' => $position['max'] + 1,
        );

        /**
         * @since 4.6.0 - Added $data option
         */
        $GLOBALS['rlHook']->load('apPhpMembershipServicesBeforeAdd', $data, $service);

        if ($action = $this->rlActions->insertOne($data, 'membership_services')) {
            $service_id = $this->insertID();
            $this->setServiceID($service_id);

            $f_key = $service['key'] ? $service['key'] : 'ms_service_' . $service_id;

            $f_name = $service['name'];

            $GLOBALS['rlHook']->load('apPhpMembershipServiceAfterAdd');

            $sql = "UPDATE `{db_prefix}membership_services` SET `Key` = '{$f_key}' WHERE `ID` = '{$service_id}' LIMIT 1";
            $this->query($sql);

            // write name's phrases
            foreach ($GLOBALS['languages'] as $key => $value) {
                $lang_keys[] = array(
                    'Code'   => $value['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => 'membership_services+name+' . $f_key,
                    'Value'  => $f_name[$value['Code']],
                );
            }

            $this->rlActions->insert($lang_keys, 'lang_keys');
            return true;
        }
        return false;
    }

    /**
     * Edit membeship service
     *
     * @param  array   $service
     * @return boolean
     */
    public function editService($service = array())
    {
        if (!$service) {
            return false;
        }

        $f_key = $GLOBALS['rlValid']->xSql($_POST['key']);

        $update_service = array(
            'fields' => array(
                'Status' => $service['status'],
            ),
            'where'  => array('Key' => $f_key),
        );

        /**
         * @since 4.6.0 - Added $update_service option
         */
        $GLOBALS['rlHook']->load('apPhpMembershipServiceBeforeEdit', $update_service, $service);

        if ($action = $this->rlActions->updateOne($update_service, 'membership_services')) {
            $GLOBALS['rlHook']->load('apPhpMembershipServiceAfterEdit');

            foreach ($GLOBALS['languages'] as $key => $value) {
                if ($this->getOne('ID', "`Key` = 'membership_services+name+{$f_key}' AND `Code` = '{$value['Code']}'", 'lang_keys')) {
                    // edit names
                    $update_phrases = array(
                        'fields' => array(
                            'Value' => $service['name'][$value['Code']],
                        ),
                        'where'  => array(
                            'Code' => $value['Code'],
                            'Key'  => 'membership_services+name+' . $f_key,
                        ),
                    );

                    // update
                    $this->rlActions->updateOne($update_phrases, 'lang_keys');
                } else {
                    // insert names
                    $insert_phrases = array(
                        'Code'   => $value['Code'],
                        'Module' => 'common',
                        'Key'    => 'membership_services+name+' . $f_key,
                        'Value'  => $service['name'][$value['Code']],
                    );
                    // insert
                    $this->rlActions->insertOne($insert_phrases, 'lang_keys');
                }
            }

            return true;
        }

        return false;
    }

    /**
     * get membership services
     * @return array
     *
     */
    public function getServices()
    {
        $services = $this->fetch('*', array('Status' => 'active'), null, '', 'membership_services');

        if ($services) {
            $services = $this->rlLang->replaceLangKeys($services, 'membership_services', array('name', 'des'));
        }
        return $services;
    }

    /**
     * get plans by account type
     *
     * @param string $type
     *
     * @return array
     */
    public function getPlans($services = true)
    {
        $sql = "SELECT DISTINCT `T1`.* ";
        $sql .= "FROM `{db_prefix}membership_plans` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position`";

        $plans = $this->getAll($sql, 'ID');

        if ($plans) {
            foreach ($plans as $key => $value) {
                if ($value['Services'] && $services) {
                    $service_ids = explode(',', $value['Services']);

                    $sql = "SELECT * FROM `{db_prefix}membership_services` WHERE `ID` = '" . implode("' OR `ID` = '", $service_ids) . "' ORDER BY `Position` ASC";
                    $plans[$key]['Services'] = $this->getAll($sql, 'Key');
                }
            }
            $plans = $this->rlLang->replaceLangKeys($plans, 'membership_plans', array('name', 'des'));
        }

        return $plans;
    }

    /**
     * check if active listings by membership plans
     *
     */
    public function checkActiveListings()
    {
        return array(
            'count'   => $this->getActiveListings(),
            'message' => str_replace('{count}', $this->getActiveListings(), $GLOBALS['lang']['have_membership_active_listings']),
        );
    }

    /**
     * assign listings to listing plan
     *
     * @param integer $plan_id
     */
    public function assignListingToListingPlan($plan_id = false)
    {
        if (!$plan_id) {
            return;
        }
        $plan_id = (int) $plan_id;
        $sql = "UPDATE `{db_prefix}listings` SET `Plan_ID` = '{$plan_id}', `Pay_date` = NOW(), `Plan_type` = 'listing' WHERE `Plan_type` = 'account' AND `Status` <> 'trash'";
        $this->query($sql);
    }

    /**
     * get active listings by membership plans

     * @return integer
     *
     */
    public function getActiveListings()
    {
        $sql = "SELECT COUNT(`ID`) AS `calc` FROM `{db_prefix}listings` WHERE `Plan_type` = 'account' LIMIT 1";
        $info = $this->getRow($sql);

        return (int) $info['calc'];
    }

    /**
     * get plan details by ID
     *
     * @param integer $id
     *
     * @return array
     */
    public function getPlan($id = false)
    {
        $sql = "SELECT * FROM `{db_prefix}membership_plans` WHERE `ID` = '{$id}' LIMIT 1";
        $plan = $this->getRow($sql);

        if ($plan) {
            $plan['name'] = $GLOBALS['lang']['membership_plans+name+' . $plan['Key']];
        }

        return $plan;
    }

    /**
     * check if account has free listing cells
     *
     * @param integer $plan_id
     * @return xajaxResponse
     */
    public function ajaxCheckMemebershipPlan($account_id = false)
    {
        if (!$account_id) {
            return $this->_response;
        }
        $account_id = $GLOBALS['rlValid']->xSql($account_id);
        $field = is_int($account_id) ? 'ID' : 'Username';

        $sql = "SELECT `T1`.*, `T2`.`Listing_number`, `T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`Featured_remains` ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_packages` AS `T3` ON `T1`.`ID` = `T3`.`Account_ID` AND `T1`.`Plan_ID` = `T3`.`Plan_ID` ";
        $sql .= "WHERE `T1`.`{$field}` = '{$account_id}' ";
        $sql .= "LIMIT 1";

        $account = $this->getRow($sql);

        if ($account) {
            if (empty($account['Plan_ID']) || $account['Listings_remains'] <= 0) {
                $this->_response->script("
                        printMessage('error', '{$GLOBALS['lang']['user_used_listing_number_limit']}');
                        $('input[name=\"decrease_listing_number\"]').prop('checked', false);
                    ");
            }
        }

        return $this->_response;
    }

    /**
     * recalculate listings number in membership plan of account
     *
     * @param array $account_info
     */
    public function handleAddListing(&$account_info, &$plan_info, $listing_type = '')
    {
        if (!$account_info || !$plan_info) {
            return;
        }

        if ($plan_info['Using']) {
            $plan_using_update = array(
                'fields' => array(
                    'Account_ID'       => $account_info['ID'],
                    'Listings_remains' => $plan_info['Listings_remains'] > 0 ? $plan_info['Listings_remains'] - 1 : 0,
                    'Date'             => 'NOW()',
                    'IP'               => $this->getClientIpAddress(),
                ),
                'where'  => array(
                    'ID' => $plan_info['Using'],
                ),
            );

            if ($plan_info['Advanced_mode']) {
                if ($listing_type == 'standard') {
                    $plan_using_update['fields']['Standard_remains'] = $plan_info['Standard_remains'] > 0 ? $plan_info['Standard_remains'] - 1 : 0;
                }
                if ($listing_type == 'featured') {
                    $plan_using_update['fields']['Featured_remains'] = $plan_info['Featured_remains'] > 0 ? $plan_info['Featured_remains'] - 1 : 0;
                }
            }

            $GLOBALS['rlActions']->updateOne($plan_using_update, 'listing_packages');
        } else {
            $plan_using_insert = array(
                'Account_ID'       => (int) $account_info['ID'],
                'Plan_ID'          => (int) $account_info['Plan_ID'],
                'Listings_remains' => $plan_info['Listing_number'] > 0 ? $plan_info['Listing_number'] - 1 : 0,
                'Standard_remains' => (int) $plan_info['Standard_listings'],
                'Featured_remains' => (int) $plan_info['Featured_listings'],
                'Type'             => 'account',
                'Date'             => 'NOW()',
                'IP'               => $this->getClientIpAddress(),
            );
            if ($plan_info['Advanced_mode']) {
                if ($listing_type == 'standard') {
                    $plan_using_insert['Standard_remains'] = $plan_info['Standard_listings'] > 0 ? $plan_info['Standard_listings'] - 1 : 0;
                }
                if ($listing_type == 'featured') {
                    $plan_using_insert['Featured_remains'] = $plan_info['Featured_listings'] > 0 ? $plan_info['Featured_listings'] - 1 : 0;
                }
            }

            $GLOBALS['rlActions']->insertOne($plan_using_insert, 'listing_packages');
        }
    }

    /**
     * get account types which allow add listings
     *
     * @return array
     */
    public function getAccountTypes($field_map = false)
    {
        $sql = "SELECT `T1`.`ID`, `T1`.`Key` ";
        $sql .= "FROM `{db_prefix}account_types` AS `T1` ";
        $sql .= "JOIN `{db_prefix}listing_types` AS `T2` ON FIND_IN_SET(`T2`.`Key`, `T1`.`Abilities`) > 0 ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($field_map) {
            return $this->getAll($sql, $field_map);
        }
        return $this->getAll($sql);
    }

    /**
     * get membership plan details by username or account ID
     *
     * @param mixed $account_id
     * @return data
     */
    public function getAccountPlanInfo($account_id = false)
    {
        if (!$account_id) {
            return array();
        }
        $field = is_int($account_id) ? 'ID' : 'Username';
        $account_id = $GLOBALS['rlValid']->xSql($account_id);

        $sql = "SELECT `T1`.`ID`, `T1`.`Username`, `T2`.*, `T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`ID` AS `Using`, `T3`.`Featured_remains` ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_packages` AS `T3` ON `T1`.`ID` = `T3`.`Account_ID` AND `T1`.`Plan_ID` = `T3`.`Plan_ID` ";
        $sql .= "WHERE `T1`.`{$field}` = '{$account_id}' ";
        $sql .= "LIMIT 1";

        return $this->getRow($sql);
    }

    /**
     * recalculate listings number in membership plan of account
     *
     * @param array $account_info
     */
    public function handleEditListing(&$listing, &$plan_info, $account_id, $new_owner, $listing_type = '')
    {
        if (!$account_id || !$new_owner || !$plan_info) {
            return;
        }

        // recalculate remains listings in current account
        $plan_info_current = $this->getAccountPlanInfo((int) $account_id);
        $plan_update_current = array(
            'fields' => array(
                'Listings_remains' => $plan_info_current['Listings_remains'] + 1,
                'Date'             => 'NOW()',
            ),
            'where'  => array(
                'ID' => $plan_info_current['Using'],
            ),
        );

        if ($plan_info_current['Advanced_mode']) {
            if ($listing['Last_type'] == 'standard') {
                $plan_update_current['fields']['Standard_remains'] = $plan_info_current['Standard_remains'] + 1;
            }
            if ($listing['Last_type'] == 'featured') {
                $plan_update_current['fields']['Featured_remains'] = $plan_info_current['Featured_remains'] + 1;
            }
        }
        $GLOBALS['rlActions']->updateOne($plan_update_current, 'listing_packages');

        // recalculate remains listings in new account
        if ($plan_info['Using']) {
            $plan_using_update = array(
                'fields' => array(
                    'Account_ID'       => $new_owner,
                    'Listings_remains' => $plan_info['Listings_remains'] > 0 ? $plan_info['Listings_remains'] - 1 : 0,
                    'Date'             => 'NOW()',
                    'IP'               => $this->getClientIpAddress(),
                ),
                'where'  => array(
                    'ID' => $plan_info['Using'],
                ),
            );

            if ($plan_info['Advanced_mode']) {
                if ($listing_type == 'standard') {
                    $plan_using_update['fields']['Standard_remains'] = $plan_info['Standard_remains'] > 0 ? $plan_info['Standard_remains'] - 1 : 0;
                }
                if ($listing_type == 'featured') {
                    $plan_using_update['fields']['Featured_remains'] = $plan_info['Featured_remains'] > 0 ? $plan_info['Featured_remains'] - 1 : 0;
                }
            }

            $GLOBALS['rlActions']->updateOne($plan_using_update, 'listing_packages');
        } else {
            $plan_using_insert = array(
                'Account_ID'       => (int) $new_owner,
                'Plan_ID'          => (int) $plan_info['ID'],
                'Listings_remains' => $plan_info['Listing_number'] > 0 ? $plan_info['Listing_number'] - 1 : 0,
                'Standard_remains' => (int) $plan_info['Standard_listings'],
                'Featured_remains' => (int) $plan_info['Featured_listings'],
                'Type'             => 'account',
                'Date'             => 'NOW()',
                'IP'               => $this->getClientIpAddress(),
            );
            if ($plan_info['Advanced_mode']) {
                if ($listing_type == 'standard') {
                    $plan_using_insert['Standard_remains'] = $plan_info['Standard_listings'] > 0 ? $plan_info['Standard_listings'] - 1 : 0;
                }
                if ($listing_type == 'featured') {
                    $plan_using_insert['Featured_remains'] = $plan_info['Featured_listings'] > 0 ? $plan_info['Featured_listings'] - 1 : 0;
                }
            }

            $GLOBALS['rlActions']->insertOne($plan_using_insert, 'listing_packages');
        }
    }
}
