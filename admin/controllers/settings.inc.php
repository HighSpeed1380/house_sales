<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SETTINGS.INC.PHP
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

use \Flynax\Utils\Valid;
use \Intervention\Image\ImageManagerStatic as Image;

/* update actions */

$reefless->loadClass('Plan');
$reefless->loadClass('MembershipPlansAdmin', 'admin');

$allLangs = $GLOBALS['languages'];
$rlSmarty->assign_by_ref('allLangs', $allLangs);

if (isset($_POST['a_config'])) {
    $dConfig = $_POST['post_config'];
    $aUrl    = array("controller" => $controller);

    if ($_POST['group_id']) {
        $aUrl['group'] = $_POST['group_id'];
    }

    /* clear compile directory */
    if (isset($dConfig['template']) && ($dConfig['template']['value'] != $config['template'])) {
        $compile = $reefless->scanDir(RL_TMP . 'compile' . RL_DS);
        foreach ($compile as $file) {
            if (in_array($file, array('index.html', '.htaccess'))) {
                continue;
            }

            unlink(RL_TMP . 'compile' . RL_DS . $file);
        }

        /* touch files */
        $reefless->flTouch(RL_ROOT . 'templates' . RL_DS . $dConfig['template']['value'] . RL_DS);
    } else if ($dConfig['pg_upload_thumbnail_width']['value'] != $config['pg_upload_thumbnail_width']
        || $dConfig['pg_upload_thumbnail_height']['value'] != $config['pg_upload_thumbnail_height']
        || $dConfig['pg_upload_large_width']['value'] != $config['pg_upload_large_width']
        || $dConfig['pg_upload_large_height']['value'] != $config['pg_upload_large_height']
        || $dConfig['watermark_using']['value'] != $config['watermark_using']
        || $dConfig['watermark_type']['value'] != $config['watermark_type']
        || $dConfig['watermark_image_url']['value'] != $config['watermark_image_url']
        || $dConfig['watermark_text']['value'] != $config['watermark_text']
        || $dConfig['img_quality']['value'] != $config['img_quality']
        || $dConfig['thumbnails_x2']['value'] != $config['thumbnails_x2']
        || $dConfig['img_crop_module']['value'] != $config['img_crop_module']
        || $dConfig['img_crop_thumbnail']['value'] != $config['img_crop_thumbnail']
        || $dConfig['watermark_position']['value'] != $config['watermark_position']
        || $dConfig['watermark_image_width']['value'] != $config['watermark_image_width']
        || $dConfig['watermark_text_font']['value'] != $config['watermark_text_font']
        || $dConfig['watermark_text_size']['value'] != $config['watermark_text_size']
        || $dConfig['watermark_text_color']['value'] != $config['watermark_text_color']
        || $dConfig['watermark_angle']['value'] != $config['watermark_angle']
        || $dConfig['watermark_opacity']['value'] != $config['watermark_opacity']
        || $dConfig['output_image_format']['value'] != $config['output_image_format']
    ) {
        $aUrl['refreshListingImages'] = 1;

        if ($dConfig['img_quality']['value'] != $config['img_quality']
            || $dConfig['thumbnails_x2']['value'] != $config['thumbnails_x2']
        ) {
            $aUrl['refreshAccountImages'] = 1;
        }

        /* add additional columns for 2x thumbnails */
        if ($dConfig['thumbnails_x2']['value'] == '1' && $config['thumbnails_x2'] == '0') {
            $rlDb->addColumnToTable('Main_photo_x2', "VARCHAR(80) NOT NULL AFTER `Main_photo`", 'listings');
            $rlDb->addColumnToTable('Thumbnail_x2', "VARCHAR(80) NOT NULL AFTER `Thumbnail`", 'listing_photos');
            $rlDb->addColumnToTable('Photo_x2', "VARCHAR(80) NOT NULL AFTER `Photo`", 'accounts');
        }

        /* drop additional columns for 2x thumbnails */
        if ($dConfig['thumbnails_x2']['value'] == '0' && $config['thumbnails_x2'] == '1') {
            $rlDb->dropColumnFromTable('Main_photo_x2', 'listings');
            $rlDb->dropColumnFromTable('Thumbnail_x2', 'listing_photos');
            $rlDb->dropColumnFromTable('Photo_x2', 'accounts');
        }
    } elseif ($dConfig['img_account_crop_thumbnail']['value'] != $config['img_account_crop_thumbnail']) {
        $aUrl['refreshAccountImages'] = 1;
    } elseif ($dConfig['mf_geo_subdomains']['value'] != $config['mf_geo_subdomains'] && $dConfig['mf_geo_subdomains']['value'] == 1) {
        $ltype_on_subdomain = $rlDb->getOne("ID", "`Links_type` = 'subdomain' AND `Status` = 'active'", "listing_types");

        if ($ltype_on_subdomain) {
            $dConfig['mf_geo_subdomains']['value'] = '0';

            $_SESSION['sessAdmin']['mfSubdomainsDenied'] = true;
        }
    } elseif ($dConfig['cache']['value'] == 1
        && ($dConfig['cache_method']['value'] == 'memcached' && (!extension_loaded('memcached') || !$rlCache->memcacheConnect())
            || $dConfig['cache_method']['value'] == 'apc' && !extension_loaded('apc'))
    ) {
        $dConfig['cache_method']['value'] = $config['cache_method'];
        $_SESSION['sessAdmin']['cacheMethodDenied'] = true;
    } elseif (
        $dConfig['mail_method']['value'] == 'smtp'
        && (
            $dConfig['mail_method']['value'] != $config['mail_method']
            || $dConfig['smtp_server']['value'] != $config['smtp_server']
            || $dConfig['smtp_username']['value'] != $config['smtp_username']
            || $dConfig['smtp_password']['value'] != $config['smtp_password']
            || $dConfig['smtp_method']['value'] != $config['smtp_method'])
    ) {
        $_SESSION['sessAdmin']['debugSmtp'] = true;
    }
    /* handler for My Ads page */
    else if ($dConfig['one_my_listings_page']['value'] != $config['one_my_listings_page']) {
        $activate = (bool) $dConfig['one_my_listings_page']['value'];
        $my_ads_status = $search_in_my_ads = $activate ? 'active' : 'trash';
        $other_pages_status = $other_search_boxes_status = $activate ? 'trash' : 'active';

        // update status of My Ads page
        $rlDb->updateOne(
            array(
                'fields' => array(
                    'Status' => $my_ads_status,
                ),
                'where'  => array(
                    'Key' => 'my_all_ads',
                ),
            ),
            'pages'
        );

        // update status of other "my listings" pages
        $sql = "UPDATE `{db_prefix}pages` SET `Status` = '{$other_pages_status}' ";
        $sql .= "WHERE `Controller` = 'my_listings' AND `Key` <> 'my_all_ads'";
        $rlDb->query($sql);

        // update status of Search in My Ads block
        $rlDb->updateOne(
            array(
                'fields' => array(
                    'Status' => $search_in_my_ads,
                ),
                'where'  => array(
                    'Key' => 'search_in_my_ads',
                ),
            ),
            'blocks'
        );

        // update status of other "my search" boxes
        $sql = "UPDATE `{db_prefix}blocks` SET `Status` = '{$other_search_boxes_status}' ";
        $sql .= "WHERE `Key` LIKE 'ltma_%'";
        $rlDb->query($sql);

        // disable pages and search blocks of inactive listing types
        $inactiveLtypes = array_filter($rlListingTypes->types,function($type){
            return $type['Status'] == 'approval';
        });

        foreach ($inactiveLtypes as $type) {
            $rlDb->updateOne(
                array(
                    'fields' => array(
                        'Status' => 'approval',
                    ),
                    'where'  => array(
                        'Key' => 'my_' . $type['Key'],
                    ),
                ),
                'pages'
            );

            $rlDb->updateOne(
                array(
                    'fields' => array(
                        'Status' => 'approval',
                    ),
                    'where'  => array(
                        'Key' => 'ltma_' . $type['Key'],
                    ),
                ),
                'blocks'
            );
        }
    }

    /* update cache */
    if (isset($dConfig['cache']) && $dConfig['cache']['value'] && !$config['cache']) {
        $config['cache'] = 1;

        $rlCache->update();
        $config['cache'] = 0;
    } elseif ($dConfig['cache']['value'] && $dConfig['cache_method']['value'] != $config['cache_method']) {
        $tmp = $config['cache_method'];
        $config['cache_method'] = $dConfig['cache_method']['value'];

        $rlCache->update();
        $config['cache_method'] = $tmp;
    } elseif ($dConfig['cache']['value'] && $dConfig['cache_divided']['value'] != $config['cache_divided']) {
        $tmp = $config['cache_method'];
        $config['cache_divided'] = $dConfig['cache_divided']['value'];

        $rlCache->update();
        $config['cache_divided'] = $tmp;
    }

    // show/hide Flynax Blog Feed
    if ($dConfig['flynax_news_number']['value'] != $config['flynax_news_number']) {
        $rlActions->updateOne(
            array(
                'fields' => array(
                    'Status' => intval($dConfig['flynax_news_number']['value']) == 0 ? 'approval' : 'active',
                ),
                'where'  => array(
                    'Key' => 'flynax_news',
                ),
            ),
            'admin_blocks'
        );

        // recount order of all admin blocks
        $blocks = $rlDb->fetch(
            array('Column', 'ID'),
            array('Status' => 'active'),
            'ORDER BY `Column` ASC',
            null,
            'admin_blocks'
        );

        foreach ($blocks as $block) {
            $block_index++;

            $update[] = array(
                'fields' => array(
                    'Column' => 'column' . $block_index,
                ),
                'where'  => array(
                    'ID' => $block['ID'],
                ),
            );
        }

        if ($blocks) {
            $rlActions->update($update, 'admin_blocks');
        }
    }

    // membership plans
    if ($dConfig['membership_module']['value'] == 0 && $rlMembershipPlansAdmin->getActiveListings() > 0 && $dConfig['base_listing_plan']['value']) {
        $rlMembershipPlansAdmin->assignListingToListingPlan($dConfig['base_listing_plan']['value']);
    }

    // Remove the ability to have sub-accounts from account types when enabled Membership plans
    if ($dConfig['membership_module']['value'] === '1' && $config['membership_module'] === '0') {
        $reefless->loadClass('AccountTypes');

        foreach ($rlAccountTypes->types as $type) {
            if ($type['Agency'] === '1') {
                $rlDb->updateOne([
                    'fields' => ['Agency' => '0'],
                    'where'  => ['Key' => $type['Key']],
                ], 'account_types');
            }
        }
    }

    if ($dConfig['multilingual_paths']['value'] === '1' && $config['multilingual_paths'] === '0') {
        foreach ($allLangs as $langKey => $langData) {
            /**
             * Skip default language
             * Or skip future default language if admin change it in same moment
             */
            if (($langKey === $config['lang'] && $config['lang'] === $dConfig['lang']['value'])
                || ($langKey === $dConfig['lang']['value'] && $config['lang'] !== $dConfig['lang']['value'])
            ) {
                continue;
            }

            $dbColumns["Path_{$langKey}"] = "VARCHAR(255) NOT NULL DEFAULT '' AFTER `Path`";
        }

        $rlDb->addColumnsToTable($dbColumns, 'categories');
        $rlDb->addColumnsToTable($dbColumns, 'pages');
    }

    if ($dConfig['multilingual_paths']['value'] === '1' && $dConfig['lang']['value'] !== $config['lang']) {
        $rlAdmin->changeDefaultLanguageHandler($config['lang'], $dConfig['lang']['value']);
    }

    $update = array();
    $fields_to_trim = array('google_map_key', 'google_server_map_key');

    foreach ($dConfig as $key => $value) {
        if ($dConfig[$key]['value'] == $config[$key]) {
            continue;
        }

        if ($value['d_type'] == 'int') {
            $value['value'] = (int) $value['value'];
        } else if (is_numeric(strpos($value['value'], '[[http'))) {
            $value['value'] = preg_replace("#\[\[http(s)?_pref\]\]#", "http$1", $value['value']);
        } else if (in_array($key, $fields_to_trim)) {
            $value['value'] = trim($value['value']);
        }

        $row['where']['Key'] = $key;
        $row['fields']['Default'] = $value['value'];
        array_push($update, $row);
    }

    $reefless->loadClass('Actions');

    // Default map location
    if (isset($dConfig['search_map_location_name'])) {
        $post_data = $rlValid->xSql($_POST['search_map_default']);
        $set_value = $dConfig['search_map_location_name']['value'] ? $post_data['lat'].','.$post_data['lng'] : '';
        $row['where']['Key'] = 'search_map_location';
        $row['fields']['Default'] = $set_value;

        if ($set_value != $GLOBALS['config']['search_map_location']) {
            array_push($update, $row);
        }
    }

    // Check map API keys
    if (($dConfig['static_map_provider']['value'] == 'google' && !$dConfig['google_map_key']['value'])
        || ($dConfig['geocoding_provider']['value'] == 'google' && !$dConfig['google_server_map_key']['value'])
    ) {
        $reefless->loadClass('Notice');
        $rlNotice->saveNotice(
            str_replace('{field}', $lang['config+name+google_map_key'], $lang['notice_field_empty']),
            'errors'
        );

        $update = false;
    }

    if ($dConfig['watermark_using']['value'] === '1') {
        $watermarkError = '';

        if ($dConfig['watermark_type']['value'] === 'image') {
            if (empty($dConfig['watermark_image_url']['value'])) {
                $watermarkError = str_replace(
                    '{field}',
                    "<b>{$lang['config+name+watermark_image_url']}</b>",
                    $lang['notice_field_empty']
                );
            } elseif (!Valid::isURL($dConfig['watermark_image_url']['value'])
                || !file_get_contents($dConfig['watermark_image_url']['value'])
            ) {
                $watermarkError = $GLOBALS['rlLang']->getSystem('watermark_image_is_not_available');
            } elseif ($dConfig['watermark_image_url']['value'] != $config['watermark_image_url']
                && Image::make($dConfig['watermark_image_url']['value'])->mime() !== 'image/png'
            ) {
                $watermarkError = $GLOBALS['rlLang']->getSystem('watermark_image_is_not_png');
            } elseif ((int) $dConfig['watermark_image_width']['value'] <= 0) {
                $watermarkError = $GLOBALS['rlLang']->getSystem('watermark_wrong_width');
            } elseif ((int) $dConfig['watermark_opacity']['value'] < 0
                || (int) $dConfig['watermark_opacity']['value'] > 100
            ) {
                $watermarkError = $GLOBALS['rlLang']->getSystem('watermark_wrong_opacity');
            }

            // Set maximum width of watermark to 250px
            if (!$watermarkError && $dConfig['watermark_image_url']['value'] != $config['watermark_image_url']) {
                $watermark = Image::make($dConfig['watermark_image_url']['value']);
                array_push($update, [
                    'where'  => ['Key' => 'watermark_image_width'],
                    'fields' => ['Default' => $watermark->width() > 250 ? 250 : $watermark->width()],
                ]);
            }
        } elseif ($dConfig['watermark_type']['value'] === 'text') {
            if ((int) $dConfig['watermark_text_size']['value'] <= 0) {
                $watermarkError = $GLOBALS['rlLang']->getSystem('watermark_wrong_text_size');
            }
        }

        if ($watermarkError) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($watermarkError, 'errors');
            $update = false;
            unset($aUrl['refreshListingImages'], $aUrl['refreshAccountImages']);
        }
    }

    $rlHook->load('apPhpConfigBeforeUpdate');

    if ($update && $rlActions->update($update, 'config')) {
        $rlHook->load('apPhpConfigAfterUpdate');

        if (!$_SESSION['sessAdmin']['mfSubdomainsDenied']
            && !$_SESSION['sessAdmin']['cacheMethodDenied']
            && !isset($_SESSION['admin_notice'])
        ) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['config_saved']);
        }
    }

    if ($update || $aUrl['group']) {
        $reefless->redirect($aUrl);
    }
}

