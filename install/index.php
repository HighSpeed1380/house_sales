<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: INDEX.PHP
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

// Include PSR-4 autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

error_reporting(E_ERROR);
ini_set('display_errors', 1);

// Define system variables
define('RL_DEBUG', true);
define('RL_DB_DEBUG', false);
define('RL_ROOT', dirname(__DIR__) . '/');
define('RL_CLASSES', RL_ROOT . 'includes/classes/');
define('RL_INSTALL', RL_ROOT . 'install/');
define('RL_DS', DIRECTORY_SEPARATOR);

define('REQUIRE_MIN_VERSION_PHP', '7.2');
define('REQUIRE_MIN_VERSION_MYSQL', '5.0');
define('REQUIRE_MIN_VERSION_GD', '2.0');

// Check installation status
function isInstallationCompleted()
{
    $file = RL_ROOT . 'includes/config.inc.php';

    if (!file_exists($file) || !is_readable($file)) {
        return false;
    }
    $contents = file_get_contents($file);

    return (bool) preg_match('/RL_DS/', $contents);
};

if (isInstallationCompleted() && $_GET['step'] != 'finish') {
    header("Location: index.php?step=finish");
    exit;
}

// Try to get remote EULA or display local version
if (isset($_REQUEST['license'])) {
    $opts = array('http' => array('method' => 'GET', 'timeout' => 1));
    $context = stream_context_create($opts);
    $eula = file_get_contents('https://www.flynax.com/installation-eula.html', false, $context);

    if (false !== $eula) {
        print $eula;
    } else {
        print file_get_contents(RL_INSTALL . 'license.html');
    }
    exit;
}

session_start();

require_once RL_CLASSES . 'rlDb.class.php';
require_once RL_CLASSES . 'reefless.class.php';

$rlDb = new rlDb();
$rlDb->dieIfError = false;

$reefless = new reefless();
$reefless->loadClass('Valid');

// Language handler
$default_lang = 'en';
$lang_dir = RL_INSTALL . 'lang' . RL_DS;
$lang_files = $reefless->scanDir($lang_dir);
$lang_count = count($lang_files);
$languages = [];
$get_lang = $_GET['lang'] ? strtolower($_GET['lang']) : false;

if ($lang_count > 1) {
    foreach ($lang_files as $lang_file) {
        $languages[] = str_replace('.json', '', $lang_file);
    }
}

if ($lang_count > 1) {
    if ($get_lang && in_array($get_lang, $languages)) {
        $default_lang = $get_lang;
        setcookie('install_lang', $default_lang, time()+60*60*24*30);
    } elseif ($_COOKIE['install_lang'] && in_array($_COOKIE['install_lang'], $languages)) {
        $default_lang = $_COOKIE['install_lang'];
    } else {
        $accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        $browser_lang = strtolower(substr($accept_lang, 0, 2));

        if (in_array($browser_lang, $languages)) {
            $default_lang = $browser_lang;
        }
    }
}

// Load phrases
$phrase = json_decode(file_get_contents($lang_dir . $default_lang . '.json'), true);

function doWritable($file)
{
    $result = chmod($file, 0755);

    if ($result === true && !is_writable($file)) {
        $result = chmod($file, 0777);
    }
    return $result;
}

function interpolatePhrase($key, $replace)
{
    return str_replace(array_keys($replace), array_values($replace), $GLOBALS['phrase'][$key]);
}

function _phrase()
{
    $args = func_get_args();
    $key = array_shift($args);
    $message = $GLOBALS['phrase'][$key];

    if (func_num_args() > 1) {
        array_unshift($args, $message);
        call_user_func_array('printf', $args);
    } else {
        echo $message;
    }
}

function clearDumpData()
{
    unset(
        $_SESSION['extra_dumps_start_line'],
        $_SESSION['extra_dumps_pointer'],
        $_SESSION['extra_dumps'],
        $_SESSION['extra_dumps_progress_line'],
        $_SESSION['extra_dumps_total_lines'],
        $_SESSION['extra_dumps_current']
    );
}

function countFileLines($file)
{
    $count = 0;
    $fp = fopen($file, 'r');

    while (!feof($fp)) {
        fgets($fp);
        $count++;
    }
    fclose($fp);

    return $count;
}

function runDump($file, &$errors)
{
    global $rlValid, $rlDb, $l_timezone, $phrase;

    if (!$file || !is_readable($file)) {
        return false;
    }

    $queryql_dump = fopen($file, 'r');

    while ($query = fgets($queryql_dump, 10240)) {
        $query = trim($query);
        if ($query[0] == '#') {
            continue;
        }

        if ($query[0] == '-') {
            continue;
        }

        if ($query[strlen($query) - 1] == ';') {
            $query_sql .= $query;
        } else {
            $query_sql .= $query;
            continue;
        }

        if (!empty($query_sql)) {
            $pass = '';
            $timezone = 'Europe/Dublin';

            // prepare secure password for administrator account
            if (false !== strpos($query_sql, '{admin_password}')) {
                if (!defined('RL_LIBS')) {
                    define('RL_LIBS', dirname(__DIR__) . '/libs/');
                }

                require_once dirname(__DIR__) . '/includes/classes/rlSecurity.class.php';

                $pass = FLSecurity::cryptPassword($_SESSION['admin_info']['admin_password']);
            }

            // Detect timezone of web server
            if (false !== strpos($query_sql, '{timezone}')) {
                require_once dirname(__DIR__) . '/libs/system.lib.php';
                $phpTimezone = date_default_timezone_get();

                if ($phpTimezone && $l_timezone) {
                    foreach($l_timezone as $shortName => $zone) {
                        $locationsInZone = $zone[1];

                        if ($locationsInZone && false !== strpos($locationsInZone, $phpTimezone)) {
                            $timezone = $shortName;
                            break;
                        }
                    }
                }
            }

            $find = array(
                '{admin_user}',
                '{admin_password}',
                '{admin_email}',
                '{db_prefix}',
                '{advanced_site_name}',
                '{advanced_site_owner}',
                '{advanced_site_email}',
                '{timezone}',
                '{listing_types_set}',
            );
            $replace = array(
                $_SESSION['admin_info']['admin_username'],
                $pass,
                $_SESSION['admin_info']['admin_email'],
                $_SESSION['database_info']['prefix'],
                $_SESSION['advanced_info']['site_name'],
                $_SESSION['advanced_info']['site_owner'],
                $_SESSION['advanced_info']['site_email'],
                $timezone,
                $GLOBALS['listing_types_set']
            );

            $rlValid->sql($replace);

            $query_sql = str_replace($find, $replace, $query_sql);
        }
        $rlDb->query($query_sql);

        if ($rlDb->lastErrno()) {
            $errors[] = sprintf($phrase['step_tables_error1'], $rlDb->lastError(), $query_sql);
        }

        unset($query_sql);
    }

    fclose($queryql_dump);

    return true;
}

