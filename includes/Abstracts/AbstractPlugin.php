<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ABSTRACTPLUGIN.PHP
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
 * @since 4.6.0
 */
abstract class AbstractPlugin
{
    /**
     * Execute arbitrary changes after installation
     *
     * @since 4.7.0
     *
     * @return void
     */
    public function install()
    {}

    /**
     * Execute arbitrary changes after uninstall
     *
     * @since 4.7.0
     *
     * @return void
     */
    public function uninstall()
    {}

    /**
     * Execute arbitrary changes after update
     *
     * @since 4.7.0 - Check method exists in plugin classes like update300
     *              - Initially was checking methods like update_300
     *
     * @param  string $version
     * @return void
     */
    public function update($version)
    {
        $version = (int) str_replace('.', '', $version);
        $version_method = 'update' . $version;

        if (method_exists($this, $version_method)) {
            $this->$version_method();
        }
    }
}
