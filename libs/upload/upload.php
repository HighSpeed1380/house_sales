<?php
/*
 * jQuery File Upload Plugin PHP Example 5.14
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 * |regex_replace:'/\.(.*)$/':'_large.$1'
 */

use Flynax\Utils\ListingMedia;

$reefless->loadClass('Actions');
$reefless->loadClass('Resize');
$reefless->loadClass('Crop');
$reefless->loadClass('Listings');

class UploadHandler
{
    protected $options;
    // PHP File Upload error message codes:
    // http://php.net/manual/en/features.file-upload.errors.php
    protected $error_messages = array(
        1                     => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2                     => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3                     => 'The uploaded file was only partially uploaded',
        4                     => 'No file was uploaded',
        6                     => 'Missing a temporary folder',
        7                     => 'Failed to write file to disk',
        8                     => 'A PHP extension stopped the file upload',
        'post_max_size'       => 'The uploaded file exceeds the post_max_size directive in php.ini',
        'max_file_size'       => 'File is too big',
        'min_file_size'       => 'File is too small',
        'accept_file_types'   => 'Filetype not allowed',
        'max_number_of_files' => 'Maximum number of files exceeded',
        'max_width'           => 'Image exceeds maximum width',
        'min_width'           => 'Image requires a minimum width',
        'max_height'          => 'Image exceeds maximum height',
        'min_height'          => 'Image requires a minimum height',
    );

