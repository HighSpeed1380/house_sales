<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SETTINGS.TPL.PHP
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

/* template settings */
$tpl_settings = array(
    'type' => 'responsive_42', // DO NOT CHANGE THIS SETTING
    'version' => 1.1,
    'name' => 'realty_rainbow_nova_wide',
    'inventory_menu' => false,
    'category_menu' => false,
    'category_menu_listing_type' => true,
    'right_block' => false,
    'long_top_block' => false,
    'featured_price_tag' => true,
    'ffb_list' => false, //field bound boxes plugins list
    'fbb_custom_tpl' => true,
    'header_banner' => true,
    'header_banner_size_hint' => '728x90',
    'home_page_gallery' => false,
    'home_page_slides' => true,
    'home_page_slides_size' => '1920x1080',
    'autocomplete_tags' => true,
    'category_banner' => false,
    'listing_type_color' => true,
    'shopping_cart_use_sidebar' => true,
    'listing_details_anchor_tabs' => true,
    'search_on_map_page' => true,
    'home_page_map_search' => false,
    'browse_add_listing_icon' => false,
    'listing_grid_except_fields' => array('title', 'bedrooms', 'bathrooms', 'square_feet', 'time_frame', 'phone', 'pay_period'),
    'category_dropdown_search' => true,
    'sidebar_sticky_pages' => array('listing_details'),
    'sidebar_restricted_pages' => array('search_on_map'),
    'svg_icon_fill' => true,
    'dark_mode' => true,
    'default_listing_grid_mode' => 'list',
    'listing_grid_mode_only' => false,
    'qtip' => array(
        'background' => '61CE70',
        'b_color'    => '61CE70',
    ),
);

if ( is_object($rlSmarty) ) {
    $rlSmarty->assign_by_ref('tpl_settings', $tpl_settings);
}

