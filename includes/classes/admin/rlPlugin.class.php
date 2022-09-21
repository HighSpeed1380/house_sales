<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLPLUGIN.CLASS.PHP
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
use Symfony\Component\Filesystem\Exception\IOException;
use Flynax\Utils\Archive;

/**
 * xajax fallback class
 *
 * @todo - Remove this class once the $_response object usage will be remove
 *         from plugins installation (AndroidConnect and iFlynaxConnect)
 *
 * @since 4.8.1
 */
class xajaxFallback
{
    /**
     * js codes
     * @var array
     */
    private $jsCode = [];

    /**
     * Collect js code
     *
     * @param  string $code - js code
     */
    public function script($code = '')
    {
        if (!$code) {
            return;
        }

        $this->jsCode[] = $code;
    }

    /**
     * Get collected js code
     *
     * @return array - js code array
     */
    public function get()
    {
        return $this->jsCode;
    }
}

/**
 * SMARTY fallback class
 *
 * @todo - Remove this class once the $rlSmarty object usage will be remove
 *         from all plugins installation (weatherForeacast)
 *
 * @since 4.8.1
 */
class smartyFallback
{
    public function register_function() {}
    public function assign_by_ref() {}
    public function assign() {}
    public function display() {}
    public function fetch() {}
}

class rlPlugin
{
    public $inTag;
    public $level = 0;
    public $attributes;

    public $key;
    public $title;
    public $description;
    public $version;
    public $uninstall;
    public $hooks;
    public $phrases;
    public $configGroup;
    public $configs;
    public $blocks;
    public $aBlocks;
    public $pages;
    public $emails;
    public $files;
    public $notice;
    public $controller;

    public $updates;
    public $notices;
    public $controllerUpdate;

    public $noVersionTag = false;

    /**
     * Install plugin
     *
     * @param string  $key        - Plugin key
     * @param boolean $remoteMode - Remote installation mode
     **/
    public function ajaxInstall($key = false, $remoteMode = false)
    {
        global $rlLang, $languages, $rlDb, $reefless, $lang, $rlDebug;

        if (!$key) {
            return false;
        }

        // Create xajax fallback class
        global $_response;
        $_response = new xajaxFallback();

        // Create SMARTY fallback class
        global $rlSmarty;
        $rlSmarty = new smartyFallback();

        $this->noVersionTag = true;

        $out = [];

        if ($reefless->checkSessionExpire() === false) {
            return array(
                'status' => 'REDIRECT',
                'data' => 'session_expired'
            );
        }

        $path_to_install = RL_PLUGINS . $key . RL_DS . 'install.xml';

        if (is_readable($path_to_install)) {
            require_once RL_LIBS . 'saxyParser' . RL_DS . 'xml_saxy_parser.php';

            $rlParser = new SAXY_Parser();
            $rlParser->xml_set_element_handler(array($this, 'startElement'), array($this, 'endElement'));
            $rlParser->xml_set_character_data_handler(array($this, 'charData'));
            $rlParser->xml_set_comment_handler(array($this, 'commentHandler'));

            // Parse xml file
            $rlParser->parse(file_get_contents($path_to_install));

            // Check compatibility with current version of the software
            if (!$this->checkCompatibilityByVersion($this->compatible)) {
                return array(
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('plugin_not_compatible_notice')
                );
            }

            $plugin = array(
                'Key'         => $this->key,
                'Class'       => $this->uninstall['class'] ?: '',
                'Name'        => $this->title,
                'Description' => $this->description,
                'Version'     => $this->version,
                'Status'      => 'approval',
                'Install'     => 1,
                'Controller'  => $this->controller,
                'Uninstall'   => $this->uninstall['code'],
                'Files'       => serialize($this->files),
            );

            // Install plugin
            if ($rlDb->insertOne($plugin, 'plugins')) {
                // Install language's phrases
                $phrases     = $this->phrases ?: [];
                $pluginTitle = $this->title;

                array_push($phrases, [
                    'Key'    => 'title_' . $this->key,
                    'Module' => 'admin',
                    'Value'  => $this->title,
                ]);
                array_push($phrases, [
                    'Key'        => 'description_' . $this->key,
                    'Module'     => 'admin',
                    'Value'      => $this->description,
                    'Target_key' => 'plugins'
                ]);

                foreach ($languages as $language) {
                    $locales[$language['Code']] = $locale = $this->getLanguagePhrases($language['Code'], $this->key);

                    if ($phrases) {
                        foreach ($phrases as $phrase) {
                            if ($phrase['Module'] == 'ext') {
                                $phrase['Module'] = 'admin';
                                $phrase['JS']     = '1';
                            }

                            $lang_keys[] = array(
                                'Code'       => $language['Code'],
                                'Module'     => $phrase['Module'],
                                'JS'         => $phrase['JS'],
                                'Target_key' => $phrase['Target_key'],
                                'Key'        => $phrase['Key'],
                                'Value'      => $locale[$phrase['Key']] ?: $phrase['Value'],
                                'Plugin'     => $this->key,
                                'Status'     => 'approval',
                            );

                            if ($phrase['Key'] === 'title_' . $this->key
                                && RL_LANG_CODE === $language['Code']
                                && $locale[$phrase['Key']]
                            ) {
                                $pluginTitle = $locale[$phrase['Key']];
                            }
                        }
                    }
                }

                // Install hooks
                $hooks = $this->hooks;
                if (!empty($hooks)) {
                    $rlDb->insert($hooks, 'hooks');
                }

                // Install configs
                $cGroup = $configGroup = $this->configGroup;
                if (!empty($configGroup)) {
                    $cg_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}config_groups` LIMIT 1");
                    unset($cGroup['Name']);
                    $cGroup['Position'] = $cg_max_poss['max'] + 1;

                    $rlDb->insertOne($cGroup, 'config_groups');
                    $group_id = $rlDb->insertID();

                    // Add config group phrases
                    foreach ($languages as $language) {
                        $locale    = $locales[$language['Code']];
                        $group_key = 'config_groups+name+' . $configGroup['Key'];

                        $lang_keys[] = array(
                            'Code'       => $language['Code'],
                            'Module'     => 'admin',
                            'Key'        => $group_key,
                            'Value'      => $locale[$group_key] ?: $configGroup['Name'],
                            'Plugin'     => $this->key,
                            'Target_key' => 'settings',
                            'Status'     => 'approval',
                        );
                    }
                }
                $group_id = empty($group_id) ? 0 : $group_id;

                $configs = $this->configs;
                if (!empty($configs)) {
                    foreach ($languages as $language) {
                        $locale = $locales[$language['Code']];

                        foreach ($configs as $conf) {
                            $name_key = 'config+name+' . $conf['Key'];
                            $lang_keys[] = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'admin',
                                'Key'        => $name_key,
                                'Value'      => $locale[$name_key] ?: $conf['Name'],
                                'Plugin'     => $this->key,
                                'Target_key' => 'settings',
                                'Status'     => 'approval',
                            );

                            if (!empty($conf['Description'])) {
                                $des_key = 'config+des+' . $conf['Key'];
                                $lang_keys[] = array(
                                    'Code'       => $language['Code'],
                                    'Module'     => 'admin',
                                    'Key'        => $des_key,
                                    'Value'      => $locale[$des_key] ?: $conf['Description'],
                                    'Plugin'     => $this->key,
                                    'Target_key' => 'settings',
                                    'Status'     => 'approval',
                                );
                            }
                        }
                    }

                    foreach ($configs as $key => $value) {
                        $position = $key;

                        if ($configs[$key]['Group']) {
                            $max_pos = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}config` WHERE `Group_ID` = '{$configs[$key]['Group']}' LIMIT 1");
                            $position = $max_pos['Max'] + $key;
                        }

                        $configs[$key]['Position'] = $position;
                        $configs[$key]['Group_ID'] = !$group_id ? $configs[$key]['Group'] : $group_id;
                        unset($configs[$key]['Name']);
                        unset($configs[$key]['Description']);
                        unset($configs[$key]['Group']);
                        unset($configs[$key]['Version']);
                    }
                    $rlDb->insert($configs, 'config');
                }