function importDumpRunQuery($query)
{
    global $rlDb;

    $query = trim($query);

    if (!$query) {
        return true;
    }
    $query = str_replace(array('{db_prefix}', PHP_EOL), array($_SESSION['database_info']['prefix'], ''), $query);

    $rlDb->query($query);

    if ($rlDb->lastErrno()) {
        return sprintf($phrase['import_dump_query_error1'], $rlDb->lastError(), $query);
    }
    return true;
}

function importDump($file)
{
    $line_per_session = 5000;
    $data_chunk_lenght = 16384;
    $start_line = $_SESSION['extra_dumps_start_line'];
    $session_line = 0;

    $query = '';
    $current_line = $start_line;
    $ret = array();

    fseek($file, $_SESSION['extra_dumps_pointer']);

    while (!feof($file) && $current_line <= $start_line + $line_per_session) {
        $line = fgets($file, $data_chunk_lenght);
        $session_line++;

        // skip commented lines
        if ((bool) preg_match('/^(\-\-|\#|\/\*)/', $line)) {
            continue;
        }
        $query .= $line;

        if ((bool) preg_match('/\;(\r\n?|\n)$/', $line) || feof($file)) {
            $query_result = importDumpRunQuery($query);

            if ($query_result !== true) {
                $ret = array('error' => $query_result);
            }
            $query = '';
        }

        if (feof($file)) {
            array_shift($_SESSION['extra_dumps']);
            fclose($file);
            $current_line = 0;

            if (count($_SESSION['extra_dumps'])) {
                $ret['action'] = 'next_file';
            } else {
                $ret['action'] = 'end';
            }

            break;
        }

        // last line
        if ($current_line == $start_line + $line_per_session && !(bool) preg_match('/\;(\r\n?|\n)$/', $line)) {
            $line_per_session++; // go one more line forward
        }

        $current_line++;
        $ret['action'] = 'next_stack';
    }

    $_SESSION['extra_dumps_progress_line'] += $session_line;
    $_SESSION['extra_dumps_start_line']    = $current_line;
    $_SESSION['extra_dumps_pointer']       = $ret['action'] === 'next_stack' ? ftell($file) : 0;

    $ret['lines'] = $session_line;
    $ret['line_num'] = $_SESSION['extra_dumps_progress_line'];
    $ret['progress'] = round(ceil(($ret['line_num'] * 100) / $_SESSION['extra_dumps_total_lines']), 1);

    if ($ret['action'] == 'end') {
        clearDumpData();
    }

    return $ret;
}

if ($_REQUEST['action'] == 'importDump') {
    $out = array();

    if (isset($_SESSION['extra_dumps']) && count($_SESSION['extra_dumps'])) {
        $dump_file = RL_INSTALL . 'mysql/' . $_SESSION['extra_dumps'][0];
        $file = fopen($dump_file, 'r');

        if ($file) {
            $rlDb = new rlDb;
            $rlDb->dieIfError = false;
            define('RL_DBPREFIX', $_SESSION['database_info']['prefix']);

            $rlDb->connect(
                $_SESSION['database_info']['hostname'],
                $_SESSION['database_info']['port'],
                $_SESSION['database_info']['username'],
                $_SESSION['database_info']['password'],
                $_SESSION['database_info']['name']
            );

            $out = importDump($file);

            $rlDb->connectionClose(true);
        } else {
            $out = array('error' => sprintf($phrase['cant_find_sql_file'], $dump_file));
        }
    }

    echo json_encode($out);
    exit;
}

$version   = '4.9.0';
$main_menu = array(
    'introduction'      => $phrase['main_menu_introduction'],
    'license_agreement' => $phrase['main_menu_license_agreement'],
    'requirements'      => $phrase['main_menu_requirements'],
    'database'          => $phrase['main_menu_database'],
    'advanced'          => $phrase['main_menu_advanced'],
    'tables'            => $phrase['main_menu_tables'],
    'config_file'       => $phrase['main_menu_config_file'],
    'finish'            => $phrase['main_menu_finish'],
);

preg_match('/([0-9]{1}\.[0-9]{1,2}\.[0-9]{1,2})/', phpversion(), $matches);
$php_version = $matches[1];

if (array_search('mysqli', get_loaded_extensions())) {
    preg_match('/([0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2})/', $rlDb->getClientInfo(), $matches);
    $mysql_version = $matches[1];
}

$php = version_compare($php_version, REQUIRE_MIN_VERSION_PHP, '>=');
$mysql = version_compare($mysql_version, REQUIRE_MIN_VERSION_MYSQL, '>=');
$php_register_globals = ini_get('register_globals') ? 'average' : 'good';
$php_magic_quotes = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ? 'average' : 'good';

if (function_exists('apache_get_modules')) {
    $php_mod_rewrite = array_search('mod_rewrite', apache_get_modules()) !== false ? 'good' : 'average';
} else {
    $php_mod_rewrite = 'average';
}
$curl = extension_loaded('curl') ? 'good' : 'average';

if (function_exists('gd_info')) {
    $gd = gd_info();

    if ($gd && $gd['GD Version']) {
        preg_match('/[0-9]\.+[0-9]\.[0-9]+/', $gd['GD Version'], $matches);

        $gd_version = $matches[0];
        $gd_library_version = $gd_version;
        $gd_library = version_compare($gd_version, REQUIRE_MIN_VERSION_GD, '>=') ? 'good' : 'bad';
    } else {
        $gd_library = 'average';
        $gd_library_version = $phrase['not_installed'];
    }
} else {
    $gd_library = 'average';
    $gd_library_version = $phrase['undefined'];
}

