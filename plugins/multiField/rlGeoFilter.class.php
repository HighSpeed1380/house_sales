<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLGEOFILTER.CLASS.PHP
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
use Flynax\Utils\Category;

class rlGeoFilter
{
    public $geo_format         = array();
    public $geo_filter_data    = array();
    public $cookieTime         = 0;
    public $detailsPage        = false;
    public $accountPage        = false;
    public $searchResultsPage  = false;
    public $systemPathField    = 'Path';
    public $userPathField      = '';
    public $selectPathSQL      = "`Path`";
    private $pages             = [];
    private $pagesControllers  = [];
    private $foundData         = [];
    private $geoUrls           = [];
    private $multilingualPaths = false;
    private $dbAccountFields   = [];
    private $dbListingFields   = [];

    /**
     * Files ignored for smarty compilation
     *
     * @since 2.4.0
     *
     * @var array
     */
    private $noCompileFiles = [
        'plugins/multiField/tplFooter.tpl'
    ];

    /**
     * Current listing location data
     * @var array
     */
    public $listing_location_data = array();

    public function __construct()
    {
        global $config, $plugins;

        $this->geo_format = json_decode($config['mf_geo_data_format'], true);

        $exp_days = (int) $config['mf_geofilter_expiration'];
        $this->cookieTime = strtotime(
            '+' . ($exp_days > 0
            ? $exp_days
            : 90) . ' days'
        );

        if (false === defined('REALM')) {
            if (($config['mod_rewrite'] && $_GET['listing_id']) || (!$config['mod_rewrite'] && $_GET['id'])) {
                $this->detailsPage = true;
            } elseif ($plugins['ref']) {
                $refPattern = str_replace(['*', '#ID#'], '', $config['ref_tpl']);
                preg_match("/\/{$refPattern}\d+\/?$/", $_SERVER['REQUEST_URI'], $matches);
                $this->detailsPage = $matches && $matches[0];
            }
        }
    }

    /**
     * @hook init
     * @since 2.0.0
     */
    public function hookInit()
    {
        if ($this->geo_format && !defined('AJAX_FILE') && !defined('CRON_FILE')) {
            $this->rewriteGet();
        }

        if (defined('AJAX_FILE') && in_array($_REQUEST['mode'], array('manageListing', 'mfGeoAutocomplete', 'novaLoadMoreListings'))) {
            $this->init();
        }
    }

    /**
     * @hook phpBeforeLoginValidation
     * @since 2.0.0
     */
    public function hookPhpBeforeLoginValidation()
    {
        $this->init();
    }

    /**
     * Fix Rewrite for cases when wildcard rule doesn't work
     */
    public function fixRewrite()
    {
        if (!defined('REWRITED')) {
            $request_uri = $_SERVER['REQUEST_URI'];

            $this->fixDir($request_uri);

            preg_match('/^\/([^\/\W]{2})\/.*/', $request_uri, $match);

            if ($match[1]) {
                // Fix language defining
                $_GET['language'] = $_GET['lang'] = $match[1];

                if ($_GET['page'] == $match[1]) {
                    // Fix other levels
                    if ($_GET['rlVareables']) {
                        $vars = explode('/', $_GET['rlVareables']);
                        $_GET['page'] = array_shift($vars);
                        $_GET['rlVareables'] = implode('/', $vars);
                    } else {
                        $_GET['page'] = '';
                    }
                }
            }

            $GLOBALS['reefless']->loadClass('Navigator');
            $GLOBALS['rlNavigator']->fixRewrite();
        }
    }

    /**
     * Rewrite Get
     *
     * check if there is location in url,
     * if there is location in the url:
     * - save applied location to the >geo_filter_data['applied_location']
     * - remove from GET to allow system define pages and variables as it works by default
     * - GET array after the function should NOT contain any location variables
     */
    public function rewriteGet()
    {
        global $rlDb, $config;

        $this->fixRewrite();

        /**
         * @todo - Remove this condition and all code related to 'reset_location' in get after 3-4 version ahead 2.5.4
         */
        if (isset($_GET['reset_location'])) {
            $this->resetLocation();

            $url = RL_URL_HOME . preg_replace('/\?(.+)$/', '', ltrim($_SERVER['REQUEST_URI'], '/'));
            Util::redirect($url);

            return false;
        }

        $page = $this->idnToUtf8($_GET['page']);
        $vareables = $_GET['rlVareables'];
        $wildcard_page = '';

        if (isset($_GET['wildcard'])) {
            $locale = $_GET['lang'] ?: $config['lang'];
            $GLOBALS['pages'] = Util::getPages(['Key', 'Path'], ['Status' => 'active'], null, ['Key', 'Path'], $locale);

            if (in_array($page, $GLOBALS['pages'])) {
                $wildcard_page = $page;
                $page = false;
            }
        }

        // Rewrite rule corrections
        if (isset($_GET['wildcard']) && $_GET['rlVareables'] && strpos($_GET['rlVareables'], '.html')) {
            $_GET['rlVareables'] = str_replace('.html', '', $_GET['rlVareables']);
        }

        if (isset($_GET['wildcard']) && $_GET['wildcard'] == '') {
            unset($_GET['wildcard']);
        }

        $get_vars = array();
        if ($page) {
            $get_vars[] = $page;
        }
        if ($vareables) {
            foreach (explode('/', $vareables) as $var) {
                $get_vars[] = $var;
            }
        }

        if ($get_vars) {
            $this->geo_filter_data['applied_location'] = $this->prepareGetVars($get_vars);

            if ($this->geo_filter_data['applied_location']) {
                $this->saveLocation($this->geo_filter_data['applied_location']);

                $new_vars = [];

                if ($_GET['rlVareables']) {
                    $applied_path_items = explode('/', $this->geo_filter_data['applied_location']['Path']);

                    foreach (explode('/', $_GET['rlVareables']) as $index => $item) {
                        if ((strlen($item) == 2 && $index === 0)
                            || empty($item)
                            || in_array($item, $applied_path_items)
                        ) {
                            continue;
                        }

                        // Add page from subdomain to get vars
                        if (!$new_vars && $wildcard_page) {
                            $new_vars[] = $wildcard_page;
                            unset($wildcard_page);
                        }

                        $new_vars[] = $item;
                    }

                    $_GET['page'] = $wildcard_page ?: array_shift($new_vars);
                    $_GET['rlVareables'] = implode('/', $new_vars);
                } else {
                    $_GET['page'] = '';
                }
            }
        }

        // Unset applied location data for the "Listing Details" page
        if ($this->detailsPage) {
            unset($this->geo_filter_data['applied_location']);
        }
    }