                // Install blocks
                $blocks = $this->blocks;
                if (!empty($blocks)) {
                    foreach ($blocks as $block_key => &$block) {
                        $block_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks` LIMIT 1");
                        $block['Position'] = $block_max_poss['max'] + 1;

                        if (in_array(strtolower($block['Type']), array('html', 'php', 'smarty'))) {
                            foreach ($languages as $language) {
                                $locale = $locales[$language['Code']];

                                // Add name phrases
                                $block_key = 'blocks+name+' . $block['Key'];
                                $lang_keys[] = array(
                                    'Code'       => $language['Code'],
                                    'Module'     => 'box',
                                    'Key'        => $block_key,
                                    'Value'      => $locale[$block_key] ?: $block['Name'],
                                    'Plugin'     => $this->key,
                                    'Target_key' => $block['Key'],
                                    'Status'     => 'avtive',
                                );

                                if (strtolower($block['Type']) == 'html') {
                                    $block_content_key = 'blocks+content+' . $block['Key'];
                                    $lang_keys[] = array(
                                        'Code'       => $language['Code'],
                                        'Module'     => 'common',
                                        'Key'        => $block_content_key,
                                        'Value'      => $locale[$block_content_key] ?: $block['Content'],
                                        'Plugin'     => $this->key,
                                        'Target_key' => $block['Key'],
                                        'Status'     => 'avtive',
                                    );
                                    unset($block['Content']);
                                }
                            }

                            unset($block['Name']);
                            unset($block['Version']);
                        } else {
                            unset($blocks[$block_key]);
                        }
                    }
                    $rlDb->insert($blocks, 'blocks');
                }

                // install admin panel blocks
                $aBlocks = $this->aBlocks;
                if ($aBlocks) {
                    if ($remoteMode) {
                        require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
                        $reefless->loadClass('Smarty');
                    }

                    foreach ($aBlocks as $key => $value) {
                        $aBlock_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}admin_blocks` WHERE `Column` = 'column{$value['Column']}' LIMIT 1");
                        $aBlocks[$key]['Position'] = $aBlock_max_poss['max'] + 1;

                        // add name phrases
                        foreach ($languages as $language) {
                            $locale   = $locales[$language['Code']];
                            $name_key =  'admin_blocks+name+' . $value['Key'];

                            $lang_keys[] = array(
                                'Code'       => $language['Code'],
                                'Module'     => 'admin',
                                'Key'        => $name_key,
                                'Value'      => $locale[$name_key] ?: $value['Name'],
                                'Plugin'     => $this->key,
                                'Target_key' => 'home',
                                'Status'     => 'active',
                            );
                        }

                        $aBlocks[$key]['name'] = $aBlocks[$key]['Name'];
                        $aBlocks[$key]['Column'] = 'column' . $aBlocks[$key]['Column'];

                        unset($aBlocks[$key]['Name']);
                        unset($aBlocks[$key]['name']);
                        unset($aBlocks[$key]['Version']);

                        // Append new block
                        if ($remoteMode) {
                            $rlSmarty->assign('block', $aBlocks[$key]);

                            $tpl = 'blocks' . RL_DS . 'homeDragDrop_block.tpl';
                            $out['html'][] = array(
                                'code' => $rlSmarty->fetch($tpl, null, null, false),
                                'box'  => $value['Column'],
                                'ajax' => $value['Ajax']
                            );
                        }
                    }
                    $rlDb->insert($aBlocks, 'admin_blocks');
                }

                // Install pages
                $pages = $this->pages;
                if (!empty($pages)) {
                    foreach ($pages as $key => $value) {
                        $page_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages` LIMIT 1");
                        $pages[$key]['Position'] = $page_max_poss['max'] + 1;

                        if (in_array($pages[$key]['Page_type'], array('system', 'static', 'external'))) {
                            // Add name phrases
                            foreach ($languages as $language) {
                                $locale   = $locales[$language['Code']];
                                $name_key = 'pages+name+' . $pages[$key]['Key'];

                                $lang_keys[] = array(
                                    'Code'   => $language['Code'],
                                    'Module' => 'common',
                                    'Key'    => $name_key,
                                    'Value'  => $locale[$name_key] ?: $pages[$key]['Name'],
                                    'Plugin' => $this->key,
                                    'Status' => 'active',
                                );
                                $lang_keys[] = array(
                                    'Code'   => $language['Code'],
                                    'Module' => 'frontEnd',
                                    'Key'    => 'pages+title+' . $pages[$key]['Key'],
                                    'Value'  => $locale[$name_key] ?: $pages[$key]['Name'],
                                    'Plugin' => $this->key,
                                    'Status' => 'active',
                                );

                                if ($pages[$key]['Page_type'] == 'static') {
                                    $content_key = 'pages+content+' . $pages[$key]['Key'];
                                    $lang_keys[] = array(
                                        'Code'       => $language['Code'],
                                        'Module'     => 'frontEnd',
                                        'Key'        => $content_key,
                                        'Value'      => $locale[$content_key] ?: $pages[$key]['Content'],
                                        'Plugin'     => $this->key,
                                        'Target_key' => $pages[$key]['Page_type'] == 'static' ? 'static' : $pages[$key]['Controller'],
                                        'Status'     => 'active',
                                    );
                                }
                            }

                            switch ($pages[$key]['Page_type']) {
                                case 'system':
                                    $pages[$key]['Controller'] = $pages[$key]['Controller'];
                                    break;
                                case 'static':
                                    $pages[$key]['Controller'] = 'static';
                                    break;
                                case 'external':
                                    $pages[$key]['Controller'] = $pages[$key]['Content'];
                                    break;
                            }
                            unset($pages[$key]['Name']);
                            unset($pages[$key]['Content']);
                            unset($pages[$key]['Version']);
                        } else {
                            unset($pages[$key]);
                        }
                    }
                    $rlDb->insert($pages, 'pages');
                }

                // Install email templates
                $emails = $this->emails;
                if (!empty($emails)) {
                    foreach ($emails as $key => $value) {
                        $email_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}email_templates` LIMIT 1");
                        $emails[$key]['Position'] = $email_max_poss['max'] + 1;

                        // add name phrases
                        foreach ($languages as $language) {
                            $locale      = $locales[$language['Code']];
                            $subject_key = 'email_templates+subject+' . $emails[$key]['Key'];
                            $body_key    = 'email_templates+body+' . $emails[$key]['Key'];

                            $lang_keys[] = array(
                                'Code'   => $language['Code'],
                                'Module' => 'email_tpl',
                                'Key'    => $subject_key,
                                'Value'  => $locale[$subject_key] ?: $emails[$key]['Subject'],
                                'Plugin' => $this->key,
                                'Status' => 'active',
                            );
                            $lang_keys[] = array(
                                'Code'   => $language['Code'],
                                'Module' => 'email_tpl',
                                'Key'    => $body_key,
                                'Value'  => $locale[$body_key] ?: $emails[$key]['Body'],
                                'Plugin' => $this->key,
                                'Status' => 'active',
                            );
                        }
                        unset($emails[$key]['Subject']);
                        unset($emails[$key]['Body']);
                        unset($emails[$key]['Version']);
                    }
                    $rlDb->insert($emails, 'email_templates');
                }

                // Add phrases
                if (!empty($lang_keys)) {
                    $rlDb->insert($lang_keys, 'lang_keys');
                }

                /**
                 * @since 4.7.0 - Using PluginManager class instead of the internal method
                 * @since 4.6.0
                 */
                try {
                    $instance = PluginManager::getPluginInstance($this->key, $this->install['class']);

                    if ($instance && $instance instanceof PluginInterface) {
                        $instance->install();
                    } elseif ($this->install['code'] !== '') {
                        @eval($this->install['code']);
                    }
                } catch (Exception $e) {
                    $rlDebug->logger($e->getMessage());
                }

                // Collect custom js code
                $out['js'] = $_response->get();

                // Check plugin files exist
                $files = $this->files;
                $files_exist = true;

                foreach ($files as $file) {
                    $file = str_replace(array('\\', '/'), array(RL_DS, RL_DS), $file);

                    if (!is_readable(RL_PLUGINS . $this->key . RL_DS . $file)) {
                        $files_exist = false;
                        $missed_files .= '/plugins/' . $this->key . '/<b>' . $file . '</b><br />';
                    }
                }

