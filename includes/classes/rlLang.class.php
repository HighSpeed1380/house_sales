<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLLANG.CLASS.PHP
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

class rlLang
{
    /**
     * Is categories phrases fetched flag
     *
     * @since 4.8.1
     *
     * @var boolean
     */
    private $categoriesAssigned = false;

    /**
     * Set language phrases
     *
     * @since 4.9.0 - Removed unused $side, $status parameters
     *
     * @param  string|array $data      - Data for replacing
     * @param  string       $module    - System module
     * @param  string|array $fields    - Fields names for execute
     * @param  string       $langCode  - Language code, possible values: any lang code or * (like all languages)
     * @return array                   - Languages values instead of languages keys
     */
    public function replaceLangKeys($data = null, $module = '', $fields = null, $langCode = RL_LANG_CODE)
    {
        global $lang;

        if (!$data) {
            return array();
        }

        // Append category related phrases
        if ($module == 'categories' && !$this->categoriesAssigned) {
            $lang = array_merge($lang, $this->getLangBySide('category'));
            $this->categoriesAssigned = true;
        }

        if (is_array($data)) {
            $fields = is_string($fields) ? [$fields] : $fields;

            foreach ($fields as &$field) {
                if (is_array(current($data))) {
                    foreach ($data as &$item) {
                        $item[$field] = $lang[$module . '+' . $field . '+' . $item['Key']];
                    }
                } else {
                    $data[$field] = $lang[$module . '+' . $field . '+' . $data['Key']];
                }
            }
        } elseif ($data) {
            return $this->getPhrase($data, $langCode, null, true);
        }

        return $data;
    }

    /**
     * Select phrases by module
     *
     * @param string $module   - Languages values module: frontEnd, system, formats, email_tpl
     * @param string $langCode - Language code
     * @param string $status   - Language status
     *
     * @return - Languages values instead of languages keys
     */
    public function getLangBySide($module = 'frontEnd', $langCode = RL_LANG_CODE, $status = 'active')
    {
        $options = "WHERE `Target_key` = '' AND ";
        if (in_array($module, array('frontEnd', 'admin'))) {
            $options .= "(`Module` = '{$module}' OR `Module` = 'common') ";
        } else {
            $options .= "`Module` = '{$module}' ";
        }
        $options .= $langCode != '*' ? "AND `Code` = '{$langCode}' " : '';
        $options .= $status != 'all' ? "AND `Status` = '{$status}'" : '';

        $phrases = $this->preparePhrases($options);

        return $phrases;
    }

    /**
     * Get frontEnd phrases
     *
     * @since 4.8.1
     *
     * @param  string $langCode   - Language code
     * @param  string $controller - Page controller
     * @param  array  $blockKeys  - Page boxes keys
     * @param  array  &$jsKeys    - Javascript related phrase's keys
     * @return array              - Phrases
     */
    public function getPhrases($langCode, $controller = null, $blockKeys = null, &$jsKeys = null)
    {
        $options = "
            WHERE `Status` = 'active' AND `Code` = '{$langCode}'
            AND
            (
                (
                    (`Module` = 'frontEnd' OR `Module` = 'common')
        ";

        if ($controller) {
            $options .= "AND (`Target_key` = '' OR `Target_key` = '{$controller}') ";
        } else {
            $options .= "AND `Target_key` = '' ";
        }

        if ($blockKeys) {
            $options .= "
                )
                OR (`Module` = 'box' AND `Target_key` IN ('" . implode("','", $blockKeys) . "'))
            )
            ";
        } else {
            $options .= "))";
        }

        $phrases = $this->preparePhrases($options, $jsKeys);

        // Set home page title as site name
        $GLOBALS['config']['site_name'] = $phrases['pages+title+home'];

        return $phrases;
    }

    /**
     * Get admin panel phrases
     *
     * @since 4.8.1
     *
     * @param  string $langCode   - Language code
     * @param  string $status     - Phrases status
     * @param  string $controller - Controller key
     * @param  array  &$jsKeys    - Javascript related phrase's keys
     * @return array              - Phrases
     */
    public function getAdminPhrases($langCode = RL_LANG_CODE, $status = 'active', $controller = null, &$jsKeys = null)
    {
        $options = "WHERE `Module` IN ('admin','common') ";
        if ($controller) {
            $options .= "AND (`Target_key` = '' OR `Target_key` = '{$controller}')";
        } else {
            $options .= "AND `Target_key` = ''";
        }
        $options .= $langCode != '*' ? "AND `Code` = '{$langCode}' " : '';
        $options .= $status != 'all' ? "AND `Status` = '{$status}'" : '';

        $phrases = $this->preparePhrases($options, $jsKeys);

        // Set home page title as site name
        $GLOBALS['config']['site_name'] = $phrases['pages+title+home'];

        return $phrases;
    }

