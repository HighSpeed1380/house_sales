<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLRESIZE.CLASS.PHP
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

use Intervention\Image\ImageManagerStatic as Image;
use Flynax\Utils\Valid;

class rlResize
{
    public $strOriginalImagePath;
    public $strResizedImagePath;
    public $arrOriginalDetails;
    public $arrResizedDetails;
    public $resOriginalImage;
    public $resResizedImage;

    /**
     * @since 4.8.2 - Renamed from "boolProtect" to "forceResize" and changed logic
     * @var bool
     */
    public $forceResize = false;

    /**
     * @var $gdVersion - gd version
     */
    public $gdVersion;

    /**
     * @var $returnRes - return result
     */
    public $returnRes = false;

    /**
     * @var $driftX - displacement watermark by x
     */
    public $driftX = 10;

    /**
     * @var $driftY - displacement watermark by y
     */
    public $driftY = 10;

    /**
     * @var $watermark - allow watermark with resize operation
     */
    public $rlWatermark;

    public function __construct()
    {
        $_gd_info = gd_info();
        if (!$_gd_info) {
            return false;
        }

        preg_match('/(\d)\.(\d)/', $_gd_info['GD Version'], $_match);

        $this->gdVersion = $_match[1];
    }

    /*
     *
     *   @Method:        rlResize
     *   @Parameters:    5
     *   @Param-1:       strPath - String - The path to the image
     *   @Param-2:       strSavePath - String - The path to save the new image to
     *   @Param-3:       strType - String - The type of resize you want to perform
     *   @Param-4:       value - Number/Array - The resize dimensions
     *   @Param-5:       boolProect - Boolen - Protects the image so that it doesnt resize an image if its already smaller
     *   @Description:   Calls the RVJ_Pagination method so its php 4 compatible
     *
     */
    public function resize($strPath, $strSavePath, $strType = 'W', $value = '150', $forceResize = false, $watermark = true)
    {
        //save the image/path details
        $this->strOriginalImagePath = $strPath;
        $this->strResizedImagePath = $strSavePath;
        $this->forceResize = $forceResize;
        $this->rlWatermark = $watermark;

        //get the image dimensions
        $this->arrOriginalDetails = getimagesize($this->strOriginalImagePath);
        $this->arrResizedDetails = $this->arrOriginalDetails;

        //create an image resource to work with
        $this->resOriginalImage = $this->createImage($this->strOriginalImagePath);

        //select the image resize type
        switch (strtoupper($strType)) {
            case 'P':
                $this->resizeToPercent($value);
                break;
            case 'H':
                $this->resizeToHeight($value);
                break;
            case 'C':
                $this->resizeToCustom($value);
                break;
            case 'W':
            default:
                $this->resizeToWidth($value);
                break;
        }
    }

    /*
     *
     *   @Method:        findResourceDetails
     *   @Parameters:    1
     *   @Param-1:       resImage - Resource - The image resource you want details on
     *   @Description:   Returns an array of details about the resource identifier that you pass it
     *
     */
    public function findResourceDetails($resImage)
    {
        //check to see what image is being requested
        if ($resImage == $this->resResizedImage) {
            //return new image details
            return $this->arrResizedDetails;
        } else {
            //return original image details
            return $this->arrOriginalDetails;
        }
    }

    /*
     *
     *   @Method:        updateNewDetails
     *   @Parameters:    0
     *   @Description:   Updates the width and height values of the resized details array
     *
     */
    public function updateNewDetails()
    {
        $this->arrResizedDetails[0] = imagesx($this->resResizedImage);
        $this->arrResizedDetails[1] = imagesy($this->resResizedImage);
    }

    /*
     *
     *   @Method:        createImage
     *   @Parameters:    1
     *   @Param-1:       strImagePath - String - The path to the image
     *   @Description:   Created an image resource of the image path passed to it
     *
     */
    public function createImage($strImagePath)
    {
        // Get the image details
        $arrDetails = $this->findResourceDetails($strImagePath);

        // Choose the correct function for the image type
        switch ($arrDetails['mime']) {
            case 'image/jpeg':
                return imagecreatefromjpeg($strImagePath);
                break;
            case 'image/png':
                return imagecreatefrompng($strImagePath);
                break;
            case 'image/gif':
                return imagecreatefromgif($strImagePath);
                break;
            case 'image/webp':
                return imagecreatefromwebp($strImagePath);
                break;
        }
    }

