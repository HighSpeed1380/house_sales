<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLSUBSCRIPTION.CLASS.PHP
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

class rlSubscription extends reefless
{
    protected $account_info;

    /**
     * constructor
     *
     */
    public function __construct()
    {
        global $account_info;

        $this->loadClass('Actions');
        $this->account_info = &$account_info;
    }

    /**
     * get subscription plan
     *
     * @param mixed $service
     * @param integer $plan_id
     * @return data
     */
    public function getPlan($service = false, $plan_id = false)
    {
        if (!$service || !$plan_id) {
            return false;
        }

        if ($service == 'package' || $service == 'featured') {
            $service = 'listing';
        }

        $sql = "SELECT * FROM `{db_prefix}subscription_plans` WHERE `Service` = '{$service}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
        $plan_info = $this->getRow($sql);

        return $plan_info;
    }

    /**
     * get subscription
     *
     * @param integer $subscription_id
     * @return data
     */
    public function getSubscription($subscription_id = false)
    {
        if (!$subscription_id) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `ID` = '{$subscription_id}' LIMIT 1";
        return $this->getRow($sql);
    }

    /**
     * Cancel subscription
     *
     * @param string $service
     * @param int $itemID
     * @param int $subscriptionID
     * @param bool $page
     * @return array
     */
    public function ajaxCancelSubscription($service = '', $itemID = 0, $subscriptionID = 0, $page = false)
    {
        global $rlHook, $rlDb, $lang, $reefless;

        if (!$service || !$itemID || !$subscriptionID) {
            return [];
        }

        $out = [];

        $reefless->loadClass('PaymentFactory');

        if (!is_object('rlGateway')) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }

        // get subscription
        $subscriptionInfo = $this->getSubscription($subscriptionID);

        // get subscription plan
        $planInfo = $this->getPlan($service, $subscriptionInfo['Plan_ID']);

        if ($planInfo) {
            $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `ID` = '{$subscriptionInfo['Gateway_ID']}' LIMIT 1";
            $gatewayInfo = $rlDb->getRow($sql);

            /**
             * @since 4.8.1 - Added $subscriptionInfo, $planInfo, $gatewayInfo
             */
            $rlHook->load('phpPreCancelSubscription', $subscriptionInfo, $planInfo, $gatewayInfo);

            $className = $gatewayInfo['Key'] . ($gatewayInfo['Plugin'] ? 'Gateway' : '');
            $rlGateway = $GLOBALS['rlPaymentFactory']->create($className, $gatewayInfo['Plugin']);
            $response = $rlGateway->cancelSubscription($subscriptionInfo, $planInfo);

            /**
             * @since 4.8.1 $response
             */
            $rlHook->load('phpPostCancelSubscription', $response);

            if ($response) {
                $sql = "UPDATE `{db_prefix}subscriptions` SET `Status` = 'canceled' WHERE `ID` = '{$subscriptionID}' LIMIT 1";
                $rlDb->query($sql);

                if (filter_var($response, FILTER_VALIDATE_URL)) {
                    $out['redirect'] = $response;
                }

                $out['status'] = 'OK';
                $out['message'] = $lang['cancel_subscription_success'];

                if ($page == 'upgrade_listing') {
                    $separator = $GLOBALS['config']['mod_rewrite'] ? '?' : '&';
                    $out['url'] = $reefless->getPageUrl($page) . $separator . 'id=' . $itemID;
                    $out['upgradeListing'] = true;
                }

                /**
                 * @since 4.8.1 $out
                 */
                $rlHook->load('phpCancelSubscription', $out);
            } else {
                $out['status'] = 'ERROR';
                $out['message'] = $lang['cancel_subscription_error'];
            }
        }

