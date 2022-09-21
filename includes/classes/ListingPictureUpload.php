<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTINGPICTUREUPLOAD.PHP
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

namespace Flynax\Classes;

use Flynax\Abstracts\AbstractFileUpload;
use Flynax\Utils\ListingMedia;

/**
 * @since 4.6.0
 */
class ListingPictureUpload extends AbstractFileUpload
{
    /**
     * Mapping of hook names used in methods
     * @var array
     */
    protected $hookNames = array(
        'createScaledImage' => 'phpUploadScaledImage',
    );

    /**
     * Listing ID
     * @var integer
     */
    private $listingID;

    /**
     * Class constructor
     * @param integer $listing_id - listing ID
     */
    public function __construct($listing_id = 0)
    {
        $this->options     = ListingMedia::getOptions();
        $this->listingID   = (int) ($listing_id ?: $_REQUEST['listing_id']);
        $this->accountInfo = $_SESSION['account'];

        $GLOBALS['rlHook']->load('phpUploadHandlerInit', $this->options);

        if (!$this->listingID) {
            die('PICTURE UPLOAD: No listing ID specified');
        }

        // Get listing owner ID
        // In case of ID = '-1' the process is also allowed because this means that singleStep
        // mode is enabled
        $owner_id = (int) $GLOBALS['rlDb']->getOne('Account_ID', "`ID` = {$this->listingID}", 'listings');

        if ($owner_id != '-1' && !$GLOBALS['rlAccount']->isLogin() && !$GLOBALS['rlAccount']->isAdmin()) {
            die('PICTURE UPLOAD: User is not logged in');
        }

        if ($this->accountInfo['ID'] != $owner_id && !$GLOBALS['rlAccount']->isAdmin()) {
            die('PICTURE UPLOAD: The listing with ' . $this->listingID . ' ID is not belong to logged in user');
        }
    }

    /**
     * Get listing pictures
     */
    protected function get()
    {
        $files = $GLOBALS['rlDb']->fetch(
            array('ID', 'Photo', 'Thumbnail', 'Description', 'Original', 'Type'),
            array('Listing_ID' => $this->listingID),
            'ORDER BY `Position`',
            null,
            'listing_photos');

        return array(
            'status'  => 'OK',
            'results' => ListingMedia::prepareURL($files),
            'count'   => count($files),
        );
    }

    /**
     * Upload file
     * @param  string $uploaded_file - source file path
     * @param  string $name          - uploaded file name
     * @param  string $size          - uploaded file size
     * @param  string $type          - uploaded file type
     * @param  string $error         - uploaded file error
     * @param  int    $index         - uploaded file index
     * @param  string $content_range - content range
     * @return array                 - file data
     */
    protected function uploadFile($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $type      = $type ?: '/' . $extension;

        $base_name = ListingMedia::buildName(
            $this->listingID,
            null,
            $index,
            $extension,
            $this->options['dir_name']
        );

        $file_name = str_replace('{postfix}', 'orig', $base_name);

        if ((bool) preg_match($this->options['picture_file_types'], $type)) {
            $media_type = 'picture';
        } elseif ((bool) preg_match($this->options['video_file_types'], $type)) {
            $media_type = 'video';
        }

        $file = array();
        $file['name'] = $file_name . '.' . $extension;
        $file['size'] = $this->fixTntegerOverflow(intval($size));
        $file['Type'] = $media_type;

        if ($this->validate($uploaded_file, $file, $error)) {
            $file_path = $this->options['upload_dir'] . $file['name'];
            $append_file = $content_range && is_file($file_path) && $file['size'] > $this->getFileSize($file_path);

            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    move_uploaded_file($uploaded_file, $file_path);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }

            // get file orientation
            if (function_exists('exif_read_data')) {
                $exif = exif_read_data($file_path);
                $orientation = $exif['Orientation'];
            }

            // insert to DB
            $insert = array(
                'Listing_ID'  => $this->listingID,
                'Position'    => $index + 1,
                'Original'    => $this->options['dir_name'] . $file['name'],
                'Description' => '',
                'Type'        => $media_type,
                'Status'      => 'active',
            );

            // create image versions
            if ($media_type == 'picture') {
                $file_size = $this->getFileSize($file_path, $append_file);

                if ($file_size === $file['size']) {
                    if ($this->options['orient_image']) {
                        $this->orientImage($file_path, $orientation);
                    }

                    foreach ($this->options['image_versions'] as $version => $options) {
                        $new_file_name = str_replace('_{postfix}', $options['prefix'], $base_name);
                        $new_file_name = $new_file_name . '.' . $GLOBALS['config']['output_image_format'];

                        if ($this->createScaledImage($file['name'], $new_file_name, $options, $version)) {
                            $insert[$options['db_field']] = $file[$options['db_field']] = $this->options['dir_name'] . $new_file_name;
                        }
                    }
                } elseif (!$content_range && $this->options['discard_aborted_uploads']) {
                    unlink($file_path);
                    $file['error'] = 'abort';
                }
            }

            $GLOBALS['rlHook']->load('phpUploadBeforeSaveData', $insert, $this->options['dir_name'], $file);

            // insert new photo
            $GLOBALS['rlDb']->insert($insert, 'listing_photos');

            // send data
            $file['ID'] = $GLOBALS['rlDb']->insertID();
            $file['primary'] = 0;
            $file['description'] = '';
            $file['Original'] = $this->options['dir_name'] . $file['name'];

            // update media data
            ListingMedia::updateMediaData($this->listingID);

            // prepare URLs
            ListingMedia::prepareURL($file, true);
        }

        return $file;
    }

    /**
     * Create directory
     */
    protected function createDirectory()
    {
        if ($existing_photo = $GLOBALS['rlDb']->getOne('Photo', "`Listing_ID` = '{$this->listingID}'", 'listing_photos')) {
            $exp_dir = explode('/', $existing_photo);

            if (count($exp_dir) > 1) {
                array_pop($exp_dir);

                $dir = RL_FILES . implode(RL_DS, $exp_dir) . RL_DS;
                $dir_name = implode('/', $exp_dir) . '/';
            }
        }

        if (!$dir) {
            $dir = RL_FILES . date('m-Y') . RL_DS . 'ad' . $this->listingID . RL_DS;
            $dir_name = date('m-Y') . '/ad' . $this->listingID . '/';
        }

        $url = RL_FILES_URL . $dir_name;

        $GLOBALS['rlHook']->load('phpUploadPost', $dir, $dir_name, $url);

        $GLOBALS['reefless']->rlMkdir($dir);

        // assign data
        $this->options['upload_dir'] = $dir;
        $this->options['upload_url'] = $url;
        $this->options['dir_name'] = $dir_name;
    }
}