    /**
     * Saves the resized image (adding watermark optional)
     */
    public function saveImage()
    {
        global $config;

        switch ($this->arrResizedDetails['mime']) {
            case 'image/jpeg':
                $this->returnRes = imagejpeg($this->resResizedImage, $this->strResizedImagePath, $config['img_quality']);
                break;
            case 'image/png':
                $this->returnRes = imagepng($this->resResizedImage, $this->strResizedImagePath);
                break;
            case 'image/gif':
                $this->returnRes = imagegif($this->resResizedImage, $this->strResizedImagePath);
                break;
            case 'image/webp':
                $this->returnRes = imagewebp($this->resResizedImage, $this->strResizedImagePath, $config['img_quality']);
                break;
        }

        $originalExtension = pathinfo($this->strOriginalImagePath, PATHINFO_EXTENSION);
        $finalExtension    = pathinfo($this->strResizedImagePath, PATHINFO_EXTENSION);

        // Convert output image to necessary format
        if ($originalExtension !== $finalExtension) {
            try {
                $image = Image::make($this->strResizedImagePath);

                // Replace alpha channel by white background
                if (strtolower($finalExtension) === 'jpg') {
                    $image = Image::canvas($image->getWidth(), $image->getHeight(), '#ffffff')->insert($image);
                }

                $image->save($this->strResizedImagePath, $config['img_quality']);
            } catch (Exception $e) {
                $GLOBALS['rlDebug']->logger('Image conversion failed: ' . $e->getMessage());
            }
        }

        if (file_exists($this->strResizedImagePath) && $config['watermark_using'] && $this->rlWatermark) {
            $this->addWatermark($this->strResizedImagePath);
        }
    }