$requires_access = true;
$databa_access = true;
$requires_button = '';

$session_support = $_SESSION['session_test'] == 'flynax' ? 1 : 0;

if (!$php || !$mysql || !$session_support) {
    $requires_access = false;
    $requires_button = 'disabled';
}

$requires = array(
    'php_version'      => array(
        'name'        => $phrase['requires_name_php_version'],
        'description' => sprintf($phrase['requires_desc_php_version'], REQUIRE_MIN_VERSION_PHP),
        'value'       => $php_version,
        'result'      => $php ? 'good' : 'bad',
    ),
    'mysql'            => array(
        'name'        => $phrase['requires_name_mysql'],
        'description' => sprintf($phrase['requires_desc_mysql'], REQUIRE_MIN_VERSION_MYSQL),
        'value'       => $mysql_version ?: $phrase['not_installed'],
        'result'      => $mysql ? 'good' : 'bad',
    ),
    'gd_library'       => array(
        'name'        => $phrase['requires_name_gd_library'],
        'description' => sprintf($phrase['requires_desc_gd_library'], REQUIRE_MIN_VERSION_GD),
        'value'       => $gd_library_version,
        'result'      => $gd_library,
    ),
    'register_globals' => array(
        'name'        => $phrase['requires_name_register_globals'],
        'description' => $phrase['requires_desc_register_globals'],
        'value'       => ini_get('register_globals') ? $phrase['enabled'] : $phrase['disabled'],
        'result'      => $php_register_globals,
    ),
    'magic_quots'      => array(
        'name'        => $phrase['requires_name_magic_quotes'],
        'description' => $phrase['requires_desc_magic_quotes'],
        'value'       => function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()
            ? $phrase['enabled']
            : $phrase['disabled'],
        'result'      => $php_magic_quotes,
    ),
    'session'          => array(
        'name'        => $phrase['requires_name_session'],
        'description' => $phrase['requires_desc_session'],
        'value'       => $session_support ? $phrase['enabled'] : $phrase['disabled'],
        'result'      => $session_support ? 'good' : 'bad',
    ),
    'mod_rewrite'      => array(
        'name'        => $phrase['requires_name_mod_rewrite'],
        'description' => $phrase['requires_desc_mod_rewrite'],
        'result'      => $php_mod_rewrite,
    ),
    'curl'             => array(
        'name'        => $phrase['requires_name_curl'],
        'description' => $phrase['requires_desc_curl'],
        'value'       => extension_loaded('curl') ? $phrase['installed'] : $phrase['not_installed'],
        'result'      => $curl,
    ),
);

if (function_exists('apache_get_modules')) {
    $mod_rewrite = array_search('mod_rewrite', apache_get_modules()) !== false;
    $requires['mod_rewrite']['value'] = $mod_rewrite ? $phrase['installed'] : $phrase['not_installed'];
} else {
    $requires['mod_rewrite']['value'] = $phrase['undefined'];
}

$permissions = array(
    array(
        'path'        => '/tmp/aCompile/',
        'description' => $phrase['permissions_aCompile'],
    ),
    array(
        'path'        => '/tmp/compile/',
        'description' => $phrase['permissions_compile'],
    ),
    array(
        'path'        => '/tmp/cache/',
        'description' => $phrase['permissions_cache'],
    ),
    array(
        'path'        => '/tmp/errorLog/',
        'description' => $phrase['permissions_errorLog'],
    ),
    array(
        'path'        => '/tmp/upload/',
        'description' => $phrase['permissions_upload'],
    ),
    array(
        'path'        => '/files/',
        'description' => $phrase['permissions_files'],
    ),
    array(
        'path'        => '/plugins/',
        'description' => $phrase['permissions_plugins'],
    ),
    array(
        'path'        => '/backup/plugins/',
        'description' => $phrase['permissions_backup_plugins'],
    ),
    array(
        'path'        => '/includes/config.inc.php',
        'description' => $phrase['permissions_config'],
    ),
    array(
        'path'         => '/admin/',
        'description'  => $phrase['permissions_admin'],
        'no_necessary' => true,
    ),
);

$database = array(
    array(
        'name'        => $phrase['database_name_hostname'],
        'var'         => 'hostname',
        'type'        => 'text',
        'description' => $phrase['database_desc_hostname'],
        'value'       => 'localhost',
    ),
    array(
        'name'        => $phrase['database_name_port'],
        'var'         => 'port',
        'type'        => 'text',
        'description' => $phrase['database_desc_port'],
        'value'       => '3306',
    ),
    array(
        'name'        => $phrase['database_name_username'],
        'var'         => 'username',
        'type'        => 'text',
        'description' => $phrase['database_desc_username'],
        'value'       => '',
    ),
    array(
        'name'        => $phrase['database_desc_password'],
        'var'         => 'password',
        'type'        => 'password',
        'description' => $phrase['database_desc_password'],
        'value'       => '',
    ),
    array(
        'name'        => $phrase['database_name_name'],
        'var'         => 'name',
        'type'        => 'text',
        'description' => $phrase['database_desc_name'],
        'value'       => '',
    ),
    array(
        'name'        => $phrase['database_name_prefix'],
        'var'         => 'prefix',
        'type'        => 'text',
        'description' => $phrase['database_desc_prefix'],
        'value'       => 'fl_',
    ),
);

$advanced = array(
    array(
        'name'        => $phrase['advanced_name_site_name'],
        'var'         => 'site_name',
        'type'        => 'text',
        'description' => $phrase['advanced_desc_site_name'],
        'value'       => $phrase['advanced_value_site_name'],
    ),
    array(
        'name'        => $phrase['advanced_name_site_owner'],
        'var'         => 'site_owner',
        'type'        => 'text',
        'description' => $phrase['advanced_desc_site_owner'],
        'value'       => $_POST['site_owner'],
    ),
    array(
        'name'        => $phrase['advanced_name_site_email'],
        'var'         => 'site_email',
        'type'        => 'text',
        'description' => $phrase['advanced_desc_site_email'],
        'value'       => $_POST['site_email'],
    ),
    array(
        'name'        => $phrase['advanced_name_www_prefix'],
        'var'         => 'www_prefix',
        'type'        => 'radio',
        'description' => $phrase['advanced_desc_www_prefix'],
        'value'       => $_POST['www_prefix'],
    ),
    array(
        'name'        => $phrase['advanced_name_thumbnails_x2'],
        'var'         => 'thumbnails_x2',
        'type'        => 'radio',
        'description' => $phrase['advanced_desc_thumbnails_x2'],
        'value'       => $_POST['thumbnails_x2'],
    ),
    array(
        'name'        => $phrase['advanced_name_https'],
        'var'         => 'https',
        'type'        => 'radio',
        'description' => $phrase['advanced_desc_https'],
        'value'       => $_POST['https'],
    ),
);

