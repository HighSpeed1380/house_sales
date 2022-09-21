<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ABSTRACTFILEUPLOAD.PHP
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

namespace Flynax\Abstracts;

/**
 * @since 4.6.1
 */
abstract class AbstractFileUpload
{
    /**
     * Error messages
     * @var array
     */
    protected $error_messages = array();

    /**
     * Upload options
     * @var array
     */
    public $options;

    /**
     * Create directory
     */
    protected function createDirectory()
    {}

    /**
     * Initialize the upload process
     * @return array - method related data
     */
    public function init()
    {
        global $lang;

        $this->error_messages = array(
            1 => $lang['error_maxFileSize'], // max_file_size (php.ini) limit error
            2 => $lang['error_maxFileSize'], // max_file_size (class var) limit error
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'A PHP extension stopped the file upload',

            'post_max_size'       => 'The uploaded file exceeds the post_max_size directive in php.ini',
            'max_file_size'       => 'File is too big',
            'min_file_size'       => 'File is too small',
            'accept_file_types'   => $lang['error_wrong_file_type'],
            'max_number_of_files' => 'Maximum number of files exceeded',
            'max_width'           => 'Image exceeds maximum width',
            'min_width'           => 'Image requires a minimum width',
            'max_height'          => 'Image exceeds maximum height',
            'min_height'          => 'Image requires a minimum height',
            'abort'               => 'File upload aborted',
        );

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                return $this->get();
                break;
            case 'POST':
                return $this->post();
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    /**
     * Get method
     */
    protected function get()
    {}

    /**
     * Post method
     * @return array - file data
     */
    protected function post()
    {
        global $reefless;

        // get files data
        $upload = isset($_FILES[$this->options['param_name']])
        ? $_FILES[$this->options['param_name']]
        : null;

        // parse the Content-Disposition header
        $file_name = isset($_SERVER['HTTP_CONTENT_DISPOSITION'])
        ? rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', $_SERVER['HTTP_CONTENT_DISPOSITION']))
        : null;

        // parse the Content-Description header
        $file_type = isset($_SERVER['HTTP_CONTENT_DESCRIPTION'])
        ? $_SERVER['HTTP_CONTENT_DESCRIPTION']
        : null;

        // parse the Content-Range header, which has the following form:
        // content-Range: bytes 0-524287/2000000
        $content_range = isset($_SERVER['HTTP_CONTENT_RANGE'])
        ? preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE'])
        : null;

        // get size
        $size = $content_range ? $content_range[3] : null;

        // upload files
        if ($upload && $upload['tmp_name']) {
            // create directory
            $this->createDirectory();

            // Load Classes
            $reefless->loadClass('Resize');
            $reefless->loadClass('Crop');

            // upload
            if (is_array($upload['tmp_name'])) {
                foreach ($upload['tmp_name'] as $index => $value) {
                    $info[] = $this->uploadFile(
                        $upload['tmp_name'][$index],
                        $file_name ?: $upload['name'][$index],
                        $size ?: $upload['size'][$index],
                        $file_type ?: $upload['type'][$index],
                        $upload['error'][$index],
                        isset($_REQUEST['index']) ? $_REQUEST['index'] : $index,
                        $content_range
                    );
                }
            } else {
                $info[] = $this->uploadFile(
                    $upload['tmp_name'],
                    $file_name ?: $upload['name'],
                    $size ?: $upload['size'],
                    $file_type ?: $upload['type'],
                    $upload['error'],
                    0,
                    $content_range
                );
            }
        } else {
            return \Flynax\Utils\Util::errorResponse('FILE UPLOAD: there are not tmp_name data available');
        }

        return array($this->options['param_name'] => $info);
    }

    /**
     * Get error message by error code
     * @param  mixed $error - error code
     * @return string       - error message
     */
    protected function getRrrorMessage($error)
    {
        return array_key_exists($error, $this->error_messages) ? $this->error_messages[$error] : $error;
    }

    /**
     * Convert to bytes
     * @param  string $val - number string
     * @return integer     - converted value
     */
    protected function getConfigBytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);

