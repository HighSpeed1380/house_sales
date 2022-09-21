<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PROFILE.PHP
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

use Flynax\Classes\ProfileThumbnailUpload;

/**
 * @since 4.6.1
 */
class Profile
{
    /**
     * Delete thumbnail
     *
     * @since 4.6.2 - Parameter $account_info has been replaced to $account_id
     *
     * @param  int     $account_id - ID of account
     * @return boolean             - Success status
     */
    public static function deleteThumbnail($account_id = 0)
    {
        global $rlDb, $config;

        $account_id = (int) $account_id;

        if (!$account_id) {
            return Util::errorResponse('Unable to delete profile thumbnail, no $account_id parameter passed');
        }

        $account_media = self::getProfilePhotoData($account_id);

        $update = array(
            'fields' => $config['thumbnails_x2']
                ? array('Photo' => '', 'Photo_x2' => '', 'Photo_original' => '')
                : array('Photo' => '', 'Photo_original' => ''),
            'where' => array('ID' => $account_id)
        );

        $GLOBALS['rlHook']->load('phpAjaxDelProfileThumbnailBeforeUpdate', $update); // from v4.0.2

        $rlDb->update($update, 'accounts');

        foreach ($account_media as $field => $file) {
            if (!$GLOBALS['rlAccount']->isAdmin()) {
                $_SESSION['account'][$field] = '';
            }
        }

        // Remove dir
        $exp_dir = explode('/', $account_media['Photo']);
        if (count($exp_dir) > 1) {
            array_pop($exp_dir);
            $dir = RL_FILES . implode(RL_DS, $exp_dir) . RL_DS;
            $GLOBALS['reefless']->deleteDirectory($dir);
        }

        return true;
    }

    /**
     * Crop thumbnail
     * @param  array $data          - crop dimensions
     * @param  array $account_info  - logged in account data
     * @return array                - cropped files data
     */
    public static function cropThumbnail($data, $account_info)
    {
        global $config, $reefless, $rlCrop;

        if (!$account_info) {
            return Util::errorResponse("Unable to crop profile thumbnail, no account_info data passed");
        }

        $reefless->loadClass('Crop');
        $reefless->loadClass('Resize');

        $account_media   = self::getProfilePhotoData($account_info['ID']);
        $original_name   = $account_media['Photo_original'] ?: $account_media['Photo'];
        $original        = RL_FILES . $original_name;
        $picture_data    = pathinfo($original_name);
        $name_hash       = time() . mt_rand();
        $cropped_picture = RL_UPLOAD . 'tmp_' . $name_hash . '.' . $config['output_image_format'];
        $cropped_by_user = false;

        // Crop image to given dimensions
        if (isset($data['x']) && isset($data['y'])) {
            $cropped_by_user = true;

            $sx = ceil($data['x']);
            $sy = ceil($data['y']);
            $ex = $sx + ceil($data['width']);
            $ey = $sy + ceil($data['height']);

            $rlCrop->loadImage($original);
            $rlCrop->cropToDimensions($sx, $sy, $ex, $ey);
            $rlCrop->saveImage($cropped_picture, $config['img_quality']);
            $rlCrop->flushImages();
        } else {
            copy($original, $cropped_picture);
        }

        // Resize image to resize versions
        $upload_obj     = new ProfileThumbnailUpload();
        $image_versions = $upload_obj->options['image_versions'];
        $folder_name    = $upload_obj->buildName($account_info['ID']);
        $rand           = $upload_obj->options['rand'];

        if (is_readable($cropped_picture)) {
            foreach ($image_versions as $version => $options) {
                $new_file_name = "{$picture_data['dirname']}/{$options['prefix']}-{$rand}.{$config['output_image_format']}";
                $new_file_path = RL_FILES . $new_file_name;

                $update_data[$options['db_field']] = $results[$options['db_field']] = $new_file_name;

                if (!$cropped_by_user && $options['force_crop']) {
                    $rlCrop->loadImage($cropped_picture);
                    $rlCrop->cropBySize($options['max_width'], $options['max_height'], ccCENTRE);
                    $rlCrop->saveImage($new_file_path, $config['img_quality']);
                    $rlCrop->flushImages();
                }

                $GLOBALS['rlResize']->resize(
                    !$cropped_by_user && $options['force_crop'] ? $new_file_path : $cropped_picture,
                    $new_file_path,
                    'C',
                    array($options['max_width'], $options['max_height']),
                    $options['force_crop'],
                    $options['watermark']
                );
            }
        } else {
            return Util::errorResponse("Unable to crop an profile picture, destination file is unreadable: {$cropped_picture}");
        }

        if (is_readable(RL_FILES . $update_data['Photo'])) {
            // Remove old files
            foreach ($image_versions as &$version_options) {
                unlink(RL_FILES . $account_media[$version_options['db_field']]);
            }

            // Remove tmp cropped file
            unlink($cropped_picture);

            self::updateData($account_info['ID'], $update_data);

            // Update session data in frontend
            if (!$GLOBALS['rlAccount']->isAdmin()) {
                foreach ($results as $db_field => $file_name) {
                    $_SESSION['account'][$db_field] = $file_name;
                }
            }

            return $results;
        } else {
            return Util::errorResponse("Unable to complete the crop profile thumbnail task, unable to create picture versions");
        }
    }

