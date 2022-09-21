<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLLISTINGS.CLASS.PHP
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

use Flynax\Classes\Agencies;
use Flynax\Utils\Util;
use Flynax\Utils\ListingMedia;

class rlListings extends reefless
{
    /**
     * @var actions class object
     **/
    public $rlActions;

    /**
     * @var calculate items
     **/
    public $calc;

    /**
     * @var listing fields list (view listing details mode)
     **/
    public $fieldsList;

    /**
     * @var created listing id
     **/
    public $id;

    /**
     * @var selected listing IDs
     **/
    public $selectedIDs;

    /**
     * @var temporary storage
     **/
    public $tmp = array();

    /**
     * exclude the fields from the short form fields line
     **/
    public $exclude_short_form_fields = array('price', 'salary', 'title', 'bedrooms', 'bathrooms', 'square_feet', 'time_frame');

    /**
     * create listing
     *
     * @param array $plan_info - plan information
     * @param array $data   - listing data
     * @param array $fields - current listing kind fields
     *
     **/
    public function create($info = false, $data = false, $fields = false, $plan_info = false)
    {
        global $rlCommon, $account_info, $location, $rlHook, $rlActions, $rlValid, $config, $rlDb;

        // system listing data, plan, category, account etc
        $listing = $info;
        $listing['Plan_ID'] = $plan_info['ID'];
        $listing_type = $_POST['listing_type'] && defined('REALM') ? $_POST['listing_type'] : $_SESSION['add_listing']['listing_type'];

        /* activation/periods handler TODO */
        if ($listing['Plan_type'] != 'account' && ((empty($plan_info['Price']) || REALM == 'admin') || ($plan_info['Type'] == 'package' && !empty($plan_info['Listings_remains'])))) {
            $listing['Pay_date'] = "NOW()";

            if (($plan_info['Featured'] && $plan_info['Advanced_mode'] && $listing_type == 'featured') || ($plan_info['Featured'] && !$plan_info['Advanced_mode'])) {
                $listing['Featured_ID'] = $plan_info['ID'];
                $listing['Featured_date'] = 'NOW()';
            }
        }
        if ($listing['Plan_type'] == 'account') {
            if (($plan_info['Advanced_mode'] && $listing_type == 'featured') || ($plan_info['Featured_listing'] && !$plan_info['Advanced_mode'])) {
                $listing['Featured_ID'] = $plan_info['ID'];
                $listing['Featured_date'] = $account_info['Pay_date'] && $account_info['Pay_date'] != '0000-00-00 00:00:00' ? $account_info['Pay_date'] : 'NOW()';
            }
        }

        /* activation/periods handler end */
        if (!empty($fields) && !empty($data)) {
            foreach ($fields as $key => $value) {
                $fk = $fields[$key]['Key'];

                if (isset($data[$fields[$key]['Key']])) {
                    /* collect location fields/data */
                    if (!$data['account_address_on_map'] && $value['Map'] && $data[$fk]) {
                        if (is_array($data[$fk])) {
                            $location[] = $rlCommon->adaptValue($value, $data[$fk][RL_LANG_CODE]);
                        } else {
                            $location[] = $rlCommon->adaptValue($value, $data[$fk]);
                        }
                    }
                    /* collect location fields/data end */

                    switch ($fields[$key]['Type']) {
                        case 'text':
                            if ($value['Multilingual'] && count($GLOBALS['languages']) > 1) {
                                $out = '';
                                foreach ($GLOBALS['languages'] as $language) {
                                    $val = $data[$fk][$language['Code']];
                                    if ($val) {
                                        $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                                    }
                                }

                                $listing[$fk] = $out;
                            } else {
                                if ($value['Condition'] == 'isUrl' && ($data[$fk] == 'http://' || $data[$fk] == 'https://')) {
                                    break;
                                }

                                $listing[$fk] = $data[$fk];
                            }
                            break;

                        case 'phone':
                            $out = '';

                            /* code */
                            if ($value['Opt1']) {
                                $code = $rlValid->xSql(substr($data[$fk]['code'], 0, $value['Default']));
                                $out = 'c:' . $code . '|';
                            }

                            /* area */
                            $area = $rlValid->xSql($data[$fk]['area']);
                            $out .= 'a:' . $area . '|';

                            /* number */
                            $number = $rlValid->xSql(substr($data[$fk]['number'], 0, $value['Values']));
                            $out .= 'n:' . $number;

                            if (!$area || !$number) {
                                break;
                            }

                            /* extension */
                            if ($value['Opt2']) {
                                $ext = $rlValid->xSql($data[$fk]['ext']);
                                $out .= '|e:' . $ext;
                            }

                            $listing[$fk] = $out;
                            break;

                        case 'select':
                        case 'bool':
                        case 'radio':
                            $listing[$fk] = $data[$fk];
                            break;

                        case 'number':
                            $listing[$fk] = preg_replace('/[^\d|.]/', '', $data[$fk]);
                            break;

                        case 'date':
                            if ($fields[$key]['Default'] == 'single') {
                                $listing[$fk] = $data[$fk];
                            } elseif ($fields[$key]['Default'] == 'multi') {
                                $listing[$fk] = $data[$fk]['from'];
                                $listing[$fk . '_multi'] = $data[$fk]['to'];
                            }
                            break;

                        case 'textarea':
                            if ($value['Condition'] == 'html') {
                                $html_fields[] = $value['Key'];
                            }

                            $limit = (int) $value['Values'];

                            if ($value['Multilingual'] && count($GLOBALS['languages']) > 1) {
                                $out = '';
                                foreach ($GLOBALS['languages'] as $language) {
                                    $val = $data[$fk][$language['Code']];

                                    if ($limit) {
                                        // Revert quotes characters and remove trailing new line code
                                        Flynax\Utils\Valid::revertQuotes($val);
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
                                $listing[$fk] = $out;
                            } else {
                                if ($value['Values']) {
                                    if ($limit) {
                                        // Revert quotes characters and remove trailing new line code
                                        Flynax\Utils\Valid::revertQuotes($data[$fk]);
                                        $data[$fk] = str_replace(PHP_EOL, '', $data[$fk]);

                                        if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                                            mb_internal_encoding('UTF-8');
                                            $data[$fk] = mb_substr($data[$fk], 0, $limit);
                                        } else {
                                            $data[$fk] = substr($data[$fk], 0, $limit);
                                        }
                                    }
                                }
                                $listing[$fk] = $data[$fk];
                            }
                            break;

                        case 'mixed':
                            if (!empty($data[$fk]['value'])) {
                                $df = $data[$fk]['value'] . '|' . $data[$fk]['df'];
                                $listing[$fk] = $df;
                            }
                            break;

                        case 'price':
                            if (!empty($data[$fk]['value'])) {
                                $data[$fk]['value'] = str_replace(array(',', "'"), '', $data[$fk]['value']);

                                if ($config['price_separator'] != '.') {
                                    $data[$fk]['value'] = str_replace($config['price_separator'], '.', $data[$fk]['value']);
                                }

                                $data[$fk]['value'] = str_replace(array(',', "'"), '', $data[$fk]['value']);

                                $price = $data[$fk]['value'] . '|' . $data[$fk]['currency'];
                                $listing[$fk] = $price;
                            }
                            break;

                        case 'unit':
                            if (!empty($data[$fk]['value'])) {
                                $unit = $data[$fk]['value'] . '|' . $data[$fk]['unit'];
                                $listing[$fk] = $unit;
                            }
                            break;

                        case 'checkbox':
                            unset($chValues);
                            unset($data[$fk][0]);
                            foreach ($data[$fk] as $chRow) {
                                $chValues .= $chRow . ",";
                            }
                            $chValues = substr($chValues, 0, -1);

                            $listing[$fk] = $chValues;
                            break;

                        case 'image':
                            $file_name = 'listing_' . $fk . '_' . time() . mt_rand();
                            $resize_type = $fields[$key]['Default'];
                            $resolution = strtoupper($resize_type) == 'C'
                            ? explode('|', $fields[$key]['Values'])
                            : $fields[$key]['Values'];

                            $file_name = $rlActions->upload($fk, $file_name, $resize_type, $resolution, 'f', false);
                            $listing[$fk] = $file_name;
                            break;

                        case 'file':
                            $file_name = 'listing_' . $fk . '_' . time() . mt_rand();
                            $file_name = $rlActions->upload($fk, $file_name, false, false, 'f', false);
                            $listing[$fk] = $file_name;
                            break;

                        case 'accept':
                            $listing[$fk] = $data[$fk];
                            break;
                    }
                }
            }

            $rlHook->load('listingCreateBeforeInsert', $listing, $location); // $listing, $location >= v4.3

            /* get coordinates by address request */
            if (!$data['account_address_on_map'] && $location) {
                $this->geocodeLocation($location, $listing);
            } elseif ($data['account_address_on_map']) {
                $listing['Loc_address'] = $account_info['Loc_address'];
                $listing['Loc_latitude'] = $account_info['Loc_latitude'];
                $listing['Loc_longitude'] = $account_info['Loc_longitude'];
            }

            $res = $rlDb->insertOne($listing, 'listings', $html_fields);

            $this->id = $rlDb->insertID();

            return $res;
        } else {
            trigger_error("Can not add new listing, no listing data or fields found.", E_WARNING);
            $GLOBALS['rlDebug']->logger("Can not add new listing, no listing data or fields found.");
        }
    }

    /**
     * edit listing
     *
     * @param int $id - listing ID
     * @param array $plan_info - plan information
     * @param array $data   - listing data
     * @param array $fields - current listing kind fields
     *
     **/
    public function edit($id = false, $info = false, $data = false, $fields = false, $plan_info = false)
    {
        global $config, $rlCommon, $rlHook, $location, $account_info, $rlValid, $rlActions, $rlDb;

        if (!$id || !$info || !$data || !$fields) {
            return false;
        }

        $listing['where'] = array(
            'ID' => $id,
        );

        // Define listing system data
        $listing['fields'] = $info;

        if (!empty($fields) && !empty($data)) {
            foreach ($fields as $key => $value) {
                $fk = $fields[$key]['Key'];

                if (!$data['account_address_on_map'] && $value['Map']) {
                    if (is_array($data[$fk])) {
                        $location[] = $rlCommon->adaptValue($value, $data[$fk][RL_LANG_CODE]);
                    } else {
                        $location[] = $rlCommon->adaptValue($value, $data[$fk]);
                    }
                    $location_check[$fk] = $data[$fk];
                }

                switch ($fields[$key]['Type']) {
                    case 'text':
                        if ($value['Multilingual'] && count($GLOBALS['languages']) > 1) {
                            $out = '';
                            foreach ($GLOBALS['languages'] as $language) {
                                $val = $data[$fk][$language['Code']];
                                if ($val) {
                                    $out .= "{|{$language['Code']}|}" . $val . "{|/{$language['Code']}|}";
                                }
                            }

                            $listing['fields'][$fk] = $out;
                        } else {
                            if ($value['Condition'] == 'isUrl' && $data[$fk] == 'http://') {
                                break;
                            }

                            $listing['fields'][$fk] = $data[$fk];
                        }
                        break;

                    case 'phone':
                        $out = '';

                        // code
                        if ($value['Opt1']) {
                            $code = $rlValid->xSql(substr($data[$fk]['code'], 0, $value['Default']));
                            $out = $code ? 'c:' . $code . '|' : '';
                        }

                        // area
                        $area = $rlValid->xSql($data[$fk]['area']);
                        $out .= $area ? 'a:' . $area . '|' : '';

                        // number
                        $number = $rlValid->xSql(substr($data[$fk]['number'], 0, $value['Values']));
                        $out .= $number ? 'n:' . $number : '';

                        // extension
                        if ($value['Opt2']) {
                            $ext = $rlValid->xSql($data[$fk]['ext']);
                            $out .= $ext ? '|e:' . $ext : '';
                        }

                        $listing['fields'][$fk] = $out;
                        break;

                    case 'number':
                        $listing['fields'][$fk] = preg_replace('/[^\d|.]/', '', $data[$fk]);
                        break;

                    case 'select':
                    case 'bool':
                    case 'radio':
                        $listing['fields'][$fk] = $data[$fk];
                        break;

                    case 'date':
                        if ($fields[$key]['Default'] == 'single') {
                            $listing['fields'][$fk] = $data[$fk];
                        } elseif ($fields[$key]['Default'] == 'multi') {
                            $listing['fields'][$fk] = $data[$fk]['from'];
                            $listing['fields'][$fk . '_multi'] = $data[$fk]['to'];
                        }
                        break;

                    case 'textarea':
                        if ($value['Condition'] == 'html') {
                            $html_fields[] = $value['Key'];
                        }

                        $limit = (int) $value['Values'];

                        if ($value['Multilingual'] && count($GLOBALS['languages']) > 1) {
                            $out = '';
                            foreach ($GLOBALS['languages'] as $language) {
                                $val = $data[$fk][$language['Code']];

                                if ($limit) {
                                    // Revert quotes characters and remove trailing new line code
                                    Flynax\Utils\Valid::revertQuotes($val);
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
                            $listing['fields'][$fk] = $out;
                        } else {
                            if ($value['Values']) {
                                if ($limit) {
                                    // Revert quotes characters and remove trailing new line code
                                    Flynax\Utils\Valid::revertQuotes($data[$fk]);
                                    $data[$fk] = str_replace(PHP_EOL, '', $data[$fk]);

                                    if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                                        mb_internal_encoding('UTF-8');
                                        $data[$fk] = mb_substr($data[$fk], 0, $limit);
                                    } else {
                                        $data[$fk] = substr($data[$fk], 0, $limit);
                                    }
                                }
                            }
                            $listing['fields'][$fk] = $data[$fk];
                        }
                        break;

                    case 'mixed';
                        if (empty($data[$fk]['value'])) {
                            $listing['fields'][$fk] = '';
                        } else {
                            $df = $data[$fk]['value'] . '|' . $data[$fk]['df'];
                            $listing['fields'][$fk] = $df;
                        }
                        break;

                    case 'price';
                        if (empty($data[$fk]['value'])) {
                            $listing['fields'][$fk] = '';
                        } else {
                            if ($config['price_separator'] != '.') {
                                $data[$fk]['value'] = str_replace($config['price_separator'], '.', $data[$fk]['value']);
                            }
                            $data[$fk]['value'] = str_replace(array(',', "'"), '', $data[$fk]['value']);

                            $price = $data[$fk]['value'] . '|' . $data[$fk]['currency'];
                            $listing['fields'][$fk] = $price;
                        }
                        break;

                    case 'checkbox';
                        unset($chValues);
                        unset($data[$fk][0]);
                        foreach ($data[$fk] as $chRow) {
                            $chValues .= $chRow . ",";
                        }
                        $chValues = substr($chValues, 0, -1);
                        $listing['fields'][$fk] = $chValues;
                        break;

                    case 'image':
                        if ($_FILES[$fk] || $data['sys_exist_' . $fk]) {
                            $file_name = 'listing_' . $fk . '_' . time() . mt_rand();
                            $resize_type = $fields[$key]['Default'];
                            $resolution = strtoupper($resize_type) == 'C'
                            ? explode('|', $fields[$key]['Values'])
                            : $fields[$key]['Values'];

                            $file_name = $rlActions->upload($fk, $file_name, $resize_type, $resolution, 'f', false);
                            $listing['fields'][$fk] = $file_name ?: $data['sys_exist_' . $fk];

                            // remove old image when user uploaded a new image
                            if ($file_name
                                && $data['sys_exist_' . $fk]
                                && $file_name !== $data['sys_exist_' . $fk]
                            ) {
                                unlink(RL_FILES . $data['sys_exist_' . $fk]);
                            }
                        }
                        break;

                    case 'file':
                        if ($_FILES[$fk] || $data['sys_exist_' . $fk]) {
                            $file_name = 'listing_' . $fk . '_' . time() . mt_rand();
                            $file_name = $rlActions->upload($fk, $file_name, false, false, 'f', false);
                            $listing['fields'][$fk] = $file_name ?: $data['sys_exist_' . $fk];

                            // remove old file when user uploaded a new file
                            if ($file_name
                                && $data['sys_exist_' . $fk]
                                && $file_name !== $data['sys_exist_' . $fk]
                            ) {
                                unlink(RL_FILES . $data['sys_exist_' . $fk]);
                            }
                        }
                        break;

                    case 'accept':
                        $listing['fields'][$fk] = $data[$fk];
                        break;
                }
            }

            /**
             * @since 4.7.2
             */
            $rlHook->load('listingBeforeEdit', $listing, $location);

            /* get coordinates by address request */
            if (!$data['account_address_on_map'] && $location) {
                $allow_geocode_call = true;

                // check if location data was changed
                if ($location_check) {
                    $sql = "SELECT * FROM `{db_prefix}listings` WHERE `ID` = {$id} AND (";
                    foreach ($location_check as $lck => $lcv) {
                        $lcv = $rlValid->xSql($lcv);
                        $sql .= " `{$lck}` != '{$lcv}' OR ";
                    }
                    $sql = substr($sql, 0, -3);
                    $sql .= ")";

                    if (!$rlDb->getRow($sql)) {
                        $allow_geocode_call = false; //location data is not changed, no need for geocoder call
                    }
                }

                if ($allow_geocode_call) {
                    $this->geocodeLocation($location, $listing['fields']);
                }
            } elseif ($data['account_address_on_map']) {
                if (defined('REALM')) {
                    $account_id = $listing['fields']['Account_ID'];
                } else if ($account_info['ID']) {
                    $account_id = $account_info['ID'];
                } else {
                    $account_id = $rlDb->getOne('Account_ID', "`ID` = {$id}", 'listings');
                }

                $accountData = $GLOBALS['rlAccount']->getProfile((int) $account_id);

                $listing['fields']['Loc_address']   = $accountData['Loc_address'];
                $listing['fields']['Loc_latitude']  = $accountData['Loc_latitude'];
                $listing['fields']['Loc_longitude'] = $accountData['Loc_longitude'];
            }

            return $rlDb->updateOne($listing, 'listings', $html_fields);
        } else {
            trigger_error("Can not edit listing, no listing data or fields found.", E_WARNING);
            $GLOBALS['rlDebug']->logger("Can not edit listing, no listing data or fields found.");
        }
    }

    /**
     * Is listing active and visible on the front end
     *
     * @since 4.7.0 - Update logic of getting status
     *                Get a value from `Status` column only, without any calculating
     *
     * @param  int  $listing_id
     * @return bool
     */
    public function isActive($id = 0)
    {
        $id = (int) $id;

        if (!$id) {
            return false;
        }

        return ($GLOBALS['rlDb']->getOne('Status', "`ID` = {$id}", "listings") == 'active');
    }

    /**
     * get listings by category
     *
     * @param string $category_id - category ID
     * @param string $order - field name for order
     * @param string $order_type - order type
     * @param int $start - start DB position
     * @param int $limit - listing number per request
     * @param string $listing_type - listing type key
     *
     * @return array - listings information
     **/
    public function getListings($category_id = false, $order = false, $order_type = 'ASC', $start = 0, $limit = 10, $listing_type = false)
    {
        global $sorting, $sql, $custom_order, $config, $rlListingTypes, $rlValid, $rlHook, $rlCommon;

        $category_id = (int) $category_id;
        $start       = $start > 1 ? ($start - 1) * $limit : 0;
        $hook        = '';
        $dbcount     = $category_id == 0 ? $rlListingTypes->types[$listing_type]['Count'] : $GLOBALS['category']['Count'];
        $sql         = "SELECT ";

        /**
         * @since 4.6.1
         */
        $rlHook->load('listingsModifyPreSelect', $dbcount);

        if (!$dbcount) {
            $sql .= "SQL_CALC_FOUND_ROWS ";
        }

        $sql .= " {hook} ";
        $sql .= "`T1`.*, `T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, `T3`.`Parent_keys`, `T3`.`Parent_IDs`, ";
        $sql .= "`T3`.`Path` AS `Path`, ";

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T3`.`Path_{$languageKey}`, ";
            }
        }

        $rlHook->load('listingsModifyField');

        $sql .= "IF(`T1`.`Featured_date` <> '0000-00-00 00:00:00', '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";

        if ($GLOBALS['conversion_rates'][$sorting[$order]['Condition']]) {
            foreach ($sorting as $key => $fVal) {
                if ($GLOBALS['conversion_rates'][$fVal['Condition']] && $fVal['Condition']) {
                    $sql .= "LEFT JOIN `{db_prefix}data_formats` AS `TDF_{$fVal['Condition']}` ON `TDF_{$fVal['Condition']}`.`Key` = SUBSTRING_INDEX(`T1`.`{$fVal['Key']}`, '|', -1) ";
                }
            }
        }

        $rlHook->load('listingsModifyJoin');

        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($category_id > 0) {
            $sql .= "AND (`T1`.`Category_ID` = '{$category_id}' OR (FIND_IN_SET('{$category_id}', `T1`.`Crossed`) > 0) ";

            if ($config['lisitng_get_children']) {
                $sql .= "OR FIND_IN_SET('{$category_id}', `T3`.`Parent_IDs`) > 0 ";
            }

            $sql .= ") ";

            $hook = "IF (FIND_IN_SET('{$category_id}', `T1`.`Crossed`) > 0, 1, 0) AS `Crossed_listing`, ";
        } else {
            $sql .= "AND `T3`.`Type` = '{$listing_type}' ";
        }

        $rlHook->load('listingsModifyWhere');
        $rlHook->load('listingsModifyGroup');

        $rlValid->sql($order);
        $rlValid->sql($order_type);

        $default_order = true;
        $sql .= 'ORDER BY ';

        /**
         * @since 4.8.2
         */
        $rlHook->load('listingsModifyOrder', $sql, $default_order, $custom_order, $order);

        $sql .= '`Featured_date` DESC ';

        if ($custom_order) {
            $sql .= ", `{$custom_order}` " . strtoupper($order_type) . " ";
        } elseif ($order) {
            switch ($sorting[$order]['Type']) {
                case 'mixed':
                    if ($GLOBALS['conversion_rates'][$sorting[$order]['Condition']]) {
                        $sql .= ", SUBSTRING_INDEX(`T1`.`{$sorting[$order]['Key']}`, '|', 1)/IF(`TDF_{$sorting[$order]['Condition']}`.`Rate` IS NULL, 1, `TDF_{$sorting[$order]['Condition']}`.`Rate`) ";
                        $sql .= " " . strtoupper($order_type) . " ";
                        break;
                    }
                case 'price':
                case 'unit':
                    $sql .= ", ROUND(`T1`.`{$order}`) " . strtoupper($order_type) . " ";
                    break;

                case 'select':
                    if ($sorting[$order]['Key'] == 'Category_ID') {
                        $sql .= ", `T3`.`Key` " . strtoupper($order_type) . " ";
                    } else {
                        $sql .= ", `T1`.`{$order}` " . strtoupper($order_type) . " ";
                    }
                    break;

                default:
                    $sql .= ", `T1`.`{$order}` " . strtoupper($order_type) . " ";
                    break;
            }
        } elseif ($default_order) {
            $sql .= ", `Pay_date` DESC ";
        }

        $sql .= "LIMIT {$start}, {$limit} ";

        /* replace hook */
        $sql = str_replace('{hook}', $hook, $sql);

        $listings = $this->getAll($sql);

        if (empty($listings)) {
            return false;
        }

        $this->calc = $dbcount ?: $this->getRow("SELECT FOUND_ROWS() AS `calc`", 'calc');

        $type = $rlListingTypes->types[$listing_type];
        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        if ($type['Cat_general_only']) {
            $shortFormFields = $this->getFormFields($type['Cat_general_cat'], 'short_forms', $listing_type);
            $this->getFormFields($type['Cat_general_cat'], 'listing_titles', $listing_type);
        }

        foreach ($listings as &$listing) {
            $rlCommon->listings[$listing['ID']] = $listing;

            if (!$shortFormFields) {
                $shortFormFields = $this->getFormFields(
                    $listing['Category_ID'],
                    'short_forms',
                    $listing['Listing_type'],
                    $listing['Parent_IDs']
                );
            }

            $listingFields = $shortFormFields;

            foreach ($listingFields as &$field) {
                $field['value'] = $rlCommon->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields']        = $listingFields;
            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                false,
                $listing['Parent_IDs']
            );
            $listing['url'] = $this->getListingUrl($listing);

            unset($field, $listingFields);
        }

        return $listings;
    }

    /**
     * Get listings by account ID
     *
     * @param array|int $account   - Account info of ID of account
     * @param string    $order     - Field name for order
     * @param string    $orderType - Order type
     * @param int       $start     - Start DB position
     * @param int       $limit     - Listing number per request
     *
     * @return array - listings information
     */
    public function getListingsByAccount($account = false, $order = false, $orderType = 'ASC', $start = 0, $limit = false)
    {
        global $sorting, $sql, $config, $rlDb;

        $accountID = intval(is_array($account) && $account['ID'] ? $account['ID'] : $account);

        if (!$accountID) {
            return [];
        }

        $limit = (int) $limit;
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "SELECT ";
        $sql .= "SQL_CALC_FOUND_ROWS ";
        $sql .= "`T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, `T3`.`Parent_IDs`, ";
        $sql .= "`T1`.*, `T1`.`Shows`, `T3`.`Path` AS `Path`, ";

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T3`.`Path_{$languageKey}`, ";
            }
        }

        $GLOBALS['rlHook']->load('listingsModifyFieldByAccount');

        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";

        $GLOBALS['rlHook']->load('listingsModifyJoinByAccount');

        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($accountID) {
            $agencies = new Agencies();

            if (is_array($account) && $agencies->isAgency($account)) {
                $agencies->addSqlConditionGetListings($sql, $accountID);
            } else {
                $sql .= "AND `T1`.`Account_ID` = '{$accountID}' ";
            }
        }

        $GLOBALS['rlHook']->load('listingsModifyWhereByAccount');
        $GLOBALS['rlHook']->load('listingsModifyGroupByAccount');

        $GLOBALS['rlValid']->sql($orderType);

        $sql .= "ORDER BY ";
        if ($order && $sorting[$order]['field']) {
            switch ($sorting[$order]['Type']) {
                case 'price':
                case 'unit':
                case 'mixed':
                    $sql .= " ROUND(`T1`.`{$sorting[$order]['field']}`) " . strtoupper($orderType) . " ";
                    break;

                case 'select':
                    if ($sorting[$order]['Key'] == 'Category_ID') {
                        $sql .= " `T3`.`Key` " . strtoupper($orderType) . " ";
                    } else {
                        $sql .= " `T1`.`{$sorting[$order]['field']}` " . strtoupper($orderType) . " ";
                    }
                    break;

                default:
                    $sql .= " `T1`.`{$sorting[$order]['field']}` " . strtoupper($orderType) . " ";
                    break;
            }
        } else {
            $sql .= "`Pay_date` DESC ";
        }

        if (isset($start) && $limit) {
            $sql .= "LIMIT {$start}, {$limit} ";
        }

        $listings = $rlDb->getAll($sql);

        if (!$listings) {
            return false;
        }

        $calc = $rlDb->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        foreach ($listings as &$listing) {
            /* populate fields */
            $fields = $this->getFormFields(
                $listing['Category_ID'],
                'short_forms',
                $listing['Listing_type'],
                $listing['Parent_IDs']
            );

            foreach ($fields as &$field) {
                $field['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields'] = $fields;

            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                false,
                $listing['Parent_IDs']
            );
            $listing['url'] = $this->getListingUrl($listing);
        }

        return $listings;
    }

    /**
     * Get listings by type and time period
     *
     * @param int    $start        - Page number
     * @param int    $limit        - Listing number per request
     * @param string $listing_type - Key of listing type
     *
     * @return bool|array - Listings information
     */
    public function getRecentlyAdded($start = 0, $limit = 0, $listing_type = '')
    {
        global $config, $rlHook, $rlCommon;

        $GLOBALS['rlValid']->sql($listing_type);

        /* define start position */
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $date_field = $config['recently_added_order_field'] ?: 'Date';

        $sql = "SELECT ";

        if ($listing_type) {
            $dbcount = $GLOBALS['rlListingTypes']->types[$listing_type]['Count'] ?: false;
        }

        /**
         * @since 4.7.1
         */
        $GLOBALS['rlHook']->load('phpRecentlyAddedModifyPreSelect', $dbcount);

        if (!$dbcount) {
            $sql .= " SQL_CALC_FOUND_ROWS ";
        }

        $sql .= "TIMESTAMPDIFF(DAY, DATE(`T1`.`Date`), DATE_ADD(CURDATE(), INTERVAL 1 DAY)) AS `Date_diff`, ";
        $sql .= "`T1`.*, `T4`.`Path`, `T4`.`Type` AS `Listing_type`, ";
        $sql .= "DATE(`T1`.`{$date_field}`) AS `Post_date`, `T4`.`Parent_keys`, `T4`.`Parent_IDs`, ";

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T4`.`Path_{$languageKey}`, ";
            }
        }

        /**
         * @since 4.7.1 - Added $date_field parameter
         * @since 4.4   - Added parameters $sql, $limit, $listing_type
         */
        $rlHook->load('listingsModifyFieldByPeriod', $sql, $limit, $listing_type, $date_field);

        $sql .= "IF(`T1`.`Featured_date` <> '0000-00-00 00:00:00', '1', '0') `Featured`, ";
        $sql .= "`T4`.`Parent_ID`, `T4`.`Key` AS `Cat_key`, `T4`.`Key` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";

        /**
         * @since 4.4 - Added parameters $sql, $limit, $listing_type
         */
        $rlHook->load('listingsModifyJoinByPeriod', $sql, $limit, $listing_type);

        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($listing_type) {
            $sql .= "AND `T4`.`Type` = '{$listing_type}' ";
        }

        /**
         * @since 4.4 - Added parameters $sql, $limit, $listing_type
         */
        $rlHook->load('listingsModifyWhereByPeriod', $sql, $limit, $listing_type);

        /**
         * @since 4.4 - Added parameters $sql, $limit, $listing_type
         */
        $rlHook->load('listingsModifyGroupByPeriod', $sql, $limit, $listing_type);

        /**
         * @since 4.7.1
         */
        $rlHook->load('listingsOrderByPeriod', $sql, $limit, $listing_type, $date_field);

        $default_order = true;
        $sql .= 'ORDER BY ';

        /**
         * @since 4.8.2
         */
        $rlHook->load('listingsModifyOrderByPeriod', $sql, $default_order);

        if ($default_order) {
            $sql .= "`T1`.`{$date_field}` DESC ";
        }

        $sql .= "LIMIT {$start}, {$limit}";

        $listings = $this->getAll($sql);

        if (empty($listings)) {
            return false;
        }

        $this->calc = $dbcount ?: $this->getRow("SELECT FOUND_ROWS() AS `calc`", 'calc');

        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        /**
         * @since 4.4 - Added $parameters
         */
        $rlHook->load('listingsAfterSelectByPeriod', $listings, $listing_type);

        foreach ($listings as &$listing) {
            $rlCommon->listings[$listing['ID']] = $listing;

            /* populate fields */
            $fields = $this->getFormFields(
                $listing['Category_ID'],
                'short_forms',
                $listing['Listing_type'],
                $listing['Parent_IDs']
            );

            foreach ($fields as &$field) {
                $field['value'] = $rlCommon->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields'] = $fields;

            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                false,
                $listing['Parent_IDs']
            );
            $listing['url'] = $this->getListingUrl($listing);
        }

        return $listings;
    }