    /**
     * Fetch and prepare phrases by given mysql condition
     *
     * @since 4.8.1
     *
     * @param  string $options - Mysql "WHERE" clause options
     * @param  array  &$jsKeys - Javascript related phrase's keys
     * @return array           - Prepared phrases
     */
    public function preparePhrases($options, &$jsKeys = null)
    {
        global $rlDb;

        $replace_pattern = '/(<script.*<\/script>)/sm';

        $rlDb->setTable('lang_keys');

        foreach ($rlDb->fetch(array('Key', 'Value', 'JS'), null, $options) as $phrase) {
            $js = array();

            if (false !== strpos($phrase['Value'], "<script")) {
                preg_match($replace_pattern, $phrase['Value'], $js);
                $phrase['Value']  = preg_replace($replace_pattern, '{js-script}', $phrase['Value']);
            }

            if (false !== strpos($phrase['Value'], "'")) {
                $phrase['Value'] = preg_replace('/(\')(?=[^>]*(<|$))/', '&rsquo;', $phrase['Value']);
            }

            if (false !== strpos($phrase['Value'], '"')) {
                $phrase['Value'] = preg_replace('/(")(?=[^>]*(<|$))/', '&quot;', $phrase['Value']);
            }

            if ($js) {
                $phrase['Value'] = str_replace('{js-script}', $js[0], $phrase['Value']);
            }

            // Replace NL in boxes content
            if (0 === strpos($phrase['Key'], 'blocks+name+')) {
                $phrase['Value'] = nl2br($phrase['Value']);
            }

            if (isset($jsKeys) && $phrase['JS']) {
                $jsKeys[] = $phrase['Key'];
            }

            $phrases[$phrase['Key']] = $phrase['Value'];
        }

        return $phrases;
    }

    /**
     * define site language
     *
     * @param sting $language - language code
     *
     * @return set define site language
     **/
    public function defineLanguage($language = false)
    {
        global $config, $languages, $rlNavigator, $reefless;

        /* fix for links with wrong language in url */
        if ($rlNavigator->cLang && $rlNavigator->cLang != $config['lang']) {
            if (!$languages[$rlNavigator->cLang]) {
                $GLOBALS['sError'] = true;
            }
        }
        /* fix for links with wrong language in url end */

        $count       = count($languages);
        $cookie_lang = defined('REALM') ? 'rl_lang_' . REALM : 'rl_lang_front';

        if ($count > 1) {
            if (!empty($language)) {
                $GLOBALS['rlValid']->sql($language);
                $reefless->createCookie($cookie_lang, $language, time() + ($config['expire_languages'] * 86400));

                if ($languages[$language]) {
                    define('RL_LANG_CODE', $language);
                } else {
                    define('RL_LANG_CODE', $config['lang']);
                }
            } elseif (isset($_COOKIE[$cookie_lang])) {
                $GLOBALS['rlValid']->sql($_COOKIE[$cookie_lang]);

                if ($languages[$_COOKIE[$cookie_lang]]) {
                    define('RL_LANG_CODE', $_COOKIE[$cookie_lang]);
                } else {
                    define('RL_LANG_CODE', $config['lang']);
                }
            } else {
                define('RL_LANG_CODE', $config['lang']);
            }
        } else {
            define('RL_LANG_CODE', $config['lang']);
        }

        define('RL_LANG_DIR', $languages[RL_LANG_CODE]['Direction']);
    }

