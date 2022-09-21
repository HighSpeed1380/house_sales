<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PLUGINS.INC.PHP
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

use Flynax\Interfaces\PluginInterface;
use Flynax\Classes\PluginManager;
use Flynax\Utils\Valid;

// Ext js action
if ($_GET['q'] == 'ext') {
    require '../../includes/config.inc.php';
    require RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require RL_LIBS . 'system.lib.php';

    if ($_GET['action'] == 'update') {
        $type       = Valid::escape($_GET['type']);
        $field      = Valid::escape($_GET['field']);
        $value      = Valid::escape(nl2br($_GET['value']));
        $key        = Valid::escape($_GET['key']);
        $id         = (int) $_GET['id'];
        $filesExist = true;

        if ($field == 'Status' && $id) {
            // Activation/Deactivation of the plugin
            $pluginInfo = $rlDb->fetch(['Key', 'Files', 'Class'], ['ID' => $id], null, 1, 'plugins', 'row');

            if (empty($pluginInfo)) {
                exit;
            }

            if ($value == 'active') {
                $files = unserialize($pluginInfo['Files']);
                foreach ($files as $file) {
                    $file = str_replace(['\\', '/'], [RL_DS, RL_DS], $file);

                    if (!is_readable(RL_PLUGINS . $pluginInfo['Key'] . RL_DS . $file)) {
                        $filesExist = false;
                        $missed_files .= '/plugins/' . $pluginInfo['Key'] . '<b>' . $file . '</b><br />';
                    }
                }
            }

            if ($filesExist === true) {
                $tables = ['lang_keys', 'hooks', 'blocks', 'admin_blocks', 'pages', 'email_templates', 'payment_gateways'];

                foreach ($tables as $table) {
                    unset($plugin_update);
                    $plugin_update = [
                        'fields' => ['Status' => $value],
                        'where'  => ['Plugin' => $pluginInfo['Key']],
                    ];
                    $rlDb->updateOne($plugin_update, $table);
                }

                /**
                 * @since 4.7.0
                 */
                try {
                    $instance = PluginManager::getPluginInstance($pluginInfo['Key'], $pluginInfo['Class']);

                    if ($instance && method_exists($instance, 'statusChanged')) {
                        $instance->statusChanged($value);
                    }
                } catch (Exception $exception) {
                    $rlDebug->logger($exception->getMessage());
                }

                if ($pluginInfo['Key'] == 'androidConnect' || $pluginInfo['Key'] == 'iFlynaxConnect') {
                    $apContUpdate = [
                        'fields' => ['Parent_ID' => $value == 'active' ? 0 : -1],
                        'where'  => ['Key' => $pluginInfo['Key'] == 'androidConnect' ? 'android' : 'iFlynaxConnect'],
                    ];
                    $rlDb->updateOne($apContUpdate, 'admin_controllers');
                }
            } else {
                $message = str_replace('{files}', '<br />' . $missed_files, $rlLang->getSystem('plugin_files_missed'));
                echo $message;
                unset($missed_files);
            }
        }

        if ($filesExist === true) {
            $updateData = [
                'fields' => [$field => $value],
                'where'  => ['ID' => $id],
            ];

            $rlHook->load('apExtPluginsUpdate');

            $rlDb->updateOne($updateData, 'plugins');
        }
        exit;
    }

    $limit = (int) $_GET['limit'];
    $start = (int) $_GET['start'];

    $sql = 'SELECT `T1`.* FROM `{db_prefix}plugins` AS `T1` ';

    if (array_key_exists('plugin', $_GET)) {
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('title_', `T1`.`Key`) = `T2`.`Key` ";
    }

    $sql .= 'WHERE 1 ';

    // Search simulation
    if (array_key_exists('plugin', $_GET)) {
        $plugin = urldecode($_GET['plugin']);
        $plugin = Valid::escape($plugin);
        $sql .= "AND (
            `T1`.`Key` LIKE '%{$plugin}%' OR `T1`.`Name` LIKE '%{$plugin}%'
            OR `T2`.`Key` LIKE '%{$plugin}%' OR `T2`.`Value` LIKE '%{$plugin}%'
        ) ";
    }

    $onlyNotInstalled = false;
    if (array_key_exists('status', $_GET)) {
        if ($_GET['status'] == 'not_installed') {
            $onlyNotInstalled = true;
        } else {
            $pluginStatus = Valid::escape($_GET['status']);
            $sql .= "AND `T1`.`Status` = '{$pluginStatus}' ";
        }
    }

    $data = $rlDb->getAll($sql);

    foreach ($data as $key => $value) {
        $description = $rlLang->getPhrase('description_' . $value['Key'], null, null, true) ?: $value['Description'];

        $data[$key]['Status']           = $lang[$data[$key]['Status']];
        $data[$key]['Name']             = $lang['title_' . $value['Key']] ?: $value['Name'];
        $data[$key]['Description']      = $description;
        $insPlugins[$data[$key]['Key']] = $data[$key];
    }
    unset($data);

    // Scan plugins directory
    $allPlugins = $reefless->scanDir(RL_PLUGINS, true);
    $pluginsOut = [];

    // Sorting by status
    foreach ($allPlugins as $key => $value) {
        if (isset($insPlugins[$allPlugins[$key]])) {
            if (!$onlyNotInstalled) {
                array_push($pluginsOut, $insPlugins[$allPlugins[$key]]);
            }
        } else {
            if (array_key_exists('status', $_GET) && !$onlyNotInstalled) {
                continue;
            }

            array_push($pluginsOut, array(
                'Name'        => $allPlugins[$key],
                'Key'         => $allPlugins[$key] . '|not_installed',
                'Version'     => $lang['not_available'],
                'Description' => $lang['not_available'],
                'Status'      => 'not_installed',
            ));
        }
    }

    // Check not compatible plugins
    $reefless->loadClass('Plugin', 'admin');

    $countPlugins = count($pluginsOut);

    if (!array_key_exists('plugin', $_GET) && $pluginsOut) {
        $pluginsOut = array_slice($pluginsOut, $start, $limit);
    }

    foreach ($pluginsOut as $key => $value) {
        if (false !== strpos($value['Key'], 'not_installed')) {
            $pluginKey  = explode('|', $value['Key'])[0];
            $pluginData = $rlPlugin->getXmlData($pluginKey);

            $pluginsOut[$key]['Compatible']  = $rlPlugin->checkCompatibilityByVersion($pluginData['compatible']);
            $pluginsOut[$key]['Name']        = $pluginData['title'];
            $pluginsOut[$key]['Description'] = $pluginData['description'];
            $pluginsOut[$key]['Version']     = $pluginData['version'];

            if (!$pluginsOut[$key]['Compatible']) {
                $pluginsOut[$key]['Status'] = $lang['plugin_not_compatible'];
            }
        } else {
            $pluginsOut[$key]['Compatible'] = true;
        }
    }

    // Sort by name/key if necessary
    if (array_key_exists('plugin', $_GET) && !empty($pluginsOut)) {
        $pattern = "/{$plugin}/ui";

        foreach ($pluginsOut as $key => $value) {
            if (!preg_match($pattern, $value['Key']) && !preg_match($pattern, $value['Name'])) {
                unset($pluginsOut[$key]);
            }
        }

        // Reset keys of final list of plugins
        $pluginsOut   = array_values($pluginsOut);
        $countPlugins = count($pluginsOut);
    }

    /**
     * @since 4.8.0 - Added $pluginsOut, $countPlugins parameters
     */
    $rlHook->load('apExtPluginsData', $pluginsOut, $countPlugins);

    echo json_encode(['total' => $countPlugins, 'data' => $pluginsOut]);
}
/* ext js action end */

