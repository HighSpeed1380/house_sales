<?php

/*
 * This file is part of the Flynax package.
 *
 * (c) Flynax Classifieds Software <support@flynax.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flynax\Component;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

/**
 * Is an extension class of the Symfony component.
 * Provides basic utility to manipulate the file system
 *
 * @author Alex <runexes@me.com>
 * @since  1.0.1 Renamed method "copy" to "copyTo"
 */
class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * Makes a copy of the file/folder source to dest
     *
     * @param string   $source             - Path to the source
     * @param string   $destination        - The destination path
     * @param callable $catchExceptionFunc - To get exception details use callable like "function ($exception) {}"
     * @param array    $options            - An array of boolean options
    *                  Valid options are:
    *                   * $options['override']        - Whether to override an existing file on copy or not
    *                   * $options['copy_on_windows'] - Whether to copy files instead of links on Windows
    *                   * $options['delete']          - Whether to delete files that are not in the source directory
     *
     * @throws FileNotFoundException - When source doesn't exist
     * @throws IOException           - When copy fails
     *
     * @return bool
     */
    public function copyTo($source, $destination, $catchExceptionFunc = null, $options = array())
    {
        $override = isset($options['override']) ? $options['override'] : false;

        try {
            if (is_dir($source)) {
                $iterator = isset($options['iterator']) ? $options['iterator'] : null;
                $options  = array(
                    'override'        => $override,
                    'copy_on_windows' => isset($options['copy_on_windows']) ? $options['copy_on_windows'] : false,
                    'delete'          => isset($options['delete']) ? $options['delete'] : false,
                );
                parent::mirror($source, $destination, $iterator, $options);
            } else {
                parent::copy($source, $destination, $override);
            }
        } catch (IOException $exception) {
            if (null !== $catchExceptionFunc && is_callable($catchExceptionFunc)) {
                $catchExceptionFunc($exception);
            }

            return false;
        }

        return true;
    }
}