    public function __construct($options = null, $initialize = true)
    {
        global $config;

        $this->options = array(
            'script_url'                       => $this->get_full_url() . '/',
            'upload_dir'                       => RL_FILES,
            'upload_url'                       => RL_FILES_URL,
            'user_dirs'                        => false,
            'mkdir_mode'                       => 0755,
            'param_name'                       => 'files',
            // Set the following option to 'POST', if your server does not support
            // DELETE requests. This is a parameter sent to the client:
            'delete_type'                      => 'POST',
            'access_control_allow_origin'      => '*',
            'access_control_allow_credentials' => false,
            'access_control_allow_methods'     => array(
                'OPTIONS',
                'HEAD',
                'GET',
                'POST',
                'PUT',
                'DELETE',
            ),
            'access_control_allow_headers'     => array(
                'Content-Type',
                'Content-Range',
                'Content-Disposition',
                'Content-Description',
            ),
            // Enable to provide file downloads via GET requests to the PHP script:
            'download_via_php'                 => false,
            // Defines which files can be displayed inline when downloaded:
            'inline_file_types'                => '/\.(gif|jpe?g|png|webp)$/i',
            // Defines which files (based on their names) are accepted for upload:
            'accept_file_types'                => '/.+$/i',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size'                    => null,
            'min_file_size'                    => 1,
            // The maximum number of files for the upload directory:
            'max_number_of_files'              => null,
            // Image resolution restrictions:
            'max_width'                        => null,
            'max_height'                       => null,
            'min_width'                        => 1,
            'min_height'                       => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads'          => true,
            // Set to true to rotate images based on EXIF meta data, if available:
            'orient_image'                     => true,
            'image_versions'                   => array(
                'large'        => array(
                    'prefix'     => '_large',
                    'db_field'   => 'Photo',
                    'max_width'  => $config['pg_upload_large_width'] ?: 640,
                    'max_height' => $config['pg_upload_large_height'] ?: 480,
                    'watermark'  => (bool) $config['watermark_using'],
                    'force_crop' => (bool) $config['img_crop_module'],
                ),
                'thumbnail'    => array(
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
        if ($options) {
            $this->options = array_merge_recursive($this->options, $options);
        }
        if ($initialize) {
            $this->initialize();
        }
    }

    protected function initialize()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
            case 'HEAD':
                $this->head();
                break;
            case 'GET':
                $this->get();
                break;
            case 'POST':
                $this->post();
                break;
            default:
                header('HTTP/1.1 405 Method Not Allowed');
        }
    }

    protected function get_full_url()
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['HTTP_CF_VISITOR'] == '{"scheme":"https"}';
        return
        ($https ? 'https://' : 'http://') .
        (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : '') .
        (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] .
            ($https && $_SERVER['SERVER_PORT'] === 443 ||
                $_SERVER['SERVER_PORT'] === 80 ? '' : ':' . $_SERVER['SERVER_PORT']))) .
        substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }

    protected function get_user_id()
    {
        @session_start();
        return session_id();
    }

    protected function get_user_path()
    {
        if ($this->options['user_dirs']) {
            return $this->get_user_id() . '/';
        }
        return '';
    }

    protected function get_upload_path($file_name = null, $version = null)
    {
        $file_name = $file_name ? $file_name : '';
        $version_path = empty($version) ? '' : $version . '/';
        return $this->options['upload_dir'] . $this->get_user_path()
            . $version_path . $file_name;
    }

    protected function get_download_url($file_name, $version = null)
    {
        if ($this->options['download_via_php']) {
            $url = $this->options['script_url'] . '?file=' . rawurlencode($file_name);
            if ($version) {
                $url .= '&version=' . rawurlencode($version);
            }
            return $url . '&download=1';
        }
        $version_path = empty($version) ? '' : rawurlencode($version) . '/';
        return $this->options['upload_url'] . $this->get_user_path()
        . $version_path . rawurlencode($file_name);
    }

    public function set_file_delete_properties(&$file, $listing_id, $thumbnail)
    {
        global $upload_controller;

        $file->delete_url = $this->options['script_url'] . $upload_controller . '?file=' . rawurlencode($thumbnail) . '&id=' . $listing_id;
        $file->delete_type = $this->options['delete_type'];
        if ($file->delete_type !== 'DELETE') {
            $file->delete_url .= '&_method=DELETE';
        }
        if ($this->options['access_control_allow_credentials']) {
            $file->delete_with_credentials = true;
        }
    }

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    protected function fix_integer_overflow($size)
    {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    protected function get_file_size($file_path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) {
            clearstatcache();
        }
        return $this->fix_integer_overflow(filesize($file_path));
    }

    protected function is_valid_file_object($file_name)
    {
        $file_path = $this->get_upload_path($file_name);
        if (is_file($file_path) && $file_name[0] !== '.') {
            return true;
        }
        return false;
    }

    public function get_file_object($photo = false)
    {
        global $config;

        if ($photo) {
            $files_url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['HTTP_CF_VISITOR'] == '{"scheme":"https"}' ? str_replace('http://', 'https://', RL_FILES_URL) : RL_FILES_URL;

            $file = new stdClass();
            $file->id = $photo['ID'];
            $file->listing_id = $photo['Listing_ID'];
            $file->description = $photo['Description'];
            $file->is_crop = $photo['Original'] && $config['img_crop_interface'] ? 1 : 0;
            $file->primary = $photo['Type'] == 'main' ? 1 : 0;
            $file->type = $info['mime'];
            $file->name = $photo['Photo'];
            $file->size = filesize(RL_FILES . $photo['Photo']);
            $file->url = $files_url . $photo['Photo'];
            $file->thumbnail_url = $files_url . $photo['Thumbnail'];
            $file->original = $files_url . $photo['Original'];

            $this->set_file_delete_properties($file, $photo['Listing_ID'], $photo['Thumbnail']);
            return $file;
        }
        return;
    }

    protected function get_file_objects($iteration_method = 'get_file_object')
    {
        global $listing_id;

        $photos = $GLOBALS['rlDb']->fetch(
            '*',
            array('Listing_ID' => $listing_id, 'Type' => 'picture'),
            "ORDER BY `Position`",
            null,
            'listing_photos'
        );
        if (!$photos) {
            return false;
        }

        return array_values(array_filter(array_map(array($this, $iteration_method), $photos)));
    }

    protected function count_file_objects()
    {
        return count($this->get_file_objects('is_valid_file_object'));
    }

    public function create_scaled_image($file_name, $new_file_name, $options, $version)
    {
        global $rlResize, $rlCrop, $config, $rlHook;

        $file_path = $this->options['upload_dir'] . $file_name;
        $new_file_path = $this->options['upload_dir'] . $new_file_name;

        $rlHook->load('phpUploadScaledImage', $file_name, $new_file_name, $this->options['upload_dir'], $options, $version);

        if ($options['force_crop']) {
            $rlCrop->loadImage($file_path);
            $rlCrop->cropBySize($options['max_width'], $options['max_height'], ccCENTRE);
            $rlCrop->saveImage($new_file_path, $config['img_quality']);
            $rlCrop->flushImages();
        }

        $rlResize->resize(
            $options['force_crop'] ? $new_file_path : $file_path,
            $new_file_path,
            'C',
            array($options['max_width'], $options['max_height']),
            $options['force_crop'],
            $options['watermark']
        );

        return true;
    }

    protected function get_error_message($error)
    {
        return array_key_exists($error, $this->error_messages) ?
        $this->error_messages[$error] : $error;
    }

    public function get_config_bytes($val)
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
        return $this->fix_integer_overflow($val);
    }

    protected function validate($uploaded_file, $file, $error, $index)
    {
        if ($error) {
            $file->error = $this->get_error_message($error);
            return false;
        }
        $content_length = $this->fix_integer_overflow(intval($_SERVER['CONTENT_LENGTH']));
        if ($content_length > $this->get_config_bytes(ini_get('post_max_size'))) {
            $file->error = $this->get_error_message('post_max_size');
            return false;
        }
        if (!preg_match($this->options['accept_file_types'], $file->name)) {
            $file->error = $this->get_error_message('accept_file_types');
            return false;
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = $this->get_file_size($uploaded_file);
        } else {
            $file_size = $content_length;
        }
        if ($this->options['max_file_size'] && (
            $file_size > $this->options['max_file_size'] ||
            $file->size > $this->options['max_file_size'])
        ) {
            $file->error = $this->get_error_message('max_file_size');
            return false;
        }
        if ($this->options['min_file_size'] &&
            $file_size < $this->options['min_file_size']) {
            $file->error = $this->get_error_message('min_file_size');
            return false;
        }
        if (is_int($this->options['max_number_of_files']) && (
            $this->count_file_objects() >= $this->options['max_number_of_files'])
        ) {
            $file->error = $this->get_error_message('max_number_of_files');
            return false;
        }
        list($img_width, $img_height) = @getimagesize($uploaded_file);
        if (is_int($img_width)) {
            if ($this->options['max_width'] && $img_width > $this->options['max_width']) {
                $file->error = $this->get_error_message('max_width');
                return false;
            }
            if ($this->options['max_height'] && $img_height > $this->options['max_height']) {
                $file->error = $this->get_error_message('max_height');
                return false;
            }
            if ($this->options['min_width'] && $img_width < $this->options['min_width']) {
                $file->error = $this->get_error_message('min_width');
                return false;
            }
            if ($this->options['min_height'] && $img_height < $this->options['min_height']) {
                $file->error = $this->get_error_message('min_height');
                return false;
            }
        }
        return true;
    }

    protected function upcount_name_callback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';
        return ' (' . $index . ')' . $ext;
    }

    protected function upcount_name($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcount_name_callback'),
            $name,
            1
        );
    }

    protected function trim_file_name($name, $type, $index, $content_range)
    {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $file_name = trim(basename(stripslashes($name)), ".\x00..\x20");
        // Add missing file extension for known image types:
        if (strpos($file_name, '.') === false &&
            preg_match('/^image\/(gif|jpe?g|png|webp)/', $type, $matches)) {
            $file_name .= '.' . $matches[1];
        }
        while (is_dir($this->get_upload_path($file_name))) {
            $file_name = $this->upcount_name($file_name);
        }
        $uploaded_bytes = $this->fix_integer_overflow(intval($content_range[1]));
        while (is_file($this->get_upload_path($file_name))) {
            if ($uploaded_bytes === $this->get_file_size(
                $this->get_upload_path($file_name))) {
                break;
            }
            $file_name = $this->upcount_name($file_name);
        }
        return $file_name;
    }

    protected function handle_form_data($file, $index)
    {
        // Handle form data, e.g. $_REQUEST['description'][$index]
    }

    protected function orient_image($file_path)
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $orientation = (int) $headers['File-Orientation'];
        } elseif ($_SERVER['HTTP_FILE_ORIENTATION']) {
            $orientation = (int) $_SERVER['HTTP_FILE_ORIENTATION'];
        }

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
        // Free up memory (imagedestroy does not delete files):
        @imagedestroy($image);
        return $success;
    }

    public function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null, $content_range = null)
    {
        global $listing_id, $rlDb, $rlActions, $config, $rlHook;

        // deep checking: get file mime
        $uf_info = getimagesize($uploaded_file);
        if (!$uf_info || !preg_match('/^image\/(gif|jpe?g|png|webp)$/i', $uf_info['mime'])) {
            die("Filetype not allowed");
        }

        /* file directories handler */
        $cur_photo = $rlDb->getOne('Photo', "`Listing_ID` = '{$listing_id}'", 'listing_photos');
        if ($cur_photo) {
            $exp_dir = explode('/', $cur_photo);
            if (count($exp_dir) > 1) {
                array_pop($exp_dir);
                $dir = RL_FILES . implode(RL_DS, $exp_dir) . RL_DS;
                $dir_name = implode('/', $exp_dir) . '/';
            }
        }

        if (!$dir) {
            $dir = RL_FILES . date('m-Y') . RL_DS . 'ad' . $listing_id . RL_DS;
            $dir_name = date('m-Y') . '/ad' . $listing_id . '/';
        }

        $files_url = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['HTTP_CF_VISITOR'] == '{"scheme":"https"}' ? str_replace('http://', 'https://', RL_FILES_URL) : RL_FILES_URL;
        $url = $files_url . $dir_name;
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        $GLOBALS['reefless']->rlMkdir($dir);

        $rlHook->load('phpUploadPost');

        $this->options['upload_dir'] = $dir;
        $this->options['upload_url'] = $url;
        $this->options['dir_name']   = $dir_name;

        $baseName = ListingMedia::buildName($listing_id, null, $index, $ext, $this->options['dir_name']);

        $file = new stdClass();
        $file->name = str_replace('{postfix}', 'orig', $baseName) . '.' . pathinfo($name, PATHINFO_EXTENSION);
        $file->size = $this->fix_integer_overflow(intval($size));
        $file->type = $type;

        if ($this->validate($uploaded_file, $file, $error, $index)) {
            $this->handle_form_data($file, $index);
            $upload_dir = $dir;
            $file_path = $upload_dir . $file->name;
            $append_file = $content_range && is_file($file_path) && $file->size > $this->get_file_size($file_path);
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
            $file_size = $this->get_file_size($file_path, $append_file);
            if ($file_size === $file->size) {
                if ($this->options['orient_image']) {
                    $this->orient_image($file_path);
                }

                if ($config['img_crop_interface']) {
                    $GLOBALS['rlCrop']->loadImage($file_path);
                    $GLOBALS['rlCrop']->cropBySize($uf_info[0], $uf_info[1], ccCENTER);
                    $GLOBALS['rlCrop']->saveImage($file_path, $config['img_quality']);
                    $GLOBALS['rlCrop']->flushImages();
                }

                $file->url = $files_url . $file->name;
                foreach ($this->options['image_versions'] as $version => $options) {
                    if ($version == 'thumbnail_x2' && !$config['thumbnails_x2']) {
                        continue;
                    }

                    $new = str_replace('_{postfix}', $options['prefix'], $baseName) . '.' . $config['output_image_format'];
                    if ($this->create_scaled_image($file->name, $new, $options, $version)) {
                        if (!empty($version)) {
                            $file->$version = $new;
                        } else {
                            $file_size = $this->get_file_size($file_path, true);
                        }
                    }
                }
            } else if (!$content_range && $this->options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $this->set_file_delete_properties($file, $listing_id, $dir_name . $file->thumbnail);

            /* define picture position */
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                $position = $headers['File-Index'];
            } elseif ($_SERVER['HTTP_FILE_INDEX']) {
                $position = (int) $_SERVER['HTTP_FILE_INDEX'];
            } else {
                $max_pos = $rlDb->getRow(
                    "SELECT MAX(`Position`) AS `Max` FROM `" . RL_DBPREFIX . "listing_photos`
                     WHERE `Listing_ID` = {$listing_id}"
                );
                $position = $max_pos['Max'] + 1;
            }

            // insert to DB
            $insert = array(
                'Listing_ID'  => $listing_id,
                'Position'    => $position,
                'Photo'       => $dir_name . $file->large,
                'Thumbnail'   => $dir_name . $file->thumbnail,
                'Original'    => $dir_name . $file->name,
                'Description' => '',
                'Type'        => 'picture',
                'Status'      => 'active',
            );

            if ($config['thumbnails_x2']) {
                $insert['Thumbnail_x2'] = $dir_name . $file->thumbnail_x2;
            }

            $rlHook->load('phpUploadBeforeSaveData', $insert, $dir_name, $file); // used in version above 4.1.0

            /* insert new photo */
            $rlActions->insertOne($insert, 'listing_photos');

            /* send data */
            $file->id = $rlDb->insertID();
            $file->listing_id = $listing_id;
            $file->primary = 0;
            $file->is_crop = $config['img_crop_interface'] ? 1 : 0;
            $file->description = '';
            $file->original = $files_url . $dir_name . $file->name;
            $file->thumbnail_url = $this->options['upload_url'] . $file->thumbnail;

            /* update photos data */
            $GLOBALS['rlListings']->updatePhotoData($listing_id);
        }

        return $file;
    }

    protected function generate_response($content, $print_response = true)
    {
        if ($print_response) {
            $json = json_encode($content);
            $redirect = isset($_REQUEST['redirect']) ?
            stripslashes($_REQUEST['redirect']) : null;
            if ($redirect) {
                header('Location: ' . sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if (isset($_SERVER['HTTP_CONTENT_RANGE']) && is_array($content) &&
                is_object($content[0]) && $content[0]->size) {
                header('Range: 0-' . ($this->fix_integer_overflow(intval($content[0]->size)) - 1));
            }
            echo $json;
        }
        return $content;
    }

    protected function get_version_param()
    {
        return isset($_GET['version']) ? basename(stripslashes($_GET['version'])) : null;
    }

    protected function get_file_name_param()
    {
        return isset($_GET['file']) ? basename(stripslashes($_GET['file'])) : null;
    }

    protected function get_file_type($file_path)
    {
        switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
            case 'jpeg':
            case 'jpg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return '';
        }
    }

    protected function download()
    {
        if (!$this->options['download_via_php']) {
            header('HTTP/1.1 403 Forbidden');
            return;
        }
        $file_name = $this->get_file_name_param();
        if ($this->is_valid_file_object($file_name)) {
            $file_path = $this->get_upload_path($file_name, $this->get_version_param());
            if (is_file($file_path)) {
                if (!preg_match($this->options['inline_file_types'], $file_name)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . $file_name . '"');
                    header('Content-Transfer-Encoding: binary');
                } else {
                    // Prevent Internet Explorer from MIME-sniffing the content-type:
                    header('X-Content-Type-Options: nosniff');
                    header('Content-Type: ' . $this->get_file_type($file_path));
                    header('Content-Disposition: inline; filename="' . $file_name . '"');
                }
                header('Content-Length: ' . $this->get_file_size($file_path));
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file_path)));
                readfile($file_path);
            }
        }
    }

    protected function send_content_type_header()
    {
        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
    }

    protected function send_access_control_headers()
    {
        header('Access-Control-Allow-Origin: ' . $this->options['access_control_allow_origin']);
        header('Access-Control-Allow-Credentials: '
            . ($this->options['access_control_allow_credentials'] ? 'true' : 'false'));
        header('Access-Control-Allow-Methods: '
            . implode(', ', $this->options['access_control_allow_methods']));
        header('Access-Control-Allow-Headers: '
            . implode(', ', $this->options['access_control_allow_headers']));
    }

    public function head()
    {
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Disposition: inline; filename="files.json"');
        // Prevent Internet Explorer from MIME-sniffing the content-type:
        header('X-Content-Type-Options: nosniff');
        if ($this->options['access_control_allow_origin']) {
            $this->send_access_control_headers();
        }
        $this->send_content_type_header();
    }

    public function get($print_response = true)
    {
        if ($print_response && isset($_GET['download'])) {
            return $this->download();
        }
        $file_name = $this->get_file_name_param();
        if ($file_name) {
            $info = $this->get_file_object($file_name);
        } else {
            $info = $this->get_file_objects();
        }
        return $this->generate_response($info, $print_response);
    }

    public function post($print_response = true)
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return $this->delete($print_response);
        }
        $upload = isset($_FILES[$this->options['param_name']]) ?
        $_FILES[$this->options['param_name']] : null;
        // Parse the Content-Disposition header, if available:
        $file_name = isset($_SERVER['HTTP_CONTENT_DISPOSITION']) ?
        rawurldecode(preg_replace(
            '/(^[^"]+")|("$)/',
            '',
            $_SERVER['HTTP_CONTENT_DISPOSITION']
        )) : null;
        $file_type = isset($_SERVER['HTTP_CONTENT_DESCRIPTION']) ?
        $_SERVER['HTTP_CONTENT_DESCRIPTION'] : null;
        // Parse the Content-Range header, which has the following form:
        // Content-Range: bytes 0-524287/2000000
        $content_range = isset($_SERVER['HTTP_CONTENT_RANGE']) ?
        preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null;
        $size = $content_range ? $content_range[3] : null;
        $info = array();
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $file_type ? $file_type : $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
            }
        } else {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $info[] = $this->handle_file_upload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                $file_name ? $file_name : (isset($upload['name']) ?
                    $upload['name'] : null),
                $size ? $size : (isset($upload['size']) ?
                    $upload['size'] : $_SERVER['CONTENT_LENGTH']),
                $file_type ? $file_type : (isset($upload['type']) ?
                    $upload['type'] : $_SERVER['CONTENT_TYPE']),
                isset($upload['error']) ? $upload['error'] : null,
                null,
                $content_range
            );
        }
        return $this->generate_response($info, $print_response);
    }

    public function delete($print_response = true)
    {
        global $listing_id, $rlHook, $config, $rlDb;

        /* photos required option deny deleting on add photo page */
        if ($_SESSION['page_info']['current'] == 'add_photo') {
            $sql = "SELECT `T3`.`Photo_required` FROM `{db_prefix}listings` AS `T1` ";
            $sql .="JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
            $sql .="JOIN `{db_prefix}listing_types` AS `T3` ON `T3`.`Key` = `T2`.`Type` ";
            $sql .="WHERE `T1`.`ID` = {$listing_id}";
            $photo_required = $rlDb->getRow($sql, "Photo_required");

            if ($photo_required) {
                $sql = "SELECT COUNT(*) as `count` FROM `{db_prefix}listing_photos` ";
                $sql .="WHERE `Listing_ID` = {$listing_id} AND `Status` = 'active'";
                $count_photos = $rlDb->getRow($sql, 'count');

                if ($count_photos == 1) {
                    return false;
                }
            }
        }
        /* photos required option deny deleting on add photo page end */

        $id = (int) $_REQUEST['id'];
        $file_name = isset($_REQUEST['file']) ? stripslashes($_REQUEST['file']) : null;
        $file_path = $this->options['upload_dir'] . $file_name;
        $fetch_fields = array('Thumbnail', 'Photo', 'Original');

        if ($config['thumbnails_x2']) {
            $fetch_fields[] = 'Thumbnail_x2';
        }

        $rlHook->load('phpUploadPrepareDelete', $fetch_fields); // used in version above 4.1.0

        $success = $id == $listing_id;
        $info = $rlDb->fetch(
            $fetch_fields,
            array('Listing_ID' => $listing_id, 'Thumbnail' => $file_name),
            null,
            1,
            'listing_photos',
            'row'
        );

        if ($success && $info) {
            unlink($file_path);

            $original_dir = $this->options['upload_dir'] . $info['Original'];
            unlink($original_dir);

            $photo_dir = $this->options['upload_dir'] . $info['Photo'];
            unlink($photo_dir);

            if ($config['thumbnails_x2']) {
                $thumbnails_x2_dir = $this->options['upload_dir'] . $info['Thumbnail_x2'];
                unlink($thumbnails_x2_dir);
            }

            $rlHook->load('phpUploadDelete', $info, $this->options); // moved & updated in version above 4.1.0

            /* remove from DB */
            $rlDb->query(
                "DELETE FROM `" . RL_DBPREFIX . "listing_photos`
                 WHERE `Listing_ID` = '{$listing_id}' AND `Thumbnail` = '{$file_name}' LIMIT 1"
            );

            /* update photos data */
            $GLOBALS['rlListings']->updatePhotoData($listing_id);

            /* remove related directory if it is empty */
            $del_dir = explode('/', $photo_dir);
            array_pop($del_dir);
            $GLOBALS['reefless']->deleteDirectory(implode(RL_DS, $del_dir) . RL_DS, true);
        }

        return $this->generate_response($success, $print_response);
    }
}

$options = null;
$initialize = true;

$rlHook->load('phpUploadHandlerInit', $options, $initialize); // used in version above 4.1.0

$upload_handler = new UploadHandler($options, $initialize);
