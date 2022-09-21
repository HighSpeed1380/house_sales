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

use Flynax\Utils\Profile;
use Flynax\Utils\ListingMedia;
use Flynax\Utils\Valid;

require_once '..' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'config.inc.php';
require_once 'controllers' . RL_DS . 'ext_header.inc.php';

$item = $_REQUEST['item'];

switch ($item) {
    case 'getCategoryPathsByID':
        $categoryID = (int) $_REQUEST['id'];

        if ($categoryID) {
            if ($config['multilingual_paths']) {
                $languages = $rlLang->getLanguagesList();

                foreach ($languages as $languageKey => $languageData) {
                    $select[] = $languageKey === $config['lang'] ? "Path` AS `Path_{$languageKey}" : "Path_{$languageKey}";
                }
            } else {
                $select[] = 'Path` AS `Path_' . $config['lang'];
            }

            $paths = $rlDb->fetch($select, ['ID' => $categoryID], null, null, 'categories', 'row');

            if ($config['multilingual_paths']) {
                $defaultPath = $paths["Path_{$config['lang']}"];
                foreach ($paths as &$path) {
                    $path = $path ?: $defaultPath;
                }
            }

            $out = ['status' => 'OK', 'paths' => $paths];
        } else {
            $out = ['status' => 'ERROR'];
        }
        break;

    /* get category titles by listing type */
    case 'getCategoriesByType':
        $type_key = $rlValid->xSql($_REQUEST['type']);
        $category_id = (int) $_REQUEST['id'];

        $reefless->loadClass('Categories');
        $out = array_values($rlCategories->getCatTree($category_id, $type_key));
        break;

    case 'getCategoryParent':
        $category_id = (int) $_REQUEST['id'];

        if ($category_id) {
            $out = $rlDb->getOne('Parent_IDs', "`ID` = {$category_id}", 'categories');
        }
        break;

    case 'updatePluginStatus':
        $key = $_REQUEST['key'];
        $domain = $_REQUEST['domain'];
        $license = $_REQUEST['license'];

        $out['status'] = 'fail';

        if ($key && $domain && $license) {
            $url = 'https://www.flynax.com/_request/remote-plugin-status.php?key=' . $key . '&domain=' . $domain . '&license=' . $license;
            $out['status'] = $reefless->getPageContent($url) === '1' ? 'paid' : 'unpaid';
        }
        break;

    case 'rebuildListingImages':
        $limit   = intval($_REQUEST['limit'] ?: 10);
        $start   = intval($_REQUEST['start'] ?: 0);
        $last_id = 0; // Last listing ID
        $photos  = $rlDb->fetch(
            '*',
            array('Type' => 'picture'),
            'ORDER BY `Listing_ID` ASC, `Position` ASC',
            array($start, $limit),
            'listing_photos'
        );

        if ($start === 0) {
            $sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}listing_photos`
                WHERE `Type` = 'picture'
            ";
            $_SESSION['rebuildListingImages']['total'] = $rlDb->getRow($sql, 'Count');
        }

        // Rebuilding of the listings images
        if ($photos) {
            foreach ($photos as $photo) {
                ListingMedia::updatePicture($photo);

                if ($last_id != $photo['Listing_ID']) {
                    ListingMedia::updateMediaData($photo['Listing_ID']);
                }

                $last_id = $photo['Listing_ID'];
            }

            $action = ($start + $limit) >= $_SESSION['rebuildListingImages']['total']
            ? 'completed'
            : 'next';

            $out = array(
                'status'   => 'OK',
                'action'   => $action,
                'progress' => floor((($start + $limit) * 100) / $_SESSION['rebuildListingImages']['total'])
            );

            if ($action == 'completed') {
                unset($_SESSION['rebuildListingImages']);
            }
        } else {
            $out = array(
                'status'   => 'OK',
                'action'   => 'completed',
                'progress' => 100
            );
        }
        break;

    case 'rebuildAccountImages':
        $limit = intval($_REQUEST['limit'] ?: 10);
        $start = intval($_REQUEST['start'] ?: 0);
        $key   = Valid::escape($_REQUEST['key']);

        $reefless->loadClass('Account');
        $reefless->loadClass('AccountTypes');

        $select = '`ID`, `Type`, `Photo`, `Photo_original`';

        if ($config['thumbnails_x2']) {
            $select .= ', `Photo_x2`';
        }

        $photos = $rlDb->getAll("
            SELECT {$select} FROM `{db_prefix}accounts`
            WHERE (`Photo` <> '' OR `Photo_original` <> '')
            " . ($key ? "AND `Type` = '{$key}' " : '') . "
            ORDER BY `ID`
            LIMIT {$start}, {$limit}
        ");

        if ($start === 0) {
            $sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}accounts`
                WHERE (`Photo` <> '' OR `Photo_original` <> '')
                " . ($key ? "AND `Type` = '{$key}' " : '') . "
            ";
            $_SESSION['rebuildAccountImages']['total'] = $rlDb->getRow($sql, 'Count');
        }

        // Rebuilding of the accounts thumbnails
        if ($photos) {
            foreach ($photos as $photo) {
                $size = $rlAccountTypes->types[$photo['Type']];
                Profile::cropThumbnail(
                    array('width' => $size['Thumb_width'], 'height' => $size['Thumb_height']),
                    $photo
                );
            }

            $action = ($start + $limit) >= $_SESSION['rebuildAccountImages']['total']
            ? 'completed'
            : 'next';

            $out = array(
                'status'   => 'OK',
                'action'   => $action,
                'progress' => floor((($start + $limit) * 100) / $_SESSION['rebuildAccountImages']['total'])
            );

            if ($action == 'completed') {
                unset($_SESSION['rebuildAccountImages']);
            }
        } else {
            $out = array(
                'status'   => 'OK',
                'action'   => 'completed',
                'progress' => 100
            );
        }
        break;

    case 'phrase':
        $key = $rlValid->xSql($_REQUEST['key']);
        $lang = $rlValid->xSql($_REQUEST['lang']);

        $out = $rlDb->getOne('Value', "`Key` = '{$key}' AND `Code` = '{$lang}'", 'lang_keys');
        break;

    case 'accounts':
        $str = $rlValid->xSql($_REQUEST['str']);
        $fields = $_REQUEST['add_id'] || $_REQUEST['add_type']
        ? ($_REQUEST['add_id'] ? ', `ID`' : '') . ($_REQUEST['add_type'] ? ', `Type`' : '')
        : '';

        $type = $rlValid->xSql($_REQUEST['type']);
        $out = array();

        if (!empty($str)) {
            $sql = "SELECT `Username`{$fields} FROM `{db_prefix}accounts` ";
            $sql .= "WHERE `Username` REGEXP '^{$str}' AND `Status` = 'active' ";
            $sql .= $type && $type != '*' ? "AND `Type` = '{$type}'" : "";

            $out = $rlDb->getAll($sql);
        }
        break;

    case 'checkListingsByMembership':
        $reefless->loadClass('MembershipPlansAdmin', 'admin');
        $out = $rlMembershipPlansAdmin->checkActiveListings();
        break;

    case 'configUpdate':
        $reefless->loadClass('Actions');
        $config_key = $rlValid->xSql($_REQUEST['key']); // $_REQUEST['value'] will be validated in updateOne method
        $out['status'] = $rlConfig->setConfig($config_key, $_REQUEST['value']) ? 'OK' : 'ERROR';
        break;

    case 'checkAccountTypes':
        $reefless->loadClass('MembershipPlansAdmin', 'admin');
        $account_types = $rlMembershipPlansAdmin->getAccountTypes();
        $link = RL_URL_HOME . ADMIN . '/index.php?controller=account_types';
        $out = $account_types ? array('data' => $account_types, 'count' => count($account_types)) : array('data' => null, 'count' => 0, 'message' => str_replace('[link]', $link, $lang['not_available_account_types']));
        break;

    case 'changeListingStatus':
        $reefless->loadClass('Actions');
        $reefless->loadClass('Account');
        $reefless->loadClass('ListingsAdmin', 'admin');
        $result = $rlListingsAdmin->changeListingStatus($_REQUEST['id'], $_REQUEST['value'], $_REQUEST['membership_upgarde']);
        $out = $result ? array('status' => 'ok') : array('status' => 'failure');
        break;

    case 'checkMemebershipPlan':
        $reefless->loadClass('MembershipPlansAdmin', 'admin');
        $plan_info = $rlMembershipPlansAdmin->getAccountPlanInfo($_REQUEST['username']);
        $listing_type_not_match = false;
        if ($plan_info['Listing_number'] == 0 ||
            ($plan_info['Listing_number'] > 0 && ($plan_info['Listings_remains'] > 0 || !isset($plan_info['Listings_remains']))) ||
            ($plan_info['Advanced_mode'] && ($plan_info['Standard_remains'] > 0 || $plan_info['Standard_listings'] == 0)) ||
            ($plan_info['Advanced_mode'] && ($plan_info['Featured_remains'] > 0 || $plan_info['Featured_listings'] == 0))
        ) {
            $status = 'ok';
            if (isset($_REQUEST['edit']) && $plan_info['Advanced_mode']) {
                if ($plan_info[ucfirst($_REQUEST['listing_type']) . '_listings'] > 0 && $plan_info[ucfirst($_REQUEST['listing_type']) . '_remains'] <= 0) {
                    $listing_type_not_match = true;
                }
            }
        } else {
            $status = 'failure';
        }
        $out = array(
            'status'                 => $status,
            'plan'                   => $plan_info,
            'listing_type_not_match' => true, //$listing_type_not_match
        );
        break;

    case 'editListingDescription':
        $reefless->loadClass('ListingsAdmin', 'admin');

        if ($rlListingsAdmin->editDescription($_REQUEST['id'], $_REQUEST['listing_id'], $_REQUEST['description'])) {
            $out['status'] = 'OK';
            $out['message'] = $GLOBALS['lang']['notice_description_saved'];
        } else {
            $out['status'] = 'ERROR';
            $out['message'] = $GLOBALS['lang']['system_error'];
        }
        break;

    case 'accountTypeDeactivation':
        // get key of account type
        $key = $rlValid->xSql($_REQUEST['key']);

        if ($key) {
            $updateData = array('fields' => array('Status' => 'approval'), 'where' => array('Key' => $key));
            $type_info = array(
                'Key'    => $key,
                'Status' => $rlDb->getOne('Status', "`Key` = '{$key}'", 'account_types'),
            );

            $rlHook->load('apExtAccountTypesUpdate');

            $reefless->loadClass('Actions');
            $rlActions->updateOne($updateData, 'account_types');

            // deactivate accounts
            $sql = "UPDATE `{db_prefix}accounts` SET `Status` = 'approval' ";
            $sql .= "WHERE `Type` = '{$key}' AND `Status` != 'incomplete' AND `Status` != 'pending' ";
            $rlDb->query($sql);

            $reefless->loadClass('Listings');
            $rlListings->listingStatusControl(array('Account_type' => $key), 'approval');

            $out['status'] = 'OK';
            $out['message'] = $lang['notice_account_type_deactivated'];
        } else {
            $out['status'] = 'ERROR';
            $out['message'] = $lang['system_error'];
        }
        break;

    case 'removeSlide':
        $id = (int) $_REQUEST['id'];

        if ($id) {
            $picture = $rlDb->getOne('Picture', "`ID` = {$id}", 'slides');
            unlink(RL_FILES . 'slides' . RL_DS . $picture);

            $rlDb->delete(array('ID' => $id), 'slides');
            $rlDb->delete(array('Key' => 'slides+title+' . $id), 'lang_keys', null, null);
            $rlDb->delete(array('Key' => 'slides+description+' . $id), 'lang_keys', null, null);

            $out = array(
                'status'  => 'OK',
                'message' => $lang['item_deleted']
            );
        } else {
            $out = array(
                'status'  => 'ERROR',
                'message' => $lang['system_error']
            );
        }
        break;

    case 'cropListingPicture':
        $listing_id = (int) $_REQUEST['listing_id'];
        $media_id   = (int) $_REQUEST['media_id'];
        $data       = $_REQUEST['data'];

        if (!$listing_id || !$media_id) {
            return false;
        }

        $condition = array('ID' => $media_id, 'Listing_ID' => $listing_id);
        $picture = $rlDb->fetch('*', $condition, null, 1, 'listing_photos', 'row');

        if (!$picture) {
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
        $picture = $rlDb->fetch('*', $condition, null, 1, 'listing_photos', 'row');

        if (!$picture) {
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

        if (!$media_id) {
            return false;
        }

        ListingMedia::tmpRotate($media_id);

        break;

    case 'getCountMissingPhrases':
        $languageCode = Valid::escape($_REQUEST['language']);

        if (!$languageCode || !$config['lang']) {
            $out['status'] = 'ERROR';
            break;
        }

        $countMissingPhrases = (int) $rlDb->getRow(
            "SELECT COUNT(*) as `count` FROM `{db_prefix}lang_keys`
            WHERE `Code` = '{$config['lang']}' AND `Status` = 'active'
            AND `Key` NOT IN (
                SELECT `Key` FROM `{db_prefix}lang_keys`
                WHERE `Code` = '{$languageCode}' AND `Status` = 'active'
            )",
            'count'
        );

        $out = ['count' => $countMissingPhrases, 'status' => 'OK'];
        break;

    case 'importMissingPhrases':
        $languageCode = Valid::escape($_REQUEST['language']);

        if (!$languageCode || !$config['lang']) {
            $out['status'] = 'ERROR';
            break;
        }

        $_SESSION['lang_1'] = $config['lang'];
        $_SESSION['lang_2'] = $languageCode;

        $reefless->loadClass('AjaxLang', 'admin');
        $rlAjaxLang->ajaxCopyPhrases(1, 2, false);

        $out['status'] = 'OK';
        break;
    case 'isUserExist':
        $errorMessage = '';

        $lookIn = Valid::escape($_REQUEST['lookIn']) ?: 'accounts';
        $byField = Valid::escape($_REQUEST['byField']);
        $getField = Valid::escape($_REQUEST['getField']) ?: 'ID';
        $value = Valid::escape($_REQUEST['value']);

        $isExist = $rlDb->fetch(array($getField, 'Status'), array($byField => $value), null, null, $lookIn, 'row');

        if ($isExist) {
            switch ($byField) {
                case 'Email':
                case 'Mail':
                    $langKey = $lookIn == 'admins' ? 'admin' : 'account';
                    $errorMessage = str_replace('{email}', "<b>{$value}</b>", $lang["notice_{$langKey}_email_exist"]);

                    if ($isExist['Status'] == 'trash') {
                        $errorMessage .= " <b>({$lang['in_trash']})</b>";
                    }
                    break;
            }
        }

        $out = [
            'status' => !$isExist ? 'OK' : 'ERROR',
            'message' => $errorMessage,
        ];
        break;

    case 'getSubscribersByPlan':
        $plan_id = (int) $_REQUEST['plan_id'];
        $service = Valid::escape($_REQUEST['service']);

        if (!is_object($rlSmarty)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        $reefless->loadClass('Subscription');

        $out = $rlSubscription->ajaxGetSubscribersByPlan($plan_id, $service);
        break;

    case 'checkSubscription':
        $itemID = (int) $_REQUEST['itemID'];

        if (!is_object($rlSmarty)) {
            require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        if (!is_object($GLOBALS['rlGateway'])) {
            require_once RL_CLASSES . 'rlGateway.class.php';
        }

        $reefless->loadClass('Subscription');

        $rlSubscription->ajaxCheckSubscription($itemID);
        break;

    case 'refreshLocations':
        $reefless->loadClass('Controls', 'admin');
        $out = $rlControls->refreshLocations(
            (int) $_REQUEST['start'],
            Valid::escape($_REQUEST['mode'])
        );
        break;
}

/**
 * @param $out, $item @since 4.6.0
 **/
$rlHook->load('apAjaxRequest', $out, $item);

// close the connection with a database
$rlDb->connectionClose();

echo json_encode($out);
