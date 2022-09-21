<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: REEFLESS.CLASS.PHP
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
use Flynax\Utils\Category;
use Flynax\Component\Filesystem;

class reefless extends rlDb
{
    /**
     * @var int $time_limit - for net operations
     **/
    public $time_limit = 10;

    /**
     * @var int $attempts - attempts reached
     **/
    public $attempts = false;

    /**
     * @var int $attemptsLeft - attempts left
     **/
    public $attemptsLeft = false;

    /**
     * @var string $attemptsMessage - warning message text
     **/
    public $attemptsMessage = false;

    /**
     * User agent which system will send with other cURL options in request
     * @since 4.9.0
     * @var string
     */
    private $curlUserAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36';

    /**
     * Prevent url modifying in hookPhpUrlBottom hook
     *
     * @since 4.9.0
     * @var boolean
     */
    public $preventUrlModifying = false;

    /**
     * requires php class file, creates global class object
     *
     * @param string $className  - loaded class name
     * @param string $type       - class type ( null or admin )
     * @param string $plugin     - plugin folder name
     * @param mixed $class_param - class parameter (optional)
     **/
    public function loadClass($className, $type = null, $plugin = null, $class_param = null)
    {
        $className = ucfirst($className);
        $className = 'rl' . $className;

        global $$className;

        if (!is_object($$className)) {
            $path = $plugin ? RL_PLUGINS . $plugin . RL_DS : RL_CLASSES;

            if ($type === 'admin') {
                $fileSource = $path . 'admin' . RL_DS . $className . '.class.php';
            } else {
                $fileSource = $path . $className . '.class.php';
            }

            if (file_exists($fileSource)) {
                require_once $fileSource;
            } else {
                die("The '{$className}' class not found");
            }

            $GLOBALS[$className] = new $className($class_param);
        }
    }

    /**
     * Redirect to referer
     *
     * @param string $vars    - Add additional string to url
     * @param string $curLang - Code of current language
     * @param string $newLang - Code of language which must be added to url
     */
    public function referer($vars = '', $curLang = '', $newLang = '')
    {
        global $config, $rlDb, $page_info;

        $requestUrl = $_SERVER['HTTP_REFERER'] ?: $_SERVER['REQUEST_URI'];

        if ($config['mod_rewrite'] && $curLang) {
            $replace = $search = RL_URL_HOME;
            $search  .= $config['lang'] == $curLang ? '' : $curLang . '/';
            $replace .= $config['lang'] == $newLang ? '' : $newLang . '/';
            $requestUrl = str_replace($search, $replace, $requestUrl);

            // Replace path to multilingual path of page by selected language code
            if ($newLang && $config['multilingual_paths'] && $page_info['prev']) {
                $pagePath = explode('/', parse_url($requestUrl)['path']);
                $pagePath = str_replace('.html', '', end($pagePath));
                $pagePath = urldecode($pagePath);

                $select = $newLang === $config['lang'] ? 'Path' : "Path_{$newLang}";
                if ($multilingualPath = $rlDb->getOne($select, "`Key` = '{$page_info['prev']}'", 'pages')) {
                    $requestUrl = str_replace($pagePath, urlencode($multilingualPath), urldecode($requestUrl));
                }
            }
        }

        if (!empty($vars)) {
            $requestUrl .= (false !== strpos($requestUrl, '?') ? '&' : '?') . $vars;
        }

        // Remove logout handler from URL for logged user
        if ($_SESSION['remove_logout_handler'] && false !== strpos($requestUrl, '?logout')) {
            unset($_SESSION['remove_logout_handler']);
            $requestUrl = str_replace('?logout', '', $requestUrl);
        }

        header('Location: ' . $requestUrl);
        exit;
    }

    /**
     * Redirect to some url with necessary http_response_code
     *
     * @since 4.6.0 - Added "$http_response_code" parameter
     *
     * @param array  $vars               - Additional parameters to url
     * @param string $target             - Target url
     * @param int    $http_response_code
     */
    public function redirect($vars = null, $target = '', $http_response_code = 301)
    {
        global $rlHook, $config;

        if (!$vars && !$target) {
            return false;
        }

        if ($target) {
            // > 4.1.0
            // @since 4.6.0 added $http_response_code parameter
            $rlHook->load('reeflessRedirctTarget', $target, $http_response_code);

            header('Location: ' . $target, true, $http_response_code);
            exit;
        }

        if (defined('REALM')) {
            $request_url = str_replace(trim(RL_DIR, RL_DS), '', $_SERVER['PHP_SELF']);
            $request_url = trim($request_url, '/');
        } else {
            // $request_url = str_replace( array(trim(RL_DIR, RL_DS),trim(RL_LANG_CODE, RL_DS) ), '', $_SERVER['REQUEST_URI']);
            // $request_url = trim($request_url, '/');
            $dir_name = trim(RL_DIR, RL_DS);
            $lang_name = trim(RL_LANG_CODE, RL_DS);

            $pattern = '#(?:';
            if ($dir_name) {
                $pattern .= '/' . $dir_name . '|';
            }
            $pattern .= '/' . $lang_name . '/)#';
            $request_url = preg_replace($pattern, '', $_SERVER['REQUEST_URI']);
            $request_url = trim($request_url, '/');
        }

        if (!$config['mod_rewrite']) {
            $request_url = preg_replace('/^(index\\.php)/', '', $request_url);
        }

        $desktop_base = defined('REALM') ? RL_URL_HOME : SEO_BASE;
        $base = $desktop_base;
        $request_url = $base . $request_url;

        if (is_array($vars)) {
            if (defined('REALM')) {
                $request_url .= "?";
            } else {
                if (false !== strpos($request_url, '?')) {
                    $request_url .= "&";
                } else {
                    $request_url .= "?";
                }
            }

            foreach ($vars as $var => $value) {
                $request_url .= $var . "=" . $value . "&";
            }
            $request_url = substr($request_url, 0, -1);
        }

        // @since 4.6.0 added $vars, $http_response_code parameters
        $rlHook->load('reeflessRedirctVars', $request_url, $vars, $http_response_code);

        $GLOBALS['rlDb']->connectionClose();

        header('Location: ' . $request_url, true, $http_response_code);
        exit;
    }

