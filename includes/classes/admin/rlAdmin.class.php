<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLADMIN.CLASS.PHP
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

use Flynax\Utils\Util;
use Flynax\Utils\Valid;

class rlAdmin extends reefless
{
    /**
     * @var mMenu_controllers
     **/
    public $mMenu_controllers;

    /**
     * check admin panel logining
     *
     * @param mixed $user - admin username
     * @param MD5 $pass - admin user password
     * @param varchar $lang - language inerface
     *
     * @todo put data to session
     *
     **/
    public function LogIn($userInfo)
    {
        $_SESSION['sessAdmin'] = array(
            'user_id' => $userInfo['ID'],
            'user'    => $userInfo['User'],
            'pass'    => md5($userInfo['Pass']),
            'mail'    => $userInfo['Email'],
            'name'    => $userInfo['Name'],
            'rights'  => unserialize($userInfo['Rights']),
            'type'    => $userInfo['Type'],
        );
    }

    /**
     * administrator log out
     *
     * @todo destroy the current session and administrator information
     *
     **/
    public function LogOut()
    {
        unset($_SESSION['sessAdmin']);
        unset($_SESSION['query_string']);
    }

    /**
     * check admin log in
     *
     * @todo check admin login or not
     *
     * @return bool
     **/
    public function isLogin()
    {
        $username = $GLOBALS['rlValid']->xSql($_SESSION['sessAdmin']['user']);
        $password = $GLOBALS['rlValid']->xSql($_SESSION['sessAdmin']['pass']);

        if (!$username || !$password) {
            return false;
        }

        $pass = $this->getOne('Pass', "`User` = '{$username}' AND `Status` = 'active'", 'admins');

        if (!empty($pass)) {
            if (md5($pass) == $password) {
                return true;
            }
        }

        return false;
    }

    /**
     * get main admin menu
     *
     * @return mixed array data
     *
     **/
    public function getMainMenuItems()
    {
        $rights = $_SESSION['rlAdmin']['rights'];

        $this->setTable('admin_controllers');

        $mMenuItems = $this->fetch(array('ID', 'Key', 'Controller', 'Vars'), array('Parent_ID' => '0'), 'ORDER BY `Position`');
        $mMenuItems = $GLOBALS['rlLang']->replaceLangKeys($mMenuItems, 'admin_controllers', array('name'), RL_LANG_CODE, 'admin');
        foreach ($mMenuItems as $key => $value) {
            $mMenuChild = $this->fetch(array('Key', 'Controller', 'Vars'), array('Parent_ID' => $mMenuItems[$key]['ID']), 'ORDER BY `Position`');
            $mMenuChild = $GLOBALS['rlLang']->replaceLangKeys($mMenuChild, 'admin_controllers', array('name'), RL_LANG_CODE, 'admin');

            foreach ($mMenuChild as $mKey => $mVal) {
                // Remove "slides" controller if the current template doesn't support it
                if ($mVal['Key'] == 'slides' && !$GLOBALS['tpl_settings']['home_page_slides']) {
                    unset($mMenuChild[$mKey]);
                    continue;
                }

                $mMenuItems[$key]['Controllers_list'][] = $mVal['Controller'];

                if ($rights && array_key_exists($mVal['Key'], $rights)) {
                    $this->mMenu_controllers[$mVal['Key']] = $mVal['Controller'];
                }
            }

            if (!empty($mMenuChild)) {
                $mMenuItems[$key]['child'] = $mMenuChild;
            }

            if ($mMenuItems[$key]['Key'] == 'plugins') {
                $plugins = $this->fetch(
                    ['Name` AS `name', 'Key` AS `Plugin', 'Key', 'Controller'],
                    ['Install' => 1, 'Status' => 'active'],
                    "ORDER BY `ID`", null, 'plugins'
                );

                $mMenuItems[$key]['child'][0] = $mMenuChild[0];
                foreach ($plugins as $pluginKey => &$plugin) {
                    $mMenuItems[$key]['Controllers_list'][] = $plugin['Controller'];
                    $plugin['name'] = $GLOBALS['lang']['title_' . $plugin['Key']] ?: $plugin['name'];

                    if (!empty($plugins[$pluginKey]['Controller'])) {
                        $mMenuItems[$key]['child'][$pluginKey + 1] = $plugins[$pluginKey];
                    }
                }
            }
        }

        $this->resetTable();

        return $mMenuItems;
    }

    /**
     * get admin bread crumbs (recursive method)
     *
     * @param string $id    - current part id (controller)
     * @param string $aStep - additional step
     * @param array $path   - path
     *
     * @return mixed array - broad crumbs path
     *
     **/
    public function getBreadCrumbs($id, $aStep = array(), $path = array(), $plugin = false)
    {
        $GLOBALS['rlValid']->sql($id);

        if ($plugin) {
            $iteration = $this->fetch(
                ['Name` AS `name', 'Key` AS `Plugin', 'Controller'],
                ['Key' => $plugin],
                null, null, 'plugins', 'row'
            );

            $iteration['name'] = $GLOBALS['lang']['title_' . $iteration['Plugin']] ?: $iteration['name'];
        } else {
            $iteration = $this->fetch('*', array('ID' => $id), null, null, 'admin_controllers', 'row');
            $iteration = $GLOBALS['rlLang']->replaceLangKeys($iteration, 'admin_controllers', array('name'), RL_LANG_CODE, 'admin');
        }

        array_push($path, $iteration);

        if ($iteration['Parent_ID'] > '0') {
            return $this->getBreadCrumbs($iteration['Parent_ID'], $aStep, $path);
        } else {
            $path = array_reverse($path);
            if ($aStep != null) {
                if (is_array($aStep)) {
                    foreach ($aStep as $key => $value) {
                        array_push($path, $aStep[$key]);
                    }
                } else {
                    array_push($path, array('name' => $aStep));
                }
            }
            if (!$plugin) {
                unset($path[0]);
            }

            return $path;
        }
    }

    /**
     * get controller info by ID
     *
     * @param string $controller - current part (controller)
     *
     * @return mixed array - controller information
     *
     **/
    public function getController($controller)
    {
        $GLOBALS['rlValid']->sql($controller);

        $info = $this->fetch('*', array('Controller' => $controller), null, null, 'admin_controllers', 'row');
        $info = $GLOBALS['rlLang']->replaceLangKeys($info, 'admin_controllers', array('name'), RL_LANG_CODE, 'admin');

        if (empty($info)) {
            $info = $this->fetch(array('Name` AS `name', 'Key` AS `Plugin', 'Key', 'Controller'), array('Controller' => $controller), null, null, 'plugins', 'row');
        }

        $info['prev'] = $_SESSION['ad_prev_page_key'] ? $_SESSION['ad_prev_page_key'] : false;
        $_SESSION['ad_prev_page_key'] = $info['Key'];

        return $info;
    }