/* get all config groups */
$g_sql = "SELECT `T1`.*, `T2`.`Status` AS `Plugin_status` FROM `{db_prefix}config_groups` AS `T1` ";
$g_sql .= "LEFT JOIN `{db_prefix}plugins` AS `T2` ON `T1`.`Plugin` = `T2`.`Key` GROUP BY `ID` ";
$configGroups = $rlDb->getAll($g_sql);

$configGroups = $rlLang->replaceLangKeys($configGroups, 'config_groups', 'name', RL_LANG_CODE, 'admin');
$rlSmarty->assign_by_ref('configGroups', $configGroups);

foreach ($configGroups as $key => $value) {
    $groupIDs[] = $value['ID'];
}

/* get all configs */
$configsLsit = $rlDb->fetch('*', null, "WHERE `Group_ID` = '" . implode("' OR `Group_ID` = '", $groupIDs) . ")' ORDER BY `Position`", null, 'config');
$configsLsit = $rlLang->replaceLangKeys($configsLsit, 'config', array('name', 'des'), RL_LANG_CODE, 'admin');
$rlAdmin->mixSpecialConfigs($configsLsit);

foreach ($configsLsit as $key => $value) {
    $configs[$value['Group_ID']][] = $value;
}
$rlSmarty->assign_by_ref('listing_plans', $rlPlan->getPlans());