    /**
     * refresh page
     *
     * @param array $varString - additional url array
     *
     * @return redirect
     **/
    public function refresh()
    {
        $GLOBALS['rlDb']->connectionClose();

        header("Refresh:0");
        exit;

        // $addUrl = str_replace( RL_DIR, '', $_SERVER['REQUEST_URI']);
        // $addUrl = trim($addUrl, '/');
        // $refresh = RL_URL_HOME;
        // $refresh .= $addUrl;
        // header( "Location: " . $refresh );
        // exit;
    }

    /**
     * check admin session expire time
     **/
    public function checkSessionExpire()
    {
        $sess_exp = session_cache_expire() * 60;

        if (isset($_SESSION['admin_expire_time']) && time() - $_SESSION['admin_expire_time'] >= $sess_exp) {
            return false;
        } else {
            $_SESSION['admin_expire_time'] = $_SERVER['REQUEST_TIME'];
            return true;
        }
    }

    /**
     * read directory
     *
     * @param string $dir - directory path
     * @param bool $dir_mode - read directories only
     * @param bool $type - require content type
     *
     * @return directory content
     **/
    public function scanDir($dir = null, $dir_mode = false, $type = false)
    {
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                $index = 0;
                while (($file = readdir($dh)) !== false) {
                    if (!preg_match('/^\.{1,2}/', $file)) {
                        if ($type) {
                            $content[$index]['name'] = $file;
                            $content[$index]['type'] = filetype($dir . $file);
                            $index++;
                        } else {
                            if ($dir_mode) {
                                if (is_dir($dir . $file)) {
                                    $content[] = $file;
                                }
                            } else {
                                $content[] = $file;
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }

        return $content;
    }

    /**
     * Get page content
     *
     * @param  string $url - Page url
     * @return mixed       - Page content or null
     */
    public function getPageContent($url)
    {
        $content = null;

        if (extension_loaded('curl')) {
            $ch = curl_init();

            // localhost usage mode
            if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->time_limit);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->time_limit);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->curlUserAgent);
            curl_setopt($ch, CURLOPT_REFERER, RL_URL_HOME);
            $content = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $default = ini_set('default_socket_timeout', $this->time_limit);
            $stream = fopen($url, "r");
            ini_set('default_socket_timeout', $default);

            if ($stream) {
                while (!feof($stream)) {
                    $content .= fgets($stream, 4096);
                }
                fclose($stream);
            }
        } else {
            $GLOBALS['rlDebug']->logger("Unable to get content from: {$url}");
            return 'Unable to get content from: ' . $url;
        }

        return $content;
    }

    /**
     * Deletion of directory (recursively)
     *
     * @param string $dirName - Directory name
     * @param bool   $passive - Passive mode, remove the requested directory in case if it is empty
     *
     * @return bool
     */
    public function deleteDirectory($dirName = '', $passive = false)
    {
        $dirName = (string) $dirName;

        if (!$dirName || realpath($dirName) === realpath(RL_FILES)) {
            $backtrace = serialize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
            $error = "Folder deletion failed, folder is missing or it's a system \"/files/\" directory (stack: {$backtrace})";
            $GLOBALS['rlDebug']->logger($error);
            return false;
        }

        if (is_dir($dirName)) {
            $dir_handle = opendir($dirName);
        }

        if (!$dir_handle) {
            return false;
        }

        // passive mode
        if ($passive) {
            $empty = true;
            $file = readdir($dir_handle);

            while ($file = readdir($dir_handle)) {
                if ($file != "." && $file != "..") {
                    $empty = false;
                }
            }

            if ($empty) {
                rmdir($dirName);
            }

            return true;
        }
        while ($file = readdir($dir_handle)) {
            if ($file != '.' && $file != '..') {
                if (!is_dir($dirName . RL_DS . $file)) {
                    unlink($dirName . RL_DS . $file);
                } else {
                    $this->deleteDirectory($dirName . RL_DS . $file);
                }
            }
        }

        closedir($dir_handle);
        rmdir($dirName);

        return true;
    }