    /**
     * load recently added listings by listing type to related area
     *
     * @package AJAX
     *
     * @param string $key - listing type key
     *
     **/
    public function ajaxloadRecentlyAdded($key = false)
    {
        global $_response, $config, $pInfo, $rlSmarty, $rlHook, $lra_listings, $requested_key;

        if (!$key) {
            return $_response;
        }

        $requested_key = $key;

        // Define sidebar exists
        $GLOBALS['rlCommon']->defineSidebarExists();

        /* get listings */
        $lra_listings = $this->getRecentlyAdded(0, $config['listings_per_page'], $key);
        $rlSmarty->assign_by_ref('listings', $lra_listings);

        $pInfo['calc'] = $this->calc;

        $_SESSION['recently_added_type'] = $key;
        $rlSmarty->assign_by_ref('requested_type', $key);
        $rlSmarty->assign_by_ref('lt_key', $key);

        $pInfo['current'] = 1;
        $rlSmarty->assign_by_ref('pInfo', $pInfo);

        $rlHook->load('ajaxRecentlyAddedLoadPre');

        $tpl = 'blocks' . RL_DS . 'recently.tpl';
        $_response->assign('area_' . str_replace('_', '', $key), 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));

        $_response->script('flynaxTpl.afterListingsAjaxLoad()');

