<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: FILE.PHP
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

/**
 * @since 4.6.0
 */
class File
{
    /**
     * Remove temporary form file
     *
     * @param string $field  - file input name
     * @param string $parent - file input array parent name, ex: ...
     *                         name="profile[photo]", "profile" is parent, "photo" is field
     */
    public static function removeTmpFile($field, $parent = null)
    {
        $file_name = $parent
        ? $_SESSION['tmp_files'][$parent][$field]
        : $_SESSION['tmp_files'][$field];
        $field_name = $parent ? $parent . "[{$field}]" : $field;

        /**
         * @since 4.5.2 - Moved below $file_name
         * @since 4.5.2 - $field, $parent, $id, $file_name, $field_name
         */
        $GLOBALS['rlHook']->load('ajaxRemoveTmpFile', $field, $parent, $id, $file_name, $field_name);

        @unlink(RL_UPLOAD . $file_name);

        return true;
    }

    /**
     * Remove uploaded file from listing/account field
     *
     * @since 4.7.0
     * 
     * @param  string $field    - Key of field
     * @param  string $value    - Name of uploaded file (value in field)
     * @param  string $type     - Listing or account field (available values: listing/account)
     * @param  int    $owner_id
     * @return bool
     */
    public static function removeFile($field, $value, $type = 'listing', $owner_id = 0)
    {
        global $rlDb;

        Valid::escape($field);
        Valid::escape($value);
        Valid::escape($type);
        $owner_id = (int) $owner_id;

        if (!$field || !$value || !$type || !$owner_id) {
            return false;
        }

        $table      = $type == 'listing' ? 'listings' : 'accounts';
        $select     = $type == 'listing' ? array('ID', 'Account_ID') : array('ID');
        $info       = $rlDb->fetch($select, array($field => $value), null, 1, $table)[0];
        $account_id = intval($type == 'listing' ? $info['Account_ID'] : $info['ID']);
        $where      = $account_id == $owner_id ? "`ID` = {$info['ID']}" : '';

        if ($table && $where) {
            @unlink(RL_FILES . $value);
            $GLOBALS['rlHook']->load('ajaxRemoveFile', $field, $value, $type, $owner_id, $table, $where);
            $rlDb->query("UPDATE `{db_prefix}{$table}` SET `{$field}` = '' WHERE {$where} LIMIT 1");

            return true;
        } else {
            return false;
        }
    }
}