    /**
     * Get tmp file
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $field  - File input name
     *                       - @param string $parent - Parent file input name,
     *                                               - Ex: ... name="profile[photo]", 'profile' is parent, 'photo' is field
     *                       - @param string $dom    - ID of the dom object
     */
    public function getTmpFile($aParams = false)
    {
        global $lang, $l_deny_files_regexp, $rlHook;

        $field  = $aParams['field'];
        $parent = $aParams['parent'];
        $id     = $aParams['id'] ? $field . '_' . $aParams['id'] . '_tmp' : false;

        if ($_FILES[$parent]['name'][$field] || $_FILES[$field]['name']) {
            $file_name = $parent && $_FILES[$parent] ? $_FILES[$parent]['name'][$field] : $_FILES[$field]['name'];

            /* prevent denied files upload */
            if (preg_match($l_deny_files_regexp, $file_name)) {
                return false;
            }

            $file_name = mt_rand() . '_' . $file_name;
            $file_type = $parent && $_FILES[$parent] ? $_FILES[$parent]['type'][$field] : $_FILES[$field]['type'];
            $file_tmp_dir = $parent && $_FILES[$parent] ? $_FILES[$parent]['tmp_name'][$field] : $_FILES[$field]['tmp_name'];
            $file_dir = RL_UPLOAD . $file_name;

            /**
             * @since 4.5.2 - $aParams, $file_tmp_dir, $file_dir
             */
            $rlHook->load('phpGetTmpFileFromFiles', $aParams, $file_tmp_dir, $file_dir);

            if (move_uploaded_file($file_tmp_dir, $file_dir)) {
                $GLOBALS['reefless']->rlChmod($file_dir);

                $content = "<input type=\"hidden\" name=\"{$parent}[sys_exist_{$field}]\" value=\"{$file_name}\" />";

                /* print image */
                if (strpos($file_type, 'image') !== false && is_readable($file_dir)) {
                    $file_info = getimagesize(RL_UPLOAD . $file_name);
                    $resize_type = $file_info[0] > $file_info[1] ? 'width' : 'height';

                    $content .= '<img alt="" title="' . $file_name . '" style="' . $resize_type;
                    $content .= ': 250px;" src="' . RL_URL_HOME . 'tmp/upload/' . $file_name . '" />';
                }
                /* print file */
                else {
                    $file_name_display = substr($file_name, strpos($file_name, '_') + 1);
                    $content .= '<span style="font-style:italic;" title="' . $file_name . '"><b>' . $file_name_display . '</b></span>';
                }

                if ($parent) {
                    $_SESSION['tmp_files'][$parent][$field] = $file_name;
                } else {
                    $_SESSION['tmp_files'][$field] = $file_name;
                }
            } else {
                trigger_error("Can't move uploaded file", E_USER_WARNING);
                $GLOBALS['rlDebug']->logger("Can't move uploaded file");
            }
        } elseif (($_SESSION['tmp_files'][$parent][$field] && is_readable(RL_UPLOAD . $_SESSION['tmp_files'][$parent][$field])) || ($_SESSION['tmp_files'][$field] && is_readable(RL_UPLOAD . $_SESSION['tmp_files'][$field]))) {
            $file_name = $_SESSION['tmp_files'][$parent][$field] ? $_SESSION['tmp_files'][$parent][$field] : $_SESSION['tmp_files'][$field];
            $file_info = getimagesize(RL_UPLOAD . $file_name);

            /**
             * @since 4.5.2 - $file_name, $file_info
             */
            $rlHook->load('phpGetTmpFileFromTmp', $file_name, $file_info);

            /* print image */
            if (strpos($file_info['mime'], 'image') !== false) {
                $resize_type = $file_info[0] > $file_info[1] ? 'width' : 'height';

                $content = "<input type=\"hidden\" name=\"{$parent}[sys_exist_{$field}]\" value=\"{$file_name}\" />";
                $content .= '<img class="thumbnail" alt="" title="' . $file_name . '" style="' . $resize_type;
                $content .= ': 250px;" src="' . RL_URL_HOME . 'tmp/upload/' . $file_name . '" />';
            }
            /* print file */
            else {
                $file_name_display = substr($file_name, strpos($file_name, '_') + 1);
                $content = '<span style="font-style:italic;" title="' . $file_name . '"><b>' . $file_name_display . '</b></span>';
            }
        }

        if ($content) {
            $side = RL_LANG_DIR == 'rtl' ? 'right' : 'left';
            $out = '<div class="fleft file-data" data-id="' . $id . '" data-field="' . $field . '" data-parent="' . $parent . '" style="margin: 0 0 10px;">'
                . '<div style="padding: 8px 0 4px;">'
                . '<table class="sTable"><tr><td>'
                . $lang['currently_uploaded_file']
                . '</td><td class="ralign" style="padding-' . $side . ': 10px;">'
                . '<span class="link remove-file">'
                . $lang['remove']
                . ' <img class="icon delete" src="' . RL_TPL_BASE . 'img/blank.gif" />'
                . '</span>'
                . '</td></tr></table>'
                . '</div>'
                . $content
                . '</div><div class="clear"></div>';
        }

        return $out;
    }

    /**
     * generate hash string
     *
     * @param int $number - number of characters
     * @param string $case - case of characters (lower, upper, hex, password)
     * @param bool $numbers - include numbers
     *
     **/
    public function generateHash($number = 32, $case = 'lower', $numbers = true)
    {
        switch ($case) {
            case 'lower':
                $chars = range('a', 'z');
                break;
            case 'upper':
                $chars = range('A', 'Z');
                break;
            case 'hex':
                $chars = range('A', 'F');
                break;
            case 'password':
                if (!$numbers && function_exists('random_bytes')) {
                    return random_bytes($number);
                } else {
                    $chars = range('a', 'z');
                    $chars = array_merge($chars, range('A', 'Z'));
                    $chars = array_merge($chars, array('!', '@', '#', '^', '*', '(', ')', '[', ']'));
                }
                break;
        }

        for ($i = 0; $i < $number; $i++) {
            $turn = $numbers ? rand(0, 1) : 0;

            if ($turn) {
                $string .= rand(0, 9);
            } else {
                $index = rand(0, count($chars) - 1);
                $string .= $chars[$index];
            }
        }

        return $string;
    }

    /**
     * Create a new writable directory
     *
     * @param string $pathname - the directory path
     * @return bool
     **/
    public function rlMkdir($pathname)
    {
        if (!is_string($pathname) || $pathname == '') {
            return false;
        }

        $dirs = explode(RL_DS, str_replace(RL_ROOT, '', $pathname));
        $directory = RL_ROOT;

        /**
         * @since 4.5.2 - $pathname, $dirs was added
         */
        $GLOBALS['rlHook']->load('phpMkDir', $pathname, $dirs);

        foreach ($dirs as $next) {
            $directory .= $next . RL_DS;

            if (is_dir($directory)) {
                $this->rlChmod($directory);
            } else {
                if (false !== mkdir($directory)) {
                    $this->rlChmod($directory);
                }
            }
        }
        return is_writable($pathname);
    }

    /**
     * Set writable permisions
     *
     * @param string $filename - Path to the file/directory
     * @return bool
     **/
    public function rlChmod($filename)
    {
        if (!is_string($filename) || $filename == '') {
            return false;
        }

        /**
         * @since 4.5.2 - $filename was added
         */
        $GLOBALS['rlHook']->load('phpChmod', $filename);

        $result = chmod($filename, 0755);

        if ($result === true && !is_writable($filename)) {
            $result = chmod($filename, 0777);
        }
        return $result;
    }

    /**
     * parse multilingual value
     *
     * @param string $string - value string
     * @param string $lang - return value by request language code
     *
     * @return array - values by languages, ex: array('en' => val, 'gr' => val2)
     *
     **/
    public function parseMultilingual($string = false, $lang = false)
    {
        global $config;

        preg_match_all('/\{\|([a-zA-Z]{2})\|\}(.*?){\|\/[a-zA-Z]{2}\|\}/smi', $string, $matches);
        //preg_match_all('/\{\|([a-zA-Z]{2})\|\}([^\{\|]*){\|\/[a-zA-Z]{2}\|\}/', $string, $matches);
        $codes = $matches[1];
        $values = $matches[2];

        if ($codes && $values) {
            foreach ($codes as $index => $code) {
                if ($values[$index]) {
                    $out[$code] = $values[$index];
                }
            }
        } else {
            $out[RL_LANG_CODE] = $string;
        }

        if ($lang) {
            if ($out[$lang]) {
                return $out[$lang];
            } elseif ($out[$config['lang']]) {
                return $out[$config['lang']];
            } else {
                return current($out);
            }
        }

        return $out ? $out : false;
    }

