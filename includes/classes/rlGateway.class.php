<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLGATEWAY.CLASS.PHP
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

abstract class rlGateway
{
    /**
     * Payment options
     *
     * @var array
     */
    protected $request_data;

    /**
     * Errors of payment gateway
     *
     * @var array
     */
    protected $errors;

    /**
     * Transaction ID
     *
     * @var string
     */
    protected $transaction_id;

    /**
     * Test mode
     *
     * @var bool
     */
    protected $test_mode;

    /*
     *  Start payment process
     */
    abstract public function call();

    /**
     * Complete payment process
     */
    abstract public function callBack();

    /**
     * Check settings of payment gateway
     */
    abstract public function isConfigured();

    /**
     * Check if has any errors in payment process
     *
     * @return bool
     */
    public function hasErrors()
    {
        if ($this->errors) {
            return true;
        }
        return false;
    }

    /**
     * Get errors
     *
     * @return array;
     */
    public function getErrors()
    {
        if ($this->errors) {
            return $this->errors;
        }
        return false;
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionID()
    {
        return $this->transaction_id;
    }

    /**
     * Set transaction ID
     */
    public function setTransactionID($txn_id = false)
    {
        if ($txn_id) {
            $this->transaction_id = $txn_id;
            return;
        }
        $this->transaction_id = $_SESSION['gateway_txn_id'] = rand(100000, 999999);
    }

    /**
     *  Update system transaction
     *
     * @param array $data
     */
    public function updateTransaction(array $data)
    {
        if (!$data) {
            return;
        }
        $update = array();
        foreach ($data as $key => $value) {
            $update['fields'][$key] = $value;
        }

        if ($update) {
            $update['where'] = array(
                'ID' => $GLOBALS['rlPayment']->getTransactionID(),
            );
            $GLOBALS['rlActions']->updateOne($update, 'transactions');
        }
    }

    /**
     * Get system transaction by reference code
     *
     * @param  string $txn_id
     * @param  bool $unpaid
     * @return []
     */
    public function getTransactionByReference($txn_id = false, $unpaid = true)
    {
        if (!$txn_id) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `Txn_ID` = '{$txn_id}' ";
        if ($unpaid) {
            $sql .= "AND `Status` = 'unpaid' ";
        }
        $sql .= "LIMIT 1";

        return $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * Clear payment options
     */
    public function clear()
    {
        $this->request_data = array();
        unset($_SESSION['gateway_txn_id']);
    }

    /**
     * Set dealer credentials on shopping cart checkout page
     *
     * @param  array $dealer
     * @param  string $gateway
     * @return null
     */
    public function setDealerCredentials(&$dealer, $gateway = '')
    {
        if ($dealer) {
            foreach ($dealer as $key => $val) {
                if (substr_count($key, 'shc_' . $gateway) > 0
                    && $key != 'shc_' . $gateway . '_enable'
                ) {
                    $keys[] = $key;
                }
            }
        }
        $error = false;
        foreach ($keys as $key) {
            if (isset($dealer[$key]) && empty($dealer[$key])) {
                $error = true;
            } else {
                $GLOBALS['config'][str_replace('shc_', '', $dealer[$key])] = $dealer[$key];
            }
        }
        if ($error) {
            $GLOBALS['errors'][] = $GLOBALS['lang'][$gateway . '_seller_payment_details_empty'];
        }
    }

    /*
     * Add option to payment request
     */
    public function setOption($key = false, $value = '')
    {
        if (!$key) {
            return;
        }
        $this->request_data[$key] = $value;
    }

    /**
     * Get payment option
     *
     * @param  mixed $key
     * @return string
     */
    public function getOption($key = false)
    {
        if (isset($this->request_data[$key])) {
            return $this->request_data[$key];
        }

        return;
    }

    /**
     * Set test mode of current gateway
     *
     * @param bool $status
     */
    public function setTestMode($status = true)
    {
        $this->test_mode = $status;
    }

    /**
     * Is enable test mode of current gateway
     *
     * @return bool
     */
    public function isTestMode()
    {
        if ($this->test_mode) {
            return true;
        }

        return false;
    }

    /**
     * Build payment form page
     */
    public function buildPage()
    {
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html><head></head><body>' . PHP_EOL;
        $html = '<form name="payment_form"  action="' . $this->api_host . '" method="post">' . PHP_EOL;

        foreach ($this->request_data as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . PHP_EOL;
        }

        $html .= '</form>' . PHP_EOL;
        $html .= '<script type="text/javascript">document.forms[\'payment_form\'].submit();</script></body></html>';

        echo $html;
    }

    /**
     * Explode payment string to options
     *
     * @param  string $item
     * @return []
     */
    public function explodeItems($item = false)
    {
        if ($item) {
            return explode('|', base64_decode(urldecode($item)));
        }
        return array();
    }
}