    /**
     * Prepare Get Variables - recursive function
     *
     * Check if there is location in url, remove from given string and return last applied location
     *
     * @param  string get_vars   - all get variables string (including sub-domain)
     * @return string            - applied location array (from data_formats table)
     */
    public function prepareGetVars($get_vars)
    {
        global $rlDb, $config, $pages;

        // Remove exists pages from the vars array
        foreach ($get_vars as $index => $path) {
            if (($pages && array_search($path, $pages)) || strlen($path) == 2 || empty($path)) {
                unset($get_vars[$index]);
            }
        }

        $get_vars = array_slice($get_vars, 0, $this->geo_format['Levels']);

        if (!$get_vars) {
            return false;
        }

        $get_lang   = Valid::escape($_GET['lang']);
        $path_field = 'Path';

        $sql = "SELECT `T1`.*, `T2`.`Value` AS `name`";

        if ($config['mf_multilingual_path']) {
            $path_system = 'Path_' . $config['lang'];
            $path_field  = $get_lang && $rlDb->getOne('ID', "`Code` = '{$get_lang}' AND `Status` = 'active'", 'languages')
            ? 'Path_' . $get_lang
            : $path_system;

            $sql .= ", IF(`{$path_field}` != '', `{$path_field}`, `{$path_system}`) AS `Path`";
        }

        $locale = $get_lang ?: $config['lang'];

        $sql .= "
            FROM `{db_prefix}multi_formats` AS `T1`
            LEFT JOIN `{db_prefix}multi_formats_lang_{$locale}` AS `T2` ON `T2`.`Key` = `T1`.`Key`
            WHERE `T1`.`Status` = 'active' AND (
        ";

        if ($config['mf_geo_subdomains_type'] == 'combined' && $config['mf_geo_subdomains']) {
            $check = $this->idnToUtf8($get_vars[0]);
            $sql .= "REPLACE(`{$path_field}`, '/', '-') = '{$check}' ";

            if ($path_system) {
                $sql .= "OR REPLACE(`{$path_system}`, '/', '-') = '{$check}'";
            }
        } else {
            $check = implode('/', array_map(function($var) {
                return $this->idnToUtf8($var);
            }, $get_vars));

            $sql .= "`{$path_field}` = '{$check}' ";

            if ($path_system) {
                $sql .= "OR `{$path_system}` = '{$check}'";
            }
        }

        $sql .= ')';

        $data_entry = $GLOBALS['rlDb']->getRow($sql);

        if ($data_entry) {
            return $data_entry;
        } else {
            $get_vars = array_slice($get_vars, 0, -1);
            return $this->prepareGetVars($get_vars);
        }
    }

    /**
     * Initialization
     *
     * Makes necessary variable preparation for the geo filtering
     */
    public function init()
    {
        static $initted = false;

        if ($initted) {
            return;
        }

        $this->definePathField();
        $this->appliedLocation();

        $GLOBALS['rlSmarty']->assign_by_ref('geo_filter_data', $this->geo_filter_data);

        $initted = true;

        /**
         * Workaround for old version conditions
         * @todo Remove variable from GLOBALS scope
         */
        $GLOBALS['geo_filter_data']            = $this->geo_filter_data;
        $GLOBALS['geo_filter_data']['geo_url'] = $this->geo_filter_data['applied_location']['Path'];
    }

    /**
     * Applied Location
     *
     * Expand applied location array (that was defined in RewriteGet function)
     */
    private function appliedLocation()
    {
        global $rlDb, $config;

        $this->geo_filter_data['filtering_pages']    = $config['mf_filtering_pages']
        ? explode(',', $config['mf_filtering_pages'])
        : [];

        $this->geo_filter_data['location_url_pages'] = $config['mf_location_url_pages']
        ? explode(',', $config['mf_location_url_pages'])
        : [];

        // Set/get session location
        $this->geo_filter_data['from_session'] = false;
        if ($this->geo_filter_data['applied_location']) {
            $_SESSION['geo_filter_location'] = $this->geo_filter_data['applied_location'];
        } elseif ($_SESSION['geo_filter_location']) {
            $this->geo_filter_data['applied_location'] = $_SESSION['geo_filter_location'];
            $this->geo_filter_data['from_session'] = true;
        } elseif (isset($_COOKIE['mf_geo_location']) && $_COOKIE['mf_geo_location'] != 'reset') {
            $this->geo_filter_data['applied_location'] = json_decode($_COOKIE['mf_geo_location'], true);
            $this->geo_filter_data['from_session'] = true;
        }

        // Get stack of locations from applied location and up to the top level
        $applied_location = $this->geo_filter_data['applied_location'];

        if ($applied_location['Key']) {
            $applied_location_keys = array();
            $applied_location_keys[] = $applied_location['Key'];

            $locale = defined('RL_LANG_CODE') ? RL_LANG_CODE : $_REQUEST['lang'];

            $parent_id = $applied_location['Parent_ID'];

            // Get rest location levels
            while ($parent_id != $this->geo_format['ID']) {
                $sql = "SELECT `T1`.`Key`, `T1`.`Parent_ID`, `T2`.`Value` AS `name`, {$this->selectPathSQL} ";
                $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_{$locale}` AS `T2` ";
                $sql .= "ON `T2`.`Key` = `T1`.`Key` ";
                $sql .= "WHERE `T1`.`ID` = '{$parent_id}' AND `T1`.`Status` = 'active'";
                $location = $GLOBALS['rlDb']->getRow($sql);

                if ($location) {
                    $applied_location_keys[] = $location['Key'];
                    $parent_id = $location['Parent_ID'];

                    $this->geo_filter_data['location'][] = $location;
                } else {
                    $this->resetLocation();
                    unset($this->geo_filter_data['applied_location'], $applied_location_keys);

                    $GLOBALS['rlDebug']->logger("MultiField: unable to find format with ID: {$parent_id}");
                    break;
                }
            }

            $this->geo_filter_data['location_keys'] = array_reverse($applied_location_keys);
        }

        $this->prepareLocationFields();
    }

    /**
     * Perform proper location redirect
     *
     * @since 2.1.0
     */
    private function validLocationRedirect()
    {
        global $config, $page_info;

        if (!$this->geo_filter_data['applied_location']
            || $this->detailsPage
            || $this->searchResultsPage
            || (defined('AJAX_FILE') && AJAX_FILE === true)
            || !$config['mod_rewrite']
        ) {
            return;
        }

        if (
            $this->accountPage && (
                $config['mf_account_page_filtration'] == 'url'
                || ($config['mf_account_page_filtration'] == 'filter' && !in_array($page_info['Key'], $this->geo_filter_data['location_url_pages']))
            )
        ) {
            return;
        }

        $host = $GLOBALS['domain_info']['scheme'] . '://' . $this->idnToUtf8($_SERVER['HTTP_HOST']);
        $request_url = $this->buildUrl(
            $this->cleanUrl($host),
            $this->geo_filter_data['is_location_url'] ? $this->geo_filter_data['applied_location'] : null
        );
        $real_url = $host . urldecode($_SERVER['REQUEST_URI']);

        if ($index = strpos($real_url, '?')) {
            $real_url = substr($real_url, 0, $index);
        }

        if ($request_url != $real_url) {
            if ($_SERVER['QUERY_STRING']) {
                $request_url .= '?' . $_SERVER['QUERY_STRING'];
            }

            header("Location: {$request_url}", true, 302);
            exit;
        }
    }

    /**
     * @deprecated 2.1.0
     */
    private function appliedLocation2()
    {}

    /**
     * Define multilingual path field variables
     *
     * @since 2.1.0
     */
    public function definePathField()
    {
        global $config;

        if ($config['mf_multilingual_path']) {
            $user_lang = defined('RL_LANG_CODE') ? RL_LANG_CODE : $_REQUEST['lang'];

            $this->systemPathField = 'Path_' . $config['lang'];
            $this->userPathField   = $user_lang ? 'Path_' . $user_lang : $this->systemPathField;
            $this->selectPathSQL   = "IF(`{$this->userPathField}` != '', `{$this->userPathField}`, `{$this->systemPathField}`) AS `Path`";
        }
    }

    /**
     * Prepare location listing and account fields
     * @since 2.0.0
     */
    public function prepareLocationFields()
    {
        global $rlDb;

        if (!$this->geo_format) {
            return;
        }

        static $location_fields_data_fetched = false;

        if ($location_fields_data_fetched) {
            return;
        }

        $listing_fields = $rlDb->fetch(
            array('Key'),
            array(
                'Condition' => $this->geo_format['Key'],
                'Status'    => 'active',
            ),
            "AND `Key` NOT LIKE 'citizenship%' ORDER BY `Key`",
            null,
            'listing_fields'
        );

        if ($listing_fields) {
            foreach ($listing_fields as $k => $field) {
                if ($value = $this->geo_filter_data['location_keys'][$k]) {
                    $this->geo_filter_data['location_listing_fields'][$field['Key']] = $value;
                } else {
                    $this->geo_filter_data['location_listing_fields'][$field['Key']] = '';
                }
            }
        }

        $account_fields = $rlDb->fetch(
            array('Key'),
            array(
                'Condition' => $this->geo_format['Key'],
                'Status'    => 'active',
            ),
            "AND `Key` NOT LIKE 'citizenship%' ORDER BY `Key`",
            null,
            'account_fields'
        );

        if ($account_fields) {
            foreach ($account_fields as $k => $field) {
                if ($value = $this->geo_filter_data['location_keys'][$k]) {
                    $this->geo_filter_data['location_account_fields'][$field['Key']] = $value;
                } else {
                    $this->geo_filter_data['location_account_fields'][$field['Key']] = '';
                }
            }
        }

        $location_fields_data_fetched = true;
    }

    /**
     * Build clean (without location data) url by given host and path (optional),
     * otherwise the function generates clean url of current page.
     *
     * @since 2.1.0 - $lang_code parameter removed
     *
     * @param  string $req_host  - Request host
     * @param  string $req_url   - Request url
     * @return string            - Clean url
     */
    public function cleanUrl($host = null, $path = null)
    {
        global $rlDb, $config, $domain_info;

        $scheme = $domain_info['scheme'] . '://';
        $host   = $host ?: $scheme . $domain_info['host'];
        $path   = isset($path) ? $path : str_replace('?reset_location', '', urldecode($_SERVER['REQUEST_URI']));

        $this->fixDir($path, $host);

        // Remove applied location from the url
        if ($this->geo_filter_data['applied_location']['Path']) {
            $this->removeLocationDataFromUrl($this->geo_filter_data['applied_location']['Path'], $host, $path);

            // Remove default location version if so
            if ($config['mf_multilingual_path']) {
                $this->removeLocationDataFromUrl($this->geo_filter_data['applied_location']['Path_' . $config['lang']], $host, $path);
            }
        }

        return $host . $path;
    }

    /**
     * Remove applied location data from URL
     *
     * @since 2.1.0
     *
     * @param string $data  - Path data
     * @param string &$host - Host part of url
     * @param string &$path - Path part of url
     */
    public function removeLocationDataFromUrl($data, &$host, &$path)
    {
        global $config;

        $location = explode('/', $data);

        if ($config['mf_geo_subdomains']) {
            if ($config['mf_geo_subdomains_type'] == 'combined') {
                $find = implode('-', $location);
                unset($location);
            } else {
                $find = $location[0];
                array_shift($location);
            }

            $host = str_replace($find . '.', '', $host);
        }

        if ($location) {
            $path = str_replace('/' . implode('/', $location), '', $path);
        }
    }

    /**
     * @deprecated 2.1.0
     */
    public function buildGeoLink($item = false, $clean_url = false, $nolocfix = false, $pathLangCode = '')
    {}

    /**
     * @deprecated 2.1.0
     */
    private function fixUrl($link, $nolocfix = false, $wwwfix = false)
    {}

    /**
     * parse_url() function for multi-bytes character encodings
     *
     * @since 2.1.0
     *
     * @param  string $url       - Url to parse
     * @param  int    $component - Components to retrieve
     * @return array             - Parsed url data
     */
    function parseURL($url, $component = -1)
    {
        $encodedUrl = preg_replace_callback('%[^:/@?&=#]+%usD', function($matches) {
            return urlencode($matches[0]);
        }, $url);

        $parts = parse_url($encodedUrl, $component);

        if (is_array($parts) && count($parts) > 0) {
            foreach ($parts as $name => $value) {
                $parts[$name] = urldecode($value);
            }
        }

        return $parts;
    }

    /**
     * @hook specialBlock
     * @since 2.0.0
     */
    public function hookSpecialBlock()
    {
        if ($this->geo_format) {
            $this->detectLocation();

            if ($GLOBALS['config']['mf_select_interface'] == 'box' && $GLOBALS['blocks']['geo_filter_box']) {
                $this->boxData();
            } elseif ($GLOBALS['config']['mf_select_interface'] == 'usernavbar') {
                $GLOBALS['rlStatic']->addFooterCSS(RL_TPL_BASE . 'components/popup/popup.css');

                $name = $GLOBALS['tpl_settings']['name'];
                $GLOBALS['rlSmarty']->assign('mf_is_nova', boolval(strpos($name, '_nova')));
                $GLOBALS['rlSmarty']->assign('mf_is_flatty', (strpos($name, '_flatty') + 7) == strlen($name));
                $GLOBALS['rlSmarty']->assign('mf_hide_name', in_array($name, ['escort_sun_cocktails_wide', 'general_cragslist_wide']));
            }
        }
    }

    /**
     * @hook pageinfoArea
     * @since 2.0.0
     */
    public function hookPageinfoArea()
    {
        global $page_info;

        $search_page_key = $page_info['Key'] === '404' ? 'home' : $page_info['Key'];

        $this->geo_filter_data['is_filtering'] = in_array($search_page_key, $this->geo_filter_data['filtering_pages']);

        if ($GLOBALS['config']['mod_rewrite']) {
            $this->geo_filter_data['is_location_url'] = in_array($search_page_key, $this->geo_filter_data['location_url_pages']);
        }

        if (false !== strpos($_SERVER['REQUEST_URI'], $GLOBALS['search_results_url'])
            || false !== strpos($_SERVER['REQUEST_URI'], $GLOBALS['advanced_search_url'])
        ) {
            $this->searchResultsPage = true;
        }

        $this->defineAccountPage($page_info);
        $this->prepareLocationData();
        $this->validLocationRedirect();

        /**
         * Reset location and try to redirect to the proper URL if saved location path causes 404,
         * but the requested uri is not map,js or css file
         */
        if ($page_info['Key'] == '404'
            && $this->geo_filter_data['applied_location']
            && $this->geo_filter_data['is_location_url']
            && !preg_match('/\.(map|js|css)$/', $_SERVER['REQUEST_URI'])
        ) {
            $parent = end($this->geo_filter_data['location']);
            $url    = RL_URL_HOME;

            if ($parent['Parent_link']) {
                $url = $this->buildUrl($this->cleanUrl(null, ''), $parent);
            } else {
                $this->resetLocation();
                unset($this->geo_filter_data['applied_location']);
            }

            Util::redirect($url);
        }
    }

    /**
     * @hook phpMetaTags
     * @since 2.0.0
     */
    public function hookPhpMetaTags()
    {
        global $page_info;

        $this->adaptPageInfo();

        // Add canonical to pages with geo filter applied.
        if ($this->geo_filter_data['applied_location']
            && $this->geo_filter_data['is_location_url']
            && !$this->searchResultsPage
        ) {
            $page_info['canonical'] = $this->buildUrl($this->cleanUrl(), $this->geo_filter_data['applied_location']);
        }
    }

    /**
     * Define is the current page is account page
     *
     * @since 2.5.0
     *
     * @param  array  &$pageInfo - Current page info
     */
    private function defineAccountPage(&$pageInfo = [])
    {
        global $config;

        if ($pageInfo['Controller'] != 'account_type') {
            return;
        }

        $account_id = (int) $_GET['id'] ? (int) $_GET['id'] : $_GET['nvar_1'];

        if (!$account_id) {
            return;
        }

        if ($GLOBALS['rlAccount']->getProfile($account_id)) {
            $this->accountPage = true;

            $this->geo_filter_data['is_location_url'] = (
                $config['mf_account_page_filtration'] == 'filter'
                && in_array($pageInfo['Key'], $this->geo_filter_data['location_url_pages'])
            );

            // Reset location to previous due we can't unset location in rewriteGet() method
            if ($_COOKIE['mf_geo_location']
                && $_COOKIE['mf_geo_location'] != 'reset'
                && (!$this->geo_filter_data['is_location_url'] || $config['mf_account_page_filtration'] == 'url')
            ) {
                $_SESSION['geo_filter_location'] = json_decode($_COOKIE['mf_geo_location'], true);

                if ($config['mf_account_page_filtration'] == 'url') {
                    $this->geo_filter_data['applied_location'] = $_SESSION['geo_filter_location'];
                }
            }
        }
    }

    /**
     * Box Data
     *
     * Prepare Data to the Geo Filtering box - get data based on current location and assigns to Smarty
     */
    public function boxData()
    {
        global $rlDb;

        $geo_box_data['levels'] = $this->geo_format['Levels'];

        if ($this->geo_filter_data['applied_location']) {
            $format_id = (int) $this->geo_filter_data['applied_location']['ID'];
        } else {
            $format_id = (int) $this->geo_format['ID'];
        }

        $host = $this->buildHost();
        $data = $GLOBALS['rlMultiField']->getData($format_id, true, $this->geo_format['Order_type']);

        foreach ($data as &$item) {
            $item['Link'] = $this->buildUrl($this->cleanUrl($host), $item);
            unset($item['Path']);
        }

        $GLOBALS['rlHook']->load('mfPhpBoxDataBottom', $this, $data, $host);

        $geo_box_data['levels_data'][] = $data;

        $GLOBALS['rlSmarty']->assign('geo_box_data', $geo_box_data);
    }

    /**
     * Get popular cities
     *
     * @since 2.4.0
     *
     * @param  string $requestLang - Requested language code
     * @param  string $pagePath    - Requested page path/uri
     * @return array               - Cities array
     */
    public function getPopularCities($requestLang = null, $pagePath = null)
    {
        global $config;

        $this->definePathField();
        $this->appliedLocation();

        if (!$this->geo_filter_data['location_listing_fields']) {
            return [];
        }

        if ($config['mf_popular_locations_level']
            && isset($this->geo_filter_data['location_listing_fields'][$config['mf_popular_locations_level']])
        ) {
            $last_level = $config['mf_popular_locations_level'];
        } else {
            end($this->geo_filter_data['location_listing_fields']);
            $last_level = key($this->geo_filter_data['location_listing_fields']);
            reset($this->geo_filter_data['location_listing_fields']);
        }

        $locale = $requestLang ?: $GLOBALS['config']['lang'];
        $add_where = $this->geo_filter_data['applied_location']
        ? " AND `T2`.`Key` != '{$this->geo_filter_data['applied_location']['Key']}' "
        : '';

        $sql = "
            SELECT {$this->selectPathSQL}, `T1`.`{$last_level}` AS `items`, `T2`.`Key`, COUNT(`T1`.`{$last_level}`) AS `count`, `T3`.`Value` AS `name`
            FROM `{db_prefix}listings` AS `T1`
            LEFT JOIN `{db_prefix}multi_formats` AS `T2` ON `T1`.`{$last_level}` = `T2`.`Key`
            LEFT JOIN `{db_prefix}multi_formats_lang_{$locale}` AS `T3` ON `T2`.`Key` = `T3`.`Key`
            WHERE `T1`.`{$last_level}` != '' AND `T1`.`{$last_level}` != '0' AND `T1`.`Status` = 'active'
            AND `T2`.`Status` = 'active' {$add_where}
            GROUP BY `T1`.`{$last_level}`
            ORDER BY `count` DESC
            LIMIT 12
        ";

        $cities = $GLOBALS['rlDb']->getAll($sql);
        $host = $this->buildHost();

        foreach ($cities as &$item) {
            $item['Link'] = $this->buildUrl($this->cleanUrl($host, $pagePath), $item);
        }

        return $cities ?: [];
    }

    /**
     * Build host
     *
     * @since 2.4.0
     *
     * @return string - Current request host
     */
    private function buildHost()
    {
        return $GLOBALS['config']['mf_geo_subdomains'] ? null : $GLOBALS['domain_info']['scheme'] . '://' . $_SERVER['HTTP_HOST'];
    }

    /**
     * Prepare applied location levels, add current location as the latest applied locations level
     * Append 'Parent_link' and 'Parent_path' items to the location levels
     *
     * @since 2.1.0
     */
    private function prepareLocationData()
    {
        // Prepare locations data
        $this->geo_filter_data['location'] = $this->geo_filter_data['location']
            ? array_reverse($this->geo_filter_data['location'])
            : [];

        $host = $GLOBALS['config']['mf_geo_subdomains'] ? null : $GLOBALS['domain_info']['scheme'] . '://' . $_SERVER['HTTP_HOST'];
        $clean_url = $this->cleanUrl($host);

        // Add applied location as the last level
        if ($this->geo_filter_data['applied_location']) {
            $applied_location = $this->geo_filter_data['applied_location'];
            $applied_location['Parent_link'] = $this->buildUrl($clean_url, $prev_path);
            $this->geo_filter_data['location'][] = $applied_location;
        }

        $prev_location = null;
        foreach ($this->geo_filter_data['location'] as &$location) {
            $location['Parent_link'] = $this->buildUrl($clean_url, $prev_location, null, true);
            $location['Parent_path'] = $prev_location['Path'];

            $prev_location = $location;
        }
    }

    /**
     * Modify Where
     *
     * function is to modify sql queries and add addition condition by location
     *
     * @param  $sql   - sql query to add condition to
     * @param  $table - listings or accounts
     */
    public function modifyWhere(&$sql, $table = 'listings')
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        if (!$sql) {
            $sql = &$GLOBALS['sql'];
        }

        $data_key = $table == 'accounts' ? 'location_account_fields' : 'location_listing_fields';
        $data     = $this->geo_filter_data[$data_key];

        // Return if location search already performed from the search form
        if (strpos($sql, key($this->geo_filter_data[$data_key]))) {
            return;
        }

        foreach ($data as $field => $value) {
            if ($value) {
                $sql .= "AND `T1`.`{$field}` = '{$value}' ";
            }
        }
    }

    /**
     * Modify sql queries and add addition condition by location
     *
     * @since 2.6.0
     *
     * @param string $sql   - SQL query to add condition to
     * @param string $table - Listings or accounts
     */
    public function modifyWhereNearby(&$sql, $table = 'listings')
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        if (!$sql) {
            $sql = &$GLOBALS['sql'];
        }

        $data_key = $table == 'accounts' ? 'location_account_fields' : 'location_listing_fields';
        $data     = $this->geo_filter_data[$data_key];

        $lat = $this->geo_filter_data['applied_location']['Latitude'];
        $lng = $this->geo_filter_data['applied_location']['Longitude'];
        $sub_sql = '';

        if ($data) {
            $sub_sql = '(';
            foreach ($data as $field => $value) {
                if ($value) {
                    $sub_sql .= "`T1`.`{$field}` = '{$value}' AND ";
                }
            }
            $sub_sql = substr($sub_sql, 0, -5) . ') OR ';
        }

        $distance = $GLOBALS['config']['mf_nearby_distance'] ?: 150;
        $distance /= 1.609344; // Convert to miles

        $sql .= "AND ({$sub_sql}(3956 * 2 * ASIN(SQRT( POWER(SIN(({$lat} - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) + COS({$lat} * 0.0174532925) * COS(`T1`.`Loc_latitude` * 0.0174532925) * POWER(SIN(({$lng} - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2))) <= {$distance})) ";
    }

    /**
     * Modify select fields part of sql query
     *
     * @since 2.6.0
     *
     * @param string $sql          - SQL query
     * @param string $sqlLastComma - Is last comma exist
     */
    public function modifyFieldSelect(&$sql, $sqlLastComma = true)
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        if (!$this->isNearbySearchReady()) {
            return;
        }

        if (!$sql) {
            $sql = &$GLOBALS['sql'];
        }

        $data_key = $table == 'accounts' ? 'location_account_fields' : 'location_listing_fields';
        $data     = $this->geo_filter_data[$data_key];

        if ($data) {
            $sql .= $sqlLastComma ? '' : ', ';
            $sql .= 'IF (';
            $match_sql = '';

            foreach ($data as $field => $value) {
                if ($value) {
                    $match_sql .= "`T1`.`{$field}` = '{$value}' AND ";
                }
            }

            $match_sql = substr($match_sql, 0, -4);

            $sql .= $match_sql;
            $sql .= ', 1, 0) AS `mf_filter_match`';
            $sql .= $sqlLastComma ? ', ' : '';
        }

        $lat = $this->geo_filter_data['applied_location']['Latitude'];
        $lng = $this->geo_filter_data['applied_location']['Longitude'];

        $sql .= $sqlLastComma ? '' : ', ';
        $sql .= "IF({$match_sql}, 0, (3956 * 2 * ASIN(SQRT(POWER(SIN((" . $lat . " - `T1`.`Loc_latitude`) * 0.0174532925 / 2), 2) + COS(" . $lat . " * 0.0174532925) * COS(`T1`.`Loc_latitude` * 0.0174532925) * POWER(SIN((" . $lng . " - `T1`.`Loc_longitude`) * 0.0174532925 / 2), 2))))) AS `mf_distance`";
        $sql .= $sqlLastComma ? ', ' : '';
    }

    /**
     * Modify order part of sql query
     *
     * @since 2.6.0
     *
     * @param string $sql - SQL query
     */
    public function modifyOrder(&$sql)
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        if (!$this->isNearbySearchReady()) {
            return;
        }

        $sql .= "`mf_filter_match` DESC, `mf_distance` ASC, ";
    }

    /**
     * Define is nearby search mode is available with currently selected location
     *
     * @since 2.6.0
     *
     * @return book - Is available
     */
    public function isNearbySearchReady()
    {
        return $GLOBALS['config']['mf_show_nearby_listings']
        && $this->geo_filter_data['applied_location']['Latitude'] !== '0'
        && $this->geo_filter_data['applied_location']['Longitude'] !== '0'
        && isset($this->geo_filter_data['applied_location']['Latitude']);
    }

    /**
     * Recount account listings based on selected user location
     *
     * @since 2.0.0
     *
     * @param  string &$sql - initial sql query
     */
    public function recountAccountListings(&$sql)
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
        ) {
            return;
        }

        $data = $this->geo_filter_data['location_listing_fields'];

        if ($data) {
            $count_sql = "
                SELECT COUNT(`ID`) FROM `{db_prefix}listings`
                WHERE `Account_ID` = `T1`.`ID` AND `Status` = 'active'
            ";

            if ($GLOBALS['plugins']['listing_status']) {
                $count_sql .= "AND `Sub_status` <> 'invisible' ";
            }

            foreach ($data as $field => $value) {
                if ($value) {
                    $count_sql .= "AND `{$field}` = '{$value}' ";
                }
            }

            $sub_sql = ", IF (`Listings_count` = 0, 0, ({$count_sql})) AS `Listings_count` ";

            if (strpos($sql, '`Listings_count`')) {
                $sql = str_replace(', `Listings_count`', $sub_sql, $sql);
            } else {
                $sql .= $sub_sql;
            }
        }
    }

    /**
     * Smarty Fetch Hook
     * Replace html (smarty) content and change links based on pages enabled for geo filtering
     *
     * @since 2.4.0 $resourceName second parameter added
     *
     * @param string $html - smarty html content
     */
    public function smartyFetchHook(&$html, &$resourceName)
    {
        global $config, $domain_info;

        // Ignore compilation of files (prevent duplicate file compiling)
        if (in_array(str_replace(RL_ROOT, '', $resourceName), $this->noCompileFiles)) {
            return;
        }

        /**
         * Cache for short links replace data
         * @var array
         */
        static $short_links_data = [];

        if ($this->geo_filter_data['applied_location']['Path']) {
            // Prevent replace in listing urls
            $html = preg_replace('/(\"|\')http([^\"]+\-[0-9]+\.html)(\"|\')/sm', '"httplocfix$2"', $html);

            // Prevent replace in "search results" urls
            $search_pattern = $GLOBALS['search_results_url'] . '|' . $GLOBALS['advanced_search_url'];
            $search_pattern = str_replace(['-', '/'], ['\-', '\/'], $search_pattern);
            $html = preg_replace('/(\"|\')http([^\'\"]+)('. $search_pattern .')(\.html)?(\"|\')/sm', '"httplocfix$2$3$4"', $html);

            $lang_code = defined('RL_LANG_CODE') ? RL_LANG_CODE : $_REQUEST['lang'];
            $home_url  = defined('SEO_BASE') ? SEO_BASE : RL_URL_HOME;
            $locale    = $lang_code == $config['lang'] ? '' : $lang_code . '/';

            foreach ($this->geo_filter_data['location_url_pages'] as $page_key) {
                /**
                 * Adapt FBB page path using `path` smarty variable assigned in associated FBB box
                 *
                 * @since 2.5.0
                 * @todo - Remove once te FBB plugin will generate separated page for every FBB page
                 */
                if ($page_key == 'listings_by_field') {
                    if (!$page_path = $GLOBALS['rlSmarty']->_tpl_vars['path']) {
                        continue;
                    }
                } else {
                    $page_path = $GLOBALS['pages'][$page_key];
                }

                if ($page_path) {
                    $type_key = str_replace('lt_', '', $page_key);
                    $type = $GLOBALS['rlListingTypes']->types[$type_key];

                    switch ($type['Links_type']) {
                        case 'subdomain':
                            $subdomain_host = $domain_info['scheme'] . '://' . $page_path . '.' . $domain_info['host'] . '/';
                            $replace = $this->buildUrl($subdomain_host, $this->geo_filter_data['applied_location']);

                            $html = str_replace($subdomain_host . $locale, $replace, $html);
                            break;

                        case 'short':
                            if (!$short_links_data[$type_key]) {
                                foreach ($this->getCategoriesByType($type_key) as $category) {
                                    if (!$category['Path']) {
                                        continue;
                                    }

                                    $clean_page_url = $this->cleanUrl(null, '/' . $category['Path']);

                                    $short_links_data[$type_key]['find'][]    = $home_url . $category['Path'];
                                    $short_links_data[$type_key]['replace'][] = $this->buildUrl($clean_page_url, $this->geo_filter_data['applied_location']);
                                }
                            }

                            $html = str_replace($short_links_data[$type_key]['find'], $short_links_data[$type_key]['replace'], $html);

                        case 'full':
                        default:
                            $find = $home_url . $page_path;
                            $clean_page_url = $this->cleanUrl(null, '/' . $page_path);
                            $replace = $this->buildUrl($clean_page_url, $this->geo_filter_data['applied_location']);

                            $html = str_replace($find, $replace, $html);
                            break;
                    }
                } elseif ($page_path === '') {
                    $find = $home_url . '"';
                    $clean_page_url = $this->cleanUrl(null, '/');
                    $replace = $this->buildUrl($clean_page_url, $this->geo_filter_data['applied_location']) . '"';

                    $html = str_replace($find, $replace, $html);
                }
            }
        }

        $html = str_replace('locfix', '', $html);
    }

    /**
     * Get (environment availability lookup is in priority) categories data by type
     *
     * @since 2.1.0
     *
     * @param  string $type - Requested listing type key
     * @return array        - Categories data
     */
    private function getCategoriesByType($type)
    {
        // Get from the categories block on the home page
        if ($box_categories = &$GLOBALS['rlSmarty']->_tpl_vars['box_categories'][$type]) {
            // All in one mode
            if (isset($box_categories[0]['Links_type'])) {
                foreach ($box_categories as $box_type) {
                    if ($box_type['Key'] == $type) {
                        return $box_type['sub_categories'];
                    }
                }
            }
            // Default mode
            else {
                return $GLOBALS['rlSmarty']->_tpl_vars['box_categories'][$type];
            }
        }
        // Get from the categories block on listing type page
        elseif ($GLOBALS['rlSmarty']->_tpl_vars['categories']
            && $GLOBALS['rlSmarty']->_tpl_vars['listing_type']['Key'] == $type
        ) {
            return $GLOBALS['rlSmarty']->_tpl_vars['categories'];
        }
        // Fetch from system cache or db
        else {
            return Category::getCategories($type);
        }
    }

    /**
     * @hook ajaxRequest
     * @since 2.0.0
     */
    public function hookAjaxRequest(&$out, $request_mode, $request_item, $request_lang)
    {
        global $rlDb, $config;

        if ($_REQUEST['mf_filtering']) {
            $this->geo_filter_data['is_filtering'] = true;
        }

        switch ($request_mode) {
            case 'mfApplyLocation':
                if ($_REQUEST['key']) {
                    $condition = "`T1`.`Key` = '{$_REQUEST['key']}'";
                } elseif ($request_item) {
                    $path_field = $config['mf_multilingual_path'] ? 'Path_' . $request_lang : 'Path';
                    $condition = "`T1`.`{$path_field}` = '{$request_item}'";
                } else {
                    $this->resetLocation();
                }

                if ($condition) {
                    $sql = "SELECT `T1`.*, `T2`.`Value` AS `name`";

                    if ($config['mf_multilingual_path']) {
                        $sql .= ", IF(`Path_{$request_lang}` != '', `Path_{$request_lang}`, `Path_{$config['lang']}`) AS `Path`";
                    }

                    $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
                    $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_{$request_lang}` AS `T2` ON `T1`.`Key` = `T2`.`Key` ";
                    $sql .= "WHERE " . $condition;
                    $sql .= "LIMIT 1";

                    $location = $rlDb->getRow($sql);

                    $_SESSION['geo_filter_location'] = $location;
                    $this->saveLocation($_SESSION['geo_filter_location']);
                }

                $out = array(
                    'status' => 'OK',
                );
                break;

            case 'mfGeoAutocomplete':
                $out = array(
                    'status'  => 'OK',
                    'results' => $this->geoAutocomplete($request_item, $request_lang, $_REQUEST['currentLocation'], $_REQUEST['currentPage'])
                );
                break;

            case 'mfGetCities':
                $out = array(
                    'status'  => 'OK',
                    'results' => $this->getPopularCities($request_lang, $_REQUEST['path'])
                );
                break;
        }
    }

    /**
     * Geo autocomplete handler
     *
     * @since 2.0.0 $current_location parameter added
     *
     * @param  string $query            - Autocomplete query
     * @param  string $lang             - Requested language code
     * @param  string $currentLocation  - Format key of current location
     * @param  string $currentPage      - Current page url the ajax was called from
     * @return array                    - Match locations
     */
    public function geoAutocomplete($query = '', $lang = false, $currentLocation = null, $currentPage = null)
    {
        global $config, $rlDb, $rlLang;

        $query = Valid::escape($query);
        $currentLocation = Valid::escape($currentLocation);

        $sql = "
            SELECT `Value`, `Key`, CHAR_LENGTH(`Value`) AS `char_length`,
            IF (`Key` LIKE '{$currentLocation}%', 1, 0) AS `relevance`
            FROM `{db_prefix}multi_formats_lang_{$lang}`
            WHERE `Value` LIKE '{$query}%'
            AND `Key` != '{$currentLocation}' AND `Key` LIKE '{$this->geo_format['Key']}\_%'
            ORDER BY `relevance` DESC, `char_length` ASC
            LIMIT {$config['mf_geo_autocomplete_limit']}
        ";

        $locations = $rlDb->getAll($sql);

        if ($locations) {
            $this->definePathField();

            $page_data  = $this->parseURL(urldecode($currentPage));
            $clean_url  = $this->cleanUrl($page_data['scheme'] . '://' . $page_data['host'], $page_data['path']);

            $path_data  = array();
            $items_data = array();

            foreach ($locations as $key => &$item) {
                $sql = "
                    SELECT {$this->selectPathSQL}, `Parent_ID`, `Parent_IDs`
                    FROM `{db_prefix}multi_formats`
                    WHERE `Key` = '{$item['Key']}' AND `Status` = 'active'
                    LIMIT 1
                ";
                $location = $rlDb->getRow($sql);

                if (!$location) {
                    unset($locations[$key]);
                    continue;
                }

                $item['Path'] = $location['Path'];
                $item['href'] = $this->buildUrl($clean_url, $location, $lang);

                if ($location['Parent_IDs']) {
                    $parent_ids = explode(',', $location['Parent_IDs']);
                    $levels = count($parent_ids);

                    $item_names = array();
                    for ($i = 0; $i < $levels; $i++) {
                        $parent_id = $parent_ids[$i];

                        // Skip the top parent item (data format)
                        if ($parent_id === $this->geo_format['ID']) {
                            continue;
                        }

                        if (!$item_name = $items_data[$parent_id]) {
                            $parent_item = $rlDb->fetch('*', array('ID' => $parent_ids[$i]), null, null, 'multi_formats', 'row');
                            $item_name = $rlDb->getOne('Value', "`Key` = '{$parent_item['Key']}'", 'multi_formats_lang_' . $lang);

                            $items_data[$parent_id] = $item_name;
                        }

                        $item_names[] = $item_name;
                    }

                    if ($item_names) {
                        $item['Value'] .= ', ' . implode(', ', $item_names);
                    }
                }

                $item['Key'] = str_replace('data_formats+name+', '', $item['Key']);
            }
        }

        return array_values($locations);
    }

    /**
     * Reset location and save cookies
     *
     * @since 2.0.0
     */
    private function resetLocation()
    {
        unset($_SESSION['geo_filter_location']);
        $_COOKIE['mf_geo_location'] = 'reset';
        $GLOBALS['reefless']->createCookie('mf_geo_location', 'reset', $this->cookieTime);
    }

    /**
     * Save selected location in cookies
     *
     * @since 2.0.0
     *
     * @param array $location - selected location data
     */
    private function saveLocation($location)
    {
        if ($this->detailsPage) {
            return;
        }

        $GLOBALS['reefless']->createCookie('mf_geo_location', json_encode($location), $this->cookieTime);
    }

    /**
     * @hook phpCategoriesGetCategoriesCache
     * @since 2.0.0
     */
    public function hookPhpCategoriesGetCategoriesCache(&$param1)
    {
        $this->adaptCategories($param1);
    }

    /**
     * @hook phpCategoriesGetCategories
     * @since 2.0.0
     */
    public function hookPhpCategoriesGetCategories(&$param1)
    {
        $this->adaptCategories($param1);
    }

    /**
     * @hook listingsModifyWhere
     * @since 2.0.0
     */
    public function hookListingsModifyWhere(&$sql)
    {
        if ($this->isNearbySearchReady()) {
            $this->modifyWhereNearby($sql);
        } else {
            $this->modifyWhere($sql);
        }
    }

    /**
     * @hook modifyWhereByAccount
     * @since 2.0.0
     */
    public function hookListingsModifyWhereByAccount(&$sql)
    {
        if ($GLOBALS['config']['mf_account_page_filtration'] == 'filter') {
            $this->modifyWhere($sql);
        }
    }

    /**
     * @hook listingsModifyWhereByPeriod
     * @since 2.0.0
     */
    public function hookListingsModifyWhereByPeriod(&$sql)
    {
        if ($this->isNearbySearchReady()) {
            $this->modifyWhereNearby($sql);
        } else {
            $this->modifyWhere($sql);
        }
    }

    /**
     * @hook listingsModifyWhereFeatured
     * @since 2.0.0
     */
    public function hookListingsModifyWhereFeatured(&$sql)
    {
        if ($this->isNearbySearchReady()) {
            $this->modifyWhereNearby($sql);
        } else {
            $this->modifyWhere($sql);
        }
    }

    /**
     * @hook listingsModifyField
     * @since 2.6.0
     */
    public function hookListingsModifyField(&$sql)
    {
        $this->modifyFieldSelect($sql);
    }

    /**
     * @hook listingsModifyOrder
     * @since 2.6.0
     */
    public function hookListingsModifyOrder(&$sql)
    {
        $this->modifyOrder($sql);
    }

    /**
     * @hook listingsModifyFieldByPeriod
     * @since 2.6.0
     */
    public function hookListingsModifyFieldByPeriod(&$sql)
    {
        $this->modifyFieldSelect($sql);
    }

    /**
     * @hook listingsModifyOrderByPeriod
     * @since 2.6.0
     */
    public function hookListingsModifyOrderByPeriod(&$sql)
    {
        $this->modifyOrder($sql);
    }

    /**
     * @hook listingsModifySelectFeatured
     * @since 2.6.0
     */
    public function hookListingsModifySelectFeatured(&$sql)
    {
        $this->modifyFieldSelect($sql, false);
    }

    /**
     * @hook listingsModifyOrderFeatured
     * @since 2.6.0
     */
    public function hookListingsModifyOrderFeatured(&$sql)
    {
        $this->modifyOrder($sql);
    }

    /**
     * @hook listingTop
     * @since 2.6.0
     */
    public function hookListingTop()
    {
        if (!$this->isNearbySearchReady()) {
            return;
        }

        global $rlSmarty;

        static $match_listings = false;

        $listing = &$rlSmarty->_tpl_vars['listing'];

        if ($listing['mf_filter_match'] === '0') {
            if (!$match_listings && !$GLOBALS['pInfo']['current']) {
                $rlSmarty->assign('mf_nearby_listings_only', true);
            }

            // Show the nearby header once
            if (!defined('MF_NEARBY_HEADER')) {
                $rlSmarty->display(RL_PLUGINS . 'multiField' . RL_DS . 'nearby_header.tpl');
                define('MF_NEARBY_HEADER', true);
            }

            // Prevent displaying the fields in nearby listings
            foreach($listing['fields'] as &$field) {
                $field['Details_page'] = false;
            }
        } else {
            $match_listings = true;
        }
    }

    /**
     * @hook listingAfterFields
     * @since 2.6.0
     */
    public function hookListingAfterFields()
    {
        if (!$this->isNearbySearchReady()) {
            return;
        }

        if (!defined('MF_NEARBY_HEADER')) {
            return;
        }

        if (!$this->geo_filter_data['location_listing_fields']) {
            return;
        }

        global $rlSmarty;

        $listing = &$rlSmarty->_tpl_vars['listing'];
        $location = [];

        foreach ($this->geo_filter_data['location_listing_fields'] as $key => $value) {
            $field = array(
                'Key' => $key,
                'Type' => 'select',
                'Condition' => $this->geo_format['Key']
            );

            $item = $GLOBALS['rlCommon']->adaptValue(
                $field,
                $listing[$key],
                'listing',
                false,
                true,
                false,
                false,
                false,
                false,
                null,
                $listing['Listing_type']
            );

            if ($item) {
                $location[] = $item;
            }
        }

        if ($rlSmarty->_tpl_vars['listing']['mf_filter_match'] === '0' && $location) {
            echo implode(', ', array_splice(array_reverse($location), 0, 2));
        }
    }

    /**
     * @hook smartyFetchHook
     * @since 2.0.0
     */
    public function hookSmartyFetchHook(&$html, &$resourceName)
    {
        if ($this->geo_filter_data
            && $GLOBALS['config']['mod_rewrite']
            && $this->geo_format
            && !defined('REALM')
        ) {
            $this->smartyFetchHook($html, $resourceName);
        }
    }

    /**
     * @hook boot
     * @since 2.0.0
     */
    public function hookBoot()
    {
        $this->adaptCategoryMetaData();

        if (version_compare($GLOBALS['config']['rl_version'], '4.7.1', '>')) {
            return;
        }
    }

    /**
     * @deprecated 2.1.0
     */
    public function fixHreflang()
    {}

    /**
     * @deprecated 2.5.0
     */
    public function hookAccountsSearchDealerSqlWhere(&$sql)
    {}

    /**
     * @hook accountsGetDealersByCharSqlWhere
     * @since 2.0.0
     */
    public function hookAccountsGetDealersByCharSqlWhere(&$sql)
    {
        $this->modifyWhere($sql, 'accounts');

        if ($GLOBALS['config']['mf_account_page_filtration'] == 'filter') {
            $this->recountAccountListings($sql);
        }
    }

    /**
     * @hook listingsModifyPreSelect
     * @since 2.0.0
     */
    public function hookListingsModifyPreSelect(&$dbcount = false)
    {
        if ($this->geo_filter_data['applied_location']) {
            $dbcount = false;
        }
    }

    /**
     * @hook phpRecentlyAddedModifyPreSelect
     * @since 2.0.0
     */
    public function hookPhpRecentlyAddedModifyPreSelect(&$dbcount = false)
    {
        if ($this->geo_filter_data['applied_location']) {
            $dbcount = false;
        }
    }

    /**
     * @hook hookPhpUrlBottom
     * @since 2.0.0
     */
    public function hookPhpUrlBottom(&$url, $mode, $data, $custom_lang)
    {
        global $config;

        if (!$config['mod_rewrite']) {
            return;
        }

        if ($mode == 'listing') {
            if (!(is_integer($data) || (is_array($data) && $data['ID']))) {
                return $url;
            }

            if ($GLOBALS['ref_url']) {
                return $url;
            }

            $this->prepareLocationFields();
            $this->definePathField();

            if ($config['mf_listing_geo_urls'] && $this->geo_filter_data['location_listing_fields']) {
                // Validate and add missing required data
                if (is_integer($data)) {
                    $data = $GLOBALS['rlDb']->fetch('*', ['ID' => $data], null, 1, 'listings', 'row');
                } elseif (is_array($data)) {
                    $missing_keys = array_diff_key($this->geo_filter_data['location_listing_fields'], $data);

                    if ($missing_keys) {
                        $missing_values = $GLOBALS['rlDb']->fetch(
                            array_keys($missing_keys),
                            ['ID' => $data['ID']],
                            null, 1, 'listings', 'row'
                        );
                        if ($missing_values) {
                            $data = array_merge($data, $missing_values);
                        }
                    }
                }

                $url = $this->buildListingUrl($url, $data, $custom_lang);
            }
        } else {
            if (!$this->isCallFunction('getPersonalAddress') && !$GLOBALS['reefless']->preventUrlModifying) {
                $page_key = $mode == 'category' ? 'lt_' . $data['Type'] : $data['key'];

                if ($this->geo_filter_data['location_url_pages']
                    && in_array($page_key, $this->geo_filter_data['location_url_pages'])
                ) {
                    $url = $this->buildUrl($url, $this->geo_filter_data['applied_location'], $custom_lang);
                }
            }
        }
    }

    /**
     * Define is current method called by another method
     *
     * @since 2.5.0
     *
     * @param  string  $name - Caller method name
     * @return boolean       - Is call or not
     */
    public function isCallFunction($name)
    {
        $match = false;

        foreach (debug_backtrace() as $item) {
            if ($item['function'] == $name) {
                $match = true;
                break;
            }
        }

        return $match;
    }

    /**
     * Build listing url with geo location
     *
     * @param  string $url          - default listing url
     * @param  array  $data         - listing data with location fields
     * @param  string $lang_code    - language code to add to the url
     * @return string               - listing url with location path, hreflang generation mode
     */
    public function buildListingUrl($url, $data, $lang_code = '')
    {
        return $this->buildResourceUrl($url, $data, $lang_code);
    }

    /**
     * Build listing or account url with geo location
     *
     * @since 2.5.0
     *
     * @param  string $url          - Default listing or account
     * @param  array  $data         - Resource data with location fields
     * @param  string $lang_code    - Language code to add to the url
     * @param  string $mode         - Build url mode, listing or account
     * @return string               - Resource url with location path, hreflang generation mode
     */
    public function buildResourceUrl($url, $data, $lang_code = '', $mode = 'listing')
    {
        static $data_formats = array();

        $path_sql = $this->selectPathSQL;

        // Build custom sql path select for custom lang
        if ($GLOBALS['config']['mf_multilingual_path'] && $lang_code) {
            $system_path = 'Path_' . $GLOBALS['config']['lang'];
            $path_field  = 'Path_' . $lang_code;
            $path_sql    = "IF(`{$path_field}` != '', `{$path_field}`, `{$system_path}`) AS `Path`";
        }

        $fields = $mode == 'listing' ? 'location_listing_fields' : 'location_account_fields';

        foreach (array_reverse($this->geo_filter_data[$fields]) as $field_key => $value) {
            if ($data[$field_key]) {
                // Get from the cache
                if (array_key_exists($data[$field_key], $data_formats)) {
                    $geo_path = $data_formats[$data[$field_key]];
                }
                // Get from the db and put to the cache
                else {
                    $sql = "
                        SELECT *, {$path_sql} FROM `{db_prefix}multi_formats`
                        WHERE `Key`= '{$data[$field_key]}'
                        LIMIT 1
                    ";
                    $geo_path = $GLOBALS['rlDb']->getRow($sql);
                    $data_formats[$data[$field_key]] = $geo_path;
                }
                break;
            }
        }

        if (!$geo_path) {
            return $url;
        }

        $resource_url = $this->buildUrl($url, $geo_path, $lang_code);

        return $resource_url;
    }

    /**
     * Apply the location data to the url
     *
     * @since 2.2.0 - 4th parameter ($noLocfix) renamed
     * @since 2.1.0
     *
     * @param  string  $url      - URL to apply location
     * @param  array   $location - Location data array, 'Path' index is requied
     * @param  string  $langCode - Langeuage code
     * @param  bool    $locfix   - Add locfix postfix to the url to avoid it replace by smartyFetchHook()
     * @return string            - New url with location applied
     */
    public function buildUrl($url, $location, $langCode = null, $locfix = false)
    {
        global $config;

        $langCode = $langCode ?: RL_LANG_CODE;

        $parsed = $this->parseURL($url);
        $scheme = $parsed['scheme'] . '://';
        $path   = $parsed['path'];
        $locale = $langCode == $config['lang'] ? '' : $langCode . '/';

        if ($langCode) {
            $path = str_replace("/{$langCode}/", '/', $path);
        }

        $location_path = $location['Path'];

        if ($config['mf_multilingual_path']) {
            if ($location['Path_' . $langCode]) {
                $location_path = $location['Path_' . $langCode];
            } elseif ($location[$this->systemPathField]) {
                $location_path = $location[$this->systemPathField];
            }
        }

        $locfix  = $locfix ? 'locfix' : '';

        if ($config['mf_geo_subdomains']) {
            if ($location) {
                $parsed['host'] = preg_replace('/^www\./', '', $parsed['host']);
            }

            switch ($config['mf_geo_subdomains_type']) {
                case 'combined':
                case 'unique':
                    $location_path = str_replace('/', '-', $location_path);
                    $subdomain     = $location_path ? $location_path . '.' : '';
                    $host          = $scheme . $subdomain . $parsed['host'];
                    $uri           = $locale . $path;

                    $this->validateUri($uri);

                    $new_url = $host . $locfix . '/' . $uri;
                    break;

                case 'mixed':
                    $location_data = explode('/', $location_path);
                    $subdomain     = $location_data[0] ? array_shift($location_data) . '.' : '';
                    $host          = $scheme . $subdomain . $parsed['host'];

                    $this->fixDir($path, $host);

                    $location_path = implode('/', $location_data);
                    $uri           = $locale . $location_path .  $path;

                    $this->validateUri($uri);

                    $new_url = $host . $locfix . '/' . $uri;
                    break;
            }
        } else {
            $host          = $scheme . $parsed['host'];

            $this->fixDir($path, $host);

            $location_path = $location_path ? $location_path . '/' : '';
            $uri           = $locale . $location_path . $path;

            $this->validateUri($uri);

            $new_url = $host . $locfix . '/' . $uri;
        }

        return $new_url;
    }

    /**
     * Validate uri: replace double slashes to single and prevent single slash string
     *
     * @since 2.1.0
     *
     * @param  string &$uri - uri
     */
    private function validateUri(&$uri)
    {
        $uri = preg_replace('/([\/]+)/', '/', $uri);
        $uri = ltrim($uri, '/');
    }

    /**
     * Fix script subdirectory name position in url by moving it from the url to the host
     *
     * @since 2.1.0
     *
     * @param string &$path - Url path or uri
     * @param string &$host - Url host
     */
    private function fixDir(&$path, &$host = null)
    {
        if (RL_DIR === '') {
            return;
        }

        $rl_dir = trim(RL_DIR, '/');

        if (isset($host)) {
            $host .= '/'. $rl_dir;
        }

        if (strpos(trim($path, '/'), $rl_dir) === 0) {
            $path = substr($path, strlen(RL_DIR));
        }
    }

    /**
     * @deprecated 2.2.0
     */
    private function isListingRedirect() {}

    /**
     * @hook phpOriginalUrlRedirect
     * @since 2.0.0
     */
    public function hookPhpOriginalUrlRedirect(&$request_uri, &$real_uri, &$real_base, &$request_base)
    {
        if ($this->geo_filter_data['is_location_url']
            && $this->geo_filter_data['applied_location']['Path']
        ) {
            if (!is_numeric(strpos($real_uri, $this->geo_filter_data['applied_location']['Path']))) {
                $real_uri = $this->geo_filter_data['applied_location']['Path'] . '/' . $real_uri;
            }
        }
        elseif ($this->detailsPage) {
            global $listing_data;

            $real_url = $GLOBALS['reefless']->url('listing', $listing_data);

            /**
             * @todo - Remove this code once the parseUrl() php function will be replaced with notlatin
             *         characters reacting in rlListings->originalUrlRedirect() method.
             */
            $parsed_real = $this->parseURL($real_url);

            $real_uri = ltrim($parsed_real['path'], '/');
            $real_base = $parsed_real['scheme'] . '://' . $this->idnToUtf8($parsed_real['host']) . '/' . $rlDir;
            /* todo end */

            /**
             * @todo - Remove this code once the $request_base is permanently converted to utf8 in
             *         rlListings->originalUrlRedirect() method.
             */
            $parsed_request = $this->parseURL($request_base);
            $request_base = $parsed_request['scheme'] . '://' . $this->idnToUtf8($parsed_request['host']) . '/';
        }
    }

    /**
     * @deprecated 2.1.0
     */
    public function hookPhpAbstractStepsBuildStepUrl(&$url)
    {}

    /**
     * @deprecated 2.1.0
     */
    public function hookReeflessRedirctVars(&$request_url, $vars, $http_response_code)
    {}

    /**
     * Remove locfix from the url in redirect
     *
     * @hook utilsRedirectURL
     * @since 2.6.1
     */
    public function hookUtilsRedirectURL(&$url)
    {
        $url = str_replace('locfix', '', $url);
    }

    /**
     * @hook phpValidateUserLocation
     * @since 2.0.0
     */
    public function hookPhpValidateUserLocation($location, &$errors, &$errors_trigger, $wrapper)
    {
        if ($GLOBALS['rlDb']->getOne(
            'ID',
            "`{$this->systemPathField}` = '{$location}' AND `Key` LIKE '{$this->geo_format['Key']}%'",
            "multi_formats"
        )) {
            $errors_trigger = true;
            $errors = $GLOBALS['lang']['personal_address_in_use'];
        }
    }

    /**
     * @hook apPhpAccountsValidate
     * @since 2.0.0
     */
    public function hookApPhpAccountsValidate()
    {
        global $config;

        $location = (string) $GLOBALS['profile_data']['location'];

        if (!$location || !$this->geo_format) {
            return;
        }

        $this->definePathField();

        if ($GLOBALS['rlDb']->getOne(
            'ID',
            "`{$this->systemPathField}` = '{$location}' AND `Key` LIKE '{$this->geo_format['Key']}%'",
            "multi_formats"
        )) {
            $GLOBALS['errors'][] = $GLOBALS['lang']['personal_address_in_use'];
            $GLOBALS['error_fields'][] = "profile[location]";
        }
    }

    /**
     * @hook phpSearchOnMapDefaultAddress
     * @since 2.0.0
     */
    public function hookPhpSearchOnMapDefaultAddress(&$default_map_location)
    {
        if ($this->geo_filter_data['applied_location'] && $this->geo_filter_data['is_filtering']) {
            $default_map_location = '';
            foreach ($this->geo_filter_data['location'] as $loc_item) {
                $default_map_location .= $loc_item['name'] . ', ';
            }
            $default_map_location = trim($default_map_location, ', ');

            Valid::escape($default_map_location);
        }
    }

    /**
     * @hook phpGetProfileModifyField
     * @since 2.0.0
     */
    public function hookPhpGetProfileModifyField(&$sql, &$edit_mode)
    {
        if ($edit_mode) {
            return;
        }

        if ($GLOBALS['config']['mf_account_page_filtration'] == 'filter') {
            $this->recountAccountListings($sql);
        }
    }

    /**
     * Replace location patterns in meta category phrases before rlListings::replaceMetaFields() call,
     * because that method will remove all location patterns from the phrases
     *
     * @hook listingDetailsBeforeMetaData
     */
    public function hookListingDetailsBeforeMetaData()
    {
        global $lang, $cat_bread_crumbs, $listing_data, $rlListingTypes;

        $this->prepareListingLocationData();

        $meta = array('meta_description', 'meta_keywords', 'meta_title');
        foreach ($meta as $area) {
            $pattern_found = false;

            foreach (array_reverse($cat_bread_crumbs) as $category) {
                if ($lang['categories+listing_' . $area . '+' . $category['Key']]) {
                    $this->adaptLocString($lang['categories+listing_' . $area . '+' . $category['Key']]);
                    $pattern_found = true;
                    break;
                }
            }
        }

        // Search in general category
        if (!$pattern_found && $general_id = $rlListingTypes->types[$listing_data['Cat_type']]['Cat_general_cat']) {
            $general_key = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$general_id}", 'categories');

            foreach ($meta as $area) {
                if ($lang['categories+listing_' . $area . '+' . $general_key]) {
                    $this->adaptLocString($lang['categories+listing_' . $area . '+' . $general_key]);
                }
            }
        }
    }