    /**
     * Add watermark to image
     *
     * @since 4.8.2
     *
     * @param  $imagePath
     * @return bool
     */
    public function addWatermark($imagePath)
    {
        if (!$imagePath = (string) $imagePath) {
            throw new \Exception("Error: Input Image for adding watermark is missing.");
        }

        global $config;

        try {
            $img = Image::make($imagePath);

            if ($config['watermark_type'] === 'image') {
                $watermark = Valid::isURL($config['watermark_image_url'])
                    ? str_replace(RL_URL_HOME, RL_ROOT, $config['watermark_image_url'])
                    : $config['watermark_image_url'];
                $watermark = Image::make($watermark);

                if ($watermark->width() !== (int) $config['watermark_image_width']) {
                    $watermark->widen((int) $config['watermark_image_width']);
                }

                if ($config['watermark_opacity']) {
                    $watermark->opacity($config['watermark_opacity']);
                }

                if ($config['watermark_position'] === 'tiled') {
                    $this->addTileWatermark($img, $imagePath, $watermark);
                } else {
                    if ($config['watermark_angle']) {
                        $watermark->rotate($config['watermark_angle']);
                    }

                    $img->insert($watermark, $config['watermark_position'], $this->driftX, $this->driftY);
                }
            } else {
                $watermark = $config['watermark_text'] ?: $GLOBALS['rlValid']->getDomain(RL_URL_HOME);

                if ($this->arrResizedDetails) {
                    $width  = $this->arrResizedDetails[0];
                    $height = $this->arrResizedDetails[1];
                } else {
                    $imageSize = getimagesize($imagePath);
                    $width     = $imageSize[0];
                    $height    = $imageSize[1];
                }

                $align  = 'left'; // Possible values are left, right and center. Default: left
                $valign = 'bottom'; // Possible values are top, bottom and middle. Default: bottom
                $x      = $this->driftX;
                $y      = $this->driftY;

                $rect = imagettfbbox(
                    $config['watermark_text_size'],
                    $config['watermark_angle'],
                    RL_LIBS . 'fonts/' . $config['watermark_text_font'],
                    $watermark
                );

                $minX = min([$rect[0],$rect[2],$rect[4],$rect[6]]);
                $maxX = max([$rect[0],$rect[2],$rect[4],$rect[6]]);
                $minY = min([$rect[1],$rect[3],$rect[5],$rect[7]]);
                $maxY = max([$rect[1],$rect[3],$rect[5],$rect[7]]);
                $watermarkSize = ['width'  => $maxX - $minX, 'height' => $maxY - $minY];

                if ($config['watermark_position'] === 'tiled') {
                    $this->addTileWatermark($img, $imagePath, $watermark, $watermarkSize);
                } else {
                    switch ($config['watermark_position']) {
                        case 'top-left':
                             if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $watermarkSize['width'] / 2;
                                $y      = $watermarkSize['height'] / 2;
                             } else {
                                 $valign = 'top';
                             }
                             break;
                        case 'top':
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $width / 2;
                                $y      = $watermarkSize['height'] / 2;
                            } else {
                                $align  = 'center';
                                $valign = 'top';
                                $x      = $width / 2;
                            }
                            break;
                        case 'top-right':
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $width - ($watermarkSize['width'] / 2);
                                $y      = $watermarkSize['height'] / 2;
                            } else {
                                $x      = $width - $this->driftX;
                                $valign = 'top';
                                $align  = 'right';
                            }
                            break;
                        case 'left':
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $watermarkSize['width'] / 2;
                                $y      = $height / 2;
                            } else {
                                $y      = $height / 2;
                                $valign = 'middle';
                            }
                            break;
                        case 'center':
                            $x      = $width / 2;
                            $y      = $height / 2;
                            $align  = 'center';
                            $valign = 'middle';
                            break;
                        case 'right':
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $width - ($watermarkSize['width'] / 2);
                                $y      = $height / 2;
                            } else {
                                $x      = $width - $this->driftX;
                                $y      = $height / 2;
                                $align  = 'right';
                                $valign = 'middle';
                            }
                            break;
                        case 'bottom-left':
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $watermarkSize['width'] / 2;
                                $y      = $height - ($watermarkSize['height'] / 2);
                            } else {
                                $y      = $height - $watermarkSize['height'];
                                $valign = 'top';
                            }
                            break;
                        case 'bottom':
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $width / 2;
                                $y      = $height - ($watermarkSize['height'] / 2);
                            } else {
                                $x      = $width / 2;
                                $y      = $height - $watermarkSize['height'];
                                $align  = 'center';
                                $valign = 'top';
                            }
                            break;
                        case 'bottom-right':
                        default:
                            if ($config['watermark_angle']) {
                                $align  = 'center';
                                $valign = 'middle';
                                $x      = $width - ($watermarkSize['width'] / 2);
                                $y      = $height - ($watermarkSize['height'] / 2);
                            } else {
                                $x     = $width - $this->driftX;
                                $y     = $height - $this->driftY;
                                $align = 'right';
                            }
                            break;
                    }

                    $color = explode(',', $config['watermark_text_color']);
                    array_push($color, (int) $config['watermark_opacity'] ? $config['watermark_opacity'] / 100 : 1);

                    $img->text($watermark, $x, $y, function($font) use ($config, $align, $valign, $color) {
                            $font->file(RL_LIBS . 'fonts/' . $config['watermark_text_font']);
                            $font->size($config['watermark_text_size']);
                            $font->color($color);
                            $font->align($align);
                            $font->valign($valign);
                            $font->angle($config['watermark_angle']);
                        }
                    );
                }
            }

            $img->save($imagePath, $config['img_quality']);
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger("Watermark didn't added: " . $e->getMessage());
        }

        return true;
    }

    /**
     * Add watermark as tile to image
     *
     * @since 4.8.2
     *
     * @param object        $img
     * @param string        $imagePath
     * @param object|string $watermark
     * @param array         $size      - Size of watermark with text type, must have [width, height]
     *
     * @return bool
     */
    private function addTileWatermark($img, $imagePath, $watermark, $size = [])
    {
        global $config;

        if (!is_object($img) || (!$imagePath = (string) $imagePath) || !$watermark) {
            return false;
        }

        if ($this->arrResizedDetails) {
            $width  = $this->arrResizedDetails[0];
            $height = $this->arrResizedDetails[1];
        } else {
            $imageSize = getimagesize($imagePath);
            $width     = $imageSize[0];
            $height    = $imageSize[1];
        }

        $watermarkWidth  = $size && $size['width'] ? $size['width'] : $watermark->width();
        $watermarkHeight = $size && $size['height'] ? $size['height'] : $watermark->height();
        $watermarkPano   = Image::canvas($width + ceil($width * 0.6), $height + ceil($height * 0.6));
        $positionX       = 0;
        $iterationX      = 1;
        $iterationY      = 1;

        if (!$watermarkWidth || !$watermarkHeight) {
            throw new \Exception("Error: Width or height of watermark is missing.");
        }

        if ($config['watermark_type'] === 'image') {
            while ($positionX < $watermarkPano->width()) {
                $positionY = 0;

                while ($positionY < $watermarkPano->height()) {
                    if ($iterationX % 2 !== 0 && $iterationY % 2 !== 0) {
                        $watermarkPano->insert($watermark, null, $positionX, $positionY);
                    }

                    $positionY += ceil($watermarkHeight * 1.2);
                    $iterationY++;
                }

                $positionX += ceil($watermarkWidth * 0.8);
                $iterationX++;
            }
        } else {
            $color = explode(',', $config['watermark_text_color']);
            array_push($color, (int) $config['watermark_opacity'] ? $config['watermark_opacity'] / 100 : 1);

            while ($positionX < $watermarkPano->width()) {
                $positionY = 0;
                while ($positionY < $watermarkPano->height()) {
                    if ($iterationX % 2 !== 0 && $iterationY % 2 !== 0) {
                        $watermarkPano->text($watermark, $positionX, $positionY, function ($font) use ($config, $color) {
                            $font->file(RL_LIBS . 'fonts/' . $config['watermark_text_font']);
                            $font->size($config['watermark_text_size']);
                            $font->color($color);
                            $font->align('left');
                            $font->valign('top');
                        });
                    }

                    $positionY += ceil(($watermarkHeight + strlen($watermark)) * ($config['watermark_angle'] ? 0.5 : 1));
                    $iterationY++;
                }

                $positionX += ceil(($watermarkWidth + strlen($watermark)) *  0.6);
                $iterationX++;
            }
        }

        if ($config['watermark_angle']) {
            $watermarkPano->rotate($config['watermark_angle']);
        }

        $img->insert($watermarkPano, 'center');

        return true;
    }

    /*
     *
     *   @Method:        showImage
     *   @Parameters:    1
     *   @Param-1:       resImage - Resource - The resource of the image you want to display
     *   @Description:   Displays the image resouce on the screen
     *
     */
    public function showImage($resImage)
    {
        //get the image details
        $arrDetails = $this->findResourceDetails($resImage);

        //set the correct header for the image we are displaying
        header("Content-type: " . $arrDetails['mime']);

        switch ($arrDetails['mime']) {
            case 'image/jpeg':
                return imagejpeg($resImage);
            case 'image/png':
                return imagepng($resImage);
            case 'image/gif':
                return imagegif($resImage);
            case 'image/webp':
                return imagewebp($resImage);
        }
    }

    /*
     *
     *   @Method:        destroyImage
     *   @Parameters:    1
     *   @Param-1:       resImage - Resource - The image resource you want to destroy
     *   @Description:   Destroys the image resource and so cleans things up
     *
     */
    public function destroyImage()
    {
        imagedestroy($this->resResizedImage);
        imagedestroy($this->resOriginalImage);

        unset($this->resResizedImage);
        unset($this->strResizedImagePath);
        unset($this->resOriginalImage);
        unset($this->strOriginalImagePath);
    }

    /*
     *
     *   @Method:        _resize
     *   @Parameters:    2
     *   @Param-1:       numWidth - Number - The width of the image in pixels
     *   @Param-2:       numHeight - Number - The height of the image in pixes
     *   @Description:   Resizes the image by creatin a new canvas and copying the image over onto it. DONT CALL THIS METHOD DIRECTLY - USE THE METHODS BELOW
     *
     */
    public function _resize($numWidth, $numHeight)
    {
        if (!$numWidth || !$numHeight) {
            return;
        }

        switch ($this->arrOriginalDetails['mime']) {
            case 'image/gif':
                $this->resResizedImage = imagecreate($numWidth, $numHeight);
                break;

            case 'image/png':
                $this->resResizedImage = imagecreatetruecolor($numWidth, $numHeight);
                imagealphablending($this->resResizedImage, false);
                imagesavealpha($this->resResizedImage, true);
                break;

            default:
                $this->resResizedImage = imagecreatetruecolor($numWidth, $numHeight);
                break;
        }

        // Update the image size details
        $this->updateNewDetails();

        $resize_method = function_exists('imagecopyresampled') ? 'imagecopyresampled' : 'imagecopyresized';
        $resize_method($this->resResizedImage, $this->resOriginalImage, 0, 0, 0, 0, $numWidth, $numHeight, $this->arrOriginalDetails[0], $this->arrOriginalDetails[1]);

        $this->saveImage();
        $this->destroyImage();
    }

    /*
     *
     *   @Method:        _imageProtect
     *   @Parameters:    2
     *   @Param-1:       numWidth - Number - The width of the image in pixels
     *   @Param-2:       numHeight - Number - The height of the image in pixes
     *   @Description:   Checks to see if we should allow the resize to take place or not depending on the size the image will be resized to
     *
     */
    public function _imageProtect($numWidth, $numHeight)
    {
        if ($this->forceResize and ($numWidth > $this->arrOriginalDetails[0] or $numHeight > $this->arrOriginalDetails[1])) {
            return 0;
        }

        return 1;
    }

    /*
     *
     *   @Method:        resizeToWidth
     *   @Parameters:    1
     *   @Param-1:       numWidth - Number - The width to resize to in pixels
     *   @Description:   Works out the height value to go with the width value passed, then calls the resize method.
     *
     */
    public function resizeToWidth($numWidth)
    {
        $numHeight = (int) (($numWidth * $this->arrOriginalDetails[1]) / $this->arrOriginalDetails[0]);
        $this->_resize($numWidth, $numHeight);
    }

    /*
     *
     *   @Method:        resizeToHeight
     *   @Parameters:    1
     *   @Param-1:       numHeight - Number - The height to resize to in pixels
     *   @Description:   Works out the width value to go with the height value passed, then calls the resize method.
     *
     */
    public function resizeToHeight($numHeight)
    {
        $numWidth = (int) (($numHeight * $this->arrOriginalDetails[0]) / $this->arrOriginalDetails[1]);
        $this->_resize($numWidth, $numHeight);
    }

    /*
     *
     *   @Method:        resizeToPercent
     *   @Parameters:    1
     *   @Param-1:       numPercent - Number - The percentage you want to resize to
     *   @Description:   Works out the width and height value to go with the percent value passed, then calls the resize method.
     *
     */
    public function resizeToPercent($numPercent)
    {
        $numWidth = (int) (($this->arrOriginalDetails[0] / 100) * $numPercent);
        $numHeight = (int) (($this->arrOriginalDetails[1] / 100) * $numPercent);
        $this->_resize($numWidth, $numHeight);
    }

    /*
     *
     *   @Method:        resizeToCustom
     *   @Parameters:    1
     *   @Param-1:       size - Number/Array - Either a number of array of numbers for the width and height in pixels
     *   @Description:   Checks to see if array was passed and calls the resize method with the correct values.
     *
     */
    public function resizeToCustom($size)
    {
        if (is_array($size)) {
            // current image params
            $_photo_width = $this->arrOriginalDetails[0];
            $_photo_height = $this->arrOriginalDetails[1];

            // new image params
            $img_width = (int) $size[0];
            $img_height = (int) $size[1];

            // the following code does not creates white BG, the code should be set instead of 3 code iines above
            if (($_photo_width > $img_width && $img_width) || ($_photo_height > $img_height && $img_height)) {
                if ($_photo_width > $_photo_height) {
                    $_resized_photo_width = $img_width;
                    $_percent = round(100 * $img_width / $_photo_width);
                    $_resized_photo_height = round($_percent * $_photo_height / 100);
                } elseif ($_photo_width < $_photo_height) {
                    $_resized_photo_height = $img_height;
                    $_percent = round(100 * $img_height / $_photo_height);
                    $_resized_photo_width = round($_percent * $_photo_width / 100);
                } else {
                    if ($img_width > $img_height) {
                        $_resized_photo_width = $img_width;
                        $_percent = round(100 * $img_width / $_photo_width);
                        $_resized_photo_height = round($_percent * $_photo_height / 100);
                    } else {
                        $_resized_photo_height = $img_height;
                        $_percent = round(100 * $img_height / $_photo_height);
                        $_resized_photo_width = round($_percent * $_photo_width / 100);
                    }
                }
            } else {
                if ($this->forceResize) {
                    $_resized_photo_width  = $img_width;
                    $_resized_photo_height = $img_height;
                } else {
                    $_resized_photo_width  = $_photo_width;
                    $_resized_photo_height = $_photo_height;
                }
            }

            $this->_resize($_resized_photo_width, $_resized_photo_height);
        } else {
            $this->resizeToWidth($size);
        }
    }
}