    /**
     * define site language (for EXT)
     *
     * @package EXT JS
     *
     * @return set define site language
     **/
    public function extDefineLanguage()
    {
        global $config, $rlDb;

        $cookie_lang = defined('REALM') ? "rl_lang_" . REALM : "rl_lang_front";

        if (isset($_COOKIE[$cookie_lang])) {
            $GLOBALS['rlValid']->sql($_COOKIE[$cookie_lang]);
            $user_lang = $rlDb->fetch(array('ID', 'Date_format'), array('Status' => 'active', 'Code' => $_COOKIE[$cookie_lang]), null, null, 'languages', 'row');

            define('RL_DATE_FORMAT', $user_lang['Date_format']);

            if (!empty($user_lang)) {
                define('RL_LANG_CODE', $_COOKIE[$cookie_lang]);
            } else {
                define('RL_LANG_CODE', $config['lang']);
            }
        } else {
            $user_lang = $rlDb->fetch(array('Date_format'), array('Status' => 'active', 'Code' => $config['lang']), null, null, 'languages', 'row');
            define('RL_DATE_FORMAT', $user_lang['Date_format']);

            define('RL_LANG_CODE', $config['lang']);
        }

        define('RL_LANG_DIR', $GLOBALS['languages'][RL_LANG_CODE]['Direction']);
    }

    /**
     * Get system languages by status
     *
     * @param  sting $status - Languages status 'active', 'approval', 'trash' or 'all'
     * @return array         - Languages list
     **/
    public function getLanguagesList($status = 'active')
    {
        global $rlDb;

        if (!$status) {
            return false;
        }

        static $language_cache = [];

        if (!$language_cache[$status]) {
            $options = null;
            $where = null;

            if ($status == 'all') {
                $options = "WHERE `Status` <> 'trash'";
            } else {
                $where = array('Status' => $status);
            }

            $order_column = 'Code`, IF(`Code` = "' . $GLOBALS['config']['lang'] . '", 1, 0) AS `Order';
            $columns = array($order_column, 'Key', 'Direction', 'Locale', 'Date_format', 'Status');

            $rlDb->setTable('languages');
            $rlDb->outputRowsMap = 'Code';
            $languages = $rlDb->fetch($columns, $where, $options . ' ORDER BY `Order` DESC');

            if ($languages) {
                $language_names = $rlDb->fetch(
                    ['Key', 'Value', 'Code'],
                    ['module' => 'common'],
                    "AND `Key` LIKE 'languages+name+%'",
                    null, 'lang_keys'
                );

                foreach ($languages as &$language) {
                    foreach ($language_names as $key => $name) {
                        if ($name['Code'] == $language['Code'] && strpos($name['Key'], $language['Key'])) {
                            $language['name'] = $name['Value'];
                            unset($language_names[$key]);
                            break;
                        }
                    }
                }

                $language_cache[$status] = $languages;
                unset($languages, $language_names);
            } else {
                return false;
            }
        }

        return $language_cache[$status];
    }

    /**
     * modify langs list for fronEnd
     *
     * @param sting $langList - languages status
     *
     * @return array - modified languages list
     **/
    public function modifyLanguagesList(&$langList)
    {
        global $page_info;

        foreach ($langList as $key => $value) {
            if ($langList[$key]['Code'] == $GLOBALS['config']['lang'] && $page_info['Controller'] != 'home') {
                $langList[$key]['dCode'] = "";
            } else {
                $langList[$key]['dCode'] = $langList[$key]['Code'] . "/";
            }

            if ($langList[$key]['Code'] == RL_LANG_CODE) {
                define('RL_DATE_FORMAT', $langList[$key]['Date_format']);
            }
        }
    }

