<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: VALID.PHP
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

namespace Flynax\Utils;

use HTMLPurifier_Config;

/**
 * @since 4.6.0
 */
class Valid
{
    /**
     * Escape string
     *
     * @param  string  - string to escape
     * @param  boolean - reference mode
     * @return string  - escaped string if no reference mode
     */
    public static function escape(&$string, $reference = false)
    {
        if (!is_string($string)) {
            return $string;
        }

        // mysqli mode
        if ($GLOBALS['mysqli']) {
            $string = $GLOBALS['mysqli']->real_escape_string($string);
        }
        // legacy mysql mode
        elseif (function_exists('mysql_real_escape_string')) {
            $string = mysql_real_escape_string($string);
        }
        // default mode
        else {
            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
                $string = stripslashes($string);
            }
            $string = str_replace("\'", "'", $string);
            $string = addslashes($string);
        }

        // return string if no reference mode
        if (!$reference) {
            return $string;
        }
    }

    /**
     * Replace quotes with it's html code
     *
     * @since 4.6.2
     * @param mixed $data - array or string to validate
     */
    public static function escapeQuotes(&$data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                self::escapeQuotes($item);
            }
        } else {
            $data = StringUtil::replaceAssoc($data, array(
                '"' => '&quot;',
                "'" => '&#039;',
            ));
        }
    }

    /**
     * Revert quotes to it's original chars
     *
     * @since 4.6.2
     * @param mixed $data - array or string to validate
     */
    public static function revertQuotes(&$data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                self::revertQuotes($item);
            }
        } else {
            $data = StringUtil::replaceAssoc(
                $data,
                array(
                    '&quot;'  => '"',
                    '&#34;'   => '"',
                    '&#039;'  => "'",
                    '&rsquo;' => "'",
                )
            );
        }
    }

    /**
     * Validation E-mail
     *
     * @param  string - email address to validate
     * @return boolean
     */
    public static function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validation URL
     *
     * @since 4.6.2
     *
     * @param  string - URL to validate
     * @return boolean
     */
    public static function isURL($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Strip javascript tags
     *
     * @param array $data - requested string
     * @param boolean     - reference mode
     * @return mixed      - stripped data
     **/
    public static function stripJS(&$data, $reference = false)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                self::stripJS($item, true);
            }
        } else {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('CSS', 'MaxImgLength');
            $purifier = new \HTMLPurifier($config);

            $data = $purifier->purify($data);
        }

        // Return data if no reference mode
        if (!$reference) {
            return $data;
        }
    }

    /**
     * HTML tags conversion
     *
     * @param  array $data - requested string
     * @param  boolean     - reference mode
     * @return mixed       - converted data
     */
    public static function html(&$data, $reference = false)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                self::html($value, true);
            }
        } else {
            $data = htmlspecialchars($data);
        }

        // Return data if no reference mode
        if (!$reference) {
            return $data;
        }
    }
}
