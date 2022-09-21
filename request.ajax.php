<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: REQUEST.AJAX.PHP
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
use Flynax\Utils\Category;
use Flynax\Utils\File;
use Flynax\Utils\ListingMedia;
use Flynax\Utils\Profile;
use Flynax\Utils\Valid;
use Flynax\Utils\Util;

define('AJAX_FILE', true);

require_once 'includes/config.inc.php';

header('Access-Control-Allow-Origin: ' . rtrim(RL_URL_HOME, '/'));
header('Access-Control-Allow-Credentials: true');

require_once RL_INC . 'control.inc.php';

$rlHook->load('init');

// set language
$request_lang = @$_REQUEST['lang'] ?: $config['lang'];
$rlValid->sql($request_lang);

$languages = $rlLang->getLanguagesList();
$rlLang->defineLanguage($request_lang);
$rlLang->modifyLanguagesList($languages);

$lang = $rlLang->getLangBySide('frontEnd', $request_lang);

// load system libs
require_once RL_LIBS . 'system.lib.php';

// set timezone
$reefless->setTimeZone();
$reefless->setLocalization();

// load main types classes
$reefless->loadClass('ListingTypes', null, false, true);
$reefless->loadClass('AccountTypes', null, false, true);

// get page paths
$reefless->loadClass('Navigator');
$pages = $rlNavigator->getAllPages();

// load classes
$reefless->loadClass('Account');
$reefless->loadClass('MembershipPlan');

// define seo base
$seo_base = RL_URL_HOME;
if ($config['lang'] != RL_LANG_CODE && $config['mod_rewrite']) {
    $seo_base .= RL_LANG_CODE . '/';
}
if (!$config['mod_rewrite']) {
    $seo_base .= 'index.php';
}

$rlHook->load('seoBase');
define('SEO_BASE', $seo_base);

$rlSmarty->registerFunctions();

/**
 * @since 4.8.2
 */
$account_info = $_SESSION['account'];

// validate data
$request_mode = $rlValid->xSql($_REQUEST['mode']);
$request_item = $rlValid->xSql($_REQUEST['item']);

// out variable will be printed as response
$out = array();

/**
 * @since 4.6.0
 */
$rlHook->load('requestAjaxBeforeSwitchCase', $request_mode, $request_item, $request_lang);