    /**
     * integrate special configuration values
     *
     * @param array $configs - referent to current configurations
     *
     * @return mixed array - configurations array with special values
     *
     **/
    public function mixSpecialConfigs(&$configs)
    {
        global $lang, $rlHook, $l_timezone, $tpl_settings, $rlLang, $rlDb, $reefless;

        /**
         * The list of box keys which will ignored for option 'home_special_box'
         *
         * @since 4.8.2
         *
         * @var array
         */
        $specialBoxRejectedKeys = [
            'rv_listings',
            'sl_similar_listings'
        ];

        /**
         * @since 4.8.2
         */
        $rlHook->load('apPhpMixConfigTop', $configs, $specialBoxRejectedKeys);

        /**
         * List of required fields with "select" type
         * These fields wouldn't have "- Select -" value
         *
         * @since 4.7.1
         *
         * @var array
         */
        $systemSelects = [
            'timezone',
            'map_provider',
            'static_map_provider',
            'geocoding_provider',
            'search_map_location_zoom',
            'map_default_zoom',
            'watermark_type',
            'watermark_position',
            'output_image_format',
            'keyword_search_type',
        ];

        foreach ($configs as $key => &$value) {
            $value['Values'] = explode(',', $value['Values']);

            if (in_array($value['Type'], array('select', 'radio', 'checkbox'))) {
                if (is_array($value['Values'])) {
                    $select_out = array();
                    $found_phrases = 0;

                    foreach ($value['Values'] as $select_value) {
                        $phrase = $lang['config+option+' . $value['Key'] . '_' . $select_value];

                        if ($phrase) {
                            $select_out[] = array(
                                'ID'   => $select_value,
                                'name' => $phrase,
                            );
                            $found_phrases++;
                        }
                    }

                    if ($found_phrases == count($value['Values'])) {
                        $value['Values'] = $select_out;
                    }
                }
            }

            // mixing by keys
            switch ($value['Key']) {
                case 'template':
                    $tpl_dir = RL_ROOT . "templates" . RL_DS;
                    $values = $this->scanDir($tpl_dir, true);
                    sort($values);

                    $value['Values'] = $values;
                    break;

                case 'lang':
                    $langList = $rlLang->getLanguagesList('all');
                    foreach ($langList as $lIndex => $lValue) {
                        $langValues[] = $langList[$lIndex]['Code'];
                    }
                    $value['Values'] = $langValues;
                    break;

                case 'alphabetic_field':
                    $this->setTable('account_fields');
                    $account_fields = $this->fetch(array('Key`, `Key` AS `ID'), array('Status' => 'active'), "AND FIND_IN_SET(`Type`, 'text,textarea') AND `Condition` != 'isUrl'");
                    $this->resetTable();

                    $value['Values'] = $rlLang->replaceLangKeys($account_fields, 'account_fields', array('name'));
                    break;

                case 'timezone':
                    $values = array();
                    foreach ($l_timezone as $tz_key => $tz) {
                        $values[] = array(
                            'name' => $tz[1],
                            'Key'  => $tz_key,
                            'ID'   => $tz_key,
                        );
                    }
                    $value['Values'] = $values;
                    break;

                case 'address_on_map':
                    if ($GLOBALS['config']['address_on_map']) {
                        /* add configs for account address on map mapping */
                        $sql = "SELECT `Key`, `Condition` FROM `{db_prefix}listing_fields` WHERE `Map` = '1' AND `Status` = 'active'";
                        $listing_fields = $this->getAll($sql);
                        $listing_fields = $rlLang->replaceLangKeys($listing_fields, 'listing_fields', array('name'));

                        $i = 0;
                        if (!isset($GLOBALS['config']['amp_divider'])) {
                            $add_conf[$i]['Key'] = 'amp_divider';
                            $add_conf[$i]['Type'] = 'divider';
                            $add_conf[$i]['Data_type'] = 'varchar';
                            $add_conf[$i]['Group_ID'] = $value['Group_ID'];
                            $add_conf[$i]['Position'] = 99;

                            $rlDb->insertOne($add_conf[$i], 'config');

                            $GLOBALS['config']['amp_divider'] = '';

                            foreach ($GLOBALS['languages'] as $lang_val) {
                                $lang_insert[] = array(
                                    'Code'   => $lang_val['Code'],
                                    'Key'    => 'config+name+' . $add_conf[$i]['Key'],
                                    'Value'  => $lang['amp_divider_name'],
                                    'Module' => 'admin',
                                    'Status' => 'active',
                                );
                            }
                            $rlDb->insert($lang_insert, 'lang_keys');

                            $add_conf[$i]['name'] = $lang['amp_divider_name'];
                        } else {
                            foreach ($listing_fields as $lk => $lVal) {
                                $map_configs .= "address_on_map_" . $lVal['Key'] . ",";
                            }
                            $map_configs = substr($map_configs, 0, -1);
                            $sql = "DELETE FROM `{db_prefix}config` ";
                            $sql .= "WHERE NOT FIND_IN_SET(`Key`,'{$map_configs}') ";
                            $sql .= "AND `Key` LIKE 'address_on_map_%' ";

                            $this->query($sql);
                        }

                        foreach ($listing_fields as $lk => $lVal) {
                            $clone = array();
                            $conf_key = $value['Key'] . "_" . $lVal['Key'];

                            if (!isset($GLOBALS['config'][$conf_key])) {
                                $i++;
                                $add_conf[$i]['Key'] = $conf_key;
                                $add_conf[$i]['Type'] = "select";
                                $add_conf[$i]['Data_type'] = "varchar";
                                $add_conf[$i]['Group_ID'] = $value['Group_ID'];
                                $add_conf[$i]['Position'] = 100;

                                $GLOBALS['rlActions']->insertOne($add_conf[$i], "config");

                                $k = 0;
                                $lang_insert = array();
                                foreach ($GLOBALS['languages'] as $lkey => $lang_val) {
                                    $lang_insert[$k]['Code'] = $lang_val['Code'];
                                    $lang_insert[$k]['Key'] = 'config+name+' . $add_conf[$i]['Key'];
                                    $lang_insert[$k]['Value'] = $lVal['name'];
                                    $lang_insert[$k]['Module'] = 'admin';
                                    $lang_insert[$k]['Status'] = 'active';

                                    if ($lVal['Condition']) {
                                        $k++;
                                        $lang_insert[$k]['Code'] = $lang_val['Code'];
                                        $lang_insert[$k]['Key'] = 'config+des+' . $add_conf[$i]['Key'];
                                        $lang_insert[$k]['Value'] = $lang['amp_condition_hint'];
                                        $lang_insert[$k]['Module'] = 'admin';
                                        $lang_insert[$k]['Status'] = 'active';

                                        $add_conf[$i]['des'] = $lang_insert[$k]['Value'];
                                    }
                                    $k++;
                                }
                                $GLOBALS['rlActions']->insert($lang_insert, "lang_keys");
                                $add_conf[$i]['name'] = $lVal['name'];
                            }
                        }
                    }
                    break;

                case 'header_banner_space':
                    // unset 'header banner space' config if the current template doesn't allow it
                    if (!$tpl_settings['header_banner']) {
                        unset($configs[$key]);
                    }
                    break;

                case 'category_alphabet_box':
                    // unset 'category_alphabet_box' config if the current template doesn't allow it
                    if (!$tpl_settings['category_alphabet_box']) {
                        unset($configs[$key]);
                    }
                    break;

                case 'price_tag_field':
                    $this->setTable('listing_fields');
                    $price_fields = $this->fetch(array('Key`, `Key` AS `ID'), array('Status' => 'active', 'Type' => 'price'));
                    $this->resetTable();

                    $value['Values'] = $rlLang->replaceLangKeys($price_fields, 'listing_fields', array('name'));
                    break;

                case 'banner_in_grid_position':
                    $value['Values'] = array();

                    for ($i = 3; $i <= ((int) $GLOBALS['config']['listings_per_page'] ?: 10); $i++) {
                        $value['Values'][$i] = $i;
                    }
                    break;

                case 'home_special_box':
                    if ($tpl_settings['home_page_special_block']) {
                        $sql = "
                            SELECT `Key`, `Key` AS `ID`
                            FROM `{db_prefix}blocks`
                            WHERE `Key` NOT LIKE 'ltfb\_%' AND `Status` = 'active'
                            AND `Key` NOT IN ('" . implode("','", $specialBoxRejectedKeys) . "')
                            AND FIND_IN_SET('1', `Page_ID`)
                        ";
                        $boxes = $rlDb->getAll($sql);

                        foreach ($boxes as $box) {
                            $boxesKeys[] = $box['Key'];
                        }

                        $rlDb->outputRowsMap = ['Key', 'Value'];
                        $boxesPhrases = $rlDb->fetch(
                            ['Key', 'Value'],
                            ['Code' => RL_LANG_CODE],
                            "AND `Key` IN ('blocks+name+" . implode("','blocks+name+", $boxesKeys) . "')",
                            null, 'lang_keys'
                        );
                        $lang = array_merge($lang, (array) $boxesPhrases);

                        $value['Values'] = $rlLang->replaceLangKeys($boxes, 'blocks', ['name']);
                    } else {
                        unset($configs[$key]);
                    }
                    break;

                case 'home_gallery_box':
                    if ($tpl_settings['home_page_gallery']) {
                        $select = "`T1`.`Key`, `T1`.`Key` AS `ID`";
                        $where  = "`T1`.`Status` = 'active' ";
                        $where .= "AND ((`T1`.`Key` LIKE 'ltfb\_%' AND `T2`.`Photo` = '1') ";
                        $where .= "OR `T1`.`Plugin` = 'listings_box') AND FIND_IN_SET('1', `Page_ID`) ";

                        $sql = "SELECT {$select} FROM `{db_prefix}blocks` AS `T1` ";
                        $sql .= "LEFT JOIN `{db_prefix}listing_types` AS `T2` ";
                        $sql .= "ON `T2`.`Key` = REPLACE(`T1`.`Key`, 'ltfb_', '') ";
                        $sql .= "WHERE {$where} ";

                        /**
                         * @since 4.7.1 - Last parameter $addWhere removed;
                         *              - Structure of other params have been changed
                         */
                        $rlHook->load('apPhpConfigHomeGalleryBox', $value, $select, $where);

                        $boxes = $rlDb->getAll($sql);

                        foreach ($boxes as $box) {
                            $boxesKeys[] = $box['Key'];
                        }

                        $rlDb->outputRowsMap = ['Key', 'Value'];
                        $boxesPhrases = $rlDb->fetch(
                            ['Key', 'Value'],
                            ['Code' => RL_LANG_CODE],
                            "AND `Key` IN ('blocks+name+" . implode("','blocks+name+", $boxesKeys) . "')",
                            null, 'lang_keys'
                        );
                        $lang = array_merge($lang, (array) $boxesPhrases);

                        $value['Values'] = $rlLang->replaceLangKeys($boxes, 'blocks', ['name']);
                    } else {
                        unset($configs[$key]);
                    }
                    break;

                case 'search_map_location_zoom':
                case 'map_default_zoom':
                    $value['Values'] = array();

                    foreach (range(1, 19) AS $item) {
                        $set_name = $item;
                        switch($item) {
                            case 1:
                                $set_name = "{$item} ({$lang['zoom_world']})";
                                break;

                            case 11:
                                $set_name = "{$item} ({$lang['zoom_city']})";
                                break;

                            case 19:
                                $set_name = "{$item} ({$lang['zoom_street']})";
                                break;
                        }
                        $value['Values'][] = array('ID' => $item, 'name' => $set_name);
                    }
                    break;
                case 'geocoding_restrict_by_country':
                    $countries = [];
                    foreach (Util::getCountries(RL_LANG_CODE) as $isoCode => $countryName) {
                        $countries[] = ['ID' => $isoCode, 'name' => $countryName];
                    }
                    $value['Values'] = $countries;
                    break;
                case 'watermark_text_font':
                    $value['Values'] = array_filter($reefless->scanDir(RL_LIBS . 'fonts/'), function ($font) {
                        return pathinfo($font, PATHINFO_EXTENSION) === 'ttf';
                    });
                    break;
                case 'output_image_format':
                    $value['Values'] = array_map(function ($format) {
                        return ['ID' => strtolower($format), 'name' => $format];
                    }, $GLOBALS['output_image_formats']);
                    break;
                case 'keyword_search_type':
                    $value['Values'] = array_map(function ($value) use ($lang) {
                        return ['ID' => $value, 'name' => $lang['keyword_search_opt' . $value]];
                    }, $value['Values']);
                    break;
            }

            /**
             * @since 4.7.1 - Added $systemSelects parameter
             */
            $rlHook->load('apMixConfigItem', $value, $systemSelects);
        }

        $GLOBALS['rlSmarty']->assign('systemSelects', $systemSelects);

        if ($add_conf) {
            $configs = array_merge($configs, $add_conf);
        }

        /* prepare values for account on map mapping */
        foreach ($configs as &$value) {
            if (is_numeric(strpos($value['Key'], 'address_on_map_'))) {
                $sql = "SELECT `Type`, `Condition` FROM `{db_prefix}listing_fields` ";
                $sql .= "WHERE `Key` = '" . str_replace('address_on_map_', '', $value['Key']) . "' ";
                $lf_info = $this->getRow($sql);

                $sql = "SELECT `Key`, `Key` AS `ID` FROM `{db_prefix}account_fields` AS `T1` ";
                $sql .= "WHERE `Map` = '1' AND `Status` = 'active' ";
                $sql .= "AND `Type` = '{$lf_info['Type']}' AND `Condition` = '{$lf_info['Condition']}'";

                $account_fields_map = $this->getAll($sql);
                $account_fields_map = $rlLang->replaceLangKeys($account_fields_map, 'account_fields', array('name'));

                $value['Values'] = $account_fields_map;
            }
        }
    }

