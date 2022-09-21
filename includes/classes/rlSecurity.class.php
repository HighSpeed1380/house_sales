<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLSECURITY.CLASS.PHP
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

class FLSecurity
{
    /**
     * Crypt password
     *
     * 1. Password encrypted using bcrypt if php version more than 5.3.7
     * 2. If less than 5.3.7 than password crypted using salted md5
     *
     * @param string $password Plain password
     *
     * @return string $hash Crypted password
     */
    public static function cryptPassword($password)
    {
        if (version_compare(phpversion(), '5.3.7') > 0) {
            require_once RL_LIBS . 'password.php';

            $hash = password_hash($password, PASSWORD_DEFAULT, array('cost' => 12));
        } else {
            $salt = $GLOBALS['reefless']->generateHash(32, 'password');
            $hash = md5($salt . $password) . ':' . $salt;
        }

        return $hash;
    }

    /**
     * Verify password
     *
     * password can be hashed using bcrypt (>4.4.1 and php>5.3.7)
     * password can be crypted with md5 (old flynax versions)
     * password can be crypted with salted md5 (>4.4.1 version when php<5.3.7)
     *
     * @param string $password Plain password
     * @param string $dbhash   Password hash from database
     * @param bool   $direct   Trigger to diff inputs directly
     *
     * @return bool
     */
    public static function verifyPassword($password, $dbhash, $direct = false)
    {
        if ($direct) {
            return ($password == $dbhash);
        }

        if (strlen($dbhash) == 32) {
            return md5($password) === $dbhash;
        }

        if (strlen($dbhash) == 65) {
            $dbpass = substr($dbhash, 0, 32);
            $dbsalt = substr($dbhash, 33, 32);

            return md5($dbsalt . $password) . ':' . $dbsalt === $dbhash;
        }

        if (version_compare(phpversion(), '5.3.7') > 0) {
            require_once RL_LIBS . 'password.php';

            if (password_verify($password, $dbhash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rehash password if necessary
     *
     * @param string $oldhash        Password hash
     * @param string $plain_password Plain password
     *
     * @return string|bool
     */
    public static function rehashIfNecessary($oldhash, $plain_password)
    {
        if (strlen($oldhash) == 32 || strlen($oldhash) == 65 && version_compare(phpversion(), '5.3.7') > 0) {
            return FLSecurity::cryptPassword($plain_password);
        }

        return false;
    }
}
