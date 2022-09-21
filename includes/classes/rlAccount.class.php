<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLACCOUNT.CLASS.PHP
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

use Flynax\Utils\Profile;
use Flynax\Utils\Valid;
use Flynax\Utils\Util;
use Flynax\Classes\Agencies;

class rlAccount extends reefless
{
    /**
     * @var Google Map fields list
     **/
    public $mapLocation = array();

    /**
     * @var calculate items
     **/
    public $calc;

    /**
     * @var calculate items in alphabet mode
     **/
    public $calc_alphabet;

    /**
     * @var login return message type
     **/
    public $messageType = 'error';

    /**
     * @var selected listing IDs
     **/
    public $selectedIDs;

    /**
     * @var location fields mapping
     **/
    public $loc_mapping = array();

    /**
     * class constructor
     **/
    public function __construct()
    {
        require_once RL_CLASSES . "rlSecurity.class.php";
    }

    /**
     * Get accounts types information
     *
     * @param  array $except - Except keys
     * @return array         - List of account types
     */
    public function getAccountTypes($except = false)
    {
        global $fields;

        if ($except) {
            if (is_array($except)) {
                $additional = " AND `Key` <> '" . implode("' AND `Key` <> '", $except) . "' ";
            } else {
                $additional = " AND `Key` <> '{$except}' ";
            }
        }

        $fields = [
            'ID', 'Key', 'Abilities', 'Page', 'Own_location', 'Email_confirmation',
            'Admin_confirmation', 'Auto_login', 'Quick_registration', 'Agent', 'Agency',
        ];

        $GLOBALS['rlHook']->load('rlAccountGetAccountTypesFields', $fields, $additional); // from v4.1.0 ($additional >= v4.4)

        $this->outputRowsMap = 'ID';
        $types = $this->fetch($fields, array('Status' => 'active'), "{$additional} ORDER BY `Position`", null, 'account_types');
        $types = $GLOBALS['rlLang']->replaceLangKeys($types, 'account_types', array('name', 'desc'));

        return $types;
    }

    /**
     * get account type details
     *
     * @param mixed $key - account key or ID
     *
     * @return array - requested account details
     **/
    public function getAccountType($key = false)
    {
        $where = is_int($key) ? array('ID' => $key) : array('Key' => $key);
        $where['Status'] = 'active';

        $fields = array('ID', 'Key', 'Abilities', 'Page', 'Own_location', 'Email_confirmation', 'Admin_confirmation', 'Auto_login');
        $type = $this->fetch($fields, $where, null, 1, 'account_types', 'row');

        if (!$type) {
            return false;
        }

        $type = $GLOBALS['rlLang']->replaceLangKeys($type, 'account_types', array('name', 'desc'));
        $type['Abilities'] = explode(',', $type['Abilities']);

        return $type;
    }

    /**
     * Account registration
     *
     * @param int   $type_id - Account type id
     * @param array $step1   - Step 1 data
     * @param array $account - Step 2 data
     * @param array $fields  - Current account type fields
     */
    public function registration($type_id, $profile, $account, $fields)
    {
        global $rlCommon, $account_types;

        // get account type key
        $type_id = (int) $type_id;
        $account_type_key = $this->getOne('Key', "`ID` = '{$type_id}'", 'account_types');

        if (empty($account_type_key)) {
            return false;
        }

        // step 1
        $data = array(
            'Type'         => $account_type_key,
            'Username'     => trim($profile['username']),
            'Own_address'  => $profile['location'],
            'Password'     => FLSecurity::cryptPassword($profile['password']),
            'Password_tmp' => $account_types[$type_id]['Email_confirmation'] ? $profile['password'] : '',
            'Lang'         => $profile['lang'] ?: RL_LANG_CODE,
            'Mail'         => $profile['mail'],
            'Date'         => 'NOW()',
        );

        if ($profile['display_email']) {
            $data['Display_email'] = 1;
        }

        // check administrator confirmation
        $data['Status'] = $account_types[$type_id]['Admin_confirmation'] && !defined('REALM') ? 'pending' : 'active';

        if ($account_types[$type_id]['Email_confirmation'] && !defined('REALM')) {
            $confirm_code = md5(mt_rand());
            $data['Confirm_code'] = $confirm_code;
            $data['Status'] = 'incomplete';
        }

        // set membership plan
        if (defined('REALM') && $GLOBALS['config']['membership_module']) {
            $data['Plan_ID'] = (int) $profile['plan'];
            $data['Pay_date'] = 'NOW()';
        }

        $GLOBALS['rlHook']->load('phpRegistrationBeforeInsert', $data, $profile); // > v4.1.0, profile param since 4.5.2

        // insert data
        if ($GLOBALS['rlActions']->insertOne($data, 'accounts', array('Username', 'Password', 'Password_tmp'))) {
            $account_id = $_SESSION['registration']['account_id'] = $this->insertID();

            if ($_COOKIE['agencyInviteConfirmationKey']) {
                $GLOBALS['rlDb']->updateOne([
                    'fields' => ['Agent_ID'    => $account_id],
                    'where'  => ['Invite_Code' => $_COOKIE['agencyInviteConfirmationKey']],
                ], 'agency_invites');

                (new Agencies())
                    ->setInviteKey($_COOKIE['agencyInviteConfirmationKey'])
                    ->acceptInvite()
                    ->removeInviteKey();
            }

            // step 2
            if (empty($account)) {
                return true;
            }

            foreach ($fields as $fIndex => $fRow) {
                $sFields[$fIndex] = $fields[$fIndex]['Key'];
            }

            foreach ($account as $key => $value) {
                $poss = array_search($key, $sFields);

                if ($fields[$poss]['Map'] && $value) {
                    $location[] = $rlCommon->adaptValue($fields[$poss], $value);
                }

                switch ($fields[$poss]['Type']) {
                    case 'text':
                        if ($fields[$poss]['Multilingual'] && count($GLOBALS['languages']) > 1) {
                            $out = '';
                            foreach ($GLOBALS['languages'] as $language) {
                                $val = $account[$key][$language['Code']];
                                if ($val) {
                                    $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                                }
                            }

                            $data2['fields'][$key] = $out;
                        } else {
                            $data2['fields'][$key] = $account[$key];
                        }

                        break;

                    case 'textarea':
                        if ($fields[$poss]['Condition'] == 'html') {
                            $html_fields[] = $fields[$poss]['Key'];
                        }

                        if ($fields[$poss]['Multilingual'] && count($GLOBALS['languages']) > 1) {
                            $limit = (int) $fields[$poss]['Values'];

                            $out = '';
                            foreach ($GLOBALS['languages'] as $language) {
                                $val = $account[$key][$language['Code']];
                                if ($limit && $fields[$poss]['Condition'] != 'html') {
                                    $limit = (int) $fields[$poss]['Values'];
                                    if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                                        mb_internal_encoding('UTF-8');
                                        $val = mb_substr($val, 0, $limit);
                                    } else {
                                        $val = substr($val, 0, $limit);
                                    }
                                }

                                if ($val) {
                                    $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                                }
                            }
                            $data2['fields'][$key] = $out;
                        } else {
                            if ($fields[$poss]['Values']) {
                                $limit = (int) $fields[$poss]['Values'];

                                if ($limit && $fields[$poss]['Condition'] != 'html') {
                                    if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                                        mb_internal_encoding('UTF-8');
                                        $account[$key] = mb_substr($account[$key], 0, $limit);
                                    } else {
                                        $account[$key] = substr($account[$key], 0, $limit);
                                    }
                                }
                            }
                            $data2['fields'][$key] = $account[$key];
                        }

                        break;

                    case 'select':
                    case 'bool':
                    case 'radio':
                        $data2['fields'][$key] = $account[$key];

                        break;

                    case 'number':
                        $data2['fields'][$key] = preg_replace('/[^\d|.]/', '', $account[$key]);

                        break;

                    case 'phone':
                        $out = '';

                        /* code */
                        if ($fields[$poss]['Opt1']) {
                            $code = $GLOBALS['rlValid']->xSql(substr($account[$key]['code'], 0, $fields[$poss]['Default']));
                            $out = 'c:' . $code . '|';
                        }

                        /* area */
                        $area = $GLOBALS['rlValid']->xSql($account[$key]['area']);
                        $out .= 'a:' . $area . '|';

                        /* number */
                        $number = $GLOBALS['rlValid']->xSql(substr($account[$key]['number'], 0, $fields[$poss]['Values']));
                        $out .= 'n:' . $number;

                        /* extension */
                        if ($fields[$poss]['Opt2']) {
                            $ext = $GLOBALS['rlValid']->xSql($account[$key]['ext']);
                            $out .= '|e:' . $ext;
                        }

                        $data2['fields'][$key] = $out;
                        break;

                    case 'mixed':
                        $data2['fields'][$key] = $account[$key]['value'] . '|' . $account[$key]['df'];
                        break;

                    case 'unit':
                        $data2['fields'][$key] = $account[$key]['value'] . '|' . $account[$key]['unit'];
                        break;

                    case 'date':
                        if ($fields[$poss]['Default'] == 'single') {
                            $data2['fields'][$key] = $account[$key];
                        } elseif ($fields[$poss]['Default'] == 'multi') {
                            $data2['fields'][$key] = $account[$key]['from'];
                            $data2['fields'][$key . '_multi'] = $account[$key]['to'];
                        }

                        break;

                    case 'checkbox';

                        unset($account[$key][0], $chValues);
                        foreach ($account[$key] as $chRow) {
                            $chValues .= $chRow . ",";
                        }
                        $chValues = substr($chValues, 0, -1);

                        $data2['fields'][$key] = $chValues;

                        break;

                    case 'image':
                        $file_name   = 'account_' . $account_id . '_' . $key . '_' . time();
                        $resize_type = $fields[$poss]['Default'];
                        $resolution  = strtoupper($resize_type) == 'C' ? explode('|', $fields[$poss]['Values']) : $fields[$poss]['Values'];
                        $parent      = $this->isAdmin() ? 'f' : 'account';

                        $file_name = $GLOBALS['rlActions']->upload($key, $file_name, $resize_type, $resolution, $parent, false);
                        $data2['fields'][$key] = $file_name;

                        break;

                    case 'file':
                        $file_name = 'account_' . $account_id . '_' . $key . '_' . time();
                        $file_name = $GLOBALS['rlActions']->upload($key, $file_name, false, false, false, false);
                        $data2['fields'][$key] = $file_name;
                        break;

                    case 'accept':
                        $data2['fields'][$key] = $account[$key];

                        break;
                }
            }

            if (!empty($data2)) {
                /* get coordinates by address request */
                $this->geocodeLocation($location, $data2['fields']);

                $data2['where'] = array('ID' => $account_id);

                $GLOBALS['rlHook']->load('phpRegistrationBeforeUpdate', $data2, $account_id); // from v4.0.2, params > 4.1.0

                $GLOBALS['rlActions']->updateOne($data2, 'accounts', $html_fields);
            }