    /**
     * delete page
     *
     * @package ajax
     *
     * @param string $key - page key
     *
     **/
    public function ajaxDeletePage($key)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);

            return $_response;
        }

        $key = $GLOBALS['rlValid']->xSql($key);

        $lang_keys[] = array(
            'Key' => 'pages+name+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'pages+title+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'pages+h1+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'pages+meta_description+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'pages+meta_keywords+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'pages+content+' . $key,
        );

        $page_readonly = $this->fetch(array('Readonly'), array('Key' => $key), "AND `Status` <> 'trash'", 1, 'pages', 'row');
        if (!$page_readonly['Readonly']) {
            $GLOBALS['rlActions']->delete(array('Key' => $key), array('pages', 'lang_keys'), "Readonly = '0'", 1, $key, $lang_keys);

            $del_mode = $GLOBALS['rlActions']->action;

            $_response->script("
                pagesGrid.reload();
                printMessage('notice', '{$lang['page_' . $del_mode]}');
            ");
        } else {
            $_response->script("printMessage('alert', '{$lang['page_readonly']}')");
        }

        return $_response;
    }

    /**
     * delete block
     *
     * @package ajax
     *
     * @param string $key - block key
     *
     **/
    public function ajaxDeleteBlock($key)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlValid']->sql($key);

        $lang_keys[] = array(
            'Key' => 'blocks+name+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'blocks+content+' . $key,
        );

        $block_info = $this->fetch(array('Readonly', 'Content', 'Type'), array('Key' => $key), "AND `Status` <> 'trash'", 1, 'blocks', 'row');

        if (!$block_info['Readonly']) {
            $GLOBALS['rlActions']->delete(array('Key' => $key), array('blocks', 'lang_keys'), "Readonly = '0'", 1, $key, $lang_keys);

            $del_mode = $GLOBALS['rlActions']->action;

            $_response->script("
                blocksGrid.reload();
                printMessage('notice', '{$lang['block_' . $del_mode]}')
            ");

            // delete file | for banner type
            if ($block_info['Type'] == 'banner' && !$GLOBALS['config']['trash']) {
                unlink(RL_FILES . $block_info['Content']);
            }
        } else {
            $_response->script("printMessage('alert', '{$lang['block_readonly']}')");
        }

        return $_response;
    }

    /**
     * delete admin
     *
     * @package ajax
     *
     * @param string $id - admin ID
     *
     **/
    public function ajaxDeleteAdmin($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        $GLOBALS['rlActions']->delete(array('ID' => $id), array('admins'), null, 1, $id);

        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            adminGrid.reload();
            printMessage('notice', '{$lang['admin_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * get and build an additional fields
     *
     * @package xAjax
     *
     * @param int $id - account type id
     *
     **/
    public function ajaxGetAccountFields($id)
    {
        global $_response, $rlAccount, $rlCommon, $lang, $rlSmarty;

        $id = (int) $id;
        $fields = $rlAccount->getFields($id);
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, 'account_fields', array('name', 'description'));
        $fields = $rlCommon->fieldValuesAdaptation($fields, 'account_fields');

        if (empty($fields)) {
            $_response->script("
                form_submit = true;
                document.account_reg_form.submit();
            ");
        } else {
            $jsScript = '';
            foreach ($fields as $key => $value) {
                switch ($value['Type']) {
                    case 'date':
                        if ($fields[$key]['Default'] == 'single') {
                            $jsScript .= "$('#date_{$fields[$key]['Key']}').datepicker({
                                showOn         : 'both',
                                buttonImage    : '" . RL_TPL_BASE . "img/calendar.png',
                                buttonText     : '{$lang['dp_choose_date']}',
                                buttonImageOnly: true,
                                dateFormat     : 'yy-mm-dd',
                                changeMonth    : true,
                                changeYear     : true,
                                yearRange      : '-100:+30'
                            }).datepicker($.datepicker.regional['" . RL_LANG_CODE . "']);";
                        } else {
                            $jsScript .= "$('#date_{$fields[$key]['Key']}_from').datepicker({
                                showOn         : 'both',
                                buttonImage    : '" . RL_TPL_BASE . "img/calendar.png',
                                buttonText     : '{$lang['dp_choose_date']}',
                                buttonImageOnly: true,
                                dateFormat     : 'yy-mm-dd',
                                changeMonth    : true,
                                changeYear     : true,
                                yearRange      : '-100:+30'
                            }).datepicker($.datepicker.regional['" . RL_LANG_CODE . "']);";

                            $jsScript .= "$('#date_{$fields[$key]['Key']}_to').datepicker({
                                showOn         : 'both',
                                buttonImage    : '" . RL_TPL_BASE . "img/calendar.png',
                                buttonText     : '{$lang['dp_choose_date']}',
                                buttonImageOnly: true,
                                dateFormat     : 'yy-mm-dd',
                                changeMonth    : true,
                                changeYear     : true,
                                yearRange      : '-100:+30'
                            }).datepicker($.datepicker.regional['" . RL_LANG_CODE . "']);";
                        }
                        break;

                    case 'accept':
                        unset($fields[$key]);
                        break;
                    case 'select':
                        if ($fields[$key]['Autocomplete']) {
                            $jsScript .= "flynax.addAutocompleteForDropdown(
                                $('[name=\"f[{$fields[$key]['Key']}]\"]')
                            );";
                        }
                        break;
                }
            }

            $rlSmarty->assign_by_ref('fields', $fields);

            $tpl = 'blocks' . RL_DS . 'account_field.tpl';
            $_response->assign('additional_fields', 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));
            $_response->script("$('#account_field_area').fadeIn('slow');");
            $_response->script($jsScript);

            $_response->script("
                $('.qtip').each(function(){
                    $(this).qtip({
                        content: $(this).attr('title'),
                        show: 'mouseover',
                        hide: 'mouseout',
                        position: {
                            corner: {
                                target: 'topRight',
                                tooltip: 'bottomLeft'
                            }
                        },
                        style: {
                            width: 150,
                            background: '#8e8e8e',
                            color: 'white',
                            border: {
                                width: 7,
                                radius: 5,
                                color: '#8e8e8e'
                            },
                            tip: 'bottomLeft'
                        }
                    });
                }).attr('title', '');

                flynax.tabs();
            ");

            $_response->script("flynax.slideTo('#account_field_area'); $('#next1').slideUp();");
            $_response->script("
                var run = '';
                $('.eval').each(function(){
                    run += $(this).html();
                });

                eval(run);
            ");
        }

        $_response->script(
            "$('#step1_loading').fadeOut('normal');
            $('input.numeric').numeric();
            flynax.phoneField();"
        );

        $GLOBALS['rlHook']->load('apPhpSubmitProfileEnd');

        return $_response;
    }

    /**
     * delete account
     *
     * @package ajax
     *
     * @param int $id - account ID
     * @param string $reason - reason text
     * @param bool $direct - direct call mode, no xajax code
     *
     **/
    public function ajaxDeleteAccount($id = false, $reason = false, $direct = false)
    {
        global $_response, $config, $lang, $delete_items, $rlHook, $rlDb;

        // check admin session expire
        if ($this->checkSessionExpire() === false && !$direct) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$id) {
            return $_response;
        }

        if (is_array($id)) {
            $replace_id = (int) $id[1];
            $id = (int) $id[0];
        } else {
            $id = (int) $id;
        }

        /* get account info */
        $account_info = $GLOBALS['rlAccount']->getProfile($id);

        /* replace mode */
        if ($replace_id) {
            if ($replace_id == $id) {
                $_response->script("printMessage('error', '" . str_replace('{username}', $account_info['Username'], $lang['replace_account_duplicate']) . "');");
                return $_response;
            } else {
                $update = array(
                    'fields' => array(
                        'Account_ID' => $replace_id,
                    ),
                    'where'  => array(
                        'Account_ID' => $id,
                    ),
                );
                $rlDb->updateOne($update, 'listings');
                $rlDb->updateOne($update, 'tmp_categories');

                // Refresh listing count of a recipient
                $rlDb->query(
                    "UPDATE `{db_prefix}accounts` SET `Listings_count` = (
                        SELECT COUNT(*) FROM `{db_prefix}listings` AS `T1`
                        WHERE `T1`.`Status` = 'active'
                          AND `T1`.`Account_ID` = `{db_prefix}accounts`.`ID`
                        )
                    WHERE `ID` = {$replace_id}"
                );
            }
        }

        /* save listings data */
        $sql = "SELECT `T1`.`ID`, `T1`.`Category_ID`, `T1`.`Crossed`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Account_ID` = '{$id}'";
        $listings = $this->getAll($sql);

        /* delete/trash account data */
        $delete_items = array('accounts', 'listings');
        $rlHook->load('deleteAccountSetItems', $id, $replace_id, $listings); //>= 4.4.1

        if (!$config['trash']) {
            $this->deleteAccountDetails($id, $listings);
        } else {
            $GLOBALS['reefless']->loadClass('Listings');
            $GLOBALS['rlListings']->listingStatusControl(array('Account_ID' => $id), 'inactive');
        }

        /* delete account data */
        $GLOBALS['rlActions']->delete(array('ID' => $id), $delete_items, false, false, $id);

        /* send message for account owner */
        $this->loadClass('Mail');
        $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('account_deleted', $account_info['Lang']);
        $find = array(
            '{name}',
            '{reason}',
        );

        $no_reason_specified_phrase = $GLOBALS['rlLang']->getPhrase(
            array('key' => 'no_reason_specified', 'lang' => $account_info['Lang'])
        );
        $replace = array(
            $account_info['Full_name'],
            $reason ? nl2br($reason) : ($no_reason_specified_phrase ?: $lang['no_reason_specified']),
        );

        $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);
        $GLOBALS['rlMail']->send($mail_tpl, $account_info['Mail']);

        $GLOBALS['rlCache']->updateStatistics();

        if (!$direct) {
            /* print notice */
            $del_mode = $GLOBALS['rlActions']->action;
            if ($_GET['action']) {
                $this->loadClass('Notice');
                $GLOBALS['rlNotice']->saveNotice($lang['account_' . $del_mode . '_notice']);
                $redirect_url = RL_URL_HOME . ADMIN . "/index.php?controller=accounts";
                $_response->redirect($redirect_url);
            } else {
                $_response->script("
                    accountsGrid.reload();
                    $('#delete_block').fadeOut();
                    printMessage('notice', '{$lang['account_' . $del_mode . '_notice']}');
                ");
            }

            return $_response;
        }
    }

    /**
     * Remove account and all info (message, listings and etc.) from website
     *
     * @since 4.7.0 - Added $force_removing parameter
     *
     * @param  int   $id
     * @param  array $listings       - Data of listings
     * @param  bool  $force_removing - Remove info about listings and account from DB
     * @return bool
     */
    public function deleteAccountDetails($id = 0, $listings = array(), $force_removing = false)
    {
        global $rlDb, $rlCache, $reefless;

        if (!$id) {
            return false;
        }

        // Delete folder with thumbnail of account
        if ($photo = $rlDb->getOne('Photo', "`ID` = {$id}", 'accounts')) {
            // Old format of account photos
            if (is_file(RL_FILES . $photo) && false === strpos($photo, 'account-media/')) {
                unlink(RL_FILES . $photo);
            } else {
                $reefless->deleteDirectory(RL_FILES . dirname($photo));
            }
        }

        $rlDb->query("DELETE FROM `{db_prefix}messages` WHERE `From` = {$id} OR `To` = {$id}");

        // delete all account files
        $file_fields = $rlDb->getAll("
            SELECT `Key` FROM `{db_prefix}account_fields`
            WHERE `Type` = 'file' OR `Type` = 'image'
        ");

        if ($file_fields) {
            $files_sql = "SELECT ";
            foreach ($file_fields as $key => $field) {
                $files_sql .= "`" . $file_fields[$key]['Key'] . "`, ";
            }
            $files_sql = substr($files_sql, 0, -2);
            $files_sql .= " FROM `{db_prefix}accounts` WHERE `ID` = {$id}";

            foreach ($rlDb->getRow($files_sql) as $key => $value) {
                if (!empty($files[$key])) {
                    unlink(RL_FILES . $files[$key]);
                }
            }
        }

        foreach (array('favorites', 'saved_search', 'tmp_categories', 'listing_packages') as $table) {
            $rlDb->delete(array('Account_ID' => $id), $table, null, 0);
        }

        // get account listings
        if (!$listings) {
            $sql = "SELECT `T1`.`ID`, `T1`.`Category_ID`, `T1`.`Crossed`, `T2`.`Type` AS `Listing_type` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Account_ID` = {$id}";
            $listings = $rlDb->getAll($sql);
        }

        if ($listings) {
            if ($force_removing) {
                $GLOBALS['config']['trash'] = 0;
            }

            $reefless->loadClass('Listings');

            foreach ($listings as $listing) {
                $GLOBALS['rlListings']->deleteListingData(
                    $listing['ID'],
                    $listing['Category_ID'],
                    $listing['Crossed'],
                    $listing['Listing_type'],
                    false
                );
            }
        }

        if ($force_removing) {
            $GLOBALS['config']['trash'] = 0;

            $delete_items = array('accounts', 'listings');

            /**
             * @since 4.7.0
             */
            $GLOBALS['rlHook']->load('phpDeleteAccountDetails', $id, $delete_items);

            foreach ($delete_items as $table) {
                $rlDb->delete(array(($table == 'accounts' ? 'ID' : 'Account_ID') => $id), $table, null, 0);
            }
        }

        // update count of listings in categories and listing types
        $GLOBALS['rlCategories']->recountCategories();
        $GLOBALS['rlListingTypes']->updateCountListings();
        $rlCache->updateCategories();
        $rlCache->updateStatistics();

        return true;
    }

    /**
     * delete news
     *
     * @package ajax
     *
     * @param string $id - news ID
     *
     **/
    public function ajaxDeleteNews($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;

        $lang_keys[] = array(
            'Key' => 'news+title+' . $id,
        );
        $lang_keys[] = array(
            'Key' => 'news+content+' . $id,
        );
        $lang_keys[] = array(
            'Key' => 'news+meta_keywords+' . $id,
        );
        $lang_keys[] = array(
            'Key' => 'news+meta_description+' . $id,
        );

        $GLOBALS['rlActions']->delete(array('ID' => $id), array('news'), null, null, $id, $lang_keys);

        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            newsGrid.reload();
            printMessage('notice', '{$lang['news_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * delete contact
     *
     * @package ajax
     *
     * @param string $id - contact ID
     *
     **/
    public function ajaxDeleteContact($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        $GLOBALS['rlActions']->delete(array('ID' => $id), array('contacts'), $id, null, $id, false);

        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            contactsGrid.reload();
            printMessage('notice', '{$lang['contact_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * delete transaction
     *
     * @package ajax
     *
     * @param string $id - transaction ID
     *
     **/
    public function ajaxDeleteTransaction($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (false === (bool) strpos($id, '|')) {
            $id = (int) $id;
            $GLOBALS['rlActions']->delete(array('ID' => $id), array('transactions'), $id, null, $id, false);
        } else {
            $ids = explode('|', $id);
            foreach ($ids as $id) {
                $id = (int) $id;
                $GLOBALS['rlActions']->delete(array('ID' => $id), array('transactions'), $id, null, $id, false);
            }
        }

        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            transactionsGrid.reload();
            transactionsGrid.checkboxColumn.clearSelections();
            transactionsGrid.actionsDropDown.setVisible(false);
            transactionsGrid.actionButton.setVisible(false);
            printMessage('notice', '{$lang['transaction_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * delete plan using
     *
     * @package ajax
     *
     * @param string $id - item ID
     *
     **/
    public function ajaxDeletePlanUsing($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        $this->query("DELETE FROM `{db_prefix}listing_packages` WHERE `ID` = {$id} LIMIT 1");

        $_response->script("
            plansUsingGrid.reload();
            printMessage('notice', '{$lang['item_deleted']}');
        ");

        return $_response;
    }

    /**
     * restore item from Trash Box
     *
     * @param string $id - trash item ID
     *
     **/
    public function restoreTrashItem($id)
    {
        if (!$id) {
            return false;
        }

        $id = (int) $id;
        $trash_item = $this->fetch('*', array('ID' => $id), null, 1, 'trash_box', 'row');
        $criterion = $trash_item['Criterion'];

        if (empty($trash_item['Criterion']) && empty($trash_item['Key'])) {
            $GLOBALS['rlDebug']->logger("Can not restore item from Trash Box, CRITERIONS or KEY/ID does not define");

            return false;
        }

        $tables = explode(',', $trash_item['Zones']);

        if (empty($tables)) {
            return false;
        }

        /* call restore method */
        $className = $trash_item['Class_name'];
        $restoreMethod = $trash_item['Restore_method'];

        if ($className && $restoreMethod) {
            $this->loadClass($className, null, $plugin);
            $className = 'rl' . $className;

            if (!method_exists($className, $restoreMethod)) {
                $GLOBALS['rlDebug']->logger("There are not such method ({$restoreMethod}) in loaded class ({$className})");
                return false;
            }

            global $$className;
            $$className->$restoreMethod($trash_item['Key']);
        }

        /* restore item */
        foreach ($tables as $table) {
            if ($tables[0] == 'accounts' && $table != 'accounts') {
                $criterion = str_replace('ID', 'Account_ID', $criterion);
            }

            switch ($table) {
                case 'contacts':
                    $new_status = 'readed';
                    break;

                case 'categories':
                    $new_status = 'active';
                    $this->loadClass('Categories');
                    $cat_id = $this->getOne('ID', $criterion, $table);

                    if ($cat_id) {
                        $GLOBALS['rlCategories']->categoryWalker($cat_id, 'restore');
                    }

                    break;
                case 'account_types':
                    $sql = "UPDATE `{db_prefix}pages` AS `T1` ";
                    $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('pages+name+', `T1`.`Key`) ";
                    $sql .= "SET `T1`.`Status` = 'approval', `T2`.`Status` = 'approval' ";
                    $sql .= "WHERE `T1`.`Key` = 'at_{$trash_item['Key']}'";
                    $this->query($sql);
                /*case 'listing_type':
                $type_info = $this -> fetch(array('Key', 'ID'), null, "WHERE {$trash_item['Criterion']}", 1, 'listing_types', 'row');
                $rlListingTypes -> restoreListingTypeData($type_info['Key']);*/

                default:
                    $new_status = 'approval';
                    break;
            }

            $sql = "UPDATE `" . RL_DBPREFIX . $table . "` SET `Status` = '{$new_status}' WHERE " . $criterion;
            $this->query($sql);

            // update cache
            if ($table == 'categories') {
                $GLOBALS['rlCache']->updateCategories();
                $GLOBALS['rlCache']->updateStatistics();
            }
        }

        // Restore languages phrases
        if (!empty($trash_item['Lang_keys'])) {
            $lang_keys = unserialize($trash_item['Lang_keys']);
            // Set "Active" status for phrases
            foreach ($lang_keys as $lKey => $lVal) {
                $l_update[$lKey]['where'] = $lang_keys[$lKey];
                $l_update[$lKey]['fields'] = array('Status' => 'active');
            }

            if ($l_update) {
                $this->loadClass('Actions');
                $GLOBALS['rlActions']->update($l_update, 'lang_keys');
            }
        }

        /* delete item from trash box */
        $this->query("DELETE FROM `{db_prefix}trash_box` WHERE `ID` = '{$id}' LIMIT 1");

        return true;
    }

    /**
     * restore item from Trash Box
     *
     * @package ajax
     *
     * @param string $id - item ID
     *
     **/
    public function ajaxRestoreTrashItem($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if ($this->restoreTrashItem($id)) {
            $_response->script("
                trashGrid.reload();
                printMessage('notice', '{$lang['item_restored']}');
            ");
        }

        return $_response;
    }

    /**
     * delete item from Trash Box
     *
     * @package ajax
     *
     * @param string $id - item ID
     *
     **/
    public function ajaxDeleteTrashItem($id)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if ($this->deleteTrashItem($id)) {
            $_response->script("
                trashGrid.reload();
                printMessage('notice', '{$lang['item_deleted']}');
            ");
        }

        return $_response;
    }

    /**
     * Delete item from Trash Box
     *
     * @param  int  $id - Trash item ID
     * @return bool
     */
    public function deleteTrashItem($id = 0)
    {
        $id = (int) $id;

        if (!$id) {
            return false;
        }

        global $rlDb, $rlCategories, $rlListings, $reefless, $rlDebug;

        $trashItem = $rlDb->fetch('*', ['ID' => $id], null, 1, 'trash_box', 'row');

        if (empty($trashItem['Criterion'])
            || (empty($trashItem['Key'])
                && $trashItem['Zones'] != 'listings'
                && $trashItem['Zones'] != 'saved_search'
            )
        ) {
            $rlDebug->logger('Can not delete item from Trash Box, CRITERIONS or KEY/ID does not define');
            return false;
        }

        $tables = $trashItem['Zones'];
        if (false !== strpos($tables, ',')) {
            $tables = explode(',', $tables);
        }

        if (!is_array($tables)) {
            $tables = [$tables];
        }

        if (empty($tables)) {
            return false;
        }

        // Call delete method
        $className    = $trashItem['Class_name'];
        $deleteMethod = $trashItem['Remove_method'];

        if ($className && $deleteMethod) {
            $reefless->loadClass($className);
            $className = 'rl' . $className;

            if (!method_exists($className, $deleteMethod)) {
                $rlDebug->logger("There are not such method ({$deleteMethod}) in loaded class ({$className})");
                return false;
            }

            global $$className;
            $$className->$deleteMethod($trashItem['Key']);
        }

        $removedEntity = $tables[0];
        $criterion     = $trashItem['Criterion'];

        switch ($removedEntity) {
            case 'accounts':
                $this->deleteAccountDetails($trashItem['Key'],  null, true);
                break;

            case 'listings':
                $reefless->loadClass('Listings');
                $rlListings->deleteListingData($trashItem['Key']);
                break;

            case 'categories':
                $reefless->loadClass('Categories');
                $where = "WHERE {$trashItem['Criterion']}";
                $cat_info = $rlDb->fetch(['Key', 'ID'], null, $where, 1, 'categories', 'row');

                $rlCategories->categoryWalker($cat_info['ID'], 'delete');
                $rlCategories->deleteCatRelations($cat_info['ID']);
                break;

            case 'tmp_categories':
                $rlDb->query(
                    "DELETE FROM `{db_prefix}tmp_categories`
                     WHERE {$trashItem['Criterion']} LIMIT 1"
                );
                break;

            case 'listing_groups':
                $this->loadClass('Categories');
                $where = "WHERE {$trashItem['Criterion']}";
                $groupKey = $rlDb->fetch(['Key'], null, $where, 1, 'listing_groups', 'row');

                $rlCategories->deleteGroupRelations($groupKey['Key']);
                break;

            case 'data_formats':
                $formatID = $rlDb->getOne('ID', $criterion, $removedEntity);

                // Get child keys
                $rlDb->setTable('data_formats');
                $childKeys = $rlDb->fetch(['Key'], ['Parent_ID' => $formatID]);
                $rlDb->resetTable();

                // Remove items lang keys
                foreach ($childKeys as $cKey => $cVal) {
                    $rlDb->query(
                        "DELETE FROM `{db_prefix}lang_keys`
                         WHERE `Key` = 'data_formats+name+{$childKeys[$cKey]['Key']}'"
                    );
                }

                if ($formatID) {
                    $rlDb->query(
                        "DELETE FROM `{db_prefix}{$removedEntity}`
                         WHERE `Parent_ID` = '{$formatID}'"
                    );
                }
                break;

            case 'search_forms':
                $formID = $rlDb->getOne('ID', "{$trashItem['Criterion']}", 'search_forms');
                $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$formID}'");
                break;

            default:
                $rlDb->query("DELETE FROM `{db_prefix}{$removedEntity}` WHERE {$criterion}");
                break;
        }

        // Delete language's phrases
        if (!empty($trashItem['Lang_keys'])) {
            $langKeys = unserialize($trashItem['Lang_keys']);

            if (!empty($langKeys)) {
                $where = '';
                foreach ($langKeys as $lKey => $lVal) {
                    $where .= "`Key` = '{$langKeys[$lKey]['Key']}' OR ";
                }
                $where = substr($where, 0, -3);
                $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE {$where}");
            }
        }

        $rlDb->query("DELETE FROM `{db_prefix}trash_box` WHERE `ID` = '{$id}' LIMIT 1");

        return true;
    }

    /**
     * clear trash box
     *
     * @package ajax
     *
     **/
    public function ajaxClearTrash()
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $this->setTable('trash_box');

        $trash = $this->fetch('ID');

        foreach ($trash as $item) {
            $this->deleteTrashItem($item['ID']);
        }

        $_response->script("
            trashGrid.reload();
            printMessage('notice', '{$lang['trash_cleared']}');
            $('.button_bar span.center_remove').html('{$lang['clear_trash']}');
        ");

        return $_response;
    }

    /**
     * mass actions with trash
     *
     * @package xAjax
     *
     * @param string $ids     - listings ids, ex: 250,3942,501,...
     * @param string $action  - mass action
     *
     **/
    public function ajaxTrashMassActions($ids, $action)
    {
        global $_response, $lang;

        $ids = explode('|', $ids);

        foreach ($ids as $id) {
            if ($action == 'delete') {
                $this->deleteTrashItem($id);
                $notice = $lang['notice_items_deleted'];
            } elseif ($action == 'restore') {
                $this->restoreTrashItem($id);
                $notice = $lang['notice_items_restored'];
            }
        }

        $_response->script("
            trashGrid.reload();
            printMessage('notice', '{$notice}');
        ");

        return $_response;
    }

    /**
     * Execute SQL query
     *
     * @param string $query - SQL query
     * @package ajax
     **/
    public function ajaxRunSqlQuery($query)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING'])
            ? '?session_expired'
            : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $lines = preg_split('/\r\n|\r|\n/', $query);
        $this->dieIfError = false;

        if (isset($lines[1])) {
            foreach ($lines as $query) {
                $query = trim($query);
                if ($query[0] == '#' || $query[0] == '-') {
                    continue;
                }

                if ($query[strlen($query) - 1] == ';') {
                    $query_sql .= $query;
                } else {
                    $query_sql .= $query;
                    continue;
                }

                if (!empty($query_sql)) {
                    $prefix = array('{prefix}', '{sql_prefix}', '{db_prefix}');
                    $query_sql = str_replace($prefix, RL_DBPREFIX, $query_sql);
                }

                $res = $this->query($query_sql);
                if (!$res) {
                    $errors[] = $lang['can_not_run_sql_query'] . addslashes($this->lastError());
                }
                unset($query_sql);

                $rows += $this->affectedRows();
            }

            /* print errors */
            if ($errors) {
                $out = '<ul>';
                foreach ($errors as $error) {
                    $out .= '<li>' . $error . '</li>';
                }
                $out .= '</ul>';
                $_response->script("printMessage('error', '" . $out . "');");
            } else {
                $message = str_replace('{number}', '<b>' . $rows . '</b>', $lang['query_ran']);
                $_response->script("printMessage('notice', '" . $message . "');");
            }
        } else {
            $prefix = array('{prefix}', '{sql_prefix}', '{db_prefix}');
            $query = str_replace($prefix, RL_DBPREFIX, $query);
            $res = $this->query($query);

            if (!$res) {
                $_response->script('printMessage("error", "' . $this->lastError() . '");');
            } else {
                preg_match(sprintf("/^(SELECT|SHOW).+\s+FROM\s+\`?%s.+/i", RL_DBPREFIX), $query, $matches);

                if (!empty($matches[1])) {
                    $out = $fields = array();

                    while ($row = $res->fetch_assoc()) {
                        array_push($out, $row);
                    }

                    // get row fields
                    $fields = array_keys($out[0]);

                    $GLOBALS['rlSmarty']->assign_by_ref('fields', $fields);
                    $GLOBALS['rlSmarty']->assign_by_ref('out', $out);

                    $tpl = 'blocks' . RL_DS . 'database_grid.tpl';
                    $_response->assign('grid', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
                }

                $notice = str_replace('{number}', '<b>' . $this->affectedRows() . '</b>', $lang['query_ran']);
                $_response->script("printMessage('notice', '" . $notice . "')");
            }
        }

        $this->dieIfError = true;
        $_response->script("$('#run_button').val('" . $lang['go'] . "')");

        return $_response;
    }

    /**
     * Get all pages keys=>paths
     *
     * @since 4.7.1 - Logic moved to \Flynax\Utils\Util::getPages method
     *
     * @return array - pages keys/paths
     */
    public function getAllPages()
    {
        return Util::getPages(array('Key', 'Path'), null, null, array('Key', 'Path'));
    }

    /**
     * delete account type
     *
     * @package ajax
     *
     * @param string $key - account type Key
     * @param string $reason - remove type reason message
     * @param int $replace_key - new account type key to replace with
     *
     **/
    public function ajaxDeleteAccountType($key = false, $reason = false, $replace_key = false)
    {
        global $_response, $lang, $config, $rlActions;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        if (is_array($key)) {
            $replace_key = $key[1];
            $key = $key[0];
        }

        $GLOBALS['rlValid']->sql($replace_key);
        $GLOBALS['rlValid']->sql($key);

        /* replace type id mode */
        if ($replace_key) {
            $update = array(
                'fields' => array(
                    'Type' => $replace_key,
                ),
                'where'  => array(
                    'Type' => $key,
                ),
            );
            $GLOBALS['rlActions']->updateOne($update, 'accounts');
        } else {
            /* check exist accounts used requested account type */
            $this->setTable('accounts');
            $accounts = $this->fetch(array('ID', 'Username', 'First_name', 'Last_name', 'Mail'), array('Type' => $key));
            $this->resetTable();

            /* delete accounts data */
            if ($accounts) {
                foreach ($accounts as $account) {
                    $this->ajaxDeleteAccount($account['ID'], $reason, true);
                }
            }
        }

        /* delete account type */
        $lang_keys[] = array(
            'Key' => 'account_types+name+' . $key,
        );
        $lang_keys[] = array(
            'Key' => 'account_types+desc+' . $key,
        );

        if (!$config['trash']) {
            // remove enum option from listing plans table
            $rlActions->enumRemove('listing_plans', 'Allow_for', $key);
        }

        /* delete related page */
        if (!$config['trash']) {
            $GLOBALS['rlAccountTypes']->removePageFromSystemBoxes($key);

            $sql = "DELETE `T1`, `T2` FROM `{db_prefix}pages` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('pages+name+', `T1`.`Key`) ";
            $sql .= "WHERE `T1`.`Key` = 'at_{$key}'";
            $this->query($sql);
        } else {
            $sql = "UPDATE `{db_prefix}pages` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('pages+name+', `T1`.`Key`) ";
            $sql .= "SET `T1`.`Status` = 'trash', `T2`.`Status` = 'trash' ";
            $sql .= "WHERE `T1`.`Key` = 'at_{$key}'";
            $this->query($sql);
        }

        /* delete acounts */
        $rlActions->delete(array('Key' => $key), array('account_types'), null, null, $key, $lang_keys);
        $del_mode = $rlActions->action;

        $_response->script("
            accountTypesGrid.reload();
            printMessage('notice', '{$lang['item_' . $del_mode]}');
            $('#delete_block').fadeOut();
        ");

        return $_response;
    }

    /**
     * account type pre deleting checking
     *
     * @package ajax
     *
     * @param string $key - account type Key
     *
     **/
    public function ajaxPreAccountTypeDelete($key = false)
    {
        global $_response, $config, $lang, $rlHook, $rlSmarty;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlValid']->sql($key);

        /* get account details */
        $account_type = $this->fetch(array('ID', 'Key'), array('Key' => $key), null, 1, 'account_types', 'row');
        $account_type['name'] = $lang['account_types+name+' . $account_type['Key']];
        $rlSmarty->assign_by_ref('account_type', $account_type);

        /* check account types count */
        $this->setTable('account_types');
        $available = $this->fetch(array('ID'), null, "WHERE `Key` <> 'visitor' AND `Status` <> 'trash'");

        if (count($available) <= 1) {
            $_response->script("
                $('#delete_block').stop().fadeOut();
                printMessage('alert', '{$lang['limit_account_types_remove']}');
            ");
            return $_response;
        }

        /* check exist accounts use requested account type */
        $this->setTable('accounts');
        $accounts = $this->fetch(array('ID', 'Username', 'First_name', 'Last_name', 'Mail'), array('Type' => $key), "AND `Status` <> 'trash'");

        $accounts_total = count($accounts);
        $delete_total_items = 0;

        $delete_details[] = array(
            'name'  => $lang['accounts'],
            'items' => $accounts_total,
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=accounts&amp;account_type=' . $key,
        );
        $delete_total_items += $accounts_total;

        /* check exist listings use requested account types */
        $sql = "SELECT COUNT(`T1`.`ID`) AS `Count` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$key}' AND `T1`.`Status` <> 'trash'";
        $listings = $this->getRow($sql);

        $listings_total = $listings['Count'];

        $delete_details[] = array(
            'name'  => $lang['listings'],
            'items' => $listings_total,
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;account_type=' . $key,
        );
        $delete_total_items += $listings_total;

        $rlHook->load('deleteAccountTypeDataCollection');

        $rlSmarty->assign_by_ref('delete_details', $delete_details);

        if ($delete_total_items) {
            $tpl = 'blocks' . RL_DS . 'delete_preparing_account_type.tpl';
            $_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("$('#delete_block').slideDown();");
        } else {
            $phrase = $config['trash'] ? $lang['trash_confirm'] : $lang['drop_confirm'];
            $_response->script("
                $('#delete_block').slideUp();
                rlConfirm('{$phrase}', 'xajax_deleteAccountType', '{$key}');
            ");
        }

        return $_response;
    }

    /**
     * update account fields kit
     *
     * @package ajax
     *
     * @param int $type_id - account type ID
     * @param int $account_id - account ID
     *
     **/
    public function ajaxUpdateAccountFields($type_id = false, $account_id = false)
    {
        global $_response, $account_info, $rlAccount, $rlActions, $rlCommon, $account_info, $rlSmarty, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $type_id = (int) $type_id;
        $account_id = (int) $account_id;

        /* update type */
        $new_type_key = $this->getOne('Key', "`ID` = '{$type_id}'", 'account_types');
        $update = array(
            'fields' => array(
                'Type' => $new_type_key,
            ),
            'where'  => array(
                'ID' => $account_id,
            ),
        );
        $rlActions->updateOne($update, 'accounts');

        /* update form */
        $account_fields = $rlAccount->getFields($type_id);
        $account_fields = $GLOBALS['rlLang']->replaceLangKeys($account_fields, 'account_fields', array('name', 'description'));
        $account_fields = $rlCommon->fieldValuesAdaptation($account_fields, 'account_fields');

        if (!empty($account_fields)) {
            foreach ($account_info as $i_index => $i_val) {
                $search_fields[$i_index] = $i_index;
            }

            foreach ($account_fields as $key => $value) {
                if ($account_info[$account_fields[$key]['Key']] != '') {
                    switch ($account_fields[$key]['Type']) {
                        case 'mixed':
                            $df_item = false;
                            $df_item = explode('|', $account_info[$account_fields[$key]['Key']]);

                            $account_fields[$key]['value'] = $df_item[0];
                            $account_fields[$key]['df'] = $df_item[1];
                            break;

                        case 'date':
                            if ($account_fields[$key]['Default'] == 'single') {
                                $account_fields[$key]['current'] = $account_info[$search_fields[$account_fields[$key]['Key']]];
                            } elseif ($account_fields[$key]['Default'] == 'multi') {
                                $account_fields[$key]['from'] = $account_info[$account_fields[$key]['Key']];
                                $account_fields[$key]['to'] = $account_info[$account_fields[$key]['Key'] . '_multi'];
                            }
                            break;

                        case 'price':
                            $price = false;
                            $price = explode('|', $account_info[$account_fields[$key]['Key']]);

                            $account_fields[$key]['value'] = $price[0];
                            $account_fields[$key]['currency'] = $price[1];
                            break;

                        case 'unit':
                            $unit = false;
                            $unit = explode('|', $account_info[$account_fields[$key]['Key']]);

                            $account_fields[$key]['value'] = $unit[0];
                            $account_fields[$key]['unit'] = $unit[1];
                            break;

                        case 'checkbox':
                            $ch_items = null;
                            $ch_items = explode(',', $account_info[$account_fields[$key]['Key']]);

                            $account_fields[$key]['current'] = $ch_items;
                            unset($ch_items);
                            break;

                        case 'accept':
                            unset($account_fields[$key]);
                            break;

                        default:
                            $account_fields[$key]['current'] = $account_info[$search_fields[$account_fields[$key]['Key']]];
                            break;
                    }
                }
            }

            $rlSmarty->assign_by_ref('fields', $account_fields);

            $tpl = 'blocks' . RL_DS . 'account_field.tpl';
            $_response->assign('additional_fields', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("$('#account_field_area').fadeIn(); $('#next1').slideUp();");

            foreach ($account_fields as $key => $value) {
                switch ($value['Type']) {
                    case 'date':
                        if ($value['Default'] == 'single') {
                            $_response->script("$('#date_{$value['Key']}').datepicker({
                                showOn         : 'both',
                                buttonImage    : '" . RL_TPL_BASE . "img/blank.gif',
                                buttonText     : '{$lang['dp_choose_date']}',
                                buttonImageOnly: true,
                                dateFormat     : 'yy-mm-dd',
                                changeMonth    : true,
                                changeYear     : true,
                                yearRange      : '-100:+30'
                            }).datepicker($.datepicker.regional['" . RL_LANG_CODE . "']);");
                        } else {
                            $_response->script("$('#date_{$value['Key']}_from').datepicker({
                                showOn         : 'both',
                                buttonImage    : '" . RL_TPL_BASE . "img/blank.gif',
                                buttonText     : '{$lang['dp_choose_date']}',
                                buttonImageOnly: true,
                                dateFormat     : 'yy-mm-dd',
                                changeMonth    : true,
                                changeYear     : true,
                                yearRange      : '-100:+30'
                            }).datepicker($.datepicker.regional['" . RL_LANG_CODE . "']);");

                            $_response->script("$('#date_{$value['Key']}_to').datepicker({
                                showOn         : 'both',
                                buttonImage    : '" . RL_TPL_BASE . "img/blank.gif',
                                buttonText     : '{$lang['dp_choose_date']}',
                                buttonImageOnly: true,
                                dateFormat     : 'yy-mm-dd',
                                changeMonth    : true,
                                changeYear     : true,
                                yearRange      : '-100:+30'
                            }).datepicker($.datepicker.regional['" . RL_LANG_CODE . "']);");
                        }
                        break;

                    case 'phone':
                        $_response->script('flynax.phoneField();');
                        break;
                }

            }

            $_response->script("
                $('.qtip').each(function(){
                    $(this).qtip({
                        content: $(this).attr('title'),
                        show: 'mouseover',
                        hide: 'mouseout',
                        position: {
                            corner: {
                                target: 'topRight',
                                tooltip: 'bottomLeft'
                            }
                        },
                        style: {
                            width: 150,
                            background: '#8e8e8e',
                            color: 'white',
                            border: {
                                width: 7,
                                radius: 5,
                                color: '#8e8e8e'
                            },
                            tip: 'bottomLeft'
                        }
                    });
                }).attr('title', '');
            ");

            $_response->script("flynax.slideTo('#account_field_area');");
            $_response->script("$('.eval').each(function(){
                eval($(this).html());
            });");
        } else {
            $_response->script("
                $('#account_field_area').fadeOut();
                $('#next1').slideDown();
                printMessage('alert', '{$lang['account_type_has_not_fields']}');
            ");
        }

        $_response->script("$('#type_change_loading').fadeOut('normal');");

        $GLOBALS['rlHook']->load('apPhpSubmitProfileEnd');

        return $_response;
    }

    /**
     * delete search form
     *
     * @package ajax
     *
     * @param string $key - search form KEY
     *
     **/
    public function ajaxDeleteSearchForm($key)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlValid']->sql($key);

        $info = $this->fetch(array('Readonly', 'ID'), array('Key' => $key), "AND `Status` <> 'trash'", 1, 'search_forms', 'row');

        if ($info) {
            $lang_keys[] = array(
                'Key' => 'search_forms+name+' . $key,
            );

            if (!$info['Readonly']) {
                $GLOBALS['rlActions']->delete(array('Key' => $key), array('search_forms'), "Readonly = '0'", 1, $key, $lang_keys);

                $del_mode = $GLOBALS['rlActions']->action;
                $_response->script("searchFormsGrid.reload();");

                // delete relations if delete
                if (!$GLOBALS['config']['trash']) {
                    $this->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$info['ID']}'");
                }

                $_response->script("printMessage('notice', '{$lang['form_' . $del_mode]}');");
            } else {
                $_response->script("printMessage('alert', '{$lang['form_readonly']}');");
            }
        } else {
            trigger_error("Can not delete search form, exist query resopnse is empty", E_WARNING);
            $GLOBALS['rlDebug']->logger("Can not delete search form, exist query resopnse is empty");
        }

        return $_response;
    }

    /**
     * get Flynax RSS blog feed
     *
     * @package ajax
     *
     **/
    public function ajaxGetFlynaxRss()
    {
        global $config, $lang, $rlSmarty, $reefless, $_response;

        $rl_news_feed = $reefless->getPageContent($config['flynax_news_feed']);

        $reefless->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = $config['flynax_news_number'];
        $GLOBALS['rlRss']->items[] = 'date';
        $GLOBALS['rlRss']->createParser($rl_news_feed);

        $rss_content = $GLOBALS['rlRss']->getRssContent();
        $rlSmarty->assign_by_ref('rss_content', $rss_content);
        $tpl = 'blocks' . RL_DS . 'flynaxNews.blocks.tpl';
        $_response->assign('flynax_news_container', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#flynax_news_container').fadeIn('normal')");

        return $_response;
    }

    /**
     * get Flynax RSS blog feed
     *
     * @package ajax
     *
     **/
    public function ajaxGetPluginsLog()
    {
        global $config, $lang, $rlSmarty, $reefless, $_response;

        if ($_SESSION['sessAdmin']['type'] != 'super' && !$_SESSION['sessAdmin']['rights']['plugins']) {
            $_response->script("
                $('#plugins_log_container').html('<div class=\"box-center purple_13\">{$lang['plugins_changelog_denied']}</div>').fadeIn();
            ");
            return $_response;
        }

        /*
         * get plugins log
         * YOU ARE NOT PERMITTED TO MODIFY THE CODE BELOW
         */
        @eval(base64_decode(RL_SETUP));
        $feed_url = $config['flynax_plugins_log_feed'] . '?domain=' . $license_domain . '&license=' . $license_number;
        $feed_url .= '&software=' . $config['rl_version'] . '&php=' . phpversion();
        $xml = $this->getPageContent($feed_url);
        /* END CODE */

        $reefless->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = 20;
        $GLOBALS['rlRss']->items = array('key', 'path', 'name', 'version', 'comment', 'paid', 'date', 'compatible');
        $GLOBALS['rlRss']->createParser($xml);
        $change_log_content = $GLOBALS['rlRss']->getRssContent();

        /* check current plugins conditions */
        $this->setTable('plugins');
        $tmp_plugins = $this->fetch(array('Key', 'Version'));
        $this->resetTable();

        if (!$change_log_content) {
            $fail_msg = strpos($xml, 'access_forbidden') ? $lang['flynax_connect_forbidden'] : $lang['flynax_connect_fail'];
            $_response->script("
                $('#plugins_log_container').html('<div class=\"box-center purple_13\">{$fail_msg}</div>').fadeIn();
            ");
            return $_response;
        }

        foreach ($tmp_plugins as $index => $plugin) {
            $plugins[$plugin['Key']] = $plugin['Version'];
        }
        unset($tmp_plugins);

        foreach ($change_log_content as $index => $item) {
            $change_log_content[$index]['current'] = $plugins[$item['key']];

            if ($plugins[$item['key']]) {
                $compare = version_compare($item['version'], $plugins[$item['key']]);
                $status = false;

                switch ($compare) {
                    case 0:
                        $status = 'current';
                        break;
                    case 1:
                        $status = 'update';
                        break;
                    case -1:
                        $status = $plugins[$item['key']] ? 'no' : 'install';
                        break;
                }

                $change_log_content[$index]['status'] = $status;
            } else {
                $change_log_content[$index]['status'] = 'install';
            }

            if ($item['compatible'] && version_compare($item['compatible'], $config['rl_version']) > 0) {
                $change_log_content[$index]['compatible'] = false;
            } else {
                $change_log_content[$index]['compatible'] = true;
            }
        }

        /* build DOM */
        $rlSmarty->assign_by_ref('change_log_content', $change_log_content);
        $tpl = 'blocks' . RL_DS . 'flynaxPluginsLog.blocks.tpl';
        $_response->assign('plugins_log_container', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#plugins_log_container').fadeIn('normal')");

        $_response->call('rlPluginRemoteInstall');

        return $_response;
    }

    /**
     * check plugin for update
     *
     * @param string $key - requested plugin key
     *
     * @package ajax
     *
     **/
    public function ajaxCheckForUpdate($key = false)
    {
        global $config, $lang, $rlSmarty, $reefless, $_response;

        if (!$key) {
            return $_response;
        }

        /**
         * Get plugin details
         * YOU ARE NOT PERMITTED TO MODIFY THE CODE BELOW
         */
        @eval(base64_decode(RL_SETUP));
        $feed_url = $config['flynax_plugins_browse_feed'] . '?key=' . $key;
        $feed_url .= '&domain=' . $license_domain . '&license=' . $license_number;
        $feed_url .= '&software=' . $config['rl_version'] . '&php=' . phpversion();
        $change_log = $reefless->getPageContent($feed_url);

        $reefless->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = 1;
        $GLOBALS['rlRss']->items = array('key', 'name', 'path', 'version', 'comment', 'date', 'compatible');
        $GLOBALS['rlRss']->createParser($change_log);
        $change_log_content = $GLOBALS['rlRss']->getRssContent();

        if (!$change_log_content) {
            $_response->script("printMessage('error', '{$lang['flynax_connect_fail']}');");
            return $_response;
        }

        /* get requested plugin details */
        $plugin_info = $this->fetch(array('Name', 'Version'), array('Key' => $key), null, 1, 'plugins', 'row');
        $update_available = false;

        $log = $change_log_content[1];

        if (version_compare($log['version'], $plugin_info['Version']) > 0
            && $log['key'] == $key
            && version_compare($log['compatible'], $config['rl_version']) <= 0
        ) {
            $update_available = true;
            $update_version = $log['version'];
        }

        if ($update_available) {
            $link = '<a target="_blank" href="https://www.flynax.com/plugins/' . $log['path'] . '.html#changelog">' . $plugin_info['Name'] . '</a>';

            $_response->script("
                $('#browse_area').slideUp('fast');
                $('#update_area').slideDown();
                $('#update_info').fadeIn();
                flynax.slideTo('#bc_container');
                $('#update_version').html('{$update_version}');
                $('#update_link').html('{$link}');
                $('#plugin_name').html('{$plugin_info['Name']}');
                update_plugin_key = '{$key}';
            ");
        } else {
            if (version_compare($log['compatible'], $config['rl_version']) > 0) {
                $message = str_replace(
                    array('{plugin}', '{plugin_version}', '{software_version}'),
                    array($plugin_info['Name'], $log['version'], $log['compatible']),
                    $lang['plugin_have_not_compatible_update']
                );
            } else {
                $message = str_replace('{plugin}', $plugin_info['Name'], $lang['plugin_update_not_found']);
            }

            $_response->script("printMessage('alert', '{$message}');");
        }

        return $_response;
    }

    /**
     * save configs
     *
     * @package ajax
     *
     **/
    public function ajaxSaveConfig($data = false)
    {
        global $_response, $rlActions, $lang, $config;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        foreach ($data as $item) {
            if ($item['key'] == 'lang') {
                if ($item['value'] != RL_LANG_CODE) {
                    $redirect = "language=" . $item['value'];
                }
            } elseif ($item['key'] == 'flynax_news_number') {
                $update = array(
                    'fields' => array(
                        'Default' => $item['value'],
                    ),
                    'where'  => array(
                        'Key' => $item['key'],
                    ),
                );
                $rlActions->updateOne($update, 'config');

                if ($item['value'] != $config[$item['key']]) {
                    $_response->script("
                        if ( $('.block div[lang=flynax_news]').is(':visible') )
                        {
                            xajax_getFlynaxRss();
                        }
                    ");
                }
            } else {
                if (!$item['deny']) {
                    $update = array(
                        'fields' => array(
                            'Default' => $item['value'],
                        ),
                        'where'  => array(
                            'Key' => $item['key'],
                        ),
                    );
                    $rlActions->updateOne($update, 'config');
                }
            }
        }

        $_response->script('$("#save_settings").val("' . $lang['save'] . '")');

        if ($redirect) {
            $_response->redirect(RL_URL_HOME . ADMIN . '/index.php?' . $redirect);
        }

        return $_response;
    }

    /**
     * fetch adapt and admin panel blocks
     *
     * @todo assign blocks array in smarty
     *
     **/
    public function assignBlocks()
    {
        global $rlDb, $rlSmarty;

        $rlDb->setTable('admin_blocks');
        $blocks = $rlDb->fetch(array('Column', 'Key', 'Ajax', 'Content', 'Fixed', 'Position', 'Status'), array('Status' => 'active'), "ORDER BY `Position`");
        $rlDb->resetTable();
        $blocks = $GLOBALS['rlLang']->replaceLangKeys($blocks, 'admin_blocks', array('name'), RL_LANG_CODE, 'admin');

        $cookie_blocks_status_tmp = explode(',', $_COOKIE['ap_blocks_status']);

        foreach ($cookie_blocks_status_tmp as $item) {
            $tmp_item = explode('|', $item);
            $cookie_blocks_status[$tmp_item[0]] = $tmp_item[1];
        }
        unset($cookie_blocks_status_tmp);

        $cookie_blocks_fixed_tmp = explode(',', $_COOKIE['ap_blocks_fixed']);
        foreach ($cookie_blocks_fixed_tmp as $item) {
            $tmp_item = explode('|', $item);
            $cookie_blocks_fixed[$tmp_item[0]] = $tmp_item[1];
        }
        unset($cookie_blocks_fixed_tmp);

        $rlSmarty->assign_by_ref('blocks', $blocks);

        foreach ($_COOKIE as $cIndex => $cValue) {
            if (false !== strpos($cIndex, 'ap_arrangement')) {
                $column = str_replace('ap_arrangement_', '', $cIndex);
                $cItems = explode(',', $cValue);
                foreach ($cItems as $cItem) {
                    $cItemExp = explode('|', $cItem);
                    $cookie_items[$cItemExp[0]] = $column;
                }
            }
        }

        foreach ($blocks as $key => $value) {
            $blocks[$key]['Status'] = isset($cookie_blocks_status[$value['Key']]) ? ($cookie_blocks_status[$value['Key']] == 'true' ? 'active' : 'approval') : $blocks[$key]['Status'];
            $blocks[$key]['Fixed'] = isset($cookie_blocks_fixed[$value['Key']]) ? ($cookie_blocks_fixed[$value['Key']] == 'true' ? 1 : 0) : $blocks[$key]['Fixed'];
            if ($cookie_items[$value['Key']]) {
                $ap_blocks[$cookie_items[$value['Key']]][] = $blocks[$key];
            } else {
                $ap_blocks[$value['Column']][] = $blocks[$key];
            }
        }

        // check access to Statistics box for admin with limited access
        if ($_SESSION['sessAdmin']['type'] == 'limited') {
            $statistics_content = array('listings', 'custom_categories', 'all_accounts', 'contacts');
            $allow_statistics = false;

            foreach ($_SESSION['sessAdmin']['rights'] as $key => $value) {
                if (in_array($key, $statistics_content)) {
                    $allow_statistics = true;

                    break;
                }
            }

            // remove box from Dashboard if access is denied
            if (!$allow_statistics) {
                foreach ($ap_blocks as $key => $value) {
                    if ($value[0]['Key'] == 'statistics') {
                        unset($ap_blocks[$key]);

                        break;
                    }
                }
            }
        }

        foreach ($ap_blocks as $key => $value) {
            if ($_COOKIE['ap_arrangement_' . $key]) {
                $cookie_column_tmp = explode(',', $_COOKIE['ap_arrangement_' . $key]);

                foreach ($cookie_column_tmp as $vk => $vi) {
                    $it = explode('|', $vi);
                    $cookie_column[$it[0]] = $it[1];
                }

                $new_value = false;
                foreach ($value as $bk => $bv) {
                    $position = isset($cookie_column[$bv['Key']]) ? $cookie_column[$bv['Key']] : $bv['Position'];
                    $new_value[$position] = $bv;
                    $ap_blocks[$key] = $new_value;
                }
                ksort($ap_blocks[$key]);
            }
        }

        $rlSmarty->assign_by_ref('ap_blocks', $ap_blocks);
    }

    /**
     * get content statistics
     *
     * @todo get and assign content statistic to SMARTY
     **/
    public function getStatistics()
    {
        global $lang, $config, $content_stat, $rlSmarty;

        /* get listings */
        $listing_statuses = array('total', 'new', 'active', 'pending', 'incomplete', 'expired');
        $sql = "SELECT ";
        foreach ($listing_statuses as $l_status) {
            // exclude empty listings from result list
            $empty_listings = "AND (`T1`.`Main_photo` OR `T1`.`Last_step` = 'checkout' OR `T1`.`Last_step` = 'photo')";
            $empty_listings .= ", 1, 0)) AS `{$l_status}`, ";

            switch ($l_status) {
                case 'total':
                    $sql .= "SUM(IF(`T1`.`Status` <> 'trash' AND `T1`.`Account_ID` > 0 " . $empty_listings;
                    break;

                case 'active':
                    $sql .= "SUM(IF(`T1`.`Status` = 'active' AND `T1`.`Account_ID` > 0, 1, 0)) AS `{$l_status}`, ";
                    break;

                case 'new':
                    $sql .= "SUM( IF(( UNIX_TIMESTAMP(DATE_ADD(`T1`.`Pay_date`, INTERVAL `T2`.`Listing_period` DAY)) > UNIX_TIMESTAMP(NOW()) OR `T2`.`Listing_period` = 0 ) AND UNIX_TIMESTAMP(`T1`.`Pay_date`) BETWEEN UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$config['new_period']} DAY)) AND UNIX_TIMESTAMP(NOW()), 1, 0) ) AS `{$l_status}`, ";
                    break;

                case 'incomplete':
                    $sql .= "SUM(IF(`T1`.`Status` = '{$l_status}' AND `T1`.`Account_ID` > 0 " . $empty_listings;
                    break;

                default:
                    $sql .= "SUM( IF(`T1`.`Status` = '{$l_status}' AND `T1`.`Account_ID` > 0, 1, 0) ) AS `{$l_status}`, ";
                    break;
            }
        }
        $sql = rtrim($sql, ', ');

        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_plans` AS `T2` ON `T1`.`Plan_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T4` ON `T1`.`Account_ID` = `T4`.`ID` ";

        $listings = $this->getRow($sql);
        $listings_total = $listings['total'];
        unset($listings['total']);

        //$listings['approval'] = $listings_total - $listings['active'];

        $content_stat['listings'] = array(
            'name'  => $lang['listings'],
            'total' => $listings_total,
            'items' => $listings,
        );
        unset($listings, $sql, $listing_statuses, $listings_total);

        /* get accounts */
        $account_statuses = array('new', 'active', 'pending', 'incomplete');
        $sql = "SELECT ";
        foreach ($account_statuses as $a_status) {
            switch ($a_status) {
                case 'new':
                    $sql .= "SUM( IF(UNIX_TIMESTAMP(`T1`.`Date`) BETWEEN UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$config['new_period']} DAY)) AND UNIX_TIMESTAMP(NOW()), 1, 0) ) AS `{$a_status}`, ";
                    break;

                default:
                    $sql .= "SUM( IF(`T1`.`Status` = '{$a_status}', 1, 0) ) AS `{$a_status}`, ";
                    break;
            }
        }
        $sql = rtrim($sql, ', ');

        $sql .= "FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
        $sql .= "WHERE `T2`.`Status` = 'active' ";

        $accounts = $this->getRow($sql);

        $content_stat['accounts'] = array(
            'name'  => $lang['accounts'],
            'total' => array_sum($accounts) - $accounts['new'],
            'items' => $accounts,
        );
        unset($accounts, $sql, $account_statuses);

        /* custom categories */
        $sql = "SELECT COUNT('ID') AS `Count` FROM `{db_prefix}tmp_categories` WHERE `Status` = 'approval'";

        $custom_categories = $this->getRow($sql);

        $content_stat['categories'] = array(
            'name' => $lang['admin_controllers+name+custom_categories'],
            'new'  => $custom_categories['Count'],
        );
        unset($custom_categories, $sql);

        /* contacts */
        $sql = "SELECT COUNT('ID') AS `Count`, SUM(IF(`Status` = 'new', 1, 0)) AS `New` FROM `{db_prefix}contacts` WHERE `Status` != 'trash'";

        $contacts = $this->getRow($sql);

        $content_stat['contacts'] = array(
            'name'  => $lang['contacts'],
            'total' => $contacts['Count'],
            'new'   => $contacts['New'],
        );
        unset($contacts, $sql);

        $rlSmarty->assign_by_ref('content_stat', $content_stat);
    }

    /**
     * add new amenity
     *
     * @param array $names = amenity names
     *
     * @package ajax
     *
     **/
    public function ajaxAddAmenity($names = false)
    {
        global $_response, $rlActions, $lang, $config, $rlValid;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        /* load the utf8 lib */
        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        /* check names */
        $languages = $GLOBALS['languages'];
        $key = '';
        foreach ($languages as $language) {
            if (empty($names[$language['Code']])) {
                $errors[] = str_replace('{field}', "'<b>{$lang['value']} ({$language['name']})</b>'", $lang['notice_field_empty']);
            } else {
                if (!$key) {
                    $key = $names[$language['Code']];
                    if (!utf8_is_ascii($key)) {
                        $key = utf8_to_ascii($key);
                    }

                    $key = $rlValid->str2key($key, '-');
                    $key = $rlValid->uniqueKey($key, 'map_amenities');
                }

                $lang_keys[] = array(
                    'Code'   => $language['Code'],
                    'Module' => 'common',
                    'Key'    => 'map_amenities+name+' . $key,
                    'Value'  => $names[$language['Code']],
                );
            }
        }

        if ($errors) {
            $out = '<ul>';
            /* print errors */
            foreach ($errors as $error) {
                $out .= '<li>' . $error . '</li>';
            }
            $out .= '</ul>';
            $_response->script("printMessage('error', '{$out}');");
        } else {
            $position = $this->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}map_amenities`");

            $insert = array(
                'Key'      => $key,
                'Position' => $position['max'] + 1,
            );

            /* insert new item */
            if ($rlActions->insertOne($insert, 'map_amenities')) {
                $rlActions->insert($lang_keys, 'lang_keys');

                $_response->script("printMessage('notice', '{$lang['item_added']}')");

                $_response->script("mapAmenitiesGrid.reload();");
                $_response->script("$('#new_item').slideUp('normal')");
            }
        }

        $_response->script("
            $('input[name=add_item_submit]').val('{$lang['add']}');
            $('#new_item input[type=text]').val('');
        ");

        return $_response;
    }

    /**
     * edit amenity names by key
     *
     * @param string $key = amenity key
     * @param array $names = amenity names
     *
     * @package ajax
     *
     **/
    public function ajaxEditAmenity($key = false, $names = false)
    {
        global $_response, $rlActions, $lang, $config, $rlValid;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key || !$names) {
            return $_response;
        }

        /* check names */
        foreach ($GLOBALS['languages'] as $language) {
            if (empty($names[$language['Code']])) {
                $errors[] = str_replace('{field}', "'<b>{$lang['value']} ({$language['name']})</b>'", $lang['notice_field_empty']);
            } else {
                if ($this->getOne('ID', "`Key` = 'map_amenities+name+{$key}' AND `Code` = '{$language['Code']}' AND 1", 'lang_keys')) {
                    $lang_keys_update[] = array(
                        'fields' => array(
                            'Value' => $names[$language['Code']],
                        ),
                        'where'  => array(
                            'Code' => $language['Code'],
                            'Key'  => 'map_amenities+name+' . $key,
                        ),
                    );
                } else {
                    $lang_keys_insert[] = array(
                        'Code'   => $language['Code'],
                        'Module' => 'common',
                        'Key'    => 'map_amenities+name+' . $key,
                        'Value'  => $names[$language['Code']],
                    );
                }
            }
        }

        if ($errors) {
            $out = '<ul>';
            /* print errors */
            foreach ($errors as $error) {
                $out .= '<li>' . $error . '</li>';
            }
            $out .= '</ul>';
            $_response->script("printMessage('error', '{$out}');");
        } else {
            if ($lang_keys_update) {
                $rlActions->update($lang_keys_update, 'lang_keys');
            }
            if ($lang_keys_insert) {
                $rlActions->insert($lang_keys_insert, 'lang_keys');
            }

            $_response->script("printMessage('notice', '{$lang['item_edited']}')");

            $_response->script("mapAmenitiesGrid.reload();");
            $_response->script("$('#new_item').slideUp('normal')");
        }

        $_response->script("
            $('input[name=edit_item_submit]').val('{$lang['edit']}');
            $('#edit_item').slideUp();
            $('#edit_item input[type=text]').val('');
        ");

        return $_response;
    }

    /**
     * delete amenity
     *
     * @param string $key - amenity key
     *
     * @package ajax
     *
     **/
    public function ajaxDeleteAmenity($key = false)
    {
        global $_response, $rlActions, $lang, $config, $rlValid;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        $rlValid->sql($key);
        $this->query("DELETE FROM `{db_prefix}map_amenities` WHERE `Key` = '{$key}' LIMIT 1");
        $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'map_amenities+name+{$key}'");
        $_response->script("mapAmenitiesGrid.reload();");

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");

        return $_response;
    }

    /**
     * check new admin messqages
     *
     **/
    public function checkNewMessages()
    {
        global $rlSmarty;

        $id = (int) $_SESSION['sessAdmin']['user_id'];

        if (!$id) {
            return false;
        }

        $sql = "SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` ";
        $sql .= "WHERE `Admin` = {$id} AND `Status` = 'new' AND `To` = 0";
        $count = $this->getRow($sql);

        $rlSmarty->assign_by_ref('new_messages', $count['Count']);
    }

    /**
     * delete saved search
     *
     * @package ajax
     *
     * @param string $id - saved search ID
     *
     **/
    public function ajaxDeleteSavedSearch($id = false)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);

            return $_response;
        }

        $id = (int) $id;
        $GLOBALS['rlActions']->delete(array('ID' => $id), array('saved_search'));
        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            savedSearchesGrid.reload();
            printMessage('notice', '{$lang['item_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * make search by saved search criteria
     *
     * @package ajax
     *
     * @param string $id - saved search ID
     *
     **/
    public function ajaxCheckSavedSearch($id = false)
    {
        global $_response, $lang, $config, $rlListingTypes, $pages, $rlSmarty, $rlActions;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);

            return $_response;
        }

        $id = (int) $id;

        /* get saved search details */
        $saved_search = $this->fetch('*', array('ID' => $id), null, 1, 'saved_search', 'row');
        $saved_search['Content'] = unserialize($saved_search['Content']);

        $checked_listings = $saved_search['Matches'];
        $exploded_matches = explode(',', $checked_listings);

        $this->loadClass('Search');
        $this->loadClass('Mail');

        /* load fields */
        $GLOBALS['rlSearch']->getFields($saved_search['Form_key'], $saved_search['Listing_type']);

        $GLOBALS['rlSearch']->exclude = $saved_search['Matches'];
        $matches = $GLOBALS['rlSearch']->search($saved_search['Content'], $saved_search['Listing_type'], 0, 20);
        $GLOBALS['rlSearch']->exclude = false;

        if ($matches) {
            foreach ($matches as $listing) {
                if (!in_array($listing['ID'], $exploded_matches)) {
                    $checked_listings .= empty($checked_listings) ? $listing['ID'] : ',' . $listing['ID'];

                    $page_path = $pages[$rlListingTypes->types[$listing['Listing_type']]['Page_key']];

                    $link = $this->url('listing', $listing);
                    $links .= '<a href="' . $link . '">' . $link . '</a><br />';

                    $counter += 1;
                }
            }

            /* send notification */
            if ($counter) {
                /* get profile details */
                $this->loadClass('Account');
                $profile_data = $GLOBALS['rlAccount']->getProfile((int) $saved_search['Account_ID']);

                $email_tpl = $GLOBALS['rlMail']->getEmailTemplate('cron_saved_search_match', $profile_data['Lang']);
                $email_tpl['body'] = str_replace(array('{name}', '{count}', '{links}'), array($profile_data['Full_name'], $counter, $links), $email_tpl['body']);

                $GLOBALS['rlMail']->send($email_tpl, $profile_data['Mail']);

                $message = str_replace(array('{count}', '{name}'), array($counter, $profile_data['Full_name']), $lang['saved_search_search_results']);
                $_response->script("printMessage('notice', '{$message}')");
            } else {
                $_response->script("printMessage('alert', '{$lang['saved_search_no_listings_found']}')");
            }

            /* update entry */
            $update = array(
                'fields' => array(
                    'Date'    => 'NOW()',
                    'Cron'    => 1,
                    'Matches' => $checked_listings,
                ),
                'where'  => array(
                    'ID' => $id,
                ),
            );
            $rlActions->updateOne($update, 'saved_search');
        } else {
            $_response->script("printMessage('alert', '{$lang['saved_search_no_listings_found']}')");
        }

        return $_response;
    }

    /**
     * Prepare notifications for admin panel
     **/
    public function apNotifications()
    {
        global $rlSmarty, $rlHook, $lang, $config;

        $notifications = array();

        // Php version checking
        preg_match('/[0-9.]+/', phpversion(), $php_version);
        if (version_compare($php_version[0], $config['minimal_required_php_version']) < 0) {
            $notifications[] = str_replace('{min_version}', $config['minimal_required_php_version'], $lang['notice_php_need_update']);
        }

        // wrong cron configuration notice
        if (!$config['cron_last_run']) {
            $notifications[] = $lang['cron_not_configured'];
        } else if (time() - $config['cron_last_run'] > 604800) {
            $date_format = str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT);
            $formatted_date = gmdate($date_format, $config['cron_last_run']);

            $notifications[] = str_replace('{date}', $formatted_date, $lang['cron_broken']);
        }

        // google browser/server api key is missing
        if (($config['static_map_provider'] == 'google' && !$config['google_map_key'])
            || ($config['geocoding_provider'] == 'google' && !$config['google_server_map_key'])
        ) {
            $notifications[] = $lang['google_map_keys_missing'];
        }

        // show notice about limit of Google Maps Geocoding requests
        if ($config['geocode_request_limit_reached']) {
            $notifications[] = $lang['geocode_request_limit_reached_notice'];
        }

        $rlHook->load('apNotifications', $notifications); // >= 4.5

        $rlSmarty->assign('notifications', $notifications);
    }

    /**
     * Update something when admin change default language in the system
     *
     * @since 4.8.0
     *
     * @param $oldLang
     * @param $newLang
     */
    public function changeDefaultLanguageHandler($oldLang, $newLang)
    {
        global $rlDb, $config, $rlHook;

        $oldLang = Valid::escape($oldLang);
        $newLang = Valid::escape($newLang);

        if (!$oldLang || !$newLang) {
            return;
        }

        $rlHook->load('apPhpBeforeChangeDefaultLanguage', $oldLang, $newLang);

        if ($config['multilingual_paths']) {
            // Rename the system columns when admin change the default language
            $rlDb->query("ALTER TABLE `{db_prefix}categories` CHANGE `Path` `Path_{$oldLang}` VARCHAR(255) NOT NULL");
            $rlDb->query("ALTER TABLE `{db_prefix}categories` CHANGE `Path_{$newLang}` `Path` VARCHAR(255) NOT NULL");

            $rlDb->query("ALTER TABLE `{db_prefix}pages` CHANGE `Path` `Path_{$oldLang}` VARCHAR(255) NOT NULL");
            $rlDb->query("ALTER TABLE `{db_prefix}pages` CHANGE `Path_{$newLang}` `Path` VARCHAR(255) NOT NULL");

            // Copy values from old default language to new
            $rlDb->query("UPDATE `{db_prefix}pages` SET `Path` = `Path_{$oldLang}` WHERE `Path` = ''");
            $rlDb->query("UPDATE `{db_prefix}categories` SET `Path` = `Path_{$oldLang}` WHERE `Path` = ''");

            $GLOBALS['languages'] = $GLOBALS['rlLang']->getLanguagesList('all');

            $config['tmp_lang'] = $config['lang'];
            $config['lang']     = $newLang;

            $GLOBALS['rlCache']->updateCategories();

            $config['lang'] = $config['tmp_lang'];
            unset($config['tmp_lang']);
        }

        $rlHook->load('apPhpAfterChangeDefaultLanguage', $oldLang, $newLang);
    }
}
