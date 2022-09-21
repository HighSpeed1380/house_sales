<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLPAYMENT.CLASS.PHP
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

class rlPayment extends reefless
{
    /**
     * Payment URL steps
     */
    const POST_URL = 'post';
    const CHECKOUT_URL = 'checkout';
    const SUCCESS_URL = 'success';
    const FAIL_URL = 'fail';

    /**
     * Payment options
     *
     * @var array
     */
    protected $payment_data;

    /**
     * Recurring option of current payment
     *
     * @var bool
     */
    protected $recurring;

    /**
     * System transaction ID
     *
     * @var int
     */
    protected $transaction_id;

    /**
     * If needs to load checkout page
     *
     * @var bool
     */
    public $gateways_page = false;

    /**
     * Required payment options
     *
     * @var array
     */
    protected $required_option = array('service', 'total', 'item_id', 'account_id');

    /**
     * Gateway details
     *
     * @var array
     */
    public $gateway_info;

    /**
     * System notification URL
     *
     * @var string
     */
    protected $notify_url;

    /**
     * Breadcrumbs to previous page
     *
     * @var array
     */
    protected $bread_crumbs;

    /**
     * All payment gateways
     *
     * @var array
     */
    public $gateways;

    /**
     * Referer
     *
     * @var string
     */
    protected $referer;

    /**
     * Display payment form
     *
     * @var bool
     */
    protected $is_form;

    /**
     * Payment form file
     *
     * @var string
     */
    protected $form;

    /**
     * Class constructor
     */
    public function __construct()
    {
        // set payment form
        $this->setForm('blocks' . RL_DS . 'credit_card_payment.tpl');

        if (!isset($GLOBALS['rlActions'])) {
            $this->loadClass('Actions');
        }
        if (!empty($_SESSION['complete_payment'])) {
            $this->payment_data = $_SESSION['complete_payment'];
            // set redirect mode
            if (isset($_SESSION['complete_payment']['redirect'])) {
                $this->gateways_page = (bool) $_SESSION['complete_payment']['redirect'];
            }
            if ($_SESSION['complete_payment']['referer']) {
                $this->referer = $_SESSION['complete_payment']['referer'];
            }
        }
        if (isset($_SESSION['transaction_id'])) {
            $this->transaction_id = $_SESSION['transaction_id'];
        }
        if (isset($this->payment_data['gateway'])) {
            $this->setGateway($this->payment_data['gateway']);
        }
        if ($_SESSION['payment_service_breadcumbs'] && count($_SESSION['payment_service_breadcumbs'])) {
            $this->bread_crumbs = $_SESSION['payment_service_breadcumbs'];
        }
        if ($_SESSION['complete_payment']['recurring']) {
            $this->enableRecurring();
        }

        // set callback URL
        $url = RL_URL_HOME;
        if ($_SERVER['HTTPS'] == 'on') {
            $url = str_replace('http://', 'https://', $url);
        }
        $url .= $GLOBALS['config']['mod_rewrite']
        ? $GLOBALS['pages']['payment'] . '/' . self::POST_URL . '.html'
        : '?page=' . $GLOBALS['pages']['payment'] . '&rlVareables=' . self::POST_URL;
        $this->setNotifyURL($url);

        // get gateways
        $this->getGatewaysAll();
    }

    /**
     * Initialize payment process
     *
     * @param array $errors
     * @return null
     */
    public function init(&$errors)
    {
        foreach ($this->required_option as $key => $value) {
            if (!isset($this->payment_data[$value])) {
                $errors[] = str_replace(
                    '{option}',
                    $GLOBALS['lang']['payment_option_' . $value],
                    $GLOBALS['lang']['required_payment_option_error']
                );
            }
        }

        if (count($this->gateways) <= 0) {
            $errors[] = $GLOBALS['lang']['not_available_payment_gateways'];
        }
        if (!$errors) {
            $this->clearTransactionID();
            $this->createTransaction();

            $GLOBALS['rlHook']->load('phpAfterCreatePaymentTxn');

            if ($this->isRedirect()) {
                $redirect_url = SEO_BASE;
                $redirect_url .= $GLOBALS['config']['mod_rewrite']
                ? $GLOBALS['pages']['payment'] . '/' . self::CHECKOUT_URL . '.html'
                : '?page=' . $GLOBALS['pages']['payment'] . '&rlVareables=' . self::CHECKOUT_URL;

                $this->redirect(false, $redirect_url);
                exit;
            }
        }
    }