                // Activate plugin
                if ($files_exist === true) {
                    $tables = array('lang_keys', 'hooks', 'blocks', 'admin_blocks', 'pages', 'email_templates');

                    foreach ($tables as $table) {
                        unset($update);
                        $update = array(
                            'fields' => array(
                                'Status' => 'active',
                            ),
                            'where'  => array(
                                'Plugin' => $this->key,
                            ),
                        );
                        $rlDb->updateOne($update, $table);
                    }

                    unset($update);
                    $update = array(
                        'fields' => array(
                            'Status' => 'active',
                        ),
                        'where'  => array(
                            'Key' => $this->key,
                        ),
                    );
                    $rlDb->updateOne($update, 'plugins');

                    if ($this->notice || is_array($this->notices)) {
                        $post_notice = is_array($this->notices) ? $this->notices[0]['Content'] : $this->notice;

                        if (RL_LANG_CODE !== 'en'
                            && $locales[RL_LANG_CODE]
                            && $locales[RL_LANG_CODE]['notice_' . $this->key . '_1']
                        ) {
                            $post_notice = $locales[RL_LANG_CODE]['notice_' . $this->key . '_1'];
                        }

                        $post_install_notice = "<br /><b>" . $lang['notice'] . ":</b> " . $post_notice;
                    }
                    $out['notice'] = $rlLang->getSystem('notice_plugin_installed') . $post_install_notice;

                    // Define menu item data
                    if ($this->controller) {
                        $out['menu'] = [
                            'key' => $this->key,
                            'controller' => $this->controller,
                            'title' => $pluginTitle,
                        ];
                    }
                } else {
                    return array(
                        'status' => 'ERROR',
                        'message' => str_replace('{files}', "<br />" . $missed_files, $rlLang->getSystem('plugin_files_missed'))
                    );
                }
            } else {
                return array(
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('plugin_download_deny')
                );
                $rlDebug->logger("Can not install plugin (" . $this->title . "), insert command failed");
            }
        } else {
            return array(
                'status' => 'ERROR',
                'message' => $rlLang->getSystem('install_not_found')
            );
        }

        if ($remoteMode) {
            $out['phrase'] = array(
                'remote_progress_installation_completed' => $rlLang->getSystem('remote_progress_installation_completed'),
                'no_new_plugins' => $rlLang->getSystem('no_new_plugins')
            );
        }

        $out['status'] = 'OK';

        return $out;
    }

    /**
     * Update plugin
     *
     * @param string  $pluginKey  - Plugin key
     * @param boolian $remoteMode - Remote mode
     **/
    public function ajaxUpdate($pluginKey = false, $remoteMode = false)
    {
        global $rlLang, $languages, $rlDb, $reefless, $lang, $rlDebug;

        if (!$pluginKey) {
            return false;
        }

        // Create xajax fallback class
        global $_response;
        $_response = new xajaxFallback();

        // Create SMARTY fallback class
        global $rlSmarty;
        $rlSmarty = new smartyFallback();

        $out = [];

        if ($reefless->checkSessionExpire() === false) {
            return array(
                'status' => 'REDIRECT',
                'data' => 'session_expired'
            );
        }

        $current_version = $rlDb->getOne('Version', "`Key` = '{$pluginKey}'", 'plugins');

        $plugin_dir = RL_UPLOAD . $pluginKey . RL_DS;
        $path_to_update = $plugin_dir . 'install.xml';

        if (is_readable($path_to_update)) {
            require_once RL_LIBS . 'saxyParser' . RL_DS . 'xml_saxy_parser.php';

            $rlParser = new SAXY_Parser();
            $rlParser->xml_set_element_handler(array($this, 'startElement'), array($this, 'endElement'));
            $rlParser->xml_set_character_data_handler(array($this, 'charData'));
            $rlParser->xml_set_comment_handler(array($this, 'commentHandler'));

            // Parse xml file
            $rlParser->parse(file_get_contents($path_to_update));

            // Check compatibility with current version of the software
            if (!$this->checkCompatibilityByVersion($this->compatible)) {
                 return array(
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('plugin_not_compatible_notice')
                );
            }

            // Check custom changes in the plugin
            if ($rlDb->getOne('Custom', "`Key` = '{$pluginKey}'", 'plugins') === '1') {
                return [
                    'status'  => 'ERROR',
                    'message' => $rlLang->getSystem('deny_update_custom_plugin'),
                    'js'      => [
                        "$('#update_area, #search_area').slideUp('fast');",
                        "typeof xajax_getPluginsLog === 'function' ? xajax_getPluginsLog() : null;",
                    ],
                ];
            }

            $plugin = array(
                'fields' => array(
                    'Name'        => $this->title,
                    'Class'       => $this->uninstall['class'] ?: '',
                    'Description' => $this->description,
                    'Version'     => $this->version,
                    'Controller'  => $this->controller,
                    'Uninstall'   => $this->uninstall['code'],
                    'Files'       => serialize($this->files),
                ),
                'where'  => array(
                    'Key' => $this->key,
                ),
            );

            // Update plugin
            foreach ($this->updates as $update_index => $update_item) {
                $success = true;

                if (version_compare($update_item['Version'], $current_version) > 0) {
                    $lang_keys_insert = array();
                    $lang_keys_update = array();

                    $configs_insert = array();
                    $configs_update = array();

                    $update_item['Files'] = rtrim('install.xml,i18n/,' . $update_item['Files'], ',');

                    // Copy plugin files
                    foreach (explode(',', $update_item['Files']) as $update_file) {
                        $file_to_copy = trim($update_file);
                        $file_source = $plugin_dir . $file_to_copy;
                        $error_message = '';

                        // Skip 'i18n' directory if there is not changes in
                        if ($file_to_copy == 'i18n/' && !file_exists($file_source)) {
                            continue;
                        }

                        if (!file_exists($file_source)) {
                            $error_message = "The '/tmp/upload/{$pluginKey}/{$file_to_copy}' does not exist.";
                        } elseif (!is_writable($plugin_dir)) {
                            $error_message = "The '/plugins/{$pluginKey}/' directory is not writable.";
                        }

                        if ($error_message) {
                            $rlDebug->logger("Plugin updating: {$error_message}");
                            $success = false;
                            break;
                        }

                        $destination = RL_PLUGINS . $pluginKey . RL_DS . $file_to_copy;
                        $catchExceptionFunc = function (IOException $e) use (&$success, $pluginKey) {
                            $GLOBALS['rlDebug']->logger("
                                Plugin updating: Thrown exception '{$e->getMessage()}' in {$pluginKey} plugin.
                            ");
                            $success = false;
                        };
                        $options = ['override' => true];

                        $filesystem = new \Flynax\Component\Filesystem();
                        $filesystem->copyTo($file_source, $destination, $catchExceptionFunc, $options);
                    }

                    if ($success) {
                        // Update phrases
                        $phrases = $this->phrases;

                        foreach ($languages as $language) {
                            $locales[$language['Code']] = $locale = $this->getLanguagePhrases($language['Code'], $this->key);

                            if ($phrases) {
                                foreach ($phrases as $phrase) {
                                    if (version_compare($phrase['Version'], $update_item['Version'], '!=')) {
                                        continue;
                                    }

                                    if ($phrase['Module'] == 'ext') {
                                        $phrase['Module'] = 'admin';
                                        $phrase['JS']     = '1';
                                    }

                                    $phrase_value = $locale[$phrase['Key']] ?: $phrase['Value'];
                                    $exist_phrase = $rlDb->fetch(
                                        '*',
                                        ['Key'  => $phrase['Key'], 'Code' => $language['Code']],
                                        null, 1, 'lang_keys', 'row'
                                    );

                                    if ($exist_phrase) {
                                        $update['where']['ID'] = $exist_phrase['ID'];

                                        foreach ($exist_phrase as $column => $value) {
                                            if ($column === 'ID') {
                                                continue;
                                            }

                                            if ($column === 'Value') {
                                                if (!$exist_phrase['Modified']) {
                                                    $update['fields'][$column] = $phrase_value;
                                                }
                                            } else {
                                                $update['fields'][$column] = $phrase[$column] ?: $value;
                                            }
                                        }

                                        $lang_keys_update[] = $update;
                                    } else {
                                        // Insert
                                        $lang_keys_insert[] = array(
                                            'Code'       => $language['Code'],
                                            'Module'     => $phrase['Module'],
                                            'Key'        => $phrase['Key'],
                                            'Value'      => $phrase_value,
                                            'Plugin'     => $this->key,
                                            'JS'         => $phrase['JS'],
                                            'Target_key' => $phrase['Target_key'],
                                            'Status'     => 'active',
                                        );
                                    }
                                }
                            }
                        }

                        // Update hooks
                        $hooks = $this->hooks;
                        if (!empty($hooks)) {
                            foreach ($hooks as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version']) == 0) {
                                    $options = '';
                                    $where = array(
                                        'Name' => $value['Name'],
                                        'Plugin' => $this->key
                                    );

                                    if ($value['Class']) {
                                        $common_class = strval($this->class);
                                        $options = "AND (`Class` = '' OR `Class` IN ('{$value['Class']}','{$common_class}'))";
                                    }

                                    if ($hook_data = $rlDb->fetch(['ID', 'Class'], $where, $options, 1, 'hooks', 'row')) {
                                        $where['Class'] = $hook_data['Class'];

                                        $hook_update = array(
                                            'fields' => array(
                                                'Class' => $value['Class'],
                                                'Code'  => $value['Code'],
                                            ),
                                            'where'  => $where
                                        );

                                        $rlDb->updateOne($hook_update, 'hooks');
                                    } else {
                                        $hook_insert = $value;
                                        unset($hook_insert['Version']);
                                        $hook_insert['Status'] = 'active';

                                        $rlDb->insert($hook_insert, 'hooks');
                                    }
                                }
                            }
                        }

                        // Update configs' group
                        $cGroup = $configGroup = $this->configGroup;
                        if (!empty($configGroup)) {
                            if (version_compare($configGroup['Version'], $update_item['Version']) == 0) {
                                if ($rlDb->getOne('ID', "`Key` = '{$configGroup['Key']}' AND `Plugin` = '" . $this->key . "'", 'config_groups')) {
                                    foreach ($languages as $language) {
                                        $locale    = $locales[$language['Code']];
                                        $group_key = 'config_groups+name+' . $configGroup['Key'];

                                        $lang_keys_update[] = array(
                                            'fields' => array(
                                                'Value'      => $locale[$group_key] ?: $configGroup['Name'],
                                                'Target_key' => 'settings',
                                            ),
                                            'where'  => array(
                                                'Code' => $language['Code'],
                                                'Key'  => $group_key,
                                            ),
                                        );
                                    }
                                } else {
                                    $cg_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}config_groups` LIMIT 1");
                                    unset($cGroup['Name']);
                                    unset($cGroup['Version']);
                                    $cGroup['Position'] = $cg_max_poss['max'] + 1;

                                    $rlDb->insertOne($cGroup, 'config_groups');
                                    $group_id = $rlDb->insertID();

                                    foreach ($languages as $language) {
                                        $locale    = $locales[$language['Code']];
                                        $group_key = 'config_groups+name+' . $configGroup['Key'];

                                        $lang_keys_insert[] = array(
                                            'Code'       => $language['Code'],
                                            'Module'     => 'admin',
                                            'Key'        => $group_key,
                                            'Value'      => $locale[$group_key] ?: $configGroup['Name'],
                                            'Plugin'     => $this->key,
                                            'Target_key' => 'settings',
                                            'Status'     => 'active',
                                        );
                                    }
                                }
                            }
                        }

                        $group_id = empty($group_id) ? 0 : $group_id;

                        // Update configs
                        $configs = $this->configs;
                        if (!empty($configs)) {
                            foreach ($configs as $key => $value) {
                                $name_key = 'config+name+' . $value['Key'];
                                $des_key  = 'config+des+' . $value['Key'];

                                if (version_compare($value['Version'], $update_item['Version'], '!=')) {
                                    continue;
                                }

                                if ($rlDb->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'config')) {
                                    // Update
                                    $configs_update[] = array(
                                        'fields' => array(
                                            'Default'   => $value['Default'],
                                            'Values'    => $value['Values'],
                                            'Type'      => $value['Type'],
                                            'Data_type' => $value['Data_type'],
                                        ),
                                        'where'  => array(
                                            'Key'    => $value['Key'],
                                            'Plugin' => $this->key,
                                        ),
                                    );

                                    foreach ($languages as $language) {
                                        $locale       = $locales[$language['Code']];
                                        $phrase_name  = $locale[$name_key] ?: $value['Name'];
                                        $exist_phrase = $rlDb->fetch(
                                            array('Modified', 'Value'),
                                            array(
                                                'Key'  => $name_key,
                                                'Code' => $language['Code']
                                            ),
                                            null, 1, 'lang_keys', 'row'
                                        );

                                        if ($exist_phrase) {
                                            if ($language['Code'] == $GLOBALS['config']['lang']
                                                || (
                                                    $language['Code'] != $GLOBALS['config']['lang']
                                                    && !$exist_phrase['Modified']
                                                    && $exist_phrase['Value'] != $phrase_name
                                                )
                                            ) {
                                                $lang_keys_update[] = array(
                                                    'fields' => array(
                                                        'Value'      => $phrase_name,
                                                        'Target_key' => 'settings',
                                                    ),
                                                    'where'  => array(
                                                        'Code' => $language['Code'],
                                                        'Key'  => $name_key,
                                                    ),
                                                );
                                            }
                                        } else {
                                            $lang_keys_insert[] = array(
                                                'Code'       => $language['Code'],
                                                'Module'     => 'admin',
                                                'Key'        => $name_key,
                                                'Value'      => $phrase_name,
                                                'Plugin'     => $this->key,
                                                'Target_key' => 'settings',
                                                'Status'     => 'active',
                                            );
                                        }

                                        if (!empty($value['Description'])) {
                                            if (!$rlDb->getOne('ID', "`Key` = 'config+des+{$value['Key']}' AND `Code` = '{$language['Code']}'", 'lang_keys')) {
                                                $lang_keys_insert[] = array(
                                                    'Code'       => $language['Code'],
                                                    'Module'     => 'admin',
                                                    'Key'        => 'config+des+' . $value['Key'],
                                                    'Value'      => $value['Description'],
                                                    'Plugin'     => $this->key,
                                                    'Target_key' => 'settings',
                                                    'Status'     => 'active',
                                                );
                                            }
                                        }
                                    }
                                } else {
                                    // Insert
                                    foreach ($languages as $language) {
                                        $locale = $locales[$language['Code']];

                                        $lang_keys_insert[] = array(
                                            'Code'       => $language['Code'],
                                            'Module'     => 'admin',
                                            'Key'        => $name_key,
                                            'Value'      => $locale[$name_key] ?: $value['Name'],
                                            'Plugin'     => $this->key,
                                            'Target_key' => 'settings',
                                            'Status'     => 'active',
                                        );

                                        if (!empty($value['Description'])) {
                                            $lang_keys_insert[] = array(
                                                'Code'       => $language['Code'],
                                                'Module'     => 'admin',
                                                'Key'        => $des_key,
                                                'Value'      => $locale[$des_key] ?: $value['Description'],
                                                'Plugin'     => $this->key,
                                                'Target_key' => 'settings',
                                                'Status'     => 'active',
                                            );
                                        }
                                    }
                                    $position = $key;

                                    if ($configs[$key]['Group']) {
                                        $max_pos = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}config` WHERE `Group_ID` = '{$value['Group']}' LIMIT 1");
                                        $position = $max_pos['Max'] + $key;
                                    }

                                    if ($configGroup['Key']) {
                                        $group_id = $rlDb->getOne('ID', "`Key` = '{$configGroup['Key']}' AND `Plugin` = '" . $this->key . "'", 'config_groups');
                                    }

                                    $configs_insert[] = array(
                                        'Group_ID'  => !$group_id ? $value['Group'] : $group_id,
                                        'Position'  => $position,
                                        'Key'       => $value['Key'],
                                        'Default'   => $value['Default'],
                                        'Values'    => $value['Values'],
                                        'Type'      => $value['Type'],
                                        'Data_type' => $value['Data_type'],
                                        'Plugin'    => $this->key,
                                    );
                                }
                            }

                            if (!empty($configs_update)) {
                                $rlDb->update($configs_update, 'config');
                            }

                            if (!empty($configs_insert)) {
                                $rlDb->insert($configs_insert, 'config');
                            }
                        }

                        // Update blocks
                        $blocks = $this->blocks;
                        if (!empty($blocks)) {
                            foreach ($blocks as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version'], '!=')) {
                                    continue;
                                }

                                if (in_array(strtolower($value['Type']), array('html', 'php', 'smarty'))) {
                                    if ($rlDb->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'blocks')) {
                                        // Update
                                        $block_update = array(
                                            'fields' => array(
                                                'Type'     => $value['Type'],
                                                'Content'  => $value['Content'],
                                                'Readonly' => $value['Readonly'],
                                            ),
                                            'where'  => array(
                                                'Key'    => $value['Key'],
                                                'Plugin' => $this->key,
                                            ),
                                        );

                                        if (strtolower($value['Type']) == 'html') {
                                            unset($block_update['fields']['Content']);
                                        }

                                        $rlDb->updateOne($block_update, 'blocks');
                                    } else {
                                        $block_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks` LIMIT 1");
                                        $blocks[$key]['Position'] = $block_max_poss['max'] + 1;

                                        $name_key    = 'blocks+name+' . $value['Key'];
                                        $content_key = 'blocks+content+' . $value['Key'];

                                        // Add name phrases
                                        foreach ($languages as $language) {
                                            $locale = $locales[$language['Code']];

                                            $lang_keys_insert[] = array(
                                                'Code'       => $language['Code'],
                                                'Module'     => 'box',
                                                'Key'        => $name_key,
                                                'Value'      => $locale[$name_key] ?: $value['Name'],
                                                'Plugin'     => $this->key,
                                                'Target_key' => $value['Key'],
                                                'Status'     => 'active',
                                            );

                                            if (strtolower($value['Type']) == 'html') {
                                                $lang_keys_insert[] = array(
                                                    'Code'       => $language['Code'],
                                                    'Module'     => 'box',
                                                    'Key'        => $content_key,
                                                    'Value'      => $locale[$content_key] ?: $value['Content'],
                                                    'Plugin'     => $this->key,
                                                    'Target_key' => $value['Key'],
                                                    'Status'     => 'active',
                                                );
                                            }
                                        }

                                        if (strtolower($value['Type']) == 'html') {
                                            unset($blocks[$key]['Content']);
                                        }
                                        unset($blocks[$key]['Name']);
                                        unset($blocks[$key]['Version']);
                                        $blocks[$key]['Status'] = 'active';

                                        $rlDb->insertOne($blocks[$key], 'blocks');
                                    }
                                }
                            }
                        }

                        // Update admin panel blocks
                        $aBlocks = $this->aBlocks;
                        if ($aBlocks) {
                            if ($remoteMode) {
                                require_once RL_LIBS . 'smarty' . RL_DS . 'Smarty.class.php';
                                $reefless->loadClass('Smarty');
                            }

                            foreach ($aBlocks as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version'], '!=')) {
                                    continue;
                                }

                                if ($rlDb->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'admin_blocks')) {
                                    // Update
                                    $aBlock_update = array(
                                        'fields' => array(
                                            'Ajax'    => $value['Ajax'],
                                            'Content' => $value['Content'],
                                            'Fixed'   => $value['Fixed'],
                                        ),
                                        'where'  => array(
                                            'Key'    => $value['Key'],
                                            'Plugin' => $this->key,
                                        ),
                                    );

                                    $rlDb->updateOne($aBlock_update, 'admin_blocks');
                                } else {
                                    $aBlock_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}admin_blocks` WHERE `Column` = 'column{$value['Column']}' LIMIT 1");
                                    $aBlocks[$key]['Position'] = $aBlock_max_poss['max'] + 1;

                                    // Add name phrases
                                    foreach ($languages as $lkey => $lval) {
                                        $lang_keys_insert[] = array(
                                            'Code'       => $lval['Code'],
                                            'Module'     => 'admin',
                                            'Key'        => 'admin_blocks+name+' . $value['Key'],
                                            'Value'      => $value['Name'],
                                            'Plugin'     => $this->key,
                                            'Target_key' => 'home',
                                            'Status'     => 'active',
                                        );
                                    }

                                    $aBlocks[$key]['name'] = $aBlocks[$key]['Name'];
                                    $aBlocks[$key]['Column'] = 'column' . $aBlocks[$key]['Column'];
                                    $aBlocks[$key]['Status'] = 'active';
                                    unset($aBlocks[$key]['Name']);
                                    unset($aBlocks[$key]['name']);
                                    unset($aBlocks[$key]['Version']);

                                    $rlDb->insertOne($aBlocks[$key], 'admin_blocks');

                                    // Append new block
                                    if ($remoteMode) {
                                        $rlSmarty->assign('block', $aBlocks[$key]);

                                        $tpl = 'blocks' . RL_DS . 'homeDragDrop_block.tpl';
                                        $out['html'][] = array(
                                            'code' => $rlSmarty->fetch($tpl, null, null, false),
                                            'box'  => $value['Column'],
                                            'ajax' => $value['Ajax']
                                        );
                                    }
                                }
                            }
                        }

                        // Update pages
                        $pages = $this->pages;
                        if (!empty($pages)) {
                            foreach ($pages as $key => $value) {
                                $name_key    = 'pages+name+' . $value['Key'];
                                $content_key = 'pages+content+' . $value['Key'];

                                if (in_array($value['Page_type'], array('system', 'static', 'external'))) {
                                    if (version_compare($value['Version'], $update_item['Version'], '!=')) {
                                        continue;
                                    }

                                    if ($rlDb->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'pages')) {
                                        $page_update = array(
                                            'fields' => array(
                                                'Page_type'  => $value['Page_type'],
                                                'Get_vars'   => $value['Get_vars'],
                                                'Controller' => $value['Controller'],
                                                'Deny'       => $value['Deny'],
                                                'Tpl'        => $value['Tpl'],
                                                'Readonly'   => $value['Readonly'],
                                            ),
                                            'where'  => array(
                                                'Key'    => $key['Key'],
                                                'Plugin' => $this->key,
                                            ),
                                        );

                                        $rlDb->updateOne($page_update, 'pages');
                                    } else {
                                        $page_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}pages` LIMIT 1");
                                        $pages[$key]['Position'] = $page_max_poss['max'] + 1;

                                        // Add name phrases
                                        foreach ($languages as $language) {
                                            $locale = $locales[$language['Code']];

                                            $lang_keys_insert[] = array(
                                                'Code'   => $language['Code'],
                                                'Module' => 'common',
                                                'Key'    => $name_key,
                                                'Value'  => $locale[$name_key] ?: $value['Name'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );

                                            $lang_keys_insert[] = array(
                                                'Code'   => $language['Code'],
                                                'Module' => 'frontEnd',
                                                'Key'    => 'pages+title+' . $value['Key'],
                                                'Value'  => $locale[$name_key] ?: $value['Name'],
                                                'Plugin' => $this->key,
                                                'Status' => 'active',
                                            );

                                            if ($value['Page_type'] == 'static') {
                                                $lang_keys_insert[] = array(
                                                    'Code'       => $language['Code'],
                                                    'Module'     => 'frontEnd',
                                                    'Key'        => $content_key,
                                                    'Value'      => $locale[$content_key] ?: $value['Content'],
                                                    'Plugin'     => $this->key,
                                                    'Target_key' => $value['Page_type'] == 'static' ? 'static' : $pages[$key]['Controller'],
                                                    'Status'     => 'active',
                                                );
                                            }
                                        }

                                        switch ($value['Page_type']) {
                                            case 'system':
                                                $pages[$key]['Controller'] = $pages[$key]['Controller'];
                                                break;
                                            case 'static':
                                                $pages[$key]['Controller'] = 'static';
                                                break;
                                            case 'external':
                                                $pages[$key]['Controller'] = $pages[$key]['Content'];
                                                break;
                                        }

                                        unset($pages[$key]['Name']);
                                        unset($pages[$key]['Content']);
                                        unset($pages[$key]['Version']);
                                        $pages[$key]['status'] = 'active';

                                        $rlDb->insertOne($pages[$key], 'pages');
                                    }
                                }
                            }
                        }

                        // Update email templates
                        $emails = $this->emails;
                        if (!empty($emails)) {
                            foreach ($emails as $key => $value) {
                                if (version_compare($value['Version'], $update_item['Version'], '!=')) {
                                    continue;
                                }

                                $subject_key = 'email_templates+subject+' . $value['Key'];
                                $body_key    = 'email_templates+body+' . $value['Key'];

                                if (!$rlDb->getOne('ID', "`Key` = '{$value['Key']}' AND `Plugin` = '" . $this->key . "'", 'email_templates')) {
                                    $email_max_poss = $rlDb->getRow("SELECT MAX(`Position`) AS `max` FROM `{db_prefix}email_templates` LIMIT 1");
                                    $emails[$key]['Position'] = $email_max_poss['max'] + 1;

                                    foreach ($languages as $language) {
                                        $locale = $locales[$language['Code']];

                                        $lang_keys_insert[] = array(
                                            'Code'   => $language['Code'],
                                            'Module' => 'email_tpl',
                                            'Key'    => $subject_key,
                                            'Value'  => $locale[$subject_key] ?: $value['Subject'],
                                            'Plugin' => $this->key,
                                            'Status' => 'active',
                                        );
                                        $lang_keys_insert[] = array(
                                            'Code'   => $language['Code'],
                                            'Module' => 'email_tpl',
                                            'Key'    => $body_key,
                                            'Value'  => $locale[$body_key] ?: $value['Body'],
                                            'Plugin' => $this->key,
                                            'Status' => 'active',
                                        );
                                    }
                                    unset($emails[$key]['Subject']);
                                    unset($emails[$key]['Body']);
                                    unset($emails[$key]['Version']);
                                    $emails[$key]['Status'] = 'active';

                                    $rlDb->insertOne($emails[$key], 'email_templates');
                                }
                            }
                        }

                        /**
                         * @since 4.7.0 - Using PluginManager class instead of the internal method
                         * @since 4.6.0
                         */
                        try {
                            $instance = PluginManager::getPluginInstance($this->key, $update_item['Class']);

                            if ($instance && $instance instanceof PluginInterface) {
                                $instance->update($update_item['Version']);
                            } elseif ($update_item['Code'] !== '') {
                                @eval($update_item['Code']);
                            }
                        } catch (Exception $e) {
                            $rlDebug->logger($e->getMessage());
                        }

                        // Collect custom js code
                        $out['js'] = array_merge((array) $out['js'], $_response->get());

                        // Add phrases
                        if (!empty($lang_keys_insert)) {
                            $rlDb->insert($lang_keys_insert, 'lang_keys');
                        }

                        // Update phrases
                        if (!empty($lang_keys_update)) {
                            $rlDb->update($lang_keys_update, 'lang_keys');
                        }

                        $plugin_version_update = array(
                            'fields' => array(
                                'Version' => $update_item['Version'],
                            ),
                            'where'  => array(
                                'Key' => $this->key,
                            ),
                        );

                        $rlDb->updateOne($plugin_version_update, 'plugins');
                    }
                }
            }

            // Delete unzipped plugin from TMP
            $reefless->deleteDirectory(RL_UPLOAD . $this->key . RL_DS);

            if ($success && $rlDb->updateOne($plugin, 'plugins')) {
                $update_notice = $rlLang->getSystem('plugin_updated');

                // Print notices
                if (!empty($this->notices)) {
                    $pluginUpdateNotice = '';
                    foreach ($this->notices as $key => $value) {
                        if (version_compare($value['Version'], $current_version) > 0) {
                            $pluginUpdateNotice .= '<li style="list-style:initial"><b>' . $lang['notice'];
                            $pluginUpdateNotice .= " ({$lang['version']} {$value['Version']}):</b> ";

                            if (RL_LANG_CODE !== 'en'
                                && $locales[RL_LANG_CODE]
                                && $locales[RL_LANG_CODE]['notice_' . $this->key . '_' . ($key + 1)]
                            ) {
                                $pluginUpdateNotice .= $locales[RL_LANG_CODE]['notice_' . $this->key . '_' . ($key + 1)];
                            } else {
                                $pluginUpdateNotice .= $value['Content'];
                            }

                            $pluginUpdateNotice .= '</li>';
                        }
                    }
                    $update_notice .= $pluginUpdateNotice
                    ? "<br /><br /><ul>" . $pluginUpdateNotice . "</ul>"
                    : "";
                }

                $out['notice'] = $update_notice;

                // Define menu item data
                if ($this->controller && version_compare($this->controllerUpdate, $current_version) > 0) {
                    $out['menu'] = [
                        'key' => $this->key,
                        'controller' => $this->controller,
                        'title' => $this->title,
                    ];
                }
            } else {
                $rlDebug->logger("Cannot update plugin (" . $this->title . "), success variable returned FALSE.");
                return array(
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('install_fail_files_upload')
                );
            }
        } else {
            $rlDebug->logger("Cannot update plugin (" . $this->title . "), '{$path_to_update}' does not found.");
            return array(
                'status' => 'ERROR',
                'message' => $rlLang->getSystem('install_not_found')
            );
        }

        if ($remoteMode) {
            $out['phrase'] = array(
                'remote_progress_update_completed' => $rlLang->getSystem('remote_progress_update_completed')
            );
        }

        $out['status'] = 'OK';

        return $out;
    }

    public function startElement($parser, $name, $attributes)
    {
        global $rlPlugin;

        $rlPlugin->level++;
        $rlPlugin->inTag = $name;
        $rlPlugin->attributes = $attributes;

        if ($rlPlugin->inTag == 'plugin' && isset($attributes['name'])) {
            $rlPlugin->key = $attributes['name'];
        }

        $rlPlugin->path[] = $name;
    }

    public function endElement($parser, $name)
    {
        $GLOBALS['rlPlugin']->level--;
    }

    public function charData($parser, $text)
    {
        global $rlPlugin;

        switch ($rlPlugin->inTag) {
            case 'hook':
                $_class = strval($rlPlugin->attributes['class'] ?: $rlPlugin->class);

                $rlPlugin->hooks[] = array(
                    'Name'    => $rlPlugin->attributes['name'],
                    'Class'   => $_class,
                    'Version' => $rlPlugin->attributes['version'],
                    'Code'    => empty($_class) ? $text : '',
                    'Plugin'  => $rlPlugin->key,
                    'Status'  => 'approval',
                );

                if ($rlPlugin->noVersionTag) {
                    $itemIndex = count($rlPlugin->hooks) - 1;
                    unset($rlPlugin->hooks[$itemIndex]['Version']);
                }
                break;

            case 'phrase':
                $rlPlugin->phrases[] = array(
                    'Key'        => $rlPlugin->attributes['key'],
                    'Version'    => $rlPlugin->attributes['version'],
                    'Module'     => $rlPlugin->attributes['module'],
                    'JS'         => $rlPlugin->attributes['js'] ? '1' : '0',
                    'Target_key' => $rlPlugin->attributes['target'],
                    'Value'      => $text,
                );
                break;

            case 'configs':
                $rlPlugin->configGroup = array(
                    'Key'     => $rlPlugin->attributes['key'],
                    'Version' => $rlPlugin->attributes['version'],
                    'Name'    => $rlPlugin->attributes['name'],
                    'Plugin'  => $rlPlugin->key,
                );

                if ($rlPlugin->noVersionTag) {
                    unset($rlPlugin->configGroup['Version']);
                }
                break;

            case 'config':
                $rlPlugin->configs[] = array(
                    'Key'         => $rlPlugin->attributes['key'],
                    'Version'     => $rlPlugin->attributes['version'],
                    'Group'       => $rlPlugin->attributes['group'],
                    'Name'        => $rlPlugin->attributes['name'],
                    'Description' => $rlPlugin->attributes['description'],
                    'Default'     => $text,
                    'Values'      => $rlPlugin->attributes['values'],
                    'Type'        => $rlPlugin->attributes['type'],
                    'Data_type'   => $rlPlugin->attributes['validate'],
                    'Plugin'      => $rlPlugin->key,
                );
                break;

            case 'block':
                $rlPlugin->blocks[] = array(
                    'Key'      => $rlPlugin->attributes['key'],
                    'Version'  => $rlPlugin->attributes['version'],
                    'Name'     => $rlPlugin->attributes['name'],
                    'Side'     => $rlPlugin->attributes['side'],
                    'Type'     => $rlPlugin->attributes['type'],
                    'Readonly' => (isset($rlPlugin->attributes['lock']) && $rlPlugin->attributes['lock'] == 0) ? 0 : 1,
                    'Tpl'      => (int) $rlPlugin->attributes['tpl'],
                    'Content'  => $text,
                    'Plugin'   => $rlPlugin->key,
                    'Status'   => 'approval',
                    'Sticky'   => 1,
                    'Header'   => (isset($rlPlugin->attributes['header']) && $rlPlugin->attributes['header'] == '0') ? 0 : 1,
                );
                break;

            case 'aBlock':
                $rlPlugin->aBlocks[] = array(
                    'Key'     => $rlPlugin->attributes['key'],
                    'Version' => $rlPlugin->attributes['version'],
                    'Name'    => $rlPlugin->attributes['name'],
                    'Content' => $text,
                    'Plugin'  => $rlPlugin->key,
                    'Status'  => 'approval',
                    'Column'  => (int) $rlPlugin->attributes['column'],
                    'Ajax'    => (int) $rlPlugin->attributes['ajax'],
                    'Fixed'   => (int) $rlPlugin->attributes['fixed'],
                );
                break;

            case 'page':
                $rlPlugin->pages[] = array(
                    'Key'        => $rlPlugin->attributes['key'],
                    'Version'    => $rlPlugin->attributes['version'],
                    'Login'      => (int) $rlPlugin->attributes['login'],
                    'Name'       => $rlPlugin->attributes['name'],
                    'Page_type'  => $rlPlugin->attributes['type'],
                    'Path'       => $rlPlugin->attributes['path'],
                    'Get_vars'   => $rlPlugin->attributes['get'],
                    'Controller' => $rlPlugin->attributes['controller'],
                    'Menus'      => $rlPlugin->attributes['menus'],
                    'Tpl'        => (int) $rlPlugin->attributes['tpl'],
                    'Content'    => $text,
                    'Plugin'     => $rlPlugin->key,
                );
                break;

            case 'email':
                $is_valid_type = in_array($rlPlugin->attributes['type'], array('plain', 'html'));

                $rlPlugin->emails[] = array(
                    'Key'     => $rlPlugin->attributes['key'],
                    'Type'    => $is_valid_type ? $rlPlugin->attributes['type'] : 'plain',
                    'Version' => $rlPlugin->attributes['version'],
                    'Subject' => $rlPlugin->attributes['subject'],
                    'Body'    => $text,
                    'Plugin'  => $rlPlugin->key,
                );
                break;

            case 'update':
                $_class = strval($rlPlugin->attributes['class'] ?: $rlPlugin->class);

                $rlPlugin->updates[] = array(
                    'Version' => $rlPlugin->attributes['version'],
                    'Files'   => $rlPlugin->attributes['files'],
                    'Class'   => $_class,
                    'Code'    => $text,
                );
                break;

            case 'notice':
                $rlPlugin->notices[] = array(
                    'Version' => $rlPlugin->attributes['version'],
                    'Content' => $text,
                );
                break;

            case 'file';
                $rlPlugin->files[] = $text;
                break;

            case 'install':
            case 'uninstall':
                $_class = strval($rlPlugin->attributes['class'] ?: $rlPlugin->class);

                $rlPlugin->{$rlPlugin->inTag} = array(
                    'class' => $_class ?: false,
                    'code'  => $text,
                );
                break;

            case 'version':
            case 'date':
            case 'class':
            case 'title':
            case 'description':
            case 'author':
            case 'owner':
            case 'controller':
                $rlPlugin->controllerUpdate = $rlPlugin->attributes['version'];
            case 'notice':
            case 'compatible':
                $rlPlugin->{$rlPlugin->inTag} = $text;
                break;
        }
    }

    public function commentHandler($parser, $comment)
    {}

    /**
     * Uninstall plugin
     *
     * @package xAjax
     *
     * @param  string $plugin_key
     * @return object
     */
    public function ajaxUnInstall($plugin_key)
    {
        global $_response, $lang, $rlValid, $rlDb;

        $rlValid->sql($plugin_key);

        $plugin_info = $rlDb->getRow("
            SELECT `Class`, `Uninstall` FROM `{db_prefix}plugins` WHERE `Key` = '{$plugin_key}'
        ");

        $tables = array(
            'lang_keys',
            'hooks',
            'config',
            'config_groups',
            'blocks',
            'admin_blocks',
            'pages',
            'email_templates',
        );
        foreach ($tables as $table) {
            $rlDb->query("DELETE FROM `{db_prefix}{$table}` WHERE `Plugin` = '{$plugin_key}'");
        }
        $rlDb->query("DELETE FROM `{db_prefix}plugins` WHERE `Key` = '{$plugin_key}'");

        /**
         * @since 4.7.0 - Using PluginManager class instead of the internal method
         * @since 4.6.0
         */
        try {
            $instance = PluginManager::getPluginInstance($plugin_key, $plugin_info['Class']);

            if ($instance && $instance instanceof PluginInterface) {
                $instance->uninstall();
            } elseif ($plugin_info['Uninstall'] !== '') {
                @eval($plugin_info['Uninstall']);
            }
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger($e->getMessage());
        }

        // Reload grid
        $_response->script('pluginsGrid.reload();');
        $_response->script("printMessage('notice', '{$lang['notice_plugin_uninstalled']}');");

        // Remove menu item
        $_response->script("
            $('#mPlugin_{$plugin_key}').remove();
            apMenu['plugins']['{$plugin_key}'] = false;
        ");

        return $_response;
    }

    /**
     * Remote plugin installation
     *
     * @param string $key - Plugin key
     */
    public function ajaxRemoteInstall($key = false)
    {
        global $lang, $reefless, $rlLang, $rlDebug;

        if (!$key) {
            return false;
        }

        $out = [];

        if ($reefless->checkSessionExpire() === false) {
            return array(
                'status' => 'REDIRECT',
                'data' => 'session_expired'
            );
        }

        @eval(base64_decode(RL_SETUP));

        if ($key && $license_domain && $license_number) {
            $destination = RL_PLUGINS . $key . '.zip';
            $copy = "https://www.flynax.com/_request/remote-plugin-upload.php";
            $copy .= "?key={$key}";
            $copy .= "&domain={$license_domain}";
            $copy .= "&license={$license_number}";
            $copy .= "&software={$GLOBALS['config']['rl_version']}";
            $copy .= '&php=' . phpversion();
            $target = RL_PLUGINS . $key . '/';

            // Copy remote file
            if ($reefless->copyRemoteFile($copy, $destination)) {
                $reefless->rlChmod($destination);

                if (is_readable($destination)) {
                    Archive::unpack($destination, $target);

                    if (is_readable("{$target}install.xml")) {
                        return array(
                            'status' => 'OK'
                        );
                    } else {
                        $rlDebug->logger('Unable to use remote plugin downloading wizard, downloading/extracting file fail.');
                        return array(
                            'status' => 'ERROR',
                            'message' => $rlLang->getSystem('plugin_download_fail')
                        );
                    }
                } else {
                    $rlDebug->logger('Unable to use remote plugin downloading wizard, downloading/extracting file fail.');
                    return array(
                        'status' => 'ERROR',
                        'message' => $rlLang->getSystem('plugin_download_fail')
                    );
                }
            } else {
                $rlDebug->logger('Unable to use remote plugin downloading wizard, connect fail.');
                return array(
                    'status' => 'ERROR',
                    'message' => $lang['flynax_connect_fail']
                );
            }
        } else {
            $rlDebug->logger('Unable to use remote plugin downloading wizard, license conflict.');
            return array(
                'status' => 'ERROR',
                'message' => $lang['plugin_download_deny']
            );
        }
    }

    /**
     * Remote plugin update
     *
     * @param string  $key - plugin key
     **/
    public function ajaxRemoteUpdate($key = false)
    {
        global $lang, $reefless, $rlLang, $rlDebug, $rlDb;

        if (!$key) {
            return false;
        }

        $out = [];

        if ($reefless->checkSessionExpire() === false) {
            return array(
                'status' => 'REDIRECT',
                'data' => 'session_expired'
            );
        }

        @eval(base64_decode(RL_SETUP));

        if ($license_domain && $license_number) {
            // Get plugin info
            $plugin = $rlDb->fetch(array('Version'), array('Key' => $key), null, 1, 'plugins', 'row');

            if (is_writable(RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS)) {
                // Backup current plugin version
                $source  = RL_PLUGINS . $key . RL_DS;
                $archive = RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS . $key;
                $archive .= "({$plugin['Version']})_" . date('d.m.Y') . '.zip';

                Archive::pack($source, $archive);

                // Backup hooks
                $rlDb->setTable('hooks');
                $backup_hooks = $rlDb->fetch(array('Name', 'Code'), array('Plugin' => $key));
                if ($backup_hooks) {
                    foreach ($backup_hooks as $index => $backup_hook) {
                        $file_content .= <<< VS
{$backup_hook['Name']}\r\n{$backup_hook['Code']}\r\n\r\n
VS;
                    }

                    $hooks_backup_path = RL_ROOT . 'backup' . RL_DS . 'plugins' . RL_DS . $key . "({$plugin['Version']})_" . date('d.m.Y') . ".txt";
                    $file = fopen($hooks_backup_path, 'w+');

                    fwrite($file, $file_content);
                    fclose($file);
                }

                $destination = RL_UPLOAD . $key . '.zip';
                $copy = "https://www.flynax.com/_request/remote-plugin-upload.php";
                $copy .= "?key={$key}";
                $copy .= "&domain={$license_domain}";
                $copy .= "&license={$license_number}";
                $copy .= "&software={$GLOBALS['config']['rl_version']}";
                $copy .= '&php=' . phpversion();
                $target = RL_UPLOAD . $key . '/';

                // Copy remote file
                if ($reefless->copyRemoteFile($copy, $destination)) {
                    $reefless->rlChmod($destination);

                    if (is_readable($destination)) {
                        Archive::unpack($destination, $target);

                        if (is_readable("{$target}install.xml")) {
                            return array(
                                'status' => 'OK'
                            );
                        } else {
                            $rlDebug->logger('Unable to use remote plugin downloading wizard, downloading/extracting file fail.');
                            return array(
                                'status' => 'ERROR',
                                'message' => $rlLang->getSystem('plugin_download_fail')
                            );
                        }
                    } else {
                        $rlDebug->logger('Unable to use remote plugin downloading wizard, downloading/extracting file fail.');
                        return array(
                            'status' => 'ERROR',
                            'message' => $rlLang->getSystem('plugin_download_fail'),
                            'reload_log' => 1
                        );
                    }
                } else {
                    if ($http_response_header[0] == 'HTTP/1.1 403 Forbidden') {
                        return array(
                            'status' => 'ERROR',
                            'message' => $rlLang->getSystem('plugin_denied')
                        );
                    } else {
                        $rlDebug->logger('Unable to use remote plugin downloading wizard, connect fail.');
                        return array(
                            'status' => 'ERROR',
                            'message' => $lang['flynax_connect_fail']
                        );
                    }
                }
            } else {
                $rlDebug->logger('Unable to backup current plugin version.');
                return array(
                    'status' => 'ERROR',
                    'message' => $rlLang->getSystem('plugin_backingup_deny'),
                    'reload_log' => 1
                );
            }
        } else {
            $rlDebug->logger('Unable to use remote plugin downloading wizard, license conflict.');
            return array(
                'status' => 'ERROR',
                'message' => $lang['plugin_download_deny']
            );
        }
    }

    /**
     * browse plugins
     *
     * @package xAjax
     *
     **/
    public function ajaxBrowsePlugins()
    {
        global $_response, $config, $lang, $rlSmarty, $reefless;

        // check admin session expire
        if ($reefless->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        /* scan plugins directory */
        $plugins_exist = $reefless->scanDir(RL_PLUGINS, true);

        /*
         * get available plugins
         * YOU ARE NOT PERMITTED TO MODIFY THE CODE BELOW
         */
        @eval(base64_decode(RL_SETUP));
        $feed_url = $config['flynax_plugins_browse_feed'] . '?domain=' . $license_domain . '&license=' . $license_number;
        $feed_url .= '&software=' . $config['rl_version'] . '&php=' . phpversion();
        $xml = $reefless->getPageContent($feed_url);
        /* END CODE */

        $reefless->loadClass('Rss');
        $GLOBALS['rlRss']->items_number = 200;
        $GLOBALS['rlRss']->items = array('key', 'path', 'name', 'version', 'date', 'paid', 'compatible');
        $GLOBALS['rlRss']->createParser($xml);
        $plugins = $GLOBALS['rlRss']->getRssContent();

        if (!$plugins) {
            $fail_msg = strpos($xml, 'access_forbidden') ? $lang['flynax_connect_forbidden'] : $lang['flynax_connect_fail'];
            $_response->script("
                printMessage('error', '{$fail_msg}');
                $('.button_bar > #browse_plugins').html('{$lang['browse']}');
            ");
            return $_response;
        }

        foreach ($plugins as $key => $plugin) {
            if (is_numeric(array_search($plugin['key'], $plugins_exist))) {
                unset($plugins[$key]);
            }
        }

        if (count($plugins)) {
            $rlSmarty->assign_by_ref('plugins', $plugins);
            // build DOM
            $tpl = 'blocks' . RL_DS . 'flynaxPluginsBrowse.block.tpl';
            $_response->assign('browse_content', 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));
        } else {
            $no_new_plugins = $GLOBALS['rlLang']->getSystem('no_new_plugins');
            $_response->script("$('#browse_content').html(\"{$no_new_plugins}\");");
        }

        $_response->script("
            $('#update_area, #search_area').slideUp('fast');
            $('#browse_area').slideDown('normal');
            $('.button_bar > #browse_plugins').html('{$lang['more_plugins']}');
            plugins_loaded = true;
        ");

        $_response->call('rlPluginRemoteInstall');

        return $_response;
    }

    /**
     * Checking compatibility between version of the plugin and version of the software
     *
     * @since 4.6.0
     *
     * @param  string $plugin - Key of plugin which need be checked
     * @return bool
     */
    public function checkCompatibility($plugin)
    {
        $compatible = $this->getXmlData($plugin, ['compatible'])['compatible'];
        return $this->checkCompatibilityByVersion($compatible);
    }

    /**
     * Get XML data of plugin by provided key
     *
     * @since 4.8.0
     *
     * @param  string $key
     * @param  array  $tags - List of tags which must be provided from plugin
     * @return mixed
     */
    public function getXmlData($key = '', $tags = ['title', 'description', 'version', 'compatible'])
    {
        $key  = (string) $key;
        $data = false;

        if (!$key || !$tags || !is_readable($installXmlFile = RL_PLUGINS . $key . '/install.xml')) {
            return $data;
        }

        require_once RL_LIBS . 'saxyParser/xml_saxy_parser.php';
        $rlParser = new SAXY_Parser();
        $rlParser->xml_set_element_handler([&$this, 'startElement'], [&$this, 'endElement']);
        $rlParser->xml_set_character_data_handler([&$this, 'charData']);
        $rlParser->xml_set_comment_handler([&$this, 'commentHandler']);
        $rlParser->parse(file_get_contents($installXmlFile));

        if (in_array('title', $tags) || in_array('description', $tags)) {
            if (is_readable($translationFile = RL_PLUGINS . $key . '/i18n/' . RL_LANG_CODE . '.json')) {
                $translation = json_decode(file_get_contents($translationFile), true);
            }
        }

        foreach ($tags as $tag) {
            $data[$tag] = $translation[$tag . '_' . $key] ?: ($this->$tag ?: '');
            unset($this->$tag);
        }

        return $data;
    }

    /**
     * Compare needed version and version of the software
     *
     * @since 4.6.0
     *
     * @param  string $version - Version of the plugin
     * @return bool
     */
    public function checkCompatibilityByVersion($version)
    {
        if ($version && version_compare($version, $GLOBALS['config']['rl_version']) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get phrases from the localization file
     *
     * @since 4.8.1
     *
     * @param  string $code      - Language code
     * @param  string $pluginKey - Plugin key
     * @return array|bool        - Phrases array as key => phrases or false
     */
    public function getLanguagePhrases($code, $pluginKey)
    {
        $code = strtolower($code);
        $file = RL_PLUGINS . $pluginKey . RL_DS . 'i18n' . RL_DS . $code . '.json';

        if (file_exists($file) && $phrases = file_get_contents($file)) {
            return json_decode($phrases, true);
        }

        return false;
    }
}