    /**
     * Parse phone value
     *
     * @since 4.9.0 - Added $encode parameter
     *
     * @param string $string - Value string
     * @param array  $field  - Field details, format phone number
     * @param bool   $encode - Hide denied digits of number
     *
     * @return array|string  - Phone details, ex: array('code' => val, 'area' => val2, 'number' => val3, 'ext' => val4)
     *                       - or phone string (if field details passed)
     */
    public function parsePhone($string = false, $field = false, bool $encode = true)
    {
        global $config, $lang;

        preg_match('/(c:([0-9]+))?\|?(a:([0-9]+))?\|(n:([0-9]+))?\|?(e:([0-9]+))?/', $string, $matches);

        if (!$matches) {
            return $string;
        }

        $out['code']   = $matches[2];
        $out['area']   = $matches[4];
        $out['number'] = $matches[6];
        $out['ext']    = $matches[8];

        if ($field) {
            $phone = '';
            if ($field['Opt1'] && $out['code']) {
                $phone .= '+' . $out['code'] . ' ';
            }
            if ($out['area']) {
                $phone .= "({$out['area']}) ";
            }
            if ($out['number']) {
                $phone .= $this->flStrSplit($out['number'], 4, '-');
            }
            if ($field['Opt2'] && $out['ext']) {
                $phone .= ' ' . $lang['phone_ext_out'] . $out['ext'];
            }

            if ($encode && $field['Hidden'] && $config['hidden_phone_numbers'] == 0) {
                $phone = preg_replace('/[\d]/', 'X', $phone);
            } else if ($encode && $field['Hidden'] && $config['hidden_phone_numbers'] > 0) {
                $revertedPhone       = strrev($phone);
                $countReplacedDigits = 0;
                $hiddenPhone         = '';

                for ($i = 0; $i < strlen($revertedPhone); $i++) {
                    if (is_numeric($revertedPhone[$i])) {
                        if ($countReplacedDigits < $config['hidden_phone_numbers']) {
                            $hiddenPhone .= 'X';
                            $countReplacedDigits++;
                        } else {
                            $hiddenPhone .= $revertedPhone[$i];
                        }
                    } else {
                        $hiddenPhone .= $revertedPhone[$i];
                    }
                }

                $phone = strrev($hiddenPhone);
            }

            return $phone;
        }

        return $out;
    }

    /**
     * fit char to the string by requested position
     *
     * @param string $string - string
     * @param int $pos - position in string to insert char to
     * @param string $char - char to be inserted
     *
     * @return array - formated string
     *
     **/
    public function flStrSplit($string = false, $pos = false, $char = '-')
    {
        if (!$string || !$char || !$pos) {
            return $string;
        }

        $splitted = str_split($string, $pos - 1);
        $out = $splitted[0] . $char;
        array_shift($splitted);
        $out .= join($splitted);

        return $out;
    }

    /**
     * array multisort
     *
     * @param array $array - array to sort | by referent
     * @param string $field - field name to sort by
     * @param constant $sort_type - sorting type (array_multisort() function default params)
     *
     **/
    public function rlArraySort(&$array, $field = false, $sort_type = SORT_ASC)
    {
        if (!$array || !$field) {
            return $array || false;
        }

        foreach ($array as &$value) {
            $sort[] = strtolower($value[$field]);
        }

        array_multisort($sort, $sort_type, $array);
        unset($sort);
    }

    /**
     * touch directory
     *
     * @param array $array - array to sort | by referent
     * @param string $field - field name to sort by
     * @param constant $sort_type - sorting type (array_multisort() function default params)
     *
     **/
    public function flTouch($dir = RL_ROOT, $ext = 'tpl')
    {
        $files = $this->scanDir($dir, false, true);

        if ($files) {
            foreach ($files as $file) {
                if ($file['type'] == 'dir') {
                    $this->flTouch(rtrim($dir) . RL_DS . $file['name'] . RL_DS, $ext);
                } elseif ($file['type'] == 'file') {
                    if ($ext) {
                        $file_ext = array_reverse(explode('.', $file['name']));
                        if ($file_ext[0] == $ext) {
                            touch(rtrim($dir) . RL_DS . $file['name']);
                        }
                    } else {
                        touch(rtrim($dir) . RL_DS . $file['name']);
                    }
                }
            }
        }
    }