    /**
     * Redirect user to preferred language (by browser or his choice) for the first visit
     *
     * @since 4.8.1 - Fixed typo in previous name of method
     *
     * @param  array $availableLanguages
     * @return bool
     */
    public function preferredLanguageRedirect($availableLanguages)
    {
        global $config;

        if ($_COOKIE['language_detected'] || $_GET['page'] || !$config['preffered_lang_redirect'] || IS_BOT) {
            return false;
        }

        if ($_COOKIE['userLangChoice'] && in_array($_COOKIE['userLangChoice'], array_keys($availableLanguages))) {
            $redirectLang = $_COOKIE['userLangChoice'];
        } elseif ($_COOKIE['rl_lang_front'] && in_array($_COOKIE['rl_lang_front'], array_keys($availableLanguages))) {
            $redirectLang = $_COOKIE['rl_lang_front'];
        } else {
            foreach ($availableLanguages as $k => $item) {
                $available_codes[] = strtolower($item['Code']);
                $return_codes[strtolower($item['Code'])] = $item['Code'];
            }

            $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

            preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
                "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
                $http_accept_language, $hits, PREG_SET_ORDER);

            $bestlang = $available_codes[0];
            $bestqval = 0;

            foreach ($hits as $arr) {
                $langprefix = strtolower($arr[1]);
                if (!empty($arr[3])) {
                    $langrange = strtolower($arr[3]);
                    $language = $langprefix . "-" . $langrange;
                } else {
                    $language = $langprefix;
                }

                $qvalue = 1.0;
                if (!empty($arr[5])) {
                    $qvalue = floatval($arr[5]);
                }

                if (in_array($language, $available_codes) && ($qvalue > $bestqval)) {
                    $bestlang = $language;
                    $bestqval = $qvalue;
                } else if (in_array($langprefix, $available_codes) && (($qvalue * 0.9) > $bestqval)) {
                    $bestlang = $langprefix;
                    $bestqval = $qvalue * 0.9;
                }
            }

            $redirectLang = $return_codes[$bestlang] ?: $bestlang;

            $GLOBALS['reefless']->createCookie('language_detected', true, time() + ($config['expire_languages'] * 86400));
        }

        if ($redirectLang != RL_LANG_CODE) {
            Util::redirect($GLOBALS['reefless']->getPageUrl('home', '', $redirectLang), true, 302);
        }
    }

    /**
     * Get phrase
     *
     * Get phrases by key and language code (optional),
     * It's also possible to pass params separately i.e. getPhrase($key, $lang_code, $assign, $dbcheck);
     *
     * @since 4.5.0
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $key      - Phrase key
     *                       - @param string $lang     - ISO language code (optional)
     *                       - @param string $assign   - Assign to variable
     *                       - @param string $db_check - Force database check
     *
     * @return string - Phrase
     */
    public function getPhrase($params)
    {
        global $lang;

        if (!is_array($params)) {
            $tmp = array();
            list($tmp['key'], $tmp['lang'], $tmp['assign'], $tmp['db_check']) = func_get_args();
            $tmp['assign'] = false;
            $params = $tmp;
        }

        if (!$params['key']) {
            return 'No phrase key specified';
        }

        $lang_code = $params['lang'] ?: RL_LANG_CODE;
        $set_key   = $params['key'];

        // Phrase by requested language
        if ($params['lang'] && RL_LANG_CODE != $params['lang']) {
            $params['db_check'] = true;
            $set_key = $params['lang'] . '_' . $set_key;
        }

        // Get category phrases
        if (strpos($set_key, 'categories+') === 0 && !$GLOBALS['rlLang']->categoriesAssigned) {
            $lang = array_merge($lang, $GLOBALS['rlLang']->getLangBySide('category'));
            $GLOBALS['rlLang']->categoriesAssigned = true;
        }

        // Lookup phrase
        if ($lang[$set_key]) {
            $phrase = $lang[$set_key];
        } elseif (!$lang[$set_key] && $params['db_check']) {
            $_where = "`Key` = '{$params['key']}' AND `Code` = '{$lang_code}'";
            $phrase = $GLOBALS['rlDb']->getOne('Value', $_where, 'lang_keys');

            if ($lang) {
                $lang[$set_key] = $phrase;
            }
        } else {
            $phrase = 'No phrase found by "' . $params['key'] . '" key';
        }

        $GLOBALS['rlHook']->load('getPhrase', $params, $phrase);

        // Assign phrase to the requested smarty variable
        if ($params['assign'] && is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $phrase);
        }
        // Return variable
        else {
            return $phrase;
        }
    }

    /**
     * Get system phrase, force DB fetch
     *
     * @since 4.8.1
     *
     * @param  string $key      - Phrase key
     * @param  string $langCode - Force lang code filter, user language will be used by default
     * @return string           - Phrase value
     */
    public function getSystem($key, $langCode = null)
    {
        return $this->getPhrase($key, $langCode, null, true);
    }

    /*** DEPRECATED METHODS ***/

    /**
     * @deprecated 4.8.1 - Typo in name of method, use instead preferredLanguageRedirect()
     * @param $available_languages
     */
    public function preferedLanguageRedirect($available_languages)
    {
        $this->preferredLanguageRedirect($available_languages);
    }
}