$rlSmarty->assign_by_ref('configs', $configs);
unset($configGroups, $configsLsit);

$rlHook->load('apPhpConfigBottom');

if ($_SESSION['sessAdmin']['mfSubdomainsDenied']) {
    unset($_SESSION['sessAdmin']['mfSubdomainsDenied']);
    $rlSmarty->assign('mfSubdomainsDenied', true);
} elseif ($_SESSION['sessAdmin']['cacheMethodDenied']) {
    unset($_SESSION['sessAdmin']['cacheMethodDenied']);
    $rlSmarty->assign('cacheMethodDenied', true);
} elseif ($_SESSION['sessAdmin']['debugSmtp']) {
    $reefless->loadClass('Mail');
    $smtp_debug = $rlMail->testSMTP();

    preg_match("#250 ([0-9\. ]+)?OK#i", $smtp_debug, $match);

    if ($match) {
        $_SESSION['admin_notice'] = $lang['notice_smtp_ok'];
    } else {
        $_SESSION['admin_notice'] = $lang['notice_smtp_failed'];
        $_SESSION['admin_notice_type'] = 'errors';
        $rlSmarty->assign('smtpDebug', $smtp_debug);
    }

    unset($_SESSION['sessAdmin']['debugSmtp']);
}

$rlXajax->registerFunction(array('checkActiveListings', $rlMembershipPlansAdmin, 'ajaxCheckActiveListings'));