        return $out;
    }

    /**
     * get subscription options to plan
     *
     * @return array
     *
     */
    public function getPlanOptions()
    {
        $response = array();

        $sql = "SHOW COLUMNS FROM `{db_prefix}subscription_plans` WHERE `Field` RLIKE 'sop_(.*)$'";
        $fields = $this->getAll($sql);

        if ($fields) {
            foreach ($fields as $fKey => $fVal) {
                if ($fVal['Field']) {
                    $type = $this->getFieldType($fVal['Type'], $fVal['Field']);
                    $response[] = array(
                        'Key' => $fVal['Field'],
                        'Type' => is_array($type) ? $type[0] : $type,
                        'name' => $GLOBALS['lang']['subscription_plans+name+' . strtolower($fVal['Field'])],
                        'values' => is_array($type) ? $type[1] : '',
                    );
                }
            }
        }

        return $response;
    }

    /**
     * save plan options
     *
     * @param mixed $service
     * @param integer $plan_id
     * @param float $total
     */
    public function savePlanOptions($service = false, $plan_id = false, $total = false)
    {
        if ($_POST) {
            $plan_id = (int) $plan_id;
            $service = $GLOBALS['rlValid']->xSql($service);
            $period = $GLOBALS['rlValid']->xSql($_POST['period']);
            $period_total = (int) $_POST['period_total'];

            $sql = "SELECT * FROM `{db_prefix}subscription_plans` WHERE `Service` = '{$service}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
            $plan_info = $this->getRow($sql);

            $data = $_POST['sop'];
            $fields = $this->getPlanOptions();

            if (!empty($plan_info['ID'])) {
                foreach ($fields as $fKey => $fValue) {
                    if (isset($data[$fValue['Key']])) {
                        $update['fields'][$fValue['Key']] = $data[$fValue['Key']];
                    }
                }
                $update['fields']['Status'] = $_POST['subscription'] ? 'active' : 'approval';
                $update['fields']['Total'] = (float) $total;
                $update['fields']['Period'] = $period;
                $update['fields']['Period_total'] = $period_total;

                $update['where'] = array(
                    'ID' => $plan_info['ID'],
                );
                $GLOBALS['rlActions']->updateOne($update, 'subscription_plans');
            } else {
                if ($_POST['subscription']) {
                    $insert = array(
                        'Service' => $service,
                        'Plan_ID' => (int) $plan_id,
                        'Total' => (float) $total,
                        'Period' => $period,
                        'Period_total' => $period_total,
                    );

                    foreach ($fields as $fKey => $fValue) {
                        if (isset($data[$fValue['Key']])) {
                            $insert[$fValue['Key']] = $data[$fValue['Key']];
                        }
                    }
                    $GLOBALS['rlActions']->insertOne($insert, 'subscription_plans');
                }
            }
        }
    }

    /**
     * get field type of subscription option
     *
     * @param string $type
     * @param string $field
     */
    protected function getFieldType($type = false, $field = false)
    {
        if (!$type) {
            return false;
        }

        $option_type = false;

        if (substr_count($type, 'varchar') > 0) {
            $option_type = 'text';
        } elseif (substr_count($type, 'double') > 0 || substr_count($type, 'int') > 0) {
            $option_type = 'numeric';
        } elseif (substr_count($type, 'enum') > 0) {
            $enum = trim(str_replace(array("'", "(", ")", "enum"), "", $type));
            $enum = explode(",", $enum);

            if (count($enum) == 2 && in_array(0, $enum) && in_array(1, $enum)) {
                $option_type = 'bool';
                $enum_list = array();
            } else {
                foreach ($enum as $eKey => $eValue) {
                    $enum_list[] = array(
                        'key' => $eValue,
                        'name' => $GLOBALS['lang'][str_replace('sop_', '', $field) . '_' . $eValue],
                    );
                }
                $option_type = array('select', $enum_list);
            }
        }

        return $option_type;
    }

    /**
     * get subscription details
     *
     * @param mixed $subscription_id
     * @return array
     */
    public function getSubscriptionDetails($subscription_id = false)
    {
        if (!$subscription_id) {
            return false;
        }

        $sql = "SELECT `T1`.*, `T3`.`Key` AS `Gateway`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
        $sql .= "FROM `{db_prefix}subscriptions` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}payment_gateways` AS `T3` ON `T1`.`Gateway_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$subscription_id}' ";
        $sql .= "LIMIT 1";

        $subscription_info = $this->getRow($sql);

        if ($subscription_info) {
            switch ($subscription_info['Service']) {
                case 'package':
                case 'listing':
                    $sql = "SELECT * FROM `{db_prefix}listing_plans` WHERE `ID` = '{$subscription_info['Plan_ID']}' LIMIT 1";
                    $plan_info = $this->getRow($sql);

                    if ($plan_info) {
                        $plan_info['name'] = $GLOBALS['lang']['listing_plans+name+' . $plan_info['Key']];
                        $subscription_info['plan'] = $plan_info;
                    }
                    break;
                case 'membership':
                    $sql = "SELECT * FROM `{db_prefix}membership_plans` WHERE `ID` = '{$subscription_info['Plan_ID']}' LIMIT 1";
                    $plan_info = $this->getRow($sql);

                    if ($plan_info) {
                        $plan_info['name'] = $GLOBALS['lang']['membership_plans+name+' . $plan_info['Key']];
                        $subscription_info['plan'] = $plan_info;
                    }
                    break;
            }
            $subscription_info['Service'] = $GLOBALS['lang'][$subscription_info['Service'] == 'listing' || $subscription_info['Service'] == 'package' ? $subscription_info['Service'] . '_plan' : $subscription_info['Service']];
            $subscription_info['Gateway'] = $GLOBALS['lang']['payment_gateways+name+' . $subscription_info['Gateway']];

            $GLOBALS['rlHook']->load('phpSubscriptionDetails');
        }
        return $subscription_info;
    }

    /**
     * get active subscription of specific item
     *
     * @param integer $item_id
     * @param string $service
     * @return boolean
     */
    public function getActiveSubscription($item_id = false, $service = false)
    {
        if (!$item_id || !$service) {
            return false;
        }

        $item_id = (int) $item_id;
        $service = $GLOBALS['rlValid']->xSql($service);

        $sql = "SELECT * FROM `{db_prefix}subscriptions`
                WHERE `Item_ID` = '{$item_id}'
                AND `Account_ID` = '{$this->account_info['ID']}'
                AND `Status` = 'active'
                AND `Service` = '{$service}'
                LIMIT 1";
        $subscription = $this->getRow($sql);

        if (!empty($subscription['ID'])) {
            return $subscription;
        }

        return false;
    }

    /**
     * get the exact service name
     *
     * @param string $service
     */
    public function getService($service = false)
    {
        if (!$service) {
            return false;
        }
        if (in_array($service, array('package', 'featured'))) {
            $service = 'listing';
        }

        return $service;
    }

    /**
     * get plans (for all services)
     *
     */
    public function getAllPlans()
    {
        $plans = array();

        // get listing plans
        $this->loadClass('Plan');
        $l_plans = $GLOBALS['rlPlan']->getPlans(array('listing', 'package', 'featured'));

        if ($l_plans) {
            foreach ($l_plans as $lpKey => $plValue) {
                if ($plValue['Price'] > 0) {
                    $plans['listing'][] = array(
                        'ID' => $plValue['ID'],
                        'name' => $plValue['name'],
                        'Type' => $plValue['Type'],
                        'Price' => $plValue['Price'],
                    );
                }
            }
        }

        $GLOBALS['rlHook']->load('phpSubscriptionGetPlans', $plans);

        return $plans;
    }

    /**
     * Get subscribers by plan
     *
     * @param int $plan_id
     * @param string $service
     * @return array
     */
    public function ajaxGetSubscribersByPlan($plan_id = 0, $service = 'listing')
    {
        global $rlDb, $rlSmarty, $lang;

        if (!$service || !$plan_id) {
            return [
                'status' => 'ERROR',
                'message' => $lang['get_subscribers_empty_params'],
            ];
        }

        $out = [];

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T3`.`Key` AS `Gateway`, ";
        $sql .= "IF(`T2`.`Last_name` <> '' AND `T2`.`First_name` <> '', CONCAT(`T2`.`First_name`, ' ', `T2`.`Last_name`), `T2`.`Username`) AS `Full_name` ";
        $sql .= "FROM `{db_prefix}subscriptions` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}payment_gateways` AS `T3` ON `T1`.`Gateway_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`Plan_ID` = '{$plan_id}' AND `T1`.`Service` = '{$service}'";
        $sql .= "LIMIT 10";

        $subscribers = $rlDb->getAll($sql);

        // get total subscribers
        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $total_subscribers = (int) $calc['calc'];

        if ($subscribers) {
            $rlSmarty->assign('count_subscribers', str_replace('{count}', count($subscribers), $lang['subscribers_by_plan']));
            $rlSmarty->assign('total_subscribers', $total_subscribers);
            $rlSmarty->assign('subscribers', $subscribers);

            $out = [
                'status' => 'OK',
                'content' => $rlSmarty->fetch('blocks/plan_subscribers.tpl', null, null, false),
            ];
        } else {
            $out = [
                'status' => 'OK',
                'content' => $lang['subscribers_by_plan_no'],
            ];
        }

        return $out;
    }

    public function deletePlan($service = false, $plan_id = false)
    {
        if (!$service || $plan_id) {
            return;
        }

        $sql = "SELECT * FROM `{db_prefix}subscription_plans` WHERE `Service` = '{$service}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
        $plan_info = $this->getRow($sql);

        if ($plan_info) {
            $sql = "DELETE FROM `{db_prefix}subscription_plans` WHERE `ID` = '{$plan_info['ID']}' LIMIT 1";
            $this->query($sql);
        }
    }

    /**
     * Check subscription in gateway
     *
     * @since 4.8.1
     *
     * @param int $itemID
     * @return array
     */
    public function ajaxCheckSubscription($itemID = 0)
    {
        global $rlDb, $rlSmarty, $lang;

        if (!$itemID) {
            return $lang['subscription_not_found'];
        }

        $out = '';

        $sql = "SELECT * FROM `{db_prefix}subscriptions` WHERE `ID` = {$itemID}";
        $subscriptionInfo = $rlDb->getRow($sql);

        if ($subscriptionInfo) {
            $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `ID` = '{$subscriptionInfo['Gateway_ID']}'";
            $gatewayInfo = $rlDb->getRow($sql);

            $gatewayClassName = ucfirst($gatewayInfo['Key']) . ($gatewayInfo['Plugin'] ? 'Gateway' : '');
            $gatewayClass = 'rl' . $gatewayClassName;
            $GLOBALS['reefless']->loadClass($gatewayClassName, null, $gatewayInfo['Plugin']);

            $response = $GLOBALS[$gatewayClass]->getSubscriptionDetails($subscriptionInfo['Subscription_ID']);

            $rlSmarty->assign('subscription', $response);

            if (!isset($lang['last_date_update'])) {
                $lang = array_merge($lang, $GLOBALS['rlLang']->getPhrases(RL_LANG_CODE, 'subscriptions'));
            }

            $rlSmarty->assign_by_ref('lang', $lang);

            $out = $rlSmarty->fetch('blocks/subscription_info.tpl', null, null, false);
        } else {
            $out = $lang['subscription_not_found'];
        }

        // Ext popup doesn't support json response
        echo $out;exit;
    }
}