    /**
     * Get data from selected profile
     *
     * @since 4.7.0
     *
     * @param  int   $id
     * @param  array $columns - List of columns in "accounts" table which need be selected
     * @return array
     */
    public static function getProfileData($id, $columns = array('ID'))
    {
        $id = (int) $id;

        if (!$id || !is_array($columns)) {
            return array();
        }

        $GLOBALS['rlHook']->load('phpGetProfileData', $id, $columns);

        $data = $GLOBALS['rlDb']->fetch($columns, array('ID' => $id), null, 1, 'accounts', 'row');

        return $data ?: array();
    }

    /**
     * Get photo data from selected profile
     *
     * @since 4.7.0
     *
     * @param  int   $id
     * @return array     - List of photos
     */
    public static function getProfilePhotoData($id)
    {
        $columns = $GLOBALS['config']['thumbnails_x2']
        ? array('Photo', 'Photo_x2', 'Photo_original')
        : array('Photo', 'Photo_original');

        return self::getProfileData($id, $columns);
    }

    /**
     * Update data of selected profile
     *
     * @since 4.7.0
     *
     * @param  int   $id
     * @param  array $data - Data of profile which must be updated ('Photo' => 'value', and etc.)
     * @return bool
     */
    public static function updateData($id, $data = array())
    {
        $id = (int) $id;

        if (!$id || !$data) {
            return false;
        }

        $GLOBALS['rlHook']->load('ajaxRequestAccountThumbnailBeforeInsert', $id, $data);

        $GLOBALS['rlDb']->update(array('fields' => $data, 'where'  => array('ID' => $id)), 'accounts');

        return true;
    }

    /**
     * Get url with a personal address of selected profile
     *
     * @since 4.7.2 - Added $langCode parameter
     * @since 4.7.1
     *
     * @param  int|array   $account  - ID or data of profile
     * @param  array       $type     - Data of account type of this profile [optionally]
     * @param  string      $langCode - Get url in specific language
     * @return string|bool
     */
    public static function getPersonalAddress($account, $type = array(), $langCode = '')
    {
        global $rlAccount, $config, $rlHook, $reefless;

        if (!$account) {
            return false;
        }

        if (is_array($account)) {
            $id = (int) $account['ID'];
        } else {
            $id      = (int) $account;
            $account = $rlAccount->getProfile($id);
        }

        $rlHook->load('phpGetPersonalAddressBefore', $id, $account, $type);

        $address = $account['Own_address'];
        $url     = $reefless->getPageUrl('home', false, $langCode);
        $url     = str_replace(['http://', 'https://'], '', $url);
        $scheme  = $GLOBALS['domain_info']['scheme'];
        $type    = $type ?: $rlAccount->getTypeDetails($account['Type']);

        $rlHook->load('phpGetPersonalAddressPrepare', $account, $id, $address, $type, $scheme, $url);

        if (!$type['Page'] && !$type['Own_page']) {
            return false;
        }

        if ($type['Own_location'] && $address && $config['mod_rewrite']) {
            if ($config['account_wildcard']) {
                $url = str_replace('www.', '', $url);
                $url = "{$scheme}://{$address}.{$url}";
            } else {
                $url = "{$scheme}://{$url}{$address}/";
            }
        } else {
            $path = $config['mod_rewrite'] ? [$address] : ['id' => $id];
            $url  = $reefless->getPageUrl('at_' . ($type['Key'] ?: $type['Type']), $path, $langCode);
            $url  = str_replace('.html', '/', $url);
        }

        /**
         * @since 4.9.0 - Added $langCode parameter
         */
        $rlHook->load('phpGetPersonalAddressAfter', $id, $account, $address, $type, $url, $langCode);

        return $url;
    }

    /**
     * Prepare media data item
     *
     * @since 4.8.0
     *
     * @param  array   &$data     - media item(s)
     * @return mixed              - prepared media items
     */
    public static function prepareURL(&$data)
    {
        if (!is_array($data)) {
            return $data;
        }

        reset($data);

        if (is_array(current($data))) {
            foreach ($data as &$item) {
                self::prepare($item);
            }
        } else {
            self::prepare($data);
        }
    }

    /**
     * Prepare media url, fix or change url in hook
     *
     * @since 4.8.0
     *
     * @param array &$item - media item data
     */
    private static function prepare(&$item)
    {
        $file_dir = RL_FILES_URL;
        $fields = array('Photo', 'Photo_x2', 'Photo_original');

        $GLOBALS['rlHook']->load('accountMediaPrepare', $item, $fields, $file_dir);

        foreach ($fields as $field) {
            if (!empty($item[$field])) {
                $item[$field] = $file_dir . $item[$field];
            }
        }
    }

}