            return true;
        }
    }

    /**
     * Quick user registration
     *
     * @param  string $name    - User name
     * @param  string $email   - User E-mail
     * @param  int    $plan_id - ID of membership plan
     * @param  int    $account_type_id - ID of account type
     * @param  string $listing_type_key - listing type key to check available abilities for automatic account type selection
     *
     * @return bool|array      - Return [username|email, password, id] when successful registration
     */
    public function quickRegistration($name, $email, $plan_id = 0, $account_type_id = 0, $listing_type_key = '')
    {
        global $config, $rlDb;

        if (!$name || !$email) {
            return false;
        }

        // preparing of data
        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $password = $GLOBALS['reefless']->generateHash(10, 'password', true);
        $username = !utf8_is_ascii($name) ? utf8_to_ascii($name) : $name;
        $username = $this->makeUsernameUnique($username);
        $first_name = $name;

        if ($account_type_id) {
            $account_types = $GLOBALS['rlAccountTypes']->types;

            // get key from types
            if ($account_types) {
                foreach ($account_types as $atype) {
                    if ($atype['ID'] == $account_type_id) {
                        $type = $atype['Key'];
                        break;
                    }
                }
            }
            // get key from database if types aren't available
            else {
                $type = $rlDb->getOne('Key', "`ID` = {$account_type_id}", 'account_types');
            }
        } else {
            $where = $listing_type_key ? "FIND_IN_SET('{$listing_type_key}', `Abilities`) > 0 AND " : '';
            $where .= "`Key` <> 'visitor' ORDER BY `Position` ASC";

            // select first type
            $type = $rlDb->getOne('Key', $where, 'account_types');
        }

        // insert new account
        if (is_array($exp_name = explode(' ', $name))) {
            $first_name = $exp_name[0];
            array_shift($exp_name);
            $last_name = implode(' ', $exp_name);
        }

        $own_address = $GLOBALS['rlSmarty']->str2path($username);

        $new_account = array(
            'Quick'       => 1,
            'Type'        => $type,
            'Username'    => trim($username),
            'Own_address' => $own_address,
            'Password'    => FLSecurity::cryptPassword($password),
            'Lang'        => RL_LANG_CODE,
            'First_name'  => $first_name,
            'Last_name'   => $last_name,
            'Mail'        => $email,
            'Date'        => 'NOW()',
            'Status'      => 'active',
        );

        // Set membership plan
        if ($plan_id) {
            $new_account['Plan_ID'] = (int) $plan_id;

            if ($rlDb->getOne('Price', "`ID` = {$plan_id}", 'membership_plans') <= 0) {
                $new_account['Pay_date'] = 'NOW()';
            }
        }

        /**
         * @since 4.0.2
         *
         * @since 4.3 @new_account
         * @since 4.6.0 @$listing_type
         */
        $GLOBALS['rlHook']->load('phpQuickRegistrationBeforeInsert', $new_account, $listing_type_key);

        $rlDb->insert($new_account, 'accounts');
        $account_id = $rlDb->insertID();

        $GLOBALS['rlCache']->updateStatistics();

        unset($new_account, $user_exist, $first_name, $last_name);

        return array(($config['account_login_mode'] == 'email' ? $email : $username), $password, $account_id);
    }

    /**
     * Get fields by account type ID
     *
     * @param int $id - Account type ID
     */
    public function getFields($id = false, $form = 'account_submit_form')
    {
        global $languages;

        $id = (int) $id;

        $sql = "SELECT `T1`.`Key`, `T1`.`Type`, `T1`.`Default`, `T1`.`Values`, `T1`.`Condition`,  ";
        $sql .= "CONCAT('account_fields+default+', `T1`.`Key`) as `pDefault`, `T1`.`Map`, ";
        $sql .= "`T1`.`Details_page`, `T1`.`Multilingual`, `T1`.`Opt1`, `T1`.`Opt2`, `T1`.`Contact`, ";
        $sql .= "`T1`.`Required`, `T1`.`Add_page`, `T1`.`Autocomplete` ";
        $sql .= "FROM `{db_prefix}account_fields` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}{$form}` AS `T2` ON `T1`.`ID` = `Field_ID` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T2`.`Category_ID` = '{$id}' ";
        $sql .= "ORDER BY `T2`.`Position`";
        $fields = $this->getAll($sql, 'Key');

        // Add default values for text fields with Multilingual mode
        foreach ($fields as &$field) {
            if ($field['Type'] == 'text' && $field['Multilingual'] && $field['Default'] && count($languages) > 1) {
                foreach ($languages as $language) {
                    $field['pMultiDefault'][$language['Code']] = $GLOBALS['rlDb']->getOne(
                        'Value',
                        "`Key` = '{$field['pDefault']}' AND `Code` = '{$language['Code']}'",
                        'lang_keys'
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get profile details
     *
     * @param int|string $id - Account id or location address
     */
    public function getProfile($id = false, $edit_mode = false)
    {
        global $lang, $rlCommon;

        if (!$id) {
            return false;
        }

        // Clear map data to avoid data mess on multiple method call
        $this->mapLocation = [];

        $sql = "SELECT `T1`.*, `T2`.`ID` AS `Account_type_ID`, `T2`.`Own_location`, `T2`.`Page` AS `Own_page`, ";
        $sql .= "`T2`.`Thumb_width`, `T2`.`Thumb_height` ";

        $GLOBALS['rlHook']->load('phpGetProfileModifyField', $sql, $edit_mode); // >= v4.3

        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";

        $GLOBALS['rlHook']->load('phpGetProfileModifyJoin', $sql, $edit_mode); // >= v4.3

        if (is_int($id)) {
            $sql .= "WHERE `T1`.`ID` = '{$id}' LIMIT 1";
        } else {
            $GLOBALS['rlValid']->sql($id);
            $sql .= "WHERE `T1`.`Own_address` = '{$id}' LIMIT 1";
        }

        $GLOBALS['rlHook']->load('phpGetProfileModifyWhere', $sql, $edit_mode); // >= v4.3

        $data = $this->getRow($sql);

        if (!$data) {
            return false;
        }

        if (!$this->isAdmin()) {
            unset($data['Password'], $data['Password_hash'], $data['Password_tmp'], $data['Confirm_code']);
        }

        /**
         * Company value adaptation
         *
         * @since 4.9.0
         * @todo - To be removed after "Account Title form" feature creation
         */
        $data['Full_name'] = trim(
            $data['company_name'] ?:
                ($data['First_name'] || $data['Last_name']
                    ? $data['First_name'] . ' ' . $data['Last_name']
                    : $data['Username']
                )
        );

        $data['Type_name']        = $lang['account_types+name+' . $data['Type']];
        $data['Type_description'] = $lang['account_types+desc+' . $data['Type']];
        $data['Personal_address'] = Profile::getPersonalAddress($data, $data);

        $fields = $this->getFields($data['Account_type_ID']);
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name', 'description'));

        if ($edit_mode) {
            $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');
        }

        foreach ($fields as $key => $field) {
            if ((empty($data[$key]) && !$edit_mode) || ($field['Type'] == 'accept' && $edit_mode)) {
                continue;
            }

            $tmp_value = $data[$key];
            $data['Fields'][$key] = $field;
            $data['Fields'][$key]['value'] = $rlCommon->adaptValue(
                $field,
                $tmp_value,
                'account',
                $id,
                null,
                null,
                $edit_mode
            );

            if ($field['Map'] && $data['Fields'][$key]['value']) {
                $mValue = addslashes($data['Fields'][$key]['value']);
                $this->mapLocation['search'] .= $mValue . ', ';
                $this->mapLocation['show'] .= $field['name'] . ': <b>' . $mValue . '<\/b><br />';
            }
        }

        if ($this->mapLocation) {
            $this->mapLocation['search'] = substr($this->mapLocation['search'], 0, -2);
            $this->mapLocation['show'] = substr($this->mapLocation['show'], 0, -11);

            if ($data['Loc_latitude'] != '' && $data['Loc_longitude'] != '') {
                $this->mapLocation['direct'] = $data['Loc_latitude'] . ',' . $data['Loc_longitude'];
            }
        }

        unset($fields);

        if ($data['Agency_ID']) {
            $data['Agency_Info'] = $this->getProfile((int) $data['Agency_ID']);
        } else {
            $agencies = new Agencies();

            if ($agencies->isAgency((array) $data)) {
                $data['Agents_count']   = $agencies->getAgentsCount((int) $data['ID']);
                $data['Listings_count'] += $agencies->getAgentsListingsCount((int) $data['ID']);
            }
        }

        /**
         * @since 4.9.0
         */
        $GLOBALS['rlHook']->load('phpGetProfileBottom', $id, $data);

        return $data;
    }

    /**
     * if user exist
     *
     * @package xAjax
     *
     * @param string $username - requested username
     *
     **/
    public function ajaxUserExist($username = false)
    {
        global $_response, $lang;

        if (!$username || $_SESSION['registration']['account_id']) {
            return $_response;
        }

        $username = trim($username);
        $GLOBALS['rlValid']->sql($username);
        $exist = (bool) $this->getOne('ID', "`Username` = '{$username}'", 'accounts');
        $message = str_replace('{username}', $username, $lang['notice_account_exist']);

        $GLOBALS['rlHook']->load('phpAjaxUserExist', $username, $message, $exist); // from v4.0.2

        preg_match('/^(?=.{3,15}$)[\-\_a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/', $username, $valid);
        if ($exist || empty($username)
            || (!$valid && $message = str_replace('{field}', '<span class="field_error">"' . $lang['username'] . '"</span>', $lang['notice_field_not_valid']))
        ) {
            $_response->script("
                if ( !$('input[name=\"profile[username]\"]').hasClass('error') )
                {
                    printMessage('error', '{$message}');
                    $('input[name=\"profile[username]\"]').addClass('error').next().remove();
                    $('input[name=\"profile[username]\"]').after('<span class=\"fail_field\">&nbsp;</span>');
                }
            ");
        } else {
            $_response->script("
                $('div.error div.close').trigger('click');
                $('input[name=\"profile[username]\"]').removeClass('error').next().remove();
                $('input[name=\"profile[username]\"]').after('<span class=\"success_field\">&nbsp;</span>');
            ");
        }

        return $_response;
    }

    /**
     * if email exist
     *
     * @package xAjax
     *
     * @param string $email - requested e-mail
     *
     **/
    public function ajaxEmailExist($email = false)
    {
        global $_response, $lang;

        if (!$email || $_SESSION['registration']['account_id']) {
            return $_response;
        }

        if (!$GLOBALS['rlValid']->isEmail($email)) {
            $_response->script("
                if ( !$('input[name=\"profile[mail]\"]').hasClass('error') )
                {
                    printMessage('error', '" . str_replace('{email}', $email, $lang['notice_bad_email']) . "');
                    $('input[name=\"profile[mail]\"]').addClass('error').next().remove();
                    $('input[name=\"profile[mail]\"]').after('<span class=\"fail_field\">&nbsp;</span>');
                }
            ");
            return $_response;
        }

        $GLOBALS['rlValid']->sql($email);
        $exist = (bool) $this->getOne('ID', "`Mail` = '{$email}'", 'accounts');
        $message = str_replace('{email}', $email, $lang['notice_account_email_exist']);
        $callback = false;

        /**
         * @since v4.0.2
         * @since v4.4 $callback - hook callback
         */
        $GLOBALS['rlHook']->load('phpAjaxEmailExist', $email, $message, $callback);

        if ($exist || $callback) {
            $_response->script("
                if ( !$('input[name=\"profile[mail]\"]').hasClass('error') )
                {
                    printMessage('error', '{$message}');
                    $('input[name=\"profile[mail]\"]').addClass('error').next().remove();
                    $('input[name=\"profile[mail]\"]').after('<span class=\"fail_field\">&nbsp;</span>');
                }
            ");
        } else {
            $_response->script("
                $('div.error div.close').trigger('click');
                $('input[name=\"profile[mail]\"]').removeClass('error').next().remove();
                $('input[name=\"profile[mail]\"]').after('<span class=\"success_field\">&nbsp;</span>');
            ");
        }

        return $_response;
    }

    /**
     * Description
     * @param type $error
     * @param type $wrapper
     */
    public function errorsWrapper($error = false, $wrapper = false)
    {
        if (false !== $wrapper && is_string($error) && is_string($wrapper)) {
            return str_replace('{error}', $error, $wrapper);
        }
        return $error;
    }

    /**
     * Validate user location
     *
     * @param type $location
     * @param type &$errors
     * @param type &$errors_trigger
     * @param type $wrapper
     */
    public function validateUserLocation($location, &$errors, &$errors_trigger = null, $wrapper = false)
    {
        global $config, $lang;

        $location = trim($location);

        if (empty($location) || is_numeric($location)) {
            $errors = $this->errorsWrapper($lang['personal_address_error'], $wrapper);
            $errors_trigger = true;
            return;
        } else if (strlen($location) < 3) {
            $errors = $this->errorsWrapper($lang['personal_address_length_error'], $wrapper);
            $errors_trigger = true;
            return;
        }

        $GLOBALS['rlValid']->sql($location);
        $wildcard_deny = explode(',', $config['account_wildcard_deny']);

        $this->setTable('pages');
        $this->outputRowsMap = array(false, 'Path');
        $pages_paths = $this->fetch(array('Path'), null, "WHERE `Path` <> ''");
        $all_prefix = !empty($lang['alphabet_characters']) ? explode(',', $lang['alphabet_characters']) : '';
        $all_prefix = $all_prefix[0] ? strtolower($all_prefix[0]) : '';
        $wildcard_deny = array_merge($wildcard_deny, $pages_paths, array(ADMIN, $all_prefix));

        $u = !$config['url_transliteration'] ? 'u' : ''; //unicode modifier
        preg_match('/[\W]+/' . $u, str_replace(array('-', '_'), '', $location), $matches);

        if (!empty($matches)) {
            $errors = $this->errorsWrapper($lang['personal_address_error'], $wrapper);
            $errors_trigger = true;
        }
        /* check for uniqueness */
        else if (in_array($location, $wildcard_deny) || $this->getOne('ID', "`Own_address` = '{$location}'", 'accounts')) {
            $errors = $this->errorsWrapper($lang['personal_address_in_use'], $wrapper);
            $errors_trigger = true;
        }

        /**
         * @since 4.4
         **/
        $GLOBALS['rlHook']->load('phpValidateUserLocation', $location, $errors, $errors_trigger, $wrapper);
    }

    /**
     * check personal address field value
     *
     * @package xAjax
     *
     * @param int $location - personal address value
     *
     **/
    public function ajaxCheckLocation($location = false)
    {
        global $_response, $lang, $config;

        if (!$location) {
            return $_response;
        }

        // validate
        $this->validateUserLocation($location, $errors);

        /**
         * @since 4.9.0 - Removed second $wildcard_deny parameter
         */
        $GLOBALS['rlHook']->load('phpAjaxCheckLocation', $location, $errors);

        if ($errors) {
            $_response->script("
                if ( !$('input[name=\"profile[location]\"]').hasClass('error') )
                {
                    printMessage('error', '" . $errors . "');
                    $('input[name=\"profile[location]\"]').addClass('error').parent().next().remove();
                    $('input[name=\"profile[location]\"]').parent().after('<span class=\"fail_field\">&nbsp;</span>');
                }
            ");
        } else {
            $_response->script("
                $('div.error div.close').trigger('click');
                $('input[name=\"profile[location]\"]').removeClass('error').parent().next().remove();
                $('input[name=\"profile[location]\"]').parent().after('<span class=\"success_field\">&nbsp;</span>');
            ");
        }

        return $_response;
    }

    /**
     * validate "on the fly" fields
     *
     * @package xAjax
     *
     * @param string $username - requested username
     * @param string $email - requested e-mail
     * @param int $location - personal address value
     *
     **/
    public function ajaxValidateProfile($username = false, $email = false, $location = false, $check_location = false)
    {
        global $_response, $lang;

        $errors = '';
        $response = '';

        if ($username) {
            $GLOBALS['rlValid']->sql($username);
            $exist = (bool) $this->getOne('ID', "`Username` = '{$username}'", 'accounts');
            $message = str_replace('{username}', $username, $lang['notice_account_exist']);

            $GLOBALS['rlHook']->load('phpAjaxValidateProfileUsername', $username, $message, $exist); // from v4.0.2

            if ($exist) {
                $errors .= "<li>{$message}</li>";
                $response .= "
                    if ( !$('input[name=\"profile[username]\"]').hasClass('error') )
                    {
                        $('input[name=\"profile[username]\"]').addClass('error').next().remove();
                        $('input[name=\"profile[username]\"]').after('<span class=\"fail_field\">&nbsp;</span>');
                    }
                ";
            } else {
                $response .= "
                    $('input[name=\"profile[username]\"]').removeClass('error').next().remove();
                    $('input[name=\"profile[username]\"]').after('<span class=\"success_field\">&nbsp;</span>');
                ";
            }
        }

        if ($email) {
            $GLOBALS['rlValid']->sql($email);
            $exist = (bool) $this->getOne('ID', "`Mail` = '{$email}'", 'accounts');
            $message = str_replace('{email}', $email, $lang['notice_account_email_exist']);

            $GLOBALS['rlHook']->load('phpAjaxValidateProfileEmail', $email, $message, $exist); // from v4.0.2

            if ($exist) {
                $errors .= "<li>{$message}</li>";
                $response .= "
                    if ( !$('input[name=\"profile[mail]\"]').hasClass('error') )
                    {
                        $('input[name=\"profile[mail]\"]').addClass('error').next().remove();
                        $('input[name=\"profile[mail]\"]').after('<span class=\"fail_field\">&nbsp;</span>');
                    }
                ";
            } else {
                $response .= "
                    $('input[name=\"profile[mail]\"]').removeClass('error').next().remove();
                    $('input[name=\"profile[mail]\"]').after('<span class=\"success_field\">&nbsp;</span>');
                ";
            }
        }

        if ($location && $check_location) {
            $this->validateUserLocation($location, $errors, $errors_trigger, '<li>{error}</li>');

            /**
             * @since 4.9.0 - Removed second $wildcard_deny parameter
             * @since 4.0.2
             */
            $GLOBALS['rlHook']->load('phpAjaxValidateProfileLocation', $location, $errors_trigger);

            if ($errors_trigger) {
                $response .= "
                    if ( !$('input[name=\"profile[location]\"]').hasClass('error') )
                    {
                        $('input[name=\"profile[location]\"]').addClass('error').parent().next().remove();
                        $('input[name=\"profile[location]\"]').parent().after('<span class=\"fail_field\">&nbsp;</span>');
                    }
                ";
            } else {
                $response .= "
                    $('input[name=\"profile[location]\"]').removeClass('error').parent().next().remove();
                    $('input[name=\"profile[location]\"]').parent().after('<span class=\"success_field\">&nbsp;</span>');
                ";
            }
        }

        $GLOBALS['rlHook']->load('phpAjaxValidateProfile'); // from v4.0.2

        if ($errors) {
            $errors = '<ul>' . $errors . '</ul>';
            $_response->script("printMessage('error', '" . $errors . "');");
            $_response->script($response);
        }

        return $_response;
    }

    /**
     * check type fields exist
     *
     * @package xAjax
     *
     * @param int $type_id - requested account type id
     *
     **/
    public function ajaxCheckTypeFields($type_id = false)
    {
        global $_response;

        if (!$type_id) {
            return $_response;
        }

        if ($this->getFields($type_id)) {
            $_response->script("$('#step_account').fadeIn();");
        } else {
            $_response->script("$('#step_account').fadeOut();");
        }

        return $_response;
    }

    /**
     * validate username for special chars and quotes
     *
     * @param string $username - username
     *
     * @return bool res - validation result
     *
     **/
    public function validateUsername($username = false)
    {
        $res = false;

        if (!$username) {
            return $res;
        }

        if (preg_match('/^(?=.{3,30}$)[a-zA-Z][\.\-\_a-zA-Z0-9]*(?: [a-zA-Z0-9]+)*$/', $username)) {
            $res = true;
        }

        /**
         * @since v4.5.1
         */
        $GLOBALS['rlHook']->load('phpValidateUsername', $username, $res);

        return $res;
    }

    /**
     * Make username unique if such username already exists | RECURSION
     *
     * @since 4.5.1
     *
     * @param string $username - Username of account
     *
     * @return string - Unique username
     */
    public function makeUsernameUnique($username)
    {
        $username = preg_replace('/[^a-zA-Z0-9\+\s\.]+/i', '', $username);

        Valid::escape($username);

        $exist = $this->getOne('ID', "`Username` = '{$username}'", 'accounts');

        if ($exist) {
            preg_match('/([\D]*)(\d+)$/', $username, $matches);
            $postfix = isset($matches[2]) ? $matches[2] + 1 : 1;
            $username = isset($matches[2]) ? $matches[1] : $username;

            return $this->makeUsernameUnique($username . $postfix);
        }

        return $username;
    }

    /**
     * Confirm account by account ID
     *
     * @param int   $id      - Account id
     * @param array $account - Account information
     *
     */
    public function confirmAccount($id = false, $account = false)
    {
        global $config, $lang, $rlMail, $rlDb;

        $mail_tpl_key = $account['Admin_confirmation'] ? 'account_confirmed_pending' : 'account_confirmed_active';
        $mail_tpl = $rlMail->getEmailTemplate($mail_tpl_key, $account['Lang']);

        $account_area_link = '<a href="'
            . $GLOBALS['reefless']->getPageUrl('login')
            . '">'
            . $GLOBALS['rlLang']->getPhrase('blocks+name+account_area', null, null, true)
            . '</a>';

        $find = array(
            '{account_area}',
            '{login}',
            '{password}',
            '{name}',
        );

        $replace = array(
            $account_area_link,
            $config['account_login_mode'] == 'email' ? $account['Mail'] : $account['Username'],
            $account['Password_tmp'],
            trim(
                $account['First_name'] || $account['Last_name']
                ? $account['First_name'] . ' ' . $account['Last_name']
                : $account['Username']
            ),
        );

        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        $rlMail->send($mail_tpl, $account['Mail']);

        $update = array(
            'fields' => array(
                'Status' => $account['Admin_confirmation'] ? 'pending' : 'active',
                'Password_tmp' => ''
            ),
            'where' => array('ID' => $id)
        );

        $rlDb->updateOne($update, 'accounts');
    }

    /**
     * edit profile data
     *
     * @param array $profile - profile data
     *
     **/
    public function editProfile($profile = false, $id = false)
    {
        global $account_info;

        $account_id = intval($id ? $id : $account_info['ID']);

        $data = array(
            'fields' => array(
                'Mail'          => $profile['mail'],
                'Display_email' => $profile['display_email'] ? 1 : 0,
                'Lang'          => $profile['lang'],
            ),
            'where'  => array(
                'ID' => $account_id,
            ),
        );

        if (defined('REALM')) {
            $data['fields']['Type'] = $profile['type'];
            $data['fields']['Status'] = $profile['status'];

            if ($profile['password']) {
                $data['fields']['Password'] = $profile['password'];
            }

            // update membership plan
            if ($GLOBALS['config']['membership_module'] && $profile['plan']) {
                $data['fields']['Plan_ID'] = (int) $profile['plan'];
                $data['fields']['Pay_date'] = 'NOW()';
                $data['fields']['Featured'] = $profile['featured'];

                // update usage statistic
                if ($account_info['Plan_ID'] != $profile['plan']) {
                    $this->upgrade($account_id, $profile['plan']);
                }
            }
        }

        if ($profile['location']) {
            $data['fields']['Own_address'] = trim($profile['location']);
            /* update session data */
            $account_info['Own_address'] = trim($profile['location']);
        }

        $GLOBALS['rlHook']->load('phpEditProfileBeforeUpdate', $data); // from v4.0.2

        // update data
        $result = $GLOBALS['rlActions']->updateOne($data, 'accounts');

        return $result;
    }

    /**
     * edit account
     *
     * @param array $account - account data
     * @param array $fields - current account type fields
     * @param array $id - account id
     *
     **/
    public function editAccount($account_data = false, $fields = false, $id = false)
    {
        global $account_info, $rlCommon;

        if (!$account_data || !$fields) {
            return true;
        }

        $account_id = intval($id ?: $account_info['ID']);

        foreach ($fields as $fIndex => $fRow) {
            $sFields[$fIndex] = $fields[$fIndex]['Key'];
        }

        $update['where'] = array(
            'ID' => $account_id,
        );

        foreach ($account_data as $key => $value) {
            $poss = array_search($key, $sFields);

            if ($fields[$poss]['Map'] && $value) {
                $location[] = $rlCommon->adaptValue($fields[$poss], $value);
            }

            switch ($fields[$poss]['Type']) {
                case 'text':
                    if ($fields[$poss]['Condition'] == 'html') {
                        $html_fields[] = $fields[$poss]['Key'];
                    }

                    if ($fields[$poss]['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $out = '';
                        foreach ($GLOBALS['languages'] as $language) {
                            $val = $account_data[$key][$language['Code']];
                            if ($val) {
                                $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                            }
                        }

                        $update['fields'][$key] = $out;
                    } else {
                        $update['fields'][$key] = $account_data[$key];
                    }
                    break;

                case 'textarea':
                    if ($fields[$poss]['Condition'] == 'html') {
                        $html_fields[] = $fields[$poss]['Key'];
                    }

                    $limit = (int) $fields[$poss]['Values'];

                    if ($fields[$poss]['Multilingual'] && count($GLOBALS['languages']) > 1) {
                        $out = '';
                        foreach ($GLOBALS['languages'] as $language) {
                            $val = $account_data[$key][$language['Code']];
                            if ($limit && $fields[$poss]['Condition'] != 'html') {
                                // Revert quotes characters and remove trailing new line code
                                Valid::revertQuotes($val);
                                $val = str_replace(PHP_EOL, '', $val);

                                if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                                    mb_internal_encoding('UTF-8');
                                    $val = mb_substr($val, 0, $limit);
                                } else {
                                    $val = substr($val, 0, $limit);
                                }
                            }

                            if ($val) {
                                $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                            }
                        }
                        $update['fields'][$key] = $out;
                    } else {
                        if ($fields[$poss]['Values']) {
                            if ($limit && $fields[$poss]['Condition'] != 'html') {
                                // Revert quotes characters and remove trailing new line code
                                Valid::revertQuotes($account_data[$key]);
                                $account_data[$key] = str_replace(PHP_EOL, '', $account_data[$key]);

                                if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                                    mb_internal_encoding('UTF-8');
                                    $account_data[$key] = mb_substr($account_data[$key], 0, $limit);
                                } else {
                                    $account_data[$key] = substr($account_data[$key], 0, $limit);
                                }
                            }
                        }
                        $update['fields'][$key] = $account_data[$key];
                    }
                    break;

                case 'select':
                case 'bool':
                case 'radio':
                    $update['fields'][$key] = $account_data[$key];
                    break;

                case 'number':
                    $update['fields'][$key] = preg_replace('/[^\d|.]/', '', $account_data[$key]);
                    break;

                case 'phone':
                    $out = '';

                    /* code */
                    if ($fields[$poss]['Opt1']) {
                        $code = $GLOBALS['rlValid']->xSql(substr($account_data[$key]['code'], 0, $fields[$poss]['Default']));
                        $out = 'c:' . $code . '|';
                    }

                    /* area */
                    $area = $GLOBALS['rlValid']->xSql($account_data[$key]['area']);
                    $out .= 'a:' . $area . '|';

                    /* number */
                    $number = $GLOBALS['rlValid']->xSql(substr($account_data[$key]['number'], 0, $fields[$poss]['Values']));
                    $out .= 'n:' . $number;

                    /* extension */
                    if ($fields[$poss]['Opt2']) {
                        $ext = $GLOBALS['rlValid']->xSql($account_data[$key]['ext']);
                        $out .= '|e:' . $ext;
                    }

                    $update['fields'][$key] = $out;
                    break;

                case 'date':
                    if ($fields[$poss]['Default'] == 'single') {
                        $update['fields'][$key] = $account_data[$key];
                    } elseif ($fields[$poss]['Default'] == 'multi') {
                        $update['fields'][$key] = $account_data[$key]['from'];

                        /* save multi data (to date in this case) */
                        $multi['where'] = array(
                            'ID' => $id ?: $account_id,
                        );

                        $multi['fields'] = array($key . '_multi' => $account_data[$key]['to']);

                        $GLOBALS['rlHook']->load('phpEditAccountBeforeUpdateDateMulti', $multi, $account_data); // from v4.0.2

                        $GLOBALS['rlActions']->updateOne($multi, 'accounts');
                    }
                    break;

                case 'mixed':
                    $update['fields'][$key] = $account_data[$key]['value'] . '|' . $account_data[$key]['df'];
                    break;

                case 'checkbox';

                    unset($account_data[$key][0]);
                    $chValues = null;

                    foreach ($account_data[$key] as $chRow) {
                        $chValues .= $chRow . ",";
                    }
                    $chValues = substr($chValues, 0, -1);

                    $update['fields'][$key] = $chValues;
                    break;

                case 'image':
                    $file_name = 'account_' . $id . '_' . $key . '_' . time();
                    $resize_type = $fields[$poss]['Default'];
                    $resolution = strtoupper($resize_type) == 'C' ? explode('|', $fields[$poss]['Values']) : $fields[$poss]['Values'];

                    $file_name = $GLOBALS['rlActions']->upload($key, $file_name, $resize_type, $resolution, false, false);
                    if ($file_name) {
                        $update['fields'][$key] = $file_name;

                        /* unlink old image */
                        $image_name = $this->getOne($fields[$poss]['Key'], "`ID` = '{$account_id}'", 'accounts');
                        unlink(RL_FILES . $image_name);
                    }
                    break;

                case 'file':
                    $file_name = 'account_' . $id . '_' . $key . '_' . time();
                    $resize_type = $fields[$poss]['Default'];
                    $resolution = strtoupper($resize_type) == 'C' ? explode('|', $fields[$poss]['Values']) : $fields[$poss]['Values'];

                    $file_name = $GLOBALS['rlActions']->upload($key, $file_name, false, false, false, false);
                    if ($file_name) {
                        $update['fields'][$key] = $file_name;

                        /* unlink old image */
                        $image_name = $this->getOne($fields[$poss]['Key'], "`ID` = '{$account_id}'", 'accounts');
                        unlink(RL_FILES . $image_name);
                    }
                    break;

                case 'accept':
                    $update['fields'][$key] = $account_data[$key];
                    break;
            }
        }

        /* get coordinates by address request */
        $this->geocodeLocation($location, $update['fields']);

        // update location of listings on map
        $this->accountAddressUpdateListings($account_info['ID'], $update['fields'], $account_info);

        /**
         * @since 4.9.0 - Removed second $content parameter
         * @since 4.0.2
         */
        $GLOBALS['rlHook']->load('phpEditAccountBeforeUpdate', $update);

        /* save new data */
        $result = $GLOBALS['rlActions']->updateOne($update, 'accounts', $html_fields);

        if (!defined('REALM') || (defined('REALM') && REALM != 'admin')) {
            /* update session data */
            $sql = "SELECT `T1`.*, `T2`.`Abilities`, `T2`.`ID` AS `Type_ID`, `T2`.`Own_location`, `T2`.`Page` AS `Own_page`  ";
            $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
            $sql .= "WHERE `T1`.`ID` = '{$account_id}' AND `T1`.`Status` <> 'trash'";
            $account = $this->getRow($sql);

            /* check abilities */
            $abilities = explode(',', $account['Abilities']);
            $abilities = empty($abilities[0]) ? false : $abilities;
            $account['Abilities'] = $abilities;

            unset($account['Password_hash'], $account['Password_tmp'], $account['Confirm_code']);

            $account['Password'] = md5($account['Password']);
            $account['Full_name'] = $account['First_name'] || $account['Last_name'] ? $account['First_name'] . ' ' . $account['Last_name'] : $account['Username'];

            $_SESSION['account'] = $account;
        }

        return $result;
    }

    /**
     * User login if remember me cookie exists
     *
     * @since 4.5.1
     *
     * @return bool
     */
    public function loginIfRemember()
    {
        global $config;

        if (!$config['remember_me'] || $GLOBALS['ifRememberWasUsed']) {
            return false;
        }

        // force function to be used just one time to avoid recursive calls
        $GLOBALS['ifRememberWasUsed'] = true;

        if (!$_SESSION['account']['ID'] && $_COOKIE['rmc']) {
            $GLOBALS['rlValid']->sql($_COOKIE['rmc']);

            $tokens = explode(':', $_COOKIE['rmc']);
            $selector = $tokens[0];

            $sql = "SELECT * FROM `{db_prefix}auth_tokens` WHERE `Selector` = '" . $tokens[0] . "'";
            $auth_user = $this->getRow($sql);

            $token = crypt($tokens[1], '$5$' . $config['security_key'] . '$');

            if ($auth_user && hash_equals($token, $auth_user['Token'])) {
                $account = $this->getAccountBaseInfo((int) $auth_user['Account_ID'], function(&$sql) {
                    $GLOBALS['rlHook']->load('loginIfRememberSql', $sql);
                });

                $abilities = explode(',', $account['Abilities']);
                $abilities = empty($abilities[0]) ? false : $abilities;

                unset($account['Password_hash'], $account['Password_tmp'], $account['Confirm_code']);

                $account['Password'] = md5($account['Password']);
                $account['Full_name'] = $account['First_name'] || $account['Last_name'] ? $account['First_name'] . ' ' . $account['Last_name'] : $account['Username'];

                if ($config['membership_module'] && $account['Plan_ID']) {
                    $this->loadClass('MembershipPlan');
                    $account['plan'] = $GLOBALS['rlMembershipPlan']->getPlan((int) $account['Plan_ID'], true, $account);

                    $expiration_date = strtotime($account['Pay_date']) + ((int) $account['plan']['Plan_period'] * 86400);
                    $account['Status'] = time() > $expiration_date ? 'expired' : $account['Status'];
                }
                $account['Abilities'] = $abilities;
                $_SESSION['account'] = $account;

                /* renew remember me token */
                $new_token = $this->generateHash(10, 'password');
                $db_token = crypt($new_token, '$5$' . $config['security_key'] . '$');

                $rmc = $selector . ":" . $new_token;

                $cookie_period = 31556952; //one year
                $this->createCookie('rmc', $rmc, time() + $cookie_period);

                $sql = "UPDATE `{db_prefix}auth_tokens` SET `Token` = '{$db_token}', ";
                $sql .= "`Expires` = DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE `Selector` = '{$selector}'";
                $this->query($sql);
                /* renew remember me token end */

                return true;
            } elseif ($auth_user) {
                // possible hack attempt, clear all cookies
                if (isset($_SERVER['HTTP_COOKIE'])) {
                    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                    foreach ($cookies as $cookie) {
                        $parts = explode('=', $cookie);
                        $name = trim($parts[0]);
                        $this->eraseCookie($name);
                    }
                }
            }
        }

        return false;
    }

    /**
     * Login of account
     *
     * @param string $username    - Account username
     * @param string $password    - Account password
     * @param bool   $direct      - Allow login by MD5 password
     * @param bool   $remember_me - Remember user on this computer
     *
     * @return bool|array - True or errors
     */
    public function login($username = false, $password = false, $direct = false, $remember_me = false)
    {
        global $config, $reefless, $lang; //sql removed in 4.4.1 (was deprecated in 4.1.0)

        $incorrect_auto_phrase = $config['account_login_mode'] == 'email' ? 'notice_incorrect_auth_email' : 'notice_incorrect_auth';

        /* empty data return */
        if (empty($username) || empty($password)) {
            $errors[] = $GLOBALS['lang'][$incorrect_auto_phrase];
            return $errors;
        }

        /* login attempts control - error and exit */
        if ($reefless->attemptsLeft <= 0 && $config['security_login_attempt_user_module']) {
            $errors[] = str_replace('{period}', '<b>' . $config['security_login_attempt_user_period'] . '</b>', $lang['login_attempt_error']);
            return $errors;
        }

        $GLOBALS['rlValid']->sql($username);
        $errors = array();

        $account = $this->getAccountBaseInfo($username, function(&$sql) {
            $GLOBALS['rlHook']->load('loginSql', $sql);
        });

        $verified = FLSecurity::verifyPassword($password, $account['Password'], $direct);

        /* login attempts control - save attempts */
        if ($config['security_login_attempt_user_module']) {
            $insert = array(
                'IP'        => $this->getClientIpAddress(),
                'Date'      => 'NOW()',
                'Status'    => $account && $verified ? 'success' : 'fail',
                'Interface' => 'user',
                'Username'  => $username,
            );

            $this->loadClass('Actions');
            $GLOBALS['rlActions']->insertOne($insert, 'login_attempts');

            //set message
            if (!$account || !$verified) {
                $message = $lang['rl_logging_error'];
                $reefless->loginAttempt();

                /* login attempts control - show warning */
                if ($reefless->attempts > 0) {
                    $message .= '<br />' . $reefless->attemptsMessage;
                }

                $errors[] = $message;
                $this->messageType = 'alert';
            }
        }

        /* check login request */
        if ($account && $verified) {
            /**
             * @since 4.7.1 - $errors, $direct added
             * @since 4.0.2
             */
            $GLOBALS['rlHook']->load('phpLoginValidation', $errors, $account, $password, $direct);

            if ($account['Status'] != 'active' && $account['Status'] != 'expired' && $account['Type_status'] == 'active') {
                $errors[] = $lang['notice_account_approval'];
                $this->messageType = 'alert';
            } elseif ($account['Type_status'] != 'active') {
                $errors[] = $lang['notice_account_type_approval'];
                $this->messageType = 'alert';
            } elseif ($new_hash = FLSecurity::rehashIfNecessary($account['Password'], $password)) {
                $sql = "UPDATE `{db_prefix}accounts` SET `Password` = '{$new_hash}' WHERE `ID` = '{$account['ID']}' LIMIT 1";
                if ($this->query($sql)) {
                    $account['Password'] = $new_hash;
                }
            }
        } else {
            $errors[] = $lang[$incorrect_auto_phrase];
        }

        if (empty($errors)) {
            /* check abilities */
            $abilities = explode(',', $account['Abilities']);
            $abilities = empty($abilities[0]) ? false : $abilities;

            /* do not use this data in future please */
            $_SESSION['id'] = $account['ID'];
            $_SESSION['username'] = $account['Username'];
            $_SESSION['password'] = md5($account['Password']);
            $_SESSION['type'] = $account['Type'];
            $_SESSION['type_id'] = $account['Type_ID'];
            $_SESSION['abilities'] = $abilities;

            unset($account['Password_hash'], $account['Password_tmp'], $account['Confirm_code']);

            $account['Password'] = md5($account['Password']);
            $account['Full_name'] = trim($account['First_name'] || $account['Last_name'] ? $account['First_name'] . ' ' . $account['Last_name'] : $account['Username']);

            // get membership plan
            if ($GLOBALS['config']['membership_module'] && $account['Plan_ID']) {
                $this->loadClass('MembershipPlan');
                $account['plan'] = $GLOBALS['rlMembershipPlan']->getPlan((int) $account['Plan_ID'], true, $account);

                $expiration_date = strtotime($account['Pay_date']) + ((int) $account['plan']['Plan_period'] * 86400);
                $account['Status'] = time() > $expiration_date && $account['plan']['Plan_period'] > 0 ? 'expired' : $account['Status'];
            }

            /* use this only */
            $account['Abilities'] = $abilities;
            $_SESSION['account'] = $account;

            $GLOBALS['rlHook']->load('phpLoginSaveSessionData', $username, $password); // from v4.0.2
            $this->synchronizeFavorites();

            /* remember me */
            if ($config['remember_me'] && $remember_me) {
                $selector = $this->generateHash(10, 'password');
                $token = $this->generateHash(10, 'password');

                $db_token = crypt($token, '$5$' . $config['security_key'] . '$');

                $rmc = $selector . ":" . $token;

                $cookie_period = 31556952;
                $this->createCookie('rmc', $rmc, time() + $cookie_period);

                $sql = "INSERT INTO `{db_prefix}auth_tokens` ";
                $sql .= "(`Selector`, `Token`, `Account_ID`, `Expires`) VALUES ";
                $sql .= "('{$selector}', '{$db_token}', '{$account['ID']}', DATE_ADD(NOW(), INTERVAL 1 YEAR))";

                $this->query($sql);
            }
            /* remember me end */

            $this->assignMessagesByVisitorHash($account['ID']);

            return true;
        } else {
            return $errors;
        }
    }

    /**
     * is account Loged in
     *
     * @return bool
     **/
    public function isLogin()
    {
        $this->loginIfRemember();

        $username = $GLOBALS['rlValid']->xSql($_SESSION['account']['Username']);
        $password = $_SESSION['account']['Password'];

        if (!$username || !$password) {
            return false;
        }

        // get account information
        $account_password = $this->getOne('Password', "`Username` = '{$username}' AND (`Status` = 'active' OR `Status` = 'expired') ", 'accounts');
        $success = (bool) ($account_password && md5($account_password) == $password);

        $GLOBALS['rlHook']->load('phpIsLogin', $account_password, $username, $success); // $username, $success > v4.3

        if ($success) {
            return true;
        }

        if ($_SESSION['account']) {
            unset($_SESSION['account']);
        }

        return false;
    }

    /**
     * detect admin side and admin user
     *
     * @since 4.5.1
     *
     * @return boolean
     */
    public function isAdmin()
    {
        return (bool) defined('REALM') && REALM == 'admin';
    }

    /**
     * Account logout process (session/data destroying and redirect to previous page)
     */
    public function logOut()
    {
        global $pages, $config, $reefless;

        session_destroy();
        session_regenerate_id();

        if ($_COOKIE['rmc']) {
            $GLOBALS['rlValid']->sql($_COOKIE['rmc']);
            $tokens = explode(':', $_COOKIE['rmc']);
            $selector = $tokens[0];
            $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}auth_tokens` WHERE `Selector` = '{$selector}'");
            $reefless->eraseCookie('rmc');
        }

        $GLOBALS['rlHook']->load('phpLogOut');

        if ($_SERVER['HTTP_REFERER']) {
            $reefless->referer('logout');
        } else {
            $reefless->redirect(null, $reefless->getPageUrl('login', false, '', 'logout'));
        }
    }

    /**
     * Deleting account file
     *
     * @package xAjax
     *
     * @param string $key        - Field name
     * @param int    $account_id - Account ID
     * @param string $dom        - Dom element
     */
    public function ajaxDelAccountFile($key = '', $account_id = 0, $dom = '')
    {
        global $_response, $account_info, $rlDb;

        $account_id = intval($this->isAdmin() ? $account_id : $account_info['ID']);

        if (!$account_id || !$key) {
            return;
        }

        $GLOBALS['rlValid']->sql($key);
        if ($file = $rlDb->getOne($key, "`ID` = {$account_id}", 'accounts')) {
            /**
             * @since 4.5.2 - Added parameters $key, $account_id
             * @since 4.0.2
             */
            $GLOBALS['rlHook']->load('phpAjaxDelAccountFileBeforeUpdate', $key, $account_id);

            // removing of the account thumbnail
            if ($key == 'Photo') {
                Profile::deleteThumbnail($account_id);
            } else {
                $rlDb->query("UPDATE `{db_prefix}accounts` SET `{$key}` = '' WHERE `ID` = {$account_id} LIMIT 1");
                unlink(RL_FILES . $file);
            }

            $_response->script("$('#{$dom}').slideUp(); $('#{$dom}').next().fadeIn();");
        }

        return $_response;
    }

    /**
     * Change account password
     *
     * @package xAjax
     *
     * @param string $current - Current password
     * @param string $new     - New password
     * @param string $repeat  - New password repeat
     */
    public function ajaxChangePass($current, $new, $repeat)
    {
        global $_response, $new_password, $lang, $account_info, $rlValid, $rlDb, $rlHook;

        $new_password = $new;
        $errors = [];

        if (defined('IS_LOGIN')) {
            $rlValid->sql($current);
            $rlValid->sql($new);
            $rlValid->sql($repeat);

            // Check current password
            $where = ['ID' => $account_info['ID'], 'Status' => 'active'];
            $check_current = $rlDb->fetch(['Password'], $where, null, null, 'accounts', 'row');

            if (!FLSecurity::verifyPassword($current, $check_current['Password'])) {
                $errors[] = $GLOBALS['lang']['notice_incorrect_current_pass'];
            }

            // Check passwords length
            if (strlen($new) < 3) {
                $errors[] = str_replace(
                    '{field}',
                    '<span class="field_error">"' . $lang['new_password'] . '"</span>',
                    $lang['notice_reg_length']
                );
            }

            if (strlen($repeat) < 3) {
                $errors[] = str_replace(
                    '{field}',
                    '<span class="field_error">"' . $lang['password_repeat'] . '"</span>',
                    $lang['notice_reg_length']
                );
            }

            // Check passwords match
            if ($repeat != $new) {
                $errors[] = $GLOBALS['lang']['notice_pass_bad'];
            }

            $rlHook->load('phpAjaxChangePassCheckErrors', $new, $errors); // from v4.0.2

            if (empty($errors)) {
                $_response->script("$('#change_password').val('{$lang['change']}')");

                $rlHook->load('phpAjaxChangePassBeforeUpdate', $new); // from v4.0.2

                $hash = FLSecurity::cryptPassword($new);
                $update = $rlDb->query(
                    "UPDATE `{db_prefix}accounts` SET `Password` = '{$hash}'
                    WHERE `ID` = '{$account_info['ID']}' LIMIT 1"
                );

                if ($update) {
                    $account_info['Password'] = $_SESSION['password'] = $_SESSION['account']['Password'] = md5($hash);

                    if ($GLOBALS['config']['remember_me'] && $_COOKIE['rmc']) {
                        $tokens   = explode(':', Valid::escape($_COOKIE['rmc']));
                        $selector = $tokens[0];

                        // Clear all other saved sessions
                        if ($selector && $account_info['ID']) {
                            $rlDb->query(
                                "DELETE FROM `{db_prefix}auth_tokens`
                                WHERE `Selector` <> '{$selector}' AND `Account_ID` = {$account_info['ID']}");
                        }
                    }

                    $rlHook->load('accountChangePassword');

                    $_response->script("$('#current_password, #new_password, #password_repeat').attr('value', '');");
                    $_response->script("printMessage('notice', '{$lang['changes_saved']}');");
                }
            } else {
                $error_content = '<ul>';
                foreach ($errors as $error) {
                    $error_content .= "<li>" . $error . "</li>";
                }
                $error_content .= '</ul>';

                $_response->script("printMessage('error', '{$error_content}');");
            }
        }
        $_response->script("$('#change_password').val('{$lang['change']}')");

        return $_response;
    }

    /**
     * Get accounts by char
     *
     * @param string $char     - Start character
     * @param int    $perPage  - Items per page
     * @param int    $page     - Current page
     * @param array  $typeInfo - Account type info
     * @param array  $sorting
     * @param string $orderField
     * @param string $orderType
     *
     * @return array - List of accounts
     */
    public function getDealersByChar($char = false, $perPage = false, $page = false, $typeInfo = false, &$sorting = [], $orderField = '', $orderType = '')
    {
        global $alphabet, $rlHook;

        $alphabetic_field = $typeInfo['Alphabetic_field'];

        $perPage = (int) $perPage;
        $start = $page > 1 ? ($page - 1) * $perPage : 0;

        $char = $char == '0-9' ? '[0-9]' : $char;
        $char = 0 === array_search($char, $alphabet) ? false : $char;
        $GLOBALS['rlValid']->sql($char);

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Thumb_width`, `T2`.`Thumb_height`, ";

        $rlHook->load('accountsGetDealersByCharSqlSelect', $sql, $char); //4.1.1

        $sql .= "`Listings_count` FROM `{db_prefix}accounts` AS `T1` ";

        $rlHook->load('accountsGetDealersByCharSqlJoin', $sql); //4.1.1

        $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
        $sql .= "WHERE ";

        if ($char) {
            $sql .= '( ';
            if ($alphabetic_field) {
                $sql .= "`T1`.`{$alphabetic_field}` REGEXP '^{$char}+' ";

                if (function_exists('mb_detect_encoding')
                    && mb_detect_encoding($char) != 'ASCII'
                    && function_exists('mb_strtolower')
                ) {
                    $char = mb_strtolower($char);
                    $sql .= "OR `T1`.`{$alphabetic_field}` REGEXP '^{$char}+' ";
                }
            } else {
                $sql .= "`T1`.`Username` REGEXP '^{$char}+' ";

                if (function_exists('mb_detect_encoding')
                    && mb_detect_encoding($char) != 'ASCII'
                    && function_exists('mb_strtolower')
                ) {
                    $char = mb_strtolower($char);
                    $sql .= "OR `T1`.`Username` REGEXP '^{$char}+' ";
                }
            }
            $sql .= ') ';
        } else {
            $sql .= '1 ';
        }

        $sql .= "AND `T2`.`Key` = '{$typeInfo['Key']}' AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";

        $rlHook->load('accountsGetDealersByCharSqlWhere', $sql); //4.1.1

        // Order handler
        if ($orderField && $orderType) {
            $sql .= 'ORDER BY ';
            switch ($sorting[$orderField]['Key']) {
                case 'alphabet':
                    $sql .= $alphabetic_field ? "`T1`.`{$alphabetic_field}` " : '`T1`.`Username` ';
                    break;

                case 'Listings_count':
                    $orderType = 'desc';

                default:
                    $sql .= "`{$sorting[$orderField]['Key']}` ";
                    break;
            }

            $sql .= strtoupper($orderType) . ' ';
        } else {
            $sql .= 'ORDER BY `Date` DESC ';
        }

        $rlHook->load('accountsGetDealersByCharSqlOrder', $sql); // > 4.2.0
        $sql .= "LIMIT {$start}, {$perPage}";

        $rlHook->load('accountsGetDealersByCharSql', $sql); // 4.1.1

        $dealers = $this->getAll($sql);

        $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc_alphabet = $calc['calc'];

        // Get short form fields
        $fields = $this->getFormFields($typeInfo['ID']);

        $agencies = new Agencies();

        foreach ($dealers as $key => $value) {
            foreach ($fields as $fKey => $field) {
                if ($field['Condition'] == 'isUrl' || $field['Condition'] == 'isEmail') {
                    $fields[$fKey]['value'] = $dealers[$key][$fKey];
                } else {
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue($field, $value[$fKey], 'account', $value['ID']);

                    /**
                     * Company value adaptation
                     *
                     * @since 4.6.2
                     * @todo - To be removed after "Account Title form" feature creation
                     */
                    if ($field['Key'] == 'company_name') {
                        $dealers[$key]['company_name'] = $fields[$fKey]['value'];
                    }
                }
            }

            $GLOBALS['rlMembershipPlan']->fakeValues($fields);

            $dealers[$key]['Full_name'] = $value['First_name'] || $value['Last_name']
            ? trim($value['First_name'] . ' ' . $value['Last_name'])
            : $value['Username'];
            $dealers[$key]['fields'] = $fields;
            $dealers[$key]['Type_name'] = $GLOBALS['lang']['account_types+name+' . $value['Type']];
            $dealers[$key]['Personal_address'] = Profile::getPersonalAddress($value, $typeInfo);

            if ($agencies->isAgency($value)) {
                $dealers[$key]['Agents_count']   = $agencies->getAgentsCount((int) $value['ID']);
                $dealers[$key]['Listings_count'] += $agencies->getAgentsListingsCount((int) $value['ID']);
            }
        }

        return $dealers;
    }

    /**
     * Search dealers
     *
     * @param array $data     - Search data
     * @param array $form     - Search form
     * @param int   $perPage  - Items per page
     * @param int   $page     - Current page
     * @param array $typeInfo - Account type info
     *
     * @return array - List of dealers accounts
     */
    public function searchDealers($data = false, $form = false, $perPage = 10, $page = false, $typeInfo = false)
    {
        global $rlHook;

        if (!$data || !$form) {
            return [];
        }

        $perPage = (int) $perPage;
        $start = $page > 1 ? ($page - 1) * $perPage : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.*, `T2`.`Thumb_width`, `T2`.`Thumb_height`, ";

        $rlHook->load('accountsSearchDealerSqlSelect', $sql, $data); //4.1.1

        $sql .= "`T1`.`Listings_count` ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";

        $rlHook->load('accountsSearchDealerSqlJoin', $sql); //4.1.1

        $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' ";

        if ($typeInfo && $typeInfo['Key']) {
            $sql .= "AND `T2`.`Key` = '{$typeInfo['Key']}' ";
        }

        foreach ($form as $field) {
            $fKey = $field['Key'];
            $f = $GLOBALS['rlValid']->xSql($data[$fKey]);

            if (!empty($f)) {
                switch ($field['Type']) {
                    case 'mixed':
                        if ($f['df']) {
                            $sql .= "AND LOCATE('{$f['df']}', `T1`.`" . $fKey . "`) > 0 ";
                        }
                    case 'price':
                        if ($f['currency']) {
                            $sql .= "AND LOCATE('{$f['currency']}', `T1`.`" . $fKey . "`) > 0 ";
                        }
                    case 'unit':
                        if ($f['unit']) {
                            $sql .= "AND LOCATE('{$f['unit']}', `T1`.`" . $fKey . "`) > 0 ";
                        }
                    case 'number':
                        if ((int) $f['from']) {
                            $sql .= "AND ROUND(`T1`.`{$fKey}`) >= '" . intval($f['from']) . "' ";
                        }
                        if ((int) $f['to']) {
                            $sql .= "AND ROUND(`T1`.`{$fKey}`) <= '" . intval($f['to']) . "' ";
                        }
                        break;

                    case 'text':
                        if (is_array($f)) {
                            // plugin handler
                        } else {
                            $sql .= "AND `T1`.`{$fKey}` LIKE '%" . $f . "%' ";
                        }
                        break;

                    case 'date':
                        if ($field['Default'] == 'single') {
                            if ($f['from']) {
                                $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}`) >= UNIX_TIMESTAMP('" . $f['from'] . "') ";
                            }
                            if ($f['to']) {
                                $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}`) <= UNIX_TIMESTAMP('" . $f['to'] . "') ";
                            }
                        } elseif ($field['Default'] == 'multi') {
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}`) <= UNIX_TIMESTAMP('" . $f . "') ";
                            $sql .= "AND UNIX_TIMESTAMP(`T1`.`{$fKey}_multi`) >= UNIX_TIMESTAMP('" . $f . "') ";
                        }
                        break;

                    case 'select':
                        if ($field['Condition'] == 'years') {
                            if ($f['from']) {
                                $sql .= "AND `T1`.`{$fKey}` >= '" . (int) $f['from'] . "' ";
                            }
                            if ($f['to']) {
                                $sql .= "AND `T1`.`{$fKey}` <= '" . (int) $f['to'] . "' ";
                            }
                        } else {
                            $sql .= "AND `T1`.`{$fKey}` = '" . $f . "' ";
                        }
                        break;

                    case 'bool':
                        if ($f == 'on') {
                            $sql .= "AND `T1`.`{$fKey}` = '1' ";
                        } else {
                            $sql .= "AND `T1`.`{$fKey}` = '0' ";
                        }
                        break;

                    case 'radio':
                        $sql .= "AND `T1`.`{$fKey}` = '" . $f . "' ";
                        break;

                    case 'checkbox':
                        unset($f[0]);
                        if (!empty($f)) {
                            $sql .= "AND (";
                            foreach ($f as $fI => $fV) {
                                $sql .= "FIND_IN_SET('" . $f[$fI] . "', `T1`.`{$fKey}`) > 0 OR ";
                            }
                            $sql = substr($sql, 0, -3);
                            $sql .= ") ";
                        }
                        break;

                    case 'phone':
                        if (!empty($f['code']) || !empty($f['area']) || !empty($f['number']) || !empty($f['ext'])) {
                            $sql .= "AND (`T1`.`{$fKey}` <> '' ";

                            if (!empty($f['code'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%c:{$f['code']}%' ";
                            }

                            if (!empty($f['area'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%a:{$f['area']}%' ";
                            }

                            if (!empty($f['number'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%n:{$f['number']}%' ";
                            }

                            if (!empty($f['ext'])) {
                                $sql .= "AND `T1`.`{$fKey}` LIKE '%e:{$f['ext']}%' ";
                            }

                            $sql .= ") ";
                        }
                        break;
                }
            }
        }

        $rlHook->load('accountsSearchDealerSqlWhere', $sql); //4.1.1

        $sql .= "ORDER BY ";
        if ($data['sort_by'] && $form[$data['sort_by']]) {
            switch ($form[$data['sort_by']]['Type']) {
                case 'price':
                case 'unit':
                case 'mixed':
                    $sql .= "ROUND(`T1`.`{$form[$data['sort_by']]['Key']}`) " . strtoupper($data['sort_type']) . " ";
                    break;

                case 'select':
                    if ($form[$data['sort_by']]['Key'] == 'Category_ID') {
                        $sql .= "`T3`.`Key` " . strtoupper($data['sort_type']) . " ";
                    } elseif ($form[$data['sort_by']]['Key'] == 'Listing_type') {
                        $sql .= "`T3`.`Type` " . strtoupper($data['sort_type']) . " ";
                    } else {
                        $sql .= "`T1`.`{$form[$data['sort_by']]['Key']}` " . strtoupper($data['sort_type']) . " ";
                    }
                    break;

                default:
                    $sql .= "`T1`.`{$form[$data['sort_by']]['Key']}` " . strtoupper($data['sort_type']) . " ";
                    break;
            }
        } else {
            $sql .= "`Date` " . strtoupper($data['sort_type']) . " ";
        }

        $sql .= "LIMIT {$start}, {$perPage}";

        $rlHook->load('accountsSearchDealerSql', $sql); //4.1.1

        $dealers = $this->getAll($sql);

        $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        /* get short form fields */
        if (!$typeInfo && $dealers && $dealers[0] && $dealers[0]['Type']) {
            $typeInfo = $GLOBALS['rlAccountTypes']->types[$dealers[0]['Type']];
        }

        $fields = $this->getFormFields($typeInfo['ID']);

        $agencies = new Agencies();

        foreach ($dealers as $key => $value) {
            foreach ($fields as $fKey => $fValue) {
                if ($field['Condition'] == 'isUrl' || $field['Condition'] == 'isEmail') {
                    $fields[$fKey]['value'] = $dealers[$key][$fKey];
                } else {
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue($fValue, $value[$fKey], 'account', $value['ID']);

                    /**
                     * Company value adaptation
                     *
                     * @since 4.7.0
                     * @todo - To be removed after "Account Title form" feature creation
                     */
                    if ($fValue['Key'] == 'company_name') {
                        $dealers[$key]['company_name'] = $fields[$fKey]['value'];
                    }
                }
            }

            $GLOBALS['rlMembershipPlan']->fakeValues($fields);

            $dealers[$key]['Full_name'] = $value['First_name'] || $value['Last_name']
            ? trim($value['First_name'] . ' ' . $value['Last_name'])
            : $value['Username'];
            $dealers[$key]['fields'] = $fields;
            $dealers[$key]['Type_name'] = $GLOBALS['lang']['account_types+name+' . $value['Type']];
            $dealers[$key]['Personal_address'] = Profile::getPersonalAddress($value, $typeInfo);

            if ($agencies->isAgency($value)) {
                $dealers[$key]['Agents_count']   = $agencies->getAgentsCount((int) $value['ID']);
                $dealers[$key]['Listings_count'] += $agencies->getAgentsListingsCount((int) $value['ID']);
            }
        }

        return $dealers;
    }

    /**
     * get account form fields
     *
     * @param int $id - account type ID
     * @param string $table - table
     *
     * @return categories fields list
     **/
    public function getFormFields($id = false, $table = 'account_short_form')
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Contact`, `T2`.`Condition`, CONCAT('account_fields+name+', `T2`.`Key`) AS `pName`, ";
        $sql .= "`T2`.`Details_page`, `T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Hidden` ";
        $sql .= "FROM `" . RL_DBPREFIX . $table . "` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}account_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Category_ID` = '{$id}' ORDER BY `T1`.`Position`";

        $fields = $this->getAll($sql, 'Key');

        return $fields;
    }

    /**
     * Build account search form
     * @param  int   $id Account type ID
     * @return array     Search form with fields info
     */
    public function buildSearch($id = false)
    {
        $id = (int) $id;

        if (!$id) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Key`, `T1`.`Type`, `T1`.`Default`, `T1`.`Values`, `Condition`, `Required`, ";
        $sql .= "`Map`, `Opt1`, `Opt2`, CONCAT('account_fields+name+', `T1`.`Key`) AS `pName`, `Autocomplete` ";
        $sql .= "FROM `{db_prefix}account_fields` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}account_search_relations` AS `T2` ON `T1`.`ID` = `T2`.`Field_ID` ";
        $sql .= "WHERE `T2`.`Category_ID` = {$id} AND `T1`.`Status` = 'active' ORDER BY `T2`.`Position`";
        $fields = $this->getAll($sql, 'Key');

        $fields = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'account_fields');
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name', 'default'));

        return $fields;
    }

    /**
     * Get account type details
     *
     * @param string $key - Account type key
     * @return array      - Form information
     */
    public function getTypeDetails($key = '')
    {
        if (!$key) {
            return [];
        }

        $type = $this->fetch('*', ['Key' => $key, 'Status' => 'active'], null, 1, 'account_types', 'row');
        $type = $GLOBALS['rlLang']->replaceLangKeys($type, 'account_types', ['name', 'desc']);

        return $type;
    }

    /**
     * Send "edit e-mail account" confirmation
     *
     * @return array - Form information
     */
    public function sendEditEmailNotification($account_id = false, $new_email = false)
    {
        global $pages, $config;

        if (!$account_id = (int) $account_id) {
            return [];
        }

        $confirm_code = md5(mt_rand());

        /* save code */
        $save_code_sql = "UPDATE `{db_prefix}accounts` SET `Confirm_code` = '{$confirm_code}' ";
        $save_code_sql .= "WHERE `ID` = {$account_id} LIMIT 1";
        $this->query($save_code_sql);

        // create activation link
        $activation_link = SEO_BASE;
        $activation_link .= $config['mod_rewrite'] ? "{$pages['my_profile']}.html?key=" : "?page={$pages['my_profile']}&amp;key=";
        $activation_link .= $confirm_code;
        $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

        $this->loadClass('Mail');

        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('account_edit_email');
        $mail_tpl['body'] = str_replace(
            array('{activation_link}', '{name}'),
            array($activation_link, $GLOBALS['account_info']['Full_name']),
            $mail_tpl['body']
        );

        $GLOBALS['rlMail']->send($mail_tpl, $new_email);
    }

    /**
     * delete account preparation
     *
     * @package ajax
     *
     * @param int $id - account id
     *
     **/
    public function ajaxPrepareDeleting($id = false)
    {
        global $_response, $rlSmarty, $rlHook, $delete_details, $lang, $delete_total_items, $config;

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

        /* get account details */
        $account_details = $this->getProfile($id);
        $rlSmarty->assign_by_ref('account_details', $account_details);

        /* check listings */
        $listings = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}listings` WHERE `Account_ID` = {$id} AND `Status` <> 'trash'");

        $delete_details[] = array(
            'name'  => $lang['listings'],
            'items' => $listings['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;username=' . $account_details['Username'],
        );
        $delete_total_items += $listings['Count'];

        /* check custom categories */
        $custom_categories = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}tmp_categories` WHERE `Account_ID` = {$id}");
        $delete_details[] = array(
            'name'  => $lang['admin_controllers+name+custom_categories'],
            'items' => $custom_categories['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=custom_categories',
        );
        $delete_total_items += intval($custom_categories['Count']);

        $rlHook->load('deleteAccountDataCollection');

        $rlSmarty->assign_by_ref('delete_details', $delete_details);

        if ($delete_total_items) {
            $tpl = 'blocks' . RL_DS . 'delete_preparing_account.tpl';
            $_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("
                $('input[name=new_account]').rlAutoComplete({add_id: true});
                $('#delete_block').slideDown();
            ");
        } else {
            $phrase = $config['trash'] ? str_replace('{username}', $account_details['Username'], $lang['notice_drop_empty_account']) : str_replace('{username}', $account_details['Username'], $lang['notice_delete_empty_account']);
            $_response->script("
                $('#delete_block').slideUp();
                rlPrompt('{$phrase}', 'xajax_deleteAccount', {$account_details['ID']});
            ");
        }

        return $_response;
    }

    /**
     * mass delete accounts
     *
     * @package ajax
     *
     * @param int $id - account IDs
     * @param string $action - activate/approve
     *
     **/
    public function ajaxMassActions($ids = false, $action = false)
    {
        global $_response, $rlActions, $lang, $config, $pages;

        $ids = explode('|', $ids);

        if (!$ids || !in_array($action, array('activate', 'approve', 'resend_link'))) {
            return $_response;
        }

        $this->loadClass('Mail');
        $set_status = $action == 'activate' ? 'active' : 'approval';

        foreach ($GLOBALS['languages'] as $key => $lang_item) {
            $tmp_mail_tpl_resend[$lang_item['Code']] = $GLOBALS['rlMail']->getEmailTemplate('account_created_incomplete', $lang_item['Code']);
            $tmp_mail_tpl_status[$lang_item['Code']] = $GLOBALS['rlMail']->getEmailTemplate($set_status == 'active' ? 'account_activated' : 'account_deactivated', $lang_item['Code']);
        }

        foreach ($ids as $id) {
            $id = (int) $id;
            $account_info = $this->getProfile($id);
            $account_info['Lang'] = $account_info['Lang'] ? $account_info['Lang'] : $config['lang'];

            /* update status */
            if (in_array($action, array('activate', 'approve'))) {
                if ($account_info['Status'] == $set_status) {
                    continue;
                }

                /* set email notification */
                $mail_tpl_status = $tmp_mail_tpl_status[$account_info['Lang']];
                $mail_tpl_status['body'] = str_replace('{name}', $account_info['Full_name'], $mail_tpl_status['body']);
                $GLOBALS['rlMail']->send($mail_tpl_status, $account_info['Mail']);

                /* update entry */
                $update = array(
                    'fields' => array(
                        'Status' => $set_status,
                    ),
                    'where'  => array(
                        'ID' => $id,
                    ),
                );
                $success = $rlActions->updateOne($update, 'accounts');
            } elseif ($action == 'resend_link') {
                /* resend activation link */
                if ($account_info['Status'] == 'incomplete') {
                    $activation_link = RL_URL_HOME;
                    $activation_link .= $config['mod_rewrite'] ? "{$pages['confirm']}.html?key=" : "?page={$pages['confirm']}&amp;key=";
                    $activation_link .= $account_info['Confirm_code'];
                    $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

                    $mail_tpl_resend = $tmp_mail_tpl_resend[$account_info['Lang']];

                    $find = array(
                        '{activation_link}',
                        '{name}',
                        '{plan_info}'
                    );
                    $replace = array(
                        $activation_link,
                        $account_info['Full_name'],
                        $this->buildPlanDetailsToEmail($account_info['Plan_ID'])
                    );
                    $mail_tpl_resend['body'] = str_replace($find, $replace, $mail_tpl_resend['body']);
                    $GLOBALS['rlMail']->send($mail_tpl_resend, $account_info['Mail']);

                    $resend_links++;
                }
            }
        }

        if (in_array($action, array('activate', 'approve'))) {
            if ($success) {
                $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");
            } else {
                trigger_error("Can not run mass action with accounts (MySQL Fail). Action: {$action}", E_USER_ERROR);
                $GLOBALS['rlDebug']->logger("Can not run mass action with accounts (MySQL Fail). Action: {$action}");
            }
        } elseif ($action == 'resend_link') {
            if ($resend_links) {
                $mess = str_replace('{count}', $resend_links, $lang['resend_activation_link_success']);
                $_response->script("printMessage('notice', '{$mess}')");
            } else {
                $mess = $lang['resend_activation_link_fail'];
                $_response->script("printMessage('alert', '{$mess}')");
            }
        }

        return $_response;
    }

    /**
     * synchronize favorites between cookies and database
     *
     **/
    public function synchronizeFavorites()
    {
        global $account_info, $config;

        if ($_SESSION['account'] && !$account_info) {
            $account_info = $_SESSION['account'];
        }

        if (!$account_info['ID']) {
            return;
        }

        /* synchronize database with cookies */
        if ($account_info['ID'] || $_COOKIE['favorites']) {
            $sql = "SELECT GROUP_CONCAT(`Listing_ID`) as `ids` FROM `{db_prefix}favorites` AS `T1` ";
            $sql .= "JOIN `{db_prefix}listings` AS `T2` ON `T2`.`ID` = `T1`.`Listing_ID` AND `T2`.`Status` = 'active' ";
            $sql .= "WHERE `T1`.`Account_ID` = " . $account_info['ID'];

            $db_favorites = $this->getRow($sql, 'ids');
            $db_favorites_arr = explode(",", $db_favorites);

            if ($_COOKIE['favorites']) {
                $cookie_favorites = explode(",", $_COOKIE['favorites']);

                $k = 0;
                foreach ($cookie_favorites as $key => $id) {
                    if (!in_array($id, $db_favorites_arr)) {
                        $insert[$k]['Account_ID'] = $account_info['ID'];
                        $insert[$k]['Listing_ID'] = $id;
                        $insert[$k]['Date'] = 'NOW()';
                        $insert[$k]['IP'] = $this->getClientIpAddress();
                        $k++;

                        $db_favorites_arr[] = $id;
                    }
                }

                if ($insert) {
                    $GLOBALS['reefless']->loadClass('Actions');
                    $GLOBALS['rlActions']->insert($insert, "favorites");
                }
            }

            $favorites = trim(implode(",", $db_favorites_arr), ",");

            $this->createCookie('favorites', $favorites, time() + ($config['expire_languages'] * 86400));
            $_COOKIE['favorites'] = $favorites;
        }
    }

    /**
     * replaces fields in the tpl with actual values for meta data of account details page
     *
     * @param array $account_type - account_type
     * @param array $account   - account data
     * @param array $type - keywords or description
     *
     **/
    public function replaceAccountMetaFields($account_type = false, $account = false, $type = 'description')
    {
        if ($tpl = $GLOBALS['lang']['account_types+account_meta_' . $type . '+' . $account_type['Key']]) {
            preg_match_all('/\{([^\{]+)\}+/', $tpl, $fields);

            $this->outputRowsMap = 'Key';
            $possible_fields = $GLOBALS['rlValid']->xSql($fields[1]);
            $fields_info = $this->fetch("*", array('Status' => 'active'), "AND FIND_IN_SET(`Key`, '" . implode(",", $possible_fields) . "')", null, 'account_fields');

            foreach ($possible_fields as $key => $field_key) {
                $replacement[$key] = $field_key == 'ID' ? $account[$field_key] : $GLOBALS['rlCommon']->adaptValue($fields_info[$field_key], $account[$field_key], 'account');
                $pattern[$key] = $fields[0][$key];
            }

            $tpl = str_replace($pattern, $replacement, $tpl);

            return $tpl ? $tpl : $GLOBALS['page_info']['meta_' . $type];
        }

        return false;
    }

    /**
     * get step key by step path
     *
     * @param string $path - step path from get
     * @param array $steps - available steps array
     *
     **/
    public function stepByPath(&$steps, $path = false)
    {
        if (!$path) {
            return;
        }
        foreach ($steps as $key => $step) {
            if ($step['path'] == $path) {
                return $key;
            }
        }
    }

    /**
     * upgrade account
     *
     * @param integer $account_id
     * @param integer $plan_id
     * @param boolean $renew
     * @param boolean $new -
     * @return null
     */
    public function upgrade($account_id = false, $plan_id = false, $renew = false, $new = false)
    {
        if (!$account_id || !$plan_id) {
            return;
        }

        $this->loadClass('Actions');
        $this->loadClass('MembershipPlan');

        $plan_id = (int) $plan_id;
        $account_id = (int) $account_id;

        $account_info = $this->fetch('*', array('ID' => $account_id), null, 1, 'accounts', 'row');
        $plan_info = $this->fetch('*', array('ID' => $plan_id), null, 1, 'membership_plans', 'row');
        $account_type = $this->getAccountType($account_info['Type']);

        // get plan services
        $service_ids = explode(',', $plan_info['Services']);
        $sql = "SELECT * FROM `{db_prefix}membership_services` WHERE `ID` = '" . implode("' OR `ID` = '", $service_ids) . "'";
        $services = $this->getAll($sql, 'Key');

        if ($account_info) {
            $upgrade_date = 'IF(UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Plan_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, NOW(), DATE_ADD(`Pay_date`, INTERVAL ' . $plan_info['Plan_period'] . ' DAY))';
            $status = 'active';
            if ($new && !defined('REALM')) {
                if ($account_type['Admin_confirmation']) {
                    $status = 'pending';
                } elseif ($account_type['Email_confirmation']) {
                    $status = 'incomplete';
                }
            }
            $update = array(
                'fields' => array(
                    'Plan_ID'  => $plan_id,
                    'Pay_date' => $upgrade_date,
                    'Featured' => isset($services['featured']) ? 1 : 0,
                    'Status'   => $status,
                ),
                'where'  => array('ID' => $account_id),
            );

            if ($GLOBALS['rlActions']->updateOne($update, 'accounts')) {
                $account_info_update = $this->fetch('*', array('ID' => $account_id), null, 1, 'accounts', 'row');
                if ($_SESSION['account']) {
                    $_SESSION['account']['Payment_status'] = 'paid';
                    $_SESSION['account']['Plan_ID'] = $plan_id;
                    $_SESSION['account']['Pay_date'] = $account_info_update['Pay_date'];
                    $_SESSION['account']['Status'] = $account_info_update['Status'];

                    // get membership plan
                    $this->loadClass('MembershipPlan');
                    $_SESSION['account']['plan'] = $GLOBALS['rlMembershipPlan']->getPlan($plan_id, true, $_SESSION['account']);

                    $expiration_date = strtotime($account_info_update['Pay_date']) + ((int) $_SESSION['account']['plan']['Plan_period'] * 86400);
                    $_SESSION['account']['Status'] = time() > $expiration_date && (int) $_SESSION['account']['plan']['Plan_period'] > 0 ? 'expired' : $account_info_update['Status'];
                }

                // update listings
                if ($account_info['Plan_ID'] != $account_info_update['Plan_ID']) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Plan_ID` = '{$plan_id}', `Status` = 'approval' WHERE `Account_ID` = '{$account_id}' AND `Plan_type` = 'account'";
                    $this->query($sql);
                }

                // activate listings
                if ($plan_info['Advanced_mode']) {
                    // update standard listings
                    $sql = "UPDATE `{db_prefix}listings` SET `Status` = 'active', `Pay_date` = '{$account_info_update['Pay_date']}'
                            WHERE `Account_ID` = '{$account_id}'  AND `Plan_type` = 'account' AND `Status` <> 'trash' AND `Status` <> 'pending' AND (`Featured_ID` <= 0 OR `Featured_ID` = '') AND `Featured_date` IS NULL
                            ORDER BY `Date` DESC" . ($plan_info['Standard_listings'] > 0 ? " LIMIT " . $plan_info['Standard_listings'] : "");
                    $this->query($sql);

                    // update featured listings
                    $sql = "UPDATE `{db_prefix}listings` SET `Status` = 'active', `Pay_date` = '{$account_info_update['Pay_date']}'
                            WHERE `Account_ID` = '{$account_id}'  AND `Plan_type` = 'account' AND `Status` <> 'trash' AND `Status` <> 'pending' AND `Featured_ID` > 0 AND `Featured_date` IS NOT NULL
                            ORDER BY `Date` DESC" . ($plan_info['Featured_listings'] > 0 ? " LIMIT " . $plan_info['Featured_listings'] : "");
                    $this->query($sql);
                } else {
                    $sql = "UPDATE `{db_prefix}listings` SET `Status` = 'active', `Pay_date` = '{$account_info_update['Pay_date']}' WHERE `Account_ID` = '{$account_id}'  AND `Plan_type` = 'account' AND `Status` <> 'trash' AND `Status` <> 'pending' ORDER BY `Date` DESC" . ($plan_info['Listing_number'] > 0 ? " LIMIT " . $plan_info['Listing_number'] : "");
                    $this->query($sql);
                }

                // update featured date
                if ($plan_info['Featured_listing'] || ($plan_info['Advanced_mode'] && $plan_info['Featured_listings'] > 0)) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_ID` = '{$plan_id}', `Featured_date` = '{$account_info_update['Pay_date']}' WHERE `Account_ID` = '{$account_id}' AND `Plan_type` = 'account' AND `Featured_ID` > 0 AND `Featured_date` IS NOT NULL";
                } else {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_ID` = '0', `Featured_date` = '0000-00-00 00:00:00' WHERE `Account_ID` = '{$account_id}' AND `Plan_type` = 'account' AND `Featured_ID` > 0 AND `Featured_date` IS NOT NULL";
                }
                $this->query($sql);

                // update plan using
                $sql = "SELECT * FROM `{db_prefix}listing_packages` WHERE `Account_ID` = '{$account_id}' AND `Plan_ID` = '{$account_info['Plan_ID']}' AND `Type` = 'account' LIMIT 1";
                $plan_using = $this->getRow($sql);

                if ($account_info['Plan_ID'] != $account_info_update['Plan_ID']) {
                    $plan_spent = array();

                    // current plan using
                    $sql = "SELECT * FROM `{db_prefix}listing_packages` ";
                    $sql .= "WHERE `Account_ID` = {$account_id} AND `Plan_ID` = {$plan_id} AND `Type` = 'account' ";
                    $planUsingCurrent = $this->getRow($sql);

                    if ($plan_using) {
                        $plan_info_current = $this->fetch('*', array('ID' => $account_info['Plan_ID']), null, 1, 'membership_plans', 'row');

                        if ($plan_info_current) {
                            $plan_spent['Listing_number'] = $plan_info_current['Listing_number'] == 0
                            ? $plan_info_current['Listing_number']
                            : $plan_info_current['Listing_number'] - (int) $plan_using['Listings_remains'];

                            if ($plan_info['Advanced_mode']) {
                                $plan_spent['Standard_listings'] = $plan_info_current['Standard_listings'] == 0
                                ? $plan_info_current['Standard_listings']
                                : $plan_info_current['Standard_listings'] - (int) $plan_using['Standard_remains'];
                                $plan_spent['Featured_listings'] = $plan_info_current['Featured_listings'] == 0
                                ? $plan_info_current['Featured_listings']
                                : $plan_info_current['Featured_listings'] - (int) $plan_using['Featured_remains'];
                            }

                            if ($plan_info['Advanced_mode'] && !$plan_info_current['Advanced_mode']) {
                                if ($plan_info_current['Featured_listing']) {
                                    $plan_spent['Featured_listings'] = $plan_info_current['Listing_number'] == 0
                                    ? $plan_info_current['Listing_number']
                                    : $plan_info_current['Listing_number'] - (int) $plan_using['Listings_remains'];
                                } else {
                                    $plan_spent['Standard_listings'] = $plan_info_current['Listing_number'] == 0
                                    ? $plan_info_current['Listing_number']
                                    : $plan_info_current['Listing_number'] - (int) $plan_using['Listings_remains'];
                                }
                            }
                        }
                    }

                    if ($planUsingCurrent) {
                        if ($plan_info['Limit'] > 0) {
                            $update = [
                                'fields' => ['Count_used' => $plan_using['Count_used'] + 1],
                                'where' => ['ID' => $plan_using['ID']],
                            ];
                            $this->updateOne($update, 'listing_packages');
                        }
                    } else {
                        $listingNumber = $plan_info['Listing_number'] > $plan_spent['Listing_number']
                        ? $plan_info['Listing_number'] - $plan_spent['Listing_number']
                        : 0;

                        $plan_using_insert = [
                            'Account_ID' => $account_id,
                            'Plan_ID' => $plan_id,
                            'Listings_remains' => $plan_spent['Listing_number'] > 0
                            ? ($plan_info['Listing_number'] > $plan_spent['Listing_number'] ? $listingNumber : 0)
                            : $plan_info['Listing_number'],
                            'Type' => 'account',
                            'Date' => 'NOW()',
                            'IP' => $this->getClientIpAddress(),
                        ];

                        if ($plan_info['Limit'] > 0) {
                            $plan_using_insert['Count_used'] = 1;
                        }
                        if ($plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                            // Standard listings number
                            $slNumber = $plan_info['Standard_listings'] - $plan_spent['Standard_listings'];

                            $plan_using_insert['Standard_remains'] = $plan_spent['Standard_listings'] > 0
                            ? ($plan_info['Standard_listings'] > $plan_spent['Standard_listings'] ? $slNumber : 0)
                            : $plan_info['Standard_listings'];
                        }
                        if ($plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                            // Featured listings number
                            $flNumber = $plan_info['Featured_listings'] - $plan_spent['Featured_listings'];

                            $plan_using_insert['Featured_remains'] = $plan_spent['Featured_listings'] > 0
                            ? ($plan_info['Featured_listings'] > $plan_spent['Featured_listings'] ? $flNumber : 0)
                            : $plan_info['Featured_listings'];
                        }

                        $this->insertOne($plan_using_insert, 'listing_packages');
                    }
                } else {
                    if (!$plan_using && ($renew || $new)) {
                        $plan_using_insert = array(
                            'Account_ID'       => $account_id,
                            'Plan_ID'          => $plan_id,
                            'Listings_remains' => $plan_info['Listing_number'],
                            'Type'             => 'account',
                            'Date'             => 'NOW()',
                            'IP'               => $this->getClientIpAddress(),
                        );
                        if ($plan_info['Limit'] > 0) {
                            $plan_using_insert['Count_used'] = 1;
                        }
                        if ($plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                            $plan_using_insert['Standard_remains'] = $plan_info['Standard_listings'];
                        }
                        if ($plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                            $plan_using_insert['Featured_remains'] = $plan_info['Featured_listings'];
                        }
                        $GLOBALS['rlActions']->insertOne($plan_using_insert, 'listing_packages');
                    } else {
                        if ($plan_info['Limit'] > 0) {
                            $sql = "UPDATE `{db_prefix}listing_packages` SET `Count_used` = `Count_used` + 1 WHERE `Account_ID` = '{$account_id}' AND `Plan_ID` = '{$plan_id}' LIMIT 1";
                            $this->query($sql);
                        }
                    }
                }
            }
        }
    }

    /**
     * initialize registration steps
     *
     * @param array $reg_steps
     */
    public function initRegistrationSteps(&$reg_steps)
    {
        if (!$GLOBALS['config']['membership_module']) {
            unset($reg_steps['plan'], $reg_steps['checkout']);
        }
    }

    /**
     * Get short account details
     *
     * @since 4.9.0 - Added $includeAgencyInfo parameter
     *
     * @param array $seller_info       - Default seller data array
     * @param int   $accountTypeID     - Related account type ID
     * @param bool  $includeAgencyInfo - Add row with agency title and link to profile
     */
    public function getShortDetails(&$seller_info, $accountTypeID = false, $includeAgencyInfo = false)
    {
        $accountTypeID = (int) $accountTypeID;
        $fields = $this->getFormFields($accountTypeID);

        foreach ($fields as &$field) {
            $field['value'] = $GLOBALS['rlCommon']->adaptValue(
                $field,
                $seller_info[$field['Key']],
                'account',
                $seller_info['ID']
            );
        }

        if ($includeAgencyInfo && $seller_info['Agency_ID'] && $seller_info['Agency_Info']) {
            $fields = ['agency_info' => [
                'Key'          => 'agency_info',
                'Type'         => 'text',
                'pName'        => 'agency',
                'Details_page' => '1',
                'value'        => $seller_info['Agency_Info']['Personal_address']
                    ? "<a href='{$seller_info['Agency_Info']['Personal_address']}' "
                        . "target='_blank' "
                        . "title='{$seller_info['Agency_Info']['Full_name']}'>"
                        . $seller_info['Agency_Info']['Full_name']
                        . '</a>'
                    : $seller_info['Agency_Info']['Full_name'],
                ]
            ] + $fields;
        }

        return $fields;
    }

    /**
     * send notification to user after registration
     *
     * @param mixed $account
     * @return null
     */
    public function sendRegistrationNotification(&$account)
    {
        global $config, $pages, $lang;

        if (!$account) {
            return;
        }
        if ($account['Plan_ID']) {
            $plan_details = $this->buildPlanDetailsToEmail($account['Plan_ID']);
        }
        $account_type = $this->getAccountType($account['Type']);
        $this->loadClass('Mail');

        if (defined('SEO_BASE')) {
            $url_base = SEO_BASE;
        } else {
            $url_base = RL_URL_HOME;
            if ($account['Lang']) {
                $url_base .= $account['Lang'] . "/";
            }
        }
        $name = $account['First_name'] || $account['Last_name'] ? trim($account['First_name'] . ' ' . $account['Last_name']) : $account['Username'];

        /**
         * @since 4.5.1
         */
        $GLOBALS['rlHook']->load('phpSendRegistrationNotification', $account_type, $account);

        // prepare email confirmation
        if ($account_type['Email_confirmation']) {
            // create activation link
            $activation_link = $url_base;
            $activation_link .= $config['mod_rewrite'] ? "{$pages['confirm']}.html?key=" : "?page={$pages['confirm']}&amp;key=";
            $activation_link .= $account['Confirm_code'];
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';

            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('account_created_incomplete', $account['Lang']);
            $find = array(
                '{activation_link}',
                '{name}',
                '{plan_info}',
            );
            $replace = array(
                $activation_link,
                $name,
                $plan_details,
            );
            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        } else {
            $mail_tpl_key = $account_type['Admin_confirmation'] ? 'account_created_pending' : 'account_created_active';
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate($mail_tpl_key, $account['Lang']);

            $account_area_link = $url_base;
            $account_area_link .= $config['mod_rewrite'] ? $pages['login'] . '.html' : '?page=' . $pages['login'];
            $account_area_link = '<a href="' . $account_area_link . '">' . $lang['blocks+name+account_area'] . '</a>';

            $find = array(
                '{login}',
                '{password}',
                '{name}',
                '{account_area}',
                '{plan_info}',
            );
            $replace = array(
                $config['account_login_mode'] == 'email' ? $account['Mail'] : $account['Username'],
                ($_SESSION['registration']['profile']['password'] ? $_SESSION['registration']['profile']['password'] : $account['Password']),
                $name,
                $account_area_link,
                $plan_details,
            );
            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        }

        // send e-mail to new user
        $GLOBALS['rlMail']->send($mail_tpl, $account['Mail']);

        // prepare admin notification e-mail
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('account_created_admin');

        $details_link = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&amp;action=view&amp;userid=' . $account['ID'];
        $details_link = '<a href="' . $details_link . '">' . $details_link . '</a>';

        $find = array('{first_name}', '{last_name}', '{username}', '{join_date}', '{status}', '{details_link}', '{plan_info}');
        $replace = array(
            empty($account['First_name']) ? 'Not specified' : $account['First_name'],
            empty($account['Last_name']) ? 'Not specified' : $account['Last_name'],
            $account['Username'],
            date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT)),
            $lang[$account['Status']],
            $details_link,
            $plan_details,
        );
        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

        if ($account_type['Admin_confirmation']) {
            $activation_link = RL_URL_HOME . ADMIN . '/index.php?controller=accounts&amp;action=remote_activation&amp;id=' . $account['ID'] . '&amp;hash=' . md5($this->getOne('Date', "`ID` = '{$account['ID']}'", 'accounts'));
            $activation_link = '<a href="' . $activation_link . '">' . $activation_link . '</a>';
            $mail_tpl['body'] = preg_replace('/(\{if activation is enabled\})(.*)(\{activation_link\})(.*)(\{\/if\})/', '$2 ' . $activation_link . ' $4', $mail_tpl['body']);
        } else {
            $mail_tpl['body'] = preg_replace('/\{if activation is enabled\}(.*)\{\/if\}/', '', $mail_tpl['body']);
        }

        // send e-mail to admin
        $GLOBALS['rlMail']->send($mail_tpl, $config['site_main_email']);
    }

    /**
     * build plan details to E-mail notification
     *
     * @param integer $plan_id
     * @return string
     */
    public function buildPlanDetailsToEmail($plan_id = false)
    {
        if (!$plan_id) {
            return;
        }

        $plan = $GLOBALS['rlMembershipPlan']->getPlan($plan_id);

        if ($plan) {
            if ($plan['Price'] > 0) {
                $price = $GLOBALS['config']['system_currency_position'] == 'before' ? $GLOBALS['config']['system_currency'] . $plan['Price'] : $plan['Price'] . ' ' . $GLOBALS['config']['system_currency'];
            } else {
                $price = $GLOBALS['lang']['free'];
            }
            $date_expired = date(str_replace(array('b', '%'), array('M', ''), RL_DATE_FORMAT), mktime(0, 0, 0, date("m"), date("d") + (int) $plan['Plan_period'], date("Y")));

            $html = "<br />{$GLOBALS['lang']['membership']}<br />" . PHP_EOL;
            $html .= "{$GLOBALS['lang']['name']}: {$plan['name']}<br />" . PHP_EOL;
            $html .= "{$GLOBALS['lang']['price']}: {$price}<br />" . PHP_EOL;
            $html .= "{$GLOBALS['lang']['active_till']}: {$date_expired}<br /><br />" . PHP_EOL;

            return $html;
        }

        return;
    }

    /**
     * get featured accounts
     *
     * @param string $type - account type
     * @param int $limit - accounts limit
     * @param string $block_key - block key
     *
     * @return array - accounts
     **/
    public function getFeatured($type = false, $limit = 4, $block_key = false)
    {
        global $rlValid, $config, $pages, $rlAccountTypes;

        if (!$type) {
            return false;
        }

        // get account type
        $account_type = &$rlAccountTypes->types[$type];

        $rlValid->sql($block_key);
        $rlValid->sql($type);
        $limit = (int) $limit;

        $sql = "SELECT `T1`.* ";
        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "WHERE `T1`.`Featured` = '1' AND `T1`.`Status` = 'active' AND `T1`.`Type` = '{$type}' ";

        if ($this->selectedIDs) {
            $sql .= "AND `T1`.`ID` NOT IN('" . implode("','", $this->selectedIDs) . "') ";
        }

        $GLOBALS['rlHook']->load('accountsModifyWhereFeatured', $sql, $block_key, $limit); // params >= v4.5

        $sql .= "ORDER BY `Last_show` ASC ";
        $sql .= "LIMIT " . $limit;

        $accounts = $this->getAll($sql);

        if (empty($accounts)) {
            return false;
        }

        $GLOBALS['rlHook']->load('accountsAfterSelectFeatured', $sql, $block_key, $accounts); // >= v4.5

        $fields = $this->getFields($account_type['ID'], 'account_short_form');
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name', 'description'));

        foreach ($accounts as &$account) {
            $account['Full_name'] = trim(
                $account['First_name'] || $account['Last_name']
                ? $account['First_name'] . ' ' . $account['Last_name']
                : $account['Username']
            );

            $account['Personal_address'] = Profile::getPersonalAddress($account, $account_type);

            // collect accounts IDs
            $this->selectedIDs[] = $IDs[] = $account['ID'];

            foreach ($fields as &$field) {
                if (empty($account[$field['Key']]) || $field['Type'] == 'accept') {
                    continue;
                }

                $field['value'] = $GLOBALS['rlCommon']->adaptValue($field, $account[$field['Key']], 'account', $account['ID'], null, null, false);
                $account['Fields'][] = $field;
            }

            $GLOBALS['rlMembershipPlan']->fakeValues($account['Fields']);
        }

        // save show date
        if ($IDs) {
            $this->query("UPDATE `{db_prefix}accounts` SET `Last_show` = NOW() WHERE `ID` = " . implode(" OR `ID` = ", $IDs));
        }
        unset($fields);

        return $accounts;
    }

    /**
     * build featured accounts boxes
     *
     * @todo - get featured accounts by account types and assign them to related boxes
     *
     **/
    public function buildFeaturedBoxes()
    {
        global $rlSmarty, $blocks, $config, $page_info;

        // generate featured listing blocks data
        foreach ($blocks as $key => $value) {
            if (strpos($key, 'atfb_') === 0) {
                if (!$config['membership_module']) {
                    unset($blocks[$key]);
                    continue;
                }

                $f_type = str_replace('atfb_', '', $key);
                $f_type_var = 'featured_' . $f_type;

                $$f_type_var = $this->getFeatured($f_type, $config['featured_accounts_in_box'], false, false, $key);
                $rlSmarty->assign_by_ref($f_type_var, $$f_type_var);
            }
        }
    }

    /**
     * change account type when registration new user
     *
     * @param array $profile_data
     */
    public function changeType($profile_data = false)
    {
        global $account_tmp, $account_types;

        if (!$profile_data) {
            return;
        }
        if ($account_tmp['Type'] != $account_types[$profile_data['type']]['Key']) {
            $update = array(
                'fields' => array(
                    'Type'        => $account_types[$profile_data['type']]['Key'],
                    'Own_address' => $account_types[$profile_data['type']]['Own_location'] && $profile_data['location'] ? $profile_data['location'] : '',
                ),
                'where'  => array('ID' => $account_tmp['ID']),
            );

            if ($GLOBALS['rlActions']->updateOne($update, 'accounts')) {
                $match_field = $GLOBALS['config']['account_login_mode'] == 'email' ? 'mail' : 'username';
                $this->login($profile_data[$match_field], $profile_data['password']);
            }
        }
    }

    /**
     * Update account listings location data
     *
     * @since 4.5.2 - Added $check_map_account parameter
     *
     * @param  int   $account_id        - ID of account
     * @param  array $account_data      - New account info
     * @param  array $check_map_account - Old account info
     * @return bool
     */
    public function accountAddressUpdateListings($account_id, $account_data, $check_map_account)
    {
        global $config, $rlValid;

        $account_id    = (int) $account_id;
        $allow_update = false;

        if (!$config['address_on_map'] || !$account_id) {
            return false;
        }

        if (!$this->loc_mapping) {
            foreach ($config as $key => $value) {
                if (strstr($key, 'address_on_map_')) {
                    $this->loc_mapping[substr($key, 15)] = $value;
                }
            }
        }

        if ($this->loc_mapping) {
            $sql = 'UPDATE `{db_prefix}listings` SET ';

            foreach ($this->loc_mapping as $lf => $af) {
                $new_value = $rlValid->xSql($account_data[$af]);

                if ($rlValid->xSql($check_map_account[$af]) != $new_value) {
                    $sql .= "`{$lf}` = '{$new_value}', ";
                    $allow_update = true;
                }
            }

            $loc_address = $account_data['Loc_address'] ? $rlValid->xSql($account_data['Loc_address']) : '';

            if ($account_data['Loc_latitude'] && $account_data['Loc_longitude'] && $loc_address) {
                $sql .= "`Loc_latitude` = {$account_data['Loc_latitude']}, ";
                $sql .= "`Loc_longitude` = {$account_data['Loc_longitude']}, ";
                $sql .= "`Loc_address` = '{$loc_address}' ";
            } elseif ($allow_update) {
                $sql = substr($sql, 0, -2);
            }

            $sql .= " WHERE `Account_ID` = {$account_id} AND `account_address_on_map` = '1'";
        }

        return $allow_update && $sql ? $this->query($sql) : false;
    }

    /**
     * assign current account address on map to smarty
     *
     *@param $account_id optional account id
     */
    public function accountAddressAssign($account_id = false)
    {
        $info = $GLOBALS['account_info'] ?: $this->getProfile($account_id);

        if (!$GLOBALS['config']['address_on_map'] || !$info) {
            return false;
        }

        if (!$this->loc_mapping) {
            foreach ($GLOBALS['config'] as $key => $value) {
                if (strstr($key, 'address_on_map_')) {
                    $this->loc_mapping[substr($key, 15)] = $value;
                }
            }
        }

        foreach ($this->loc_mapping as $lfield => $afield) {
            $account_address[$lfield] = $info[$afield];
        }

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpAccountAddressAssign', $this->loc_mapping, $account_address, $info, $account_id);

        $GLOBALS['rlSmarty']->assign('account_address', $account_address);
    }

    /**
     * Account address add to an array
     *
     * @param array $data       - Array to add location fields to
     * @param int   $account_id
     */
    public function accountAddressAdd(&$data, $account_id = 0)
    {
        global $config;

        $info = $GLOBALS['account_info'] ?: $this->getProfile((int) $account_id);

        if (!$config['address_on_map'] || !$info || !$data['account_address_on_map']) {
            return false;
        }

        if (!$this->loc_mapping) {
            foreach ($config as $key => $value) {
                if (strstr($key, 'address_on_map_')) {
                    $this->loc_mapping[substr($key, 15)] = $value;
                }
            }
        }

        foreach ($this->loc_mapping as $lfield => $afield) {
            $data[$lfield] = $info[$afield];
        }
    }

    /**
     * Checking value of option "Sign-Up" in other account types to allow disable/remove current type
     *
     * @since 4.6.0
     *
     * @param  array $type  - Data of currenct account type
     * @param  array $types - Array of account types
     * @return bool
     */
    public function checkAbilityDisablingType($type, $types = array())
    {
        if (!$type) {
            return false;
        }

        if (!$types) {
            // get available account types
            $types = $this->getAccountTypes('visitor');
        }

        // check value of "Quick registration" option in other account types
        $allow_change_quick_registration = true;

        if ($type['Quick_registration']) {
            $count_available_atypes = 0;

            foreach ($types as $account_type) {
                if ($account_type['Quick_registration'] && $account_type['Key'] != $type['Key']) {
                    $count_available_atypes++;
                }
            }

            $allow_change_quick_registration = $count_available_atypes ? true : false;
        }

        return $allow_change_quick_registration;
    }

    /**
     * Get all account agreement fields which must be showed in 1-st step of registration
     *
     * @since 4.7.0
     *
     * @param  string $type  - Key of account type (optional)
     * @param  bool   $force - Forcing to get a new list of fields from DB
     * @return array
     */
    public function getAgreementFields($type = '', $force = false)
    {
        global $rlHook;

        Valid::escape($type);

        if ($this->agreement_fields && !$force) {
            return $this->agreement_fields;
        }

        $sql = "SELECT `T1`.`Key`, `T1`.`Default`, `T1`.`Values`";

        $rlHook->load('phpSelectAgreementFields', $sql, $type);

        $sql .= "FROM `{db_prefix}account_fields` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `Type` = 'accept' AND `T1`.`Opt1` = '1' ";

        if ($type) {
            $sql .= "AND `T1`.`Values` = '' OR FIND_IN_SET('{$type}', `T1`.`Values`) > 0";
        }

        $rlHook->load('phpWhereAgreementFields', $sql, $type);

        $this->agreement_fields = $GLOBALS['rlDb']->getAll($sql, 'Key');

        $rlHook->load('phpAgreementFields', $this->agreement_fields, $type);

        return $this->agreement_fields;
    }

    /**
     * Get base info of account in login process
     *
     * @since 4.8.2
     *
     * @param  string|int $data                 - Criteria of login: username or ID
     * @param  callable   $hookCallableFunction - Anonymous function which will call hook
     * @return array
     */
    public function getAccountBaseInfo($data, $hookCallableFunction = null)
    {
        global $config;

        if (empty($data) || (!empty($data) && !is_string($data) && !is_int($data))) {
            return [];
        }

        $sql = "SELECT `T1`.*, `T2`.`Abilities`, `T2`.`ID` AS `Type_ID`, `T2`.`Own_location`, ";
        $sql .= "`T2`.`Page` AS `Own_page`, `T2`.`Status` as `Type_status`, `T2`.`Thumb_width`, `T2`.`Thumb_height` ";

        if ($config['membership_module']) {
            $sql .= ", IF(TIMESTAMPDIFF(HOUR, `T1`.`Pay_date`, NOW()) <= `T3`.`Plan_period` * 24 ";
            $sql .= "OR `T3`.`Plan_period` = 0 OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, ";
            $sql .= "`T1`.`Status`, 'expired') AS `Status`, ";
            $sql .= " IF(IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0, 'unpaid', 'paid') AS `Payment_status` ";
        }

        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";

        if ($config['membership_module']) {
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T3` ON `T1`.`Plan_ID` = `T3`.`ID` ";
        }

        $sql .= "WHERE `T1`.`Status` <> 'trash' AND `T2`.`Status` <> 'trash' AND ";

        if (is_int($data)) {
            $sql .= "`T1`.`ID` = {$data} ";
        } else {
            if ($config['account_login_mode'] == 'email') {
                $sql .= "(`T1`.`Mail` = '{$data}' OR `T1`.`Username` = '{$data}') ";
            } else {
                $sql .= "`T1`.`Username` = '{$data}' ";
            }
        }

        if ($hookCallableFunction && is_callable($hookCallableFunction)) {
            $hookCallableFunction($sql);
        }

        return (array) $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * Save visitor message hash and redirect
     *
     * @since 4.9.0
     */
    public function visitorMessageHashHandler()
    {
        global $reefless, $account_info;

        if ($_GET['message-hash']) {
            $hash = $_GET['message-hash'];

            if (defined('IS_LOGIN')) {
                $this->assignMessagesByVisitorHash($account_info['ID'], $hash);
            } else {
                $cookie_period = 3600 * 24 * 31;
                $reefless->createCookie('visitor_message_hash', $hash, time() + $cookie_period);
            }

            $redirect_url = $reefless->getPageUrl($GLOBALS['page_info']['Key']);
            $GLOBALS['rlHook']->load('phpAccountVisitorMessageHashHandler', $redirect_url, $hash, $account_info);

            Util::redirect($redirect_url);
        }
    }

    /**
     * Assign visitor messages to his new account by hash
     *
     * @since 4.9.0
     *
     * @param string $accountID - Account ID
     * @param string $getHash   - Hash key from GET
     */
    public function assignMessagesByVisitorHash($accountID, $getHash = null)
    {
        global $rlDb;

        $hash = $getHash ?: $_COOKIE['visitor_message_hash'];

        if ($hash = Valid::escape($hash)) {
            // Return if the hash link used by listing owner
            if ($accountID == $rlDb->getOne('To', "`From` = '-1' AND `Visitor_hash` = '{$hash}'", 'messages')) {
                return;
            }

            $update[] = array(
                'fields' => array(
                    'From' => $accountID
                ),
                'where' => array(
                    'From' => '-1',
                    'Visitor_hash' => $hash
                )
            );

            $update[] = array(
                'fields' => array(
                    'To' => $accountID,
                    'Visitor_mail' => '',
                    'Visitor_hash' => '',
                ),
                'where' => array(
                    'To' => '-1',
                    'Visitor_hash' => $hash
                )
            );

            $rlDb->update($update, 'messages');
            $GLOBALS['reefless']->eraseCookie('visitor_message_hash');
        }
    }
}
