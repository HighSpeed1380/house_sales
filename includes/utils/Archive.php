<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ARCHIVE.PHP
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

use wapmorgan\UnifiedArchive\UnifiedArchive;

/**
 * Class for packing/unpacking archives
 *
 * @since 4.7.1
 */
class Archive
{
    /**
     * Unpack archive to destination directory
     *
     * @param string $archive       - Path of archive
     * @param string $destination   - Path of folder where archive must be unpacked
     * @param bool   $removeArchive - Archive will be removed after unpacking
     *
     * @return bool
     */
    public static function unpack($archive, $destination, $removeArchive = true)
    {
        global $rlDebug;

        if (!$archive || !$destination) {
            return false;
        }

        // create folder if it's not exist
        $GLOBALS['reefless']->rlMkdir($destination);

        if (class_exists('ZipArchive')) {
            $zip    = new \ZipArchive;
            $result = $zip->open($archive);

            if ($result === true) {
                $zip->extractTo($destination);
                $zip->close();
            } else {
                $rlDebug->logger("Zip Archive | Archive cannot be unpacked, error code: " . $result);
                $result = false;
            }
        } else {
            try {
                $zipFile = UnifiedArchive::open($archive);
                $zipFile->extractFiles($destination);
                $result = true;
            } catch(\Exception $e){
                $rlDebug->logger('UnifiedArchive | Archive cannot be packed, error code: ' . $e->getMessage());
                $result = false;
            }
        }

        if ($removeArchive) {
            unlink($archive);
        }

        return $result;
    }

    /**
     * Pack file/folder to archive
     *
     * @param string $source  - Path of file/directory
     *                        - Example of file: /root/folder/.../file.php
     *                        - Example of folder: /root/folder/.../folder
     *                        - Example of array: ['/root/folder/.../file.php', '/root/folder/.../folder',]
     * @param string $archive - Path of archive, example: /root/folder/.../archive.zip
     *
     * @return bool
     */
    public static function pack($source, $archive)
    {
        global $rlDebug;

        if (!$source || !$archive) {
            return false;
        }

        // prepare list of files for packing
        $files = [];

        if (is_string($source)) {
            if (is_file($source)) {
                $files[] = $source;
            } elseif (is_dir($source)) {
                // add missing directory separator
                $files[] = $source . (!in_array(substr($source, -1, 1), ['/', '\\']) ? RL_DS : '');
            }
        } else if (is_array($source)) {
            foreach ($source as $item) {
                if (is_file($item) || is_dir($item)) {
                    if (is_file($item)) {
                        $files[] = $item;
                    } elseif (is_dir($item)) {
                        // add missing directory separator
                        $files[] = $item . (!in_array(substr($item, -1, 1), ['/', '\\']) ? RL_DS : '');
                    }
                }
            }
        }

        unlink($archive);

        if (!$files) {
            return false;
        }

        if (class_exists('ZipArchive')) {
            $zip    = new \ZipArchive;
            $result = $zip->open($archive, \ZipArchive::CREATE);

            if ($result === true) {
                foreach ($files as $file) {
                    $source     = realpath($file);
                    $folderName = pathinfo($source)['dirname'];

                    if (is_dir($source)) {
                        $iterator = new \RecursiveDirectoryIterator($source);

                        // skip dot files while iterating
                        $iterator->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
                        $childFiles = new \RecursiveIteratorIterator(
                            $iterator,
                            \RecursiveIteratorIterator::SELF_FIRST
                        );

                        foreach ($childFiles as $childFile) {
                            $childFile = realpath($childFile);

                            if (is_dir($childFile)) {
                                $zip->addEmptyDir(str_replace($folderName, '', $childFile . '/'));
                            } else if (is_file($childFile)) {
                                $zip->addFile($childFile, str_replace($folderName, '', $childFile));
                            }
                        }
                    } else if (is_file($source)) {
                        $zip->addFile($source, str_replace($folderName, '', $source));
                    }
                }

                $zip->close();
            } else {
                $rlDebug->logger("Zip Archive | Archive cannot be packed, error code: " . $result);
            }
        } else {
            try {
                UnifiedArchive::archiveDirectory($source, $archive);
            } catch(\Exception $e){
                $rlDebug->logger("UnifiedArchive | Archive cannot be packed, error code: " . $e->getMessage());
            }
        }

        return (bool) file_exists($archive);
    }
}
