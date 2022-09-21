<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLDEBUG.CLASS.PHP
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

class rlDebug extends reefless
{
    /**
     * Path of file with errors
     * @var   string
     * @since 4.7.0
     */
    public $logFilePath = RL_TMP . 'errorLog/errors.log';

    /**
     * Debug class constructor
     */
    public function __construct()
    {
        if (RL_DEBUG === true) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 1);
            set_error_handler(array($this, 'errorHandler'), E_ALL | E_STRICT);
        } else {
            error_reporting(E_ERROR);
            ini_set('display_errors', 0);
            set_error_handler(array($this, 'errorHandler'), E_ALL | E_STRICT);
        }

        register_shutdown_function(array($this, 'fatalErrorHandler'));

        if (RL_DB_DEBUG) {
            unset($_SESSION['sql_debug_time']);
        }

        if (!function_exists('file_put_contents')) {
            function file_put_contents($filename, $data)
            {
                $f = @fopen($filename, 'w');
                if (!$f) {
                    return false;
                } else {
                    $bytes = fwrite($f, $data);
                    fclose($f);
                    return $bytes;
                }
            }
        }
    }

    /**
     * Fatal error handler
     */
    public function fatalErrorHandler()
    {
        $error = error_get_last();

        if (!$error) {
            return;
        }

        $error_file = $error['file'];
        $error_line = $error['line'];
        $exit = false;

        $error_type = 'Fatal Error';

        switch ($error['type']) {
            case E_PARSE:
                $error_type = 'Parse Error';

                break;

            case E_STRICT:
                $exit = true;
                $error_type = 'Strict Suggestion';

                break;

            case 8192;
                $exit = true;
                $error_type = 'Depricated run-time notices';

                break;

            default:
                $exit = true;
                break;
        }

        if ($exit) {
            return;
        }

        if (RL_DEBUG !== true) {
            echo 'A fatal error has occurred, please try again later or contact the Administrator.';
        }

        /* save log */
        if ($error) {
            $this->logger($error['message'], $error_file, $error_line, $error_type, false);
        }
    }

    /**
     * Error handler (logger), display and log the errors
     * 
     * @param  string $errno
     * @param  string $errstr
     * @param  string $errfile
     * @param  string $errline
     * @return true
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        /* if notices ocured then ignore */
        // 8192 - E_DEPRECATED
        // 16384 - E_USER_DEPRECATED
        if (in_array($errno, array(E_NOTICE, E_USER_NOTICE, E_WARNING, E_STRICT, E_USER_WARNING, 8192))) {
            return true;
        }

        switch ($errno) {
            case E_PARSE:
                $error_type = "Parse error";

                break;

            default:
                $error_type = "System error";

                break;
        }

        $this->saveLog($error_type, $errstr, $errline, $errfile);

        echo "<span style='font-family: tahoma; font-size: 12px;'>";
        echo "<h3>{$error_type} occurred</h2> <b>$errstr</b><br />";
        echo "line# <font color='green'><b>$errline</b></font><br />";
        echo "file: <font color='green'><b>$errfile</b></font><br />";
        echo "PHP version: " . PHP_VERSION . " <br /></span>";

        return true;
    }

    /**
     * Save system errors / warnings
     *
     * @todo - Write the errors
     * 
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @param string $errorType
     */
    public function logger($errstr, $errfile = __FILE__, $errline = __LINE__, $errorType = 'Warning', $trace = true)
    {
        /* override error file and line in case of proper debug backtrace */
        $error = debug_backtrace();
        if ($error && $trace) {
            $errfile = $error[0]['file'];
            $errline = $error[0]['line'];
            $errorType = 'DEBUG';
        }

        $this->saveLog($errorType, $errstr, $errline, $errfile);
    }

    /**
     * Save info in error.log file
     *
     * @since 4.7.0
     */
    protected function saveLog($type = 'DEBUG', $string = '', $line = '', $file = '')
    {
        if ($type && $string && $line && $file) {
            $file        = str_replace(RL_ROOT, RL_DS, $file);
            $repeats     = 1;
            $message     = "{$type}: {$string} on line# {$line} (file: {$file})";
            $log_line    = date('d M h:i:s') . " | {$repeats} repeats | {$message}" . PHP_EOL;
            $lineFound   = false;
            $log         = '';
            $logSize     = (int) number_format(filesize($this->logFilePath) / 1048576, 2);
            $memoryLimit = (int) ini_get('memory_limit') / 4;

            // remove old logs for software with version <= 4.7.0 or when the errors.log file have very big size
            if ($logSize >= $memoryLimit || 0 === substr_count(file_get_contents($this->logFilePath), 'repeats')) {
                $log = $log_line;
            } else {
                // count of the same messages found in file
                // open the file and scan each line
                foreach (file($this->logFilePath) as $line) {
                    if (!$lineFound && false !== strpos($line, $message) && false !== strpos($line, 'repeats')) {
                        preg_match('/([0-9]+)\srepeats/', $line, $matches);

                        if ($matches && $matches[1]) {
                            $lineFound = true;
                            $repeats   = (int) $matches[1] + 1;
                        }
                    } else {
                        $log .= $line;
                    }
                }

                $log .= str_replace('1 repeats', $repeats . ' repeats', $log_line);
            }

            file_put_contents($this->logFilePath, $log);
        }
    }
}
