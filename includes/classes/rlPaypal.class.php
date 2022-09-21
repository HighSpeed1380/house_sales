<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLPAYPAL.CLASS.PHP
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

class rlPaypal extends rlGateway
{
    /**
     * API host
     *
     * @var string
     */
    protected $api_host;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->setTestMode($GLOBALS['config']['paypal_sandbox'] ? true : false);
        $this->api_host = 'https://' . ($this->isTestMode() ? 'www.sandbox' : 'www') . '.paypal.com/cgi-bin/webscr';
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $config;

        $GLOBALS['reefless']->loadClass('Subscription');

        if ($rlPayment->isRecurring()) {
            $subscription_current = $GLOBALS['rlSubscription']->getActiveSubscription($rlPayment->getOption('item_id'), $rlPayment->getOption('service'));

            if (!$subscription_current) {
                $insert = array(
                    'Service'    => $rlPayment->getOption('service'),
                    'Account_ID' => $rlPayment->getOption('account_id'),
                    'Item_ID'    => $rlPayment->getOption('item_id'),
                    'Plan_ID'    => $rlPayment->getOption('plan_id'),
                    'Total'      => $rlPayment->getOption('total'),
                    'Gateway_ID' => (int) $rlPayment->gateways['paypal']['ID'],
                    'Item_name'  => $rlPayment->getOption('item_name'),
                    'Count'      => 1,
                );

                $action = $GLOBALS['rlActions']->insertOne($insert, 'subscriptions');
                $rlPayment->setOption('subscription_id', $GLOBALS['rlDb']->insertID());
            } else {
                if (!$rlPayment->getOption('subscription_id')) {
                    $rlPayment->setOption('subscription_id', $subscription_current['ID']);
                }
            }
        }
        $subscription_plan = $GLOBALS['rlSubscription']->getPlan($rlPayment->getOption('service'), $rlPayment->getOption('plan_id'));

        $this->setOption('business', $config['paypal_account_email']);
        $this->setOption('currency_code', $config['system_currency_code']);
        $this->setOption('item_number', $rlPayment->getTransactionID());
        $this->setOption('custom', str_replace(' ', '+', $rlPayment->buildItemData(false)));

        if ($rlPayment->isRecurring() && $subscription_plan['Period']) {
            $this->setOption('cmd', '_xclick-subscriptions');
            $this->setOption('a3', $rlPayment->getOption('total'));
            $this->setOption('p3', (int) $subscription_plan['Period_total']);
            $this->setOption('t3', $this->getPeriodSubscription($subscription_plan['Period']));
            $this->setOption('src', 1);
            $this->setOption('sra', 1);
            $this->setOption('no_note', 1);
            if (RL_LANG_CODE != 'en') {
                $this->setOption('lc', RL_LANG_CODE);
            }
        } else {
            $this->setOption('cmd', '_xclick');
            $this->setOption('amount', $rlPayment->getOption('total'));
        }

        $this->setOption('rm', 2);
        $this->setOption('no_shipping', 1);
        $this->setOption('item_name', $rlPayment->getOption('item_name'));
        $this->setOption('return', $rlPayment->getOption('success_url') ? $rlPayment->getOption('success_url') : $rlPayment->getDefaultSuccessURL());
        $this->setOption('cancel_return', $rlPayment->getOption('cancel_url') ? $rlPayment->getOption('cancel_url') : $rlPayment->getDefaultFailURL());
        $url_divider = strpos($rlPayment->getNotifyURL(), '?') > 0 ? '&' : '?';
        $this->setOption('notify_url', $rlPayment->getNotifyURL() . $url_divider . 'gateway=paypal');
        $this->setOption('charset', 'utf-8');
        $this->setOption('image_url', RL_TPL_BASE . 'img/logo.png');

