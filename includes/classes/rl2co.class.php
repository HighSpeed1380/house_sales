<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RL2CO.CLASS.PHP
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

class rl2co extends rlGateway
{
    /**
     * API host
     *
     * @var string
     */
    protected $apiHost;

    /**
     * Auth Session ID
     *
     * @var string
     */
    protected $sessionID = '';

    /**
     * Request index
     *
     * @var int
     */
    protected $index = 1;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->apiHost = 'https://api.2checkout.com/rpc/6.0/';
    }

    /**
     * Authentication
     *
     * @since 4.8.2
     */
    public function auth()
    {
        global $config;

        $merchantCode = $config['2co_id'];
        $key = $config['2co_secret_key'];

        $string = strlen($merchantCode) . $merchantCode . strlen(gmdate('Y-m-d H:i:s')) . gmdate('Y-m-d H:i:s');
        $hash = hash_hmac('md5', $string, $key);

        $jsonRpcRequest = new stdClass();
        $jsonRpcRequest->jsonrpc = '2.0';
        $jsonRpcRequest->method = 'login';
        $jsonRpcRequest->params = array($merchantCode, gmdate('Y-m-d H:i:s'), $hash);
        $jsonRpcRequest->id = $this->index++;

        $this->sessionID = $this->callRPC($jsonRpcRequest, $this->apiHost);
    }

    /**
     * Start payment process
     */
    public function call()
    {
        global $rlPayment, $config, $reefless, $account_info;

        $this->auth();
        $this->setTransactionID();

        if ($config['2co_method'] == 'direct') {
            $this->callDirect();
            return;
        }

        $productCode = $this->getItem();

        if ($productCode) {
            $productCode = $this->getProduct($productCode);

            if (!$productCode) {
                $productCode = $this->addProduct();
                $this->saveItem($productCode);
            }
        } else {
            $productCode = $this->addProduct();
            $this->saveItem($productCode);
        }

        $returnUrl = urlencode($rlPayment->getNotifyURL() . '?gateway=2co');
        $expiration = time() + 3600;

        $data = [
            'return-url' => $returnUrl,
            'return-type' => 'redirect',
            'prod' => $productCode,
            'qty' => 1,
            'order-ext-ref' => $this->getTransactionID(),
            'expiration' => $expiration,
        ];

        ksort($data);

        $serialised = '';
        foreach ($data as $k => $v) {
            $serialised .= trim($v);
        }

        $signature = hash_hmac('sha256', $serialised, $config['2co_secret_word']);
        $url = "https://secure.2checkout.com/checkout/buy?merchant={$config['2co_id']}&return-url={$returnUrl}";
        $url .= "&return-type=redirect&expiration={$expiration}&tpl=one-column&prod={$productCode}&qty=1";
        $url .= "&currency={$config['system_currency_code']}&order-ext-ref={$this->getTransactionID()}&signature={$signature}";

        $reefless->redirect(false, $url);
    }

    /**
     * Complete payment process
     */
    public function callBack()
    {
        global $reefless, $config, $rlPayment;

        // save response to log
        if ($config['2co_testmode']) {
            $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($_REQUEST, true));
            file_put_contents(RL_TMP . 'response.log', $log, FILE_APPEND);
        }

        $errors = false;

        if (!$_REQUEST['refno']) {
            header("HTTP/1.1 200 OK");
            exit;
        }
        $reference = $GLOBALS['rlValid']->xSql($_REQUEST['refno']);

        $total = (float) $_REQUEST['total'];

        foreach ($_REQUEST as $key => $val) {
            if (!in_array($key, ['signature', 'page', 'rlVareables', 'gateway'])) {
                $params[$key] = $val;
            }
        }

        $hash = $this->encrypt($params);

        if ($hash != $_REQUEST['signature']) {
            $errors = true;
            $GLOBALS['rlDebug']->logger("2checkout: Hash code invalid [{$hash} != {$_REQUEST['signature']}]");
        }

        $response = array(
            'plan_id' => $rlPayment->getOption('plan_id'),
            'item_id' => $rlPayment->getOption('item_id'),
            'account_id' => $rlPayment->getOption('account_id'),
            'total' => $total,
            'txn_id' => (int) $rlPayment->getTransactionID(),
            'txn_gateway' => $reference,
            'params' => $rlPayment->getOption('params'),
        );

        if (!$errors) {
            $rlPayment->complete(
                $response,
                $rlPayment->getOption('callback_class'),
                $rlPayment->getOption('callback_method'),
                $rlPayment->getOption('callback_plugin')
            );
            $reefless->redirect(null, $rlPayment->getOption('success_url'));
        } else {
            $reefless->redirect(null, $rlPayment->getOption('cancel_url'));
        }
    }

    /**
     * Build request options
     *
     * @since 4.8.2
     *
     * @param array $params
     * @return string
     */
    private function serializeParameters($params = [])
    {
        ksort($params);

        $serializedString = '';

        foreach ($params as $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    $serializedString .= $this->serializeParameters($value);
                } else {
                    $serializedString .= strlen($value) . $value;
                }
            }
        }

        return $serializedString;
    }

    /**
     * Generate signature key
     *
     * @since 4.8.2
     *
     * @param array $params
     * @return string
     */
    private function encrypt($params = [])
    {
        $serialized = $this->serializeParameters($params);

        if (strlen($serialized) > 0) {
            return bin2hex(hash_hmac('sha256', $serialized, $GLOBALS['config']['2co_secret_word'], true));
        }

        return null;
    }

    /**
     * Check if 2co configured
     *
     * @return bool
     */
    public function isConfigured()
    {
        if ($GLOBALS['config']['2co_id']) {
            return true;
        }
        return false;
    }

    /**
     * Send request to checkout server
     *
     * @since 4.8.2
     *
     * @param object $request
     * @param string $host
     */
    public function callRPC($request, $host = '')
    {
        global $config;

        $curl = curl_init($host);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSLVERSION, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));

        $requestString = json_encode($request);

        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestString);

        $responseString = curl_exec($curl);

        if (!empty($responseString)) {
            if ($config['2co_testmode']) {
                $log = sprintf("\n%s:\n%s\n", date('Y.m.d H:i:s'), print_r($responseString, true));
                file_put_contents(RL_TMP . 'errorLog/2co.log', $log, FILE_APPEND);
            }
            $response = json_decode($responseString);

            if (isset($response->result)) {
                return $response->result;
            }

            if (!is_null($response->error->message)) {
                $this->errors[] = $response->error->message;
            }
        }

        return null;
    }

    /**
     * Add product on 2checkout service
     *
     * @since 4.8.2
     */
    public function addProduct()
    {
        global $lang, $rlPayment, $config, $domain_info;

        $host = explode('.', $domain_info['host']);
        $prefix = $host[0] != 'www' ? $host[0] : $host[1];

        $productCode = $prefix . '_' . rand(100000, 999999);

        $product = new stdClass();
        $product->AvangateId = null;
        $product->ProductCode = $productCode;
        $product->ProductType = 'REGULAR';
        $product->ProductName = $rlPayment->getOption('item_name');
        $product->ProductVersion = '1.0';

        $product->GiftOption = false;
        $product->ShortDescription = $rlPayment->getOption('item_name');
        $product->LongDescription = '';
        $product->SystemRequirements = null;
        $product->ProductCategory = null;
        $product->Platforms = array();
        $product->Platforms[0] = new stdClass();
        $product->Platforms[0]->PlatformName = null;
        $product->Platforms[0]->Category = null;
        $product->Platforms[1] = new stdClass();
        $product->Platforms[1]->PlatformName = null;
        $product->Platforms[1]->Category = null;
        $product->ProductImages = array();
        $product->ProductImages[0] = new stdClass();
        $product->ProductImages[0]->URL = null;
        $product->ProductImages[0]->Default = false;
        $product->ProductImages[1] = new stdClass();
        $product->ProductImages[1]->URL = null;
        $product->ProductImages[1]->Default = true;
        $product->TrialUrl = null;
        $product->TrialDescription = null;
        $product->Enabled = true;

        $product->PricingConfigurations = array();
        $product->PricingConfigurations[0] = new stdClass();
        $product->PricingConfigurations[0]->Default = false;
        $product->PricingConfigurations[0]->Code = null;
        $product->PricingConfigurations[0]->Name = $lang['site_name'];
        $product->PricingConfigurations[0]->BillingCountries = array();
        $product->PricingConfigurations[0]->PricingSchema = 'DYNAMIC';
        $product->PricingConfigurations[0]->PriceType = 'NET';
        $product->PricingConfigurations[0]->DefaultCurrency = $config['system_currency_code'];
        $product->PricingConfigurations[0]->Prices = new stdClass();
        $product->PricingConfigurations[0]->Prices->Regular = array();
        $product->PricingConfigurations[0]->Prices->Regular[0] = new stdClass();
        $product->PricingConfigurations[0]->Prices->Regular[0]->Amount = $rlPayment->getOption('total');
        $product->PricingConfigurations[0]->Prices->Regular[0]->Currency = $config['system_currency_code'];
        $product->PricingConfigurations[0]->Prices->Regular[0]->MinQuantity = 1;
        $product->PricingConfigurations[0]->Prices->Regular[0]->MaxQuantity = 1;
        $product->PricingConfigurations[0]->Prices->Regular[0]->OptionCodes = array();
        $product->PricingConfigurations[0]->PriceOptions = array();

        $product->Fulfillment = 'NO_DELIVERY';
        $product->Prices = array();

        $product->GeneratesSubscription = false;

        $product->FulfillmentInformation = new stdClass();
        $product->FulfillmentInformation->IsStartAfterFulfillment = false;
        $product->FulfillmentInformation->IsElectronicCode = false;
        $product->FulfillmentInformation->IsDownloadLink = false;
        $product->FulfillmentInformation->IsBackupMedia = false;
        $product->FulfillmentInformation->IsDownloadInsuranceService = false;
        $product->FulfillmentInformation->IsInstantDeliveryThankYouPage = false;
        $product->FulfillmentInformation->IsDisplayInPartnersCPanel = false;

        $jsonRpcRequest = array(
            'jsonrpc' => '2.0',
            'id' => $this->index++,
            'method' => 'addProduct',
            'params' => array($this->sessionID, $product),
        );

        $response = $this->callRPC($jsonRpcRequest, $this->apiHost);

        if ($response) {
            return $productCode;
        }

        return null;
    }

    /**
     * Get product on 2checkout service
     *
     * @since 4.8.2
     * @param string $productCode
     * @return string
     */
    public function getProduct($productCode = '')
    {
        $jsonRpcRequest = array(
            'jsonrpc' => '2.0',
            'id' => $this->index++,
            'method' => 'getProductByCode',
            'params' => array($this->sessionID, $productCode),
        );

        $response = $this->callRPC($jsonRpcRequest, $this->apiHost);

        if ($response->ProductCode) {
            return $response->ProductCode;
        }

        return null;
    }

    /**
     * Save product code
     *
     * @since 4.8.2
     * @param string $productCode
     */
    public function saveItem($productCode = '')
    {
        global $rlPayment;

        if (!$productCode) {
            return;
        }

        $insert = [
            'Item_ID' => $rlPayment->getOption('item_id'),
            'Service' => $rlPayment->getOption('service'),
            'Code' => $productCode,
        ];

        $GLOBALS['rlDb']->insertOne($insert, '2co_products');
    }

    /**
     * Get product code
     *
     * @since 4.8.2
     * @return string
     */
    public function getItem()
    {
        global $rlPayment;

        $sql = "SELECT *  FROM `{db_prefix}2co_products` ";
        $sql .= "WHERE `Item_ID` = {$rlPayment->getOption('item_id')} AND `Service` = '{$rlPayment->getOption('service')}' ";

        $itemInfo = $GLOBALS['rlDb']->getRow($sql);

        if ($itemInfo['Code']) {
            return $itemInfo['Code'];
        }

        return null;
    }

    /**
     * Call direct method
     *
     * @since 4.8.2
     */
    public function callDirect()
    {
        global $config, $rlPayment, $reefless, $account_info, $rlAccount;

        $accountID = $rlAccount->isLogin() ? $account_info['ID'] : (int) $_SESSION['registration']['account_id'];

        $profile = $rlAccount->getProfile((int) $accountID);
        $country = $this->getCountryCode($profile['Fields']['country']['value']);

        $order = new stdClass();
        $order->Currency = $config['system_currency_code'];
        $order->Language = RL_LANG_CODE;
        $order->Country = $country;
        $order->CustomerIP = Flynax\Utils\Util::getClientIP();
        $order->Items = array();

        $order->Items[0] = new stdClass();
        $order->Items[0]->Code = null;
        $order->Items[0]->Quantity = 1;
        $order->Items[0]->PurchaseType = 'PRODUCT';
        $order->Items[0]->Tangible = false;
        $order->Items[0]->IsDynamic = true;
        $order->Items[0]->Price = new stdClass();
        $order->Items[0]->Price->Amount = $rlPayment->getOption('total');
        $order->Items[0]->Price->Type = 'CUSTOM';
        $order->Items[0]->Name = $rlPayment->getOption('item_name');
        $order->Items[0]->Description = $rlPayment->getOption('item_name');

        // adapt account phone
        $sql = "SELECT * FROM `{db_prefix}account_fields` WHERE `Key` = 'phone' LIMIT 1";
        $phone_field = $GLOBALS['rlDb']->getRow($sql);

        if ($phone_field) {
            $profile['phone'] = $reefless->parsePhone($profile['phone'], $phone_field);
        }
        $phone = $profile['phone'];

        $order->BillingDetails = new stdClass();
        if ($profile['address']) {
            $order->BillingDetails->Address1 = $profile['address'];
        }
        if ($profile['Fields']['country_level2']['value']) {
            $order->BillingDetails->City = $profile['Fields']['country_level2']['value'];
        }
        if ($profile['Fields']['country_level1']['value']) {
            $order->BillingDetails->State = $profile['Fields']['country_level1']['value'];
        }
        $order->BillingDetails->CountryCode = $country;
        if ($phone) {
            $order->BillingDetails->Phone = $phone;
        }
        if ($profile['Mail']) {
            $order->BillingDetails->Email = $profile['Mail'];
        }
        if ($profile['First_name']) {
            $order->BillingDetails->FirstName = $profile['First_name'];
        }
        if ($profile['Last_name']) {
            $order->BillingDetails->LastName = $profile['Last_name'];
        }
        if ($profile['company_name']) {
            $order->BillingDetails->Company = $profile['company_name'];
        }
        if ($profile['zip_code']) {
            $order->BillingDetails->Zip = $profile['zip_code'];
        }

        $order->PaymentDetails = new stdClass();
        $order->PaymentDetails->Type = $config['2co_testmode'] ? 'TEST' : 'EES_TOKEN_PAYMENT';

        $order->PaymentDetails->Currency = $config['system_currency_code'];
        $order->PaymentDetails->CustomerIP = Flynax\Utils\Util::getClientIP();

        $order->PaymentDetails->PaymentMethod = new stdClass();
        $order->PaymentDetails->PaymentMethod->EesToken = $_POST['2co-token'];
        $order->PaymentDetails->PaymentMethod->Vendor3DSReturnURL = $rlPayment->getOption('success_url');
        $order->PaymentDetails->PaymentMethod->Vendor3DSCancelURL = $rlPayment->getOption('cancel_url');

        $jsonRpcRequest = new stdClass();
        $jsonRpcRequest->jsonrpc = '2.0';
        $jsonRpcRequest->method = 'placeOrder';
        $jsonRpcRequest->params = array($this->sessionID, $order);
        $jsonRpcRequest->id = $this->index++;

        $response = $this->callRPC($jsonRpcRequest, $this->apiHost);

        if (!in_array($response->Status, ['AUTHRECEIVED', 'COMPLETE'])) {
            $this->errors[] = 'Invalid payment status - ' . $response->Status;
        }
        if (!in_array($response->ApproveStatus, ['WAITING', 'OK'])) {
            $this->errors[] = 'Invalid approve status - ' . $response->ApproveStatus;
        }
        if ($response->VendorApproveStatus != 'OK') {
            $this->errors[] = 'Invalid vendor status - ' . $response->VendorApproveStatus;
        }

        if (!$this->hasErrors()) {
            $this->updateTransaction(array(
                'Item_data' => $rlPayment->buildItemData(),
                'Txn_ID' => $response->RefNo,
            ));

            $txnData = array(
                'plan_id' => $rlPayment->getOption('plan_id'),
                'item_id' => $rlPayment->getOption('item_id'),
                'account_id' => $rlPayment->getOption('account_id'),
                'total' => $rlPayment->getOption('total'),
                'txn_id' => (int) $rlPayment->getTransactionID(),
                'txn_gateway' => $response->RefNo,
                'params' => $rlPayment->getOption('params'),
            );

            $rlPayment->complete(
                $txnData,
                $rlPayment->getOption('callback_class'),
                $rlPayment->getOption('callback_method'),
                $rlPayment->getOption('callback_plugin')
            );
            $reefless->redirect(null, $rlPayment->getOption('success_url'));
        }
    }

    /**
     * Get country code by name
     *
     * @since 4.8.2
     *
     * @param  string $country
     * @return string
     */
    public static function getCountryCode($country = '')
    {
        if (!$country) {
            return null;
        }

        $countries = Flynax\Utils\Util::getCountries();

        $code = false;
        $country = str_replace("_", "", $country);

        foreach ($countries as $key => $val) {
            if (strtolower($country) == strtolower($val) || $country == $key) {
                $code = trim($key);
                break;
            }
        }

        return $code;
    }
}