    /**
     * "www." prefix redirect, relates to RL_URL_HOME config
     *
     * @since 4.6.0
     * @todo  - Redirect to www.domain.com or domain.com, depended of RL_URL_HOME config
     *        - Redirect to "https" protocol if necessary
     *
     * @param bool $admin - Admin panel mode
     */
    public function baseUrlRedirect($admin = false)
    {
        global $rlValid;

        $host_s = $GLOBALS['rlValid']->getDomain(RL_URL_HOME); // system host
        $host_r = $_SERVER['HTTP_HOST']; // requested host

        preg_match('/^(www\.)?(.*)/', $host_s, $matches_s);
        preg_match('/^(www\.)?(.*)/', $host_r, $matches_r);

        $request_url = $admin ? ADMIN . '/' : ltrim($_SERVER['REQUEST_URI'], '/');

        // adapt relative path for website installed in sub-directories
        if (strpos($request_url, RL_DIR) === 0) {
            $request_url = substr($request_url, strlen(RL_DIR));
        }

        // www mismatch
        if ($matches_s[1] != $matches_r[1] && $matches_s[2] == $matches_r[2]) {
            $redirect_target = RL_URL_HOME . $request_url;
        }
        // Scheme mismatch
        elseif (strpos(RL_URL_HOME, 'https') === 0 && !$this->isHttps()) {
            $redirect_target = $GLOBALS['domain_info']['scheme'] . '://' . $host_r . '/' . $request_url;
        }

        /**
         * @since 4.5.2
         */
        $GLOBALS['rlHook']->load('reeflessWwwRedirect', $redirect_target, $admin);

        if ($redirect_target) {
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirect_target);
            exit;
        }
    }

    /**
     * login attempt control
     *
     * @param bool $admin - admin panel mode
     *
     * @todo - count available attempts count
     **/
    public function loginAttempt($admin = false)
    {
        global $config, $rlSmarty, $lang;

        $mode = $admin ? 'admin' : 'user';

        if (!$config['security_login_attempt_' . $mode . '_module']) {
            return;
        }

        $client_ip = $this->getClientIpAddress();
        $sql = "SELECT `IP` AS `Count` FROM `{db_prefix}login_attempts` ";
        $sql .= "WHERE `IP` = '{$client_ip}' AND `Status` = 'fail' AND `Interface` = '{$mode}' ";
        $sql .= "GROUP BY `Date` ";
        $sql .= "HAVING TIMESTAMPDIFF(HOUR, `Date`, NOW()) < {$config['security_login_attempt_' . $mode . '_period']} ";

        $attempts = $this->getAll($sql);
        $count = count($attempts);

        if ($count) {
            $this->attempts = $count;
            $this->attemptsLeft = $config['security_login_attempt_' . $mode . '_attempts'] - $count;

            $message = preg_replace('/(\[([^\]].*)\])/', '<span class="red">$2</span>', str_replace('{number}', '<b>' . $this->attemptsLeft . '</b>', $lang['login_attempt_warning']));
            $this->attemptsMessage = $message;
            $rlSmarty->assign('loginAttemptsMess', $message);
        } else {
            $this->attempts = 0;
            $this->attemptsLeft = $config['security_login_attempt_' . $mode . '_attempts'] ? $config['security_login_attempt_' . $mode . '_attempts'] : 1;
        }

        $rlSmarty->assign('loginAttempts', $this->attempts);
        $rlSmarty->assign('loginAttemptsLeft', $this->attemptsLeft);
    }

    /**
     * set current timezone to PHP and MySQL
     **/
    public function setTimeZone()
    {
        global $config, $l_timezone;

        if (!$config['timezone']) {
            return;
        }

        /* set PHP timezone */
        @date_default_timezone_set($config['timezone']);

        /* set MySQL timezone */
        $this->query("SET time_zone = '{$l_timezone[$config['timezone']][0]}'");
    }

    /**
     * Get client IP address | DEPRICATED
     * Use rlUtils::getClientIP() instead
     *
     * @since v4.2
     */
    public function getClientIpAddress()
    {
        return Util::getClientIP();
    }

    /**
     * Detect engine bots
     *
     * @since v4.2
     */
    public function isBot()
    {
        // if no user agent is supplied then assume it's a bot
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        // array of bots
        $bots = array(
            "google", "bot", "radian",
            "yahoo", "spider", "crawl",
            "archiver", "curl", "yandex",
            "python", "nambu", "eventbox",
            "twitt", "perl", "monitor",
            "sphere", "PEAR", "mechanize",
            "java", "wordpress", "facebookexternal",
            "^PHP",
        );

        foreach ($bots as $bot) {
            if ((bool) preg_match("/{$bot}/i", $_SERVER['HTTP_USER_AGENT'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get location by address via Google Geocoder
     * @param  array $location              - Location data
     * @param  array $array_to_add_location - Link to an array to append data to
     * @return mixed                        - Boolean true or array with coordinates
     **/
    public function geocodeLocation($location = false, &$array_to_add_location = null)
    {
        $query = is_array($location) ? implode(', ', array_filter($location)) : $location;

        if (!$query) {
            return false;
        }

        $address  = '';
        $lat      = '';
        $lng      = '';

        if ($data = Util::geocoding($query)) {
            $address  = $data[0]['location'];
            $lat      = $data[0]['lat'];
            $lng      = $data[0]['lng'];
        }

        if (is_array($array_to_add_location)) {
            $array_to_add_location['Loc_address']   = $address;
            $array_to_add_location['Loc_latitude']  = $lat;
            $array_to_add_location['Loc_longitude'] = $lng;

            return !($lat == '' || $lng == '');
        } else {
            return array(
                'Loc_address'   => $address,
                'Loc_latitude'  => $lat,
                'Loc_longitude' => $lng
            );
        }

        return false;
    }

    /**
     * Get Page/Listing/Category URL
     *
     * @param string $mode       - Get url mode (listing|category|page)
     * @param mixed  $data       - Requested page data
     * @param string $customLang - Set necessary language of content
     *
     * @return string - Url of listing/category/page
     */
    public function url($mode = 'page', $data = null, $customLang = '')
    {
        global $config, $rlDb, $page_info, $rlListingTypes, $domain_info, $listing_type, $rlHook;

        if (!$data) {
            return false;
        }

        if (!$rlListingTypes) {
            $this->loadClass('ListingTypes');
        }

        if ($customLang) {
            if ($customLang != $config['lang']) {
                $url = RL_URL_HOME;
                $url .= $config['mod_rewrite'] ? "{$customLang}/" : "index.php?language={$customLang}";
            } else {
                $url = $config['mod_rewrite'] ? RL_URL_HOME : RL_URL_HOME . 'index.php';
            }
        } else {
            $url = defined('SEO_BASE') ? SEO_BASE : RL_URL_HOME;
        }

        $firstNonModrewriteSign = '?';

        if ($customLang && $customLang != $config['lang']) {
            $firstNonModrewriteSign = '&';
        }

        if (defined('REALM') && REALM == 'admin'
            && !$config['mod_rewrite']
            && false === strpos($url, 'index.php')
        ) {
            $url .= 'index.php';
        }

        $pages = $GLOBALS['pages'];

        if ($config['multilingual_paths']) {
            $customLang = $customLang ?: RL_LANG_CODE;
            $pageKey    = '';

            switch ($mode) {
                case 'page':
                    $pageKey = is_array($data) && isset($data['key']) ? $data['key'] : $data;
                    break;
                case 'category':
                    if (isset($data['Type']) && isset($page_info) && $page_info['Key'] == 'lt_' . $data['Type']) {
                        $pageKey = $page_info['Key'];
                    }
                    break;
                case 'listing':
                    if (isset($data['Listing_type'])
                        && isset($listing_type)
                        && $listing_type['Key'] === $data['Listing_type']
                    ) {
                        $pageKey = 'view_details';
                    }
                    break;
            }

            if ($GLOBALS["pages_{$customLang}"]) {
                $pages = $GLOBALS["pages_{$customLang}"];
            } elseif ($pageKey && $page_info['Key'] === $pageKey) {
                if ($mode === 'listing' && isset($listing_type)) {
                    $pages[$listing_type['Page_key']] = $page_info["Path_{$customLang}"]
                        ?: ($page_info["Path_{$config['lang']}"] ?: $page_info['Path']);
                } else {
                    $pages[$page_info['Key']] = $page_info["Path_{$customLang}"]
                        ?: ($page_info["Path_{$config['lang']}"] ?: $page_info['Path']);
                }
            } elseif (!$pages
                || (defined('RL_LANG_CODE') && $customLang !== RL_LANG_CODE)
                || (!defined('RL_LANG_CODE') && $customLang !== $config['lang'])
            ) {
                $GLOBALS["pages_{$customLang}"] = Util::getPages(
                    ['Key', 'Path'],
                    ['Status' => 'active'],
                    null,
                    ['Key', 'Path'],
                    $customLang
                );

                $pages = $GLOBALS["pages_{$customLang}"];
            }
        }

        switch ($mode) {
            case 'listing':
                if (is_int($data)) {
                    $listing = $rlDb->fetch('*', ['ID' => $data], null, null, 'listings', 'row');
                } else {
                    $listing = $data;
                }

                if (!$listing) {
                    return false;
                }

                $lang         = $customLang ?: RL_LANG_CODE;
                $categoryID   = $listing['Category_ID'];
                $categoryPath = $listing['Cat_path'] ?: ($listing['Path'] ?: $listing['Category_path']);

                if ($config['multilingual_paths']) {
                    if (isset($listing["Path_{$lang}"])) {
                        $multilingualPath = !empty($listing["Path_{$lang}"]) ? $listing["Path_{$lang}"] : $categoryPath;
                    } else {
                        $multilingualPath = null;
                    }

                    if ($GLOBALS["categories_{$lang}"][$categoryID]) {
                        $categoryPath = $GLOBALS["categories_{$lang}"][$categoryID];
                    } else {
                        if ($lang !== $config['lang']) {
                            if ($multilingualPath) {
                                $GLOBALS["categories_{$lang}"][$categoryID] = $multilingualPath;
                            } else {
                                $multilingualPath = Category::getCategoryMultilingualPath($categoryID, $lang);
                                $GLOBALS["categories_{$lang}"][$categoryID] = $multilingualPath ?: $categoryPath;
                            }
                        } else {
                            if ($categoryPath) {
                                $GLOBALS["categories_{$lang}"][$categoryID] = $categoryPath;
                            } else {
                                $GLOBALS["categories_{$lang}"][$categoryID] = $GLOBALS['rlCategories']->getCatPath(
                                    $listing['Category_ID']
                                );
                            }
                        }

                        $categoryPath = $GLOBALS["categories_{$lang}"][$categoryID] ?: $categoryPath;
                    }
                }

                if (!$categoryPath && $GLOBALS['category']['Path']) {
                    $categoryPath = $GLOBALS['category']['Path'];
                }

                if ((!$listing['Listing_type'] || !$categoryPath) && $listing['Category_ID']) {
                    $category_info = $rlDb->fetch(
                        ['Path', 'Type'],
                        ['ID' => $listing['Category_ID']],
                        null, null, 'categories', 'row'
                    );

                    $listing['Listing_type'] = $listing['Listing_type'] ?: $category_info['Type'];
                    $categoryPath = $categoryPath ?: $category_info['Path'];
                }

                if (!$listing_type || $listing_type['Key'] != $listing['Listing_type']) {
                    if ($rlListingTypes && $listing['Listing_type']) {
                        $lType = $rlListingTypes->types[$listing['Listing_type']];
                    }
                } else {
                    $lType = $listing_type;
                }

                if (!$listing['listing_title'] && $GLOBALS['listing_title']) {
                    $listing['listing_title'] = $GLOBALS['listing_title'];
                }

                if (!$listing['listing_title'] || ($customLang && $customLang != RL_LANG_CODE)) {
                    $listing['listing_title'] = $GLOBALS['rlListings']->getListingTitle(
                        $listing['Category_ID'],
                        $listing,
                        $lType['Key'],
                        $customLang
                    );
                }

                $pagePath = $pages[$lType['Page_key']];

                if (!$pagePath && $lType['Page_key']) {
                    $pagePath = $rlDb->getOne('Path', "`Key` = '{$lType['Page_key']}'", 'pages');
                }

                if ($config['mod_rewrite']) {
                    if (!$lType['Page_key'] && $GLOBALS['category']['Type']) {
                        $lType['Page_key'] = 'lt_' . $GLOBALS['category']['Type'];
                    }

                    if ($lType['Links_type'] == 'full') {
                        $url .= $pagePath . '/';
                    } elseif ($lType['Links_type'] == 'subdomain') {
                        $url = preg_replace('#http(s)?://(www.)?#', "http$1://" . $pagePath . '.', $url);
                    }

                    $basePartUrl = $url;

                    $additionalPartUrl = $categoryPath . '/';
                    $additionalPartUrl .= $GLOBALS['rlValid']->str2path($listing['listing_title']);
                    $additionalPartUrl .= '-' . $listing['ID'] . '.html';

                    /*
                     * @since 4.8.1
                     */
                    $rlHook->load('phpListingUrl', $basePartUrl, $additionalPartUrl, $listing);

                    $url = $basePartUrl . $additionalPartUrl;
                } else {
                    $url .= $firstNonModrewriteSign . 'page=' . $pagePath . '&id=' . $listing['ID'];
                }

                break;

            case 'page':
                $pageKey = is_array($data) ? $data['key'] : $data;
                $path    = $pages[$pageKey];
                $addUrl  = '';
                $addVars = '';

                if (!$path && $pageKey != 'home') {
                    return 'Error: no such page found';
                }

                if (is_array($data['add_url'])) {
                    foreach ($data['add_url'] as $key => $value) {
                        if ($config['mod_rewrite']) {
                            $addUrl .= '/' . $value;
                        } else {
                            $addUrl .= is_numeric($key) ? '&' . $value : '&' . $key . '=' . $value;
                        }
                    }
                }

                if ($data['vars']) {
                    $addVars .= $config['mod_rewrite'] ? '?' : '&';
                    $addVars .= $data['vars'];
                }

                if ($path || $data['key'] === 'home') {
                    $listingTypeData = 0 === strpos($pageKey, 'lt_')
                    ? $rlListingTypes->types[str_replace('lt_', '', $pageKey)]
                    : null;

                    if ($listingTypeData
                        && $listingTypeData['Links_type'] === 'subdomain'
                        && $config['mod_rewrite']
                    ) {
                        $url = "{$domain_info['scheme']}://{$path}{$domain_info['domain']}{$domain_info['path']}";
                        $url .= $customLang != $config['lang'] ? "{$customLang}/" : '';
                        $url .= $addVars;
                    } else {
                        $url .= $config['mod_rewrite']
                        ? $path . $addUrl . ($path ? '.html' : '') . $addVars
                        : $firstNonModrewriteSign . 'page=' . $path . $addUrl . $addVars;
                    }
                }
                break;

            case 'category':
                $lang = $customLang ?: RL_LANG_CODE;

                if (is_numeric($data)) {
                    $data   = (int) $data;
                    $select = ['ID', 'Key', 'Type', 'Path'];

                    if ($config['multilingual_paths'] && $lang !== $config['lang']) {
                        $select[] = "Path_{$lang}";
                    }

                    $category = $rlDb->fetch($select, ['ID' => $data], null, null, 'categories', 'row');
                } else if (is_array($data)) {
                    $category = $data;
                } else {
                    return false;
                }

                $categoryID   = $category['ID'];
                $categoryPath = $category['Path'];

                if ($config['multilingual_paths']) {
                    if (isset($category["Path_{$lang}"])) {
                        $multilingualPath = !empty($category["Path_{$lang}"])
                        ? $category["Path_{$lang}"]
                        : $categoryPath;
                    } else {
                        $multilingualPath = null;
                    }

                    if ($GLOBALS["categories_{$lang}"][$categoryID]
                        || ($GLOBALS['category']["Path_{$lang}"] && $GLOBALS['category']['Key'] === $data['Key'])
                    ) {
                        if ($GLOBALS['category']["Path_{$lang}"] && !$GLOBALS["categories_{$lang}"][$categoryID]) {
                            $GLOBALS["categories_{$lang}"][$categoryID] = $GLOBALS['category']["Path_{$lang}"];
                        }

                        $categoryPath = $GLOBALS["categories_{$lang}"][$categoryID];
                    } else {
                        if ($lang !== $config['lang']) {
                            if ($multilingualPath) {
                                $GLOBALS["categories_{$lang}"][$categoryID] = $multilingualPath;
                            } else {
                                $multilingualPath = Category::getCategoryMultilingualPath($categoryID, $lang);
                                $GLOBALS["categories_{$lang}"][$categoryID] = $multilingualPath ?: $categoryPath;
                            }
                        }
                        // Get a value from emulated column for default language
                        elseif ($multilingualPath) {
                            $GLOBALS["categories_{$lang}"][$categoryID] = $multilingualPath;
                        }

                        if (!$GLOBALS["categories_{$lang}"][$categoryID] && $lang === $config['lang']) {
                            $GLOBALS["categories_{$lang}"][$categoryID] = $categoryPath;
                        }
                    }

                    $categoryPath = $GLOBALS["categories_{$lang}"][$categoryID] ?: $categoryPath;
                }

                $listingType = $rlListingTypes->types[$category['Type']];
                $catPostfix  = $listingType['Cat_postfix'] ? '.html' : '/';

                if (!$config['mod_rewrite']) {
                    $url .= "{$firstNonModrewriteSign}page={$pages[$listingType['Page_key']]}";
                    $url .= '&category=' . $category['ID'];
                } elseif ($listingType['Links_type'] == 'short') {
                    $url .= $categoryPath . $catPostfix;
                } elseif ($listingType['Links_type'] == 'subdomain') {
                    $url = preg_replace('#http(s)?://(www.)?#', "http$1://{$pages[$listingType['Page_key']]}.", $url);
                    $url .= $categoryPath . $catPostfix;
                } else {
                    $url .= "{$pages[$listingType['Page_key']]}/{$categoryPath}{$catPostfix}";
                }

                break;
        }

        /**
         * @since 4.6.0
         */
        $rlHook->load('phpUrlBottom', $url, $mode, $data, $customLang);

        $this->preventUrlModifying = false;

        return $url;
    }

    /**
     * Get page URL
     *
     * @param string $key - page key
     * @param array  $add_url - additioanl url path
     * @param string $custom_lang - simulate other language url
     * @param string $vars - additional GET variables
     *
     * @return string - page URL
     **/
    public function getPageUrl($key = false, $add_url = false, $custom_lang = false, $vars = false)
    {
        $data = array(
            'key'     => $key,
            'add_url' => $add_url,
            'vars'    => $vars,
        );
        return $this->url('page', $data, $custom_lang);
    }

    /**
     * get Category URL
     *
     * @param string $id - category id
     *
     * @return string - page URL
     **/
    public function getCategoryUrl($id = false, $custom_lang = false)
    {
        return $this->url('category', $id, $custom_lang);
    }

    /**
     * Get url of listing
     *
     * @param int|array $data       - ID|Data of listing
     * @param string    $customLang - Set necessary language of content
     *
     * @return string
     */
    public function getListingUrl($data = null, $customLang = '')
    {
        return $this->url('listing', $data, $customLang);
    }

    /**
     * Create cookie via PHP
     *
     * @since 4.8.2 - The default value of the parameter $samesite changed to an empty string
     * @since 4.8.1 - Removed $raw_method parameter
     *              - Added parameters: $secure, $httponly, $samesite
     *
     * @param string $cookieName
     * @param string $value
     * @param string $expireTime
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     * @param string $samesite
     *
     * @return bool
     */
    public function createCookie(
        $cookieName,
        $value,
        $expireTime = 0,
        $path       = '',
        $domain     = '',
        $secure     = false,
        $httponly   = false,
        $samesite   = ''
    ) {
        global $domain_info;

        if (!$cookieName || !$value) {
            return false;
        }

        $options = [
            'expires'  => $expireTime,
            'path'     => $path ?: $domain_info['path'],
            'domain'   => $domain ?: $domain_info['domain'],
            'secure'   => $secure ?: $this->isHttps(),
            'httponly' => $httponly,
            'samesite' => $samesite ?: ($domain_info['isHttps'] ? 'None' : ''),
        ];

        /**
         * @since 4.8.1 - 3rd parameter replaced to $options
         * @since 4.7.0
         */
        $GLOBALS['rlHook']->load('phpPreCreateCookie', $cookieName, $value, $options);

        /*
         * @todo - Remove when minimum PHP version will be 7.3
         */
        if (PHP_VERSION_ID >= 70300) {
            setcookie($cookieName, $value, $options);
        } else {
            $options['path'] = $options['path'] . "; samesite={$options['samesite']}";

            setcookie(
                $cookieName,
                $value,
                $options['expires'],
                $options['path'],
                $options['domain'],
                $options['secure'],
                $options['httponly']
            );
        }

        return true;
    }

    /**
     * erase cookie php function
     *
     * @param string $cookie_name - cookie name
     *
     * @return bool
     **/
    public function eraseCookie($cookie_name = false)
    {
        if (!$cookie_name) {
            return false;
        }

        return setcookie($cookie_name, '', time() - 3600, $GLOBALS['domain_info']['path'], $GLOBALS['domain_info']['domain']);
    }

    /**
     * Set the right localization for the server
     */
    public function setLocalization()
    {
        $langCode = defined('RL_LANG_CODE') ? RL_LANG_CODE : $GLOBALS['config']['lang'];

        if ($langCode && $locale = $GLOBALS['languages'][$langCode]['Locale']) {
            //putenv('LC_ALL=' . $locale);
            setlocale(LC_ALL, $locale);
            setlocale(LC_NUMERIC, 'C');
        }
    }

    /**
     * Copying of remote file to web-server
     *
     * Function is to copy remote plugin files (or others)
     * When you update or install new plugin
     *
     * @since 4.8.2 Added "Filesystem" method of copying file
     * @since 4.5.1

     * @param string $copy        - Source file
     * @param string $destination - Destination folder
     *
     * @return bool
     */
    public function copyRemoteFile($copy, $destination)
    {
        $result = false;

        if (!$copy || !$destination) {
            return $result;
        }

        // Set the appropriate copying method according to the system settings
        if (true === class_exists('\Flynax\Component\Filesystem') && ini_get('allow_url_fopen')) {
            $method = 'filesystem';
        } elseif (extension_loaded('curl')) {
            $method = 'curl';
        } elseif (ini_get('allow_url_fopen')) {
            $method = 'copy';
        }

        switch ($method) {
            case 'filesystem':
                $result = (new Filesystem())->copyTo($copy, $destination);
                break;
            case 'curl':
                $fp = fopen($destination, 'w');
                $ch = curl_init($copy);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->time_limit);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->time_limit);
                curl_setopt($ch, CURLOPT_USERAGENT, $this->curlUserAgent);
                curl_setopt($ch, CURLOPT_REFERER, RL_URL_HOME);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                $result = curl_exec($ch);
                curl_close($ch);
                fclose($fp);
                break;
            case 'copy':
                // Try simple copy and if failed try file_get_contents
                if (!$result = copy($copy, $destination)) {
                    if ($source = file_get_contents($copy)) {
                        $handle = @fopen($destination, "w");
                        fwrite($handle, $source);
                        fclose($handle);
                    }
                    if (is_readable($destination)) {
                        $result = true;
                    }
                }
                break;
        }

        if (!$result) {
            $GLOBALS['rlDebug']->logger("File copying failed, file was not copied: {$copy}");
            return $result;
        }

        return $result;
    }

    /**
     * Determine if HTTPS is used
     *
     * @since 4.6.0
     *
     * @return bool
     */
    public function isHttps()
    {
        $https = false;
        if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] === '1')) {
            $https = true;
        } else if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
            $https = true;
        } else if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $https = true;
        }

        return $https;
    }

    /**
     * Validate POST data
     * @since 4.6.2
     */
    public function validatePOST()
    {
        Flynax\Utils\Valid::escapeQuotes($_POST);
    }

    /**
     * Start session with all necessary cookie parameters
     *
     * @since 4.8.1
     */
    public function sessionStart()
    {
        $domain_info = [];

        if (!filter_var(RL_URL_HOME, FILTER_VALIDATE_IP)) {
            $domain_info = parse_url(RL_URL_HOME);
            $domain_info['domain'] = '.' . preg_replace("/^(www.)?/", "", $domain_info['host']);

            // localhost cookies problem workaround
            if (!strpos($domain_info['host'], '.')) {
                $domain_info['domain'] = null;
            }

            $domain_info['path'] = '/' . trim(RL_DIR, RL_DS);

            /**
             * @since 4.8.1
             */
            $domain_info['isHttps'] = $this->isHttps();

            /*
             * @todo - Remove when minimum PHP version will be 7.3
             */
            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path'     => $domain_info['path'],
                    'domain'   => $domain_info['domain'],
                    'secure'   => $domain_info['isHttps'],
                    'httponly' => false,
                    'samesite' => $domain_info['isHttps'] ? 'None' : '',
                ]);
            } else {
                session_set_cookie_params(
                    0,
                    $domain_info['path'] . '; samesite=' . ($domain_info['isHttps'] ? 'None' : ''),
                    $domain_info['domain'],
                    $domain_info['isHttps'],
                    false
                );
            }
        }

        session_start();

        $GLOBALS['domain_info'] = $domain_info;
    }

    /**
     * @since 4.9.0
     *
     * @return string
     */
    public function getCurlUserAgent(): string
    {
        return $this->curlUserAgent;
    }

    /**
     * @since 4.9.0
     *
     * @param string $curlUserAgent
     */
    public function setCurlUserAgent(string $curlUserAgent): void
    {
        $this->curlUserAgent = $curlUserAgent;
    }
}
