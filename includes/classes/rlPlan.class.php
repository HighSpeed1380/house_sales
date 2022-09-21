<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLPLAN.CLASS.PHP
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

class rlPlan extends reefless
{
    /**
     * get plan infor by plan id
     *
     * @param int $types - plan types
     * @param int $account_id - account ID, includes details for account plans using
     *
     * @return array of plan details
     **/
    public function getPlan($id, $account_id = null)
    {
        global $sql/*DEPRECATED*/, $lang, $rlHook;

        $id = (int) $id;
        $account_id = (int) $account_id;

        if (!$id) {
            return false;
        }

        $sql = "SELECT `T1`.*";

        if ($account_id) {
            $sql .= "
                , `T2`.`Listings_remains` AS `Using`, `T2`.`ID` AS `Plan_using_ID`,
                `T3`.`ID` AS `Package_ID`, `T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`Featured_remains`,
                `T4`.`Status` AS `Subscription`, `T4`.`Period`
            ";
        }

        $sql .= "FROM `{db_prefix}listing_plans` AS `T1` ";

        if ($account_id) {
            $sql .= "
                LEFT JOIN `{db_prefix}listing_packages` AS `T2`
                ON `T1`.`ID` = `T2`.`Plan_ID`
                AND `T2`.`Account_ID` = '{$account_id}'
                AND `T2`.`Type` = 'limited' ";

            $sql .= "
                LEFT JOIN `{db_prefix}listing_packages` AS `T3`
                ON `T1`.`ID` = `T3`.`Plan_ID`
                AND `T3`.`Account_ID` = '{$account_id}'
                AND `T3`.`Type` = 'package'
            ";
            $sql .= "
                LEFT JOIN `{db_prefix}subscription_plans` AS `T4`
                ON `T1`.`ID` = `T4`.`Plan_ID`
                AND `T4`.`Service` = 'listing'
                AND `T4`.`Status` = 'active'
            ";
        }

        $sql .= "WHERE `T1`.`ID` = '{$id}' AND `T1`.`Status` = 'active'";

        /**
         * @since 4.5.1 was added params
         **/
        $rlHook->load('phpGetPlanSql', $sql, $account_id, $id);

        $plan = $GLOBALS['rlDb']->getRow($sql);
        $plan['name'] = $lang['listing_plans+name+' . $plan['Key']];

        return $plan;
    }

    /**
     * get all plans
     *
     * @param array $types - plan types
     * @param bool $account_type_filter - filter plans by acount type availability
     *
     * @return array of plans
     **/
    public function getPlans($types = false, $account_type_filter = false)
    {
        global $account_info;

        $sql = "SELECT DISTINCT `T1`.*, `T2`.`Status` AS `Subscription`, `T2`.`Period` ";
        $sql .= "FROM `{db_prefix}listing_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Service` = 'listing' AND `T2`.`Status` = 'active' ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        /* filter by plan type */
        if ($types) {
            $where = "AND (";
            if (is_array($types)) {
                foreach ($types as $type) {
                    $where .= "`Type` =  '{$type}' OR ";
                }
                $where = substr($where, 0, -3);
            } else {
                $where .= " `Type` =  '{$types}' ";
            }
            $where .= ") ";
        }

        /* filter by account type availability */
        if ($account_type_filter) {
            $where .= "AND (FIND_IN_SET('{$account_info['Type']}', `Allow_for`) > 0 OR `Allow_for` = '') ";
        }

        $sql .= $where;
        $sql .= "ORDER BY `Position`";

        $plans = $this->getAll($sql);
        $plans = $GLOBALS['rlLang']->replaceLangKeys($plans, 'listing_plans', array('name', 'des'));

        return $plans;
    }

    /**
     * check parent with enabled subcategories including | recursive method
     *
     * @param int $id - category id
     * @param array - $data
     *
     * @return bool
     **/
    public function detectParentIncludes($id, $data = false)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        /* get parent */
        $parent = $this->getOne('Parent_ID', "`ID` = '{$id}'", 'categories');

        if (!empty($parent)) {
            /* check relations */
            $target = $this->getOne('ID', "FIND_IN_SET('{$parent}', `Category_ID`) > 0  AND `Status` = 'active' AND `Subcategories` = '1'", 'listing_plans');

            if (empty($target)) {
                return $this->detectParentIncludes($parent);
            }

            return $parent;
        } else {
            return false;
        }
    }

    /**
     * get all plans by category
     *
     * @param int $id - category id
     * @param string $account_type - account type
     * @param bool $upgrade - include featured plans and omit packages
     *
     * @return array of plans
     **/
    public function getPlanByCategory($id = false, $account_type = false, $featured = false)
    {
        global $account_info, $rlHook;

        $id = (int) $id;
        if ($id && $additional = $this->detectParentIncludes($id)) {
            $add_where = "OR (FIND_IN_SET('{$additional}', `Category_ID`) > 0 AND `Subcategories` = '1')";
        }

        $where_type = defined('REALM') ? '' : "(FIND_IN_SET('{$account_type}', `T1`.`Allow_for`) > 0 OR `T1`.`Allow_for` = '' ) ";

        $sql = "SELECT DISTINCT `T1`.`ID`, `T1`.`Key`, `T1`.`Type`, `T1`.`Featured`, `T1`.`Advanced_mode`, `T1`.`Standard_listings`, ";

        $rlHook->load('phpGetPlanByCategoryModifyField', $sql, $id, $account_type, $featured); // >= v.4.5.2

        $sql .= "`T1`.`Featured_listings`, `T1`.`Listing_number`, `T1`.`Price`, `T1`.`Cross`, `T1`.`Limit`, `T1`.`Image`, `T1`.`Image_unlim`, ";
        $sql .= "`T1`.`Video`, `T1`.`Video_unlim`, `T1`.`Listing_period`, `T1`.`Plan_period`, `T1`.`Color`, ";
        $sql .= "`T2`.`Listings_remains` AS `Using`, `T2`.`ID` AS `Plan_using_ID`, ";
        $sql .= "`T3`.`ID` AS `Package_ID`, `T3`.`Listings_remains`, `T3`.`Standard_remains`, `T3`.`Featured_remains` ";
        // subscription plan (>= v4.4)
        $sql .= ", `T4`.`Status` AS `Subscription`, `T4`.`Period` ";
        $sql .= "FROM `{db_prefix}listing_plans` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_packages` AS `T2` ON `T1`.`ID` = `T2`.`Plan_ID` AND `T2`.`Account_ID` = '{$account_info['ID']}' AND `T2`.`Type` = 'limited' ";
        $sql .= "LEFT JOIN `{db_prefix}listing_packages` AS `T3` ON `T1`.`ID` = `T3`.`Plan_ID` AND `T3`.`Account_ID` = '{$account_info['ID']}' AND `T3`.`Type` = 'package' ";
        $sql .= "AND (((`T3`.`Standard_remains` > 0 OR `T1`.`Standard_listings` = 0) AND `T1`.`Advanced_mode` = '1' ) OR ((`T3`.`Featured_remains` > 0 OR `T1`.`Featured_listings` = 0) AND `T1`.`Advanced_mode` = '1') OR ((`T3`.`Listings_remains` > 0 OR `T1`.`Listing_number` = 0) AND `T1`.`Advanced_mode` <> '1')) ";
        $sql .= "AND (UNIX_TIMESTAMP(DATE_ADD(`T3`.`Date`, INTERVAL `T1`.`Plan_period` DAY)) > UNIX_TIMESTAMP(NOW()) OR `T1`.`Plan_period` = 0) ";
        // subscription plan (>= v4.4)
        $sql .= "LEFT JOIN `{db_prefix}subscription_plans` AS `T4` ON `T1`.`ID` = `T4`.`Plan_ID` AND `T4`.`Service` = 'listing' AND `T4`.`Status` = 'active' ";

        $rlHook->load('phpGetPlanByCategoryModifyJoin', $sql, $id, $account_type, $featured); // >= v4.5.2

        $sql .= "WHERE `T1`.`Status` = 'active' ";
        if ($id) {
            $sql .= "AND ((FIND_IN_SET('{$id}', `T1`.`Category_ID`) > 0 OR `T1`.`Sticky` = '1') {$add_where}) ";
        }
        if ($account_type) {
            $sql .= "AND {$where_type} ";
        }

        if ($featured) {
            $sql .= "AND `T1`.`Type` = 'featured' ";
        } else {
            $sql .= "AND `T1`.`Type` <> 'featured' ";
        }

        $rlHook->load('phpGetPlanByCategoryModifyWhere', $sql, $id, $account_type, $featured); // >= v4.5.x

        $sql .= "ORDER BY `Position`, `T2`.`ID` ASC ";

        $plans = $this->getAll($sql, 'ID');
        $plans = $GLOBALS['rlLang']->replaceLangKeys($plans, 'listing_plans', array('name', 'des'));

        // Add availablity data
        $this->fixAvailability($plans);

        return $plans;
    }

    /**
     * get step key by step path
     *
     * @param string $path - step path from get
     * @param array $steps - available steps array
     *
     **/
    public function stepByPath($path = false, $steps = false)
    {
        if (!$path || !$steps) {
            return;
        }

        foreach ($steps as $key => $step) {
            if ($step['path'] == $path) {
                return $key;
            }
        }
    }

    /**
     * grant a package plan to a user
     *
     * @param int $account_id - account ID
     * @param int $plan_id - package plan ID
     *
     **/
    public function grantPlan($account_id = false, $plan_id = false)
    {
        global $rlActions;

        $plan_id = (int) $plan_id;
        if (!$account_id || !$plan_id) {
            return;
        }

        $package_info = $this->fetch(array('Listing_number', 'Standard_listings', 'Featured_listings'), array('ID' => $plan_id), null, 1, 'listing_plans', 'row');

        $insert = array(
            'Account_ID'       => $account_id,
            'Plan_ID'          => $plan_id,
            'Listings_remains' => $package_info['Listing_number'],
            'Standard_remains' => $package_info['Standard_listings'],
            'Featured_remains' => $package_info['Featured_listings'],
            'Type'             => 'package',
            'Date'             => 'NOW()',
            'IP'               => $this->getClientIpAddress(),
        );

        $rlActions->insertOne($insert, 'listing_packages');

        return true;
    }

    /**
     * delete plan preparation
     *
     * @package ajax
     *
     * @param int $id - plan id
     *
     **/
    public function ajaxPrepareDeleting($id = false)
    {
        global $_response, $rlSmarty, $delete_details, $lang, $config;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;

        if (!$id) {
            return $_response;
        }

        /* get plan details */
        $plan_details = $this->fetch(array('Key', 'ID', 'Type'), array('ID' => $id), null, 1, 'listing_plans', 'row');
        $plan_details['name'] = $lang['listing_plans+name+' . $plan_details['Key']];
        $rlSmarty->assign_by_ref('plan_details', $plan_details);

        /* check listings */
        $listings = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}listings` WHERE `Plan_ID` = '{$id}' AND `Status` <> 'trash'");
        $delete_total_items = 0;

        $delete_details[] = array(
            'name'  => $lang['listings'],
            'items' => $listings['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;plan_id=' . $id,
        );
        $delete_total_items += $listings['Count'];

        /* check packages in use */
        $packages = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}listing_packages` WHERE `Plan_ID` = '{$id}' AND `Type` = 'package' AND `Listings_remains` > 0");

        $delete_details[] = array(
            'name'  => $lang['purchased_packages'],
            'items' => $packages['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=plans_using&amp;plan_id=' . $id,
        );
        $delete_total_items += $packages['Count'];

        $rlSmarty->assign_by_ref('delete_details', $delete_details);

        if ($delete_total_items) {
            /* get plan for replace list */
            $plans = $this->getPlans($plan_details['Type'] == 'featured' ? 'featured' : array('listing', 'package'));
            $rlSmarty->assign_by_ref('plans', $plans);

            /* open delete block */
            $tpl = 'blocks' . RL_DS . 'delete_preparing_plan.tpl';
            $_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("
                $('input[name=new_account]').rlAutoComplete({add_id: true});
                $('#delete_block').slideDown();
            ");
        } else {
            $phrase = $config['trash'] ? $lang['notice_drop_plan'] : $lang['notice_delete_plan'];
            $_response->script("
                $('#delete_block').slideUp();
                flynax.confirm('{$phrase}', xajax_deletePlan, '{$plan_details['Key']}');
            ");
        }

        return $_response;
    }

    /**
     * delete plan
     *
     * @package ajax
     *
     * @param mixed $key - plan key or array(key, replace)
     * @param string $reason - remove plan reason
     * @param string $replace - new plan ID to replace with
     *
     **/
    public function ajaxDeletePlan($key = false, $reason = false)
    {
        global $_response, $lang, $rlCategories, $rlListings, $rlActions, $rlListingTypes, $config, $pages;

        if (is_array($key)) {
            $replace = $key[1];
            $key = $key[0];
        }

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $replace = (int) $replace;
        $GLOBALS['rlValid']->sql($key);

        $plan_info = $this->fetch(array('ID', 'Type'), array('Key' => $key), null, 1, 'listing_plans', 'row');
        $id = (int) $plan_info['ID'];

        /* set new plan ID for related listings */
        if ($replace) {
            if ($plan_info['Type'] == 'featured') {
                $this->query("UPDATE `{db_prefix}listings` SET `Featured_ID` = '{$replace}' WHERE `Featured_ID` = '{$id}'");
            } else {
                $this->query("UPDATE `{db_prefix}listings` SET `Plan_ID` = '{$replace}' WHERE `Plan_ID` = '{$id}'");
            }
        }
        /* clear plan ID in related listings and deactivate them */
        else {
            if ($plan_info['Type'] == 'featured') {
                $this->query("UPDATE `{db_prefix}listings` SET `Featured_ID` = '' WHERE `Featured_ID` = '{$id}'");
            } else {
                $this->loadClass('Mail');
                $this->loadClass('Account');

                /* listings handler */
                $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` ";
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
                $sql .= "WHERE `T1`.`Plan_ID` = '{$id}'";
                $listings = $this->getAll($sql);

                foreach ($GLOBALS['languages'] as $language) {
                    $mailTplSource[$language['Code']] = $GLOBALS['rlMail']->getEmailTemplate(
                        'listing_plan_removed',
                        $language['Code']
                    );
                }

                foreach ($listings as $listing) {
                    if ($rlListings->isActive($listing['ID'])) {
                        /* decrease counters */
                        $rlCategories->listingsDecrease($listing['Category_ID']);
                        $rlCategories->accountListingsDecrease($listing['Account_ID']);

                        if ($listing['Crossed']) {
                            $crossed_categories = explode(',', $listing['Crossed']);
                            foreach ($crossed_categories as $crossed_category) {
                                $rlCategories->listingsDecrease($crossed_category);
                            }
                        }

                        /* inform listing owner */
                        $account_info = $GLOBALS['rlAccount']->getProfile((int) $listing['Account_ID']);

                        $mail_tpl = $mailTplSource[$account_info['Lang'] ?: RL_LANG_CODE];

                        $listing_title = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);

                        $link = $contact_link = RL_URL_HOME;
                        if ($config['mod_rewrite']) {
                            $link .= $pages[$rlListingTypes->types[$listing['Listing_type']]['My_key']] . '.html';
                            $contact_link .= $pages['contact_us'] . '.html';
                        } else {
                            $link .= '?page=' . $pages[$rlListingTypes->types[$listing['Listing_type']]['My_key']];
                            $contact_link .= '?page=' . $pages['contact_us'];
                        }
                        $link = '<a href="' . $link . '">' . $listing_title . '</a>';

                        $find = array('{name}', '{link}', '{reason}');
                        $replace = array($account_info['Full_name'], $link, $reason);
                        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
                        $mail_tpl['body'] = preg_replace('/(\[(.+)\])/', '<a href="' . $contact_link . '">$2</a>', $mail_tpl['body']);

                        $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
                    }
                }
                $this->query("UPDATE `{db_prefix}listings` SET `Plan_ID` = '' WHERE `Plan_ID` = '{$id}'");
            }
        }

        /* packages handler */
        //$rlActions -> delete( array( 'Plan_ID' => $id ), array('listing_packages'));
        $this->query("DELETE FROM `{db_prefix}listing_packages` WHERE `Plan_ID` = '{$id}'");

        /* set phrase keys to remove/drop */
        $lang_keys = array(
            array('Key' => 'listing_plans+name+' . $key),
            array('Key' => 'listing_plans+des+' . $key),
        );

        // delete subscription plan (>= v4.4)
        $this->query("DELETE FROM `{db_prefix}subscription_plans` WHERE `Plan_ID` = '{$id}' AND `Service` = 'listing'");

        /* delete plan */
        $rlActions->delete(array('Key' => $key), array('listing_plans', 'lang_keys'), null, 1, $key, $lang_keys);
        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            listingPlansGrid.reload();
            printMessage('notice', '{$lang['plan_' . $del_mode]}');
            $('#delete_block').slideUp();
        ");

        return $_response;
    }

    /**
     * Fix plans availablity depending on Logged In user data
     *
     * @param array $plans - Plans to fix
     */
    public function fixAvailability(&$plans)
    {
        foreach ($plans as &$plan) {
            $plan['standard_disabled'] = false;
            $plan['featured_disabled'] = false;
            $plan['plan_disabled'] = false;

            if ($plan['Package_ID']
                && empty($plan['Standard_remains'])
                && $plan['Standard_listings'] != 0
            ) {
                $plan['standard_disabled'] = true;
            }

            if ($plan['Package_ID']
                && empty($plan['Featured_remains'])
                && $plan['Featured_listings'] != 0
            ) {
                $plan['featured_disabled'] = true;
            }

            if (($plan['Limit'] > 0 && $plan['Using'] == 0 && $plan['Using'] != '')
                || ($plan['Standard_disabled'] && $plan['Featured_listings'])
            ) {
                $plan['plan_disabled'] = true;
            }
        }
    }
}