        switch ($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $this->fixTntegerOverflow($val);
    }

    /**
     * Get file size
     * @param  string  $file_path        - file path
     * @param  boolean $clear_stat_cache - clear stat cache
     * @return integer                   - file size
     */
    protected function getFileSize($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) {
            clearstatcache();
        }

        return $this->fixTntegerOverflow(filesize($file_path));
    }

    /**
     * Create scaled image
     * @param  string  $file_name     - source file name
     * @param  string  $new_file_name - new file name
     * @param  array   $options       - resize options
     * @param  string  $version       - version key
     * @return boolean                - success
     */
    protected function createScaledImage($file_name, $new_file_name, $options, $version)
    {
        global $rlCrop, $config;

        $file_path = $this->options['upload_dir'] . $file_name;
        $new_file_path = $this->options['upload_dir'] . $new_file_name;

        $GLOBALS['rlHook']->load($this->hookNames['createScaledImage'], $file_name, $new_file_name, $this, $options, $version);

        if ($options['force_crop']) {
            $rlCrop->loadImage($file_path);
            $rlCrop->cropBySize($options['max_width'], $options['max_height'], ccCENTRE);
            $rlCrop->saveImage($new_file_path, $config['img_quality']);
            $rlCrop->flushImages();
        }

        $GLOBALS['rlResize']->resize(
            $options['force_crop'] ? $new_file_path : $file_path,
            $new_file_path,
            'C',
            array($options['max_width'], $options['max_height']),
            $options['force_crop'],
            $options['watermark']
        );

        return true;
    }

    /**
     * Validate file
     * @param  string $uploaded_file - file path
     * @param  array  &$file         - file data
     * @param  string $error         - system error
     * @return [type]                [description]
     */
    protected function validate($uploaded_file, &$file, $error)
    {
        if ($error) {
            $file['error'] = $this->getRrrorMessage($error);
            return false;
        }

        $content_length = $this->fixTntegerOverflow(intval($_SERVER['CONTENT_LENGTH']));
        if ($content_length > $this->getConfigBytes(ini_get('post_max_size'))) {
            $file['error'] = $this->getRrrorMessage('post_max_size');
            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file['name'])) {
            $file['error'] = str_replace(
                array('{ext}', '{types}'),
                array("\"{$file['type']}\"", "\"{$this->options['accept_file_ext']}\""),
                $this->getRrrorMessage('accept_file_types')
            );
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->getFileSize($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size']
            && ($file_size > $this->options['max_file_size'] || $file['size'] > $this->options['max_file_size'])
        ) {
            $file['error'] = $this->getRrrorMessage('max_file_size');
            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            $file['error'] = $this->getRrrorMessage('min_file_size');
            return false;
        }

        list($img_width, $img_height) = @getimagesize($uploaded_file);
        if (is_int($img_width)) {
            if ($this->options['min_width'] && $img_width < $this->options['min_width']) {
                $file['error'] = $this->getRrrorMessage('min_width');
                return false;
            }
            if ($this->options['min_height'] && $img_height < $this->options['min_height']) {
                $file['error'] = $this->getRrrorMessage('min_height');
                return false;
            }
        }
        return true;
    }

    /**
     * Fix file orientation
     * @param  string $file_path   - file path
     * @param  ineger $orientation - orientation index
     * @return bool                - is successed fixed
     */
    protected function orientImage($file_path, $orientation)
    {
        if ($orientation <= 0) {
            return false;
        }

        $image = @imagecreatefromjpeg($file_path);
        switch ($orientation) {
            case 3:
                $image = @imagerotate($image, 180, 0);
                break;
            case 6:
                $image = @imagerotate($image, 270, 0);
                break;
            case 8:
                $image = @imagerotate($image, 90, 0);
                break;
            default:
                return false;
        }

        $success = imagejpeg($image, $file_path);
        @imagedestroy($image);

        return $success;
    }

    /**
     * Fix integer overflow description
     * @param  integer $size - size
     * @return integer       - fixed size
     */
    protected function fixTntegerOverflow($size)
    {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }
}
