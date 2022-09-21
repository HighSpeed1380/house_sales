<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLMULTIFIELD.CLASS.PHP
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

class rlMultiField extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    public $formatKey = false;
    public $formatID = false;
    public $formatKeys = [];
    public $geoFormatData = null;
    public $formatLangTable = false;

    /**
     * Class constructor
     *
     * @since 2.2.0
     */
    public function __construct()
    {
        global $config;

        if ($config['mf_format_keys']) {
            $this->formatKeys = explode('|', $config['mf_format_keys']);
        }

        $this->geoFormatData = json_decode($config['mf_geo_data_format'], true);
    }

    /**
     * @hook ajaxRequest
     * @since 2.0.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        if ($request_mode == 'mfNext') {
            $order_type = null;

            // Get format order
            foreach ($this->formatKeys as $format_key) {
                if (strpos($request_item, $format_key) === 0) {
                    if ($format_key == $this->geoFormatData['Key']) {
                        $order_type = $this->geoFormatData['Order_type'];
                    } else {
                        $order_type = $GLOBALS['rlDb']->getOne('Order_type', "`Key` = '{$format_key}'", 'data_formats');
                    }

                    break;
                }
            }

            $data = $this->getData($request_item, false, $order_type);

            $out = array();
            $out['data'] = $data;
            $out['status'] = 'ok';
        }
    }

    /**
     * @deprecated 2.0.0 - Moved to rlGeoFilter
     **/
    public function geoAutocomplete($str = false, $lang = false) {}

    /**
     * @hook tplHeader
     * @since 2.0.0
     */
    public function hookTplHeader()
    {
        if ($this->isPageMf()) {
            echo "<script>lang['any'] = '" . $GLOBALS['lang']['any'] . "';</script>";
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "multiField" . RL_DS . "tplHeader.tpl");
    }

    /**
     * @hook tplFooter
     * @since 2.0.0
     */
    public function hookTplFooter()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . "multiField" . RL_DS . "tplFooter.tpl");
    }

    /**
     * @hook staticDataRegister
     * @since 2.0.0
     */
    public function hookStaticDataRegister()
    {
        if (!$this->isPageMF()) {
            return false;
        }

        global $rlStatic;
        $rlStatic->addJS(RL_PLUGINS_URL . 'multiField/static/lib.js');
    }

    /**
     * @hook pageinfoArea
     * @since 2.0.0
     */
    public function hookPageinfoArea()
    {
        global $page_info, $multi_formats;

        if ($this->isPageMf()) {
            $GLOBALS['rlSmarty']->assign('multi_format_keys', $this->formatKeys);
            $GLOBALS['rlSmarty']->assign('mf_form_prefix', $this->getPostPrefixByPage());
        }
    }

    /**
     * getPostPrefixByPage - return field inputs wrapper prefix f,account
     * @param string $page_controller
     * @since 2.0.0
     */
    private function getPostPrefixByPage($page_controller = null)
    {
        $page_controller = $page_controller ?: $GLOBALS['page_info']['Controller'];

        if (in_array($page_controller,
            array('add_listing', 'edit_listing', 'home', 'listing_type', 'search', 'listings_by_field',
                'compare_listings', 'recently_added', 'my_listings', 'account_type'))) {
            return 'f';
        }

        if (in_array($page_controller, array('registration', 'profile'))) {
            return 'account';
        }

        if (in_array($page_controller, array('search_map'))) {
            return '';
        }
    }

    /**
     * isPageMf - defines if there can be multiField stack on a page
     * @param string $page_controller
     * @since 2.0.0
     */
    private function isPageMf($page_controller = false)
    {
        $page_controller = $page_controller ?: $GLOBALS['page_info']['Controller'];

        if (in_array($page_controller,
            array('add_listing', 'edit_listing', 'home', 'listing_type', 'search', 'listings_by_field',
                'compare_listings', 'recently_added', 'my_listings', 'search_map')
        )
            || in_array($page_controller, array('registration', 'profile', 'account_type'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * getMultiFormats
     * @since 2.0.0
     */
    private function getMultiFormats()
    {
        $sql = "SELECT `T1`.*, `T2`.`Order_type` FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "JOIN `{db_prefix}data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`Parent_ID` = 0";

        $multi_formats = $GLOBALS['rlDb']->getAll($sql, 'Key');

        return $multi_formats;
    }

    /**
     * @hook tplListingFieldSelect
     * @since 2.0.0
     */
    public function hookTplListingFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield.tpl');
        $this->getMissingFormatItemPhrases();
    }

    /**
     * @hook tplSearchFieldSelect
     * @since 2.0.0
     */
    public function hookTplSearchFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield.tpl');
        $this->getMissingFormatItemPhrases();
    }

    /**
     * @hook tplRegFieldSelect
     * @since 2.0.0
     */
    public function hookTplRegFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield_account.tpl');
    }

    /**
     * @hook tplProfileFieldSelect
     * @since 2.0.0
     */
    public function hookTplProfileFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield_account.tpl');
    }

    /**
     * @hook adaptValueBottom
     * @since 2.0.0
     */
    public function hookAdaptValueBottom(&$value, &$field, &$out, &$lTypeKey)
    {
        global $lang, $rlDb;

        $this->defineLocale();

        if (!$this->formatLangTable) {
            return;
        }

        $phrase_key = 'data_formats+name+' . $value;

        if (!$lang[$phrase_key] && in_array($field['Condition'], $this->formatKeys)) {
            $out = $rlDb->getOne('Value', "`Key` = '{$value}'", $this->formatLangTable);

            if ($out) {
                $lang[$phrase_key] = $out;
            }
        }
    }

    /**
     * Get format data for geo format field
     *
     * @hook phpCommonFieldValuesAdaptationTop
     * @since 2.2.0
     */
    public function hookPhpCommonFieldValuesAdaptationTop(&$fields, &$table, &$listing_type)
    {
        foreach ($fields as &$field) {
            if ($field['Condition']
                && !is_array($field['Values'])
                && in_array($field['Condition'], $this->formatKeys)
                && strpos($field['Key'], '_level') === false
            ) {
                if ($field['Condition'] == $this->geoFormatData['Key']) {
                    $format_id = $this->geoFormatData['ID'];
                    $order_type = $this->geoFormatData['Order_type'];
                } else {
                    $format_id = $GLOBALS['rlDb']->getOne('ID', "`Key` = '{$field['Condition']}' AND `Parent_ID` = 0", 'multi_formats');
                    $order_type = $GLOBALS['rlDb']->getOne('Order_type', "`Key` = '{$field['Condition']}' AND `Parent_ID` = 0", 'data_formats');
                }

                $field['Values'] = $this->getData(intval($format_id), false, $order_type);

                // Adapt names
                foreach ($field['Values'] as &$item) {
                    $phrase_key = 'data_formats+name+' . $item['Key'];
                    $GLOBALS['lang'][$phrase_key] = $item['name'];
                    $item['pName'] = $phrase_key;
                    unset($item['name']);
                }

                // Clear condition and ID to avoid data overriding in hook owner method
                $field['Condition_bkp']  = $field['Condition'];
                $field['Condition']      = '';
                $field['ID_bkp']         = $field['ID'];
                $field['ID']             = -20;
                $field['mf_adapted']     = true;
            }
        }
    }

    /**
     * Revert geo format field settings
     *
     * @hook phpCommonFieldValuesAdaptationBottom
     * @since 2.2.0
     */
    public function hookPhpCommonFieldValuesAdaptationBottom(&$fields, &$table, &$listing_type)
    {
        foreach ($fields as &$field) {
            if ($field['mf_adapted']) {
                // Revert condition and ID
                $field['Condition']  = $field['Condition_bkp'];
                $field['ID']         = $field['ID_bkp'];

                unset($field['mf_adapted'], $field['Condition_bkp'], $field['ID_bkp']);
            }
        }
    }

    /**
     * Get data by parent key or ID
     *
     * @since 2.3.0 - Added $getAllPaths parameter
     * @since 2.0.0 - $order_type parameter added
     *
     * @param  int|string $parent     - Parent ID or Key
     * @param  bool       $get_path   - Include path data
     * @param  string     $order_type - Order type, 'alphabetic' or 'position'
     * @param  bool       $getAllPaths
     * @return array                  - Data array
     */
    public function getData($parent, $get_path = false, $order_type = null, $getAllPaths = false)
    {
        global $rlDb, $config, $languages;

        $parent_id = is_int($parent)
        ? $parent
        : $rlDb->getOne("ID", "`Key` = '{$parent}'", "multi_formats");

        $request_lang = defined('RL_LANG_CODE') ? RL_LANG_CODE : $_GET['lang'];

        if (!$parent_id) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Key`, `T1`.`Default`, `T2`.`Value` as `name`, `T1`.`Parent_IDs` ";

        if ($get_path) {
            if ($config['mf_multilingual_path']) {
                $requested_path_field = 'Path_' . $request_lang;
                $system_path_field    = 'Path_' . $config['lang'];

                $sql .= ", IF(`T1`.`{$requested_path_field}` != '', `T1`.`{$requested_path_field}`, `T1`.`{$system_path_field}`) AS `Path` ";

                if ($getAllPaths) {
                    if (!$languages) {
                        $languages = $GLOBALS['rlLang']->getLanguagesList();
                    }

                    foreach ($languages as $langKey => $langData) {
                        $sql .= ', `T1`.`Path_' . $langKey . '` ';
                    }
                }
            } else {
                $sql .= ", `Path` ";
            }
        }

        $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_{$request_lang}` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`Parent_ID` = {$parent_id} ";
        $sql .= "AND `T1`.`Status` = 'active' ";

        if ($order_type == 'alphabetic') {
            $sql .= "ORDER BY `T2`.`Value`";
        } elseif ($order_type == 'position') {
            $sql .= "ORDER BY `T1`.`Position`";
        }

        return $rlDb->getAll($sql);
    }

    /**
     * DEPRECATED: USE getData instead
     *
     * @param data mixed - data request (key,path,id)
     * @param get_path   - bool is path necessary or not
     * @param outputMap  - format of array
     */
    public function getMDF($data = false, $get_path = false, $outputMap = false)
    {}

    /**
     * Create multiformat language data tables
     *
     * @since 2.2.0
     */
    private function createLangTables()
    {
        global $rlDb;

        $languages = $GLOBALS['languages'] ?: $GLOBALS['rlLang']->getLanguagesList('all');

        foreach ($languages as $language) {
            $rlDb->createTable(
                'multi_formats_lang_' . $language['Code'],
                "`Key` varchar(100) NOT NULL,
                `Value` varchar(32) NOT NULL,
                KEY `Key` (`Key`),
                KEY `Value` (`Value`)",
                RL_DBPREFIX,
                'ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;'
            );
        }
    }

    /**
     * install
     * @since 2.0.0
     */
    public function install()
    {
        global $rlDb;

        $rlDb->createTable(
            'multi_formats',
            "`ID` int(11) NOT NULL AUTO_INCREMENT,
            `Parent_ID` int(11) NOT NULL,
            `Parent_IDs` varchar(47) NOT NULL,
            `Position` int(5) NOT NULL DEFAULT '0',
            `Levels` int(11) DEFAULT '0',
            `Key` varchar(100) NOT NULL DEFAULT '',
            `Default` enum('0','1') NOT NULL DEFAULT '0',
            `Geo_filter` enum('0','1') DEFAULT '0',
            `Status` enum('active','approval') NOT NULL DEFAULT 'active',
            `Path` varchar(255) NOT NULL DEFAULT '',
            `Latitude` double NOT NULL,
            `Longitude` double NOT NULL,
            PRIMARY KEY  (`ID`),
            KEY `Parent_ID` (`Parent_ID`),
            KEY `Status` (`Status`),
            KEY `Key` (`Key`),
            KEY `Path` (`Path`),
            KEY `Group_index` (`Parent_ID`,`Key`,`Status`)",
            RL_DBPREFIX,
            'ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci;'
        );

        $this->createLangTables();

        $sql  = "SELECT GROUP_CONCAT(`ID`) as `ids` ";
        $sql .= "FROM `{db_prefix}pages` ";
        $sql .= "WHERE FIND_IN_SET(`Controller`, 'home,recently_added,listing_type')";
        $page_ids = $rlDb->getRow($sql, 'ids');

        $sql  = "UPDATE `{db_prefix}blocks` ";
        $sql .= "SET `Sticky` = '0', `Position` = 1, `Page_ID` = '{$page_ids}' ";
        $sql .= "WHERE `Key` = 'geo_filter_box' LIMIT 1";
        $rlDb->query($sql);

        // Create system configs
        $add_configs = [
            'mf_db_version',
            'mf_filtering_pages',
            'mf_location_url_pages',
            'mf_geo_data_format',
            'mf_format_keys',
            'cache_multi_formats'
        ];

        foreach ($add_configs as $add_config) {
            $insert = [
                'Key' => $add_config,
                'Group_ID' => 0,
                'Plugin' => 'multiField',
                'Type' => 'text'
            ];
            $rlDb->insertOne($insert, 'config');
        }
    }

    /**
     * unInstall
     * @since 2.0.0
     */
    public function unInstall()
    {
        global $rlDb, $rlLang;

        $sql = "SELECT `T1`.*, `T2`.`ID` as `Data_Format_ID` FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`Parent_ID` = 0";
        $multi_formats = $rlDb->getAll($sql);

        if ($multi_formats) {
            $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');

            foreach ($multi_formats as $format) {
                $GLOBALS['rlMultiFieldAP']->deleteFormatChildFields($format['Key'], 'listing');
                $GLOBALS['rlMultiFieldAP']->deleteFormatChildFields($format['Key'], 'account');

                $sql ="UPDATE `{db_prefix}listing_fields` SET `Condition` = '' WHERE `Condition` = '{$format['Key']}'";
                $rlDb->query($sql);

                $sql ="UPDATE `{db_prefix}account_fields` SET `Condition` = '' WHERE `Condition` = '{$format['Key']}'";
                $rlDb->query($sql);

                $sql = "
                    DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1`
                    RIGHT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('data_formats+name+', `T1`.`Key`) = `T2`.`Key`
                    WHERE `T1`.`ID` = {$format['Data_Format_ID']}
                ";
                $rlDb->query($sql);
            }

            $sql = "DELETE FROM `{db_prefix}data_formats` WHERE `Plugin` = 'multiField'";
            $rlDb->query($sql);

            $GLOBALS['rlCache']->updateForms();
        }

        $tables_to_remove = ['multi_formats'];

        $languages = $GLOBALS['languages'] ?: $rlLang->getLanguagesList('all');
        foreach ($languages as $language) {
            $tables_to_remove[] = 'multi_formats_lang_' . $language['Code'];
        }

        $rlDb->dropTables($tables_to_remove);
    }

    /**
     * adaptCategories faceplate, to remove later.
     */
    public function adaptCategories($categories)
    {
        return $categories;

        if ($GLOBALS['rlGeoFilter']) {
            return $GLOBALS['rlGeoFilter']->adaptCategories($categories);
        } else {
            return $categories;
        }
    }

    /**
     * Get parents - get all parents of item
     *
     * @param string $key - key
     * @param array $parents - parents
     *
     * @return array
     **/
    public function getParents($key = false, $parents = false)
    {
        if (!$key) {
            return false;
        }

        $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');

        return $GLOBALS['rlMultiFieldAP']->getParents($key);
    }

    /**
     * Get Previous Field Key - define parent field key
     * @since 2.0.0
     *
     * @param  $field_key field key
     *
     * @return string
     */
    public function getPrevFieldKey($field_key)
    {
        preg_match("#([a-z0-9_-]+)_level([0-9])#", $field_key, $matches);

        if ($matches[1]) {
            if ($matches[2] == 1) {
                return $matches[1];
            } elseif ($matches[2] > 1) {
                return $matches[1] . '_' . $matches[2];
            } else {
                echo '4to to ne tak';
            }
        } else {
            return false;
        }
    }

    /**
     * @hook hookAddListingPreFields
     *
     * @since 2.0.0
     */
    public function hookAddListingPreFields()
    {
        global $rlSmarty;

        $controller = $rlSmarty->_tpl_vars['manageListing']->controller;
        $singleStep = $rlSmarty->_tpl_vars['manageListing']->singleStep;

        if ($controller == 'edit_listing'
            || ($controller == 'add_listing' && !$singleStep)
            || isset($_POST['from_post'])
            || !$this->formatKeys
            || isset($_GET['edit'])
        ) {
            return;
        }

        echo '<script class="fl-js-dynamic">
                $(function(){
                    for (var i in mfFields) {
                        (function(fields, values){
                            var mfHandler = new mfHandlerClass();
                            mfHandler.init(mf_prefix, fields, values);
                        })(mfFields[i], mfFieldVals[i]);
                    }

                    mfFields = [];
                    mfFieldVals = [];
                });
             </script>';
    }

    /**
     * Get missing prhases if the format items and assign it the global lang array
     *
     * @since 2.2.0
     */
    public function getMissingFormatItemPhrases()
    {
        global $rlSmarty;

        if (!$GLOBALS['config']['cache']) {
            return;
        }

        $field = &$rlSmarty->_tpl_vars['field'];

        if (in_array($field['Condition'], $this->formatKeys)) {
            $this->getPhrases($field['Condition']);
            $this->fixFieldOptionsOrder($field);
        }
    }

    /**
     * Fix the order of the form field options
     *
     * @since 2.2.1
     *
     * @param  array &$field - Field data, 'Values' index is required
     */
    public function fixFieldOptionsOrder(&$field)
    {
        $sorting_type = null;

        // Get sorting type from cache
        if ($field['Condition'] == $this->geoFormatData['Key']) {
            $sorting_type = $this->geoFormatData['Order_type'];
        }
        // Get sorting type from DB
        else {
            $sorting_type = $GLOBALS['rlDb']->getOne('Order_type', "`Key` = '{$field['Condition']}'", 'data_formats');
        }

        if ($sorting_type != 'alphabetic') {
            return;
        }

        foreach ($field['Values'] as &$item) {
            $item['name'] = $GLOBALS['lang'][$item['pName']];
        }

        $GLOBALS['reefless']->rlArraySort($field['Values'], 'name');
    }

    /**
     * Get format level phrases by format key/field condition
     *
     * @since 2.2.1 - $prefix parameter added
     * @since 2.2.0
     *
     * @param  string  $key         - Format key or field condition
     * @param  string  $langCode    - Language code
     * @param  boolean $globalScope - Add phrases to the global lang scope or return as array
     * @param  string  $prefix      - Phrase prefix for global scope
     * @return array                - Phrases array
     */
    public function getPhrases($key = null, $langCode = null, $globalScope = true, $prefix = 'data_formats+name+')
    {
        global $rlDb, $lang;

        if (!$key) {
            return;
        }

        $this->defineLocale();

        $lang_table = $this->formatLangTable;

        if ($langCode && array_key_exists($langCode, $GLOBALS['languages'])) {
            $lang_table = 'multi_formats_lang_' . $langCode;
        }

        if (!$lang_table) {
            return;
        }

        static $phrases_cache = [];

        if (!$phrases_cache[$key] || !$globalScope) {
            if ($id = $rlDb->getOne('ID', "`Key` = '{$key}'", 'multi_formats')) {
                $sql = "
                    SELECT `T2`.`Key`, `T2`.`Value`
                    FROM `{db_prefix}multi_formats` AS `T1`
                    LEFT JOIN `{db_prefix}{$lang_table}` AS `T2` ON `T1`.`Key` = `T2`.`Key`
                    WHERE `T1`.`Parent_ID` = {$id} AND `T1`.`Status` = 'active'
                ";

                if ($globalScope) {
                    foreach ($rlDb->getAll($sql) as $phrase) {
                        $lang[$prefix . $phrase['Key']] = $phrase['Value'];
                    }

                    $phrases_cache[$key] = true;
                } else {
                    return $rlDb->getAll($sql, ['Key', 'Value']);
                }
            }
        }
    }

    /**
     * Get format option names by key(s)
     *
     * @since 2.2.1
     *
     * @param  string|array $key         - Format option key as string or keys as array, ex: ['key1', 'key2']
     * @param  string       $langCode    - Language code
     * @param  boolean      $globalScope - Add phrases to the global lang scope or return as array
     * @param  string       $prefix      - Phrase prefix in global scope
     * @return array                     - Names array
     */
    public function getNames($key = null, $langCode = null, $globalScope = true, $prefix = 'data_formats+name+')
    {
        global $rlDb, $lang;

        if (!$key) {
            return;
        }

        $this->defineLocale();

        $key = is_array($key) ? $key : [$key];
        $lang_table = $this->formatLangTable;

        if ($langCode && array_key_exists($langCode, $GLOBALS['languages'])) {
            $lang_table = 'multi_formats_lang_' . $langCode;
        }

        if (!$lang_table) {
            return;
        }

        $sql = "
            SELECT `T2`.`Key`, `T2`.`Value`
            FROM `{db_prefix}multi_formats` AS `T1`
            LEFT JOIN `{db_prefix}{$lang_table}` AS `T2` ON `T1`.`Key` = `T2`.`Key`
            WHERE `T2`.`Key` IN ('" . implode("','", $key) . "') AND `T1`.`Status` = 'active'
        ";

        if ($globalScope) {
            foreach ($rlDb->getAll($sql) as $phrase) {
                $lang[$prefix . $phrase['Key']] = $phrase['Value'];
            }
        } else {
            return $rlDb->getAll($sql, ['Key', 'Value']);
        }
    }

    /**
     * Assign multiformat data to the account on map data mapping
     *
     * @since 2.2.1
     * @hook phpAccountAddressAssign
     */
    public function hookPhpAccountAddressAssign(&$mapping, &$accountAddress, &$profileInfo, &$accountID)
    {
        $locale = defined('RL_LANG_CODE') ? RL_LANG_CODE : $GLOBALS['config']['lang'];
        $lang_table = 'multi_formats_lang_' . $locale;

        foreach ($mapping as $key => $value) {
            if (strpos($key, 'level') > 0) {
                $pk = 'data_formats+name+' . $profileInfo[$value];
                $GLOBALS['lang'][$pk] = $GLOBALS['lang'][$pk] ?: $GLOBALS['rlDb']->getOne('Value', "`Key` = '" . $profileInfo[$value] . "'", $lang_table);
                $accountAddress[$key] = $GLOBALS['lang'][$pk];
            }
        }
    }

    /**
     * Adapt getDF multiformats data fetching
     *
     * @since 2.3.0
     * @hook phpCategoryGetDF
     */
    public function hookPhpCategoryGetDF(&$data, &$key, &$order)
    {
        global $rlDb, $config;

        if (!in_array($key, $this->formatKeys)) {
            return;
        }

        $this->defineLocale();

        if (!$this->formatLangTable) {
            return;
        }

        $id = null;

        if ($this->geoFormatData['Key'] == $key) {
            $id = $this->geoFormatData['ID'];
        } else {
            $id = $rlDb->getOne('ID', "`Key` = '{$key}' AND `Parent_ID` = 0", 'multi_formats');
        }

        $select_path_sql = '';

        if ($config['mf_multilingual_path']) {
            $user_lang = $config['lang'];

            if (defined('RL_LANG_CODE')) {
                $user_lang = RL_LANG_CODE;
            } elseif ($_REQUEST['lang']) {
                $user_lang = $_REQUEST['lang'];
            }

            $system_path_field = 'Path_' . $config['lang'];
            $user_path_field   = 'Path_' . $user_lang;
            $select_path_sql   = "IF(`{$user_path_field}` != '', `{$user_path_field}`, `{$system_path_field}`) AS `Path`, ";
        }

        $sql = "
            SELECT `T1`.*, {$select_path_sql}
            CONCAT('data_formats+name+', `T1`.`Key`) AS `pName`, `T2`.`Value` AS `name`
            FROM `{db_prefix}multi_formats` AS `T1`
            LEFT JOIN `{db_prefix}{$this->formatLangTable}` AS `T2` ON `T1`.`Key` = `T2`.`Key`
            WHERE `T1`.`Status` = 'active' AND `Parent_ID` = {$id};
        ";

        $data = $rlDb->getAll($sql);

        // Fix missing phrases
        foreach ($data as $item) {
            $GLOBALS['lang'][$item['pName']] = $item['name'];
        }

        if (!$order) {
            if ($this->geoFormatData['Key'] == $key) {
                $order = $this->geoFormatData['Order_type'];
            } else {
                $order = $rlDb->getOne('Order_type', "`Key` = '{$key}'", 'data_formats');
            }
        }

        if ($order && in_array($order, array('alphabetic', 'position'))) {
            $GLOBALS['reefless']->rlArraySort($data, $order == 'alphabetic' ? 'name' : 'Position');
        }
    }

    /**
     * Get missing phrase
     *
     * @since 2.3.0
     * @hook getPhrase
     */
    public function hookGetPhrase(&$params, &$phrase)
    {
        $key = $params['key'];

        if ($GLOBALS['lang'][$key]) {
            return;
        }

        if (strpos($key, 'data_formats+name+') !== 0) {
            return;
        }

        $item_key = str_replace('data_formats+name+', '', $key);

        if (!$item_key) {
            return;
        }

        if (!$format_key = $this->isMultifieldKey($item_key)) {
            return;
        }

        $this->getPhrases($format_key);

        $phrase = $GLOBALS['lang'][$key];
    }

    /**
     * Append multi format data to cache
     *
     * @since 2.3.0
     * @hook phpCacheUpdateDataFormats
     */
    public function hookPhpCacheUpdateDataFormats(&$rlCache, &$data)
    {
        global $rlDb;

        if (!$this->formatKeys) {
            return;
        }

        $rlDb->setTable('multi_formats');

        foreach ($this->formatKeys as $key) {
            if (!$id = $rlDb->getOne('ID', "`Key` = '{$key}'")) {
                continue;
            }

            // Append multi format items to the cache data
            $multi_formats[$key] = $rlDb->fetch(
                ['ID', 'Parent_ID', 'Key`, CONCAT("data_formats+name+", `Key`) AS `pName', 'Position', 'Default'],
                ['Status' => 'active'],
                "AND `Parent_ID` = {$id}"
            );
        }

        $rlCache->set('cache_multi_formats', $multi_formats);
    }

    /**
     * Get multiformat cache
     *
     * @since 2.3.1
     * @hook phpCacheGetBeforeFetch
     */
    public function hookPhpCacheGetBeforeFetch(&$out, $key, $id, $type, $parentIDs)
    {
        if ($key != 'cache_data_formats') {
            return;
        }

        if (!$this->formatKeys) {
            return;
        }

        if (!in_array($id, $this->formatKeys)) {
            return;
        }

        $out = $GLOBALS['rlCache']->get('cache_multi_formats', $id, $type, $parentIDs);

        $this->getPhrases($id);
    }

    /**
     * @deprecated 2.3.1
     */
    public function hookPhpCacheGetAfterFetch(&$out, &$content, &$cacheKey, &$key, &$type, &$parentIDs)
    {}

    /**
     * Define is item key contains any of multiformat keys
     *
     * @since 2.3.0
     *
     * @param  string $key - Format item key (without "data-formats+name+" prefix)
     * @return string      - Format key
     */
    public function isMultifieldKey($key)
    {
        $search_key = [];

        foreach (explode('_', $key) as $part) {
            $search_key[] = $part;

            if (in_array($found_key = implode('_', $search_key), $this->formatKeys)) {
                break;
            }
        }

        return $found_key;
    }

    /**
     * Define locale and set target formats language table to class variable
     *
     * @since 2.2.0
     */
    public function defineLocale()
    {
        $locale = $GLOBALS['config']['lang'];

        if (defined('RL_LANG_CODE')) {
            $locale = RL_LANG_CODE;
        } elseif ($_REQUEST['lang'] && array_key_exists($_REQUEST['lang'], $GLOBALS['languages'])) {
            $locale = $_REQUEST['lang'];
        }

        if ($locale) {
            $this->formatLangTable = 'multi_formats_lang_' . $locale;
        }
    }

    /**
     * Update to 1.0.2 version
     */
    public function update102()
    {
        $GLOBALS['rlDb']->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` = 'mf_cache_data_formats' LIMIT 1"
        );
    }

    /**
     * Update to 1.0.3 version
     */
    public function update103()
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Key'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX (`Key`)");
        }

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}lang_keys` WHERE `Column_name` = 'Module'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}lang_keys` ADD INDEX (`Module`)");
        }
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120()
    {
        global $rlDb;

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'phpListingsGetMyListings' AND `Plugin` = 'multiField'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'myListingTop' AND `Plugin` = 'multiField'"
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}config`
            WHERE `Key` = 'mf_cache_data_formats' LIMIT 1"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` = 'mf_cache_data_formats_top_level' LIMIT 1"
        );

        $rlDb->query(
            "UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` = 'mf_cache_data_formats_multi_leveled' LIMIT 1"
        );
    }

    /**
     * Update to 1.2.1 version
     */
    public function update121()
    {
        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}pages` SET `Geo_exclude` = '1' WHERE `Key` = 'view_details'");
    }

    /**
     * Update to 1.3.0 version
     */
    public function update130()
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Path'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX (`Path`)");
        }
    }

    /**
     * Update to 1.4.0 version
     */
    public function update140()
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Path'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` ADD INDEX (`Path`)");
        }
    }

    /**
     * Update to 1.4.4 version
     */
    public function update144()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks`
            WHERE `Name` = 'browseMiddle' AND `Plugin` = 'multiField'"
        );
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $rlDb;

        // Migrate filtering config values
        $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');

        $rlDb->outputRowsMap = array(false, 'Key');

        $in_clause = implode("','", $GLOBALS['rlMultiFieldAP']->getAvailableControllers());
        $geo_pages = $rlDb->fetch(
            array('Key'),
            array('Geo_exclude' => 1),
            "AND `Controller` IN ('{$in_clause}') ORDER BY `Position`",
            NULL, 'pages'
        );

        if ($geo_pages) {
            $sql = "
                UPDATE `{db_prefix}config` SET `Default` = '" . implode(',', $geo_pages) . "'
                WHERE `Key` IN ('mf_filtering_pages', 'mf_location_url_pages')
            ";
            $rlDb->query($sql);
        }

        $rlDb->dropColumnFromTable('Geo_exclude', 'pages');
        $rlDb->addColumnToTable('Parent_IDs', "VARCHAR(255)", 'data_formats');

        // Remove legacy config
        $configs_to_be_removed = array(
            'mf_geo_levels_toshow',
            'mf_cache_client',
            'mf_cache_system',
            'mf_cache_data_formats_multi_leveled',
            'mf_cache_data_formats_top_level',
            'mf_geo_block_list',
            'mf_geo_columns',
            'mf_geo_cookie_lifetime',
            'mf_geo_subdomains_all',
            'mf_rebuild_cache',
            'mf_geo_multileveled',
            'mf_import_per_run',
        );

        $rlDb->query("
            DELETE FROM `{db_prefix}config`
            WHERE `Plugin` = 'multiField'
            AND `Key` IN ('" . implode("','", $configs_to_be_removed) . "')
        ");

        $rlDb->query("
            DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'multiField'
            AND `Key` IN ('config+name+" . implode("','config+name+", $configs_to_be_removed) . "')
        ");

        // Remove hooks
        $hooks_to_be_removed = array(
            'seoBase',
            'apPhpControlsBottom',
            'phpSubmitProfileEnd',
            'apPhpGetAccountFieldsEnd',
            'phpSmartyClassFetch',
            'apTplPagesForm',
            'apPhpPagesBeforeEdit',
            'apPhpPagesBeforeAdd',
            'apPhpPagesPost',
            'pageTitle'
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'multiField'
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");

        // Ungroup configs
        $sql = "
            UPDATE `{db_prefix}config` SET `Group_ID` = 0
            WHERE `Key` IN ('mf_filtering_pages', 'mf_location_url_pages')
        ";
        $rlDb->query($sql);

        // Update position of configs
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 3 WHERE `Key` = 'mf_geo_autodetect'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 6 WHERE `Key` = 'mf_geo_block_autocomplete'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 7 WHERE `Key` = 'mf_geo_autocomplete_limit'");
        $rlDb->query("UPDATE `{db_prefix}config` SET `Position` = 10 WHERE `Key` = 'mf_geo_subdomains'");

        // Remove legacy files
        $files_to_be_removed = array(
            'geo_box_selectors.tpl',
            'static/jquery.geo_autocomplete.js',
            'static/style.css',
            'autocomplete.inc.php',
            'geo_block.tpl',
            'list_level.tpl',
            'mf_block.tpl',
            'mf_block_account.tpl',
            'mf_reg_js.tpl',
        );

        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'multiField/' . $file);
        }

        // remove unnecessary phrases
        $phrases = array(
            'mf_field',
            'mf_type',
            'mf_type_new',
            'mf_remove_items',
            'mf_import_progress',
            'mf_geo_path_nogeo',
            'mf_geo_select_location',
            'mf_geo_gobutton',
            'mf_geo_choose_location',
            'mf_geo_remove',
            'mf_collapse',
            'mf_expand',
            'mf_total',
            'mf_geo_show_other_items',
            'mf_geo_path_processing',
            'mf_cache_rebuilt',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'multiField' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        // Remove index from `Path` field
        if ($rlDb->getRow("SHOW INDEXES FROM `{db_prefix}data_formats` WHERE `Column_name` = 'Path'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}data_formats` DROP INDEX `Path`");
        }

        // copy configuration of old location box to new "Location Filter" box
        $positionBox = $rlDb->getOne('Position', "`Key` = 'geo_filter_block'", 'blocks');
        $pageIDs     = $rlDb->getOne('Page_ID', "`Key` = 'geo_filter_block'", 'blocks');

        $rlDb->query(
            "UPDATE `{db_prefix}blocks`
            SET `Position` = {$positionBox}, `Page_ID` = '{$pageIDs}'
            WHERE `Key` = 'geo_filter_box'"
        );

        // remove old block from DB
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` LIKE 'geo_filter_block'");
        $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE '%geo_filter_block'");

        // add new hook (Flynax 4.7.1 system cannot add/update hook in DB with same name and another class)
        if (!$rlDb->getOne(
            'ID',
            "`Name` = 'pageinfoArea' AND `Plugin` = 'multiField' AND `Class` = 'MultiField'",
            'hooks'
        )) {
            $rlDb->insertOne(
                array(
                    'Name'   => 'pageinfoArea',
                    'Class'  => 'MultiField',
                    'Plugin' => 'multiField',
                ),
                'hooks'
            );
        }

        if ($GLOBALS['config']['package_name'] === 'general') {
            // remove duplicates of rows
            $rlDb->query(
                "DELETE `{db_prefix}lang_keys` FROM `{db_prefix}lang_keys` INNER JOIN
                    (SELECT  MIN(ID) `MINID`, `Key`, `Module`, `Code`
                        FROM `{db_prefix}lang_keys`
                        GROUP BY `Key` HAVING COUNT(1) > 1) as Duplicates
                        ON (
                            Duplicates.`Key` = `{db_prefix}lang_keys`.`Key`
                            and Duplicates.`Module` = `{db_prefix}lang_keys`.`Module`
                            and Duplicates.`Code`   = `{db_prefix}lang_keys`.`Code`
                            and Duplicates.`MINID`  <> `{db_prefix}lang_keys`.ID
                        )"
            );
        }
    }

    /**
     * Update to 2.1.0 version
     */
    public function update210()
    {
        global $rlDb;

        $position = $rlDb->getOne('Position', "`Key` = 'mf_listing_geo_urls'", 'config');

        $rlDb->updateOne(array(
            'fields' => array('Position' => $position),
            'where' => array('Key' => 'mf_multilingual_path')
        ), 'config');

        // Remove legacy files
        $files_to_be_removed = array(
            'admin/edit_format_block.tpl',
        );

        foreach ($files_to_be_removed as $file) {
            unlink(RL_PLUGINS . 'multiField/' . $file);
        }

        // Remove hooks
        $hooks_to_be_removed = array(
            'utilsRedirectURL',
            'phpAbstractStepsBuildStepUrlAfterBase',
            'reeflessRedirctVars',
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'multiField'
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");
    }

    /**
     * Update to 2.2.0 version
     */
    public function update220()
    {
        global $rlDb, $config, $rlLang, $languages, $rlConfig;

        set_time_limit(0);

        $GLOBALS['reefless']->loadClass('MultiFieldAP', null, 'multiField');

        $path_fields_select = ', `Path`';
        $path_fields = [];

        $columns = array(
            'Parent_ID' => "INT(11) NOT NULL AFTER `ID`",
            'Parent_IDs' => "VARCHAR(47) NOT NULL AFTER `Parent_ID`",
            'Path' => "VARCHAR(255) NOT NULL AFTER `Status`"
        );
        $rlDb->addColumnsToTable($columns, 'multi_formats');

        $rlDb->query("
            ALTER TABLE `{db_prefix}multi_formats` CHANGE `Default` `Default` ENUM('0','1') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0'
        ");
        $rlDb->query("
            ALTER TABLE `{db_prefix}multi_formats` ADD INDEX `Group_index` ( `Parent_ID`, `Key`, `Status`)
        ");
        $rlDb->query("
            ALTER TABLE `{db_prefix}multi_formats` ADD INDEX `Key` (`Key`)
        ");
        $rlDb->query("
            ALTER TABLE `{db_prefix}multi_formats` ADD INDEX `Path` (`Path`)
        ");
        $rlDb->query("
            ALTER TABLE `{db_prefix}multi_formats` ENGINE = MyISAM;
        ");

        $this->createLangTables();

        // Remove system config phrases
        $sql = "
            DELETE FROM `{db_prefix}lang_keys` 
            WHERE `Key` LIKE '" . implode("' OR `Key` LIKE '", ['mf_db_version', 'mf_filtering_pages', 'mf_location_url_pages', 'mf_data_entry', 'mf_importing_caption']) . "'
        ";
        $rlDb->query($sql);

        // Create fields
        $languages = $languages ?: $rlLang->getLanguagesList('all');

        if ($config['mf_multilingual_path']) {
            if (count($languages) > 1) {
                // Create multilingual path fields in new table
                $GLOBALS['rlMultiFieldAP']->managePathFields(true);

                // Collect path fields
                foreach ($languages as $language) {
                    $path_fields[] = "Path_{$language['Code']}";
                }

                $path_fields_select = ',`' . implode('`,`', $path_fields) . '`';
            }
        }

        $rlDb->setTable('multi_formats');
        $format_data = $rlDb->fetch(['Key', 'ID']);

        foreach ($format_data as $format_field) {
            // Move data_formats data to new table
            $sql = "
                INSERT INTO `{db_prefix}multi_formats`
                SELECT `ID`, `Parent_ID`, `Parent_IDs`, `Position`, 0 AS `Levels`, `Key`, `Default`, '0' AS `Geo_filter`, `Status`{$path_fields_select}
                FROM `{db_prefix}data_formats`
                WHERE `Key` LIKE '{$format_field['Key']}_%'
                # AND `Plugin` = 'multiField'
            ";
            $rlDb->query($sql);

            // Replace parent ID for the first level of data
            $data_format_id = $rlDb->getOne('ID', "`Key` = '{$format_field['Key']}'", 'data_formats');
            $sql = "UPDATE `{db_prefix}multi_formats` SET `Parent_ID` = {$format_field['ID']} WHERE `Parent_ID` = {$data_format_id}";
            $rlDb->query($sql);

            // Remove data formats from system table
            $sql = "
                DELETE FROM `{db_prefix}data_formats`
                WHERE `Key` LIKE '{$format_field['Key']}_%'
                # AND `Plugin` = 'multiField'
            ";
            $rlDb->query($sql);

            foreach ($languages as $language) {
                $table_name = 'multi_formats_lang_' . $language['Code'];

                // Move lang phrases
                $sql = "
                    INSERT INTO `{db_prefix}{$table_name}`
                    SELECT REPLACE(`Key`, 'data_formats+name+', '') AS `Key`, `Value`
                    FROM `{db_prefix}lang_keys`
                    WHERE `Key` LIKE 'data_formats+name+{$format_field['Key']}_%' AND `Code` = '{$language['Code']}'
                    # AND `Module` = 'formats'
                    # AND `Plugin` = 'multiField'
                ";
                $rlDb->query($sql);
            }

            // Remove geo format related phrases
            $sql = "
                DELETE FROM `{db_prefix}lang_keys`
                WHERE `Key` LIKE 'data_formats+name+{$format_field['Key']}_%'
                # AND `Module` = 'formats'
                # AND `Plugin` = 'multiField'
            ";
            $rlDb->query($sql);
        }

        $rlDb->dropColumnFromTable('Parent_IDs', 'data_formats');

        if ($config['mf_multilingual_path']) {
            $rlDb->dropColumnsFromTable($path_fields, 'data_formats');
        } else {
            $rlDb->dropColumnFromTable('Path', 'data_formats');
        }

        // Create system configs
        $add_configs = [
            'mf_geo_data_format',
            'mf_format_keys'
        ];

        foreach ($add_configs as $add_config) {
            $insert = [
                'Key' => $add_config,
                'Group_ID' => 0,
                'Plugin' => 'multiField',
                'Type' => 'text'
            ];
            $rlDb->insertOne($insert, 'config');

            $config[$add_config] = '';
        }

        // Update geo format cache data
        $sql = "
            SELECT `T2`.`ID`, `T1`.`Order_type`, `T2`.`Levels`, `T2`.`Key`
            FROM `{db_prefix}data_formats` AS `T1`
            JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key`
            WHERE `T2`.`Geo_filter` = '1' AND `T2`.`Status` = 'active' AND `T2`.`Parent_ID` = 0
        ";
        $geo_format = $rlDb->getRow($sql);
        $rlConfig->setConfig('mf_geo_data_format', json_encode($geo_format));

        // Update multi format keys cache data
        $rlDb->setTable('multi_formats');
        $rlDb->outputRowsMap = array(false, 'Key');
        $format_keys = $rlDb->fetch(['Key'], ['Status' => 'active'], "AND `Parent_ID` = 0");
        $rlConfig->setConfig('mf_format_keys', implode('|', $format_keys));

        // Remove hooks
        $hooks_to_be_removed = array(
            'phpMailSend',
            'sitemapGetListingsBeforeGetAll',
            'listingsModifyWhereSearch'
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'multiField'
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");
    }

    /**
     * Update to 2.3.0 version
     */
    public function update230()
    {
        global $rlDb;

        $position = $rlDb->getOne('Position', "`Key` = 'mf_listing_geo_urls'", 'config');
        $configs  = ['mf_urls_in_sitemap', 'mf_home_in_sitemap', 'mf_filtering_divider'];

        foreach ($configs as $configKey) {
            $rlDb->updateOne([
                'fields' => ['Position' => ++$position],
                'where'  => ['Key'      => $configKey]
            ], 'config');
        }

        $GLOBALS['rlCache']->updateDataFormats();
    }

    /**
     * Update to 2.3.1 version
     */
    public function update231()
    {
        global $rlDb;

        // Remove hooks
        $hooks_to_be_removed = array(
            'phpCacheGetAfterFetch'
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'multiField'
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");

        $GLOBALS['rlCache']->updateDataFormats();
    }

    /**
     * Update to 2.5.0 version
     */
    public function update250()
    {
        global $rlDb;

        // Remove hooks
        $hooks_to_be_removed = array(
            'accountsSearchDealerSqlWhere'
        );
        $rlDb->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'multiField'
            AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')
        ");

        $rlDb->updateOne(['fields' => ['Default' => 'filter'], 'where'  => ['Key' => 'mf_account_page_filtration']], 'config');
    }

    /**
     * Update to 2.5.1 version
     */
    public function update251()
    {
        global $rlDb;

        // Remove unnecessary phrases
        $phrases = array(
            'ext_notice_delete_format',
        );

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'multiField' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );
    }

    /**
     * Update to 2.5.2 version
     */
    public function update252()
    {
        global $rlDb;

        foreach ($GLOBALS['languages'] as $language) {
            $rlDb->query("ALTER TABLE `{db_prefix}multi_formats_lang_{$language['Code']}` ADD INDEX(`Value`)");
            $rlDb->query("ALTER TABLE `{db_prefix}multi_formats_lang_{$language['Code']}` ENGINE = MyISAM");
            $rlDb->query("OPTIMIZE TABLE `{db_prefix}multi_formats_lang_{$language['Code']}`");
        }
    }

    /**
     * Update to 2.5.4 version
     */
    public function update254()
    {
        global $rlDb;

        $GLOBALS['reefless']->deleteDirectory(RL_PLUGINS . 'multiField/vendor/');

        $hooks_to_be_removed = [
            'apPhpIndexBottom',
            'apPhpIndexBeforeController',
        ];
        $rlDb->query(
            "DELETE FROM `{db_prefix}hooks`
             WHERE `Plugin` = 'multiField'
             AND `Name` IN ('" . implode("','", $hooks_to_be_removed) . "')"
        );

        $rlDb->updateOne([
            'fields' => ['Position' => $rlDb->getOne('Position', "`Key` = 'mf_geo_autocomplete_limit'", 'config')],
            'where'  => ['Key' => 'mf_popular_locations_level']
            ],
            'config'
        );
    }

    /**
     * Update to 2.6.0 version
     */
    public function update260()
    {
        global $rlDb;

        $position = $rlDb->getOne('Position', "`Key` = 'mf_geofilter_expiration'", 'config');
        $rlDb->updateOne([
            'fields' => ['Position' => $position],
            'where' => ['Key' => 'mf_show_nearby_listings'],
        ], 'config');
        $rlDb->updateOne([
            'fields' => ['Position' => $position],
            'where' => ['Key' => 'mf_nearby_distance'],
        ], 'config');

        $columns = array(
            'Latitude' => "double NOT NULL AFTER `Status`",
            'Longitude' => "double NOT NULL AFTER `Latitude`"
        );
        $rlDb->addColumnsToTable($columns, 'multi_formats');

        // Add indexes
        if ($GLOBALS['config']['mf_format_keys']) {
            $sql = "SHOW INDEX FROM `{db_prefix}listings`";
            $indexes = $rlDb->getAll($sql, [false, 'Column_name']);

            foreach (explode('|', $GLOBALS['config']['mf_format_keys']) as $format_key) {
                $fields = $rlDb->fetch('Key', ['Condition' => $format_key], null, null, 'listing_fields');

                foreach ($fields as $field) {
                    if (!in_array($field['Key'], $indexes)) {
                        $sql = "ALTER TABLE `{db_prefix}listings` ADD INDEX(`{$field['Key']}`)";
                        $rlDb->query($sql);
                    }
                }
            }
        }
    }
}