    /**
     * @hook sitemapAddPluginUrls
     * @since 2.3.0
     *
     * @param array $urls
     */
    public function hookSitemapAddPluginUrls(&$urls = [])
    {
        global $reefless, $config, $rlDb, $rlListingTypes;

        $this->pages = $config['mf_location_url_pages'] ? explode(',', $config['mf_location_url_pages']) : [];

        if (!$config['mf_urls_in_sitemap'] || !$this->pages || !$this->geo_format) {
            return;
        }

        set_time_limit(0);

        $reefless->loadClass('MultiField', null, 'multiField');

        if (!is_object($GLOBALS['rlSmarty'])) {
            require RL_LIBS . 'smarty/Smarty.class.php';
            $reefless->loadClass('Smarty');
        }

        $this->init();

        $this->geoUrls           = [];
        $this->multilingualPaths = $config['mf_multilingual_path'] || $config['multilingual_paths'];
        $this->dbListingFields   = array_keys($this->geo_filter_data['location_listing_fields']);
        $this->dbAccountFields   = array_keys($this->geo_filter_data['location_account_fields']);

        $rlDb->outputRowsMap    = ['Key', 'Controller'];
        $this->pagesControllers = $rlDb->fetch(
            ['Key', 'Controller'],
            ['Status' => 'active'],
            null, null, 'pages'
        );

        // Reset selected location if it's exist
        if ($this->geo_filter_data['applied_location']) {
            unset(
                $this->geo_filter_data['applied_location'],
                $this->geo_filter_data['location'],
                $this->geo_filter_data['location_keys']
            );
        }

        // Get all info about selected locations in listings/accounts for filtering not selected location
        if ($config['mf_urls_in_sitemap'] === 'not_empty') {
            $foundData = [];
            foreach ($this->geo_filter_data['location_listing_fields'] as $listingFieldKey => $listingField) {
                foreach ($this->pages as $pageKey) {
                    if ('listing_type' !== $this->pagesControllers[$pageKey]) {
                        continue;
                    }

                    $listingTypeKey = str_replace('lt_', '', $pageKey);

                    $foundData['listings'][$listingTypeKey][$listingFieldKey] = $rlDb->getAll(
                        "SELECT `T1`.`{$listingFieldKey}` FROM `{db_prefix}listings` AS `T1`
                         LEFT JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID`
                         WHERE `T1`.`{$listingFieldKey}` <> ''
                         AND `T1`.`Status` = 'active'
                         AND `T2`.`Type` = '{$listingTypeKey}' 
                         GROUP BY `T1`.`{$listingFieldKey}`",
                        [false, $listingFieldKey]
                    );
                }
            }

            foreach ($this->geo_filter_data['location_account_fields'] as $accountFieldKey => $accountField) {
                foreach ($this->pages as $pageKey) {
                    if ('account_type' !== $this->pagesControllers[$pageKey]) {
                        continue;
                    }

                    $accountTypeKey = str_replace('at_', '', $pageKey);

                    $foundData['accounts'][$accountTypeKey][$accountFieldKey] = $rlDb->getAll(
                        "SELECT `{$accountFieldKey}` FROM `{db_prefix}accounts`
                         WHERE `{$accountFieldKey}` <> ''
                         AND `Status` = 'active'
                         AND `Type` = '{$accountTypeKey}'
                         GROUP BY `{$accountFieldKey}`",
                        [false, $accountFieldKey]
                    );
                }
            }

            $this->foundData = $foundData;
            unset($foundData);

            if ($rlListingTypes) {
                $this->foundData['recentlyAddedTypeKey'] = reset($rlListingTypes->types)['Key'];
            }
        }

        $this->buildLocationUrlsForSitemap($this->geo_format);
        unset($this->foundData, $this->dbAccountFields, $this->dbListingFields, $this->pagesControllers, $this->pages);

        if ($this->geoUrls) {
            if ($this->multilingualPaths) {
                $urls['multiField'] = $this->geoUrls;
                unset($this->multilingualPaths);
            } else {
                $urls = array_merge((array) $urls, $this->geoUrls);
            }
        }

        unset($this->geoUrls);
    }

    /**
     * Build urls with locations for adding to sitemap (recursively)
     *
     * @since 2.3.0
     *
     * @param  $parentLocation
     * @return mixed
     */
    public function buildLocationUrlsForSitemap($parentLocation)
    {
        $parentLocationID = (int) $parentLocation['ID'];

        if (!$parentLocationID) {
            return false;
        }

        $locations = $GLOBALS['rlMultiField']->getData($parentLocationID, true, null, $this->multilingualPaths);

        if (!$locations || !$this->pages) {
            return false;
        }

        global $config, $rlSitemap, $reefless;

        $firstLocation = reset($locations);
        $level = empty($firstLocation['Parent_IDs']) ? 0 : (substr_count($firstLocation['Parent_IDs'], ',') + 1);

        foreach ($locations as $location) {
            foreach ($this->pages as $page) {
                $addUrl         = true;
                $pageController = $this->pagesControllers[$page];

                if ($config['mf_urls_in_sitemap'] === 'not_empty') {
                    switch ($pageController) {
                        case 'listing_type':
                        case 'recently_added':
                            $dbField = $this->dbListingFields[$level];

                            if ($pageController === 'listing_type') {
                                $listingTypeKey = str_replace('lt_', '', $page);
                            } elseif ($pageController === 'recently_added') {
                                $listingTypeKey = $this->foundData['recentlyAddedTypeKey'];
                            }

                            $addUrl = in_array(
                                $location['Key'],
                                $this->foundData['listings'][$listingTypeKey][$dbField]
                            );
                            break;

                        case 'account_type':
                            $dbField        = $this->dbAccountFields[$level];
                            $accountTypeKey = str_replace('at_', '', $page);
                            $addUrl         = in_array(
                                $location['Key'],
                                $this->foundData['accounts'][$accountTypeKey][$dbField]
                            );
                            break;

                        case 'home':
                            $addUrl = (bool) $config['mf_home_in_sitemap'];
                            break;
                    }

                    if (!$addUrl) {
                        continue;
                    }
                }

                $this->geoUrls[] = $this->buildUrl(
                    str_replace('[lang]/', '', $reefless->getPageUrl($page)),
                    $location
                );

                if ($this->multilingualPaths) {
                    foreach ($rlSitemap->languages as $langCode => $langData) {
                        if ($config['lang'] !== $langCode)  {
                            $this->geoUrls[] = $this->buildUrl(
                                str_replace('[lang]/', '', $reefless->getPageUrl($page, null, $langCode)),
                                $location,
                                $langCode
                            );
                        }
                    }
                }
            }

            if ($level + 1 < (int) $this->geo_format['Levels']) {
                $this->buildLocationUrlsForSitemap($location);
            }
        }
    }

    /**
     * Display location selector in header navigation bar
     *
     * @since 2.4.0
     */
    public function hookTplHeaderUserNav()
    {
        if (!$this->geo_format || $GLOBALS['config']['mf_select_interface'] != 'usernavbar') {
            return;
        }

        global $rlSmarty;

        if ($this->geo_filter_data['location']) {
            $current_location = [];
            foreach (array_reverse($this->geo_filter_data['location']) as $index => $location) {
                $current_location[] = $location['name'];

                if ($index === 1) {
                    break;
                }
            }

            $rlSmarty->assign('mf_current_location', implode(', ', $current_location));
        }

        if ($GLOBALS['page_info']['Controller'] != 'search_map') {
            $rlSmarty->display(RL_PLUGINS . 'multiField' . RL_DS . 'location_selector.tpl');
        }
    }

    /**
     * Alternative replace of location in category description
     *
     * @hook phpListingTypeBrowseQuickSearchMode
     * @since 2.5.0
     */
    public function hookPhpListingTypeBrowseQuickSearchMode()
    {
        global $category;

        if (!$category) {
            return;
        }

        $this->adaptLocString($category['h1']);
        $this->adaptLocString($category['title']);
        $this->adaptLocString($category['des']);
        $this->adaptLocString($category['meta_description']);
    }

    /**
     * @deprecated 2.2.0
     */
    public function hookPhpMailSend(&$subject, &$body) {}

    /**
     * @deprecated 2.1.0
     */
    public function makeUrlGeo($url, $langCode = '', $pathLangCode = '')
    {}

    /**
     * Adapt Page Info
     *
     * Replaces {location} variables in the string according to applied location
     */
    public function adaptPageInfo()
    {
        global $page_info, $bread_crumbs, $lang, $main_menu, $footer_menu;

        $areas = array('meta_description', 'meta_keywords', 'meta_title', 'h1', 'title');
        foreach ($areas as $area) {
            $this->adaptLocString($page_info[$area]);
        }

        if ($bread_crumbs) {
            $bc_areas = array('title', 'name');
            foreach ($bread_crumbs as $bk => $bc_item) {
                foreach ($bc_areas as $area) {
                    $this->adaptLocString($bread_crumbs[$bk][$area]);
                }
            }
        }

        $this->adaptLocString($GLOBALS['config']['site_name']);
        $this->adaptLocString($lang['pages+title+home']);
        $this->adaptLocString($GLOBALS['rss']['title']);
        $this->adaptLocString($GLOBALS['rlSmarty']->_tpl_vars['site_name']);
        $this->adaptLocString($GLOBALS['rlSmarty']->_tpl_vars['description']); // FBB item page description

        foreach ($main_menu as $k => $v) {
            $this->adaptLocString($main_menu[$k]['title']);
        }

        foreach ($footer_menu as $k => $v) {
            $this->adaptLocString($footer_menu[$k]['title']);
        }

        // Recount listings to build proper rel_prev and prev_next tags
        if ($page_info['Controller'] == 'listing_type') {
            $GLOBALS['category']['Count']     = $GLOBALS['rlListings']->calc;
            $GLOBALS['listing_type']['Count'] = $GLOBALS['rlListings']->calc;
        }
    }

    /**
     * Adapt category titles in global language array
     * Replace {location} variables in the string according to the applied location
     *
     * @since 2.5.0
     */
    private function adaptCategoryMetaData()
    {
        global $lang;

        foreach ($lang as $key => &$phrase) {
            if (
                (strpos($key, 'categories+title+') === 0 || strpos($key, 'pages+title+lt_') === 0)
                && $phrase
                && false !== strpos($phrase, 'location')
            ) {
                $this->adaptLocString($phrase);
            }
        }
    }

    /**
     * Adapt metadata on the account page
     *
     * @hook accountTypeTop
     * @since 2.5.0
     */
    public function hookAccountTypeTop()
    {
        global $lang, $account_type_key;

        $meta = ['title', 'h1', 'description', 'keywords'];

        foreach ($meta as $item) {
            $phrase_key = 'account_types+account_meta_' . $item . '+' . $account_type_key;

            if ($lang[$phrase_key]) {
                $this->adaptLocString($lang[$phrase_key]);
            }
        }
    }

    /**
     * Prevent adding of current location to the personal account page url
     *
     * @hook phpGetPersonalAddressAfter
     *
     * @since 2.6.0 - Added $langCode parameter
     * @since 2.5.0
     */
    public function hookPhpGetPersonalAddressAfter($id, $account, $address, $type, &$url, $langCode)
    {
        global $domain_info, $config;

        if (!$config['mod_rewrite']) {
            return;
        }

        if (!$this->geo_format) {
            return;
        }

        // Disable url modifying in admin panel in 'none' and Location Filter modes
        if (defined('REALM') && REALM === 'admin' && in_array($config['mf_account_page_filtration'], ['none', 'filter'])) {
            return;
        }

        switch ($config['mf_account_page_filtration']) {
            case 'url':
                if ($this->geo_filter_data['location_account_fields']) {
                    $url = $this->buildResourceUrl($url, $account, null, 'account');
                }
                break;

            case 'filter':
                if (in_array('at_' . $account['Type'], $this->geo_filter_data['location_url_pages'])) {
                    $url = $this->buildUrl($url, $this->geo_filter_data['applied_location'], $langCode);
                }
                break;

            case 'none':
                if (!defined('AJAX_FILE') && !$GLOBALS['reefless']->preventUrlModifying) {
                    $url = str_replace($domain_info['host'], $domain_info['host'] . 'locfix', $url);
                }
                break;
        }
    }

    /**
     * Prepare listing location data
     * @since 2.0.0
     */
    public function prepareListingLocationData()
    {
        foreach ($GLOBALS['listing'] as $group) {
            foreach ($group['Fields'] as $field) {
                if (isset($this->geo_filter_data['location_listing_fields'][$field['Key']])) {
                    $this->listing_location_data[] = $field['value'];
                }
            }
        }
    }

    /**
     * Adapt Location String
     *
     * Replaces {location} variables in the string according to applied location
     *
     * @param array $string
     */
    public function adaptLocString(&$string)
    {
        if (!$string) {
            return;
        }

        if (false === strpos($string, 'location')) {
            return;
        }

        for ($i = $this->geo_format['Levels']; $i > 0; $i--) {
            $replace = '{location_level' . $i . '}';
            $pattern = '{if location_level' . $i . '}(((?!\\/if|{else}).)+)({else}(((?!\\/if).)+))?{\\/if}';

            $index = $i - 1;
            if ($this->detailsPage) {
                $location = $this->listing_location_data[$index];
            } else {
                $location = $this->geo_filter_data['location'][$index]['name'];
            }

            if ($location) {
                $locations[] = $location;
            }

            if (false !== strpos($string, $replace)) {
                if ($location) {
                    $string = str_replace($replace, $location, $string);
                }

                $string = preg_replace("/{$pattern}/smiu", ($location ? '$1' : '$4'), $string);
            }
        }

        $pattern = '{if location}(((?!\\/if|{else}).)+)({else}(((?!\\/if).)+))?{\\/if}';

        if ($locations) {
            $string = str_replace('{location}', implode(', ', array_reverse($locations)), $string);
        }

        $string = preg_replace("/{$pattern}/smiu", ($locations ? '$1' : '$4'), $string);
    }

    /**
     * Recount category listings depending on current location
     *
     * @param array $categories - categories
     **/
    public function adaptCategories(&$categories)
    {
        if (!$this->geo_filter_data['applied_location']
            || !$this->geo_filter_data['is_filtering']
            || ($_POST['xjxfun'] || $GLOBALS['page_info']['Key'] == 'search')
            || !$categories
        ) {
            return;
        }

        $category_id = $GLOBALS['category']['ID'];
        $first_category = current($categories);
        $type_key = $first_category['Type'];

        if (!in_array('lt_' . $type_key, $this->geo_filter_data['filtering_pages'])) {
            return;
        }

        $sql = "
            SELECT `T1`.`Category_ID`, COUNT(`T1`.`ID`) AS `Count`, `T2`.`Parent_IDs`
            FROM `{db_prefix}listings` AS `T1`
            LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID`
            WHERE `T1`.`Status` = 'active'
        ";

        if ($GLOBALS['plugins']['listing_status']) {
            $sql .= "AND `T1`.`Sub_status` <> 'invisible' ";
        }

        $sql .= "AND `T2`.`Count` > 0 ";

        if ($type_key) {
            $sql .= "AND `T2`.`Type` = '{$type_key}' ";
        }

        if ($category_id) {
            $sql .= "AND FIND_IN_SET({$GLOBALS['category']['ID']}, `T2`.`Parent_IDs`) > 0 ";
        }

        foreach ($this->geo_filter_data['location_listing_fields'] as $field => $value) {
            if ($field && $value) {
                $sql .= "AND `T1`.`{$field}` = '{$value}' ";
            }
        }

        $sql .= 'GROUP BY `T1`.`Category_ID`';

        $counts = $GLOBALS['rlDb']->getAll($sql, 'Category_ID');

        foreach ($counts as $count) {
            if ($count['Parent_IDs']) {
                foreach (explode(',', $count['Parent_IDs']) as $parent_id) {
                    if ($counts[$parent_id]) {
                        $counts[$parent_id]['Count'] += $count['Count'];
                    } else {
                        $counts[$parent_id] = array(
                            'Count' => $count['Count']
                        );
                    }
                }
            }
        }

        foreach ($categories as &$category) {
            if (!$category['Count']) {
                continue;
            }

            $category['Count'] = $counts[$category['ID']] ? $counts[$category['ID']]['Count'] : 0;

            if ($category['sub_categories']) {
                foreach ($category['sub_categories'] as &$subcategory) {
                    if (!$subcategory['Count']) {
                        continue;
                    }

                    $subcategory['Count'] = $counts[$subcategory['ID']] ? $counts[$subcategory['ID']]['Count'] : 0;
                }
            }
        }
    }

    /**
     * User location detection process
     *
     * @since 2.5.2 - $alternativeCheck the first parameter added
     *
     * @param bool $alternativeCheck - Alternative check mode, search for English version of the location
     */
    public function detectLocation($alternativeCheck = false)
    {
        global $reefless, $config, $rlValid, $rlDb, $page_info;

        if ($this->geo_filter_data['applied_location'] || $page_info['Key'] == '404') {
            return false;
        }

        if ($reefless->isBot()
            || $_GET['q'] == 'ext'
            || $_POST['xjxfun']
            || !$config['mf_geo_autodetect']
            || isset($_GET['reset_location'])
            || $_COOKIE['mf_geo_location'] == 'reset'
            || $this->detailsPage
            || strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'
        ) {
            return;
        }

        $multilingual_check = false;
        $locations = [];
        $names = [];

        if (!$alternativeCheck
            && RL_LANG_CODE != 'en'
            && $config['mf_db_version'] == 'locations6'
            && isset($_SESSION['GEOLocationData']->Country_names)
            && $_SESSION['GEOLocationData']->Country_names[RL_LANG_CODE]
            && $_SESSION['GEOLocationData']->Region_names[RL_LANG_CODE]
            && $_SESSION['GEOLocationData']->City_names[RL_LANG_CODE]
            && in_array(RL_LANG_CODE, ['es','ru','fr','de','pt'])
        ) {
            if ($_SESSION['GEOLocationData']->Country_names) {
                $names[] = $_SESSION['GEOLocationData']->Country_names[RL_LANG_CODE];
            }
            if ($_SESSION['GEOLocationData']->Region_names) {
                $names[] = $_SESSION['GEOLocationData']->Region_names[RL_LANG_CODE];
            }
            if ($_SESSION['GEOLocationData']->City_names) {
                $names[] = $_SESSION['GEOLocationData']->City_names[RL_LANG_CODE];
            }

            $multilingual_check = true;
            $table_lang = RL_LANG_CODE;
        } else {
            if ($_SESSION['GEOLocationData']->Country_name) {
                $names[] = $_SESSION['GEOLocationData']->Country_name;
            }
            if ($_SESSION['GEOLocationData']->Region) {
                $names[] = $_SESSION['GEOLocationData']->Region;
            }
            if ($_SESSION['GEOLocationData']->City) {
                $names[] = $_SESSION['GEOLocationData']->City;
            }

            $table_lang = array_key_exists('en', $GLOBALS['languages']) ? 'en' : RL_LANG_CODE;
        }

        $parent_key = $this->geo_format['Key'];

        foreach ($names as $name) {
            Valid::escape($name);

            $sql = "SELECT `Key`, `Value` AS `name` ";
            $sql .= "FROM `{db_prefix}multi_formats_lang_" . $table_lang . "` ";
            $sql .= "WHERE `Value` = '{$name}' ";
            $sql .= "AND `Key` REGEXP '^{$parent_key}' ";
            $sql .= "ORDER BY CHAR_LENGTH(`Key`) ASC ";
            $sql .= "LIMIT 1";

            $location = $rlDb->getRow($sql);

            if ($location) {
                $parent_key = $location['Key'];
                $locations[] = $location;
            } else {
                $locations = [];
                break;
            }
        }

        if ($locations) {
            $locations = array_reverse($locations);
            $location_to_apply = $rlDb->fetch(
                '*',
                array('Key' => $locations[0]['Key'], 'Status' => 'active'),
                null, null, 'multi_formats', 'row'
            );

            if (!$location_to_apply) {
                return;
            }

            // Save automatically detected location for 12 hours
            $reefless->createCookie('mf_geo_location', json_encode($location_to_apply), strtotime('+ 12 hours'));

            $_SESSION['geo_location_autodetected'] = true;

            $location_to_apply['name'] = $locations[0]['name'];
            $path = $location_to_apply['Path'];

            $this->definePathField();

            if ($config['mf_multilingual_path']) {
                $path_lang_code = 'Path_' . RL_LANG_CODE;
                $path = $location_to_apply[$path_lang_code] ?: $location_to_apply['Path_' . $config['lang']];
            }

            if ($this->geo_filter_data['is_location_url'] && $path) {
                $redirect_url = $this->buildUrl($this->cleanUrl(), $location_to_apply);

                if ($_SERVER['QUERY_STRING']) {
                    $redirect_url .= '?' . $_SERVER['QUERY_STRING'];
                }

                // Redirect using default header function to avoid utilsRedirectURL hook call
                header("Location: {$redirect_url}", true, 302);
                exit;
            } else {
                $_SESSION['geo_filter_location'] = $location_to_apply;
                header('Refresh: 0');
                exit;
            }
        } elseif ($multilingual_check && array_key_exists('en', $GLOBALS['languages'])) {
            $this->detectLocation(true);
        }
    }

    /**
     * Convert idn host to utf8 format
     *
     * @since 2.1.2
     *
     * @param  string $host - Host to convert
     * @return string       - Converted host
     */
    public function idnToUtf8($host = '')
    {
        if (method_exists(Util::class, 'idnToUtf8')) {
            return Util::idnToUtf8($host);
        } else {
            if (!$host = (string) $host) {
                return $host;
            }

            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                $host = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            } else {
                $host = idn_to_utf8($host);
            }

            return $host;
        }
    }

    /**
     * Ignore compilation of the tpl file content
     *
     * @since 2.4.0
     *
     * @param string $file - File path from script root
     */
    public function ignoreFileCompilation($file) {
        $this->noCompileFiles[] = ltrim($file, RL_DS);
    }

    /* DEPRECATED METHODS */

    /**
     * @hook sitemapGetListingsBeforeGetAll
     * @deprecated 2.2.0
     * @since 2.0.0
     */
    public function hookSitemapGetListingsBeforeGetAll()
    {}
}