        $rlHook->load('ajaxRecentlyAddedLoadPost');

        return $_response;
    }

    /**
     * Get all my (account) listings
     *
     * @param  string $type       - Key of listing type
     * @param  string $order      - Field name for order
     * @param  string $order_type - Order type
     * @param  int    $start      - Start DB position
     * @param  int    $limit      - listing number per request
     * @return array              - Listings information
     */
    public function getMyListings($type = '', $order = 'ID', $order_type = 'asc', $start = 0, $limit = 0)
    {
        global $sql, $rlListingTypes, $account_info, $config;

        $allow_tmp_categories = 0;
        foreach ($rlListingTypes->types as $ltype) {
            if ($ltype['Cat_custom_adding']) {
                $allow_tmp_categories = 1;
            }
        }

        /* define start position */
        $limit = $limit ?: $config['listings_per_page'];
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
            `T1`.*, IF( `T2`.`Price` = 0, 'free', '' ) AS `Free`, `T4`.`Path`, `T4`.`Parent_ID`, `T4`.`Parent_IDs`, 
            CONCAT('categories+name+', `T4`.`Key`) AS `Cat_key`, `T4`.`Type` AS `Category_type`, `T4`.`Type` AS `Listing_type`,
        ";

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T4`.`Path_{$languageKey}`, ";
            }
        }

        if ($config['membership_module']) {
            $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Pay_date`, INTERVAL `T7`.`Plan_period` DAY), DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY)) AS `Plan_expire`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', CONCAT('listing_plans+name+', `T7`.`Key`), CONCAT('listing_plans+name+', `T2`.`Key`)) AS `Plan_key`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', DATE_ADD(`T1`.`Featured_date`, INTERVAL `T8`.`Plan_period` DAY), DATE_ADD(`T1`.`Featured_date`, INTERVAL `T3`.`Listing_period` DAY)) AS `Featured_expire`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image`, `T2`.`Image`) AS `Plan_image`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Image_unlim`, `T2`.`Image_unlim`) AS `Image_unlim`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video`, `T2`.`Video`) AS `Plan_video`, ";
            $sql .= "IF (`T1`.`Plan_type` = 'account', `T7`.`Video_unlim`, `T2`.`Video_unlim`) AS `Video_unlim`, ";
        } else {
            $sql .= "DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY) AS `Plan_expire`, ";
            $sql .= "CONCAT('listing_plans+name+', `T2`.`Key`) AS `Plan_key`, ";
            $sql .= "DATE_ADD(`T1`.`Featured_date`, INTERVAL `T3`.`Listing_period` DAY) AS `Featured_expire`, ";
            $sql .= "`T2`.`Image` AS `Plan_image`, `T2`.`Image_unlim` AS `Image_unlim`, `T2`.`Video` AS `Plan_video`, `T2`.`Video_unlim` AS `Video_unlim`, ";
        }

        $sql .= "`T6`.`ID` AS `Subscription_ID`, `T6`.`Service` AS `Subscription_service` "; // >= v4.4

        if ($allow_tmp_categories) {
            $sql .= ", `T5`.`Name` AS `Tmp_name` ";
        }

        $GLOBALS['rlHook']->load('myListingsSqlFields', $sql); // > 4.1.0

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T3` ON `T1`.`Featured_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";

        if ($allow_tmp_categories) {
            $sql .= "LEFT JOIN `{db_prefix}tmp_categories` AS `T5` ON `T1`.`ID` = `T5`.`Listing_ID` ";
        }
        $sql .= "LEFT JOIN `{db_prefix}subscriptions` AS `T6` ON `T1`.`ID` = `T6`.`Item_ID` AND `T6`.`Status` = 'active' "; // >= v4.4

        if ($config['membership_module']) {
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T7` ON `T1`.`Plan_ID` = `T7`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T8` ON `T1`.`Featured_ID` = `T8`.`ID` ";
        }

        $GLOBALS['rlHook']->load('myListingsSqlJoin', $sql, $type); // >= v4.3

        $sql .= "WHERE `T1`.`Account_ID` = '{$account_info['ID']}' ";
        $sql .= "AND `T1`.`Status` <> 'trash' AND `T4`.`Status` = 'active' ";

        if ($type != 'all_ads') {
            $sql .= "AND `T4`.`Type` = '{$type}' ";
        }

        $GLOBALS['rlHook']->load('myListingsSqlWhere', $sql, $type); // >= v4.3

        if ($order) {
            if ($order == 'Plan_expire') {
                $sql .= "ORDER BY `{$order}` " . strtoupper($order_type) . " ";
            } elseif ($order == 'category') {
                $sql .= "ORDER BY `T4`.`Path` " . strtoupper($order_type) . " ";
            } else {
                $sql .= "ORDER BY `T1`.`{$order}` " . strtoupper($order_type) . " ";
            }
        } else {
            $sql .= "ORDER BY `T1`.`ID` DESC ";
        }
        $sql .= "LIMIT {$start}, {$limit}";

        $GLOBALS['rlHook']->load('myListingsSql', $sql, $type); // >= v4.3

        $listings = $this->getAll($sql);

        if (empty($listings)) {
            return false;
        }

        $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        foreach ($listings as &$listing) {
            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                null,
                $listing['Parent_IDs']
            );
            $listing['url'] = $this->getListingUrl($listing);

            $GLOBALS['rlHook']->load('phpListingsGetMyListings', $listing, $type); // $type >= v4.3
        }

        return $listings;
    }

    /**
     * get my favorite listings
     *
     * @param string $order - field name for order
     * @param string $order_type - order type
     * @param int $start - start DB position
     * @param int $limit - listing number per request
     *
     * @return array - listings information
     **/
    public function getMyFavorite($order = 'ID', $order_type = 'asc', $start = 0, $limit = false)
    {
        global $sql, $config;

        $cookies = explode(',', $GLOBALS['rlValid']->xSql($_COOKIE['favorites']));

        if (!$cookies[0]) {
            return false;
        }

        /* define start position */
        $start = $start > 1 ? ($start - 1) * $limit : 0;

        $GLOBALS['rlHook']->load('myFavoriteSysFields');

        $sql = "SELECT SQL_CALC_FOUND_ROWS ";
        $sql .= "`T1`.*, `T4`.`Path`, `T4`.`Type` AS `Listing_type`, `T4`.`Key` AS `Key`, `T4`.`Parent_ID`, ";
        $sql .= "`T4`.`Key` AS `Cat_key`, `T4`.`Parent_IDs`, ";

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T4`.`Path_{$languageKey}`, ";
            }
        }

        $GLOBALS['rlHook']->load('listingsModifyFieldMyFavorite');

        $sql .= "IF(`T1`.`Featured_date`, '1', '0') `Featured` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";

        $GLOBALS['rlHook']->load('listingsModifyJoinMyFavorite');

        $sql .= "WHERE `T1`.`Status` = 'active' ";
        $sql .= "AND (`T1`.`ID` = '" . implode("' OR `T1`.`ID` ='", $cookies) . "') ";

        $GLOBALS['rlHook']->load('listingsModifyWhereMyFavorite');
        $GLOBALS['rlHook']->load('listingsModifyGroupMyFavorite');

        if (false === strpos($sql, 'GROUP BY')) {
            $sql .= " GROUP BY `T1`.`ID` ";
        }

        if ($order) {
            if ($order == 'Category_ID') {
                $sql .= "ORDER BY `T4`.`Path` " . strtoupper($order_type) . " ";
            } elseif ($order == 'Featured') {
                $sql .= "ORDER BY `Featured` " . $order_type . " ";
            } else {
                $sql .= "ORDER BY `T1`.`{$order}`, `Featured` " . strtoupper($order_type) . " ";
            }
        }
        $sql .= "LIMIT {$start}, {$limit}";

        $listings = $this->getAll($sql);

        if (empty($listings)) {
            return false;
        }

        $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
        $this->calc = $calc['calc'];

        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        foreach ($listings as &$listing) {
            /* populate fields */
            $fields = $this->getFormFields(
                $listing['Category_ID'],
                'short_forms',
                $listing['Listing_type'],
                $listing['Parent_IDs']
            );

            foreach ($fields as &$field) {
                $field['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields'] = $fields;

            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                false,
                $listing['Parent_IDs']
            );
            $listing['url'] = $this->getListingUrl($listing);
        }

        return $listings;
    }

    /**
     * get listing short details by ID
     *
     * @param int $id - listing id
     * @param bool $plan_info - include plan information
     *
     * @return array - listing information
     **/
    public function getShortDetails($id, $plan_info = false)
    {
        global $rlHook;

        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $sql = "SELECT `T1`.*, `T3`.`Type` AS `Listing_type`, `T3`.`Path` AS `Category_path`, ";

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpListingsGetShortDetailsModifyField', $sql, $id, $plan_info);

        if ($plan_info) {
            if ($GLOBALS['config']['membership_module']) {
                $sql .= "IF (`T1`.`Plan_type` = 'account', `T4`.`Image`, `T2`.`Image`) AS `Plan_image`, ";
                $sql .= "IF (`T1`.`Plan_type` = 'account', `T4`.`Image_unlim`, `T2`.`Image_unlim`) AS `Image_unlim`, ";
                $sql .= "IF (`T1`.`Plan_type` = 'account', `T4`.`Video`, `T2`.`Video`) AS `Plan_video`, ";
                $sql .= "IF (`T1`.`Plan_type` = 'account', `T4`.`Video_unlim`, `T2`.`Video_unlim`) AS `Video_unlim`, ";
                $sql .= "IF (`T1`.`Plan_type` = 'account', `T4`.`Key`, `T2`.`Key`) AS `Plan_key`, ";
            } else {
                $sql .= "`T2`.`Image` AS `Plan_image`, `T2`.`Image_unlim`, `T2`.`Video` AS `Plan_video`, `T2`.`Video_unlim`, ";
                $sql .= "`T2`.`Key` AS `Plan_key`, ";
            }
        }

        $sql .= "`T3`.`Parent_IDs` ";

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        if ($plan_info) {
            $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
            if ($GLOBALS['config']['membership_module']) {
                $sql .= "LEFT JOIN `{db_prefix}membership_plans` AS `T4` ON `T1`.`Plan_ID` = `T4`.`ID` ";
            }
        }

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpListingsGetShortDetailsModifyJoin', $sql, $id, $plan_info);

        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$id}' ";

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpListingsGetShortDetailsModifyWhere', $sql, $id, $plan_info);

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpListingsGetShortDetailsModifyOrder', $sql, $id, $plan_info);

        $sql .= "LIMIT 1";

        $listing = $this->getRow($sql);

        $fields = $this->getFormFields(
            $listing['Category_ID'],
            'short_forms',
            $listing['Listing_type'],
            $listing['Parent_IDs']
        );

        foreach ($fields as &$field) {
            if ($listing[$field['Key']] == '') {
                unset($fields[$field['Key']]);
            } else {
                $field['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }
        }

        $listing['fields'] = $fields;
        $listing['listing_title'] = $this->getListingTitle(
            $listing['Category_ID'],
            $listing,
            $listing['Listing_type'],
            false,
            $listing['Parent_IDs']
        );
        $listing['url'] = $this->getListingUrl($listing);

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpListingsGetShortDetailsBottom', $listing, $id, $plan_info);

        return $listing;
    }

    /**
     * Get listing details by id (using field groups relations)
     *
     * @param  int   $id           - Category id
     * @param  array $listing      - Listing fields values
     * @param  array $listing_type - Listing type details
     * @return array               - Listing details
     */
    public function getListingDetails($id, &$listing, $listing_type = false)
    {
        global $rlCache, $config, $rlCategories, $rlSmarty, $rlCommon, $tpl_settings;

        // tmp salary field solution
        $config['price_tag_field'] = $listing_type['Key'] == 'jobs' ? 'salary' : $config['price_tag_field'];

        if (!$id || !$listing || !$listing_type) {
            return [];
        }

        $id = (int) $id;
        $form = [];

        // Get form from cache
        if ($config['cache']) {
            $form = $rlCache->get('cache_submit_forms', $id, $listing_type, $listing['Parent_IDs']);
        }
        // Get form from Database
        else {
            $rows = $rlCategories->getParentCatRelations($id);

            if (empty($rows)) {
                $rows = $rlCategories->getParentCatRelations($listing_type['Cat_general_cat'], false);
            }

            if (!$rows) {
                return [];
            }

            foreach ($rows as $key => $value) {
                if (!empty($value['Fields'])) {
                    $sql = "SELECT *, FIND_IN_SET(`ID`, '{$value['Fields']}' ) AS `Order`, ";
                    $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, CONCAT('listing_fields+description+', `Key`) AS `pDescription`, ";
                    $sql .= "CONCAT('listing_fields+default+', `Key`) AS `pDefault`, `Multilingual` ";
                    $sql .= "FROM `{db_prefix}listing_fields` ";
                    $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
                    $sql .= "ORDER BY `Order`";
                    $fields = $this->getAll($sql, 'Key');

                    if (empty($fields)) {
                        unset($rows[$key]);
                    } else {
                        $rows[$key]['Fields'] = $rlCommon->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);
                    }
                } else {
                    $rows[$key]['Fields'] = false;
                }

                unset($field_ids, $fields, $field_info);

                $set = count($form) + 1;
                $index = $value['Key'] ?: 'nogroup_' . $set;
                $form[$index] = $rows[$key];
            }
            unset($rows);
        }

        if (!$form) {
            return [];
        }

        $display_posted_date = $config['display_posted_date'] && !$tpl_settings['ld_posted_date_fixed'];

        foreach ($form as $gKey => &$group) {
            if ($group['Fields']) {
                foreach ($group['Fields'] as $fKey => &$value) {
                    if (!empty($value) && (!empty($listing[$value['Key']]) || $value['Type'] == 'bool')) {
                        $form[$gKey]['Fields'][$fKey]['source'] = is_string($listing[$value['Key']])
                            ? explode(',', $listing[$value['Key']])
                            : [];
                        $form[$gKey]['Fields'][$fKey]['value'] = $rlCommon->adaptValue(
                            $value,
                            $listing[$value['Key']],
                            'listing',
                            $listing['ID'],
                            true,
                            false,
                            false,
                            false,
                            $listing['Account_ID'],
                            'listing_form',
                            $listing['Listing_type']
                        );

                        $this->fieldsList[] = $form[$gKey]['Fields'][$fKey];

                        // assign price tag value and hide it
                        if ($value['Key'] == $config['price_tag_field']) {
                            $rlSmarty->assign('price_tag_value', $form[$gKey]['Fields'][$fKey]['value']);
                            $form[$gKey]['Fields'][$fKey]['Details_page'] = 0;
                        }
                    } else {
                        unset($form[$gKey]['Fields'][$fKey]);
                    }
                }
                $form[$gKey]['Fields'] = $GLOBALS['rlLang']->replaceLangKeys($form[$gKey]['Fields'], 'listing_fields', array('name'));

                // add "posted date" field to the end of first group
                if ($display_posted_date && !is_numeric(strpos($gKey, 'nogroup'))) {
                    $form[$gKey]['Fields']['posted_date'] = array(
                        'Type'         => 'text',
                        'Details_page' => 1,
                        'name'         => $GLOBALS['lang']['posted'],
                        'pName'        => 'posted',
                        'value'        => date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($listing['Date'])),
                    );
                    $display_posted_date = false;
                }
            }
        }

        $form = $GLOBALS['rlLang']->replaceLangKeys($form, 'listing_groups', array('name'));

        return $form;
    }

    /**
     * get parent category fields
     *
     * @param int $id - category id
     * @param string $table - table
     *
     * @return categories fields list
     **/
    public function getParentCatFields($id, $table)
    {
        $id = (int) $id;

        $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, ";
        $sql .= "`T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact`, `T2`.`Hidden` ";
        $sql .= "FROM `" . RL_DBPREFIX . $table . "` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Category_ID` = '{$id}' ORDER BY `T1`.`Position`";

        $fields = $this->getAll($sql, 'Key');

        if (empty($fields)) {
            $parent = $this->getOne('Parent_ID', "`ID` = '{$id}' AND `Parent_ID` != '{$id}'", 'categories');
            if (!empty($parent)) {
                return $this->getParentCatFields($parent, $table);
            }
        } else {
            return $fields;
        }
    }

    /**
     * Get listing form fields
     *
     * @since 4.7.1 - $parent_ids parameter added
     *
     * @param  integer $id         - category id
     * @param  string  $table      - table name
     * @param  string  $type       - listing type key
     * @param  mixed   $parent_ids - parent ids as array or string of comma separated ids: 12,51,61
     * @return array               - categories fields list
     **/
    public function getFormFields($id = false, $table = 'short_forms', $type = false, $parent_ids = null)
    {
        global $rlListingTypes, $config, $rlCache;

        if (!$id || !$type) {
            return false;
        }

        if ($this->tmp[$table][$id]) {
            return $this->tmp[$table][$id];
        }

        if (!$rlListingTypes) {
            $this->loadClass('ListingTypes');
        }

        /* get data from cache */
        if ($config['cache']) {
            $GLOBALS['reefless']->loadClass('Cache');
            $fields = $rlCache->get('cache_' . $table . '_fields', $id, $rlListingTypes->types[$type], $parent_ids);
            $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'listing_fields', array('name', 'default'));

            $this->tmp[$table][$id] = $fields;

            return $fields;
        }

        if ($rlListingTypes->types[$type]['Cat_general_only']) {
            $fields = $this->getParentCatFields($rlListingTypes->types[$type]['Cat_general_cat'], $table);
        } else {
            $fields = $this->getParentCatFields($id, $table);
            if (empty($fields) && $rlListingTypes->types[$type]['Cat_general_cat']) {
                $fields = $this->getParentCatFields($rlListingTypes->types[$type]['Cat_general_cat'], $table);
            }
        }

        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'listing_fields', array('name', 'default'));
        $this->tmp[$table][$id] = $fields;

        return $fields;
    }

    /**
     * reorder photos
     *
     * @package AJAX
     *
     * @param int $listing_id - listing id
     * @param string $data - sorting data
     *
     **/
    public function ajaxReorderPhoto($listing_id = false, $data = false)
    {
        global $_response, $account_info, $lang;

        $listing_id = (int) $listing_id;
        if (!$listing_id || !$data) {
            return $_response;
        }

        $_response->setCharacterEncoding('UTF-8');

        /* get listing info */
        $listing = $this->getShortDetails($listing_id);

        if ($listing['Account_ID'] != $account_info['ID'] && !defined('REALM')) {
            return $_response;
        }

        $sort = explode(';', $data);
        foreach ($sort as $value) {
            $item = explode(',', $value);
            $update[] = array(
                'fields' => array('Position' => $item[1]),
                'where'  => array('ID' => $item[0]),
            );
        }

        $GLOBALS['rlActions']->update($update, 'listing_photos');

        /* update listing data */
        register_shutdown_function(array($this, 'updatePhotoData'), $listing_id);

        return $_response;
    }

    /**
     * reorder video
     *
     * @package AJAX
     *
     * @param int $listing_id - listing id
     * @param string $data - sorting data
     *
     **/
    public function ajaxReorderVideo($listing_id = false, $data = false)
    {
        global $_response, $account_info, $lang;

        if (!$listing_id || !$data) {
            return $_response;
        }

        $listing_id = (int) $listing_id;

        $_response->setCharacterEncoding('UTF-8');

        /* get listing info */
        $listing = $this->getShortDetails($listing_id);

        if ($listing['Account_ID'] != $account_info['ID'] && !defined('REALM')) {
            return $_response;
        }

        $sort = explode(';', $data);
        foreach ($sort as $value) {
            $item = explode(',', $value);
            $update[] = array(
                'fields' => array('Position' => $item[1]),
                'where'  => array('ID' => $item[0]),
            );
        }

        $GLOBALS['rlActions']->update($update, 'listing_photos');

        return $_response;
    }

    /**
     * Get listing title
     *
     * @since 4.7.1 - $parent_ids parameter added
     *
     * @param  integer $category_id - category id
     * @param  array   $listing     - listing data from database
     * @param  string  $type        - listing type key
     * @param  string  $custom_lang - custom lang code
     * @param  mixed   $parent_ids  - parent ids as array or string of comma separated ids: 12,51,61
     * @return string               - listing title
     *
     **/
    public function getListingTitle($category_id, $listing, $type = null, $custom_lang = null, $parent_ids = null)
    {
        global $lang;

        if ($this->tmp['lfields'][$category_id]) {
            $fields = $this->tmp['lfields'][$category_id];
        } else {
            $fields = $this->getFormFields($category_id, 'listing_titles', $type, $parent_ids);
            $this->tmp['lfields'][$category_id] = $fields;
        }

        foreach ($fields as $key => $value) {
            if (array_key_exists($fields[$key]['Key'], $listing)) {
                if (!empty($listing[$fields[$key]['Key']])) {
                    $item = $GLOBALS['rlCommon']->adaptValue(
                        $value,
                        $listing[$value['Key']],
                        'listing',
                        $listing['ID'],
                        false,
                        true,
                        false,
                        $custom_lang,
                        $listing['Account_ID'],
                        'title_form',
                        $listing['Listing_type']
                    );

                    $title .= $item ? $item . ', ' : '';
                }
            }
        }

        $title = substr($title, 0, -2);
        $title = empty($title) ? 'listing' : $title;

        return $title;
    }

    /**
     * delete listing photo
     *
     * @package AJAX
     *
     * @param int $listing_id - listing id
     * @param int $photo_id - photo id
     *
     **/
    public function ajaxDeletePhoto($listing_id, $photo_id)
    {
        global $_response;

        $photo_id = (int) $photo_id;
        $listing_id = (int) $listing_id;
        $_response->setCharacterEncoding('UTF-8');

        /* get listing info */
        $listing = $this->getShortDetails($listing_id);

        if ($listing['Account_ID'] != $_SESSION['id'] && !defined('REALM')) {
            return $_response;
        }

        /* get listing photos */
        $photo = $this->fetch(array('Photo', 'Thumbnail', 'Original'), array('ID' => $photo_id), null, null, 'listing_photos', 'row');
        $sql = "DELETE FROM `{db_prefix}listing_photos` WHERE `ID` = '{$photo_id}' LIMIT 1";

        if ($this->query($sql)) {
            if (!empty($photo)) {
                unlink(RL_FILES . $photo['Photo']);
                unlink(RL_FILES . $photo['Thumbnail']);
                unlink(RL_FILES . $photo['Original']);
            }

            $photos = $this->fetch('*', array('Listing_ID' => $listing_id), "ORDER BY `ID`", null, 'listing_photos');

            // rebuild photos block
            $GLOBALS['rlSmarty']->assign_by_ref('photos', $photos);
            $tpl = 'blocks' . RL_DS . 'photo_block.tpl';
            $_response->assign('photos_dom', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));

            // rebuild upload section
            // get listing info
            $listing = $this->getShortDetails($listing_id, $plan_info = true);
            $GLOBALS['rlSmarty']->assign_by_ref('listing', $listing);

            /* get current listing photos count */
            $photos_count = $this->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}listing_photos` WHERE `Listing_ID` = '{$listing_id}'");
            $GLOBALS['rlSmarty']->assign_by_ref('photos_count', $photos_count['count']);

            $tpl = 'blocks' . RL_DS . 'photos_upload.tpl';
            $_response->assign('upload_section_dom', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));

            $listing = $this->getShortDetails($listing_id, $plan_info = true);

            if (!$listing['Image_unlim']) {
                $photos_allow = $listing['Plan_image'];

                $photos_leave = (int) $photos_allow - (int) $photos_count['count'];
                $photos_leave = str_replace('{count}', $photos_leave, $GLOBALS['lang']['upload_photo'] . ' (' . $GLOBALS['lang']['photos_leave'] . ')');
                $_response->assign('fstitle_upload', 'innerHTML', $photos_leave);
            }

            $mess = $GLOBALS['lang']['item_deleted'];
            $_response->script("$('#notice_obj').fadeOut('fast', function(){ $('#notice_message').html('{$mess}'); $('#notice_obj').fadeIn('slow'); $('#error_obj').fadeOut('fast');});");

            $_response->script("$('#gallery a.gallery_item:not(.disabled)').lightBox(); current_field = 2;");
            if ($GLOBALS['config']['img_crop_interface']) {
                $_response->includeScript(RL_TPL_BASE . "js/crop.js");
            }

            if (defined('REALM')) {
                $_response->call("setPositions");
                $_response->call("setCropMask");
            }

            return $_response;
        }
    }

    /**
     * delete listing file/image
     *
     * @package xAjax
     *
     * @param string $field  - listing field
     * @param string $value  - file/image name
     *
     **/
    public function ajaxDeleteListingFile($field, $value, $dom_id)
    {
        global $_response, $lang, $account_info;

        if (defined('IS_LOGIN') || (defined('REALM') && REALM == 'admin')) {
            $field = $GLOBALS['rlValid']->xSql($field);
            $value = $GLOBALS['rlValid']->xSql($value);

            $info = $this->fetch(array('ID', 'Account_ID'), array($field => $value), null, 1, 'listings', 'row');

            if ($info['Account_ID'] == $account_info['ID'] || defined('REALM')) {
                unlink(RL_FILES . $value);
                $this->query("UPDATE `{db_prefix}listings` SET `{$field}` = '' WHERE `ID` = '{$info['ID']}' LIMIT 1");
            } else {
                return $_response;
            }
        }

        $_response->script("
                    $('#{$dom_id}').slideUp('normal');
                    printMessage('notice', '{$lang['item_deleted']}');
                ");

        return $_response;
    }

    /**
     * Send link of listing to friend with comment
     *
     * @package xAjax
     *
     * @param   string $friend_name   - Friend name
     * @param   string $friend_email  - Friend email
     * @param   string $your_name     - Your name
     * @param   string $your_email    - Your email
     * @param   string $message       - Message
     * @param   string $security_code - Security code (captcha)
     * @param   int    $listing_id    - Listing ID
     * @return  mixed
     */
    public function ajaxTellFriend(
        $friend_name,
        $friend_email,
        $your_name = '',
        $your_email = '',
        $message = '',
        $security_code = '',
        $listing_id = 0) {
        global $_response, $lang, $rlMail;

        $errors = array();
        $error_fields = '';

        // check required fields
        if (empty($friend_name)) {
            $errors[] = str_replace(
                '{field}',
                '<span class="field_error">"' . $lang['friend_name'] . '"</span>',
                $lang['notice_field_empty']
            );
            $error_fields .= 'friend_name,';
        }

        if (empty($friend_email)) {
            $errors[] = str_replace(
                '{field}',
                '<span class="field_error">"' . $lang['friend_email'] . '"</span>',
                $lang['notice_field_empty']
            );
            $error_fields .= 'friend_email,';
        }

        if (!empty($friend_email) && !$GLOBALS['rlValid']->isEmail($friend_email)) {
            $errors[] = $lang['notice_bad_email'];
            $error_fields .= !is_numeric(strpos($error_fields, 'friend_email,')) ? 'friend_email,' : '';
        }

        if (!empty($your_email) && !$GLOBALS['rlValid']->isEmail($your_email)) {
            if (!in_array($lang['notice_bad_email'], $errors)) {
                $errors[] = $lang['notice_bad_email'];
            }

            $error_fields .= 'your_email,';
        }

        if ($GLOBALS['config']['security_img_tell_friend']
            && ($security_code != $_SESSION['ses_security_code'] || !$security_code)
        ) {
            $errors[] = $lang['security_code_incorrect'];
            $error_fields .= 'security_code,';
        }

        if (!empty($errors)) {
            $error_content = '<ul>';
            foreach ($errors as $error) {
                $error_content .= "<li>{$error}</li>";
            }
            $error_content .= '</ul>';

            $error_fields = $error_fields ? substr($error_fields, 0, -1) : '';
            $_response->script("printMessage('error', '{$error_content}', '{$error_fields}')");
        } else {
            // get listing info
            $listing_id = (int) $listing_id;
            $listing_data = $this->getListing($listing_id, true);

            // build listing link
            if ($listing_data['listing_title'] && $listing_data['listing_link']) {
                $link = "<a href=\"{$listing_data['listing_link']}\">{$listing_data['listing_title']}</a>";
            }

            $this->loadClass('Mail');

            $mail_tpl = $rlMail->getEmailTemplate('tell_friend');
            $mail_tpl['body'] = str_replace(
                array('{friend_name}', '{name}', '{message}', '{link}'),
                array($friend_name, $your_name, $message, $link),
                $mail_tpl['body']
            );

            // send e-mail for friend
            $rlMail->send($mail_tpl, $friend_email, null, $your_email, $your_name);

            $mess = $lang['notice_message_sent'];
            $_response->script("printMessage('notice', '{$mess}')");
            $fields_ids = '#friend_name,#your_name,#friend_email,#your_email,#message,#security_code';
            $_response->script("$('{$fields_ids}').val('')");
            $captcha_src = RL_LIBS_URL . 'kcaptcha/getImage.php';
            $_response->script("$('#security_img').attr('src', '{$captcha_src}?' + Math.random())");
        }

        $_response->script("$('#area_tell_friend [name=finish]').val('{$lang['send']}')");

        return $_response;
    }

    /**
     * delete listing
     *
     * @package xAjax
     *
     * @param string $id - listing ID
     *
     **/
    public function ajaxDeleteListing($id = false)
    {
        global $_response, $pages, $account_info, $lang, $config, $page_info, $rlHook;

        $id = (int) $id;
        if (!$id) {
            return false;
        }

        if (defined('IS_LOGIN')) {
            if ($this->deleteListing($id, $account_info['ID'])) {
                $exist_sql = "SELECT COUNT(`T1`.`ID`) as `cnt` FROM `{db_prefix}listings` AS `T1` ";
                $exist_sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
                $exist_sql .= "WHERE `T1`.`Account_ID` = {$account_info['ID']} AND `T1`.`Status` <> 'trash' ";
                if ($GLOBALS['listings_type']) {
                    $exist_sql .= "AND `T2`.`Type` = '{$GLOBALS['listings_type']['Key']}' ";
                }
                $exist = $this->getRow($exist_sql, 'cnt');

                if (!$exist) {
                    $href = $config['mod_rewrite'] ? SEO_BASE . $pages['add_listing'] . '.html' : RL_URL_HOME . '?page=' . $pages['add_listing'];
                    $replace = preg_replace('/(\[(.+)\])/', '<a href="' . $href . '">$2</a>', $lang['no_listings_here']);
                    $empty_mess = '<div class="info">' . $replace . '</div>';
                    $_response->assign('controller_area', 'innerHTML', $empty_mess);
                }

                $_response->script("$('#listing_{$id}').fadeOut('slow');");
                $_response->script("printMessage('notice', '{$lang['notice_listing_deleted']}')");

                /* redirect user to the previous page if it was the latest listing on the current page */
                $listings_count = $exist;
                $pages_count = ceil($listings_count / $config['listings_per_page']);

                if ($listings_count <= ($config['listings_per_page'] * ($_GET['pg'] - 1)) && $_GET['pg'] > 1) {
                    $url = SEO_BASE;
                    if ($pages_count > 1 && $pages_count != $_GET['pg']) {
                        if ($config['mod_rewrite']) {
                            $url .= $page_info['Path'] . '/index' . $pages_count . '.html';
                        } else {
                            $url .= '?page=' . $page_info['Path'] . '&pg=' . $pages_count;
                        }
                    } else {
                        $url .= $config['mod_rewrite'] ? $page_info['Path'] . '.html' : '?page=' . $page_info['Path'];
                    }
                    $_response->redirect($url);
                }
                /* redirect user to the previous page if it was the latest listing on the current page */
            }
        }

        return $_response;
    }

    /**
     * Delete listing
     *
     * @since 4.8.2 - Added $recountCategories parameter
     *
     * @param int  $id
     * @param int  $accountID
     * @param bool $recountCategories
     */
    public function deleteListing($id, $accountID = 0, $recountCategories = true)
    {
        $id = (int) $id;

        $sql = "SELECT `T1`.`ID`, `T1`.`Category_ID`, `T2`.`Type`, `T1`.`Crossed`, `T1`.`Status`, `T2`.`Type` AS `Listing_type`, `T1`.`Plan_ID`, `T1`.`Account_ID`, `T1`.`Plan_type`, ";
        $sql .= "`T1`.`Featured_ID`, `T1`.`Featured_date` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$id}' AND `T1`.`Status` <> 'trash'";

        if ($accountID) {
            //additional check for listing owner (front-end only)
            $sql .= "AND `T1`.`Account_ID` = '{$accountID}' ";
        }

        $info = $this->getRow($sql);

        if (empty($info)) {
            return false;
        }

        $GLOBALS['rlHook']->load('phpListingsAjaxDeleteListing', $info); // >= 4.4 , 4.5 > name is the same but the function is not ajax now.

        $this->deleteListingData($info['ID'], $info['Category_ID'], $info['Crossed'], $info['Listing_type'], $recountCategories);
        $GLOBALS['rlActions']->delete(array('ID' => $info['ID']), 'listings', $info['ID'], 1, $info['ID']);

        $GLOBALS['rlCache']->updateStatistics($info['Listing_type']);

        // handle membership plan
        if ($info['Plan_type'] == 'account') {
            $account_info = $this->fetch('*', array('ID' => $info['Account_ID']), null, null, 'accounts', 'row');
            $membership_plan = $this->fetch('*', array('ID' => $account_info['Plan_ID']), null, null, 'membership_plans', 'row');
            $plan_using = $this->fetch('*', array('Account_ID' => $info['Account_ID'], 'Plan_ID' => $info['Plan_ID'], 'Type' => 'account'), null, null, 'listing_packages', 'row');

            if ($plan_using) {
                $update = array(
                    'fields' => array(
                        'Listings_remains' => $membership_plan['Listing_number'] > $plan_using['Listings_remains'] ? $plan_using['Listings_remains'] + 1 : $membership_plan['Listing_number'],
                    ),
                    'where'  => array('ID' => $plan_using['ID']),
                );

                if ($membership_plan['Advanced_mode']) {
                    if ($info['Featured_ID']) {
                        $update['fields']['Featured_remains'] = $membership_plan['Featured_listings'] > $plan_using['Featured_remains'] ? $plan_using['Featured_remains'] + 1 : $membership_plan['Featured_listings'];
                    } else {
                        $update['fields']['Standard_remains'] = $membership_plan['Standard_listings'] > $plan_using['Standard_remains'] ? $plan_using['Standard_remains'] + 1 : $membership_plan['Standard_listings'];
                    }
                }

                if ($GLOBALS['rlActions']->updateOne($update, 'listing_packages')) {
                    $_SESSION['account']['plan']['Listings_remains'] = $update['fields']['Listings_remains'];
                    $_SESSION['account']['plan']['Standard_remains'] = $update['fields']['Standard_remains'];
                    $_SESSION['account']['plan']['Featured_remains'] = $update['fields']['Featured_remains'];
                }
            }
        }

        /**
         * @since 4.7.2
         */
        $GLOBALS['rlHook']->load('phpAfterDeleteListing', $info);

        return true;
    }

    /**
     * Delete all listing data
     *
     * @param  int    $id
     * @param  int    $category_id
     * @param  array  $crossed      - Crossed category IDs
     * @param  string $type         - Listing type key
     * @param  bool   $recount_cats - Update recount of categories
     * @return bool
     */
    public function deleteListingData($id = 0, $category_id = 0, $crossed = array(), $type = '', $recount_cats = true)
    {
        global $config, $rlAccount, $rlDb, $reefless, $rlCategories;

        $id = (int) $id;

        if (!$id) {
            return false;
        }

        // decrease category listing
        if ($category_id && $this->isActive($id) && $recount_cats) {
            $reefless->loadClass('Categories');

            $rlCategories->listingsDecrease($category_id, $type);
            $rlCategories->accountListingsDecrease($rlDb->getOne('Account_ID', "`ID` = {$id}", 'listings'));

            // crossed listings count control
            if ($crossed) {
                $crossed = explode(',', $crossed);
                foreach ($crossed as $crossed_id) {
                    $rlCategories->listingsDecrease($crossed_id);
                }
            }
        }

        $reefless->loadClass('Account');

        if (($config['trash'] && !$rlAccount->isAdmin())
            || ($config['trash'] && $rlAccount->isAdmin() && $_GET['controller'] != 'trash')
        ) {
            // if trash is enabled return after count changes
            return true;
        }

        $rlDb->delete(array('Listing_ID' => $id), 'listings_shows', null, 0);
        $rlDb->delete(array('Listing_ID' => $id), 'favorites', null, 0);
        $rlDb->delete(array('Listing_ID' => $id), 'tmp_categories', null, 0);

        $mediaPath = $rlDb->getOne('Original', "`Listing_ID` = {$id} AND `Original` != 'youtube'", 'listing_photos');

        if ($mediaPath) {
            ListingMedia::removeEmptyDir(RL_FILES . dirname($mediaPath), true);
            $rlDb->delete(array('Listing_ID' => $id), 'listing_photos', null, 0);
        }

        // delete files of listing fields with "image" or "file" types
        $file_fields = $rlDb->getAll(
            "SELECT `Key` FROM `{db_prefix}listing_fields` WHERE `Type` = 'image' OR `Type` = 'file' ",
            array(false, 'Key')
        );

        if ($file_fields) {
            $listing_info = $rlDb->fetch($file_fields, array('ID' => $id), null, null, 'listings', 'row');

            foreach ($listing_info as $listing_file) {
                unlink(RL_FILES . $listing_file);
            }
        }

        /**
         * @since 4.5.0
         */
        $GLOBALS['rlHook']->load('phpDeleteListingData', $id, $category_id, $crossed, $type);

        return true;
    }

    /**
     * Get featured listings
     *
     * @param  string $type      - Listing type
     * @param  int    $limit     - Listings limit
     * @param  string $field     - Filter field
     * @param  string $value     - Filter value
     * @param  string $block_key
     * @return array
     */
    public function getFeatured($type = '', $limit = 10, $field = '', $value = '', $block_key = '')
    {
        global $rlValid, $category, $rlHook, $rlCommon, $rlDb, $config;

        $rlValid->sql($field);
        $rlValid->sql($value);
        $rlValid->sql($block_key);
        $rlValid->sql($type);
        $start = 0;
        $limit = (int) $limit;
        $dbcount = false;

        $sql = "SELECT ";

        /**
         * @since 4.6.1
         */
        $rlHook->load('listingsModifyPreSelectFeatured', $dbcount);

        if ($dbcount) {
            $sql .= "SQL_CALC_FOUND_ROWS ";
        }

        $sql .= "`T1`.*, `T4`.`Path`, `T4`.`Parent_ID`, `T4`.`Type` AS `Listing_type`, ";

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T4`.`Path_{$languageKey}`, ";
            }
        }

        $sql .= "`T4`.`Key` AS `Cat_key`, `T4`.`Parent_keys`, `T4`.`Parent_IDs` ";

        if ($category['ID']) {
            // $sql .= ", CASE ";
            // $sql .= "WHEN `T1`.`Category_ID` = '{$category['ID']}' THEN 2 ";
            // $sql .= "WHEN FIND_IN_SET('{$category['ID']}', `T4`.`Parent_IDs`) > 0 THEN 1 ";
            // $sql .= "ELSE 0 ";
            // $sql .= "END AS `Category_match` ";
            $sql .= ", IF(`T1`.`Category_ID` = {$category['ID']} OR FIND_IN_SET({$category['ID']}, ";
            $sql .= "`T4`.`Parent_IDs`) > 0, 1, 0) AS `Category_match` ";
        }

        /**
         * @since 4.6.1
         */
        $rlHook->load('listingsModifySelectFeatured', $sql);

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";

        /**
         * @since 4.6.1
         */
        $rlHook->load('listingsModifyJoinFeatured', $sql);

        $sql .= "WHERE `T1`.`Featured_date` <> '0000-00-00 00:00:00' AND `T1`.`Status` = 'active' ";

        if ($type) {
            $sql .= "AND `T4`.`Type` = '{$type}' ";
        }

        if ($this->selectedIDs) {
            $sql .= "AND `T1`.`ID` NOT IN('" . implode("','", $this->selectedIDs) . "') ";
        }

        if ($field && $value) {
            $sql .= "AND `T1`.`{$field}` = '{$value}' ";
        }

        /**
         * @since 4.6.1 - Added $start param
         * @since 4.3   - Params added
         */
        $rlHook->load('listingsModifyWhereFeatured', $sql, $block_key, $limit, $start);

        $default_order = true;
        $sql .= 'ORDER BY ';

        /**
         * @since 4.8.2
         */
        $rlHook->load('listingsModifyOrderFeatured', $sql, $default_order);

        if ($default_order) {
            $sql .= ($category['ID'] ? '`Category_match` DESC, ' : '') . "`Last_show` ASC ";
        }

        $sql .= "LIMIT {$start}, {$limit}";

        $listings = $rlDb->getAll($sql);

        if (empty($listings)) {
            return false;
        }

        $this->calc = $dbcount ? (int) $rlDb->getRow('SELECT FOUND_ROWS() AS `calc`', 'calc') : 0;

        $rlHook->load('listingsAfterSelectFeatured', $sql, $block_key, $listings); // >= v4.3

        foreach ($listings as &$listing) {
            $listing_type = $type ?: $listing['Listing_type'];

            $rlCommon->listings[$listing['ID']] = $listing;

            // get listing IDs
            $this->selectedIDs[] = $IDs[] = $listing['ID'];

            // populate fields
            $fields = $this->getFormFields(
                $listing['Category_ID'],
                'featured_form',
                $listing_type,
                $listing['Parent_IDs']
            );

            foreach ($fields as &$field) {
                $field['value'] = $rlCommon->adaptValue(
                    $field,
                    $listing[$field['Key']],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields'] = $fields;
            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing_type,
                false,
                $listing['Parent_IDs']
            );
            $listing['url'] = $GLOBALS['reefless']->getListingUrl($listing);
        }

        // save show date
        if ($IDs) {
            $sql = "UPDATE `{db_prefix}listings` SET `Last_show` = NOW() ";
            $sql .= "WHERE `ID` = " . implode(" OR `ID` = ", $IDs);
            $rlDb->shutdownQuery($sql);
        }

        return $listings;
    }

    /**
     * get random listing
     *
     * @param string $type - listing type
     * @param string $mode - single, multi or list
     * @param string $number - number of listings (available in multi or list mode)
     *
     * @return array - listing information
     **/
    public function getRandom($type = false, $mode = 'single', $number = 10)
    {
        if (!$type) {
            return false;
        }

        $mode = in_array($mode, array('single', 'multi', 'list')) ? $mode : 'single';
        $GLOBALS['rlValid']->sql($type);
        $number = (int) $number;

        $sql = "SELECT DISTINCT `T1`.*, `T4`.`Path` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";

        $sql .= "WHERE `T1`.`Featured_date` ";
        $sql .= "AND `T1`.`Status` = 'active' ";
        $sql .= "AND `T4`.`Type` = '{$type}' ";

        if ($mode != 'list') {
            $sql .= "AND `T1`.`Photos_count` > 0 ";
        }
        $sql .= "AND `T4`.`Status` = 'active' ";

        if ($this->selectedIDs) {
            $sql .= "AND `T1`.`ID` NOT IN ('" . implode(',', $this->selectedIDs) . "') ";
        }

        $sql .= "GROUP BY `T1`.`ID` ORDER BY `Last_show` ASC, RAND() ";

        if ($mode == 'single') {
            $sql .= "LIMIT 1";
            $listing = $this->getRow($sql);

            if (empty($listing)) {
                return false;
            }

            $this->selectedIDs[] = $listing['ID'];

            $photos = $this->fetch(array('Photo'), array('Listing_ID' => $listing['ID']), "ORDER BY `Type` DESC, `Position` ASC", null, 'listing_photos');
            if ($photos) {
                foreach ($photos as $photo) {
                    $listing['Photos'][] = $photo['Photo'];
                }
            }

            /* save show date */
            $this->query("UPDATE `{db_prefix}listings` SET `Last_show` = NOW() WHERE `ID` = '{$listing['ID']}'");

            /* populate fields */
            $fields = $this->getFormFields($listing['Category_ID'], 'featured_form', $type);

            foreach ($fields as $fKey => $fValue) {
                $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue($fValue, $listing[$fKey], 'listing', $listing['ID'], true, false, false, false, $listing['Account_ID']);
            }

            $listing['fields'] = $fields;
            $listing['listing_title'] = $this->getListingTitle($listing['Category_ID'], $listing, $type);
            $listing['url'] = $this->url('listing', $listing);

            return $listing;
        } else {
            $sql .= "LIMIT {$number}";
            $listings = $this->getAll($sql);

            if (empty($listings)) {
                return false;
            }

            foreach ($listings as $key => $value) {
                /* get listing IDs */
                $IDs[] = $value['ID'];

                /* populate fields */
                $fields = $this->getFormFields($value['Category_ID'], 'featured_form', $type);

                foreach ($fields as $fKey => $fValue) {
                    $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue($fValue, $value[$fKey], 'listing', $value['ID'], true, false, false, false, $value['Account_ID']);
                }

                $listings[$key]['fields'] = $fields;
                $listings[$key]['Photo'] = $this->getOne('Photo', "`Listing_ID` = {$value['ID']} ORDER BY `Type` DESC, `Position` ASC", 'listing_photos');

                $listings[$key]['listing_title'] = $this->getListingTitle($value['Category_ID'], $value, $type);
                $listings[$key]['url'] = $this->url('listing', $value);
            }

            /* save show date */
            if ($IDs) {
                $sql = "UPDATE `{db_prefix}listings` SET `Last_show` = NOW() ";
                $sql .= "WHERE `ID` = " . implode(" OR `ID` = ", $IDs);
                $this->shutdownQuery($sql);
            }

            return $listings;
        }
    }

    /**
     * Calc listing visits
     *
     * @param int $id - listing ID
     **/
    public function countVisit($id)
    {
        $id = (int) $id;
        $today_period = (date('G') * 3600) + (date('i') * 60) + date('s');

        // get and check current IP address
        $ip = $this->getClientIpAddress();

        $sql = "SELECT `IP` FROM `{db_prefix}listings_shows` ";
        $sql .= "WHERE UNIX_TIMESTAMP(DATE_ADD(`Date`, INTERVAL {$today_period} SECOND)) > UNIX_TIMESTAMP() ";
        $sql .= "AND `Listing_ID` = {$id} AND `IP` = '{$ip}' ";
        $visit_ip = $this->getRow($sql);

        if (empty($visit_ip)) {
            $save_ip = array(
                'Listing_ID' => $id,
                'IP'         => $ip,
                'Date'       => 'NOW()'
            );

            $this->loadClass('Actions');
            $GLOBALS['rlActions']->insertOne($save_ip, 'listings_shows');

            // update shows
            $sql = "UPDATE `{db_prefix}listings` SET `Last_show` = NOW(), `Shows` = `Shows` + 1 ";
            $sql .= "WHERE `ID` = {$id} LIMIT 1";
            $this->query($sql);
        }
    }

    /**
     * Upgrade listing after payment process
     *
     * @param int  $listing_id - listing ID
     * @param int  $plan_id    - plan ID
     * @param int  $account_id - account ID
     * @param bool $featured   - is listing featured flag
     *
     */
    public function upgradeListing($listing_id = null, $plan_id = null, $account_id = null, $featured = false)
    {
        global $config, $rlMail, $rlDb;

        $this->loadClass('Categories');
        $this->loadClass('Cache');
        $this->loadClass('Mail');

        $plan_id = (int) $plan_id;
        $listing_id = (int) $listing_id;
        $account_id = (int) $account_id;

        // Get plan info
        $sql = "
            SELECT `T1`.`Type`, `T1`.`Listing_number`, `T1`.`Price`, `T1`.`Featured`, `T1`.`Advanced_mode`, `T1`.`Image_unlim`,
            `T1`.`Standard_listings`, `T1`.`Listing_period`, `T1`.`Featured_listings`, `T1`.`Image`, `T1`.`Limit`,
        ";

        /**
         * @since 4.5.2
         */
        $GLOBALS['rlHook']->load('phpListingsUpgradeListingSqlFields', $sql, $listing_id, $plan_id, $account_id, $featured);

        $sql .= "
            `T2`.`Listings_remains` AS `Using`, `T2`.`ID` AS `Plan_using_ID`
            FROM `{db_prefix}listing_plans` AS `T1`
            LEFT JOIN `{db_prefix}listing_packages` AS `T2`
                ON `T1`.`ID` = `T2`.`Plan_ID`
                AND `T2`.`Account_ID` = '{$account_id}'
                AND `T2`.`Type` = 'limited'
            WHERE `T1`.`ID` = '{$plan_id}' LIMIT 1
        ";
        $plan_info = $rlDb->getRow($sql);

        $listing_info = $rlDb->fetch(
            array(
                'Account_ID',
                'Category_ID',
                'Featured_ID',
                'Crossed',
                'Last_type',
                'Status',
                'Photos_count',
            ),
            array('ID' => $listing_id),
            null, null, 'listings', 'row'
        );

        $upgrade_date = 'IF((UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Pay_date`, INTERVAL ';
        $upgrade_date .= $plan_info['Listing_period'] . ' DAY)) ';
        $upgrade_date .= 'OR IFNULL(UNIX_TIMESTAMP(`Pay_date`), 0) = 0), NOW(), DATE_ADD(`Pay_date`, INTERVAL ';
        $upgrade_date .= $plan_info['Listing_period'] . ' DAY))';

        $upgrade_fdate = 'IF((UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(DATE_ADD(`Featured_date`, INTERVAL ';
        $upgrade_fdate .= $plan_info['Listing_period'] . ' DAY)) OR IFNULL(UNIX_TIMESTAMP(`Featured_date`), 0) = 0), ';
        $upgrade_fdate .= 'NOW(), DATE_ADD(`Featured_date`, INTERVAL ' . $plan_info['Listing_period'] . ' DAY))';

        if ($listing_info['Status'] != 'active') {
            $status_update = $config['listing_auto_approval'] ? 'active' : 'pending';
        }

        if (!$listing_info || !$plan_info) {
            return false;
        }

        switch ($plan_info['Type']) {
            case 'listing':
                // Update listing data
                $update = array(
                    'fields' => array(
                        'Pay_date' => $upgrade_date,
                        'Plan_ID'  => $plan_id,
                        'Cron_notified' => '0',
                    ),
                    'where'  => array(
                        'ID' => $listing_id,
                    ),
                );

                // Update listing posting date
                if ($config['posting_date_update']) {
                    $update['fields']['Date'] = 'NOW()';
                }

                if ($status_update) {
                    $update['fields']['Status'] = $status_update;
                    $update['fields']['Last_step'] = '';
                }

                if ($plan_info['Featured']) {
                    $update['fields']['Featured_ID'] = $plan_id;
                    $update['fields']['Featured_date'] = $upgrade_fdate;
                }

                $rlDb->update($update, 'listings');

                // Manage limited plan using entry
                if ($plan_info['Limit'] > 0) {
                    if ($plan_info['Using']) {
                        $plan_using_update = array(
                            'fields' => array(
                                'Account_ID'       => $account_id,
                                'Plan_ID'          => $plan_id,
                                'Listings_remains' => $plan_info['Using'] - 1,
                                'Type'             => 'limited',
                                'Date'             => 'NOW()',
                                'IP'               => $this->getClientIpAddress(),
                            ),
                            'where'  => array(
                                'ID' => $plan_info['Plan_using_ID'],
                            ),
                        );

                        $rlDb->update($plan_using_update, 'listing_packages');
                    } else {
                        $plan_using_insert = array(
                            'Account_ID'       => $account_id,
                            'Plan_ID'          => $plan_id,
                            'Listings_remains' => $plan_info['Limit'] - 1,
                            'Type'             => 'limited',
                            'Date'             => 'NOW()',
                            'IP'               => $this->getClientIpAddress(),
                        );

                        $rlDb->insert($plan_using_insert, 'listing_packages');
                    }
                }
                break;

            case 'package':
                $update = array(
                    'fields' => array(
                        'Pay_date'      => $upgrade_date,
                        'Plan_ID'       => $plan_id,
                        'Cron_notified' => '0',
                    ),
                    'where'  => array(
                        'ID' => $listing_id,
                    ),
                );

                // Update listing posting date
                if ($config['posting_date_update']) {
                    $update['fields']['Date'] = 'NOW()';
                }

                if ($plan_info['Featured'] && (!$plan_info['Advanced_mode'] || ($plan_info['Advanced_mode'] && $featured))) {
                    $update['fields']['Featured_ID'] = $plan_id;
                    $update['fields']['Featured_date'] = $upgrade_fdate;
                }

                if ($status_update) {
                    $update['fields']['Status'] = $status_update;
                    $update['fields']['Last_step'] = '';
                }

                $rlDb->update($update, 'listings');

                // Remove existing used-up package
                $rlDb->delete(
                    array(
                        'Account_ID'       => $account_id,
                        'Plan_ID'          => $plan_id,
                        'Listings_remains' => '0',
                        'Standard_remains' => '0',
                        'Featured_remains' => '0',
                        'Type'             => 'package',
                    ),
                    'listing_packages'
                );

                // Insert new package usage
                $insert = array(
                    'Account_ID'       => $account_id,
                    'Plan_ID'          => $plan_id,
                    'Listings_remains' => $plan_info['Listing_number'],
                    'Type'             => 'package',
                    'Date'             => 'NOW()',
                    'IP'               => $this->getClientIpAddress(),
                );

                if ($plan_info['Advanced_mode']) {
                    $insert['Standard_remains'] = $plan_info['Standard_listings'];
                    $insert['Featured_remains'] = $plan_info['Featured_listings'];

                    if ($featured && $plan_info['Featured_listings']) {
                        $insert['Featured_remains'] -= 1;
                    } elseif (!$featured && $plan_info['Standard_listings']) {
                        $insert['Standard_remains'] -= 1;
                    }
                }

                if ($plan_info['Listing_number']) {
                    $insert['Listings_remains'] -= 1;
                }

                $rlDb->insert($insert, 'listing_packages');
                break;

            case 'featured':
                $update = array(
                    'fields' => array(
                        'Featured_ID'   => $plan_id,
                        'Featured_date' => 'NOW()',
                    ),
                    'where'  => array(
                        'ID' => $listing_id,
                    ),
                );
                $rlDb->update($update, 'listings');

                // Manage limited plan using entry
                if ($plan_info['Limit'] > 0) {
                    if (empty($plan_info['Using'])) {
                        $plan_using_insert = array(
                            'Account_ID'       => $account_id,
                            'Plan_ID'          => $plan_id,
                            'Listings_remains' => $plan_info['Limit'] - 1,
                            'Type'             => 'limited',
                            'Date'             => 'NOW()',
                            'IP'               => $this->getClientIpAddress(),
                        );

                        $rlDb->insert($plan_using_insert, 'listing_packages');
                    } else {
                        $plan_using_update = array(
                            'fields' => array(
                                'Account_ID'       => $account_id,
                                'Plan_ID'          => $plan_id,
                                'Listings_remains' => $plan_info['Using'] - 1,
                                'Type'             => 'limited',
                                'Date'             => 'NOW()',
                                'IP'               => $this->getClientIpAddress(),
                            ),
                            'where'  => array(
                                'ID' => $plan_info['Plan_using_ID'],
                            ),
                        );

                        $rlDb->update($plan_using_update, 'listing_packages');
                    }
                }
                break;
        }

        /**
         * @since 4.5.2 - Added $plan_id, $listing_id options
         */
        $GLOBALS['rlHook']->load('phpListingsUpgradeListing', $plan_info, $plan_id, $listing_id);

        // Update listing images count if plan allows less photos then previous plan */
        if (!$plan_info['Image_unlim']
            && $plan_info['Image'] < $listing_info['Photos_count']
            && $plan_info['Type'] != 'featured') {
            $photos_count_update = array(
                'fields' => array(
                    'Photos_count' => $plan_info['Image'],
                ),
                'where'  => array(
                    'ID' => $listing_id,
                ),
            );

            $rlDb->update($photos_count_update, 'listings');
        }

        return true;
    }

    /**
     * upgrade package
     *
     * @param int $package_id - package entry ID
     * @param int $plan_id    - plan ID
     * @param int $account_id - account ID
     * @param string $txn_id  - txn ID
     * @param string $dateway - gateway name
     * @param double $total   - total summ
     *
     **/
    public function upgradePackage($package_id, $plan_id, $account_id)
    {
        $this->loadClass('Actions');
        $this->loadClass('Categories');

        $plan_id = (int) $plan_id;
        $package_id = (int) $package_id;

        /* get plan info */
        $plan_info = $this->fetch(array('Type', 'Listing_number', 'Price', 'Featured', 'Advanced_mode', 'Standard_listings', 'Featured_listings'), array('ID' => $plan_id), null, null, 'listing_plans', 'row');
        $package_info = $this->fetch(array('ID'), array('ID' => $package_id), null, null, 'listing_packages', 'row');

        if ($plan_info && $package_info) {
            /* check package exists */
            $package = $this->fetch(array('ID', 'Listings_remains', 'Standard_remains', 'Featured_remains'), array('ID' => $package_id), null, 1, 'listing_packages', 'row');

            if (empty($package)) {
                $insert = array(
                    'Account_ID'       => $account_id,
                    'Plan_ID'          => $plan_id,
                    'Listings_remains' => $plan_info['Listing_number'],
                    'Type'             => 'package',
                    'Date'             => 'NOW()',
                    'IP'               => $this->getClientIpAddress(),
                );

                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                    $insert['Standard_remains'] = $plan_info['Standard_listings'];
                }

                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                    $insert['Featured_remains'] = $plan_info['Featured_listings'];
                }

                $GLOBALS['rlActions']->insertOne($insert, 'listing_packages');
            } else {
                $update = array(
                    'fields' => array(
                        'Listings_remains' => $package['Listings_remains'] + $plan_info['Listing_number'],
                        'Type'             => 'package',
                        'Date'             => 'NOW()',
                        'IP'               => $this->getClientIpAddress(),
                    ),
                    'where'  => array(
                        'ID' => $package_id,
                    ),
                );

                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                    $update['fields']['Standard_remains'] = $package['Standard_remains'] + $plan_info['Standard_listings'];
                }

                if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                    $update['fields']['Featured_remains'] = $package['Featured_remains'] + $plan_info['Featured_listings'];
                }

                $GLOBALS['rlActions']->updateOne($update, 'listing_packages');
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * purchase package
     *
     * @param int $id - plan ID
     * @param int $plan_id    - plan ID (duplicate parameter)
     * @param int $account_id - account ID
     * @param string $txn_id  - txn ID
     * @param string $dateway - gateway name
     * @param double $total   - total summ
     * @param bool $free - free plan mode
     *
     **/
    public function purchasePackage($id, $plan_id = false, $account_id = 0, $free = false)
    {
        global $account_info, $config, $pages;

        $this->loadClass('Actions');
        $this->loadClass('Categories');
        $this->loadClass('Mail');
        $this->loadClass('Lang');

        $plan_id = (int) $id;

        // if exists
        $package_info = $this->fetch(array('ID', 'Account_ID', 'Plan_ID', 'Type'), array('Account_ID' => $account_id, 'Plan_ID' => $plan_id), null, null, 'listing_packages', 'row');
        if (!empty($package_info['ID'])) {
            return $this->upgradePackage($package_info['ID'], $plan_id, $account_id);
        }

        /* get plan info */
        $plan_info = $this->fetch(array('Type', 'Listing_number', 'Price', 'Featured', 'Advanced_mode', 'Standard_listings', 'Featured_listings'), array('ID' => $id), null, null, 'listing_plans', 'row');
        $plan_info = $GLOBALS['rlLang']->replaceLangKeys($plan_info, 'listing_plans', 'name');

        if ($plan_info) {
            $insert = array(
                'Account_ID'       => $account_id,
                'Plan_ID'          => $plan_id,
                'Listings_remains' => $plan_info['Listing_number'],
                'Type'             => 'package',
                'Date'             => 'NOW()',
                'IP'               => $this->getClientIpAddress(),
            );

            if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Standard_listings']) {
                $insert['Standard_remains'] = $plan_info['Standard_listings'];
            }

            if ($plan_info['Featured'] && $plan_info['Advanced_mode'] && $plan_info['Featured_listings']) {
                $insert['Featured_remains'] = $plan_info['Featured_listings'];
            }

            $GLOBALS['rlActions']->insertOne($insert, 'listing_packages');

            if ($free) {
                /* send notification letter to the user */
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('free_package_purchased');

                $link = SEO_BASE;
                $link .= $config['mod_rewrite'] ? $pages['add_listing'] . '.html' : '?page=' . $pages['add_listing'];

                $search = array('{plan_name}');
                $replace = array($plan_info['name']);
                $mail_tpl['body'] = str_replace($search, $replace, $mail_tpl['body']);
                $mail_tpl['body'] = preg_replace('/\[(.+)\]/', '<a href="' . $link . '">$1</a>', $mail_tpl['body']);

                $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * upload video
     *
     * @param string $type - video type (youtube or local)
     * @param mixed $source - $_FILES in case of local and URL/EMBED in case of youtube
     * @param int $listing_id - related listing ID
     *
     * @return bool
     *
     **/
    public function uploadVideo($type = false, $source = false, $listing_id = false)
    {
        global $rlHook, $l_player_file_types, $lang, $rlActions, $rlValid, $errors, $rlResize, $rlCrop, $config;

        if (!$type || !$listing_id) {
            return false;
        }

        /* file directories handler */
        $dir = RL_FILES . date('m-Y') . RL_DS . 'ad' . $listing_id . RL_DS;
        $dir_name = date('m-Y') . '/ad' . $listing_id . '/';
        $url = RL_FILES_URL . $dir_name;
        $this->rlMkdir($dir);

        $listing_id = (int) $listing_id;

        $possition = $this->getRow("SELECT MAX(`Position`) AS `Position` FROM `{db_prefix}listing_photos` WHERE `Listing_ID` = '{$listing_id}'");
        $possition = $possition['Position'] + 1;

        switch ($type) {
            case 'local':
                $video_tmp = $source['video'];
                $preview_tmp = $source['preview'];

                $rlHook->load('addVideoUpload');

                /* check video file format */
                $video_file_ext = array_reverse(explode('.', $video_tmp['name']));
                $video_file_ext = strtolower($video_file_ext[0]);

                if (empty($video_tmp['tmp_name'])) {
                    $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['file'] . '"</span>', $lang['notice_field_empty']);
                }

                if (!empty($video_tmp['tmp_name']) && !array_key_exists($video_file_ext, $l_player_file_types)) {
                    $errors[] = str_replace(array('{field}', '{ext}'), array('<span class="field_error">"' . $lang['file'] . '"</span>', '<span class="field_error">"' . $video_file_ext . '"</span>'), $lang['notice_bad_file_ext']);
                }

                /* check preview file format */
                $preview_file_ext = array_reverse(explode('.', $preview_tmp['name']));
                $preview_file_ext = $preview_file_ext[0];

                if (empty($preview_tmp['tmp_name'])) {
                    $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['preview_image'] . '"</span>', $lang['notice_field_empty']);
                }

                if (!$rlValid->isImage($preview_file_ext) && !empty($preview_tmp['tmp_name'])) {
                    $errors[] = str_replace(array('{field}', '{ext}'), array('<span class="field_error">"' . $lang['preview_image'] . '"</span>', '<span class="field_error">"' . $preview_file_ext . '"</span>'), $lang['notice_bad_file_ext']);
                }

                /* move tmp files and insert video entry to DB */
                if (empty($errors)) {
                    $file_name = 'video' . '_' . time() . mt_rand() . '.' . $video_file_ext;
                    $file_location = $dir . $file_name;

                    $thumbnail_name = 'preview_' . time() . mt_rand() . '.' . $preview_file_ext;
                    $thumbnail_location = $dir . $thumbnail_name;

                    /* move preview file */
                    if (move_uploaded_file($video_tmp['tmp_name'], $file_location)) {
                        if (move_uploaded_file($preview_tmp['tmp_name'], $thumbnail_location)) {
                            $rlCrop->loadImage($thumbnail_location);
                            $rlCrop->cropBySize(270, 180, ccCENTER);
                            $rlCrop->saveImage($thumbnail_location, $config['img_quality']);
                            $rlCrop->flushImages();

                            $rlResize->resize($thumbnail_location, $thumbnail_location, 'C', array(270, 180), true, false);
                        }

                        if (is_readable($thumbnail_location) && is_readable($file_location)) {
                            $preview_info = array(
                                'Listing_ID' => $listing_id,
                                'Position'   => $possition,
                                'Original'   => $dir_name . $file_name,
                                'Thumbnail'  => $dir_name . $thumbnail_name,
                                'Type'       => 'Video',
                            );

                            $success = $rlActions->insertOne($preview_info, 'listing_photos');
                        } else {
                            $GLOBALS['rlDebug']->logger("Can't upload video file or resize preview image.");
                            $errors[] = $lang['error_video_upload_fail'];
                        }
                    }
                }

                break;

            case 'youtube':
                if (empty($source)) {
                    $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['link_or_embed'] . '"</span>', $lang['notice_field_empty']);
                } else {
                    if (0 === strpos($source, 'http')) {
                        if (false !== strpos($source, 'youtu.be')) {
                            $source = explode('/', $source);
                            $matches[1] = array_pop($source);
                        } else {
                            preg_match('/v=([\w\-_]*)/', $source, $matches);

                            if (!$matches[1]) {
                                preg_match('/embed\/([\w\-]*)/', $source, $matches);
                            }
                        }
                    } else {
                        preg_match('/src=".*v\/(.*)\?.*"/', $source, $matches);

                        if (!$matches[1]) {
                            preg_match('/src=".*embed\/([\w\-]*)"/', $source, $matches);
                        }

                        if (!$matches[1]) {
                            preg_match('/v=([\w\-_]*)/', $source, $matches);
                        }
                    }
                }

                /* additional checking that video is available */
                $check_url = "https://www.youtube.com/oembed?format=json&url=https://www.youtube.com/watch?v=" . $matches[1];
                $ut_video = json_decode($this->getPageContent($check_url), true);

                if (!$ut_video['title']) {
                    $errors[] = $lang['youtube_check_failed'];
                }
                /* additional checking that video is available end */

                if (!$errors) {
                    if ($matches[1]) {
                        $insert = array(
                            'Listing_ID' => $listing_id,
                            'Photo'      => $matches[1],
                            'Position'   => $possition,
                            'Original'   => 'youtube',
                            'Type'       => 'video',
                        );

                        $success = $rlActions->insertOne($insert, 'listing_photos');
                    } else {
                        $errors[] = $lang['unable_parse_video_key'];
                    }
                }

                break;
        }

        return $success;
    }

    /**
     * get single listing by ID
     *
     * @param int $id - listing ID
     * @param bool $listing_title - include listing title
     * @param bool $fields - include short form fields
     *
     * @return array - listing data
     *
     **/
    public function getListing($id = false, $listing_title = false, $fields = false)
    {
        global $lang, $config, $rlListingTypes, $rlSmarty, $pages;

        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $sql = "SELECT DISTINCT `T1`.*, `T1`.`Main_photo` AS `Photo`, ";
        $sql .= "`T4`.`Path` AS `Category_path`, `T4`.`Type` AS `Listing_type`, `T4`.`Parent_IDs`, ";
        $sql .= "`T1`.`Featured_date` AS `Featured_status`, ";
        $sql .= "IF (`T1`.`Status` = 'active', 1, 0) AS `Active_status` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T4` ON `T1`.`Category_ID` = `T4`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$id}' GROUP BY `T1`.`ID` LIMIT 1 ";

        $listing = $this->getRow($sql);

        if (!$listing || !$listing['ID']) {
            return false;
        }

        $listing_type = $rlListingTypes->types[$listing['Listing_type']];

        if ($listing_title) {
            $listing['listing_title'] = $this->getListingTitle(
                $listing['Category_ID'],
                $listing,
                $listing['Listing_type'],
                false,
                $listing['Parent_IDs']
            );
            $listing['listing_link'] = $this->getListingUrl($listing);
        }

        if ($fields) {
            /* populate fields */
            $fields = $this->getFormFields(
                $listing['Category_ID'],
                'short_forms',
                $listing['Listing_type'],
                $listing['Parent_IDs']
            );

            foreach ($fields as $fKey => $fValue) {
                $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $fValue,
                    $listing[$fKey],
                    'listing',
                    $listing['ID'],
                    true,
                    false,
                    false,
                    false,
                    $listing['Account_ID'],
                    null,
                    $listing['Listing_type']
                );
            }

            $listing['fields'] = $fields;
        }

        return $listing;
    }

    /**
     * build featured listing boxes
     *
     * @todo - get featured listings by listing types and assign them to related boxes
     *
     **/
    public function buildFeaturedBoxes($listing_type_key = false)
    {
        global $rlListingTypes, $rlSmarty, $blocks, $config, $page_info;

        /* generate featured listing blocks data */
        foreach ($blocks as $key => $value) {
            if (strpos($key, 'ltfb_') === 0) {
                /* get field/value */
                preg_match("/field\='(\w+)'\s+value='(\w+)'/", $value['Content'], $matches);

                if ($matches[1] && $matches[2])
                /* splitted mode */ {
                    $field = $matches[1];
                    $value = $matches[2];

                    $f_type = explode('_', $key);
                    $f_type = $f_type[1];
                    $f_type_var = 'featured_' . $f_type . '_' . $value;

                    $$f_type_var = $this->getFeatured($f_type, $config['featured_per_page'], $field, $value, $key);
                    $rlSmarty->assign_by_ref($f_type_var, $$f_type_var);
                } else
                /* single/default mode */
                {
                    $f_type = str_replace('ltfb_', '', $key);
                    $f_type_var = 'featured_' . $f_type;

                    $$f_type_var = $this->getFeatured($f_type, $config['featured_per_page'], false, false, $key);
                    $rlSmarty->assign_by_ref($f_type_var, $$f_type_var);
                }
            }
        }
    }

    /**
     * replaces fields in the tpl with actual values for meta data of listing details page
     *
     * @param array $category_id - listing category id
     * @param array $listing_data   - listing data
     * @param array $type - keywords or description
     *
     **/
    public function replaceMetaFields($category_id = false, $listing_data = false, $type = 'description')
    {
        if (isset($listing_data['Cat_key']) && isset($listing_data['Parent_ID'])) {
            $cat_info = array(
                'Key'       => $listing_data['Cat_key'],
                'Parent_ID' => $listing_data['Parent_ID'],
            );
        } else {
            $cat_info = $this->fetch(array('Key', 'Parent_ID'), array('ID' => $category_id), null, null, 'categories', 'row');
        }

        if ($tpl = $GLOBALS['lang']['categories+listing_meta_' . $type . '+' . $cat_info['Key']]) {
            preg_match_all('/\{([^\{]+)\}+/', $tpl, $fields);

            $this->outputRowsMap = 'Key';
            $possible_fields = $GLOBALS['rlValid']->xSql($fields[1]);

            if ($possible_fields) {
                $fields_info = $this->fetch("*", array('Status' => 'active'), "AND FIND_IN_SET(`Key`, '" . implode(",", $possible_fields) . "')", null, 'listing_fields');

                foreach ($possible_fields as $key => $field_key) {
                    if ($field_key == 'ID') {
                        $replacement[$key] = $listing_data[$field_key];
                    } else {
                        $replacement[$key] = $GLOBALS['rlCommon']->adaptValue(
                            $fields_info[$field_key],
                            $listing_data[$field_key],
                            'listing',
                            $field_key == 'Category_ID' ? $listing_data['ID'] : false,
                            true,
                            false,
                            false,
                            false,
                            $listing_data['Account_ID'],
                            null,
                            $listing_data['Listing_type']
                        );
                    }

                    $pattern[$key] = $fields[0][$key];
                }
                $tpl = str_replace($pattern, $replacement, $tpl);
            }

            return $tpl ? $tpl : $GLOBALS['page_info']['meta_' . $type];
        } elseif ($cat_info['Parent_ID'] && ($GLOBALS['rlListingTypes']->types[$listing_data['Cat_type']]['Cat_general_cat'] != $category_id)) {
            unset($listing_data['Parent_ID']);
            return $this->replaceMetaFields($cat_info['Parent_ID'], $listing_data, $type);
        } elseif ($GLOBALS['rlListingTypes']->types[$listing_data['Cat_type']]['Cat_general_cat']) {
            unset($listing_data['Parent_ID']);
            if ($category_id == $GLOBALS['rlListingTypes']->types[$listing_data['Cat_type']]['Cat_general_cat']) {
                return $GLOBALS['page_info']['meta_' . $type];
            }
            return $this->replaceMetaFields($GLOBALS['rlListingTypes']->types[$listing_data['Cat_type']]['Cat_general_cat'], $listing_data, $type);
        }

        return $GLOBALS['page_info']['meta_' . $type];
    }

    /**
     * Update listing main photo
     *
     * @deprecated 4.6.0
     *
     * @param int $listingId
     */
    public function updatePhotoData($listingId)
    {
        ListingMedia::updateMediaData($listingId);
    }

    /**
     * Adding/removing listing in favorites list (for logged users only)
     *
     * @since 4.6.0 - Package changed from xAjax to ajax
     *
     * @param  int  $id
     * @param  bool $delete - Detect delete action
     */
    public function ajaxFavorite($id, $delete = false)
    {
        global $rlDb, $account_info;

        $id = (int) $id;

        if ($id && $GLOBALS['rlAccount']->isLogin() && $account_info['ID']) {
            if ($delete) {
                $rlDb->query("
                    DELETE FROM `{db_prefix}favorites`
                    WHERE `Account_ID` = {$account_info['ID']} AND `Listing_ID` = {$id}"
                );
            } else {
                $info = $rlDb->fetch(
                    array('ID'),
                    array('Account_ID' => $account_info['ID'], 'Listing_ID' => $id),
                    null,
                    1,
                    'favorites',
                    'row'
                );

                if (!$info) {
                    $rlDb->query("
                        INSERT INTO `{db_prefix}favorites` (`Account_ID`, `Listing_ID`, `Date`, `IP`)
                        VALUES ({$account_info['ID']}, {$id}, NOW(), '" . $this->getClientIpAddress() . "')"
                    );
                }
            }
        }
    }

    /**
     * Redirect to the right url of listing or category
     *
     * @param  string $mode - listing, category
     * @param  array  $data - listing or category data
     */
    public function originalUrlRedirect($mode = 'listing', $data = array())
    {
        global $pages, $listing_type, $category, $config, $reefless;

        if (!$mode
            || !$data
            || (($config['mod_rewrite'] && is_numeric(strpos($_SERVER['REQUEST_URI'], ':')))
                || (!$config['mod_rewrite'] && is_numeric(strpos($_SERVER['REQUEST_URI'], '&cf-')))
            )
            || $_GET['pg']
        ) {
            return false;
        }

        switch ($mode) {
            case 'category':
                $urlHome = RL_URL_HOME;

                // redirect for wrong category request trailing slash or .html
                if ($listing_type['Links_type'] == 'subdomain') {
                    $urlHome = preg_replace(
                        '#http(s)?://(www.)?#',
                        "http$1://" . $pages[$listing_type['Page_key']] . ".",
                        $urlHome
                    );
                }

                if ($config['mod_rewrite']
                    && $category
                    && !is_numeric(strpos($_SERVER['REQUEST_URI'], '?'))
                    && !(bool) preg_match('/index[0-9]+/', $_SERVER['REQUEST_URI'])
                    && !is_numeric(strpos($_SERVER['REQUEST_URI'], ':'))
                ) {
                    if (!$listing_type['Cat_postfix'] && !(bool) preg_match('/\\/$/', $_SERVER['REQUEST_URI'])) {
                        $path = preg_replace('/(\\..*)$/', '', $_SERVER['REQUEST_URI']);

                        Util::redirect($urlHome . ltrim($path, '/') . '/');
                    } elseif ($listing_type['Cat_postfix'] && !(bool) preg_match('/\\.html$/', $_SERVER['REQUEST_URI'])) {
                        Util::redirect($urlHome . trim($_SERVER['REQUEST_URI'], '/') . '.html');
                    }
                }

                if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
                    $multilingualPath = !empty($data['Path_' . RL_LANG_CODE]) ? $data['Path_' . RL_LANG_CODE] : null;

                    // Redirect user to multilingual path of current category if he use default path
                    if ($multilingualPath
                        && false === strpos($_SERVER['QUERY_STRING'], $multilingualPath)
                        && false === strpos($_SERVER['QUERY_STRING'], urlencode($multilingualPath))
                    ) {
                        Util::redirect($reefless->getCategoryUrl($data['ID'], RL_LANG_CODE));
                    }
                }

                break;

            case 'listing':
                // Define Request urls
                $request_uri  = ltrim($_SERVER['REQUEST_URI'], '/');
                $serverHost   = Util::idnToUtf8($_SERVER['HTTP_HOST']);
                $request_base = $GLOBALS['domain_info']['scheme'] . '://' . $serverHost . '/';

                // Remove query string
                if ($config['mod_rewrite'] && $to = strpos($request_uri, '?')) {
                    $request_uri = substr($request_uri, 0, $to);
                }

                // Define Real urls
                $real_url  = $reefless->url($mode, $data);
                $parsed    = Util::parseURL($real_url);
                $real_uri  = ltrim($parsed['path'], '/');
                $real_base = $parsed['scheme'] . '://' . Util::idnToUtf8($parsed['host']) . '/';

                /**
                 * @since 4.7.2 - $url parameter removed
                 * @since 4.7.1
                 */
                $GLOBALS['rlHook']->load('phpOriginalUrlRedirect', $request_uri, $real_uri, $real_base, $request_base);

                // Compare variables and redirect if necessary
                if (($request_uri != $real_uri && rawurldecode($request_uri) != $real_uri)
                    || $real_base != $request_base
                ) {
                    Util::redirect($real_url);
                }
                break;
        }
    }

    /**
     * @since 4.5.0
     *
     * function is to activate or deactivate listings
     * when you activate or deactivate status of related items such as:
     * plan, listing_type, account, account_type,
     *
     * @param array $data - listing id
     * @param string $status - active or inactive
     **/

    public function listingStatusControl($data = false, $status = 'active')
    {
        global $rlDb, $rlCache;

        $join = "";
        $where = "";
        foreach ($data as $field => $value) {
            switch ($field) {
                case "Category_ID":
                    $join = "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
                    $where = "(FIND_IN_SET({$value}, `T2`.`Parent_IDs`) OR ";
                    $where .= "`T1`.`{$field}` = '{$value}') AND ";
                    break;

                case "Listing_type":
                    $join = "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
                    $where .= "`T2`.`Type` = '{$value}' AND ";
                    break;

                case "Account_type":
                    $join = "JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Account_ID` ";
                    $where .= "`T2`.`Type` = '{$value}' AND ";
                    break;

                default:
                    $where .= "`T1`.`{$field}` = '{$value}' AND ";
                    break;
            }
        }

        if (!$where) {
            return false;
        }

        /* make changes to the listings count */
        $sql = "UPDATE `{db_prefix}categories` AS `TCAT` ";
        $sql .= "INNER JOIN ";
        $sql .= "( ";

        $sql .= "SELECT COUNT(`T1`.`ID`) as `cnt`, `TCP2`.`ID` as `Cat_ID` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "JOIN `{db_prefix}categories` AS `TCP` ON `TCP`.`ID` = `T1`.`Category_ID` ";
        $sql .= "RIGHT JOIN `{db_prefix}categories` AS `TCP2` ON `TCP2`.`ID` = `TCP`.`ID` ";
        $sql .= "OR FIND_IN_SET(`TCP2`.`ID`, `TCP`.`Parent_IDs`) ";
        $sql .= "OR FIND_IN_SET(`TCP2`.`ID`, `T1`.`Crossed`) ";
        $sql .= $join;
        $sql .= "WHERE ";
        $sql .= $where;

        $sign = $status == 'active' ? '+' : '-';

        $sql .= "`T1`.`Status` != '{$status}' AND `T1`.`Status` != 'pending' AND `T1`.`Status` != 'incomplete' ";
        $sql .= "GROUP BY `TCP2`.`ID`";
        $sql .= ") AS `CT` ON `TCAT`.`ID` = `CT`.`Cat_ID` ";
        $sql .= "SET `TCAT`.`Count` = `TCAT`.`Count` {$sign} `CT`.`cnt`";

        $rlDb->query($sql);
        /* make changes to the listings count */

        /* update listing statuses */
        $sql = "UPDATE `{db_prefix}listings` AS `T1` ";
        $sql .= $join;
        $sql .= "SET `T1`.`Status` = '{$status}' ";
        $sql .= "WHERE ";
        $sql .= $where;
        $sql .= "`T1`.`Status` != '{$status}' AND `T1`.`Status` != 'pending' AND `T1`.`Status` != 'incomplete' ";

        $rlDb->query($sql);
        /* update listing statuses end */

        /* recount listings number for affected accounts */
        $sql = "UPDATE `{db_prefix}accounts` AS `TA` ";
        $sql .= "LEFT JOIN `{db_prefix}listings` AS `T1` ON `T1`.`Account_ID` = `TA`.`ID` ";
        $sql .= $join;
        $sql .= "SET `TA`.`Listings_count` = ";
        $sql .= " (SELECT COUNT(*) FROM `{db_prefix}listings` AS `TL` ";
        $sql .= "  WHERE `T1`.`Status` = 'active' AND `TL`.`Account_ID` = `TA`.`ID`) ";
        $sql .= "WHERE ";
        $sql .= $where ? substr($where, 0, -4) : '1';

        $rlDb->query($sql);
        /* recount listings number end */

        $GLOBALS['rlListingTypes']->updateCountListings();

        $rlCache->updateCategories();
        $rlCache->updateStatistics();

        return true;
    }

    /**
     * Get listings by coordinates
     *
     * @since 4.8.2 - Removed $home_page parameter
     *
     * @param string $type         - Listing type key
     * @param int    $start        - Start stack
     * @param array  $coordinates  - Map coordinates to search listings between
     * @param array  $form         - Form data
     * @param bool   $group_search - Is it group search mode
     * @param double $group_lat    - Group mode request, lat
     * @param double $group_lng    - Group mode request, lng
     *
     * @return array
     */
    public function getListingsByLatLng($type = false, $start = 1, $coordinates = array(), $form = false, $group_search = false, $group_lat = false, $group_lng = false)
    {
        global $config, $data;

        if (!$type) {
            return ['listings' => null, 'count' => 0];
        }

        define('RL_SEARCH_ON_MAP', true);

        $form_key = $type . '_on_map';

        // select search form for AllInOne package by listing type
        if (!$this->getOne('ID', "`Key` = '{$form_key}' AND `Status` = 'active'", 'search_forms')) {
            $form_key = $type . '_quick';
        }

        $data = $this->adaptSerializedForm($form); // re-assign $form to $data to make it visible for 'listingsModifyWhereSearch' hook

        $this->loadClass('Search');
        $GLOBALS['rlSearch']->getFields($form_key, $type);

        if ($form) {
            unset($GLOBALS['rlSearch']->fields['address']);
        }

        $limit = $_REQUEST['device'] == 'mobile'
        ? ($config['map_search_listings_limit_mobile'] ?: 75)
        : ($config['map_search_listings_limit'] ?: 500);

        $listings = $GLOBALS['rlSearch']->search($data, $type, 0, $limit);
        $calc = $GLOBALS['rlSearch']->calc;

        ListingMedia::prepareURL($listings, true);

        return $this->prepareListings($listings, $calc);
    }

    /**
     * adapt serilaized form came from javascript request
     *
     * @param array $data - serialized form data
     *
     **/
    public function adaptSerializedForm(&$data)
    {
        global $tpl_settings;

        foreach ($data as $item) {
            if (!$item['value']) {
                continue;
            }

            // remove f[] from the field name
            $item['name'] = preg_replace('/^f\[([^\]]+)\]/', '$1', $item['name']);

            preg_match('/([^\[]+)(\[(.*?)\])?$/', $item['name'], $matches);

            if ($matches[3]) {
                $out[$matches[1]][$matches[3]] = $item['value'];
            } else {
                $out[$matches[1]] = $item['value'];
            }
        }

        return $out;
    }

    /**
     * prepare listings array for xml responce
     *
     * @param array $listings - referent to original listings array
     * @param int $count - total listings count from CALC
     *
     * @return array - listings data
     *
     **/
    public function prepareListings($listings, $count = false)
    {
        global $config, $pages, $rlListingTypes;

        $price_field_key = $config['price_tag_field'];

        // transfer fields mapping
        $transfer = array(
            'ID'            => 'ID',
            'Loc_latitude'  => 'lat',
            'Loc_longitude' => 'lng',
            'Group_count'   => 'gc',
            'listing_title' => 'title',
            //'Map_distance' => 'Map_distance',
            'Main_photo'    => 'img',
            'Main_photo_x2' => 'img_x2',
            'Photos_count'  => 'pct',
            'Featured'      => 'fd',
            'bedrooms'      => 'bds',
            'bathrooms'     => 'bts',
            'square_feet'   => 'sf',
            'fields_data'   => 'fields_data',
            'time_frame'    => 'tf',
        );

        foreach ($listings as &$listing) {
            $listing_type = &$rlListingTypes->types[$listing['Listing_type']];

            // set empty values for main fields
            $out_listing['bds'] = '';
            $out_listing['bts'] = '';
            $out_listing['price'] = '';
            $out_listing['sr'] = '';
            $out_listing['srk'] = '';
            $out_listing['tf'] = '';
            $out_listing['sf'] = '';
            $out_listing['gc'] = 1;
            $out_listing['fields_data'] = array();

            foreach ($listing as $field_key => $field_value) {
                if (isset($transfer[$field_key])) {
                    $out_listing[$transfer[$field_key]] = $field_value;
                }
            }

            // tmp solution for salary field
            $price_field_key = $listing['Listing_type'] == 'jobs' ? 'salary' : $price_field_key;

            // set price
            if ($listing['fields'][$price_field_key]['value']) {
                $out_listing['price'] = $listing['fields'][$price_field_key]['value'];
            }

            // set date field
            $out_listing['dt'] = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($listing['Date']));

            // set "property for" value
            if ($listing['fields']['sale_rent']['value']) {
                $out_listing['srk'] = $listing['sale_rent'];
                $out_listing['sr'] = $listing['fields']['sale_rent']['value'];
            }

            // set "time_frame" value
            if ($listing['fields']['time_frame']['value']) {
                $out_listing['tf'] = $listing['fields']['time_frame']['value'];
            }

            // set "square_feet" value
            if ($listing['fields']['square_feet']['value']) {
                $out_listing['sf'] = $listing['fields']['square_feet']['value'];
            }

            // set "fields_data"
            foreach ($listing['fields'] as &$field) {
                if (!$field['Details_page'] || $field['value'] == '' || in_array($field['Key'], $this->exclude_short_form_fields)) {
                    continue;
                }

                $out_listing['fields_data'][] = $field['value'];
            }

            $out_listing['url'] = $GLOBALS['reefless']->getListingUrl($listing);
            $out_listing['hasImg'] = $listing_type['Photo'];
            $out_listing['info'] = implode(', ', $out_listing['fields_data']);
            $out_listing['tmplMapListingHookData'] = '';

            /**
             * @since 4.8.0
             * @param string $out_listing['tmplMapListingHookData'] - Data to that parameter should be assigned using string concatenation, ex: $param3 .= '<div>data</div>';
             */
            $GLOBALS['rlHook']->load('phpPrepareListingsData', $listing, $out_listing, $out_listing['tmplMapListingHookData']);

            // new listing
            $out_listings[] = $out_listing;

            // clear stack
            unset($out_listing);
        }

        return array('listings' => $out_listings, 'count' => $count);
    }

    /**
     * change listing status
     *
     * @param integer $id - listing ID
     * @param string $status - new value of listing status
     *
     * @return boolean
     */
    public function changeListingStatus($id = 0, $status = '')
    {
        if (!$id || !$status) {
            return false;
        }

        $id = (int) $id;
        $status = $GLOBALS['rlValid']->xSql($status);

        if (!$id || !$status) {
            return false;
        }

        $updateData = array(
            'fields' => array(
                'Status' => $status,
            ),
            'where'  => array(
                'ID'        => $id,
                'Plan_type' => 'account',
            ),
        );

        if ($GLOBALS['rlActions']->updateOne($updateData, 'listings')) {
            return true;
        }

        return false;
    }

    /**
     * check if there is a free listing cell in a membership plan
     *
     * @param integer $id - listing ID
     * @return mixed
     */
    public function isListingOver($id = 0)
    {
        global $account_info;

        if (!$account_info || !$id) {
            return false;
        }

        $listing = $this->fetch('*', array('ID' => $id), null, 1, 'listings', 'row');
        $listing_type = 'standard';
        if ($listing['Featured_ID'] > 0 && $listing['Featured_date'] != '0000-00-00 00:00:00') {
            $listing_type = 'featured';
        }

        $membership_plan = $this->fetch('*', array('ID' => $account_info['Plan_ID']), null, 1, 'membership_plans', 'row');

        $sql = "SELECT COUNT(`ID`) AS `calc` FROM `{db_prefix}listings` WHERE `Account_ID` = '{$account_info['ID']}' AND `Plan_ID` = '{$account_info['Plan_ID']}' AND `Status` = 'active' LIMIT 1";
        $row = $this->getRow($sql);
        $total = $row['calc'];

        if ($membership_plan['Advanced_mode']) {
            if ($listing_type == 'featured') {
                $sql = "SELECT COUNT(`ID`) AS `calc` FROM `{db_prefix}listings` WHERE `Account_ID` = '{$account_info['ID']}' AND `Plan_ID` = '{$account_info['Plan_ID']}' AND `Featured_ID` > 0 AND `Featured_date` IS NOT NULL AND `Status` = 'active' LIMIT 1";
            } else {
                $sql = "SELECT COUNT(`ID`) AS `calc` FROM `{db_prefix}listings` WHERE `Account_ID` = '{$account_info['ID']}' AND `Plan_ID` = '{$account_info['Plan_ID']}' AND `Featured_ID` <= 0 AND `Featured_date` IS NULL AND `Status` = 'active' LIMIT 1";
            }
            $row = $this->getRow($sql);
            $sub_total = (int) $row['calc'];

            if ($sub_total >= $membership_plan[ucfirst($listing_type) . '_listings'] && $membership_plan[ucfirst($listing_type) . '_listings'] > 0) {
                return $sub_total;
            }
        }

        if ($total >= $membership_plan['Listing_number'] && $membership_plan['Listing_number'] > 0) {
            return $total;
        }
        return false;
    }

    /**
     * check if there is a free listing cell in a membership plan by listing type
     *
     * @param integer $id
     * @param string $type
     *
     * @return boolean
     */
    public function isListingOverByType($id = 0, $type = '')
    {
        global $account_info, $membership_plan;
        if (!$type || !$id) {
            return false;
        }
        if ($membership_plan['Advanced_mode']) {
            if ($type == 'featured') {
                $sql = "SELECT COUNT(`ID`) AS `calc` FROM `{db_prefix}listings` WHERE `Account_ID` = '{$account_info['ID']}' AND `Plan_ID` = '{$account_info['Plan_ID']}' AND `Featured_ID` > 0 AND `Featured_date` IS NOT NULL AND `Status` = 'active' LIMIT 1";
                $row = $this->getRow($sql);
                $total = (int) $row['calc'];
                if ($total >= $membership_plan['Featured_listings'] && $membership_plan['Featured_listings'] > 0) {
                    return $total;
                }
            }
            if ($type == 'standard') {
                $sql = "SELECT COUNT(`ID`) AS `calc` FROM `{db_prefix}listings` WHERE `Account_ID` = '{$account_info['ID']}' AND `Plan_ID` = '{$account_info['Plan_ID']}' AND `Featured_ID` <= 0 AND `Featured_date` IS NULL AND `Status` = 'active' LIMIT 1";
                $row = $this->getRow($sql);
                $total = (int) $row['calc'];
                if ($total >= $membership_plan['Standard_listings'] && $membership_plan['Standard_listings'] > 0) {
                    return $total;
                }
            }
        }
        return false;
    }

    /**
     * make listing featured or cancel featured status
     *
     * @param integer $id
     * @param string $featured
     *
     * @return boolean
     */
    public function changeFeaturedStatus($id = 0, $type = '')
    {
        global $account_info, $membership_plan;

        if (!$id || empty($type)) {
            return false;
        }
        $id = (int) $id;
        if ($membership_plan['Advanced_mode']) {
            if ($type == 'featured') {
                $is_featured = $this->getOne("ID", "`Featured_ID` != 0 AND `Featured_date` IS NULL AND `Account_ID` = {$account_info['ID']} AND `Plan_type` = 'account' AND `ID` = {$id}", "listings");
                if (!$is_featured) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_ID` = {$account_info['Plan_ID']}, `Featured_date` = '{$account_info['Pay_date']}', `Last_type` = 'featured' WHERE `Account_ID` = {$account_info['ID']} AND `ID` = {$id} AND `Plan_type` = 'account' LIMIT 1";
                }
            } else {
                $is_standard = $this->getOne("ID", "`Featured_ID` = 0 AND `Featured_date` = '0000-00-00 00:00:00' AND `Account_ID` = {$account_info['ID']} AND `Plan_type` = 'account' AND `ID` = {$id}", "listings");
                if (!$is_standard) {
                    $sql = "UPDATE `{db_prefix}listings` SET `Featured_ID` = 0, `Featured_date` = '0000-00-00 00:00:00', `Last_type` = 'standard' WHERE `Account_ID` = {$account_info['ID']} AND `ID` = {$id} AND `Plan_type` = 'account' LIMIT 1";
                }
            }
            if ($this->query($sql)) {
                $sql = "SELECT * FROM `{db_prefix}listing_packages` WHERE `Plan_ID` = {$account_info['Plan_ID']} AND `Type` = 'account' AND `Account_ID` = {$account_info['ID']} LIMIT 1";
                $plan_using = $this->getRow($sql);

                if ($plan_using['Listings_remains'] > 0) {
                    $standard = $plan_using['Standard_remains'];
                    $featured = $plan_using['Featured_remains'];
                    if ($type == 'standard' && $plan_using['Standard_remains'] > 0) {
                        $standard = $plan_using['Standard_remains'] - 1;
                    } elseif ($membership_plan['Standard_listings'] > $plan_using['Standard_remains']) {
                        $standard = $plan_using['Standard_remains'] + 1;
                    }
                    if ($type == 'featured' && $plan_using['Featured_remains'] > 0) {
                        $featured = $plan_using['Featured_remains'] - 1;
                    } elseif ($membership_plan['Featured_listings'] > $plan_using['Featured_remains']) {
                        $featured = $plan_using['Featured_remains'] + 1;
                    }
                }

                $plan_using_update = array(
                    'fields' => array(
                        'Standard_remains' => $membership_plan['Standard_listings'] > 0 && $standard > 0 ? $standard : 0,
                        'Featured_remains' => $membership_plan['Featured_listings'] > 0 && $featured > 0 ? $featured : 0,
                        'Date'             => 'NOW()',
                        'IP'               => $this->getClientIpAddress(),
                    ),
                    'where'  => array(
                        'ID' => $plan_using['ID'],
                    ),
                );
                $result = $GLOBALS['rlActions']->updateOne($plan_using_update, 'listing_packages');

                if ($result && isset($_SESSION['account']['plan'])) {
                    $_SESSION['account']['plan']['Standard_remains'] = (int) $plan_using_update['fields']['Standard_remains'];
                    $_SESSION['account']['plan']['Featured_remains'] = (int) $plan_using_update['fields']['Featured_remains'];
                }
                return true;
            }
        }
        return false;
    }

    public function afterImport()
    {
        $GLOBALS['rlHook']->load('afterImport');
    }
}
