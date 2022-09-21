<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PROFILETHUMBNAILUPLOAD.PHP
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
use Flynax\Utils\Util;
use Flynax\Utils\Profile;

/**
 * @since 4.6.1
 */
class ProfileThumbnailUpload extends AbstractFileUpload
{
    /**
     * Mapping of hook names useed in methods
     * @var array
     */
    protected $hookNames = array(
        'createScaledImage' => 'uploadProfileScaledImage',
    );

    /**
     * Base account media dir name
     * @var string
     */
    private $mediaDirName = 'account-media';

    /**
     * Maximum length of image path
     *
     * @since 4.7.0
     * @var   int
     */
    private $maxLengthPath = 80;

    /**
     * Class constructor
     *
     * @since 4.6.2 - Added $account_info parameter
     *
     * @param array $account_info
     */
    public function __construct(&$account_info = array())
    {
        global $config;

        if ($account_info) {
            $this->accountInfo = &$account_info;
        } else {
            // update thumbnail dimensions in current session of user
            if ($_SESSION['account']['ID']) {
                $thumb_data = $GLOBALS['rlDb']->getRow("
                    SELECT `T2`.`Thumb_width`, `T2`.`Thumb_height`
                    FROM `{db_prefix}accounts` AS `T1`
                    LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key`
                    WHERE `T1`.`ID` = {$_SESSION['account']['ID']}
                ");

                $_SESSION['account']['Thumb_width'] = $thumb_data['Thumb_width'] ?: $_SESSION['account']['Thumb_width'];
                $_SESSION['account']['Thumb_height'] = $thumb_data['Thumb_height'] ?: $_SESSION['account']['Thumb_height'];
            }

            $this->accountInfo = $_SESSION['account'];
        }

        $this->options = array(
            // Defines which files (based on their names) are accepted for upload
            'accept_file_types'       => '/(\.|\/)(gif|jpe?g|png|webp)$/i',
            // Extensions string to appear in error messages
            'accept_file_ext'         => 'gif/jpeg/png/webp',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting
            'max_file_size'           => null,
            'min_file_size'           => 1,
            'param_name'              => 'thumbnail',
            // Image resolution restrictions:
            'max_width'               => null,
            'max_height'              => null,
            'min_width'               => 1,
            'min_height'              => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads' => true,
            // Set to true to rotate images based on EXIF meta data, if available:
            'orient_image'            => true,
            // Image version to create
            'image_versions'          => array(
                'thumbnail' => array(
                    'prefix'     => 'thumbnail',
                    'db_field'   => 'Photo',
                    'max_width'  => $this->accountInfo['Thumb_width'] ?: 110,
                    'max_height' => $this->accountInfo['Thumb_height'] ?: 100,
                    'force_crop' => $config['img_account_crop_thumbnail'],
                    'watermark'  => false,
                ),
                'thumbnail_x2' => array(
                    'prefix'     => 'thumbnail-x2',
                    'db_field'   => 'Photo_x2',
                    'max_width'  => $this->accountInfo['Thumb_width'] ? $this->accountInfo['Thumb_width'] * 2 : 220,
                    'max_height' => $this->accountInfo['Thumb_height'] ? $this->accountInfo['Thumb_height'] * 2 : 200,
                    'force_crop' => $config['img_account_crop_thumbnail'],
                    'watermark'  => false,
                ),
            ),
        );

        if (!$GLOBALS['config']['thumbnails_x2']) {
            unset($this->options['image_versions']['thumbnail_x2']);
        }

        $GLOBALS['rlHook']->load('phpProfileThumbnailUploadHandlerInit', $this->options);

        if (!$this->accountInfo && !$GLOBALS['rlAccount']->isAdmin()) {
            die('PROFILE PICTURE UPLOAD: No listing account data available');
        }
    }

    /**
     * Upload file
     * @param  string $uploaded_file - source file path
     * @param  string $name          - uploaded file name
     * @param  string $size          - uploaded file size
     * @param  string $type          - uploaded file type
     * @param  string $error         - uploaded file error
     * @param  iniger $index         - uploaded file index
     * @param  string $content_range - content range
     * @return array                 - file data
     */
    protected function uploadFile($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $file_name = 'original-' . $this->options['rand'];
        $file_path = $this->options['upload_dir'] . $file_name  . '.' . $extension;

        $file = array();
        $file['name'] = $file_name . '.' . $extension;
        $file['size'] = $this->fixTntegerOverflow(intval($size));
        $file['type'] = $type;
        $file['Photo_original'] = $this->options['dir_name'] . $file['name'];

        if ($this->validate($uploaded_file, $file, $error)) {
            $append_file = $content_range && is_file($file_path) && $file['size'] > $this->getFileSize($file_path);

            $data['Photo_original'] = $this->options['dir_name'] . $file['name'];

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

            // create thumbnail
            $file_size = $this->getFileSize($file_path, $append_file);

            if ($file_size === $file['size']) {
                if ($this->options['orient_image']) {
                    $this->orientImage($file_path, $orientation);
                }

                foreach ($this->options['image_versions'] as $version => $options) {
                    $new_file_name = str_replace('original', $options['prefix'], $file_name);
                    $new_file_name .= '.' . $GLOBALS['config']['output_image_format'];

                    if ($this->createScaledImage($file['name'], $new_file_name, $options, $version)) {
                        $data[$options['db_field']] = $file[$options['db_field']] = $this->options['dir_name'] . $new_file_name;
                    }
                }
            } elseif (!$content_range && $this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file['error'] = 'abort';
            }

            Profile::updateData($this->accountInfo['ID'], $data);

            /**
             * @since 4.7.0
             */
            $GLOBALS['rlHook']->load('ajaxRequestProfileThumbnailAfterUpdate', $this->options['dir_name'], $file);

            foreach ($data as $file_index => $new_file_name) {
                // remove previous photos
                if ($this->accountInfo[$file_index]) {
                    unlink(RL_FILES . $this->accountInfo[$file_index]);
                }

                if (!$GLOBALS['rlAccount']->isAdmin()) {
                    $_SESSION['account'][$file_index] = $new_file_name;
                }

                $this->accountInfo[$file_index] = $new_file_name;
            }
        }

        return $file;
    }

    /**
     * Create directory
     */
    protected function createDirectory()
    {
        if ($this->accountInfo['Photo']
            && false !== strpos($this->accountInfo['Photo'], $this->mediaDirName)
        ) {
            $exp_dir = explode('/', $this->accountInfo['Photo']);

            if (count($exp_dir) > 1) {
                array_pop($exp_dir);

                $upload_dir = RL_FILES . implode(RL_DS, $exp_dir) . RL_DS;
                $dir_name   = implode('/', $exp_dir) . '/';
            }
        }

        $new_dir_name = $this->buildName();

        if (!$upload_dir || $new_dir_name != $dir_name) {
            $upload_dir = RL_FILES . str_replace('/', RL_DS, $new_dir_name);
            $dir_name   = $new_dir_name;
        }

        $url = RL_FILES_URL . $dir_name;

        $GLOBALS['rlHook']->load('phpProfileUploadPost', $upload_dir, $dir_name, $url);

        $GLOBALS['reefless']->rlMkdir($upload_dir);

        // assign data
        $this->options['upload_dir'] = $upload_dir;
        $this->options['upload_url'] = $url;
        $this->options['dir_name']   = $dir_name;
    }

    /**
     * Build dir name
     *
     * @since 4.6.2 - Added $id parameter, changed format of returned value to Dir name only
     *
     * @param  int    $id - ID of account
     * @return string     - Dir name
     */
    public function buildName($id = 0)
    {
        $id = (int) $id;

        if ($id) {
            $GLOBALS['reefless']->loadClass('Account');
            $full_name = $GLOBALS['rlAccount']->getProfile($id)['Full_name'];
        } else {
            $full_name = $this->accountInfo['Full_name'] ?: 'account_' . $this->accountInfo['ID'];
        }

        $account_id        = $id ?: $this->accountInfo['ID'];
        $account_id_length = strlen($account_id) + 1;

        $dir_name = $GLOBALS['rlValid']->str2path($full_name) . '-' . $account_id;
        $dir_name = $this->mediaDirName . '/' . $dir_name . '/';

        // generate RAND number in names of photos
        if ($this->accountInfo && $this->accountInfo['Photo']) {
            // get old rand number
            $old_rand = substr(explode('.', $this->accountInfo['Photo'])[0], -3);
            $this->options['rand'] = Util::getRandomNumber(3, $old_rand);
        } else {
            $this->options['rand'] = Util::getRandomNumber(3);
        }

        // get extension with maximum of length
        $dot_length = 1;
        $max_length_ext = max(array_map('strlen', explode('/', $this->options['accept_file_ext']))) + $dot_length;

        // get count of extra characters in name
        $total_length = strlen($dir_name . 'thumbnail-x2-') + strlen($this->options['rand']) + $max_length_ext;
        $extra_length = $total_length > $this->maxLengthPath ? $this->maxLengthPath - $total_length : 0;

        if ($extra_length) {
            // cut extra characters in name of folder
            $dir_name = preg_replace(
                '/\s+?(\S+)?$/',
                '',
                substr($dir_name, 0, ($extra_length - $account_id_length) - 1)
            );

            $dir_name .= '-' . $account_id . '/';
        }

        return $dir_name;
    }
}