$admin = array(
    array(
        'name'        => $phrase['admin_name_username'],
        'var'         => 'admin_username',
        'type'        => 'text',
        'description' => $phrase['admin_desc_username'],
        'value'       => 'admin',
    ),
    array(
        'name'        => $phrase['admin_name_password'],
        'var'         => 'admin_password',
        'type'        => 'password',
        'description' => $phrase['admin_desc_password'],
    ),
    array(
        'name'        => $phrase['admin_name_password_repeat'],
        'var'         => 'password_repeat',
        'type'        => 'password',
        'description' => $phrase['admin_desc_password_repeat'],
    ),
    array(
        'name'        => $phrase['admin_name_email'],
        'var'         => 'admin_email',
        'type'        => 'text',
        'description' => $phrase['admin_name_email'],
    ),
    array(
        'name'        => $phrase['admin_name_folder'],
        'var'         => 'admin_dir',
        'type'        => 'text',
        'description' => $phrase['admin_desc_folder'],
        'value'       => 'admin',
    ),
);

/* welcome */
switch ($_GET['step']) {
    case 'license_agreement':
        $_SESSION['session_test'] = 'flynax';

        $content = <<< VS
    <p>{$phrase['step_license_agreement_text1']}</p>
    <div class="loading">{$phrase['loading']}</div>
    <iframe src="index.php?license">
    </iframe>
    <p>{$phrase['step_license_agreement_text2']}</p>
    <div style="text-align: right;padding: 10px 0 0 0;">
        <a href="index.php" class="cancel">{$phrase['cancel']}</a>
        <input onclick="location.href='index.php?step=requirements'" type="button" value="{$phrase['accept']}" />
    </div>
VS;
        break;

    case 'requirements':
        $_SESSION['requires_access_info'] = $requires_access;
        $content = <<< VS
        <p>{$phrase['step_requirements_text1']}</p>

        <table class="list" style="margin: 10px 0;">
        <tr>
            <td colspan="2" class="table_caption"><div>{$phrase['step_requirements_section_requirements']}</div></td>
        </tr>
VS;
        foreach ($requires as $key => $value) {
            $content .= '
            <tr>
                <td class="td_spliter"><div><b>' . $requires[$key]['name'] . '</b><br /><span>' . $requires[$key]['description'] . '</span></div></td>
                <td class="td_value ' . $requires[$key]['result'] . '"><div>' . $requires[$key]['value'] . '</div></td>
            </tr>';
        }
        $content .= "</table>";

        $content .= <<< VS
        <p>{$phrase['step_requirements_text2']}</p>

        <table class="list" style="margin: 10px 0;">
        <tr>
            <td colspan="2" class="table_caption"><div>{$phrase['step_requirements_section_permissions']}</div></td>
        </tr>
VS;
        foreach ($permissions as $permission) {
            $permission_file = RL_ROOT . $permission['path'];
            $permission_file_writable = is_writable($permission_file);

            $permission['value'] = $permission_file_writable ? $phrase['writable'] : $phrase['unwritable'];
            $permission['status'] = $permission_file_writable ? 'good' : 'bad';

            $_SESSION['edit_admin'] = false;

            /* set writable permissions */
            if (file_exists($permission_file) && !$permission_file_writable) {
                doWritable($permission_file);
            }

            if (!is_writable($permission_file)) {
                if (!$permission['no_necessary']) {
                    $requires_access = false;
                    $requires_button = 'disabled';
                } else {
                    $permission['status'] = 'average';
                }
            } else {
                if ($permission['path'] == '/admin/') {
                    $_SESSION['edit_admin'] = true;
                }
            }

            $content .= '
            <tr>
                <td class="td_spliter"><div><b>' . $permission['path'] . '</b><br /><span>' . $permission['description'] . '</span></div></td>
                <td class="td_value ' . $permission['status'] . '"><div>' . $permission['value'] . '</div></td>
            </tr>';
        }
        $content .= '
        <tr><td colspan="2" class="table_footer"></td></tr>
        </table>';
        $content .= <<< VS
        <div style="text-align: right;padding: 10px 0 0 0;">
            <a href="index.php?step=license_agreement" class="cancel">{$phrase['back']}</a>
            <input style="margin-right: 5px;" onclick="location.href='index.php?step=requirements'" class="button" type="button" value="{$phrase['refresh']}" />
            <input {$requires_button} onclick="location.href='index.php?step=database'" class="button {$requires_button}" type="button" value="{$phrase['next']}" />
        </div>
VS;
        break;

    case 'database':
        $error = false;
        if ($_SESSION['requires_access_info']) {
            if ($_POST['action']) {
                foreach ($database as $key => $value) {
                    $database[$key]['error'] = '';
                    if (!empty($_POST[$database[$key]['var']])) {
                        $database[$key]['value'] = $_POST[$database[$key]['var']];
                    } else {
                        if ($database[$key]['var'] != 'password') {
                            $database[$key]['error'] = 'error';
                            $error = true;
                        }
                    }
                }

                if (!$error) {
                    define('RL_DBPREFIX', $_SESSION['database_info']['prefix']);
                    $rlDb->connect(
                        $_POST['hostname'],
                        $_POST['port'],
                        $_POST['username'],
                        $_POST['password'],
                        $_POST['name']
                    );

                    if ($rlDb->lastErrno()) {
                        $errors[] = $rlDb->lastError();
                        $databa_access = false;
                    }
                    // save the DB details to session for future purpose
                    else {
                        $_SESSION['database_info'] = array(
                            'hostname' => $_POST['hostname'],
                            'port'     => $_POST['port'],
                            'username' => $_POST['username'],
                            'password' => $_POST['password'],
                            'name'     => $_POST['name'],
                            'prefix'   => $_POST['prefix'],
                        );
                        header("Location: index.php?step=advanced");
                        exit;
                    }
                }
            } else {
                foreach ($database as $key => $value) {
                    if (!empty($_SESSION['database_info'][$database[$key]['var']])) {
                        $database[$key]['value'] = $_SESSION['database_info'][$database[$key]['var']];
                    }
                }
            }
        } else {
            header("Location: index.php?step=requirements");
        }

        $content = <<< VS
    <p>{$phrase['step_database_text1']}</p>
    <form action="index.php?step=database" method="post">
    <input type="hidden" name="action" value="true" />
    <table class="list" style="margin: 10px 0;">
VS;
        foreach ($database as $key => $value) {
            $content .= '
            <tr>
                <td class="td_spliter"><div><b>' . $database[$key]['name'] . '</b><br /><span>' . $database[$key]['description'] . '</span></div></td>
                <td style="width: 180px" class="td_value"><input name="' . $database[$key]['var'] . '" class="' . $database[$key]['error'] . '" type="' . $database[$key]['type'] . '" value="' . $database[$key]['value'] . '" /></td>
            </tr>';
        }
        $content .= <<< VS
    </table>
    <div style="text-align: right;padding: 10px 0 0 0;">
        <a href="index.php?step=requirements" class="cancel">{$phrase['back']}</a>
        <input {$requires_button} onclick="location.href='index.php?step=database'" class="button" type="submit" value="{$phrase['next']}" />
    </div>
    </form>
VS;
        break;

    case 'advanced':
        $error = false;
        if ($_SESSION['database_info']) {
            if ($_POST['action']) {
                foreach ($admin as $key => $value) {
                    if (empty($_POST[$admin[$key]['var']])) {
                        $error = true;
                        $admin[$key]['error'] = 'error';
                        $errors[] = sprintf($phrase['step_advanced_error1'], $admin[$key]['name']);
                    } else {
                        if ($admin[$key]['var'] == 'admin_dir') {
                            $admin[$key]['value'] = trim($rlValid->str2key($_POST[$admin[$key]['var']]));
                        } else {
                            $admin[$key]['value'] = $_POST[$admin[$key]['var']];
                        }
                    }
                }

                if (!$error) {
                    /* check password */
                    if ($_POST['admin_password'] != $_POST['password_repeat']) {
                        $errors[] = $phrase['step_advanced_error2'];
                    }

                    if (!$rlValid->isEmail($_POST['admin_email'])) {
                        $errors[] = $phrase['step_advanced_error3'];
                    }

                    if ((bool) preg_match('/[\W]/', $_POST['admin_dir'])) {
                        $errors[] = $phrase['step_advanced_error4'];
                    }
                    // checks whether a directory exists
                    elseif ($_POST['admin_dir'] !== 'admin' && file_exists(RL_ROOT . $_POST['admin_dir'])) {
                        $errors[] = $phrase['step_advanced_error5'];
                    }

                    if (!$error && empty($errors)) {
                        foreach ($advanced as $key => $value) {
                            $_SESSION['advanced_info'][$advanced[$key]['var']] = $_POST[$advanced[$key]['var']];
                        }

                        foreach ($admin as $key => $value) {
                            $_SESSION['admin_info'][$admin[$key]['var']] = $_POST[$admin[$key]['var']];
                        }

                        header("Location: index.php?step=tables");
                        exit;
                    }
                }
            } else {
                foreach ($admin as $key => $value) {
                    if (!empty($_SESSION['database_info'][$database[$key]['var']])) {
                        $database[$key]['value'] = $_SESSION['database_info'][$database[$key]['var']];
                    }
                }
            }
        } else {
            header("Location: index.php?step=database");
        }

        $content = <<< VS
    <p>{$phrase['step_database_text1']}</p>
    <form action="index.php?step=advanced" method="post">
    <input type="hidden" name="action" value="true" />
    <table cellpadding="0" cellspacing="0" style="margin: 10px 0;">
    <tr>
        <td colspan="2" class="table_caption"><div>{$phrase['step_advanced_section_main']}</div></td>
    </tr>
VS;
        foreach ($advanced as $key => $value) {
            $content .= '
            <tr>
                <td class="td_spliter"><div><b>' . $advanced[$key]['name'] . '</b><br /><span>' . $advanced[$key]['description'] . '</span></div></td>
                <td class="td_value">';

            switch ($value['var']) {
                case 'www_prefix':
                    $www_checked = $_POST['www_prefix'] != '0' ? 'checked="checked"' : '';
                    $non_www_checked = $_POST['www_prefix'] == '0' ? 'checked="checked"' : '';
                    $content .= '<label><input ' . $www_checked;
                    $content .= 'name="www_prefix" type="radio" value="1" /> ' . $phrase['yes'] . '</label>';
                    $content .= '<label style="padding: 0 0 0 10px;"><input ' . $non_www_checked;
                    $content .= ' name="www_prefix" type="radio" value="0" /> ' . $phrase['no'] . '</label>';
                    break;

                case 'thumbnails_x2':
                    $thumbnails_x2_checked = $_POST['thumbnails_x2'] == '1' ? 'checked="checked"' : '';
                    $non_thumbnails_x2_checked = $_POST['thumbnails_x2'] != '1' ? 'checked="checked"' : '';
                    $content .= '<label><input ' . $thumbnails_x2_checked;
                    $content .= 'name="thumbnails_x2" type="radio" value="1" /> ' . $phrase['yes'] . '</label>';
                    $content .= '<label style="padding: 0 0 0 10px;"><input ' . $non_thumbnails_x2_checked;
                    $content .= ' name="thumbnails_x2" type="radio" value="0" /> ' . $phrase['no'] . '</label>';
                    break;

                case 'https':
                    $https_checked = $_POST['https'] == '1' ? 'checked="checked"' : '';
                    $https_not_checked = $_POST['https'] != '1' ? 'checked="checked"' : '';
                    $content .= '<label><input ' . $https_checked;
                    $content .= 'name="https" type="radio" value="1" /> ' . $phrase['yes'] . '</label>';
                    $content .= '<label style="padding: 0 0 0 10px;"><input ' . $https_not_checked;
                    $content .= ' name="https" type="radio" value="0" /> ' . $phrase['no'] . '</label>';
                    break;

                default:
                    $content .= '<input style="width: 200px;" name="' . $advanced[$key]['var'];
                    $content .= '" class="' . $advanced[$key]['error'] . '" type="' . $advanced[$key]['type'];
                    $content .= '" value="' . $advanced[$key]['value'] . '" />';
                    break;
            }

            $content .= '
                </td>
            </tr>';
        }

        $content .= <<< VS
    </table>
    <table cellpadding="0" cellspacing="0" style="margin: 10px 0;">
    <tr>
        <td colspan="2" class="table_caption"><div>{$phrase['step_advanced_section_admin']}</div></td>
    </tr>
VS;
        foreach ($admin as $key => $value) {
            $admin_disabled = ($admin[$key]['var'] == 'admin_dir' && !$_SESSION['edit_admin']) ? 'readonly' : '';

            $content .= '
            <tr>
                <td class="td_spliter"><div><b>' . $admin[$key]['name'] . '</b><br /><span>' . $admin[$key]['description'] . '</span></div></td>
                <td class="td_value"><input ' . $admin_disabled . ' style="width: 200px;" name="' . $admin[$key]['var'] . '" class="' . $admin[$key]['error'] . '" type="' . $admin[$key]['type'] . '" value="' . $admin[$key]['value'] . '" /></td>
            </tr>';
        }

        $content .= <<< VS
    </table>
    <div style="text-align: right;">
        <a href="index.php?step=database" class="cancel">{$phrase['back']}</a>
        <input {$requires_button} onclick="location.href='index.php?step=database'" class="button" type="submit" value="{$phrase['next']}" />
    </div>
    </form>
VS;
        break;

    case 'tables':
        if ($_SESSION['requires_access_info']
            && $_SESSION['database_info']
            && $_SESSION['advanced_info']
            && $_SESSION['admin_info']
        ) {
            define('RL_DBPREFIX', $_SESSION['database_info']['prefix']);

            if (!$_SESSION['main_dump_loaded']) {
                // extra dumps import
                if (!$_SESSION['extra_dumps']) {
                    $_SESSION['extra_dumps'] = array();

                    foreach (scandir(RL_INSTALL . 'mysql') as $dump) {
                        if (!in_array($dump, array('.', '..', 'dump.sql')) && (bool) preg_match('/^fl\_.*\.sql$/', $dump)) {
                            $_SESSION['extra_dumps'][] = $dump;
                            $_SESSION['extra_dumps_total_lines'] += countFileLines(RL_INSTALL . 'mysql/' . $dump);
                        }
                    }

                    $_SESSION['extra_dumps_current'] = 1;
                }

                $rlDb->connect(
                    $_SESSION['database_info']['hostname'],
                    $_SESSION['database_info']['port'],
                    $_SESSION['database_info']['username'],
                    $_SESSION['database_info']['password'],
                    $_SESSION['database_info']['name']);

                if ($rlDb->lastErrno()) {
                    $errors[] = $rlDb->lastError();
                    $databa_access = false;
                } else {
                    if (runDump(RL_INSTALL . 'mysql/dump.sql', $errors)) {
                        $GLOBALS['listing_types_set'] = implode(',', $rlDb->getAll("SELECT `Key` FROM `{$_SESSION['database_info']['prefix']}listing_types`", [false, 'Key']));

                        runDump(RL_INSTALL . 'mysql/post_package.sql', $errors);

                        $rlDb->connectionClose();

                        if (count($_SESSION['extra_dumps']) && empty($errors)) {
                            $_SESSION['main_dump_loaded'] = true;
                        } else {
                            if (empty($errors)) {
                                header("Location: index.php?step=config_file");
                                exit;
                            }
                        }
                    } else {
                        $errors[] = $phrase['step_tables_error2'];
                    }
                }
            }
        } else {
            header("Location: index.php?system_error");
        }

        if ($errors) {
            $content .= sprintf('<p>%s</p>', $phrase['step_tables_error3']);
        }
        break;

    case 'config_file':
        if ($_SESSION['requires_access_info'] && $_SESSION['database_info'] && $_SESSION['advanced_info'] && $_SESSION['admin_info']) {
            $handle = fopen(RL_INSTALL . 'config.inc.php.tmp', 'r');
            if ($handle) {
                while (!feof($handle)) {
                    $config_content .= fgets($handle, 4096);
                }
                fclose($handle);

                if (!empty($config_content)) {
                    $host = trim($_SERVER['HTTP_HOST'], '/');
                    // www. mode
                    $host = (bool) preg_match('/^www\./', $host) && $_SESSION['advanced_info']['www_prefix'] ? $host : 'www.' . $host;
                    // non www mode
                    $host = !$_SESSION['advanced_info']['www_prefix'] ? preg_replace('/^(www\.)/', '', $host) : $host;
                    $self = $_SERVER['PHP_SELF'];

                    preg_match('/(.*)install/', $self, $matches);
                    $sub_directory = trim($matches[1], '/');
                    $is_sub_directory = ($sub_directory !== '');

                    // try to modify base rule in .htaccess file
                    if ($is_sub_directory) {
                        file_put_contents(
                            RL_ROOT . '.htaccess',
                            str_replace(
                                'RewriteBase /',
                                "RewriteBase /{$sub_directory}/",
                                file_get_contents(RL_ROOT . '.htaccess')
                            )
                        );
                    }

                    /**
                     * Detect installation on the subdomain, like domain.com.au
                     * Add necessary rule in .htaccess
                     * @since 4.8.0
                     */
                    if (substr_count($host, '.') === 2) {
                        $subdomain = explode('.', $host)[0];

                        file_put_contents(
                            RL_ROOT . '.htaccess',
                            str_replace(
                                'RewriteCond %{HTTP_HOST} !^www\. [NC]',
                                'RewriteCond %{HTTP_HOST} !^www\. [NC]'
                                    . PHP_EOL
                                    . "RewriteCond %{HTTP_HOST} !^{$subdomain}\. [NC]",
                                file_get_contents(RL_ROOT . '.htaccess')
                            )
                        );
                    }

                    $url = $_SESSION['advanced_info']['https'] == '1' ? 'https://' : 'http://';
                    $url .= $host . '/';
                    $url .= $is_sub_directory ? $sub_directory . '/' : '';

                    $root = $is_sub_directory ? str_replace($sub_directory, '', RL_ROOT) : RL_ROOT;
                    $root = rtrim($root, '/');

                    // escape backslash "\" for windows servers
                    if (DIRECTORY_SEPARATOR == '\\') {
                        $root = str_replace('\\', "\\\\", $root);
                    }

                    /* rename test */
                    if (rename(RL_ROOT . 'tmp/cache/', RL_ROOT . 'tmp/cache_tmp/')) {
                        $cache_postfix = '_' . mt_rand();
                        rename(RL_ROOT . 'tmp/cache_tmp/', RL_ROOT . 'tmp/cache/');
                    }

                    $find = array(
                        '{db_port}',
                        '{db_host}',
                        '{db_user}',
                        '{db_pass}',
                        '{db_name}',
                        '{db_prefix}',
                        '{rl_admin}',
                        '{rl_root}',
                        '{rl_dir}',
                        '{rl_url}',
                        '{rl_cache_postfix}',
                    );
                    $replace = array(
                        $_SESSION['database_info']['port'],
                        $_SESSION['database_info']['hostname'],
                        $_SESSION['database_info']['username'],
                        $_SESSION['database_info']['password'],
                        $_SESSION['database_info']['name'],
                        $_SESSION['database_info']['prefix'],
                        $_SESSION['admin_info']['admin_dir'],
                        $root,
                        $is_sub_directory ? "'{$sub_directory}' . RL_DS" : "''",
                        $url,
                        $cache_postfix,
                    );

                    $config_content = str_replace($find, $replace, $config_content);

                    /* create original config file */
                    $orig_file = fopen(RL_ROOT . 'includes/config.inc.php', 'w+');
                    if (fwrite($orig_file, $config_content) === false) {
                        $errors[] = $phrase['step_config_file_error1'];
                    } else {
                        chmod(RL_ROOT . 'includes/config.inc.php', 0644);

                        /* admin directory handler */
                        if ($_SESSION['edit_admin']) {
                            $admin_dir = RL_ROOT . trim($_SESSION['admin_info']['admin_dir'], '/');
                            rename(RL_ROOT . 'admin/', $admin_dir);
                            doWritable($admin_dir);
                        }
                        fclose($orig_file);

                        /* cache directory handler */
                        $new_cache_dir = RL_ROOT . 'tmp/cache' . $cache_postfix;
                        rename(RL_ROOT . 'tmp/cache/', $new_cache_dir);
                        doWritable($new_cache_dir);

                        /* create cache files */
                        require_once RL_ROOT . 'includes/config.inc.php';

                        /* load classes */
                        $rlDb->connect(RL_DBHOST, RL_DBPORT, RL_DBUSER, RL_DBPASS, RL_DBNAME);
                        $reefless->loadClass('Debug');
                        $reefless->loadClass('Valid');
                        $reefless->loadClass('Lang');
                        $reefless->loadClass('Actions');
                        $reefless->loadClass('Config');
                        $reefless->loadClass('Hook');

                        $config = $rlConfig->allConfig();
                        $rlLang->extDefineLanguage();

                        /* site languages array generation */
                        $lang = $rlLang->getLangBySide('admin', RL_LANG_CODE, 'all');

                        $reefless->loadClass('Cache');
                        $reefless->loadClass('ListingTypes');

                        // utf8 library functions
                        if (!function_exists('loadUTF8functions')) {
                            function loadUTF8functions()
                        {
                                $names = func_get_args();

                                if (empty($names)) {
                                    return;
                                }

                                foreach ($names as $name) {
                                    if (file_exists(RL_LIBS . 'utf8/utils/' . $name . '.php')) {
                                        require_once RL_LIBS . 'utf8/utils/' . $name . '.php';
                                    }
                                }
                            }
                        }

                        $rlCache->update();

                        /* touch files */
                        $reefless->flTouch();

                        /* drop additional columns for 2x thumbnails && disable config */
                        if ($_SESSION['advanced_info']['thumbnails_x2'] == '0') {
                            $rlDb->dropColumnFromTable('Main_photo_x2', 'listings');
                            $rlDb->dropColumnFromTable('Thumbnail_x2', 'listing_photos');
                            $rlDb->dropColumnFromTable('Photo_x2', 'accounts');

                            $rlConfig->setConfig('thumbnails_x2', '0');
                        }

                        /* clear data */
                        session_destroy();

                        header("Location: index.php?step=finish");
                        exit;
                    }
                }
            }
        } else {
            header("Location: index.php?system_error");
        }
        $content .= sprintf('<div>%s</div>', $phrase['processing']);
        break;

    case 'finish':
        require_once RL_ROOT . 'includes/config.inc.php';

        $admin_interface = RL_URL_HOME . ADMIN . '/index.php';
        $frontend_interface = RL_URL_HOME;

        $content .= interpolatePhrase('step_finish_html_text1', array(
            '{version}'            => $version,
            '{admin_interface}'    => $admin_interface,
            '{frontend_interface}' => $frontend_interface,
        ));

        // show message about detected installation in sub-directory
        if (RL_DIR) {
            $content .= "<hr>";
            $sub_directory = str_replace(RL_DS, '', RL_DIR);

            if ((bool) strpos(file_get_contents(RL_ROOT . '.htaccess'), "RewriteBase /{$sub_directory}/")) {
                $message = $phrase['subdirectory_notice1'];
            } else {
                $message = $phrase['subdirectory_notice2'];
            }

            $content .= sprintf($message, $sub_directory, $sub_directory);
            $content .= sprintf($phrase['subdirectory_notice3'], $sub_directory);
        }

        // Destroy session to clean installation fragments
        session_destroy();
        break;

    case 'introduction':
    default:
        $content .= interpolatePhrase('step_introduction_html_text1', array(
            '{version}' => $version,
        ));

        $content .= <<<HTML
        <div style="padding: 10px 0 0 0;">
            <input onclick="location.href='index.php?step=license_agreement'" type="button" value="{$phrase['install']}" />
        </div>
HTML;
        break;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="https://www.w3.org/1999/xhtml" dir="ltr" lang="en-gb" xml:lang="en-gb">
<head>
<title><?php _phrase('head_title', $version)?></title>
<link href="style.css" type="text/css" rel="stylesheet" />
<link type="image/x-icon" rel="shortcut icon" href="img/favicon.ico" />
<?php
if ($lang_count > 1 || ($_GET['step'] == 'tables' && count($_SESSION['extra_dumps']))) {
    echo '<script src="../libs/jquery/jquery.js"></script>';
}
?>
</head>
<body>
<div id="main_container">
    <div id="header">
        <div id="logo"></div>
        <div id="title"><?php _phrase('header_title', $version);?></div>
        <div id="nav_bar">
            <a target="_blank" href="<?php _phrase('url_flynax');?>"><?php _phrase('link_name_flynax');?></a>
            <a target="_blank" href="https://forum.flynax.com"><?php _phrase('link_name_forum');?></a>
            <a target="_blank" href="<?php _phrase('url_manual');?>"><?php _phrase('link_name_manual');?></a>
            <?php

            if ($lang_count > 1) {
                ?>

                <div class="lang-selector">
                    <span>
                        <?= $default_lang ?>
                        <svg>
                            <path fill="#000000" d="M4 2.577L1.716.293a1.01 1.01 0 0 0-1.423 0 1.01 1.01 0 0 0 0 1.423l2.991 2.99C3.481 4.903 3.741 5 4 5c.26.001.52-.096.716-.293l2.991-2.99a1.01 1.01 0 0 0 0-1.423 1.01 1.01 0 0 0-1.423 0L4 2.577z"></path>
                        </svg>
                    </span>

                    <div>
                    <?php

                    foreach ($languages as $lang_code) {
                        if ($lang_code == $default_lang) {
                            continue;
                        }

                        $lang_link = $_GET['step'] ? "?step={$_GET['step']}&" : '?';
                        $lang_link .= "lang={$lang_code}";

                        echo '<div><a href="' . $lang_link . '">' . $lang_code . '</a></div>';
                    }

                    ?>
                    </div>
                </div>

                <script>
                $(function(){
                    var $langSelector = $('.lang-selector');

                    $langSelector.find('> span').click(function(){
                        $('.lang-selector').toggleClass('active');
                    });
                    $(document).click(function(event){
                        if ($(event).target != $langSelector.get(0)
                            && !$(event.target).parents().hasClass('lang-selector'))
                        {
                            $langSelector.removeClass('active');
                        }
                    });
                });
                </script>

                <?php
            }

            ?>
        </div>
        <div class="clear"></div>
    </div>
    <div id="body">
        <div class="inner">
            <table>
            <tr>
                <td id="sidebar">
                    <div class="inner">
                        <ul>
                        <?php
$_GET['step'] = empty($_GET['step']) ? 'introduction' : $_GET['step'];
$no_proceed = false;
$s = 1;

foreach ($main_menu as $key => $value) {
    if ($_GET['step'] == $key) {
        $no_proceed = true;
        $margin = $s == 1 ? 'style="margin-top: 0;"' : '';
        echo '<li ' . $margin . ' class="active"><a href="javascript:void(0)">' . $value . '</a></li>';
    } else {
        if (!$no_proceed) {
            echo '<li class="done"><a href="index.php?step=' . $key . '">' . $value . '</a></li>';
        } else {
            echo '<li><div>' . $value . '</div></li>';
        }
    }
    $s++;
}
?>
                        </ul>
                        <div class="clear"></div>
                    </div>
                </td>

                <td id="content">
                    <div class="inner">
                        <?php
if (!empty($errors)) {
    echo '<div id="error"><div class="inner"><ul>';
    foreach ($errors as $er) {
        echo '<li>' . $er . '</li>';
    }
    echo '</ul></div></div>';
}
?>
                        <div id="center">
                            <?php
if ($_GET['step'] == 'tables' && count($_SESSION['extra_dumps'])) {
    ?>
                                    <p><?php _phrase('uploading_large_data_dump');?></p>
                                    <table style="margin: 10px 0;">
                                    <tr>
                                        <td class="table_caption"><div>
                                            <?php _phrase('uploading_of', $_SESSION['extra_dumps_current'], count($_SESSION['extra_dumps']));?>
                                        </div></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="progress">
                                                <div></div>
                                            </div>
                                            <div class="progress-info"><span>0</span><?php _phrase('percent_completed');?></div>
                                            <div class="progress-error-message"></div>
                                        </td>
                                    </tr>
                                    </table>
                                    <script>
                                    var import_in_progress = false;
                                    $(document).ready(function(){

                                        $.ajaxSetup({ cache: false });

                                        var loadDump = function(){
                                            import_in_progress = true;

                                            $.getJSON('index.php', {action: 'importDump'}, function(response){
                                                if (response['error']) {
                                                    $('.progress-error-message').text(response['error']);
                                                } else if (response['action'] == 'next_stack' || response['action'] == 'next_file') {
                                                    loadDump();

                                                    if (response['action'] == 'next_file') {
                                                        $('#current_dump').text(parseInt($('#current_dump').text())+1);
                                                    }

                                                    response['progress'] = response['progress'] > 100 ? 100 : response['progress'];
                                                    $('div.progress > div').width(response['progress']+'%');
                                                    $('div.progress-info > span').text(response['progress']);
                                                } else if (response['action'] == 'end') {
                                                    import_in_progress = false;
                                                    location.href='index.php?step=config_file';
                                                }
                                            })
                                        }
                                        loadDump();
                                    });

                                    $(window).bind('beforeunload', function() {
                                        if (import_in_progress) {
                                            return '<?php _phrase('uploading_in_progress');?>';
                                        }
                                    });
                                    </script>
                                    <?php
} else {
    echo $content;
}
?>
                        </div>
                    </div>
                </td>
            </tr>
            </table>
        </div>
    </div>

    <div id="footer">
        <span><?php _phrase('footer_copyright', date('Y'));?></span>
    </div>
</div>
</body>
</html>
