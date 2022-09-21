<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PLUGININTERFACE.PHP
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

namespace Flynax\Interfaces;

/**
 * @since 4.6.0
 */
interface PluginInterface
{
    /**
     * Execute arbitrary changes after installation
     *
     * @return void
     */
    public function install();

    /**
     * Execute arbitrary changes after uninstall
     *
     * @return void
     */
    public function uninstall();

    /**
     * Execute arbitrary changes after update
     *
     * @param  string $version
     * @return void
     */
    public function update($version);
}