/* ajax action */
elseif ($_REQUEST['q'] == 'ajax') {
    /* system config */
    require_once '../../includes/config.inc.php';
    require_once RL_ADMIN_CONTROL . 'ext_header.inc.php';
    require_once RL_LIBS . 'system.lib.php';

    $id = (int) $_GET['id'];

    if (empty($id)) {
        exit;
    }

    if ($_REQUEST['action'] == 'check_complete') {
        $plugin_info = $rlDb->fetch(array('Key', 'Files'), array('ID' => (int) $id), null, 1, 'plugins', 'row');

        if (empty($plugin_info)) {
            exit;
        }

        $files = unserialize($plugin_info['Files']);
        foreach ($files as $file) {
            $file = str_replace(array('\\', '/'), array(RL_DS, RL_DS), $file);

            if (!is_readable(RL_PLUGINS . $plugin_info['Key'] . RL_DS . $file)) {
                $files_exist = false;
                $message .= RL_DS . "plugins" . RL_DS . $plugin_info['Key'] . RL_DS . "<b>" . $file . "</b><br />";
            }
        }

        echo json_encode(empty($message) ? true : str_replace('{files}', "<br />" . $message, $rlLang->getSystem('plugin_files_missed')));
    }
}
/* ajax action end */

else {
    eval(base64_decode(RL_SETUP));
    eval(base64_decode(RL_ASSIGN));

    $reefless->loadClass('Plugin', 'admin');

    // Register ajax methods
    $rlXajax->registerFunction(array('unInstall', $rlPlugin, 'ajaxUnInstall'));
    $rlXajax->registerFunction(array('checkForUpdate', $rlAdmin, 'ajaxCheckForUpdate'));
    $rlXajax->registerFunction(array('browsePlugins', $rlPlugin, 'ajaxBrowsePlugins'));

    $rlHook->load('apPhpPluginsBottom');
}