    /**
     * Get payment an option
     *
     * @param  string $key
     * @return string
     */
    public function getOption($key = '')
    {
        if (isset($this->payment_data[$key])) {
            return $this->payment_data[$key];
        }
    }

    /**
     * Set payment option
     *
     * @param string $key
     * @param string $value
     */
    public function setOption($key = '', $value = '')
    {
        if ($key && $value) {
            $this->payment_data[$key] = $_SESSION['complete_payment'][$key] = $value;
        }
    }

    /**
     * Check option
     *
     * @param  string $key
     * @return boolean
     */
    public function hasOption($key = '')
    {
        if (isset($this->payment_data[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Clear current payment request
     */
    public function clear()
    {
        $this->payment_data = false;
        $this->bread_crumbs = array();
        $this->recurring = false;
        $this->gateways_page = false;
        unset($_SESSION['complete_payment'], $_SESSION['payment_service_breadcumbs']);

        /**
         * @since 4.6.0
         */
        $GLOBALS['rlHook']->load('paymentClear', $this);
    }

    /**
     * Clear value of transaction ID
     */
    public function clearTransactionID()
    {
        if ($this->getTransactionID()) {
            $transaction = $this->getTransaction();

            if ($transaction['Status'] == 'paid' || $transaction['Item_ID'] != $this->getOption('item_id')) {
                unset($_SESSION['transaction_id']);
                $this->transaction_id = '';
            }
        }
    }

    /**
     * Create transaction
     *
     * @return boolean
     */
    public function createTransaction()
    {
        if (!$this->isPrepare(false)) {
            return false;
        }
        if (!$this->getTransactionID()) {
            if ($transaction = $this->isExistsTransaction()) {
                $this->transaction_id = $_SESSION['transaction_id'] = (int) $transaction['ID'];
                return true;
            }
            $insert = array(
                'Service' => $this->getOption('service'),
                'Account_ID' => $this->getOption('account_id'),
                'Item_ID' => $this->getOption('item_id'),
                'Plan_ID' => $this->getOption('plan_id'),
                'Total' => $this->getOption('total'),
                'Date' => 'NOW()',
                'Item_name' => $this->getOption('item_name'),
                'Plan_key' => $this->getOption('plan_key') ? $this->getOption('plan_key') : '',
            );

            if ($this->hasOption('gateway')) {
                $insert['gateway'] = $this->getOption('gateway');
            }
            if ($GLOBALS['rlActions']->insertOne($insert, 'transactions')) {
                $this->transaction_id = $_SESSION['transaction_id'] = $this->insertID();
                return true;
            }
        } else {
            $update = array(
                'fields' => array(
                    'Plan_ID' => $this->getOption('plan_id'),
                    'Total' => $this->getOption('total'),
                    'Plan_key' => $this->getOption('plan_key') ? $this->getOption('plan_key') : '',
                    'Item_name' => $this->getOption('item_name'),
                ),
                'where' => array('ID' => $this->getTransactionID()),
            );
            if ($GLOBALS['rlActions']->updateOne($update, 'transactions')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if exists transaction
     */
    public function isExistsTransaction()
    {
        $sql = "SELECT * FROM `{db_prefix}transactions` ";
        $sql .= "WHERE `Service` = '" . $this->getOption('service') . "' ";
        $sql .= "AND `Account_ID` = " . $this->getOption('account_id');
        $sql .= " AND `Item_ID` = " . $this->getOption('item_id') . " AND `Status` = 'unpaid'";
        $transaction = $this->getRow($sql);

        if ($transaction) {
            return $transaction;
        }
        return false;
    }

    /**
     * Update transaction status and set up transaction ID of gateway
     *
     * @param  integer $id
     * @param  string  $txn_id
     * @param  string  $status
     * @return bool
     */
    public function updateTransaction($id = 0, $txn_id = '', $status = '', $total = 0)
    {
        if (!$id) {
            return false;
        }
        $id = (int) $id;
        $sql = "UPDATE `{db_prefix}transactions` ";
        $sql .= "SET `Total` = '{$total}', `Status` = '{$status}' " . ($txn_id ? ", `Txn_ID` = '{$txn_id}' " : "") . " ";
        $sql .= "WHERE `ID` = {$id} LIMIT 1";
        return $this->query($sql);
    }

    /**
     * Get current transaction ID
     *
     * @return integer
     */
    public function getTransactionID()
    {
        if ($this->transaction_id) {
            return (int) $this->transaction_id;
        }

        return false;
    }

    /**
     * Set transaction ID
     *
     * @param mixed $transaction_id
     */
    public function setTransactionID($transaction_id = false)
    {
        if ($transaction_id) {
            $this->transaction_id = $transaction_id;
        }
    }

    /**
     * Get gateway
     *
     * @return string
     */
    public function getGateway()
    {
        return $this->payment_data['gateway'];
    }

    /**
     * Set gateway
     *
     * @param mixed $gateway
     */
    public function setGateway($gateway)
    {
        $this->payment_data['gateway'] = $_SESSION['complete_payment']['gateway'] = $gateway;
        $this->setGatewayToTransaction();
    }

    /**
     * Add gateway to transaction
     */
    public function setGatewayToTransaction()
    {
        $id = intval($this->getTransactionID());
        $sql = "UPDATE `{db_prefix}transactions` ";
        $sql .= "SET `Gateway` = '" . $this->getGateway() . "' WHERE `ID` = {$id} LIMIT 1";
        return $this->query($sql);
    }

    /**
     * Get gateway details
     *
     * @return data
     */
    public function getGatewayDetails()
    {
        if ($this->getGateway()) {
            $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Key` = '{$this->getGateway()}' LIMIT 1";
            $this->gateway_info = $this->getRow($sql);

            return $this->gateway_info;
        }

        return;
    }

    /**
     * The method checks if the query is ready
     *
     * @param  bool $post
     * @return bool
     */
    public function isPrepare($post = true)
    {
        if ($post) {
            if ($this->payment_data && ($_POST['form'] == 'checkout' || $_POST['step'] == 'checkout')) {
                return true;
            }
        } else {
            if ($this->payment_data) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build payment options to encoded string
     *
     * @param  bool $include_urls
     * @return string
     */
    public function buildItemData($include_urls = true)
    {
        if ($this->isPrepare(false)) {
            $crypted_price = crypt(sprintf("%.2f", $this->getOption('total')), $GLOBALS['config']['paypal_secret_word']);
            $params = '';
            if ($this->getOption('params')) {
                $params = $this->getOption('params');

                if (is_array($params)) {
                    $params = implode(',', $params);
                }
            }
            $data = $this->getOption('plan_id') . '|' .
            $this->getOption('item_id') . '|' .
            $this->getOption('account_id') . '|' .
            $crypted_price . '|' .
            $this->getOption('callback_class') . '|' .
            $this->getOption('callback_method') . '|' .
            ($include_urls ? ($this->getOption('cancel_url') ? $this->getOption('cancel_url') : $this->getDefaultFailURL()) : '') . '|' .
            ($include_urls ? ($this->getOption('success_url') ? $this->getOption('success_url') : $this->getDefaultSuccessURL()) : '') . '|' .
            RL_LANG_CODE . '|' .
            $this->getOption('plugin') . '|' .
            $this->getTransactionID() . '|' .
                ($this->isRecurring() ? $this->getOption('subscription_id') : $this->isRecurring()) . '|' .
                $params;

            $data = base64_encode($data);
            $this->setOption('item_details', $data);

            return $data;
        }

        return false;
    }

    /**
     * Get callback URL (payment notification URL)
     *
     * @return string
     */
    public function getNotifyURL()
    {
        return $this->notify_url;
    }

    /**
     * Set callback URL
     *
     * @param string $url
     */
    public function setNotifyURL($url)
    {
        $this->notify_url = $url;
    }

    /**
     * Get default fail URL
     *
     * @return string
     */
    public function getDefaultFailURL()
    {
        $fail_url = SEO_BASE;
        $fail_url .= $GLOBALS['config']['mod_rewrite']
        ? $GLOBALS['pages']['payment'] . '/' . self::FAIL_URL . '.html'
        : '?page=' . $GLOBALS['pages']['payment'] . '&rlVareables=' . self::FAIL_URL;

        return $fail_url;
    }

    /**
     * Get default success URL
     *
     * @return string
     */
    public function getDefaultSuccessURL()
    {
        $success_url = SEO_BASE;
        $success_url .= $GLOBALS['config']['mod_rewrite']
        ? $GLOBALS['pages']['payment'] . '/' . self::SUCCESS_URL . '.html'
        : '?page=' . $GLOBALS['pages']['payment'] . '&rlVareables=' . self::SUCCESS_URL;

        return $success_url;
    }

    /**
     * Get payment gateways
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $recurring
     */
    public function gateways($aParams = array())
    {
        $sql = "SELECT * FROM `{db_prefix}payment_gateways` ";
        $sql .= "WHERE `Status` = 'active' ";

        if ($aParams['recurring'] || $GLOBALS['rlPayment']->isRecurring()) {
            $sql .= "AND `Recurring` = '1' ";
        }

        $GLOBALS['rlHook']->load('phpGetPaymentGatewaysWhere', $sql);

        $sql .= "ORDER BY `Position` ASC";
        $gateways = $GLOBALS['rlDb']->getAll($sql);
        $content = '';

        $GLOBALS['rlHook']->load('phpGetPaymentGateways', $gateways, $content);

        $pre_payment_url = SEO_BASE;
        $pre_payment_url .= $GLOBALS['config']['mod_rewrite'] ? $GLOBALS['pages']['payment'] . '/' . self::CHECKOUT_URL . '.html' : '?page=' . $GLOBALS['pages']['payment'] . '&rlVareables=' . self::CHECKOUT_URL;

        if ($GLOBALS['rlPayment']->isRedirect() && $gateways) {
            $content .= '<form id="form-checkout" method="post" action="' . $pre_payment_url . '">';
        }
        if ($gateways) {
            $gateways = $GLOBALS['rlLang']->replaceLangKeys($gateways, 'payment_gateways', array('name'));
            $GLOBALS['rlSmarty']->assign('name', $GLOBALS['lang']['payment_gateways']);

            $content .= $GLOBALS['rlSmarty']->fetch('blocks' . RL_DS . 'fieldset_header.tpl', null, null, false);
            $content .= '<ul id="payment_gateways">';
            foreach ($gateways as $key => $gateway) {
                $GLOBALS['rlHook']->load('phpGetPaymentGatewaysItem', $gateway, $content);
                if (!isset($gateway['ready'])) {
                    $checked = $_POST['gateway'] == $gateway['Key'] || (!$_POST['gateway'] && $gateway['Default']) ? 'checked="checked"' : '';
                    $content .= '<li' . ($checked ? ' class="active"' : '') . ' data-form-type="' . $gateway['Form_type'] . '">';

                    if ($gateway['Plugin']) {
                        $content .= '<img alt="' . $gateway['name'] . '" title="' . $gateway['name'] . '" src="' . RL_PLUGINS_URL . $gateway['Key'] . '/static/' . $gateway['Key'] . '.png" />';
                        $content .= '<p><input ' . $checked . ' type="radio" name="gateway" value="' . $gateway['Key'] . '" /></p>';
                    } else {
                        $content .= '<img alt="' . $gateway['name'] . '" title="' . $gateway['name'] . '" src="' . RL_LIBS_URL . 'payment/' . $gateway['Key'] . '/' . $gateway['Key'] . '.png" />';
                        $content .= '<p><input ' . $checked . ' type="radio" name="gateway" value="' . $gateway['Key'] . '" /></p>';
                    }
                }
                $content .= '</li>';
                $checked = '';
            }

            $content .= '</ul>';
            $content .= $GLOBALS['rlSmarty']->fetch('blocks' . RL_DS . 'fieldset_footer.tpl', null, null, false);

            $content .= '<script class="fl-js-dynamic">$(document).ready(function(){ flynax.paymentGateway(); });</script>';
        } else {
            $content .= '<div class="text-notice">' . $GLOBALS['lang']['no_payment_gateways'] . '</div>';
        }
        // credit card form
        $content .= '<div id="checkout-form-container" class="hide">';
        $content .= '<div id="default-form">' . $GLOBALS['rlSmarty']->fetch('blocks' . RL_DS . 'credit_card_payment.tpl', null, null, false) . '</div>';
        $content .= '<div id="custom-form"></div>';
        $content .= '</div>';

        $GLOBALS['rlHook']->load('phpGetPaymentGatewaysAfter', $content);

        if ($GLOBALS['rlPayment']->isForm()) {
            $content .= $GLOBALS['rlSmarty']->fetch($GLOBALS['rlPayment']->getForm(), null, null, false);
        }

        if ($GLOBALS['rlPayment']->isRedirect() && $gateways) {
            $content .= '<div class="form-buttons no-top-padding">';
            $content .= '<input id="btn-checkout" type="submit" value="' . $GLOBALS['lang']['checkout'] . '" />';
            if ($GLOBALS['rlPayment']->isForm() && $GLOBALS['rlPayment']->getGateway()) {
                $content .= '<a class="close red margin" href="' . $GLOBALS['rlPayment']->getReferer() . '">' . $GLOBALS['lang']['cancel'] . '</a>';
            }
            $content .= '</div>';
            $content .= '</form>';
        }

        return $content;
    }

    /**
     * Generate transaction ID
     *
     * @param  string $txn_tpl
     * @return string
     */
    public function generateTransactionID($txn_tpl = 'FLTXN-********')
    {
        $txn_length = substr_count($txn_tpl, '*');

        $number = $this->getLastNumberTransaction();
        $number++;
        $number_length = strlen($number);
        $txn_length = $txn_length - $number_length;

        if ($txn_length > 0) {
            $txn_stars = str_repeat('0', $txn_length);
        }

        $mask = str_replace("*", "", $txn_tpl);
        $txn = $mask . $txn_stars . $number;

        return $txn;
    }

    /**
     * Get last number transaction
     */
    protected function getLastNumberTransaction()
    {
        $sql = "SELECT `Txn_ID` FROM `{db_prefix}transactions` ORDER BY `Date` DESC LIMIT 1";
        $transaction = $this->getRow($sql);
        $number = 0;

        if ($transaction) {
            $number = explode("-", $transaction['Txn_ID']);
            $number = preg_replace('/\D/', '', $number[1]);
            $number = (int) $number;
        }

        return $number;
    }

    /**
     * Check if define transaction ID
     *
     * @param  string $transaction_id
     * @return bool
     */
    public function isTransactionExists($transaction_id = '')
    {
        if (!$transaction_id) {
            return false;
        }

        $transaction_id_db = $this->getOne("Txn_ID", "`Txn_ID` = '{$transaction_id}'", 'transactions');

        if ($transaction_id == $transaction_id_db) {
            return true;
        }

        return false;
    }

    /**
     * Get transaction details
     *
     * @param  int $transaction_id
     * @return []
     */
    public function getTransaction($transaction_id = 0)
    {
        $transaction_id = intval($transaction_id ? $transaction_id : $this->getTransactionID());

        if ($transaction_id) {
            $sql = "SELECT * FROM `{db_prefix}transactions` WHERE `ID` = " . $transaction_id . " LIMIT 1";
            $transaction_info = $this->getRow($sql);

            if ($transaction_info) {
                return $transaction_info;
            }
        }

        return array();
    }

    /**
     * Set redirect to gateways page
     *
     * @param bool $mod
     */
    public function setRedirect($mod = true)
    {
        $this->gateways_page = $_SESSION['complete_payment']['redirect'] = (bool) $mod;
    }

    /**
     * Check redirect status
     *
     * @return bool
     */
    public function isRedirect()
    {
        return (bool) $this->gateways_page;
    }

    /**
     * Send payment notification email
     *
     * @param string $transaction_id
     *
     */
    public function sendNotificationAfterPayment($transaction_id = '')
    {
        if (!$transaction_id) {
            return;
        }
        $this->loadClass('Mail');
        $this->loadClass('Account');
        $transaction_info = $this->getTransaction($transaction_id);

        if ($transaction_info) {
            // total
            $total = $GLOBALS['config']['system_currency_position'] == 'before'
            ? $GLOBALS['config']['system_currency'] . $transaction_info['Total']
            : $transaction_info['Total'] . ' ' . $GLOBALS['config']['system_currency'];

            // send user notification
            $account_info = $GLOBALS['rlAccount']->getProfile((int) $transaction_info['Account_ID']);
            $account_name = $account_info['Full_name'];

            $search = array('{name}', '{gateway}', '{txn}', '{item}', '{price}', '{date}');
            $replace = array(
                $account_name,
                $GLOBALS['lang']['payment_gateways+name+' . $transaction_info['Gateway']],
                $transaction_info['Txn_ID'],
                $transaction_info['Item_name'],
                $total,
                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            );

            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('payment_accepted', $account_info['Lang']);

            $mail_tpl['body'] = str_replace($search, $replace, $mail_tpl['body']);
            $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);

            // send admin notification
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('admin_listing_paid');
            $search = array('{id}', '{name}', '{gateway}', '{txn}', '{item}', '{price}', '{date}');
            $replace = array(
                $transaction_info['Item_ID'],
                $account_name,
                $GLOBALS['lang']['payment_gateways+name+' . $transaction_info['Gateway']],
                $transaction_info['Txn_ID'],
                $transaction_info['Item_name'],
                $total,
                date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            );

            $mail_tpl['body'] = str_replace($search, $replace, $mail_tpl['body']);
            $GLOBALS['rlMail']->send($mail_tpl, $GLOBALS['config']['notifications_email']);
        }

        unset($transaction_info, $mail_tpl);
    }

    /**
     * Complete payment process; update transaction
     *
     * @param array  $data
     * @param string $callback_class
     * @param string $callback_method
     * @param string $plugin
     */
    public function complete($data = array(), $callback_class = '', $callback_method = '', $plugin = false)
    {
        if (!$callback_class && !$callback_method) {
            return;
        }

        try {
            // check if not exists additional options
            if (!isset($data['params'])) {
                $data['params'] = null;
            }
            $this->loadClass(str_replace('rl', '', $callback_class), null, $plugin);
            $GLOBALS[$callback_class]->{$callback_method}((int) $data['item_id'], (int) $data['plan_id'], (int) $data['account_id'], $data['params']);

            $GLOBALS['rlHook']->load('postPaymentComplete', $data);

            $this->updateTransaction($data['txn_id'], $data['txn_gateway'], 'paid', $data['total']);
            $this->sendNotificationAfterPayment($data['txn_id']);
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger(get_class($e) . " thrown within the exception handler. Message: " . $e->getMessage() . " on line " . $e->getLine());
        }
    }

    /**
     * Set bread crumb option
     *
     * @param array $item
     */
    public function setBreadCrumbs($item)
    {
        $exists = false;
        foreach ($this->bread_crumbs as $bcKey => $bcValue) {
            if ($item['path'] == $bcValue['path']) {
                $exists = true;
                break;
            }
        }
        if (is_array($item) && !$exists) {
            $this->bread_crumbs[] = $_SESSION['payment_service_breadcumbs'][] = $item;
        }
    }

    /**
     * Get bread crumbs option
     *
     * @return []
     */
    public function getBreadCrumbs()
    {
        return $this->bread_crumbs;
    }

    /**
     * Enable recurring option
     *
     * @param bool $status
     */
    public function enableRecurring()
    {
        $this->recurring = $_SESSION['complete_payment']['recurring'] = true;
    }

    /**
     * Disable recurring option
     *
     * @param bool $status
     */
    public function disableRecurring()
    {
        unset($_SESSION['complete_payment']['recurring']);
        $this->recurring = false;
    }

    /**
     * Check if set up recurring mod
     *
     * @return bool
     */
    public function isRecurring()
    {
        return (bool) $this->recurring;
    }

    /**
     * Get all payment gateways
     *
     * @return []
     */
    public function getGatewaysAll()
    {
        if (!$this->gateways) {
            $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Status` = 'active'";
            $gateways = $this->getAll($sql);

            if ($gateways) {
                foreach ($gateways as $gKey => $gValue) {
                    $this->gateways[$gValue['Key']] = $gValue;
                }
            }
        }

        return $this->gateways;
    }

    /**
     * Check if exists payment gateways with activated recurring option
     *
     * @return bool
     */
    public function hasRecurringGateways()
    {
        $response = false;

        foreach ($this->gateways as $gKey => $gValue) {
            if ($gValue['Recurring']) {
                $response = true;
                break;
            }
        }

        return $response;
    }

    /**
     * Run payment process
     *
     * @param array $errors
     * @param bool  $exit
     */
    public function checkout(&$errors, $exit = false)
    {
        $this->loadClass('PaymentFactory');

        if ($_POST['gateway'] || $this->getGateway()) {
            $GLOBALS['rlHook']->load('preCheckoutPayment');

            $gateway = $_POST['gateway'] ? $_POST['gateway'] : $this->getGateway();

            if ($_POST['gateway'] || !$this->getGateway()) {
                $this->setGateway($gateway);
            }
            $gateway_info = $this->getGatewayDetails();
            $rlGateway = self::getInstanceGateway($gateway, $gateway_info['Plugin']);

            if (method_exists($rlGateway, 'isConfigured')) {
                if (!$rlGateway->isConfigured()) {
                    $errors[] = str_replace('{gateway}', $gateway_info['name'], $GLOBALS['lang']['gateway_not_configured']);
                }
            }
            if (!$errors) {
                $rlGateway->call();
                if (method_exists($rlGateway, 'getErrors')) {
                    if ($rlGateway->getErrors()) {
                        $errors = array_merge((array) $errors, (array) $rlGateway->getErrors());
                    }
                    return;
                }
                if ($exit) {
                    exit;
                }
            }
        } else {
            if ($_POST && !$_POST['gateway'] && !$this->getGateway()) {
                $errors[] = $GLOBALS['rlLang']->getSystem('notice_payment_gateway_does_not_chose');
            }
        }
    }

    /**
     * Check if display form
     */
    public function isForm()
    {
        return $this->is_form;
    }

    /**
     * Enable credit card and billing details form
     */
    public function enableForm()
    {
        $this->is_form = true;
    }

    /**
     * Disable credit card and billing details form
     */
    public function disableForm()
    {
        $this->is_form = false;
    }

    /**
     * Reset payment gateways
     */
    public function reset()
    {
        $this->setGateway(null);
        $this->disableForm();
        // unset braed crumbs item
        foreach ($this->bread_crumbs as $bcKey => $bcValue) {
            if ($bcValue['path'] == $GLOBALS['pages']['payment'] . '/' . self::CHECKOUT_URL) {
                unset($this->bread_crumbs[$bcKey], $_SESSION['payment_service_breadcumbs'][$bcKey]);
            }
        }
    }

    /**
     * Set http referer
     *
     * @param string $referer
     */
    public function setReferer($referer = '')
    {
        $this->referer = $_SESSION['complete_payment']['referer'] = $referer;
    }

    /**
     * Get http referer
     *
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set payment form (tpl file)
     *
     * @param string $name
     */
    public function setForm($name = '')
    {
        $this->form = $name;
    }

    /**
     * Get payment form (name tpl file)
     *
     * @return string
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Create instance of gateway object
     *
     * @param  string $gateway
     * @param  string $plugin
     * @return object
     */
    public static function getInstanceGateway($gateway = '', $plugin = '')
    {
        if (!$gateway) {
            return new stdClass();
        }
        $className = $plugin ? ucfirst($gateway) . 'Gateway' : ucfirst($gateway);
        $GLOBALS['reefless']->loadClass($className, null, $plugin);
        return $GLOBALS['rl' . $className];
    }

    /**
     * Load payment onsite form
     *
     * @param string $gateway
     * @param string $form
     * @return html
     */
    public function loadPaymentForm($gateway = '', $form = 'form.tpl')
    {
        if (!$gateway) {
            return;
        }
        $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Key` = '{$gateway}' LIMIT 1";
        $gateway_info = $this->getRow($sql);

        /**
         * @since 4.6.0
         */
        $GLOBALS['rlHook']->load('loadPaymentForm', $gateway_info, $form);

        if ($gateway_info) {
            if ($gateway_info['Plugin']) {
                $form = RL_PLUGINS . $gateway_info['Plugin'] . RL_DS . $form;
            } else {
                $form = 'blocks/' . $gateway_info['Key'] . '.tpl';
            }
            return $GLOBALS['rlSmarty']->fetch($form, null, null, false);
        }
        return;
    }
}
