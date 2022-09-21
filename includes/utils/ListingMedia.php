<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTINGMEDIA.PHP
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

use Flynax\Classes\ListingPictureUpload;

/**
 * @since 4.6.0
 */
class ListingMedia
{
    /**
     * Youtube preview thumbnail url patern
     * @var string
     */
    private static $youTubePreview = 'https://i.ytimg.com/vi/{key}/mqdefault.jpg';

    /**
     * Prepare media data item
     *
     * @param  array   &$data     - media item(s)
     * @param  boolean $reference - data by reference flag
     * @return mixed              - prepared media items
     */
    public static function prepareURL(&$data, $reference = false)
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

        if (!$reference) {
            return $data;
        }
    }

    /**
     * Prepare media url, fix or change url in hook
     *
     * @since 4.9.0 - Method protection type changed from private to public
     *
     * @param array &$item - media item data
     */
    public static function prepare(&$item)
    {
        $file_dir = RL_FILES_URL;
        $fields = array('Photo', 'Thumbnail', 'Thumbnail_x2', 'Original', 'Main_photo', 'Main_photo_x2');

        $GLOBALS['rlHook']->load('listingMediaPrepare', $item, $fields, $file_dir);

        if ($item['Type'] == 'video') {
            // Prepare youtube preview
            if ($item['Original'] == 'youtube') {
                $item['Thumbnail'] = str_replace('{key}', $item['Photo'], self::$youTubePreview);
                $item['href'] = "//www.youtube.com/embed/{$item['Photo']}";
                $item['href'] .= $GLOBALS['config']['video_autostart'] ? '?autoplay=1' : '';

                $item['youtube_key'] = $item['Photo'];
                unset($item['Photo']);
            } else {
                $item['href'] = RL_FILES_URL . $item['Original'];
            }
        }

        // Prepare urls
        if ($item['Original'] != 'youtube') {
            foreach ($fields as $field) {
                if (!empty($item[$field])) {
                    $item[$field] = $file_dir . $item[$field];
                }
            }
        }
    }

    /**
     * Delete media item data
     *
     * @param  int   $listing_id    - listing ID
     * @param  int   $media_id      - media ID
     * @param  array &$account_info - current account information array
     * @return boolean              - is media data removed flag
     */
    public static function delete($listing_id, $media_id, &$account_info)
    {
        global $rlDb;

        $listing_id = (int) $listing_id;
        $media_id = (int) $media_id;

        if (!$listing_id || !$media_id) {
            return false;
        }

        $condition = array('ID' => $media_id, 'Listing_ID' => $listing_id);
        $account_id = $rlDb->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');
        $item = $rlDb->fetch('*', $condition, null, 1, 'listing_photos', 'row');

        if ($account_id != $account_info['ID'] || !$item) {
            return false;
        }

        // Remove picture DB entry
        if (!$rlDb->delete($condition, 'listing_photos')) {
            return false;
        }

        // Remove related media
        $files = array('Photo', 'Thumbnail', 'Thumbnail_x2', 'Original');
        $dir   = dirname(RL_FILES . $item['Original']);

        /**
         * @since 4.7.1 - Added $dir parameter
         * @since 4.6.1 - Added $item parameter
         */
        $GLOBALS['rlHook']->load('listingMediaDeletePicture', $item, $files, $account_info, $dir);

        // remove related files (thumbnail, large and etc.)
        foreach ($files as $file) {
            unlink(RL_FILES . $item[$file]);
        }

        // update photos data
        self::updateMediaData($listing_id);

        self::removeEmptyDir($dir);

        return true;
    }

    /**
     * Manage media description
     *
     * @param  int    $listing_id    - listing ID
     * @param  int    $media_id      - media ID
     * @param  string $description   - new media description
     * @param  array  &$account_info - current account information array
     * @return boolean               - is action successfully processed flag
     */
    public static function manageDescription($listing_id, $media_id, $description = '', &$account_info = [])
    {
        global $rlDb;

        $listing_id = (int) $listing_id;
        $media_id = (int) $media_id;

        if (!$listing_id || !$media_id) {
            return false;
        }

        $condition = array('ID' => $media_id, 'Listing_ID' => $listing_id);
        $account_id = $rlDb->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');
        $item = $rlDb->fetch('*', $condition, null, 1, 'listing_photos', 'row');

        if ($account_id != $account_info['ID'] || !$item) {
            return false;
        }

        // update description
        $update = array(
            'fields' => array('Description' => $description),
            'where'  => $condition,
        );

        return $rlDb->update($update, 'listing_photos');
    }

    /**
     * Update cached media listing data
     *
     * @param  boolean $listing_id - listing ID
     * @return boolean             - is action successfully processed flag
     */
    public static function updateMediaData($listing_id)
    {
        global $rlHook, $config, $rlDb;

        $listing_id = (int) $listing_id;

        if (!$listing_id) {
            return false;
        }

        $count = 0;

        $sql = 'SELECT `Thumbnail` ';

        if ($config['thumbnails_x2']) {
            $sql .= ', Thumbnail_x2 ';
        }

        $rlHook->load('phpUpdatePhotoDataModifyField', $sql, $listing_id);

        $sql .= 'FROM `{db_prefix}listing_photos` ';
        $sql .= "WHERE `Listing_ID` = {$listing_id} AND `Type` = 'picture' ";
        $sql .= "ORDER BY `Position` ASC LIMIT 1";

        if ($listing = $rlDb->getRow($sql)) {
            $count_sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}listing_photos`
                WHERE `Listing_ID` = {$listing_id} AND `Type` = 'picture'
            ";
            $count = $rlDb->getRow($count_sql, 'Count');
        }

        $update_sql = 'UPDATE `{db_prefix}listings` SET ';
        $update_sql .= "`Main_photo` = '{$listing['Thumbnail']}', ";

        if ($config['thumbnails_x2']) {
            $update_sql .= "`Main_photo_x2` = '{$listing['Thumbnail_x2']}', ";
        }

        $update_sql .= "`Photos_count` = {$count} ";

        $rlHook->load('phpUpdatePhotoDataSetFields', $update_sql, $listing, $listing_id);

        $update_sql .= "WHERE `ID` = {$listing_id} LIMIT 1";

        $rlDb->query($update_sql);
    }

    /**
     * Reorder media
     *
     * @param int   $listing_id - Related listing ID
     * @param array $data       - Order data
     */
    public static function reorder($listing_id = false, $data = false, &$account_info = [])
    {
        global $rlDb;

        $listing_id = (int) $listing_id;
        $data = trim($data, ';');

        if (!$listing_id || !$data) {
            return false;
        }

        $account_id = $rlDb->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');

        if ($account_id != $account_info['ID']) {
            return false;
        }

        // update order
        foreach (explode(';', $data) as $value) {
            $item = explode(',', $value);

            $update[] = array(
                'fields' => array('Position' => $item[1]),
                'where'  => array(
                    'ID'         => $item[0],
                    'Listing_ID' => $listing_id,
                ),
            );
        }

        $rlDb->update($update, 'listing_photos');

        // update photos data
        self::updateMediaData($listing_id);

        return true;
    }

    /**
     * Add Youtube video by youtube embed or url
     *
     * @param  int    $listing_id    - listing ID
     * @param  string $link          - youtube link or embed code
     * @param  array  &$account_info - current account information array
     * @param  array  &$plan_info    - listing related plan data
     * @param  int    $position      - video position
     * @return boolean               - is action successfully processed flag
     */
    public static function addYouTube($listing_id, $link, &$account_info, &$plan_info, $position = 0)
    {
        global $rlDb;

        $listing_id = (int) $listing_id;

        if (!$listing_id || !$link) {
            return false;
        }

        $account_id = $rlDb->getOne('Account_ID', "`ID` = {$listing_id}", 'listings');

        if ($account_id != $account_info['ID']) {
            return false;
        }

        // Exceed limit check
        $sql = "
            SELECT COUNT(*) AS `Count`
            FROM `{db_prefix}listing_photos`
            WHERE `Listing_ID` = {$listing_id} AND `Type` = 'video'
        ";
        $videos = $rlDb->getRow($sql, 'Count');

        if (!$plan_info || (!$plan_info['Video_unlim'] && $videos >= $plan_info['Video'])) {
            return false;
        }

        // Get YouTube ID
        if (0 === strpos($link, 'http')) {
            if (false !== strpos($link, 'youtu.be')) {
                $explodedLink = explode('/', $link);
                $youtube_id = array_pop($explodedLink);
            } else {
                preg_match('/v=([^\&]+)/', $link, $matches);
                $youtube_id = $matches[1];

                if (!$youtube_id) {
                    preg_match('/embed\/([\w\-]*)/', $link, $matches);
                    $youtube_id = $matches[1];
                }
            }
        } else {
            preg_match('/src=".+embed\/([^"]+)"/', $link, $matches);

            if (!$matches[1]) {
                preg_match('/v=([^\&]*)/', $link, $matches);
            }

            $youtube_id = $matches[1];
        }

        if ($youtube_id) {
            // Check video is available
            $check_url = "https://www.youtube.com/oembed?format=json&url=https://www.youtube.com/watch?v=" . $youtube_id;
            $video = json_decode(Util::getContent($check_url), true);

            // Add media
            if ($video['type'] == 'video') {
                // Get alternative position
                if (!$position) {
                    $sql = "
                        SELECT MAX(`Position`) AS `Max`
                        FROM `{db_prefix}listing_photos`
                        WHERE `Listing_ID` = {$listing_id}
                    ";
                    $position = $rlDb->getRow($sql, 'Max') + 1;
                }

                // Insert video
                $insert = array(
                    'Listing_ID'  => $listing_id,
                    'Position'    => $position,
                    'Photo'       => $youtube_id,
                    'Thumbnail'   => '',
                    'Original'    => 'youtube',
                    'Description' => $video['title'],
                    'Type'        => 'video',
                );
                $rlDb->insert($insert, 'listing_photos');
                $insert['ID'] = $rlDb->insertID();

                return self::prepareURL($insert);
            }
        }

        $GLOBALS['rlDebug']->logger('Unable to verify youtube video by url: ' . $link);
        return false;
    }

    /**
     * @deprecated 4.9.0 - Use updatePicture() instead
     */
    public static function cropPicture($listing_id, $media_id, $data = null, &$account_info = [])
    {}

    /**
     * Rotate picture
     *
     * @since 4.9.0
     *
     * @param  string  $sourcePictire  - Source picture path
     * @param  string  $rotatedPicture - Destination picture path
     * @param  integer $angle          - Angle in degrees
     * @param  integer $quality        - Destination picture quality
     * @return boolean                 - Success status
     */
    public static function rotate($sourcePictire, $rotatedPicture, $angle, $quality = 90)
    {
        $info = getimagesize($sourcePictire);
        $source_res = false;

        switch ($info['mime']) {
            case 'image/jpeg':
                $source_res = imagecreatefromjpeg($sourcePictire);
                break;
            case 'image/png':
                $source_res = imagecreatefrompng($sourcePictire);
                break;
            case 'image/gif':
                $source_res = imagecreatefromgif($sourcePictire);
                break;
            case 'image/webp':
                $source_res = imagecreatefromwebp($sourcePictire);
                break;
        }

        $rotate_res = imagerotate($source_res, $angle, 0);
        $success = false;

        switch ($info['mime']) {
            case 'image/jpeg':
                $success = imagejpeg($rotate_res, $rotatedPicture, $quality);
                break;
            case 'image/png':
                $success = imagepng($rotate_res, $rotatedPicture);
                break;
            case 'image/gif':
                $success = imagegif($rotate_res, $rotatedPicture);
                break;
            case 'image/webp':
                $success = imagewebp($rotate_res, $rotatedPicture, $quality);
                break;
        }

        imagedestroy($rotate_res);
        imagedestroy($source_res);

        return $success;
    }

    /**
     * Rotate and show picture
     *
     * @since 4.9.0
     *
     * @param  integer $mediaID - Media ID
     * @return boolean          - Success status
     */
    public static function tmpRotate($mediaID)
    {
        $picture = $GLOBALS['rlDb']->fetch(['Original', 'Angle'], ['ID' => $mediaID], null, 1, 'listing_photos', 'row');

        if (!$picture) {
            return false;
        }

        $source_picture = RL_FILES . $picture['Original'];

        $GLOBALS['rlHook']->load('listingMediaTmpRotate', $mediaID, $picture, $source_picture);

        $info = getimagesize($source_picture);
        $source_res = false;

        switch ($info['mime']) {
            case 'image/jpeg':
                $source_res = imagecreatefromjpeg($source_picture);
                break;
            case 'image/png':
                $source_res = imagecreatefrompng($source_picture);
                break;
            case 'image/gif':
                $source_res = imagecreatefromgif($source_picture);
                break;
            case 'image/webp':
                $source_res = imagecreatefromwebp($source_picture);
                break;
        }

        $rotate_res = imagerotate($source_res, $picture['Angle'], 0);
        $success = false;

        header('Content-type: ' . $info['mime']);

        switch ($info['mime']) {
            case 'image/jpeg':
                $success = imagejpeg($rotate_res);
                break;
            case 'image/png':
                $success = imagepng($rotate_res);
                break;
            case 'image/gif':
                $success = imagegif($rotate_res);
                break;
            case 'image/webp':
                $success = imagewebp($rotate_res);
                break;
        }

        imagedestroy($rotate_res);
        imagedestroy($source_res);

        return $success;
    }

    /**
     * Get updated crop coordinates by new rotation angle
     *
     * @since 4.9.0
     *
     * @param  array   $picture - Picture data from the database, 'Original' and 'Crop' are required
     * @param  integer $angle   - New rotation angle
     * @return array|boolean    - Update crop coordinates or false
     */
    public static function getUpdatedCropData($picture, $angle = 0)
    {
        if (!is_array($picture)) {
            return false;
        }

        $source_picture = RL_FILES . $picture['Original'];

        $GLOBALS['rlHook']->load('listingMediaUpdateCropData', $picture, $angle, $source_picture);

        $info = getimagesize($source_picture);

        if (!$info) {
            return false;
        }

        $data = $picture['Crop'];
        $new_data = $data;

        switch ($angle) {
            case -90:
            case -270:
                $new_data['x'] = $info[1] - $data['y'] - $data['height'];
                $new_data['y'] = $data['x'];
                $new_data['width'] = $data['height'];
                $new_data['height'] = $data['width'];
                break;

            case -180:
            case 0:
                $new_data['x'] = $info[0] - $data['y'] - $data['height'];
                $new_data['y'] = $data['x'];
                $new_data['width'] = $data['height'];
                $new_data['height'] = $data['width'];
                break;
        }

        return $new_data;
    }

    /**
     * Get listing media taking into account plan limits
     *
     * @param  int   $listing_id  - listing ID
     * @param  mixed $photo_limit - pictures limit, data from plan
     * @param  mixed $video_limit - video limit, data from plan
     * @return boolean            - is action successfully processed flag
     */
    public static function get($listing_id = null, $photo_limit = true, $video_limit = true, $listing_type = false)
    {
        global $l_youtube_thumbnail;

        $listing_id = (int) $listing_id;

        if (!$listing_id || ($listing_type && !$listing_type['Photo'] && !$listing_type['Video'])) {
            return false;
        }

        if (!$l_youtube_thumbnail) {
            trigger_error('Unable to get the listing media, no $l_youtube_thumbnail varialbe available', E_USER_ERROR);
            return false;
        }

        $where = array(
            'Listing_ID' => $listing_id,
            'Status'     => 'active',
        );

        if ($listing_type && !$listing_type['Video']) {
            $where['Type'] = 'picture';
        } elseif ($listing_type && !$listing_type['Photo']) {
            $where['Type'] = 'video';
        }

        $media = $GLOBALS['rlDb']->fetch(
            '*',
            $where,
            "ORDER BY `Position`",
            null,
            'listing_photos'
        );

        $photo_count = 0;
        $video_count = 0;

        foreach ($media as $key => &$item) {
            if (
                (
                    $item['Type'] == 'picture'
                    && $photo_limit !== true
                    && $photo_limit <= $photo_count
                ) || (
                    $item['Type'] == 'video'
                    && $video_limit !== true
                    && $video_limit <= $video_count
                )
            ) {
                unset($media[$key]);
            }

            if ($item['Type'] == 'picture') {
                $photo_count++;
            } elseif ($item['Type'] == 'video') {
                $video_count++;
            }
        }

        $media = array_values($media);

        return self::prepareURL($media);
    }

    /**
     * Build Media File SEO Name (picture or video)
     *
     * Build human readable media file name from the listing title
     *
     * @since 4.9.0 - The extension will be replaced by option "Output image format" if it's available
     * @since 4.7.0 - moved from ListingPictureUpload
     *
     * @param  int $listing_id    - listing_id
     * @param  string $postfix    - large, orig, x2 - picture postfix
     * @param  int    $position   - numeric photo/video file position
     * @param  string $extension  - picture file extension
     * @param  string $upload_dir - directory picture uploaded to
     *
     * @return string             - photo name
     */
    public static function buildName($listing_id, $postfix, $position = 0, $extension = '', $upload_dir = '')
    {
        global $lang, $rlListings;

        $listing_id = (int) $listing_id;

        if (!$listing_id || !$upload_dir) {
            return false;
        }

        $options = self::getOptions();

        // Check if postfix starts with underscore _
        $postfix = substr($postfix, 0, 1) == '_' ? substr($postfix, 1) : $postfix;

        $extension = $GLOBALS['config']['output_image_format'] ?: $extension;

        if (!$options['seo_picture_names']) {
            $name_hash = time() . mt_rand();
            $index = '';
        } else {
            if (!$GLOBALS['rlListingTypes']) {
                $GLOBALS['reefless']->loadClass('ListingTypes');
            }
            $listing = $rlListings->getListing($listing_id);

            if (!$lang) {
                $sql = "SELECT * FROM `{db_prefix}lang_keys` ";
                $sql .= "WHERE `Key` LIKE 'categories+name+%' AND `Code` = '{$GLOBALS['config']['lang']}'";
                $lang = $GLOBALS['rlDb']->getAll($sql, array('Key', 'Value'));
            }

            if ($options['random_number_in_seo_name']) {
                $index = mt_rand();
            } else {
                $index = $position > 0 ? $position : '';
            }

            $listing_title = $rlListings->getListingTitle($listing['Category_ID'], $listing, $listing['Listing_type']);

            // Truncate the listing title to fit the Main_photo column length
            $other_part_len = strlen($index) + 4 + ($extension ? strlen($extension) + 1 : 5);
            $other_part_len += strlen($upload_dir);

            $title_maxlen  = $options['seo_picture_names_maxlength'] - $other_part_len;
            $listing_title = substr($listing_title, 0, $title_maxlen);
            $name_hash     = $GLOBALS['rlValid']->str2path($listing_title);

            // Remove trailing numbers to avoid issues with position
            $name_hash = preg_replace('/-[0-9]+$/', '', $name_hash);
        }

        $replace = array(
            '{name_hash}' => $name_hash,
            '{index}' => $index
        );

        if ($postfix !== null) {
            $replace['{postfix}'] = $postfix;
        }

        $file_name = StringUtil::replaceAssoc(
            $options['file_name_template'],
            $replace
        );

        $file_name = str_replace('-_', '_', $file_name);
        $file_name = str_replace(',', '_', $file_name);
        $file_name = trim($file_name, " \t\n\r\0\x0B-_");

        $check_file = RL_FILES . $upload_dir . $file_name . '.' . $extension;

        if (is_file($check_file)) {
            return self::buildName($listing_id, $postfix, ++$position, $extension, $upload_dir);
        }

        return $file_name;
    }

    /**
     * Update Media (picture and video) Names
     * Update names when listing title changed
     *
     * @since 4.7.0 - moved from ListingPictureUpload
     *
     * @param int $listingID - ID of the listing to update names of
     */
    public static function updateNames($listingID)
    {
        if (!$listingID = (int) $listingID) {
            return false;
        }

        $options = self::getOptions();

        if (!$options['seo_picture_names']) {
            return false;
        }

        global $rlDebug, $rlDb, $config;

        foreach ($rlDb->fetch('*', ['Listing_ID' => $listingID], null, null, 'listing_photos') as $photo) {
            $upd         = [];
            $fileInfo    = pathinfo($photo['Original']);
            $img_options = $photo['Type'] == 'picture' ? $options['image_versions'] : [];
            $orig_option = ['prefix'   => '_orig', 'db_field' => 'Original'];

            array_push($img_options, $orig_option);

            $options['upload_dir'] = RL_FILES . $fileInfo['dirname'] . '/';

            foreach ($img_options as $option) {
                $db_field = $option['db_field'];

                $upd['fields'][$db_field] = $fileInfo['dirname']
                    . '/'
                    . self::buildName(
                        $listingID,
                        $option['prefix'],
                        $photo['Position'],
                        $fileInfo['extension'],
                        $fileInfo['dirname'] . '/'
                    )
                    . '.'
                    . ($db_field === 'Original' ? $fileInfo['extension'] :  $config['output_image_format']);

                if (is_file(RL_FILES . $photo[$db_field])) {
                    if (!rename(RL_FILES . $photo[$db_field], RL_FILES . $upd['fields'][$db_field])) {
                        $rlDebug->logger('Media file rename failed on file - ' . $photo[$db_field]);
                    } else {
                        $upd['where']['ID'] = $photo['ID'];
                    }
                } else {
                    if ($photo['Original'] != 'youtube') {
                        $rlDebug->logger('Media file copying error; no file found - ' . $photo[$db_field]);
                    }
                }
            }

            if ($upd['where']['ID']) {
                $rlDb->update($upd, 'listing_photos');
            }
        }

        self::updateMediaData($listingID);

        return true;
    }

    /**
     * Get Options function - return image options
     * @since 4.7.0
     */
    public static function getOptions()
    {
        global $config;

        $options = array(
            // @since 4.8.2 - Added "webp" format
            'picture_file_types'          => '/(\.|\/)(gif|jpe?g|png|webp)$/i',
            'video_file_types'            => '/(\.|\/)(mp4|webm|ogg)$/i',
            // Defines which files (based on their names) are accepted for upload
            'accept_file_types'           => '/(\.|\/)(gif|jpe?g|png|webp|mp4|webm|ogg)$/i',
            // Extensions string to appear in error messages
            'accept_file_ext'             => 'gif/jpeg/png/webp/mp4/webm/ogg',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting
            'max_file_size'               => null,
            'min_file_size'               => 1,
            'param_name'                  => 'files',
            // Image resolution restrictions:
            'max_width'                   => null,
            'max_height'                  => null,
            'min_width'                   => 1,
            'min_height'                  => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads'     => true,
            // Set to true to rotate images based on EXIF meta data, if available:
            'orient_image'                => true,
            // Set true to get name from listing title, false to use numeric hash as name
            'seo_picture_names'           => true,
            // Main_photo listings table column length
            'seo_picture_names_maxlength' => 80,
            // Adds a random number to aboid problem with cache to name
            'random_number_in_seo_name'   => true,
            // File name template to use for picture names
            'file_name_template'          => '{name_hash}-{index}_{postfix}',
            // Image version to create
            'image_versions'              => array(
                'large'        => array(
                    'prefix'     => '_large',
                    'db_field'   => 'Photo',
                    'max_width'  => $config['pg_upload_large_width'] ?: 640,
                    'max_height' => $config['pg_upload_large_height'] ?: 480,
                    'watermark'  => (bool) $config['watermark_using'],
                    'force_crop' => (bool) $config['img_crop_module'],
                ),
                'Thumbnail'    => array(
                    'prefix'     => '',
                    'db_field'   => 'Thumbnail',
                    'max_width'  => $config['pg_upload_thumbnail_width'] ?: 120,
                    'max_height' => $config['pg_upload_thumbnail_height'] ?: 90,
                    'force_crop' => (bool) $config['img_crop_thumbnail'],
                    'watermark'  => false,
                ),
                'thumbnail_x2' => array(
                    'prefix'     => '_x2',
                    'db_field'   => 'Thumbnail_x2',
                    'max_width'  => $config['pg_upload_thumbnail_width'] ? $config['pg_upload_thumbnail_width'] * 2 : 240,
                    'max_height' => $config['pg_upload_thumbnail_height'] ? $config['pg_upload_thumbnail_height'] * 2 : 180,
                    'force_crop' => (bool) $config['img_crop_thumbnail'],
                    'watermark'  => false,
                ),
            ),
        );

        // remove x2 thumbnails of related option disabled
        if (!$config['thumbnails_x2']) {
            unset($options['image_versions']['thumbnail_x2']);
        }

        return $options;
    }

    /**
     * Remove listing folder (and parent folder) if it doesn't have any files yet
     *
     * @since 4.7.1
     *
     * @param  string $dir   - Path of folder, for ex. /root/../public_html/files/MM-YYYY/ad1/
     * @param  bool   $force - Remove folder when it's not empty
     * @return bool
     */
    public static function removeEmptyDir($dir, $force = false)
    {
        global $reefless;

        $dir       = (string) $dir;
        $parentDir = dirname($dir);
        $result    = false;

        if (!$dir || strpos($dir, RL_FILES) !== 0 || strlen(RL_FILES) >= strlen($dir)) {
            return $result;
        }

        // remove empty folder
        if (count(scandir($dir)) === 2 || $force) {
            $result = $reefless->deleteDirectory($dir);
        }

        // remove empty parent folder
        if ($result && count(scandir($parentDir)) === 2) {
            $result = $reefless->deleteDirectory($parentDir);
        }

        return $result;
    }

    /**
     * Update listing picture versions by picture data
     *
     * @since 4.9.0 - Return value may be array
     * @since 4.7.1
     *
     * @param  array $picture - Picture database entry data
     * @return array|boolean  - New files data or false
     */
    public static function updatePicture($picture)
    {
        global $reefless, $config, $rlDb, $rlHook, $rlDebug, $rlCrop;

        if (!$picture['ID'] || !$picture['Photo'] || !$picture['Listing_ID']) {
            return false;
        }

        $reefless->loadClass('Crop');
        $reefless->loadClass('Resize');
        $reefless->loadClass('Listings');

        $results        = [];
        $upload_options = self::getOptions();
        $image_versions = $upload_options['image_versions'];
        $source_picture = $picture['Original'] ?: $picture['Photo'];
        $picture_data   = pathinfo($source_picture);
        $source_file    = RL_FILES . $source_picture;

        if ($picture['Crop'] && !is_array($picture['Crop'])) {
            $picture['Crop'] = json_decode($picture['Crop'], true);
        }

        $rlHook->load(
            'phpListingMediaUpdatePictureTop',
            $picture,
            $image_versions,
            $source_picture,
            $picture_data,
            $source_file
        );

        $rotated = false;
        $cropped = false;

        // Rotate picture
        if (isset($picture['Angle']) && $picture['Angle'] !== 0) {
            $name_hash = time() . mt_rand();
            $rotated_picture = RL_UPLOAD . 'tmp_' . $name_hash . '.' . $picture_data['extension'];

            if (self::rotate($source_file, $rotated_picture, $picture['Angle'])) {
                $source_file = $rotated_picture;
                $rotated = true;
            }
        }

        // Crop picture
        if ($config['img_crop_interface'] && $picture['Crop']) {
            $name_hash = time() . mt_rand();
            $cropped_picture = RL_UPLOAD . 'tmp_' . $name_hash . '.' . $picture_data['extension'];

            $sx = ceil($picture['Crop']['x']);
            $sy = ceil($picture['Crop']['y']);
            $ex = $sx + ceil($picture['Crop']['width']);
            $ey = $sy + ceil($picture['Crop']['height']);

            $GLOBALS['rlCrop']->loadImage($source_file);
            $GLOBALS['rlCrop']->cropToDimensions($sx, $sy, $ex, $ey);

            if ($GLOBALS['rlCrop']->saveImage($cropped_picture, 90)) {
                $GLOBALS['rlCrop']->flushImages();
                $source_file = $cropped_picture;
                $cropped = true;
            }
        }

        if (is_readable($source_file)) {
            $photo_name = self::buildName(
                $picture['Listing_ID'],
                null,
                $picture['Position'],
                $config['output_image_format'],
                $picture_data['dirname'] . '/'
            );

            foreach ($image_versions as $version => $options) {
                $new_file_name = str_replace('_{postfix}', $options['prefix'], $photo_name);
                $new_file_name = $picture_data['dirname'] . '/' . $new_file_name . '.' . $config['output_image_format'];
                $new_file_path = RL_FILES . $new_file_name;

                $results[$version] = $new_file_name;

                if (!$cropped && $options['force_crop']) {
                    $rlCrop->loadImage($source_file);
                    $rlCrop->cropBySize($options['max_width'], $options['max_height'], ccCENTRE);
                    $rlCrop->saveImage($new_file_path, $config['img_quality']);
                    $rlCrop->flushImages();
                }

                $GLOBALS['rlResize']->resize(
                    !$cropped && $options['force_crop'] ? $new_file_path : $source_file,
                    $new_file_path,
                    'C',
                    array($options['max_width'], $options['max_height']),
                    $options['force_crop'],
                    $options['watermark']
                );
            }

            if (is_readable(RL_FILES . $results['Thumbnail'])) {
                // Remove old files
                foreach ($image_versions as $options) {
                    unlink(RL_FILES . $picture[$options['db_field']]);
                }

                // Update db entry
                $update = array(
                    'fields' => array(),
                    'where'  => array('ID' => $picture['ID']),
                );

                if ($rotated || $picture['Angle'] === 0) {
                    $update['fields']['Angle'] = $picture['Angle'];
                }
                if ($cropped) {
                    $update['fields']['Crop'] = json_encode($picture['Crop']);
                    $rlDb->rlAllowHTML = true;
                }

                foreach ($image_versions as $version => $options) {
                    $update['fields'][$options['db_field']] = $results[$version];
                }

                if ($rotated_picture) {
                    unlink($rotated_picture);
                }
                if ($cropped_picture) {
                    unlink($cropped_picture);
                }

                $rlHook->load(
                    'phpListingMediaUpdatePictureAfterResize',
                    $update,
                    $source_file,
                    $new_file_path,
                    $image_versions
                );

                $rlDb->update($update, 'listing_photos');

                return $results;
            } else {
                $rlDebug->logger('ListingMedia::updatePicture() - Thumbnail is unreadable after pictures resize');

                return false;
            }
        } else {
            $rlDebug->logger('ListingMedia::updatePicture() - Source file is unreadable');
            return false;
        }
    }
}