        $this->buildPage();
    }

    /**
     * Complete payment process
     */
    public function callBack()
    {
        global $rlValid;

        // save response to log
        if ($GLOBALS['config']['paypal_sandbox']) {
            $file = fopen(RL_TMP . 'response.log', 'a');
            if ($file) {
                $line = "\n\n" . date('Y.m.d H:i:s') . ":\n";
                fwrite($file, $line);
                foreach ($_REQUEST as $p_key => $p_val) {
                    $line = "{$_SERVER['REQUEST_METHOD']}: {$p_key} => {$p_val}\n";
                    fwrite($file, $line);
                }
            }
        }
        if (isset($_REQUEST['custom'])) {
            $errors = $response_status = false;

            if (extension_loaded('curl')) {
                $response_status = $this->checkResponseCURL();
            } else {
                $response_status = $this->checkResponse();
            }

            if ($response_status && $_REQUEST['txn_id']) {
                $txn_id = $rlValid->xSql($_REQUEST['item_number']);
                $txn_gateway = $rlValid->xSql($_REQUEST['txn_id']);
                $payment_status = $_REQUEST['payment_status'];
                $mc_gross = $_REQUEST['mc_gross'];
                $payment_gross = $_REQUEST['payment_gross'];
                $total = !empty($payment_gross) ? $payment_gross : $mc_gross;

                if (!in_array(trim(strtolower($payment_status)), array('completed', 'pending'))) {
                    $this->rlDebug->logger("PayPal: Exit since payment status is not Completed or Pending");
                    $errors = true;
                }

                $items = $this->explodeItems($_REQUEST['custom']);
                $subscription_id = $items[11];

                $response = array(
                    'plan_id'     => $items[0],
                    'item_id'     => $items[1],
                    'account_id'  => $items[2],
                    'total'       => $total,
                    'txn_id'      => $txn_id,
                    'txn_gateway' => $txn_gateway,
                    'params'      => $items[12],
                );
                if (!$errors) {
                    if ($subscription_id) {
                        $GLOBALS['reefless']->loadClass('Subscription');
                        $subscr_id = $rlValid->xSql($_REQUEST['subscr_id']);
                        $receiver_id = $rlValid->xSql($_REQUEST['receiver_id']);
                        $subscription_info = $GLOBALS['rlSubscription']->getSubscription($subscription_id);
                        if ($subscription_info) {
                            $update = array(
                                'fields' => array(
                                    'Txn_ID'          => $txn_gateway,
                                    'Count'           => $subscription_info['Count'] + 1,
                                    'Status'          => 'active',
                                    'Subscription_ID' => $subscr_id,
                                    'Customer_ID'     => $receiver_id,
                                    'Date'            => 'NOW()',

                                ),
                                'where'           => array('ID' => $subscription_id),
                            );
                            $GLOBALS['rlActions']->updateOne($update, 'subscriptions');
                        }
                    }
                    $GLOBALS['rlPayment']->complete($response, $items[4], $items[5], $items[9] ? $items[9] : false);
                }
            } else {
                $GLOBALS['rlDebug']->logger("PayPal: the response invalid");
            }
        }
    }

    /**
     * Validate response from paypal service
     *
     * @return boolean
     */
    public function checkResponse()
    {
        header('HTTP/1.1 200 OK');

        $req = 'cmd=_notify-validate';

        foreach ($_POST as $key => $value) {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }

        $header = "POST /cgi-bin/webscr HTTP/1.1\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Host: www" . ($this->isTestMode() ? '.sandbox' : '') . ".paypal.com\r\n";
        $header .= "Connection: close\r\n\r\n";

        $fp = fsockopen('tls://www' . ($this->isTestMode() ? '.sandbox' : '') . '.paypal.com', 443, $errno, $errstr, 30);

        if ($fp) {
            fputs($fp, $header . $req);

            while (!feof($fp)) {
                $res .= fgets($fp, 1024);
            }
            fclose($fp);

            // Process paypal response
            $arr = explode("\r\n\r\n", $res);
            $arr[1] = trim(preg_replace('/\d/', '', $arr[1]));
            if (strcmp($arr[1], 'VERIFIED') == 0 || (strcmp($arr[1], 'INVALID') == 0 && $this->isTestMode())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate response from paypal service via cURL
     *
     * @return boolean
     */
    public function checkResponseCURL()
    {
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();

        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        $req = 'cmd=_notify-validate';
        foreach ($myPost as $key => $value) {
            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req   .= "&$key=$value";
        }

        $ch = curl_init($this->api_host);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // for SSL
        //curl_setopt($ch, CURLOPT_SSLVERSION, 1);   // for NSS
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

        $res = curl_exec($ch);

        if (curl_errno($ch) != 0) {
            $this->rlDebug->logger("PayPal: Can't connect to PayPal to validate IPN message: " . curl_error($ch));
        }
        curl_close($ch);

        if (strcmp($res, "VERIFIED") == 0 || (strcmp($res, 'INVALID') == 0 && $this->isTestMode())) {
            return true;
        }

        return false;
    }

    /**
     * Get subscription period
     *
     * @param  string $period
     * @return string
     */
    public function getPeriodSubscription($period = false)
    {
        if (!$period) {
            return;
        }

        switch ($period) {
            case 'day':
                $response = 'D';
                break;

            case 'week':
                $response = 'W';
                break;

            case 'month':
                $response = 'M';
                break;

            case 'year':
                $response = 'Y';
                break;
        }
        return $response;
    }

    /**
     * Cancel subscription
     *
     * @param array $subscription_info
     * @return string
     */
    public function cancelSubscription(&$subscription_info)
    {
        if (!$subscription_info) {
            return false;
        }
        $redirect_url = $this->api_host . '?cmd=_subscr-find&alias=' . $subscription_info['Customer_ID'];
        return $redirect_url;
    }

    /**
     * Check if paypal configured
     *
     * @return boolean
     */
    public function isConfigured()
    {
        if ($GLOBALS['config']['paypal_account_email']) {
            return true;
        }
        return false;
    }
}