// do task by requested mode
switch ($request_mode) {
    case 'listing':
        $request_type = $rlValid->xSql($_REQUEST['type']);
        $request_field = $rlValid->xSql($_REQUEST['field']);

        $reefless->loadClass('Common');
        $reefless->loadClass('Listings');
        $reefless->loadClass('Search');

        $data['keyword_search'] = $request_item;
        $fields['keyword_search'] = array(
            'Type' => 'text',
        );

        $rlSearch->fields = $fields;
        $listings = $rlSearch->search($data, false, false, 20);

        foreach ($listings as $listing) {
            $out[] = array(
                'listing_title' => $listing['listing_title'],
                'Category_name' => $lang['categories+name+' . $listing['Cat_key']],
                'Category_path' => $reefless->getCategoryUrl($listing['Category_ID']),
                'Listing_path'  => $reefless->url('listing', $listing),
            );
        }
        unset($listings);

        break;

    case 'photo':
        $pattern = '/_sold_[a-z]{2}/';
        if ((bool) preg_match($pattern, $request_item)) {
            $request_item = preg_replace($pattern, '', $request_item);
        }

        $out = RL_FILES_URL . $rlDb->getOne('Photo', "`Thumbnail` = '{$request_item}'", 'listing_photos');
        break;

    case 'getListingsByCoordinates':
        require_once RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';

        $type = $rlValid->xSql($_REQUEST['type']);
        $start = (int) $_REQUEST['start'];
        $coordinates = array(
            'centerLat'    => (double) $_REQUEST['centerLat'],
            'centerLng'    => (double) $_REQUEST['centerLng'],
            'northEastLat' => (double) $_REQUEST['northEastLat'],
            'northEastLng' => (double) $_REQUEST['northEastLng'],
            'southWestLat' => (double) $_REQUEST['southWestLat'],
            'southWestLng' => (double) $_REQUEST['southWestLng'],
        );
        $form         = $_REQUEST['form'];
        $group_search = $_REQUEST['group'] ? true : false;
        $group_lat    = $rlValid->xSql($_REQUEST['lat']);
        $group_lng    = $rlValid->xSql($_REQUEST['lng']);

        $reefless->loadClass('Listings');
        $out = $rlListings->getListingsByLatLng($type, $start, $coordinates, $form, $group_search, $group_lat, $group_lng);

        break;

    case 'getCategoriesByType':
        $type_key = $rlValid->xSql($_REQUEST['type']);
        $category_id = (int) $_REQUEST['id'];

        $reefless->loadClass('Categories');
        $out = array_values($rlCategories->getCatTree($category_id, $type_key));
        break;

    case 'category':
        $sql = "SELECT `T1`.`ID`, `T1`.`Path`, `T1`.`Count`, `T1`.`Type`, `T2`.`Value` AS `name`, `T3`.`Cat_postfix` ";
        $sql .= "FROM `{db_prefix}categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('categories+name+', `T1`.`Key`) = `T2`.`Key` AND ";
        $sql .= "`T2`.`Code` = '{$request_lang}' AND `T2`.`Key` LIKE 'categories+name+%' ";
        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T3` ON `T1`.`Type` = `T3`.`Key` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T3`.`Status` = 'active' AND ";
        if ($request_item == 'rest') {
            $sql .= "`T2`.`Value` RLIKE '^[0-9]' ";
        } else {
            $sql .= "`T2`.`Value` LIKE BINARY '{$request_item}%' ";
        }
        $sql .= "GROUP BY `T1`.`ID` ";
        $sql .= "ORDER BY `T1`.`Count` DESC, `Value` ASC ";
        $sql .= "LIMIT 50";

        $out = $rlDb->getAll($sql);

        foreach ($out as &$category) {
            $category['Cat_type_page'] = $pages[$rlListingTypes->types[$category['Type']]['Page_key']];
        }

        break;

    case 'changeListingStatus':
        $reefless->loadClass('Actions');
        $reefless->loadClass('Listings');
        $total = $rlListings->isListingOver($request_item);
        if ($total && $_REQUEST['value'] == 'active') {
            $out = array('status' => 'failure', 'message_text' => str_replace('{count}', $total, $lang['not_available_free_cells']));
        } else {
            $result = $rlListings->changeListingStatus($request_item, $_REQUEST['value']);
            $out = $result ? array('status' => 'ok', 'message_text' => $lang['status_changed_ok']) : array('status' => 'failure', 'message_text' => $lang['status_changed_fail']);
        }

        break;

    case 'changeListingFeaturedStatus':
        $reefless->loadClass('Listings');
        $reefless->loadClass('Actions');
        $membership_plan = $rlDb->fetch('*', array('ID' => $account_info['Plan_ID']), null, 1, 'membership_plans', 'row');
        if ($total = $rlListings->isListingOverByType($request_item, $_REQUEST['value'])) {
            $out = array('status' => 'failure', 'message_text' => str_replace('{count}', $total, $lang['not_available_free_cells_' . $_REQUEST['value']]));
        } else {
            $result = $rlListings->changeFeaturedStatus($request_item, $_REQUEST['value']);
            $out = $result ? array('status' => 'ok', 'message_text' => $lang['type_changed_ok']) : array('status' => 'failure', 'message_text' => $lang['type_changed_fail']);
        }

        break;

    case 'contactOwner':
        $name = $_REQUEST['name'];
        $email = $rlValid->xSql($_REQUEST['email']);
        $phone = $rlValid->xSql($_REQUEST['phone']);
        $message = $_REQUEST['message'];
        $security_code = $rlValid->xSql($_REQUEST['security_code']);
        $listing_id = (int) $_REQUEST['listing_id'];
        $account_id = (int) $_REQUEST['account_id'];
        $box_index = (int) $_REQUEST['box_index'];

        $reefless->loadClass('Message');
        $out = $rlMessage->contactOwner($name, $email, $phone, $message, $security_code, $listing_id, $box_index, $account_id);

        break;

    case 'getCategoryLevel':
        $categories = Category::getCategories($_REQUEST['type'], $_REQUEST['parent_id'], 1, $_REQUEST['account_id'], $_REQUEST['from_db']);
        $out = array(
            'status'  => 'OK',
            'results' => &$categories,
            'count'   => count($categories),
        );

        break;

    case 'addUserCategory':
        $errors = [];

        if ($user_category_id = Category::addUserCategory($_REQUEST['parent_id'], $_REQUEST['name'], $_REQUEST['account_id'], $errors)) {
            $out = array(
                'status'  => 'OK',
                'results' => $user_category_id,
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $errors,
            );
        }

        break;

    case 'pictureUpload':
        $out = (new Flynax\Classes\ListingPictureUpload)->init();
        break;

    case 'mediaDelete':
        $out['status'] = ListingMedia::delete($_REQUEST['listing_id'], $_REQUEST['media_id'], $account_info)
        ? 'OK'
        : 'ERROR';

        break;

    case 'mediaChangeDescription':
        $out['status'] = ListingMedia::manageDescription($_REQUEST['listing_id'], $_REQUEST['media_id'], $_REQUEST['description'], $account_info)
        ? 'OK'
        : 'ERROR';

        break;

    case 'mediaSetOrder':
        $out['status'] = ListingMedia::reorder($_REQUEST['listing_id'], $_REQUEST['data'], $account_info)
        ? 'OK'
        : 'ERROR';

        break;

    case 'mediaAddYouTube':
        // Define the instance class by referrer controller
        $class_name = $_REQUEST['controller'] == 'edit_listing'
        ? 'Flynax\Classes\EditListing'
        : 'Flynax\Classes\AddListing';

        // Get/create instance
        $instance = $class_name::getInstance();
        $plan_info = $instance->plans[$instance->planID];

        if ($results = ListingMedia::addYouTube(
            $_REQUEST['listing_id'],
            $_REQUEST['link'],
            $account_info,
            $plan_info,
            $_REQUEST['position']
        )) {
            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }

        break;

    case 'pictureCrop':
        $listing_id = (int) $_REQUEST['listing_id'];
        $media_id   = (int) $_REQUEST['media_id'];
        $data       = $_REQUEST['data'];

        if (!$listing_id || !$media_id) {
            return false;
        }

        $condition = array('ID' => $media_id, 'Listing_ID' => $listing_id);
        $account_id = $rlDb->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');
        $picture = $rlDb->fetch('*', $condition, null, 1, 'listing_photos', 'row');

        if ($account_id != $account_info['ID'] || !$picture) {
            return false;
        }

        if (!is_numeric($data['x']) || !is_numeric($data['y']) || !$data['width'] || !$data['height']) {
            return false;
        }

        $picture['Crop'] = array_map('round', $data);

        if ($results = ListingMedia::updatePicture($picture)) {
            ListingMedia::prepare($results);
            ListingMedia::updateMediaData($listing_id);

            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }

        break;

    case 'pictureRotate':
        $listing_id = (int) $_REQUEST['listing_id'];
        $media_id   = (int) $_REQUEST['media_id'];

        if (!$listing_id || !$media_id) {
            return false;
        }

        $condition = array('ID' => $media_id, 'Listing_ID' => $listing_id);
        $account_id = $rlDb->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');
        $picture = $rlDb->fetch('*', $condition, null, 1, 'listing_photos', 'row');

        if ($account_id != $account_info['ID'] || !$picture) {
            return false;
        }

        $picture['Angle'] = $picture['Angle'] == '-270' ? 0 : ($picture['Angle'] - 90);

        if ($picture['Crop']) {
            $picture['Crop'] = json_decode($picture['Crop'], true);
            $picture['Crop'] = ListingMedia::getUpdatedCropData($picture, $picture['Angle']);
        }

        if ($results = ListingMedia::updatePicture($picture)) {
            ListingMedia::prepare($results);
            ListingMedia::updateMediaData($listing_id);

            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }

        break;

    case 'tmpRotate':
        $media_id = (int) $_REQUEST['media_id'];

        if (!$account_info || !$media_id) {
            return false;
        }

        ListingMedia::tmpRotate($media_id);

        break;

    case 'manageListing':
        require_once RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS . 'settings.tpl.php';

        // Define the instance class by referrer controller
        $class_name = $_REQUEST['controller'] == 'edit_listing'
        ? 'Flynax\Classes\EditListing'
        : 'Flynax\Classes\AddListing';

        // Get/create instance
        $instance = $class_name::getInstance();
        $results = $instance->ajaxAction($_REQUEST['action'], $_REQUEST['data'], $account_info);

        if ($results !== false) {
            // Save instance
            $class_name::saveInstance($instance);

            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }

        break;

    case 'loadPaymentForm':
        $rlSmarty->assign_by_ref('lang', $lang);

        $reefless->loadClass('Payment');
        $gateway = $rlValid->xSql($_REQUEST['gateway']);
        $form = $_REQUEST['form'] ? $_REQUEST['form'] : 'form.tpl';
        $out = array(
            'status' => 'OK',
            'html'   => $rlPayment->loadPaymentForm($gateway, $form),
        );
        break;

    case 'ajaxFavorite':
        $id = (int) $_REQUEST['id'];
        $delete_action = (bool) $_REQUEST['delete'];
        $rlListings->ajaxFavorite($id, $delete_action);
        break;

    case 'deleteTmpFile':
        if ($results = File::removeTmpFile($_REQUEST['field'], $_REQUEST['parent'])) {
            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'deleteFile':
        $data = $_REQUEST;

        if ($result = File::removeFile($data['field'], $data['value'], $data['type'], (int) $account_info['ID'])) {
            $out = array('status' => 'OK', 'results' => $result);
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'profilePictureUpload':
        $out = (new Flynax\Classes\ProfileThumbnailUpload)->init();
        break;

    case 'profileThumbnailCrop':
        if ($results = Profile::cropThumbnail($_REQUEST['data'], $account_info)) {
            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'profileThumbnailDelete':
        if ($results = Profile::deleteThumbnail($account_info['ID'])) {
            $out = array(
                'status'  => 'OK',
                'results' => $results,
            );
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'removeAccount':
        $reefless->loadClass('Admin', 'admin');

        $id       = intval($_SESSION['account']['ID'] ?: $_REQUEST['id']);
        $password = Valid::escape($_REQUEST['pass']);
        $hash     = Valid::escape($_REQUEST['hash']);
        $result   = false;
        $message  = '';

        if ($password && $_SESSION['account'] && $id) {
            $db_pass = $rlDb->fetch(array('Password'), array('ID' => $id), null, null, 'accounts', 'row');

            if (FLSecurity::verifyPassword($password, $db_pass['Password'])
                && $rlAdmin->deleteAccountDetails($id, null, true)
            ) {
                $result = true;
            } else {
                $message = $lang['notice_pass_bad'];
            }
        } elseif ($id && $hash) {
            if ($rlDb->getOne('Loc_address', "`ID` = {$id}", 'accounts') == md5(base64_decode($hash))
                && $rlAdmin->deleteAccountDetails($id, null, true)
            ) {
                $result = true;
            }
        }

        if ($result === true) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['remote_delete_account_removed']);

            $out = array('status' => 'OK', 'redirect' => $reefless->getPageUrl('home'));
        } else {
            $out = array('status' => 'ERROR', 'message' => $message);
        }
        break;

    case 'getListingData':
        $id = (int) $_REQUEST['id'];

        if ($id) {
            $data = $rlListings->getShortDetails($id);
            $info = [];

            require_once RL_ROOT . "templates/{$config['template']}/settings.tpl.php";

            foreach ($data['fields'] as $field) {
                if (!$field['value']
                    || !$field['Details_page']
                    || $field['Key'] == $config['price_tag_field']
                    || in_array($field['Key'], $tpl_settings['listing_grid_except_fields'])
                ) {
                    continue;
                }

                $info[] = $field['value'];
            }

            ListingMedia::prepareURL($data, true);

            if ($data) {
                $results = $rlListings->prepareListings([$data])['listings'][0];

                /**
                 * @todo Remove code if the above listing preparation works properly
                 */
                // $results = array(
                //     'ID'     => $data['ID'],
                //     'url'    => $data['url'],
                //     'img'    => $data['Main_photo'],
                //     'img_x2' => $data['Main_photo_x2'],
                //     'title'  => $data['listing_title'],
                //     'price'  => $data['fields'][$config['price_tag_field']]['value'],
                //     'info'   => implode(', ', $info),
                //     'hasImg' => $rlListingTypes->types[$data['Listing_type']]['Photo']
                // );
            }
        }

        $out = array(
            'status' => $results ? 'OK' : 'ERROR',
            'results' => $results
        );
        break;

    case 'getAccountData':
        $id = (int) $_REQUEST['id'];

        $ignor_fields = array('First_name', 'Last_name', 'company_name');

        if ($id) {
            $data  = $rlAccount->getProfile($id);
            $short = $rlAccount->getShortDetails($data, $data['Account_type_ID']);
            $info  = [];

            foreach ($short as $field) {
                if (!$field['value']
                    || !$field['Details_page']
                    || in_array($field['Key'], $ignor_fields)
                ) {
                    continue;
                }

                $info[] = $field['value'];
            }

            Profile::prepareURL($data);

            if ($data) {
                $results = array(
                    'ID'     => $data['ID'],
                    'url'    => $data['Personal_address'],
                    'img'    => $data['Photo'],
                    'img_x2' => $data['Photo_x2'],
                    'title'  => $data['Full_name'],
                    'info'   => implode(', ', $info)
                );
            }
        }

        $out = array(
            'status' => $results ? 'OK' : 'ERROR',
            'results' => $results
        );
        break;

    case 'placesAutocomplete':
        $query    = json_decode($_REQUEST['query'], true) ?: $_REQUEST['query'];
        $provider = $config['geocoding_provider'] == 'google'
        ? 'googlePlaces' // switch to googlePlaces for better results
        : $config['geocoding_provider'];

        if (strlen(isset($query['query']) ? $query['query'] : $query) < 3) {
            return array(
                'status' => 'ERROR',
                'message' => 'Query string is too short, 3 characters is miniumal length'
            );
        }

        if (in_array($_REQUEST['provider'], ['nominatim', 'googlePlaces'])) {
            $provider = $_REQUEST['provider'];
        }

        // Optimize country data
        if (is_array($query)) {
            if ($provider == 'googlePlaces') {
                $query['country'] = $query['country-code'];
            }

            unset($query['country-code']);

            if ($provider == 'nominatim') {
                unset($query['country-code']);
                $query = implode(',', $query);
            }
        }

        $data = Util::geocoding($query, false, $_REQUEST['lang'], $provider);

        $out = array(
            'status' => $data ? 'OK' : 'ERROR',
            'results' => $data
        );

        break;

    case 'placesСoordinates':
        $place_id = $_REQUEST['place_id'];

        if (!$place_id || !$config['google_server_map_key']) {
            return array(
                'status' => 'ERROR',
                'message' => !$config['google_server_map_key']
                ? 'No google api key specified'
                : 'No place_id param passed'
            );
        }

        $host = 'https://maps.googleapis.com/maps/api/place/details/json';
        $params = array(
            'placeid' => $place_id,
            'key' => $config['google_server_map_key']
        );

        $request  = $host . '?' . http_build_query($params);
        $response = Util::getContent($request);
        $data = json_decode($response);

        $out = array(
            'status' => $data->status,
            'results' => $data->status ? $data->result->geometry->location : null
        );
        break;

    case 'geocoder':
        $params = is_string($_REQUEST['params']) && json_decode($_REQUEST['params'])
        ? json_decode($_REQUEST['params'], true)
        : $_REQUEST['params'];
        $provider = $config['geocoding_provider'];

        if (is_array($params) && in_array($params['provider'], ['nominatim', 'googlePlaces'])) {
            $provider = $params['provider'];
            unset($params['provider']);
        }

        $data = Util::geocoding($params, false, null, $provider);

        $out = array(
            'status' => $data ? 'OK' : 'ERROR',
            'results' => $data
        );

        break;

    case 'cancelSubscription':
        $service = Valid::escape($_REQUEST['service']);
        $itemID = (int) $_REQUEST['itemID'];
        $subscriptionID = (int) $_REQUEST['subscriptionID'];
        $isPage = (bool) $_REQUEST['isPage'];

        $reefless->loadClass('Subscription');
        $out = $rlSubscription->ajaxCancelSubscription($service, $itemID, $subscriptionID, $isPage);
        break;

    case 'ajaxSaveSearch':
        $reefless->loadClass('Search');
        $out = $GLOBALS['rlSearch']->ajaxSaveSearch(
            Valid::escape($_REQUEST['type']),
            $account_info && $account_info['ID'] ? (int) $account_info['ID'] : null,
            Valid::escape($_SESSION['post_form_key'])
        );
        break;

    case 'ajaxMassSavedSearch':
        $reefless->loadClass('Search');
        $out = $GLOBALS['rlSearch']->ajaxMassSavedSearch(
            Valid::escape($_REQUEST['items']),
            Valid::escape($_REQUEST['action']),
            $account_info && $account_info['ID'] ? (int) $account_info['ID'] : null
        );
        break;

    case 'ajaxCheckSavedSearch':
        $reefless->loadClass('Search');
        $out = $GLOBALS['rlSearch']->ajaxCheckSavedSearch(
            (int) $_REQUEST['id'],
            $account_info && $account_info['ID'] ? (int) $account_info['ID'] : null
        );
        break;

    case 'sendMessageToVisitor':
        $reefless->loadClass('Message');

        $out['status'] = $rlMessage->contactVisitor(
            $_REQUEST['message'],
            $_REQUEST['email'],
            $_REQUEST['name']
        ) ? 'OK' : 'ERROR';
        break;

    case 'getPhone':
        $id     = (int) $_REQUEST['id'];
        $entity = Valid::escape($_REQUEST['entity']);
        $field   = Valid::escape($_REQUEST['field']);

        if (!$id || !$entity || !in_array($entity, ['listing', 'account']) || !$field) {
            $out['status'] = 'ERROR';
            break;
        }

        $field  = $rlDb->fetch('*', ['Key' => $field], null, 1, "{$entity}_fields", 'row');
        $phone = $reefless->parsePhone($rlDb->getOne($field['Key'], "`ID` = {$id}", "{$entity}s"), $field, false);
        $out   = ['status' => 'OK', 'phone' => $phone];
        break;

    case 'savePhoneClick':
        if ($listingID = (int) $_REQUEST['listingID']) {
            $rlDb->insertOne([
                'Listing_ID' => $listingID,
                'Account_ID' => $account_info && $account_info['ID'] ? $account_info['ID'] : 0,
                'Date'       => 'NOW()',
                'IP'         => Util::getClientIP(),
            ], 'phone_clicks');
            $out['status'] = 'OK';
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'getCallOwnerData':
        if ($listingID = (int) $_REQUEST['listingID']) {
            $listing_data = $rlDb->fetch('*', ['ID' => $listingID, 'Status' => 'active'], null, 1, 'listings', 'row');

            if ($listing_data) {
                $listing_type_key = $rlDb->getOne('Type', "`ID` = {$listing_data['Category_ID']}", 'categories');
                $listing_type = $rlListingTypes->types[$listing_type_key];
                $profile = $rlAccount->getProfile((int) $listing_data['Account_ID']);
                $listing = $rlListings->getListingDetails($listing_data['Category_ID'], $listing_data, $listing_type);
                $date = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), strtotime($profile['Date']));

                $results = [
                    'full_name' => $profile['Full_name'],
                    'seller_data' => str_replace(
                        ['{account_type}', '{date}'],
                        [$profile['Type_name'], $date],
                        $lang['account_type_since_data']
                    ),
                    'phrases' => [
                        'call_owner_additional_numbers' => $rlLang->getSystem('call_owner_additional_numbers')
                    ]
                ];

                foreach ($profile['Fields'] as $field) {
                    if ($field['Type'] == 'phone') {
                        $results['phones'][] = $field['value'];
                    }
                }

                foreach ($listing as $group) {
                    foreach ($group['Fields'] as $field) {
                        if ($field['Type'] == 'phone' && $listing_data[$field['Key']]) {
                            $results['phones'][] = $reefless->parsePhone($listing_data[$field['Key']], $field, false);
                        }
                    }
                }

                if ($results['phones']) {
                    $results['main_phone'] = array_shift($results['phones']);

                    if (!$results['phones']) {
                        unset($results['phones']);
                    }
                }

                $out = [
                    'status' => 'OK',
                    'results' => $results
                ];
            } else {
                $out['status'] = 'ERROR';
            }
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'resendAgentInvite':
        $out['status'] = (new Agencies())->resendInvite((int) $_REQUEST['id']) ? 'OK' : 'ERROR';
        break;

    case 'acceptAgentInvite':
        $agencies = new Agencies();
        $out['status'] = $agencies->setInviteKey($_REQUEST['key'])->acceptInvite() ? 'OK' : 'ERROR';
        $agencies->removeInviteKey();
        break;

    case 'declineAgentInvite':
        $agencies = new Agencies();
        $out['status'] = $agencies->setInviteKey($_REQUEST['key'])->declineInvite() ? 'OK' : 'ERROR';
        $agencies->removeInviteKey();
        break;

    case 'deleteAgentInvite':
        if ((new Agencies())->deleteInvite((int) $_REQUEST['id'])) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($rlLang->getSystem('invite_removed_successfully'));
            $out['status'] = 'OK';
        } else {
            $out['status'] = 'ERROR';
        }
        break;

    case 'getAgents':
        $agencyID = (int) $_REQUEST['agencyID'];
        $page     = (int) $_REQUEST['page'];

        if (!$agencyID || !$page) {
            $out = ['status' => 'ERROR'];
            break;
        }

        $agents = $rlAccount->searchDealers(
            ['Agency_ID' => $agencyID],
            ['Agency_ID' => ['Key' => 'Agency_ID', 'Type' => 'radio']],
            $config['dealers_per_page'],
            $page
        );

        $pagination = [
            'calc'     => $rlAccount->calc,
            'total'    => count($agents),
            'current'  => $page,
            'per_page' => $config['dealers_per_page'],
            'pages'    => ceil($rlAccount->calc / $config['dealers_per_page']),
            'first_url' => "javascript: flGetAgents(1);",
            'tpl_url'  => "javascript: flGetAgents('[pg]');",
        ];
        $rlSmarty->assign('pagination', $pagination);
        $rlSmarty->assign('lang', $lang);
        $rlSmarty->assign('side_bar_exists', true);

        $agentsHtml = '';
        foreach ($agents as $agent) {
            $rlSmarty->assign('dealer', $agent);
            $agentsHtml .= $rlSmarty->fetch('blocks/dealer.tpl');
        }

        $out = [
            'status'         => 'OK',
            'agentsHtml'     => $agentsHtml,
            'paginationHTML' => $pagination['pages'] > 1
                ? $rlSmarty->fetch(FL_TPL_COMPONENT_DIR . 'pagination/pagination.tpl')
                : ''
        ];
        break;
}

// ajax request hook
$rlHook->load('ajaxRequest', $out, $request_mode, $request_item, $request_lang);

if (!empty($out)) {
    echo json_encode($out);
} else {
    echo null;
}
