<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLNAVIGATOR.CLASS.PHP
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

class rlNavigator extends reefless
{
    /**
     * @var current page name
     **/
    public $cPage;

    /**
     * @var current language
     **/
    public $cLang;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->checkLicense();
    }

    /**
     * separate the request URL by variables array.
     *
     * @param string $vareables - the string of GET vareables
     * @param string $page - current page form $_GET
     * @param string $lang - current language form $_GET
     *
     **/
    public function rewriteGet($vareables = false, $page = false, $lang = false)
    {
        global $config, $rlValid, $languages;

        $rlValid->sql($vareables);
        $rlValid->sql($page);
        $rlValid->sql($lang);

        if ($config['multilingual_paths']) {
            $page = Util::idnToUtf8($page);
        }

        $page = empty($page) ? '' : $page;
        $items = explode('/', trim($vareables, '/'));

        // Check is language exist
        if ($lang && !$languages[$lang]) {
            $lang = $config['lang'];
        }

        if ($config['mod_rewrite']) {
            /* wildcard account request */
            if (isset($_GET['wildcard'])) {
                $request = trim($vareables, '/');
                $request_exp = explode('/', $request);

                if (count($request_exp) > 1 && strlen($request_exp[1]) == 2) {
                    $this->cLang = $_GET['lang'] = $request_exp[1];
                } elseif (count($request_exp) == 1 && strlen($request) == 2) {
                    $this->cLang = $_GET['lang'] = trim($vareables, '/');
                }
            }

            if (strlen($page) < 3 && !$_GET['lang'] && $page) {
                $this->cLang = $page;
                $this->cPage = $items[0];
                $_GET['page'] = $items[0];

                $rlVars = explode('/', trim($_GET['rlVareables'], '/'));
                unset($rlVars[0]);
                $_GET['rlVareables'] = implode('/', $rlVars);

                foreach ($items as $key => $value) {
                    $items[$key] = $items[$key + 1];

                    if (empty($items[$key])) {
                        unset($items[$key]);
                    }
                }
            } elseif ($_GET['lang']) {
                $this->cLang = $_GET['lang'];
                $this->cPage = $page;

                if (is_numeric($var_index = array_search($this->cLang, $items))) {
                    unset($items[$var_index]);
                    $_GET['rlVareables'] = implode('/', $items);
                }
            } else {
                $explodedVariables = explode('/', $vareables);
                $detectedLang      = reset($explodedVariables);
                $detectedLang      = in_array($detectedLang, array_keys($languages)) ? $detectedLang : '';
                $this->cLang       = $detectedLang ?: $config['lang'];
                $this->cPage       = $page;

                if ($detectedLang) {
                    $rlVars = explode('/', trim($_GET['rlVareables'], '/'));
                    unset($rlVars[0]);
                    $_GET['rlVareables'] = implode('/', $rlVars);

                    foreach ($items as $key => $value) {
                        $items[$key] = $items[$key + 1];

                        if (empty($items[$key])) {
                            unset($items[$key]);
                        }
                    }
                }
            }
        } else {
            $this->cLang = $lang;
            $this->cPage = $page;
        }

        if (!empty($vareables)) {
            $count_vars = count($items);

            for ($i = 0; $i < $count_vars; $i++) {
                $step = $i + 1;
                $_GET['nvar_' . $step] = $items[$i];
            }
            unset($vareables);
        }
    }

    /**
     * Detection of the page by path
     *
     * @return bool|array - Page data array
     */
    public function definePage()
    {
        global $account_info, $config, $rlDb;

        $page = $this->cPage == 'index' ? '' : $this->cPage;

        // Fix pagination
        if ($config['mod_rewrite'] && !$_GET['pg'] && false !== strpos($_GET['rlVareables'], 'index')) {
            preg_match('/index([0-9]+)(\.html|\/)?/', $_GET['rlVareables'], $match);

            if ($match) {
                $_GET['rlVareables'] = str_replace($match[0], '', $_GET['rlVareables']);
                $_GET['rlVareables'] = trim($_GET['rlVareables'], '/');

                $_GET['pg'] = $match[1];
            }
        }

        if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
            $where = "`Path` = CONVERT('{$page}' USING utf8) ";
            $where .= 'OR `Path_' . RL_LANG_CODE  . "` = CONVERT('{$page}' USING utf8)";
        } else {
            $where = "`Path` = CONVERT('{$page}' USING utf8)";
        }

        $pageInfo = $rlDb->getRow("SELECT * FROM `{db_prefix}pages` WHERE {$where} AND `Status` = 'active'");

        // System page request
        if ($pageInfo) {
            if (($pageInfo['Plugin'] && $pageInfo['Type'] == 'system'
                    && !is_readable(RL_PLUGINS . $pageInfo['Plugin'] . RL_DS . $pageInfo['Controller'] . '.inc.php')
                )
                || (empty($pageInfo['Controller'])
                || $GLOBALS['sError']
                || (!$pageInfo['Plugin'] && !is_readable(RL_CONTROL . $pageInfo['Controller'] . '.inc.php'))
            )) {
                $page = 404;

                if ($config['404_header'] || !isset($config['404_header'])) {
                    header('HTTP/1.0 404 Not Found');
                }

                $sql = "SELECT * FROM `{db_prefix}pages` WHERE `Key` = '{$page}' AND `Status` = 'active' LIMIT 1";
                $pageInfo = $rlDb->getRow($sql);
            }

            // Redirect user to multilingual path of current page if he use default path
            $currentPath = $pageInfo['Path_' . RL_LANG_CODE];
            if ($config['multilingual_paths']
                && RL_LANG_CODE !== $config['lang']
                && (!empty($currentPath) && $currentPath !== $pageInfo['Path'])
                && $pageInfo['Path'] === $page
                && !(int) $_GET['listing_id']
                && !isset($_GET['nvar_1'])
            ) {
                $GLOBALS['page_info'] = $pageInfo;
                Util::redirect($GLOBALS['reefless']->getPageUrl($pageInfo['Key'], false, RL_LANG_CODE));
            }
        }
        // Account info request
        else {
            $address        = $this->cPage;
            $accountDetails = $rlDb->getRow(
                "SELECT `ID`, `Type` FROM `{db_prefix}accounts`
                WHERE `Own_address` = CONVERT('{$address}' USING utf8) LIMIT 1"
            );

            $pageInfo = $rlDb->fetch(
                '*',
                ['Key' => "at_{$accountDetails['Type']}",
                'Status' => 'active'],
                null, 1, 'pages', 'row'
            );

            $_GET['id'] = $accountDetails['ID'];

            if (empty($pageInfo['Controller'])
                || !is_readable(RL_CONTROL . $pageInfo['Controller'] . '.inc.php')
                || ($pageInfo['Menus'] == '2' && !isset($account_info['ID'])
                || $GLOBALS['sError']
            )) {
                $page = 404;

                if ($config['404_header'] || !isset($config['404_header'])) {
                    header('HTTP/1.0 404 Not Found');
                }

                $pageInfo = $rlDb->getRow(
                    "SELECT * FROM `{db_prefix}pages`
                    WHERE `Key` = '{$page}' AND `Status` = 'active' LIMIT 1"
                );
            }
        }

        if (!$pageInfo) {
            return false;
        }

        if ($pageInfo['Controller'] == 'listing_type'
            && ($_GET['listing_id'] || (!$config['mod_rewrite'] && $_GET['id']))
        ) {
            $pageInfo['Key'] = 'view_details';
        }

        // Set alternative controller name for the "Listing Details" page
        $listing_id = intval($config['mod_rewrite'] ? $_GET['listing_id'] : $_GET['id']);
        if ($listing_id) {
            $pageInfo['Controller_alt'] = 'listing_details';
        }

        /**
         * Re-assign path of page if selected another language and enabled the option "Multilingual paths"
         * to use correct path for all internal urls
         */
        if ($config['multilingual_paths']) {
            $pageInfo["Path_{$config['lang']}"] = $pageInfo['Path'];

            if (RL_LANG_CODE !== $config['lang'] && $pageInfo['Path_' . RL_LANG_CODE]) {
                $pageInfo['Path'] = $pageInfo['Path_' . RL_LANG_CODE];
            }
        }

        return $pageInfo;
    }

    /************************************************************************************************************
     *
     * ATTENTION!
     *
     * The following method represents Flynax copyright. You're not allowed to modify the method or prevent it
     * from calling. Breach of the copyright is regarded as a criminal offense, which will result in punishment
     * and suspension of your license. Feel free to contact our support department if you have any questions.
     *
     * @todo do one call per month to inform server about current license status
     *
     ************************************************************************************************************/
    public function callServer($domain = false, $license = false, $index = 0)
    {
        eval(base64_decode("JGNsX3NlcnZpZXJzID0gYXJyYXkoJ2h0dHA6Ly9mbHZhbGlkMS5mbHluYXguY29tLz9kb21haW49e2RvbWFpbn0mbGljZW5zZT17bGljZW5zZX0mdXJsPXt1cmx9JywnaHR0cDovL2ZsdmFsaWQyLmZseW5heC5jb20vP2RvbWFpbj17ZG9tYWlufSZsaWNlbnNlPXtsaWNlbnNlfSZ1cmw9e3VybH0nLCdodHRwOi8vZmx2YWxpZDMuZmx5bmF4LmNvbS8/ZG9tYWluPXtkb21haW59JmxpY2Vuc2U9e2xpY2Vuc2V9JnVybD17dXJsfScpOw=="));

        if ($index >= count($cl_serviers)) {
            return false;
        }

        $url = str_replace(array('{domain}', '{license}', '{url}'), array($domain, $license, RL_URL_HOME), $cl_serviers[$index]);
        $response = $this->pingServer($url);

        if (false !== $response && !is_null($response)) {
            return $response;
        }

        return $this->callServer($domain, $license, ++$index);
    }

    /**
     * Ping flvalid server
     * @param string $flvalid_url - full server url
     * @return mixed
     */
    public function pingServer($flvalid_url)
    {
        // Create a stream
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => $this->time_limit,
            ),
        );
        $context = stream_context_create($opts);

        // using the HTTP headers set above
        return file_get_contents($flvalid_url, false, $context);
    }

    /************************************************************************************************************
     *
     * ATTENTION!
     *
     * The following method represents Flynax copyright. You're not allowed to modify the method or prevent it
     * from calling. Breach of the copyright is regarded as a criminal offense, which will result in punishment
     * and suspension of your license. Feel free to contact our support department if you have any questions.
     *
     ************************************************************************************************************/
    public function checkLicense()
    {
        global $config;

        eval(base64_decode(RL_SETUP));
        $current_domain = $GLOBALS['rlValid']->getDomain(RL_URL_HOME);
        $exp_domain = explode('.', $current_domain);

        if (count($exp_domain) > 2) {
            $exp_domain = array_reverse($exp_domain);
            $current_domain = $exp_domain[1] . "." . $exp_domain[0];
        }

        // allow local testing
        if (in_array(getenv('SERVER_ADDR'), array('127.0.0.1', '::1'))
            && in_array(getenv('SERVER_PORT'), array(80, 443, 8080))
        ) {
            if (($config['rl_setup'] + 2678400) < time()) {
                $this->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'rl_setup' LIMIT 1");
                @$this->callServer($license_domain, $license_number);
            }
            return true;
        }

        $exp_license_domain = explode('.', $license_domain);
        if (count($exp_license_domain) > 2) {
            $exp_license_domain = array_reverse($exp_license_domain);
            $license_domain = $exp_license_domain[1] . '.' . $exp_license_domain[0];
        }

        if ($license_domain != $current_domain || !$current_domain || !$license_number) {
            if (($config['rl_setup'] + 2678400) < time()) {
                $license_response = @$this->callServer($license_domain, $license_number);

                if ($license_response == 'false') {
                    eval(base64_decode('ZWNobyAiPGgyPkZseW5heCBsaWNlbnNlIHZpb2xhdGlvbiBkZXRlY3RlZCE8L2gyPiI7IGVjaG8gIllvdSBhcmUgbm90IGFsbG93ZWQgdG8gdXNlIEZseW5heCBTb2Z0d2FyZSBvbiB0aGlzIGRvbWFpbiwgcGxlYXNlIGNvbnRhY3QgRmx5bmF4IE93bmVycyB0byByZXNvbHZlIHRoZSBpc3N1ZS4iOyBleGl0Ow=='));
                } else {
                    $this->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'rl_setup' LIMIT 1");
                }
            }

            eval(base64_decode('ZWNobyAiPGgyPkZseW5heCBsaWNlbnNlIHZpb2xhdGlvbiBkZXRlY3RlZCE8L2gyPiI7IGVjaG8gIllvdSBhcmUgbm90IGFsbG93ZWQgdG8gdXNlIEZseW5heCBTb2Z0d2FyZSBvbiB0aGlzIGRvbWFpbiwgcGxlYXNlIGNvbnRhY3QgRmx5bmF4IE93bmVycyB0byByZXNvbHZlIHRoZSBpc3N1ZS4iOyBleGl0Ow=='));
        } else {
            if (($config['rl_setup'] + 2678400) < time()) {
                $license_response = @$this->callServer($license_domain, $license_number);

                if ($license_response == 'false') {
                    eval(base64_decode('ZWNobyAiPGgyPkZseW5heCBsaWNlbnNlIHZpb2xhdGlvbiBkZXRlY3RlZCE8L2gyPiI7IGVjaG8gIllvdSBhcmUgbm90IGFsbG93ZWQgdG8gdXNlIEZseW5heCBTb2Z0d2FyZSBvbiB0aGlzIGRvbWFpbiwgcGxlYXNlIGNvbnRhY3QgRmx5bmF4IE93bmVycyB0byByZXNvbHZlIHRoZSBpc3N1ZS4iOyBleGl0Ow=='));
                } else {
                    $this->query("UPDATE `{db_prefix}config` SET `Default` = '" . time() . "' WHERE `Key` = 'rl_setup' LIMIT 1");
                }
            }
        }
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
        return Util::getPages(['Key', 'Path'], ['Status' => 'active'], null, ['Key', 'Path']);
    }

    /**
     * Get GEO data | blank flange
     **/
    public function getGEOData()
    {}

    /*
     * Fix for cases when wildcard rule didnt work
     *
     * @since 4.7.1
     */
    public function fixRewrite()
    {
        if (!defined('REWRITED')) {
            preg_match("#^([^\.]*)\.#", $_SERVER['HTTP_HOST'], $match);

            if ($_SERVER['HTTP_HOST'] != $GLOBALS['domain_info']['host']
                && $_GET['page'] && $_GET['page'] != $match[1]
            ) {
                $_GET['rlVareables'] = $_GET['page'] . ($_GET['rlVareables'] ? '/' . $_GET['rlVareables'] : '');
                $_GET['page'] = $match[1];
                $_GET['wildcard'] = '';
            } elseif ($_SERVER['HTTP_HOST'] != $GLOBALS['domain_info']['host']
                && (!isset($_GET['page']) || $_GET['listing_id'])
            ) {
                $_GET['page'] = $match[1];
                $_GET['wildcard'] = '';
            }

            define('REWRITED', true);
        }
    }

    /**
     * Transform links for listing types
     *
     * @since 4.5.0
     */
    public function transformLinks()
    {
        global $ltypes_to_transform_links, $languages, $rlDb, $config;

        /* sub-level paths */
        $search_results_url = 'search-results';
        $advanced_search_url = 'advanced-search';
        /* sub-level paths end */

        $this->fixRewrite();

        $select   = "`T1`.`Links_type`, `T1`.`Key`, ";

        if ($config['multilingual_paths']) {
            $langKey = (string) $_GET['lang'] ?: $_GET['page'];
            $langKey = strlen($langKey) == 2 && in_array($langKey, array_keys($languages)) ? $langKey : '';

            // Case when website use subdomains for listing types
            if (!$langKey) {
                $explodedVariables = explode('/', $_GET['rlVareables']);
                $langKey = (string) reset($explodedVariables);
                $langKey = strlen($langKey) == 2 && in_array($langKey, array_keys($languages)) ? $langKey : '';
            }

            if ($langKey !== '' && $langKey !== $config['lang']) {
                $select .= "IF(`T2`.`Path_{$langKey}` <> '', `T2`.`Path_{$langKey}`, `T2`.`Path`) AS `Path`";
            } else {
                $select .= "`T2`.`Path`";
            }
        } else {
            $select .= "`T2`.`Path`";
        }

        $sql = "SELECT {$select} FROM `{db_prefix}listing_types` AS `T1` ";
        $sql .= "JOIN `{db_prefix}pages` AS `T2` ON `T2`.`Key` = CONCAT('lt_', `T1`.`Key`) ";
        $sql .= "WHERE (`Links_type` = 'short' OR `Links_type` = 'subdomain') AND `T1`.`Status` = 'active' ";
        $ltypes_to_transform_links = $rlDb->getAll($sql, 'Key');

        if ($ltypes_to_transform_links && $_GET['page']) {
            if (strlen($_GET['page']) == 2 && in_array($_GET['page'], array_keys($languages))) {
                $rwlang = $_GET['page'];
                $rwtmp = explode("/", $_GET['rlVareables']);
                $rwfirst_var = array_splice($rwtmp, 0, 1);
                $_GET['page'] = $rwfirst_var[0];
                $_GET['rlVareables'] = implode("/", $rwtmp);
            } elseif (in_array($_GET['rlVareables'], array_keys($languages))
                && (strpos($_GET['rlVareables'], '/') == 2 || strlen($_GET['rlVareables']) == 2)
            ) {
                $rwtmp = array_filter(explode("/", $_GET['rlVareables']));
                $rwfirst_var = array_splice($rwtmp, 0, 1);
                $rwlang = $rwfirst_var[0];

                $_GET['rlVareables'] = implode("/", $rwtmp);
                unset($_GET['wildcard']);
            }

            /* search results urls */
            foreach ($ltypes_to_transform_links as $lk => $type_to_rewrite) {
                if ($type_to_rewrite['Links_type'] == 'subdomain') {
                    $ltype_on_sub = true;

                    if ($type_to_rewrite && $type_to_rewrite['Links_type'] == 'subdomain'
                        && (
                            ($_GET['rlVareables'] == $search_results_url . ".html" || $_GET['rlVareables'] == $advanced_search_url . ".html")
                            && $_GET['page'] == $type_to_rewrite['Path'])
                        /*|| ($_GET['page'] == $GLOBALS['search_results_url'] || $_GET['page'] == $GLOBALS['advanced_search_url'])*/
                    ) {
                        $rwtype = $type_to_rewrite['Key'];
                        break;
                    }
                }
            }

            if ($ltype_on_sub) {
                //fix for pages when url like auto.site.com/acura.html
                if (is_numeric(strpos($_GET['rlVareables'], '.html'))) {
                    $_GET['rlVareables'] = str_replace(".html", "", $_GET['rlVareables']);
                }
            }

            if (!$rwtype) {
                $page = Valid::escape($_GET['page']);

                if ($page && $config['multilingual_paths']) {
                    $sql = 'SELECT `T1`.`Type` FROM `{db_prefix}categories` AS `T1` ';
                    $sql .= "WHERE (`T1`.`Path` = '{$page}' ";

                    foreach ($languages as $langKey => $langData) {
                        if ($langKey === $config['lang']) {
                            continue;
                        }

                        $sql .= " OR `T1`.`Path_{$langKey}` = '{$page}' ";
                    }

                    $sql .= ') AND (';
                    $ex  = false;
                    foreach ($ltypes_to_transform_links as $k => $v) {
                        if ($v['Links_type'] == 'short') {
                            $sql .= " `T1`.`Type` = '{$v['Key']}' OR ";
                            $ex  = true;
                        }
                    }

                    if ($ex) {
                        $sql = substr($sql, 0, -3);
                        $sql .= ") ";
                    } else {
                        $sql = substr($sql, 0, -5);
                    }

                    $rwtype = $rlDb->getRow($sql, 'Type');
                } elseif ($page) {
                    $sql = "SELECT `Type` FROM `{db_prefix}categories` WHERE `Path` = '{$page}' ";
                    $sql .= "AND (";

                    $ex = false;
                    foreach ($ltypes_to_transform_links as $k => $v) {
                        if ($v['Links_type'] == 'short') {
                            $sql .= " `Type` = '{$v['Key']}' OR ";
                            $ex = true;
                        }
                    }

                    if ($ex) {
                        $sql = substr($sql, 0, -3);
                        $sql .= ") ";
                    } else {
                        $sql = substr($sql, 0, -5);
                    }

                    $rwtype = $rlDb->getRow($sql, 'Type');
                }
            }

            if ($rwtype) {
                if ($ltypes_to_transform_links[$rwtype]['Links_type'] == 'short') {
                    $rwtmp = explode('/', trim($page . '/' . $_GET['rlVareables'], '/'));
                } else {
                    $_GET['rlVareables'] = str_replace('.html', '', $_GET['rlVareables']);
                    $rwtmp = explode('/', trim($_GET['rlVareables'], '/'));
                    unset($_GET['wildcard']);
                }

                if ($rwlang) {
                    $_GET['page'] = $rwlang;
                    $_GET['rlVareables'] = $ltypes_to_transform_links[$rwtype]['Path'] . '/' . implode('/', $rwtmp);
                } else {
                    $_GET['page'] = $ltypes_to_transform_links[$rwtype]['Path'];
                    $_GET['rlVareables'] = implode('/', $rwtmp);
                }
            } elseif ($rwlang) {
                $newvariables = $_GET['page'] . '/' . $_GET['rlVareables'];
                $_GET['rlVareables'] = $newvariables;
                $_GET['page']        = $rwlang;
            }
        }
    }
}