// Insert configs and hooks
if (!isset($config['nova_support'])) {
    // set phrases
    $reefless->loadClass('Lang');
    $languages = $rlLang->getLanguagesList();
    $tpl_phrases = array(
        array('admin', 'nova_category_menu', 'Category menu'),
        array('admin', 'nova_category_icon', 'Category Icon'),
        array('admin', 'nova_load_more', 'Load More'),
        array('frontEnd', 'nova_mobile_apps', 'Mobile Apps'),
        array('frontEnd', 'footer_menu_1', 'About Classifieds'),
        array('frontEnd', 'footer_menu_2', 'Help & Contact'),
        array('frontEnd', 'footer_menu_3', 'More Helpful Links'),
        array('frontEnd', 'nova_load_more_listings', 'Load More Listings'),
        array('frontEnd', 'contact_email', 'sales@flynax.com'),
        array('frontEnd', 'phone_number', '+1 (994) 546-1212'),
        array('admin', 'config+name+ios_app_url', 'iOS app url'),
        array('admin', 'config+name+android_app_url', 'Android app url'),
        array('frontEnd', 'nova_newsletter_text', 'Subscribe for our newsletters and stay updated about the latest news and special offers.'),
    );

    // insert template phrases
    foreach ($languages as $language) {
        foreach ($tpl_phrases as $tpl_phrase) {
            if (!$rlDb->getOne('ID', "`Code` = '{$language['Code']}' AND `Key` = '{$tpl_phrase[1]}'", 'lang_keys')) {
                $sql = "INSERT IGNORE INTO `". RL_DBPREFIX ."lang_keys` (`Code`, `Module`, `Key`, `Value`, `Plugin`) VALUES ";
                $sql .= "('{$language['Code']}', '{$tpl_phrase[0]}', '{$tpl_phrase[1]}', '". $rlValid->xSql($tpl_phrase[2])."', 'nova_template');";
                $rlDb->query($sql);
            }
        }
    }

    // Insert configs
    $insert_setting = array(
        array(
            'Group_ID' => 0,
            'Key' => 'nova_support',
            'Default' => 1,
            'Type' => 'text',
            'Plugin' => 'nova_template'
        ),
        array(
            'Group_ID' => 1,
            'Position' => 36,
            'Key' => 'ios_app_url',
            'Default' => 'https://itunes.apple.com/us/app/iflynax/id424570449?mt=8',
            'Type' => 'text',
            'Plugin' => 'nova_template'
        ),
        array(
            'Group_ID' => 1,
            'Position' => 36,
            'Key' => 'android_app_url',
            'Default' => 'https://play.google.com/store/apps/details?id=com.flynax.flydroid&hl=en_US',
            'Type' => 'text',
            'Plugin' => 'nova_template'
        )
    );
    $rlDb->insert($insert_setting, 'config');

    // Create fields
    $rlDb->addColumnToTable('Menu', "ENUM('1','0') NOT NULL DEFAULT '0' AFTER `Status`", 'listing_types');
    $rlDb->addColumnToTable('Menu_icon', "VARCHAR(100) NOT NULL AFTER `Menu`", 'listing_types');

    // insert hooks
    $sql = <<< MYSQL
INSERT INTO `{db_prefix}hooks` (`Name`, `Plugin`, `Class`, `Code`, `Status`) VALUES
('apAjaxRequest', 'nova_template', '', 'if (\$GLOBALS[''item''] != ''novaGetIcons'') {\r\n    return;\r\n}\r\n\r\n\$limit = 55;\r\n\$start = \$_REQUEST[''start''] ? \$_REQUEST[''start''] * \$limit : 0;\r\n\$dir = RL_ROOT . ''templates/'' . \$GLOBALS[''config''][''template''] . ''/img/icons/'';\r\n\$icons = \$GLOBALS[''reefless'']->scanDir(\$dir);\r\n\r\nif (\$q = \$_REQUEST[''q'']) {\r\n    foreach (\$icons as \$index => \$name) {\r\n        if (!is_numeric(strpos(\$name, \$q))) {\r\n            unset(\$icons[\$index]);\r\n        }\r\n    }\r\n}\r\n\r\n\$total = count(\$icons);\r\n\r\n\$GLOBALS[''out''] = array(\r\n    ''status''  => ''OK'',\r\n    ''results'' => array_slice(\$icons, \$start, \$limit),\r\n    ''total''   => \$total,\r\n    ''next''    => \$total > \$start + \$limit \r\n);', 'active'),
('ajaxRequest', 'nova_template', '', 'if (\$param2 != ''novaLoadMoreListings'') {\r\n    return;\r\n}\r\n\r\nglobal \$rlSmarty, \$config, \$reefless, \$rlDb, \$rlListings, \$lang, \$request_lang;\r\n\r\n\$ts_path = RL_ROOT . ''templates'' . RL_DS . \$config[''template''] . RL_DS . ''settings.tpl.php'';\r\nif (is_readable(\$ts_path)) {\r\n   require_once(\$ts_path);\r\n}\r\n\r\n\$type  = \$_REQUEST[''type''];\r\n\$key   = \$_REQUEST[''key''];\r\n\$total = \$_REQUEST[''total''];\r\n\$ids   = explode('','', \$_REQUEST[''ids'']);\r\n\r\n\$results   = array();\r\n\$page_info = array(\r\n    ''Controller'' => ''home'',\r\n    ''Key'' => ''home'',\r\n);\r\n\r\n\$reefless->loadClass(''Listings'');\r\n\r\n\$rlSmarty->assign(''side_bar_exists'', \$_REQUEST[''side_bar_exists'']);\r\n\$rlSmarty->assign(''block'', array(''Side'' => \$_REQUEST[''block_side'']));\r\n\r\n\$rlListings->selectedIDs = \$ids;\r\n\r\n\$lang = \$GLOBALS[''rlLang'']->getLangBySide(''frontEnd'', \$request_lang);\r\n\r\nif (\$type == ''featured'') {\r\n    \$limit      = \$config[''featured_per_page''];\r\n    \$next_limit = \$limit < 10 ? 10 : \$limit;\r\n    \$tpl        = ''blocks'' . RL_DS . ''featured.tpl'';\r\n    \$listings   = \$rlListings->getFeatured(\$key, \$next_limit);\r\n    \$count      = count(\$listings);\r\n    \$next       = \$total + \$count < \$rlListings->calc;\r\n\r\n    \$rlSmarty->assign_by_ref(''listings'', \$listings);\r\n} else {\r\n    \$reefless->loadClass(''ListingsBox'', null, ''listings_box'');\r\n\r\n    \$box_id     = str_replace(''listing_box_'', '''', \$key);\r\n    \$box_info   = \$rlDb->fetch(\r\n        ''*'',\r\n        array(''ID'' => \$box_id),\r\n        null, 1, ''listing_box'', ''row''\r\n    );\r\n    \$limit      = \$box_info[''Count''];\r\n    \$next_limit = \$limit < 10 ? 10 : \$limit;\r\n    \$tpl        = RL_PLUGINS . ''listings_box'' . RL_DS . ''listings_box.block.tpl'';\r\n    \$listings   = \$GLOBALS[''rlListingsBox'']->getListings(\r\n        \$box_info[''Type''],\r\n        \$box_info[''Box_type''],\r\n        \$next_limit,\r\n        1,\r\n        \$box_info[''By_category'']\r\n    );\r\n    \$count      = count(\$listings);\r\n    \$next       = true;\r\n\r\n    \$box_option = array(\r\n        ''display_mode'' => \$box_info[''Display_mode'']\r\n    );\r\n\r\n    \$rlSmarty->assign(''box_option'', \$box_option); \r\n    \$rlSmarty->assign_by_ref(''listings_box'', \$listings);\r\n}\r\n\r\nif (\$listings) {\r\n    \$rlSmarty->preAjaxSupport();\r\n\r\n    \$results = array(\r\n        ''next''  => \$next,\r\n        ''count'' => \$count,\r\n        ''ids''   => \$rlListings->selectedIDs,\r\n        ''html''  => \$rlSmarty->fetch(\$tpl, null, null, false)\r\n    );\r\n\r\n    \$rlSmarty->postAjaxSupport(\$results, \$page_info, \$tpl);\r\n}\r\n\r\n\$param1 = array(\r\n    ''status'' => ''OK'',\r\n    ''results'' => \$results\r\n);', 'active'),
('listingsModifyPreSelectFeatured', 'nova_template', '', 'if (\$_REQUEST[''mode''] == ''novaLoadMoreListings'') {\r\n    \$param1 = true;\r\n}', 'active'),
('apTplListingTypesForm', 'nova_template', '', 'if (!\$GLOBALS[''tpl_settings''][''category_menu_listing_type'']) {\r\n    return;\r\n}\r\n\r\n\$GLOBALS[''rlSmarty'']->display(RL_ROOT . ''templates'' . RL_DS . \$GLOBALS[''config''][''template''] . RL_DS . ''icon-manager.tpl'');', 'active'),
('apPhpListingTypesPost', 'nova_template', '', 'if (!\$GLOBALS[''tpl_settings''][''category_menu_listing_type'']) {\r\n    return;\r\n}\r\n\r\n\$_POST[''category_menu''] = \$GLOBALS[''type_info''][''Menu''];\r\n\$_POST[''category_menu_icon''] = \$GLOBALS[''type_info''][''Menu_icon''];', 'active'),
('apPhpListingTypesBeforeEdit', 'nova_template', '', 'if (!\$GLOBALS[''tpl_settings''][''category_menu_listing_type'']) {\r\n    return;\r\n}\r\n\r\nglobal \$update_date;\r\n\r\n\$update_date[''fields''][''Menu''] = \$_POST[''category_menu''];\r\n\$update_date[''fields''][''Menu_icon''] = \$_POST[''category_menu_icon''];', 'active'),
('apPhpListingTypesBeforeAdd', 'nova_template', '', 'if (!\$GLOBALS[''tpl_settings''][''category_menu_listing_type'']) {\r\n    return;\r\n}\r\n\r\nglobal \$data;\r\n\r\n\$data[''Menu''] = \$_POST[''category_menu''];\r\n\$data[''Menu_icon''] = \$_POST[''category_menu_icon''];', 'active');
MYSQL;
    $rlDb->query($sql);

    // Refresh the page to apply new hooks
    if (defined('REALM') && REALM == 'admin') {
        $reefless->referer();
        exit;
    }
}

// Insert Thumbnails Preview support hook and set config flag
if (!$config['nova_thumbnails_preview']) {
    // Insert configs
    $insert_setting = array(
        'Group_ID' => 0,
        'Key' => 'nova_thumbnails_preview',
        'Default' => 1,
        'Type' => 'text',
        'Plugin' => 'nova_template'
    );
    $rlDb->insertOne($insert_setting, 'config');

    $config['nova_thumbnails_preview'] = 1;

    $sql = <<< MYSQL
    INSERT INTO `{db_prefix}hooks` (`Name`, `Plugin`, `Class`, `Code`, `Status`) VALUES
    ('ajaxRequest', 'nova_template', '', 'if (\$param2 == ''getListingPhotos'') {\r\n    \$listing_id = (int) \$_REQUEST[''id''];\r\n\r\n    if (\$listing_id) {\r\n        \$fields = [''Thumbnail''];\r\n\r\n        if (\$GLOBALS[''config''][''thumbnails_x2'']) {\r\n            \$fields[] = ''Thumbnail_x2'';\r\n        }\r\n\r\n        \$photos = \$GLOBALS[''rlDb'']->fetch(\r\n            \$fields,\r\n            [''Type'' => ''picture'', ''Status'' => ''active'', ''Listing_ID'' => \$listing_id],\r\n            \"ORDER BY `Position`\",\r\n            5, ''listing_photos''\r\n        );\r\n\r\n        if (\$photos) {\r\n            \$param1 = array(\r\n                ''status'' => ''OK'',\r\n                ''data'' => \$photos\r\n            );\r\n        } else {\r\n            \$param1 = array(\r\n                ''status'' => ''ERROR''\r\n            );\r\n        }\r\n    }\r\n} elseif (\$param2 == ''novaGetCategories'') {\r\n    \$listing_type = \$GLOBALS[''rlListingTypes'']->types[\$_REQUEST[''type'']];\r\n    \$categories = \\\Flynax\\\Utils\\\Category::getCategories(\$_REQUEST[''type''], \$_REQUEST[''parent_id''], \$listing_type[''Ablock_show_subcats''] ? 2 : 0);\r\n\r\n    /**\r\n     * @todo Remove this code once the `geo_filter_data[''location_url_pages'']` is available in `hookPhpUrlBottom` multiField plugin hook\r\n     */\r\n    if (\$GLOBALS[''plugins''][''multiField'']) {\r\n        \$GLOBALS[''reefless'']->loadClass(''GeoFilter'', null, ''multiField'');\r\n\r\n        if (\$GLOBALS[''rlGeoFilter'']->geo_format && !\$GLOBALS[''rlGeoFilter'']->geo_filter_data[''location_url_pages'']) {\r\n            \$GLOBALS[''rlGeoFilter'']->init();\r\n        }\r\n    }\r\n\r\n    foreach (\$categories as \$key => &\$category) {\r\n        if (\$listing_type[''Cat_hide_empty''] && \$category[''Count''] <= 0) {\r\n            unset(\$categories[\$key]);\r\n            continue;\r\n        }\r\n\r\n        if (\$listing_type[''Cat_listing_counter'']) {\r\n            \$category[''show_count''] = 1;\r\n        }\r\n\r\n        \$category[''link''] = \$GLOBALS[''reefless'']->url(''category'', \$category);\r\n\r\n        if (!\$category[''sub_categories'']) {\r\n            continue;\r\n        }\r\n\r\n        if (\$listing_type[''Ablock_show_subcats''] && \$listing_type[''Ablock_subcat_number''] > 0) {\r\n            array_splice(\$category[''sub_categories''], \$listing_type[''Ablock_subcat_number'']);\r\n        }\r\n\r\n        \$reset_index = false;\r\n\r\n        foreach (\$category[''sub_categories''] as \$sub_key => &\$sub_category) {\r\n            if (\$listing_type[''Cat_hide_empty''] && \$sub_category[''Count''] <= 0) {\r\n                unset(\$categories[\$key][''sub_categories''][\$sub_key], \$sub_category);\r\n                \$reset_index = true;\r\n                continue;\r\n            }\r\n\r\n            \$sub_category[''Type''] = \$category[''Type''];\r\n            \$sub_category[''name''] = \$GLOBALS[''rlLang'']->getPhrase(\$sub_category[''pName'']);\r\n            \$sub_category[''link''] = \$GLOBALS[''reefless'']->url(''category'', \$sub_category);\r\n        }\r\n\r\n        if (\$reset_index) {\r\n            \$category[''sub_categories''] = array_values(\$category[''sub_categories'']);\r\n        }\r\n    }\r\n\r\n    \$param1 = array(\r\n        ''status''  => ''OK'',\r\n        ''results'' => \$categories\r\n    );\r\n}', 'active');
MYSQL;
    $rlDb->query($sql);

    // Refresh the page to apply new hooks
    if (defined('REALM') && REALM == 'admin') {
        $reefless->referer();
        exit;
    }
}

// Insert Dark Mode support hooks and set config flag
if (!$config['nova_dark_mode_support']) {
    // Insert configs
    $insert_setting = array(
        'Group_ID' => 0,
        'Key' => 'nova_dark_mode_support',
        'Default' => 1,
        'Type' => 'text',
        'Plugin' => 'nova_template'
    );
    $rlDb->insertOne($insert_setting, 'config');

    $config['nova_dark_mode_support'] = 1;

    $sql = <<< MYSQL
INSERT INTO `{db_prefix}hooks` (`Name`, `Class`, `Plugin`, `Code`, `Status`) VALUES
('staticDataRegister', '', 'nova_template', 'if (\$GLOBALS[''tpl_settings''][''dark_mode'']) {\r\n    \$GLOBALS[''rlStatic'']->removeCSS(RL_TPL_BASE . ''css/style.css'');\r\n    \$GLOBALS[''rlStatic'']->addHeaderCSS(RL_TPL_BASE . ''css/light.css'');\r\n    \$GLOBALS[''rlStatic'']->addHeaderCSS(RL_TPL_BASE . ''css/dark.css'');\r\n    \$GLOBALS[''rlStatic'']->addHeaderCSS(RL_TPL_BASE . ''css/style.css'');\r\n\r\n    if (RL_LANG_DIR == ''rtl'') {\r\n        \$GLOBALS[''rlStatic'']->removeCSS(RL_TPL_BASE . ''css/rtl.css'');\r\n        \$GLOBALS[''rlStatic'']->addHeaderCSS(RL_TPL_BASE . ''css/rtl.css'');\r\n    }\r\n}', 'active'),
('staticDataPostGet', '', 'nova_template', 'if (\$GLOBALS[''tpl_settings''][''dark_mode'']) {\r\n    foreach (\$param1 as &\$file) {\r\n        if (strpos(\$file, ''dark.css'')) {\r\n            \$media = ''(prefers-color-scheme: dark)'';\r\n            if (isset(\$_COOKIE[''colorTheme''])) {\r\n                \$media = \$_COOKIE[''colorTheme''] == ''light'' ? ''not all'' : ''all'';\r\n            }\r\n            \$file .= ''\" media=\"'' . \$media;\r\n        } else if (strpos(\$file, ''light.css'')) {\r\n            \$media = ''(prefers-color-scheme: no-preference), (prefers-color-scheme: light)'';\r\n            if (isset(\$_COOKIE[''colorTheme''])) {\r\n                \$media = \$_COOKIE[''colorTheme''] == ''dark'' ? ''not all'' : ''all'';\r\n            }\r\n            \$file .= ''\" media=\"'' . \$media;\r\n        }\r\n    }\r\n}', 'active');
MYSQL;
    $rlDb->query($sql);

    // Refresh the page to apply new hooks
    if (defined('REALM') && REALM == 'admin') {
        $reefless->referer();
        exit;
    }
}
