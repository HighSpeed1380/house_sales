<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLMULTIFIELDAP.CLASS.PHP
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

use Flynax\Utils\Valid;

class rlMultiFieldAP
{
    /**
     * Admin directory path
     *
     * @since 2.0.0
     * @var string
     */
    public $adminDir = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS;

    /**
     * Controllers of pages allowed for geo filtering
     *
     * @since 2.0.0
     * @var array
     */
    private $availableControllers = array(
        'home',
        'listing_type',
        'account_type',
        'recently_added',
    );

    /**
     * Controllers of pages allowed for location fields predefine
     *
     * @since 2.0.0
     * @var array
     */
    private $predefineControllers = array(
        'search',
        'search_map',
        'add_listing',
        'registration',
    );

    /**
     * Format data lang table
     *
     * @since 2.2.0
     * @var string
     */
    public $formatLangTable = null;

    /**
     * Multifield format keys
     *
     * @since 2.2.0
     * @var array
     */
    public $formatKeys = [];

    /**
     * Geo format data
     *
     * @since 2.2.1
     * @var array
     */
    public $geoFormatData = null;

    /**
     * Class constructor
     *
     * @since 2.2.0
     */
    public function __construct()
    {
        global $config;

        $this->formatLangTable = 'multi_formats_lang_' . RL_LANG_CODE;

        if ($config['mf_format_keys']) {
            $this->formatKeys = explode('|', $config['mf_format_keys']);
        }

        if ($config['mf_geo_data_format']) {
            $this->geoFormatData = json_decode($config['mf_geo_data_format'], true);
        }
    }

    /**
     * @hook apTplFieldsFormBottom
     * @since 2.0.0
     */
    public function hookApTplFieldsFormBottom()
    {
        if ($GLOBALS['disable_condition']) {
            echo '<script type="text/javascript">$(document).ready(function(){';
            echo "$('#dd_select_block').attr('disabled', 'disabled').addClass('disabled');";
            echo "$('#dd_select_block').after('<input ";
            echo 'type="hidden" name="data_format" value="' . $GLOBALS['disable_condition']['Condition'] . '"';
            echo "/>');";
            echo '})</script>';
        }
    }

    /**
     * @hook apPhpAccountFieldsTop
     * @since 2.0.0
     */
    public function hookApPhpAccountFieldsTop()
    {
        global $disable_condition;

        $sql = "SELECT `T1`.`Condition` FROM `{db_prefix}account_fields` AS `T1` ";
        $sql .= "JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Condition` ";
        $sql .= "WHERE `T1`.`Key` = '" . $_GET['field'] . "' AND `T1`.`Key` REGEXP 'level[0-9]'";

        $disable_condition = $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * @hook apPhpListingFieldsBeforeEdit
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsBeforeEdit()
    {
        global $f_data, $rlDb;

        $f_data['Key'] = $GLOBALS['e_key'];

        $current_format = $rlDb->getOne("Condition", "`Key` = '" . $f_data['Key'] . "'", 'listing_fields');

        $old_multi = $rlDb->getOne("Key", "`Key` = '" . $current_format . "'", 'multi_formats');
        $new_multi = $rlDb->getOne("Key", "`Key` = '" . $f_data['data_format'] . "'", 'multi_formats');

        if ($new_multi) {
            $this->addIndexOnField($f_data['Key'], 'listings');
        }

        if ($new_multi && !$old_multi) {
            $this->createSubFields($f_data, 'listing');
        } elseif ($old_multi && !$new_multi) {
            $this->deleteSubFields($f_data, 'listing');
        } elseif ($old_multi && $new_multi && $old_multi != $new_multi) {
            $this->deleteSubFields($f_data, 'listing');
            $this->createSubFields($f_data, 'listing');
        }
    }

    /**
     * @hook apPhpAccountFieldsBeforeEdit
     * @since 2.0.0
     */
    public function hookApPhpAccountFieldsBeforeEdit()
    {
        global $f_data, $rlDb;

        $f_data['Key'] = $GLOBALS['e_key'];

        $current_format = $rlDb->getOne("Condition", "`Key` = '" . $f_data['Key'] . "'", 'account_fields');

        $old_multi = $rlDb->getOne("Key", "`Key` = '" . $current_format . "'", 'multi_formats');
        $new_multi = $rlDb->getOne("Key", "`Key` = '" . $f_data['data_format'] . "'", 'multi_formats');

        if ($new_multi) {
            $this->addIndexOnField($f_data['Key'], 'accounts');
        }

        if ($new_multi && !$old_multi) {
            $this->createSubFields($f_data, 'account');
        } elseif ($old_multi && !$new_multi) {
            $this->deleteSubFields($f_data, 'account');
        } elseif ($old_multi && $new_multi && $old_multi != $new_multi) {
            $this->deleteSubFields($f_data, 'account');
            $this->createSubFields($f_data, 'account');
        }
    }

    /**
     * @hook apPhpFieldsAjaxDeleteAField
     * @since 2.0.0
     */
    public function hookApPhpFieldsAjaxDeleteAField()
    {
        global $id;
        if ($id) {
            $key = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$id}", 'account_fields');
            $this->deleteFieldChildFields($key, 'account');
        }
    }

    /**
     * @hook apPhpListingFieldsTop
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsTop()
    {
        global $disable_condition;

        $sql = "SELECT `T1`.`Condition` FROM `{db_prefix}listing_fields` AS `T1` ";
        $sql .= "JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Condition` ";
        $sql .= "WHERE `T1`.`Key` = '" . $_GET['field'] . "' AND `T1`.`Key` REGEXP 'level[0-9]' ";

        $disable_condition = $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * @hook tplApPhpFieldsAjaxDeleteField
     * @since 2.0.0
     */
    public function hookApPhpFieldsAjaxDeleteField()
    {
        global $field;

        if (!$field['Key'] && $field['ID']) {
            $field['Key'] = $GLOBALS['rlDb']->getOne('Key', "`ID` = {$field['ID']}", 'listing_fields');
        }
        $this->deleteFieldChildFields($field['Key'], 'listing');
    }

    /**
     * @hook apPhpDataFormatsBottom
     * @since 2.0.0
     */
    public function hookApPhpDataFormatsBottom()
    {
        if ($_GET['mode'] == 'manage') {
            if ($id = $GLOBALS['rlDb']->getOne("ID", "`Key` = '{$_GET['format']}'", 'multi_formats')) {
                $GLOBALS['reefless']->redirect(array('controller' => 'multi_formats', 'parent' => $id));
            }
        }
    }

    /**
     * @hook apPhpDataFormatsAfterEdit
     * @since 2.2.1
     */
    public function hookApPhpDataFormatsAfterEdit()
    {
        global $f_key;

        if ($f_key == $this->geoFormatData['Key'] && $_POST['order_type'] != $this->geoFormatData['Order_type']) {
            $this->saveGeoFormatData();
        }
    }

    /**
     * @hook apExtDataFormatsUpdate
     * @since 2.2.1
     */
    public function hookApExtDataFormatsUpdate()
    {
        global $id, $field, $value, $rlDb;

        $key = $rlDb->getOne('Key', "`ID` = {$id}", 'data_formats');

        if ($key == $this->geoFormatData['Key'] && $field == 'Order_type' && $value != $this->geoFormatData['Order_type']) {
            // Force data update to allow saveGeoFormatData() method fetch actual data
            $updateData = array(  'fields' => array(
                    $field => $value,
                ),
                'where'  => array(
                    'ID' => $id,
                ),
            );
            $rlDb->updateOne($updateData, 'data_formats');

            $this->saveGeoFormatData();
        }
    }

    /**
     * @hook apPhpFormatsAjaxDeleteFormatPreDelete
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxDeleteFormatPreDelete()
    {
        global $rlDb;

        $format_id =  $GLOBALS['id'];
        $format_key = Valid::escape($_POST['xjxargs'][0]);

        if (!$rlDb->getOne('ID', "`Key` = '{$format_key}'", 'multi_formats')) {
            return;
        }

        $GLOBALS['config']['trash'] = false;

        $this->deleteDF($format_key);
    }

    /**
     * @hook apPhpFormatsAjaxDeleteFormat
     * @since 2.0.2
     */
    public function hookApPhpFormatsAjaxDeleteFormat()
    {
        global $rlDb;

        if (!$GLOBALS['config']['trash']) {
            return;
        }

        $format_id =  $GLOBALS['id'];
        $format_key = Valid::escape($_POST['xjxargs'][0]);

        if (!$rlDb->getOne('ID', "`Key` = '{$format_key}'", 'multi_formats')) {
            return;
        }

        $rlDb->updateOne(array(
            'fields' => array(
                'Zones' => 'data_formats,lang_keys,multi_formats'
            ),
            'where' => array(
                'Zones' => 'data_formats,lang_keys',
                'Key' => $format_key
            )
        ), 'trash_box');
    }

    /**
     * @hook apPhpAccountsTop
     * @since 2.0.0
     */
    public function hookApPhpAccountsTop()
    {
        $GLOBALS['rlSmarty']->assign('mf_form_prefix', $this->getPostPrefixByPageAp());
        $GLOBALS['rlSmarty']->assign('multi_format_keys', $this->formatKeys);
    }

    /**
     * @hook apPhpAccountsTop
     * @since 2.0.0
     */
    public function hookApPhpListingsTop()
    {
        $GLOBALS['rlSmarty']->assign('mf_form_prefix', $this->getPostPrefixByPageAp());
        $GLOBALS['rlSmarty']->assign('multi_format_keys', $this->formatKeys);
    }

    /**
     * @hook apTplHeader
     * @since 2.0.0
     */
    public function hookApTplHeader()
    {
        if (!$this->isPageMfAp()) {
            return false;
        }

        $GLOBALS['rlSmarty']->display($this->adminDir . 'tplHeader.tpl');
    }

    /**
     * @hook apPhpSubmitProfileEnd
     * @since 2.0.0
     */
    public function hookApPhpSubmitProfileEnd()
    {
        $fields = $GLOBALS['rlSmarty']->_tpl_vars['fields'];
        $js = '';

        foreach ($fields as $field) {
            if (in_array($field['Condition'], $this->formatKeys)) {
                $js .= <<< JAVASCRIPT
                if (mfFields.indexOf('{$field['Key']}') < 0) {
                    mfFields.push('{$field['Key']}');
                }
JAVASCRIPT;
            }
        }

        if ($js) {
            global $_response;

            $_response->script($js);
            $_response->script("
                var mfHandler = new mfHandlerClass();
                mfHandler.init('f', mfFields, []);
            ");
        }
    }

    /**
     * @hook apAjaxLangExportSelectPhrases - exclude multiField plugin from exporting to avoid many location data exported
     * @since 2.0.0
     *
     * @param   $select      - select fields array that will be passed to rlDb->fetch function
     * @param   $where       - where array to modify
     * @param   $extra_where - extra where string
     */
    public function hookApAjaxLangExportSelectPhrases($select = array(), $where = '', &$extra_where = '')
    {
        $extra_where .= "AND `Plugin` != 'multiField'";
    }

    /**
     * @hook apTplAccountFieldSelect
     * @since 2.0.0
     */
    public function hookApTplAccountFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield_account.tpl');

        $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
        $GLOBALS['rlMultiField']->getMissingFormatItemPhrases();
    }

    /**
     * @hook apTplListingFieldSelect
     * @since 2.0.0
     */
    public function hookApTplListingFieldSelect()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField' . RL_DS . 'mfield.tpl');

        $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
        $GLOBALS['rlMultiField']->getMissingFormatItemPhrases();
    }

    /**
     * @hook apPhpListingFieldsAfterAdd
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsAfterAdd()
    {
        global $f_data;

        $f_data['Key'] = $GLOBALS['f_key'];

        $this->createSubFields($f_data, 'listing');

        if ($_POST['data_format'] && in_array($_POST['data_format'], $this->formatKeys)) {
            $this->addIndexOnField($f_data['Key'], 'listings');
        }
    }

    /**
     * @hook apPhpAccountFieldsAfterAdd
     * @since 2.0.0
     */
    public function hookApPhpAccountFieldsAfterAdd()
    {
        global $f_data, $f_key;

        $f_data['Key'] = $f_key;

        $this->createSubFields($f_data, 'account');

        if ($_POST['data_format'] && in_array($_POST['data_format'], $this->formatKeys)) {
            $this->addIndexOnField($f_data['Key'], 'accounts');
        }
    }

    /**
     * @hook apTplFooter
     * @since 2.0.0
     */
    public function hookApTplFooter()
    {
        global $rlSmarty;

        if ($_GET['controller'] == 'settings') {
            $rlSmarty->display($this->adminDir . 'refreshEntry.tpl');
            $rlSmarty->display($this->adminDir . 'nearbyCheck.tpl');

            $rlSmarty->assign('mf_allow_subdomain', $this->isLocationOnSubdomainAllowed());
            $rlSmarty->assign('mf_geo_filter', $this->geoFilterEnabled());
            $rlSmarty->assign('mf_predefine_controllers', $this->predefineControllers);
            $rlSmarty->assign('mf_available_pages', $this->getAvailablePages());
            $rlSmarty->assign('mf_group_id', $GLOBALS['rlDb']->getOne('ID', "`Plugin` = 'multiField'", 'config_groups'));
            $rlSmarty->display($this->adminDir . 'settings.tpl');
        } elseif (in_array($_GET['controller'], ['listing_fields', 'account_fields']) && $_GET['action']) {
            if ($this->formatKeys) {
                $GLOBALS['rlSmarty']->assign('multi_format_keys', $this->formatKeys);
                $rlSmarty->display($this->adminDir . 'dataEntries.tpl');
            }

            // Restrict changing of the data format switching for field assigned to the Geo Filtering
            if ($_GET['controller'] == 'listing_fields'
                && $_GET['action'] == 'edit'
                && $this->geoFormatData['Key']
                && $this->geoFormatData['Key'] == $GLOBALS['field_info']['Condition']
            ) {
                echo <<< HTML
                    <script>
                    $(function(){
                        var \$field = \$('[name=data_format]');
                        \$field.attr('disabled', true);
                        \$field.next().text(lang.mf_geo_filter_field_restriction);

                        \$('<input>')
                            .attr('type', 'hidden')
                            .attr('name', 'data_format')
                            .val(\$field.val())
                            .appendTo(\$field.closest('td'));
                    });
                    </script>
HTML;
            }
        }

        if (!$this->isPageMFAp()) {
            return false;
        }

        $rlSmarty->display($this->adminDir . 'tplFooter.tpl');
    }

    /**
     * @hook apPhpConfigBeforeUpdate
     * @since 2.0.0
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $rlConfig;

        if (isset($_POST['a_config'])) {
            $filtration_page_keys = array();
            $location_url_keys = array();
            foreach ($_POST['mf_config'] as $mf_key => $mf_value) {
                if ($mf_value['filtration']) {
                    $filtration_page_keys[] = $mf_key;
                }
                if ($mf_value['url']) {
                    $location_url_keys[] = $mf_key;
                }
            }

            $rlConfig->setConfig('mf_filtering_pages', implode(',', $filtration_page_keys));
            $rlConfig->setConfig('mf_location_url_pages', implode(',', $location_url_keys));
        }
    }

    /**
     * Manage Path fields
     *
     * @hook apPhpConfigAfterUpdate
     * @since 2.1.0
     */
    public function hookApPhpConfigAfterUpdate()
    {
        global $config, $dConfig;

        // Manage path fields
        if ($config['mf_multilingual_path'] != $dConfig['mf_multilingual_path']['value']) {
            $this->managePathFields($dConfig['mf_multilingual_path']['value'] === '1' ? true : false);
        }

        // Manage location interface mode
        $mode = $GLOBALS['dConfig']['mf_select_interface']['value'];

        if ($GLOBALS['config']['mf_select_interface'] != $mode) {
            $update = array(
                'fields' => array(
                    'Status' => $mode == 'box' ? 'active' : 'trash'
                ),
                'where' => array(
                    'Key' => 'geo_filter_box'
                ),
            );
            $GLOBALS['rlDb']->updateOne($update, 'blocks');
        }
    }

    /**
     * Disable multilingual path option if just one languages is going to be left
     *
     * @hook apPhpIndexBottom
     *
     * @deprecated 2.5.4
     *
     * @since 2.1.0
     */
    public function hookApPhpIndexBottom()
    {}

    /**
     * Save import trigger in the session
     *
     * @hook apPhpIndexBeforeController
     *
     * @deprecated 2.5.4
     *
     * @since 2.2.0
     */
    public function hookApPhpIndexBeforeController()
    {}

    /**
     * Add new multilingual path field after new language imported
     *
     * @hook apPhpLanguageAfterImport
     * @since 2.1.0
     */
    public function hookApPhpLanguageAfterImport(&$code)
    {
        global $config, $rlDb;

        $this->createLangTable($code);

        if (!$config['mf_multilingual_path']) {
            return;
        }

        $where = "VARCHAR(255) NOT NULL AFTER `Path_{$config['lang']}`";
        $rlDb->addColumnToTable("Path_{$code}", $where, 'multi_formats');
        $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` ADD INDEX `Path_{$code}` (`Path_{$code}`)");
    }

    /**
     * @hook apPhpConfigBottom
     * @since 2.0.0
     */
    public function hookApPhpConfigBottom()
    {
        global $config, $rlSmarty;

        $rlSmarty->assign('mf_filtering_pages', explode(',', $config['mf_filtering_pages']));
        $rlSmarty->assign('mf_location_url_pages', explode(',', $config['mf_location_url_pages']));
    }

    /**
     * @hook apPhpCategoriesBottom
     * @since 2.0.0
     */
    public function hookApPhpCategoriesBottom()
    {
        if ($_GET['action'] != 'edit' || !$this->geoFilterEnabled()) {
            return;
        }

        global $fields, $rlDb;

        $format = $this->getGeoFilterFormat();

        $rlDb->outputRowsMap = array(false, 'Key');
        $format_fields = $rlDb->fetch(
            array('Key'),
            array('Condition' => $format['Key']),
            "ORDER BY `Key`",
            null, 'listing_fields'
        );

        if (!$format_fields) {
            return;
        }

        $replace  = 'if location_levelN}{location_levelN}{/if';
        $level    = 1;

        foreach ($fields as &$field) {
            if (in_array($field['Key'], $format_fields)) {
                $field['Key'] = str_replace('N', $level, $replace);
                $field['Order'] = $level + 1;
                $level++;
            } else {
                $field['Order'] = 0;
            }
        }

        // Add common location field
        $fields[] = array(
            'name' => $GLOBALS['lang']['mf_geo_location'],
            'Key' => 'if location}{location}{/if',
            'Order' => 1,
        );

        $GLOBALS['reefless']->rlArraySort($fields, 'Order');

        // Re-assign fields
        $GLOBALS['rlSmarty']->assign('fields', $fields);
    }

    /**
     * Add index on field if it does not exist yet
     *
     * @since 2.6.0
     *
     * @param string $key   - Field key
     * @param string $table - Table name to add index in
     */
    public function addIndexOnField($key, $table)
    {
        global $rlDb;

        if (!$rlDb->getRow("SHOW INDEXES FROM `{db_prefix}{$table}` WHERE `Column_name` = '{$key}'")) {
            $rlDb->query("ALTER TABLE `{db_prefix}{$table}` ADD INDEX (`{$key}`)");
        }
    }

    /**
     * Add page to the list of pages available for the data filtering
     * @since 2.0.0
     * @param string $controller - page controller key
     */
    public function addAvailablePage($controller)
    {
        $this->availableControllers[] = $controller;
    }

    /**
     * Get available geo filter data format data
     *
     * @since 2.0.0
     * @return array - format data
     */
    public function getGeoFilterFormat()
    {
        $sql = "
            SELECT *
            FROM `{db_prefix}multi_formats` AS `T1`
            WHERE `Geo_filter` = '1' AND `Status` = 'active' AND `Parent_ID` = 0
            LIMIT 1
        ";

        return $GLOBALS['rlDb']->getRow($sql);
    }

    /**
     * Get default page controllers list
     * @since 2.0.0
     * @return array - page controllers
     */
    public function getAvailableControllers()
    {
        return $this->availableControllers;
    }

    /**
     * Define is the "Location on subdomain" option allowed
     *
     * @since 2.1.0
     *
     * @return boolean
     */
    private function isLocationOnSubdomainAllowed()
    {
        $allowed = true;

        foreach ($GLOBALS['rlListingTypes']->types as $type) {
            if ($type['Links_type'] == 'subdomain') {
                $allowed = false;
                break;
            }
        }

        return $allowed;
    }

    /**
     * Get avaialble for the filtering pages list
     * @since 2.0.0
     * @return array - pages data
     */
    private function getAvailablePages()
    {
        $GLOBALS['rlHook']->load('apPhpMultifieldGetAvailablePages', $this);

        $order = array_merge($this->availableControllers, $this->predefineControllers);

        $sql = "
            SELECT `ID`, `Key`, `Controller` FROM `{db_prefix}pages`
            WHERE `Status` != 'trash'
            AND (
                `Controller` IN ('" . implode("','", $this->availableControllers) . "')
                OR (
                    `Controller` IN ('" . implode("','", $this->predefineControllers) . "')
                    AND `Plugin` = ''
                )
            )
            ORDER BY FIND_IN_SET(`Controller`, '" . implode(',', $order) . "')
        ";
        $pages = $GLOBALS['rlDb']->getAll($sql);

        return $pages;
    }

    /**
     * Defines if the multiField stack is allowed on the page
     *
     * @since 2.0.0
     *
     * @param  string $page_controller - Page controller key
     * @return bool                    - Succcess status
     */
    private function isPageMfAp($page_controller = false)
    {
        $page_controller = $page_controller ?: $_GET['controller'];

        if ((in_array($page_controller, array('listings', 'accounts'))
                && in_array($_GET['action'], array('add', 'edit'))
            ) || $page_controller === 'settings'
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * getPostPrefixByPage - return field inputs wrapper prefix f,account in admin panel
     * @param string $page_controller
     * @since 2.0.0
     */
    private function getPostPrefixByPageAp($page_controller = false)
    {
        $page_controller = $page_controller ?: $_GET['controller'];

        if (in_array($page_controller,
            array('listings'))) {
            return 'f';
        }

        if (in_array($page_controller, array('accounts'))) {
            return 'f';
        }
    }

    /**
     * get parents - get all parents of item
     *
     * @param string $key - key
     * @param array $parents - parents
     *
     * @return array
     **/
    public function getParents($key = false, $parents = false)
    {
        if (!$key) {
            return false;
        }

        $GLOBALS['rlValid']->sql($key);

        $sql = "SELECT `T2`.`Key`, `T2`.`Parent_ID` FROM `{db_prefix}data_formats` AS `T1` ";
        $sql .= "JOIN `{db_prefix}data_formats` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Key` = '{$key}' LIMIT 1";
        $parent = $GLOBALS['rlDb']->getRow($sql);

        if ($parent['Parent_ID'] == 0 && $parent['Key']) {
            return $parents;
        } else {
            $parents[] = $parent['Key'];
            return $this->getParents($parent['Key'], $parents);
        }
    }

    /**
     * get level of item
     *
     * @param int $id - id
     * @param int $level - level
     *
     * @return int
     **/
    public function getLevel($id, $level = 0)
    {
        $id = (int) $id;

        if (!$id) {
            return false;
        }

        $parent = $GLOBALS['rlDb']->getOne('Parent_ID', "`ID` = {$id}", "multi_formats");

        if ($parent) {
            $level++;
            return $this->getLevel($parent, $level);
        } else {
            return $level;
        }
    }

    /**
     * get total levels of the format
     *
     * @param int $id - id
     * @param int $levels - levels
     *
     * @return int
     **/
    public function getLevels($id, $update_db = false, $head_key = '')
    {
        global $rlDb;

        $head = $head_key ?: $this->getHead($id);

        if ($update_db) {
            $sql = "SELECT `ID` FROM `{db_prefix}multi_formats` ";
            $sql .= "WHERE `Key` LIKE '{$head}\_%' ORDER BY `ID` DESC LIMIT 1";

            $deepest_id = $rlDb->getRow($sql, 'ID');

            $levels = 0;
            while ($deepest_id && $levels < 10) {
                $deepest_id = $rlDb->getOne("Parent_ID", "`ID` = {$deepest_id}", "multi_formats");

                if ($deepest_id) {
                    $levels++;
                }
            }

            $sql = "UPDATE `{db_prefix}multi_formats` ";
            $sql .= "SET `Levels` = '{$levels}' WHERE `Key` = '{$head}'";
            $rlDb->query($sql);

            $this->saveGeoFormatData();

            return $levels;
        } else {
            return $rlDb->getOne("Levels", "`Key` = '{$head}'", "multi_formats");
        }
    }

    /**
     * get top level element key of the data/multi format
     *
     * @param int $id - id
     * @param string $key - key
     *
     * @return string
     **/
    public function getHead($id, $key = '')
    {
        if (!$id && !$key) {
            return false;
        }

        $id = (int) $id;
        $GLOBALS['rlValid']->sql($key);

        $where  = $id ? ['ID' => $id] : ['Key' => $key];
        $parent = $GLOBALS['rlDb']->fetch(['Parent_ID', 'Key'], $where, null, 1, 'multi_formats', 'row');

        if ($parent) {
            if ($parent['Parent_ID']) {
                return $this->getHead($parent['Parent_ID'], $parent['Key']);
            } else {
                return $parent['Key'];
            }
        } else {
            return $key;
        }
    }

    /**
     * Create sub fields
     *
     * @since 2.5.2 - Added $updateCache parameter
     *
     * @param array  $field_info  - field info
     * @param string $type        - type
     * @param bool   $updateCache - Indicator of necessary to update the cache
     */
    public function createSubFields($field_info, $type = 'listing', $updateCache = true)
    {
        global $rlDb;

        if (strpos($field_info['Key'], 'level') || !$field_info['Key']) {
            return false;
        }

        $format_id = $rlDb->getOne("ID", "`Key` = '" . $field_info['data_format'] . "'", 'multi_formats');
        $head_field_key = $field_info['Key'];

        if (!$format_id) {
            return false;
        }

        $languages = $GLOBALS['languages'] ?: $GLOBALS['rlLang']->getLanguagesList();

        $levels = $this->getLevels($format_id);

        if ($levels < 2) {
            return false;
        }

        for ($level = 1; $level < $levels; $level++) {
            $field_key = $head_field_key . "_level" . $level;
            $prev_fk = $level == 1 ? $head_field_key : ($head_field_key . "_level" . ($level - 1));

            $rlDb->addColumnToTable($field_key, "VARCHAR(255) NOT NULL AFTER `{$prev_fk}`", $type . 's');

            $sql = "ALTER TABLE `{db_prefix}{$type}s` ADD INDEX(`{$field_key}`)";
            $rlDb->query($sql);

            $sql = "SELECT `Key` FROM `{db_prefix}{$type}_fields` ";
            $sql .= "WHERE `Key` = '{$field_key}'";
            $field_exists = $rlDb->getRow($sql);

            if (!$field_exists) {
                $field_insert_info = array(
                    'Key'       => $field_key,
                    'Condition' => $field_info['data_format'],
                    'Type'      => 'select',
                    'Status'    => 'active',
                );

                if ($type == 'listing') {
                    $field_insert_info['Add_page'] = 1;
                    $field_insert_info['Details_page'] = 1;
                    $field_insert_info['Readonly'] = 1;
                }

                preg_match('/country|location|state|region|province|address/i', $head_field_key, $match);
                if ($match) {
                    $field_insert_info['Map'] = 1;
                }

                $rlDb->insertOne($field_insert_info, $type . "_fields");

                $field_id = $rlDb->getOne('ID', "`Key` = '{$field_insert_info['Key']}'", $type . "_fields");
                //$field_id = $rlDb->insertID();

                if ($type == 'listing') {
                    $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'listing_fields');

                    $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                    $rlDb->query($sql);

                    $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                    $rlDb->query($sql);
                } elseif ($type == 'account') {
                    $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'account_fields');

                    $sql = "SELECT `Category_ID`, `Position`, `Group_ID` ";
                    $sql .= "FROM `{db_prefix}account_submit_form` ";
                    $sql .= "WHERE `Field_ID` ={$prev_field_id}";
                    $afields = $rlDb->getAll($sql);

                    foreach ($afields as $afk => $afield) {
                        $sql = "UPDATE `{db_prefix}account_submit_form` ";
                        $sql .= "SET `Position` = `Position`+1 ";
                        $sql .= "WHERE `Position` > {$afield['Position']} ";
                        $sql .= "AND `Category_ID` = {$afield['Category_ID']} ";
                        $rlDb->query($sql);

                        $insert[$afk]['Position'] = $afield['Position'] + 1;
                        $insert[$afk]['Category_ID'] = $afield['Category_ID'];
                        $insert[$afk]['Group_ID'] = $afield['Group_ID'];
                        $insert[$afk]['Field_ID'] = $field_id;
                    }
                    $rlDb->insert($insert, 'account_submit_form');
                }

                $head_field_lkey = $type . '_fields+name+' . $head_field_key;

                $lang_keys = array();
                foreach ($languages as $key => $lang_item) {
                    $head_field_name = $rlDb->getOne("Value", "`Key` ='{$head_field_lkey}' AND `Code` = '{$lang_item['Code']}'", "lang_keys");

                    $lang_keys[] = array(
                        'Code'   => $lang_item['Code'],
                        'Module' => 'common',
                        'Key'    => $type . '_fields+name+' . $field_key,
                        'Value'  => $head_field_name . " Level " . $level,
                        'Plugin' => 'multiField',
                    );
                }
                $rlDb->insert($lang_keys, 'lang_keys');
            }
        }

        if ($updateCache) {
            $GLOBALS['rlCache']->updateForms();
        }
    }

    /**
     * delete sub fields
     *
     * @param array $field_info - field info
     * @param string $type - type
     **/
    public function deleteSubFields($field_info, $type = 'listing')
    {
        global $rlDb;

        if (strpos($field_info['Key'], 'level')) {
            return false;
        }

        $field_key = $field_info['Key'];

        if (!$field_key) {
            return false;
        }

        $old_format = $rlDb->getOne("Condition", "`Key` = '{$field_key}'", $type . '_fields');

        $sql = "SELECT * FROM `{db_prefix}listing_fields` ";
        $sql .= "WHERE `Condition` = '{$old_format}' AND `Key` REGEXP '{$field_key}_level[0-9]'";
        $fields = $rlDb->getAll($sql);

        if (!$fields) {
            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` WHERE `Field` REGEXP '{$field_key}_level[0-9]'";
            $fields_struct = $rlDb->getAll($sql);

            foreach ($fields_struct as $key => $field) {
                $rlDb->dropColumnFromTable($field['Field'], $type . 's');
            }
        }

        foreach ($fields as $key => $field) {
            $rlDb->dropColumnFromTable($field['Key'], $type . 's');

            if ($type == 'listing') {
                $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}short_forms` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            } elseif ($type == 'account') {
                $sql = "DELETE FROM `{db_prefix}account_search_relations` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_short_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_submit_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            }
        }

        $sql = "DELETE `T1`, `T2` FROM `{db_prefix}{$type}_fields` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('{$type}_fields+name+', `T1`.`Key`) ";
        $sql .= "WHERE `T1`.`Condition` = '{$old_format}' AND `T1`.`Key` REGEXP '{$field_key}_level[0-9]'";
        $rlDb->query($sql);

        $GLOBALS['rlCache']->updateForms();
    }

    /**
     * Add data format item
     *
     * @since 2.6.0 - $lat & $lng parameters added
     * @since 2.1.0
     *
     * @param  string $parentKey - Parent item key
     * @param  array  $names     - Item names, system lang as index is required
     * @param  array  $paths     - Multilingual paths
     * @param  string $lat       - Latitude
     * @param  string $lng       - Longitude
     * @param  string $status    - Item status: active or approval
     * @return array             - Ajax return data
     */
    public function addItem($parentKey, $names, $paths = [], $lat = 0, $lng = 0, $status = 'active')
    {
        global $lang, $config, $rlDb, $rlValid;

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        if (!$names[$config['lang']]) {
            $GLOBALS['rlDebug']->logger("MultiField: Unable to add new data formats entry, default language name empty.");

            return array(
                'status' => 'ERROR',
                'message' => $lang['system_error']
            );
        }

        $message = '';
        $auto_path = false;

        $key = $names[$config['lang']];
        $key = utf8_is_ascii($key) ? $key : utf8_to_ascii($key);
        $key = $rlValid->str2key($key);
        $key = $parentKey . '_' . $key;
        $key = $rlValid->uniqueKey($key, 'multi_formats', 'Key');

        $parent_info = $this->getFormatData(null, $parentKey);
        $parent_id = $parent_info['ID'];

        $head_key = $this->getHead($parent_id);
        $level = $this->getLevel($parent_id);
        $format_data = $rlDb->fetch(['Levels', 'Geo_filter'], ['Key' => $head_key], null, 1, 'multi_formats', 'row');
        $geo_filter = $format_data['Geo_filter'];

        $max_position = (int) $rlDb->getOne("Position", "`Parent_ID` = {$parent_id} ORDER BY `Position` DESC", "multi_formats");
        $parent_ids = '';

        if ($head_key != $parent_key) {
            $parent_ids = trim($parent_info['Parent_IDs'] . ',' . $parent_id, ',');
        }

        $insert = array(
            'Parent_ID'  => $parent_id,
            'Parent_IDs' => $parent_ids,
            'Key'        => $key,
            'Status'     => $status,
            'Position'   => $max_position + 1,
        );

        if ($geo_filter) {
            $field_key = 'Path';

            // Simulate default language path if the admin didn't specified it
            if (!$paths[$config['lang']]) {
                $paths[$config['lang']] = $names[$config['lang']];
                $auto_path = true;
            }

            // Disable URL transliteration for paths in multilingual path mode
            if ($config['mf_multilingual_path']) {
                $config['url_transliteration'] = false;
            }

            foreach ($paths as $code => $path) {
                if (!$path) {
                    continue;
                }

                if (strlen($path) < 3) {
                    $errors[] = $lang['incorrect_page_address'];
                    continue;
                }

                if ($config['mf_multilingual_path']) {
                    $field_key =  'Path_' . $code;
                }

                $path = $rlValid->str2path($path);

                if ($config['mf_geo_subdomains_type'] == 'unique') {
                    $path = $this->uniquePath($path, $parent_info, $code);
                } else {
                    $path = $this->buildPath($path, $parent_info, $field_key);
                }

                if ($this->isPathExists($path)) {
                    if ($auto_path) {
                        preg_match('/[0-9]+$/', $path, $match);
                        if ($match[0]) {
                            $path = preg_replace('/[0-9]+$/', ++$match[0], $path);
                        } else {
                            $path .= '-2';
                        }
                    } else {
                        $errors[] = $lang['mf_path_exists'];
                        break;
                    }
                }

                $insert[$field_key] = $path;
            }

            if ($config['mf_nearby_distance']) {
                $insert['Latitude'] = $lat != '' ? $lat : 0;
                $insert['Longitude'] = $lng != '' ? $lng : 0;
            }
        }

        if ($errors) {
            return array(
                'status' => 'ERROR',
                'message' => $errors
            );
        } else {
            if ($rlDb->insertOne($insert, 'multi_formats')) {
                if (($level + 1) > $format_data['Levels']) {
                    $update_levels = array(
                        'fields' => array('Levels' => $level + 1),
                        'where' => array('Key' => $head_key)
                    );
                    $rlDb->updateOne($update_levels, 'multi_formats');

                    $this->saveGeoFormatData();
                }

                foreach ($names as $code => $name) {
                    $insert_phrase = array(
                        'Key'   => $key,
                        'Value' => $name ?: $names[$config['lang']],
                    );
                    $rlDb->insertOne($insert_phrase, 'multi_formats_lang_' . $code);
                }

                if ($level) {
                    $GLOBALS['languages'] = $GLOBALS['rlLang']->getLanguagesList();

                    $listing_fields = $this->createLevelField($parent_id, 'listing');
                    $account_fields = $this->createLevelField($parent_id, 'account');

                    if ($listing_fields || $account_fields) {
                        $message = '<ul>';
                        $message .= "<li>" . $lang['item_added'] . "</li>";

                        foreach ($listing_fields as $field) {
                            $href = "index.php?controller=listing_fields&action=edit&field={$field}";
                            $link = '<a target="_blank" href="' . $href . '">$1</a>';
                            $row = preg_replace('/\[(.+)\]/', $link, $lang['mf_lf_created']);

                            $message .= "<li>" . $row . "</li>";
                        }

                        foreach ($account_fields as $field) {
                            $href = "index.php?controller=account_fields&action=edit&field=" . $field;
                            $link = '<a target="_blank" href="' . $href . '">$1</a>';
                            $row = preg_replace('/\[(.+)\]/', $link, $lang['mf_af_created']);
                            $message .= "<li>" . $row . "</li>";
                        }
                        $message .= '</ul>';
                    }
                }

                $GLOBALS['rlCache']->updateSubmitForms();

                return array(
                    'status' => 'OK',
                    'message' => $message
                );
            }

            return array(
                'status' => 'ERROR',
                'message' => $lang['system_error']
            );
        }
    }

    /**
     * Edit data format item
     *
     * @since 2.6.0 - $lat & $lng parameters added
     * @since 2.1.0
     *
     * @param  string $parentKey - Parent item key
     * @param  string $key       - Item key is optional, will be generated automatically if empty
     * @param  array  $names     - Item names, system lang as index is required
     * @param  array  $paths     - Multilingual paths
     * @param  string $lat       - Latitude
     * @param  string $lng       - Longitude
     * @param  string $status    - Item status: active or approval
     * @return bool|array        - Success status or errors array
     */
    public function editItem($parentKey, $key, $names, $paths = [], $lat = 0, $lng = 0, $status = 'active')
    {
        global $lang, $config, $rlDb, $rlValid;

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        if (!$names[$config['lang']]) {
            $GLOBALS['rlDebug']->logger("MultiField: Unable to add new data formats entry, default language name empty.");
            return false;
        }

        $format = $rlDb->fetch('*', array('Key' => $key), null, null, 'multi_formats', 'row');

        $parent_info = $rlDb->fetch('*', array('Key' => $parentKey), null, null, 'multi_formats', 'row');
        $parent_id = $parent_info['ID'];

        $head_key = $this->getHead($parent_id);
        $geo_filter = $rlDb->getOne("Geo_filter", "`Key` = '{$head_key}'", 'multi_formats');

        $update = array(
            'fields' => array(
                'Status' => $status
            ),
            'where' => array(
                'Key' =>  $key
            )
        );

        $update_child_paths = [];
        $errors = [];

        if ($geo_filter) {
            $field_key = 'Path';

            // Simulate default language path if the admin didn't specified it
            if (!$paths[$config['lang']]) {
                $paths[$config['lang']] = $names[$config['lang']];
            }

            // Disable URL transliteration for paths in multilingual path mode
            if ($config['mf_multilingual_path']) {
                $config['url_transliteration'] = false;
            }

            foreach ($paths as $code => $path) {
                if ($config['mf_multilingual_path']) {
                    $field_key =  'Path_' . $code;
                }

                // Empty path or not edited path (edit more)
                if (!$path || $path == $format[$field_key]) {
                    continue;
                }

                if (strlen($path) < 3) {
                    $errors[] = $lang['incorrect_page_address'];
                    continue;
                }

                $path = $rlValid->str2path($path);

                if ($config['mf_geo_subdomains_type'] == 'unique') {
                    $path = $this->uniquePath($path, $parent_info, $code);
                } else {
                    $path = $this->buildPath($path, $parent_info, $field_key);
                }

                if ($this->isPathExists($path, $key)) {
                    $errors[] = $lang['mf_path_exists'];
                    break;
                }

                $update['fields'][$field_key] = $path;

                // Save path data for the further update
                if ($config['mf_geo_subdomains_type'] != 'unique' && $format[$field_key] != $path) {
                    $update_child_paths[] = array(
                        'field'  => $field_key,
                        'path' => $path
                    );
                }
            }

            if ($config['mf_nearby_distance']) {
                $update['fields']['Latitude'] = $lat != '' ? $lat : 0;
                $update['fields']['Longitude'] = $lng != '' ? $lng : 0;
            }
        }

        if ($errors) {
            return $errors;
        } else {
            if ($rlDb->updateOne($update, 'multi_formats')) {
                // Update child paths
                if ($config['mf_geo_subdomains_type'] != 'unique' && $update_child_paths) {
                    foreach ($update_child_paths as $path_data) {
                        $find_path = $format[$path_data['field']] ?: $format['Path_' . $config['lang']];

                        if ($find_path) {
                            // Copy not existing paths from default language
                            $system_path_field = $config['mf_multilingual_path'] ? "Path_{$config['lang']}" : 'Path';
                            $copy_sql = "
                                UPDATE `{db_prefix}multi_formats`
                                SET `{$path_data['field']}` = `{$system_path_field}`
                                WHERE `{$path_data['field']}` = '' AND `Key` LIKE '{$key}\_%'
                            ";
                            $rlDb->query($copy_sql);

                            // Update paths
                            $update_sql = "
                                UPDATE `{db_prefix}multi_formats`
                                SET `{$path_data['field']}` = REPLACE(`{$path_data['field']}`, '{$find_path}/', '{$path_data['path']}/')
                                WHERE `Key` LIKE '{$key}\_%'
                            ";
                            $rlDb->query($update_sql);
                        }
                    }
                }

                // Update child items status
                if ($format['Status'] != $status) {
                    $sql = "
                        UPDATE `{db_prefix}multi_formats`
                        SET `Status` = '{$status}'
                        WHERE FIND_IN_SET({$format['ID']}, `Parent_IDs`) > 0
                    ";
                    $rlDb->query($sql);
                }

                // Update names
                foreach ($names as $code => $name) {
                    $update_phrase = array(
                        'fields' => array(
                            'Value'  => $name ?: $names[$config['lang']],
                        ),
                        'where' => array(
                            'Key'  => $key
                        )
                    );
                    $rlDb->updateOne($update_phrase, 'multi_formats_lang_' . $code);
                }

                // Update cache
                $GLOBALS['rlCache']->updateSubmitForms();

                return true;
            }

            return false;
        }
    }

    /**
     * @deprecated 2.1.0 - Use addItem() instead
     **/
    public function ajaxAddItem($key, $names, $status, $parent_key, $path = '', $subdomain_path = false)
    {}

    /**
     * create field
     *
     * check related fields and add listing fields
     * if there are no field yet for this level
     *
     * @param int $parent_id
     * @param string $type - listing or account
     **/
    public function createLevelField($parent_id, $type = 'listing')
    {
        global $languages, $rlDb;

        $out = array();
        $parent_id = (int) $parent_id;
        $multi_format = $this->getHead($parent_id);

        if (!$multi_format) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}{$type}_fields` ";
        $sql .= "WHERE `Condition` = '{$multi_format}' AND `Key` NOT REGEXP 'level[0-9]'";
        $related_fields = $rlDb->getAll($sql);

        if (!$related_fields) {
            return false;
        }

        $level = $this->getLevel($parent_id);
        $level = $level ? $level : 1;

        foreach ($related_fields as $rlk => $field) {
            $field_key = $field['Key'] . "_level" . $level;
            $prev_fk = $level == 1 ? $field['Key'] : ($field['Key'] . "_level" . ($level - 1));

            // Skip iteration if previous level field doesn't exist
            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` WHERE `Field` = '{$prev_fk}'";
            if (!$rlDb->getRow($sql)) {
                continue;
            }

            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` WHERE `Field` = '{$field_key}'";
            $field_exists = $rlDb->getRow($sql);

            if (!$field_exists) {
                $sql = "ALTER TABLE `{db_prefix}{$type}s` ";
                $sql .= "ADD `{$field_key}` VARCHAR(255) NOT NULL AFTER `{$prev_fk}`";
                $rlDb->query($sql);

                $sql = "ALTER TABLE `{db_prefix}{$type}s` ADD INDEX(`{$field_key}`)";
                $rlDb->query($sql);

                $field_info = array(
                    'Key'       => $field_key,
                    'Condition' => $multi_format,
                    'Type'      => 'select',
                    'Status'    => 'active',
                    'Readonly'  => '1',
                    'Add_page'  => '1',
                    'Details_page'  => '1',
                );

                preg_match('/country|location|state|region|province|address|city/i', $field_key, $match);

                if ($match) {
                    $field_info['Map'] = '1';
                }

                if ($rlDb->insertOne($field_info, $type . '_fields')) {
                    $field_id = $rlDb->insertID();

                    if ($type == 'listing') {
                        $prev_field_id = $rlDb->getOne('ID', "`Key` = '{$prev_fk}'", 'listing_fields');

                        $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                        $rlDb->query($sql);

                        $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$prev_field_id},', ',{$prev_field_id},{$field_id},'))) WHERE FIND_IN_SET('{$prev_field_id}', `Fields`) ";
                        $rlDb->query($sql);
                    } elseif ($type == 'account') {
                        $prev_field_id = $rlDb->getOne("ID", "`Key` = '{$prev_fk}'", 'account_fields');

                        $sql = "SELECT `Category_ID`, `Position`, `Group_ID` FROM `{db_prefix}account_submit_form` ";
                        $sql .= "WHERE `Field_ID` ={$prev_field_id}";
                        $afields = $rlDb->getAll($sql);

                        foreach ($afields as $afk => $afield) {
                            $sql = "UPDATE `{db_prefix}account_submit_form` SET `Position` = `Position`+1 ";
                            $sql .= "WHERE `Position` > " . $afield['Position'] . " AND `Category_ID` = " . $afield['Category_ID'];
                            $rlDb->query($sql);

                            $insert[$afk]['Position'] = $afield['Position'] + 1;
                            $insert[$afk]['Category_ID'] = $afield['Category_ID'];
                            $insert[$afk]['Group_ID'] = $afield['Group_ID'];
                            $insert[$afk]['Field_ID'] = $field_id;
                        }
                        $rlDb->insert($insert, 'account_submit_form');
                        unset($insert);
                    }

                    foreach ($languages as $language) {
                        $lang_keys[] = array(
                            'Code'   => $language['Code'],
                            'Module' => 'common',
                            'Key'    => $type . '_fields+name+' . $field_key,
                            'Value'  => $GLOBALS['rlLang']->getPhrase($type . '_fields+name+' . $field['Key']) . " Level " . $level,
                            'Plugin' => 'multiField',
                        );
                    }

                    $rlDb->insert($lang_keys, 'lang_keys');
                    unset($lang_keys);
                }

                $out[] = $field_key;
            }
        }

        $GLOBALS['rlCache']->updateForms();

        return $out;
    }

    /**
     * deletes automatically added fields (listing fields and account fields) when you delete multi-format
     *
     * @param string $format - multi_format key
     * @param string $type - listing or account
     **/
    public function deleteFormatChildFields($format, $type = 'listing')
    {
        global $rlDb;

        $sql = "SELECT `Key`, `ID` FROM `{db_prefix}{$type}_fields` ";
        $sql .= "WHERE `Condition` = '{$format}' AND `Key` REGEXP 'level[0-9]'";
        $related_fields = $rlDb->getAll($sql);

        foreach ($related_fields as $rlk => $field) {
            $sql = "DELETE `T1`,`T2` FROM `{db_prefix}{$type}_fields` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
            $sql .= "ON (`T2`.`Key` = CONCAT('{$type}_fields+name+', `T1`.`Key`) OR `T2`.`Key` = CONCAT('{$type}_fields+des+', `T1`.`Key`)) ";
            $sql .= "WHERE `T1`.`Key` ='{$field['Key']}'";
            $rlDb->query($sql);

            if ($type == 'listing') {
                $sql = "UPDATE `{db_prefix}listing_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "UPDATE `{db_prefix}search_forms_relations` SET `Fields` = TRIM(BOTH ',' FROM ( REPLACE( CONCAT(',',`Fields`,','), ',{$field['ID']},', ','))) WHERE FIND_IN_SET('{$field['ID']}', `Fields`) ";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}short_forms` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            } else {
                $sql = "DELETE FROM `{db_prefix}account_search_relations` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_short_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);

                $sql = "DELETE FROM `{db_prefix}account_submit_form` ";
                $sql .= "WHERE `Field_ID` = {$field['ID']}";
                $rlDb->query($sql);
            }

            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` ";
            $sql .= "WHERE `Field` = '{$field['Key']}'";
            $field_exists = $rlDb->getRow($sql);

            if ($field_exists) {
                $sql = "ALTER TABLE `{db_prefix}{$type}s` DROP `{$field['Key']}`";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * Delete data format entry with all related data
     *
     * @since 2.5.1 - $id parameter replaced with $key
     *
     * @param string $key - Format key
     **/
    public function deleteDF($key)
    {
        global $rlDb;

        $multi_format_id = $rlDb->getOne('ID', "`Key` = '{$key}'", 'multi_formats');

        if (!$key || !$multi_format_id) {
            return false;
        }

        // Detele data format related entry and it's phrases
        $sql = "
            DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1`
            LEFT JOIN `{db_prefix}lang_keys` AS `T2`
            ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`)
            WHERE `T1`.`Key` = '{$key}'
        ";
        $rlDb->query($sql);

        // Delete multi format related phrases from every language table
        foreach ($GLOBALS['rlLang']->getLanguagesList() as $language) {
            $sql = "
                DELETE `T2` FROM `{db_prefix}multi_formats` AS `T1`
                LEFT JOIN `{db_prefix}multi_formats_lang_{$language['Code']}` AS `T2`
                ON `T1`.`Key` = `T2`.`Key`
                WHERE `Parent_ID` = {$multi_format_id}
                OR FIND_IN_SET({$multi_format_id}, `Parent_IDs`) > 0
                OR `ID` = {$multi_format_id}
            ";
            $rlDb->query($sql);
        }

        // Delete multi format
        $sql = "
            DELETE FROM `{db_prefix}multi_formats`
            WHERE `Parent_ID` = {$multi_format_id}
            OR FIND_IN_SET({$multi_format_id}, `Parent_IDs`) > 0
            OR `ID` = {$multi_format_id}
        ";
        $rlDb->query($sql);

        // Update format cache in plugin configs
        if ($this->geoFormatData && $key === $this->geoFormatData['Key']) {
            $this->saveGeoFormatData();
        }

        $this->saveFormatKeys();

        $GLOBALS['rlCache']->updateDataFormats();
        $GLOBALS['rlCache']->updateForms();

        // Redefine key to keep it available in apPhpFormatsAjaxDeleteItem as global
        $tmp_key = $key;

        global $key;

        $key = $tmp_key;

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteItem');

        return true;
    }

    /**
     * deletes automatically added fields (listing fields and account fields) when you delete field
     *
     * @param string $format - multi_format key
     * @param string $type - listing or account
     **/
    public function deleteFieldChildFields($field_key, $type = 'listing')
    {
        global $rlDb;

        $GLOBALS['rlValid']->sql($field_key);

        if (!$field_key || !$type) {
            return false;
        }

        $sql = "SELECT `Key`, `ID` FROM `{db_prefix}{$type}_fields` ";
        $sql .= "WHERE `Key` REGEXP '{$field_key}_level[0-9]'";
        $related_fields = $rlDb->getAll($sql);

        foreach ($related_fields as $rlk => $field) {
            $sql = "DELETE `T1`,`T2` FROM `{db_prefix}{$type}_fields` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ";
            $sql .= "ON (`T2`.`Key` = CONCAT('{$type}_fields+name+', `T1`.`Key`) ";
            $sql .= "OR `T2`.`Key` = CONCAT('{$type}_fields+des+', `T1`.`Key`)) ";
            $sql .= "WHERE `T1`.`Key` ='{$field['Key']}'";

            $rlDb->query($sql);

            $sql = "SHOW FIELDS FROM `{db_prefix}{$type}s` ";
            $sql .= "WHERE `Field` = '{$field['Key']}'";
            $field_exists = $rlDb->getRow($sql);

            if ($field_exists) {
                $sql = "ALTER TABLE `{db_prefix}{$type}s` DROP `{$field['Key']}`";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * Prepare data for format editing
     *
     * @since 2.2.0 - $key parameter replaced with $id
     * @since 2.1.0
     *
     * @param  string $id - Format item ID
     * @return array      - Format item data
     */
    public function prepareEdit($id)
    {
        global $rlDb;

        $item = $rlDb->fetch('*', array('ID' => $id), null, 1, 'multi_formats', 'row');
        $names = [];

        if (!$item) {
            return;
        }

        $languages = $GLOBALS['rlLang']->getLanguagesList();

        foreach ($languages as $language) {
            $names[$language['Code']] = $rlDb->getOne('Value', "`Key` = '{$item['Key']}'", 'multi_formats_lang_' . $language['Code']);
        }

        $item['names'] = $names;

        return $item;
    }

    /**
     * @deprecated 2.1.0
     **/
    public function ajaxPrepareEdit($key)
    {}

    /**
     * @deprecated 2.1.0 - Use editItem() instead
     **/
    public function ajaxEditItem($key, $names, $status, $format, $path = '', $subdomain_path = false)
    {}

    /**
     * add format item
     *
     * @package ajax
     * @param string $key - item key
     **/
    public function ajaxDeleteItem($id)
    {
        global $_response, $lang;

        $id = (int) $id;

        if (!$id) {
            return $_response;
        }

        $this->deleteFormatItem($id);

        // Update cache if it's the first level of format
        if ($this->getLevel($id) <= 1) {
            $GLOBALS['rlCache']->updateForms();
            $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteItem');
        }

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
        $_response->script("$('#loading').fadeOut('normal');");

        $_response->script("itemsGrid.reload()");
        $_response->script("$('#edit_item').slideUp('normal');");
        $_response->script("$('#new_item').slideUp('normal');");

        return $_response;
    }

    /**
     * Delete format item and it's childs by format ID
     *
     * @since 2.2.0
     *
     * @param int $id - Format ID
     */
    public function deleteFormatItem($id)
    {
        global $rlDb;

        $count_lang = count($GLOBALS['languages']);
        $iteration  = 1;
        foreach ($GLOBALS['languages'] as $language) {
            $target = $count_lang == $iteration ? ',`TF`' : '';

            $sql = "
                DELETE `TL`{$target} FROM `{db_prefix}multi_formats` AS `TF`
                LEFT JOIN `{db_prefix}multi_formats_lang_{$language['Code']}` AS `TL` ON `TL`.`Key` = `TF`.`Key` 
                WHERE FIND_IN_SET({$id}, `TF`.`Parent_IDs`) > 0 OR `TF`.`ID` = {$id}
            ";
            $rlDb->query($sql);

            $iteration++;
        }
    }

    /**
     * delete format
     *
     * @package ajax
     * @param string $key - key
     **/
    public function ajaxDeleteFormat($key)
    {
        global $_response, $lang, $rlValid;

        $rlValid->sql($key);
        if (!$key) {
            return $_response;
        }

        if ($this->deleteDF($key)) {
            $GLOBALS['rlCache']->updateDataFormats();
            $GLOBALS['rlCache']->updateSubmitForms();

            $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
            $_response->script("$('#loading').fadeOut('normal');");

            $_response->script("multiFieldGrid.reload()");
            $_response->script("$('#edit_item').slideUp('normal');");
            $_response->script("$('#new_item').slideUp('normal');");
        }

        return $_response;
    }

    /**
     * get bread crumbs | recursive method
     *
     * @param int $parent_id -  parent_id
     * @return array
     **/
    public function getBreadCrumbs($parent_id = false, $bc = false)
    {
        $parent_id = (int) $parent_id;

        if (!$parent_id) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`, `T1`.`Parent_ID`, `T1`.`Key`, `T2`.`Value` AS `name` ";
        $sql .= "FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}multi_formats_lang_" . RL_LANG_CODE . "` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`ID` = {$parent_id} ";

        if (REALM !== 'admin') {
            $sql .= "AND `T1`.`Status` = 'active'";
        }

        $info = $GLOBALS['rlDb']->getRow($sql);

        // Get name for the head level
        if (!$info['name']) {
            $info['name'] = $GLOBALS['rlLang']->getPhrase('data_formats+name+' . $info['Key']);
        }

        if ($info) {
            $bc[] = $info;

            if ($info['Parent_ID']) {
                return $this->getBreadCrumbs($info['Parent_ID'], $bc);
            } else {
                return $bc;
            }
        } else {
            return $bc;
        }
    }

    /**
     * After Remote Import
     *
     * @param int $table      - location database key
     * @param int $format_key
     *
     * @return array
     **/
    public function afterRemoteImport($table, $format_key = '')
    {
        global $rlDb;

        $this->getLevels(null, true, $format_key);

        if (is_numeric(strpos($table, 'location'))) {
            $sql = "UPDATE `{db_prefix}config` SET `Default` = '{$table}' ";
            $sql .= "WHERE `Key` = 'mf_db_version'";
            $GLOBALS['rlDb']->query($sql);
        }

        return false;
    }

    /* @hook apTplControlsForm
     *
     * @since 2.0.0
     */
    public function hookApTplControlsForm()
    {
        global $lang;

        echo '<tr class="body">
            <td class="list_td">' . $lang['mf_rebuild'] . '</td>
            <td class="list_td" align="center">
                <input id="mfRebuildFields" type="button" value="' . $lang['rebuild'] . '" style="margin: 0;width: 100px;" />
            </td>
        </tr>';

        if ($this->geoFilterEnabled()) {
            echo '<tr class="body">
                <td class="list_td">' . $lang['mf_rebuild_path'] . '</td>
                <td class="list_td" align="center">
                    <input id="mfRebuildPaths" type="button" value="' . $lang['mf_refresh'] .'" style="margin: 0;width: 100px;" />
                </td>
            </tr>';
        }

        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'multiField/admin/refreshEntry.tpl');
    }

    /**
     * @hook apAjaxRequest
     *
     * @param array  $out    - ajax request response
     * @param string $action - request action
     */
    public function hookApAjaxRequest(&$out, $request_item)
    {
        switch ($_REQUEST['mode']) {
            case 'mfNext':
                $order_type = null;

                // Get format order
                foreach ($this->formatKeys as $format_key) {
                    if (strpos($request_item, $format_key) === 0) {
                        if ($format_key == $this->geoFormatData['Key']) {
                            $order_type = $this->geoFormatData['Order_type'];
                        } else {
                            $order_type = $GLOBALS['rlDb']->getOne('Order_type', "`Key` = '{$format_key}'", 'data_formats');
                        }

                        break;
                    }
                }

                $GLOBALS['reefless']->loadClass('MultiField', null, 'multiField');
                $data = $GLOBALS['rlMultiField']->getData($request_item, false, $order_type);

                $out['data'] = $data;
                $out['status'] = 'ok';
                break;

            case 'mfRebuildPaths':
                if ($_REQUEST['modify']) {
                    $GLOBALS['config']['mf_geo_subdomains_type'] = $_REQUEST['value'];
                }

                $out = $this->refreshPaths(intval($_REQUEST['start']), $_REQUEST['controller']);
                break;

            case 'mfRebuildFields':
                $this->rebuildMultiField($out);
                break;

            case 'mfAddItem':
                foreach ($_REQUEST['names'] as $name) {
                    $code = str_replace('name_', '', $name['name']);
                    $names[$code] = $name['value'];
                }
                foreach ($_REQUEST['paths'] as $path) {
                    $code = str_replace('path_', '', $path['name']);
                    $paths[$code] = $path['value'];
                }

                $out = $this->addItem(
                    $_REQUEST['parentKey'],
                    $names,
                    $paths,
                    $_REQUEST['lat'],
                    $_REQUEST['lng'],
                    $_REQUEST['status']
                );
                break;

            case 'mfPrepareItem':
                $results = $this->prepareEdit($_REQUEST['key']);

                $out = array(
                    'status' => $results ? 'OK' : 'ERROR',
                    'data' => $results
                );
                break;

            case 'mfEditItem':
                foreach ($_REQUEST['names'] as $name) {
                    $code = str_replace('name_', '', $name['name']);
                    $names[$code] = $name['value'];
                }
                foreach ($_REQUEST['paths'] as $path) {
                    $code = str_replace('path_', '', $path['name']);
                    $paths[$code] = $path['value'];
                }

                $results = $this->editItem(
                    $_REQUEST['parentKey'],
                    $_REQUEST['key'],
                    $names,
                    $paths,
                    $_REQUEST['lat'],
                    $_REQUEST['lng'],
                    $_REQUEST['status']
                );

                $out = array(
                    'status' => $results === true ? 'OK' : 'ERROR',
                    'message' => !is_bool($results) ? $results : ''
                );
                break;

            case 'mfBulkAction':
                if ($this->massAction($_REQUEST['ids'], $_REQUEST['action'])) {
                    $out = array('status' => 'OK');
                } else {
                    $out = array('status' => 'ERROR');
                }
                break;

            case 'mfCheckNearby':
                if ($this->checkNearbyOption()) {
                    $out = array('status' => 'OK');
                } else {
                    $out = array('status' => 'ERROR');
                }
            break;

        case 'mfRebuildCoordinates':
            $out = $this->refreshCoordinates(intval($_REQUEST['start']));
                break;
        }
    }

    /**
     * Check the quality of the coordinates data in locations database
     *
     * @since 2.6.0
     *
     * @return boolean - Is there is enough coordinates in the format assigned to the location filtration
     */
    public function checkNearbyOption()
    {
        global $rlDb;

        if (!$this->geoFormatData) {
            return false;
        }

        $empty = $rlDb->getRow("
            SELECT COUNT(*) AS `count` FROM `{db_prefix}multi_formats`
            WHERE `Latitude` = 0 AND `Longitude` = 0 AND `Key` LIKE '{$this->geoFormatData['Key']}_%'
        ");

        $total = $rlDb->getRow("
            SELECT COUNT(*) AS `count` FROM `{db_prefix}multi_formats`
            WHERE `Key` LIKE '{$this->geoFormatData['Key']}_%'
        ");
        $quality = 100 - ($empty['count'] * 100 / $total['count']);

        return $quality >= 70;
    }

    /**
     * Is geo filtering enabled for some data format
     *
     * @since 2.0.0
     *
     * @return int - Geo Filter format ID
     */
    public function geoFilterEnabled()
    {
        return $GLOBALS['config']['mf_geo_data_format'] ? true : false;
    }

    /**
     * Rebuild related fields
     *
     * @param int $out  - ajax request $out var to interact with
     **/
    public function rebuildMultiField(&$out)
    {
        global $rlDb, $lang;

        $multi_formats = $this->getMultiFormats();

        if (!$multi_formats) {
            $out['status'] = 'error';
            $out['message'] = $lang['mf_rebuild_no_format_configured'];

            return false;
        }

        foreach ($multi_formats as $key => $format) {
            foreach (array('listing', 'account') as $type) {
                $sql = "SELECT `Condition` as `data_format`, `Key` ";
                $sql .= "FROM `{db_prefix}{$type}_fields` ";
                $sql .= "WHERE `Condition` = '{$format['Key']}' ";
                $sql .= "AND `Key` NOT REGEXP 'level[0-9]'";
                $related_fields = $rlDb->getAll($sql);

                foreach ($related_fields as $rfKey => $rfield) {
                    $this->createSubFields($rfield, $type, false);
                    $rebuilt = true;
                }
            }
        }

        $GLOBALS['rlCache']->updateForms();

        if (!$rebuilt) {
            $out['status'] = 'error';
            $out['message'] = $lang['mf_rebuild_no_fields_configured'];

            return false;
        } else {
            $out['status'] = 'ok';

            return true;
        }
    }

    /**
     * Get multilingual path columns from data formats table
     *
     * @since 2.1.0
     *
     * @return array - Column names as: array('Path_en', 'Path_fr', ...)
     */
    public function getPathColumns()
    {
        static $columns = null;

        if ($GLOBALS['config']['mf_multilingual_path'] && $columns === null) {
            $columns = $GLOBALS['rlDb']->getAll(
                "SHOW COLUMNS FROM `{db_prefix}multi_formats` WHERE `Field` LIKE 'Path_%'",
                array(false, 'Field')
            );
        }

        return $columns;
    }

    /**
     * Refresh path initializer
     *
     * @since 2.1.0 - The second parameter $controller added
     *
     * @param int $start - start position
     **/
    private function refreshPaths($start = 0, $controller = '')
    {
        global $rlDb, $config;

        $path_field = $config['mf_multilingual_path'] ? 'Path_' . $config['lang'] : 'Path';

        $format = $this->getGeoFilterFormat();

        if (!$format || !$format['ID']) {
            return true;
        }

        if ($start == 0) {
            unset($_SESSION['mf_refresh_path']);

            $sql = "UPDATE `{db_prefix}multi_formats` SET `Parent_IDs` = '', ";
            if ($config['mf_multilingual_path']) {
                $sql .= "`Path_{$config['lang']}` = '' ";
            } else {
                $sql .= "`Path` = '' ";
            }
            $sql .= "WHERE `Key` LIKE '{$format['Key']}%'";

            $rlDb->query($sql);

            $sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}multi_formats`
                WHERE `Key` LIKE '{$format['Key']}%'
            ";
            $_SESSION['mf_refresh_path']['total'] = $rlDb->getRow($sql, 'Count');

            // Speed up the duplicate path search query
            // if ($config['mf_geo_subdomains_type'] == 'unique') {
            //     $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` ADD INDEX(`{$path_field}`)");
            // }

            $parents = array($format['ID']);
        } else {
            $parents = $_SESSION['mf_refresh_path']['parents'];
        }

        if (!$parents) {
            $msg = 'Path refresh failed, no parent IDs array defined';
            $GLOBALS['rlDebug']->logger('MultiField: ' . $msg);

            return array(
                'status'  => 'ERROR',
                'message' => $msg
            );
        }

        if ($this->updateLocationPath($format, $parents)) {
            return array(
                'status'   => 'next',
                'progress' => floor(($start * 100 * 1000) / $_SESSION['mf_refresh_path']['total'])
            );
        } else {
            // if ($config['mf_geo_subdomains_type'] == 'unique') {
            //     $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` DROP INDEX `{$path_field}`");
            // }

            // Manage multilingual path fields
            if ($config['mf_multilingual_path'] && $controller == 'settings') {
                $languages   = $GLOBALS['rlLang']->getLanguagesList();
                $system_lang = 'Path_' . $config['lang'];

                foreach ($languages as $language) {
                    if ($language['Code'] != $config['lang']) {
                        $field = 'Path_' . $language['Code'];

                        // Remove parent part from the path
                        if ($config['mf_geo_subdomains_type'] == 'unique') {
                            $rlDb->query("
                                UPDATE `{db_prefix}multi_formats` SET `{$field}` = SUBSTRING_INDEX(`{$field}`, '/', -1)
                                WHERE `{$field}` != ''
                            ");
                        }
                        // Add parent path from the default language
                        else {
                            $rlDb->query("
                                UPDATE `{db_prefix}multi_formats` SET `{$field}` = CONCAT(SUBSTRING_INDEX(`{$system_lang}`, '/', LENGTH(`{$system_lang}`) - LENGTH(REPLACE(`{$system_lang}`, '/', ''))), IF(LENGTH(`{$system_lang}`) - LENGTH(REPLACE(`{$system_lang}`, '/', '')) = 0, '', '/') ,`{$field}`)
                                WHERE `{$field}` != ''
                            ");
                        }
                    }
                }
            }

            return array(
                'status' => 'completed'
            );
        }
    }

    /**
     * Refresh coordinates
     *
     * @since 2.6.0
     *
     * @param int $start - Start position
     **/
    private function refreshCoordinates($start = 0)
    {
        global $rlDb, $config;

        $format = $this->getGeoFilterFormat();

        if (!$format || !$format['ID']) {
            return array('status' => 'ERROR');
        }

        if ($start == 0) {
            unset($_SESSION['mf_refresh_coordinates']);

            $count = $this->getFData([
                'table' => $config['mf_db_version'],
                'getcount' => true
            ]);
            $_SESSION['mf_refresh_coordinates']['total'] = $count;
            $_SESSION['mf_refresh_coordinates']['progress'] = 0;
            $_SESSION['mf_refresh_coordinates']['start'] = 0;

            $parents = $this->getFData([
                'table' => $config['mf_db_version'],
                'start' => $start,
            ]);

            if ($parents) {
                $_SESSION['mf_refresh_coordinates']['current_parent'] = array_shift($parents);
                $_SESSION['mf_refresh_coordinates']['parents'] = $parents;

                // Update parent's coordinates
                $update = [];
                foreach ($parents as $item) {
                    $update[] = array(
                        'fields' => array(
                            'Latitude' => $item->Latitude,
                            'Longitude' => $item->Longitude
                        ),
                        'where' => array(
                            'Key' => $this->geoFormatData['Key'] . '_' . $item->Key,
                            'Latitude' => '',
                            'Longitude' => ''
                        ),
                    );
                }
                if ($update) {
                    $GLOBALS['rlDb']->update($update, 'multi_formats');
                }
            } else {
                $GLOBALS['rlDebug']->logger('MultiField: refreshCoordinates() failed, no parents data received.');
                return array('status' => 'ERROR');
            }
        }

        if ($this->updateCoordinates()) {
            return array(
                'status' => 'next',
                'progress' => floor(($_SESSION['mf_refresh_coordinates']['progress'] * 100) / $_SESSION['mf_refresh_coordinates']['total']),
            );
        } else {
            return array(
                'status' => 'completed',
            );
        }
    }

    /**
     * Update coordinates
     *
     * @since 2.6.0
     */
    private function updateCoordinates()
    {
        global $config;

        $start = $_SESSION['mf_refresh_coordinates']['start'];
        $parent = $_SESSION['mf_refresh_coordinates']['current_parent'];
        $limit = 1000;
        $update = [];

        $childs = $this->getFData([
            'table' => $config['mf_db_version'],
            'parent' => $parent->Key,
            'start' => $start * $limit,
            'limit' => $limit,
        ]);

        if ($childs) {
            $_SESSION['mf_refresh_coordinates']['progress'] += count($childs);

            foreach ($childs as $child) {
                if ($child->Latitude == '' || $child->Longitude == '') {
                    continue;
                }

                $update[] = array(
                    'fields' => array(
                        'Latitude' => $child->Latitude,
                        'Longitude' => $child->Longitude
                    ),
                    'where' => array(
                        'Key' => $this->geoFormatData['Key'] . '_' . $child->Key,
                        'Latitude' => '',
                        'Longitude' => ''
                    ),
                );
            }

            if ($update) {
                $GLOBALS['rlDb']->update($update, 'multi_formats');
            }

            $_SESSION['mf_refresh_coordinates']['start']++;

            return true;
        } else {
            $_SESSION['mf_refresh_coordinates']['start'] = 0;
            return $_SESSION['mf_refresh_coordinates']['current_parent'] = array_shift($_SESSION['mf_refresh_coordinates']['parents']);
        }
    }

    /**
     * Update location path
     *
     * @param array $format  - Geo Location format data
     * @param array $parents - parent IDs to look into
     * @param int   $start   - count of affected items in this ajax session
     **/
    private function updateLocationPath($format, $parents, $count = 0)
    {
        global $rlDb, $config;

        $limit = 1000;
        $from  = $limit - $count;

        $path_field = $config['mf_multilingual_path'] ? 'Path_' . $config['lang'] : 'Path';
        $parent_ids = implode("','", $parents);

        $sql = "
            SELECT `T2`.`Value` AS `name`, `T1`.`ID`, `T1`.`Parent_ID`
            FROM `{db_prefix}multi_formats` AS `T1`
            LEFT JOIN `{db_prefix}multi_formats_lang_{$config['lang']}` AS `T2` ON `T2`.`Key` = `T1`.`Key`
            WHERE `T1`.`Parent_ID` IN ('{$parent_ids}')
            AND `{$path_field}` = ''
            ORDER BY `T1`.`ID`
            LIMIT {$from}
        ";

        $locations = $rlDb->getAll($sql);
        $count    += count($locations);

        if ($locations) {
            foreach ($locations as $location) {
                $path = $this->str2path($location['name']);
                $parent_ids = '';

                if ($location['Parent_ID'] != $format['ID']) {
                    $parent = $rlDb->fetch(
                        '*',
                        array('ID' => $location['Parent_ID']),
                        null,
                        1,
                        'multi_formats',
                        'row'
                    );

                    $parent_ids = $parent['Parent_IDs']
                    ? $location['Parent_ID'] . ',' . $parent['Parent_IDs']
                    : $location['Parent_ID'];

                    if ($config['mf_geo_subdomains_type'] == 'unique') {
                        $path = $this->uniquePath($path, $parent);
                    } else {
                        $path = $parent[$path_field] . '/' . $path;
                    }
                }

                $update = array(
                    'fields' => array(
                        $path_field  => $path,
                        'Parent_IDs' => $parent_ids,
                    ),
                    'where' => array(
                        'ID' => $location['ID']
                    )
                );
                $rlDb->update($update, 'multi_formats');

                // Save parents to avoid mess
                if (!in_array($location['ID'], $parents)) {
                    if (!substr_count($parent_ids, ',')) {
                        $parents[] = $location['ID'];
                    } elseif ($rlDb->getOne('ID', "`Parent_ID` = {$location['ID']}", 'multi_formats')) {
                        $parents[] = $location['ID'];
                    }
                }
            }

            $sql = "
                SELECT COUNT(*) AS `Count` FROM `{db_prefix}multi_formats`
                WHERE `Key` LIKE '{$format['Key']}\_%' AND `{$path_field}` = ''
            ";

            $total = $rlDb->getRow($sql, 'Count');

            // Completed
            if (!$total) {
                unset($_SESSION['mf_refresh_path']);
                return false;
            }
            // Next session
            elseif ($count >= $limit) {
                $_SESSION['mf_refresh_path']['parents'] = $parents;
                return true;
            }
            // Next stack
            else {
                return $this->updateLocationPath($format, $parents, $count);
            }
        } else {
            $GLOBALS['rlDebug']->logger('MultiField: Unexpected error occured, no locations found during paths refresh');
            return false;
        }

        return false;
    }

    /**
     * Check path for system usage, data_formats, account address, pages and categories
     *
     * @since 2.1.0
     *
     * @param  string  $path     - Path to check
     * @param  string  $itemKey  - Format item key to avoid checking in edit mode
     * @return boolean           - Exists or not
     */
    public function isPathExists($path = '', $itemKey = null)
    {
        global $config, $rlDb;

        if (!$path) {
            return false;
        }

        // Check data formats
        $sql = "SELECT `ID` FROM `{db_prefix}multi_formats` WHERE (";
        if ($config['mf_multilingual_path']) {
            $columns = $this->getPathColumns();
            $sql .= "`" . implode("` = '{$path}' OR `", $columns) . "` = '{$path}'";
        } else {
            $sql .= "`Path` = '{$path}'";
        }
        $sql .= ")";

        if ($itemKey) {
            $sql .= "AND `Key` != '{$itemKey}'";
        }

        if ($rlDb->getRow($sql)) {
            return true;
        }

        if ($rlDb->getOne('ID', "`Own_address` = '{$path}'", 'accounts')) {
            return true;
        }

        if ($rlDb->getOne('ID', "`Path` = '{$path}'", 'pages')) {
            return true;
        }

        if ($rlDb->getOne('ID', "`Path` = '{$path}'", 'categories')) {
            return true;
        }

        return false;
    }

    /**
     * Make path unique by adding parent level path
     *
     * @since 2.1.0 - $code parameter added
     * @since 2.0.0
     *
     * @param  string $path   - path
     * @param  array  $parent - parent level data
     * @param  string $code   - path language code, required in "mf_multilingual_path" mode
     * @return string         - unique path
     */
    public function uniquePath($path, $parent, $code = null)
    {
        global $rlDb, $config;

        $field = 'Path';

        if ($config['mf_multilingual_path']) {
            $field = 'Path_' . ($code ?: $config['lang']);
        }

        if ($rlDb->getOne('ID', "`{$field}` = '{$path}'", "multi_formats")) {
            $add_path = explode('/', $parent[$field]);
            $path .= '-' . array_pop($add_path);
        }

        return $path;
    }

    /**
     * Build location path using parent location path
     *
     * @since 2.1.0
     *
     * @param  string $path   - Item path
     * @param  array  $parent - Parent location data (Multilingual path fields)
     * @param  string $field  - Path field name
     * @return string         - Combined path
     */
    public function buildPath($path, $parent, $field)
    {
        $parent_path = $parent[$field] ?: $parent['Path_' . $GLOBALS['config']['lang']];
        return $parent_path ? $parent_path . '/' . $path : $path;
    }

    /**
     * local str2path function
     *
     * @param string $str  - string to make path from
     **/
    private function str2path($str)
    {
        return $GLOBALS['rlValid']->str2path($str);
    }

    /**
     * rebuild multi fields - rebuild sub fields
     *
     * @deprecated since 2.0.0
     **/
    public function ajaxRebuildMultiField($self, $mode = false, $no_ajax = false)
    {
    }

    /**
     * @deprecated since 2.0.0
     **/
    public function ajaxRebuildPath($self, $firstrun = false, $no_ajax = false)
    {
    }

    /**
     * @deprecated since 2.0.0
     **/
    public function updatePath($parent, $top_level = false, $nolimit = false)
    {
    }

    /**
     * @deprecated since 2.0.0
     **/
    public function updatePathPlain($parent)
    {
    }

    /**
     * ajaxImportSource - imports data from server
     *
     * @package ajax
     **/
    public function ajaxImportSource($parents = '', $table = false, $one_ignore = false, $resume = false, $updatePhrases = false)
    {
        global $_response, $rlDb;

        if (!$table) {
            return $_response;
        }

        $parent_id = (int) $_GET['parent'];

        if (!$resume) {
            if ($updatePhrases) {
                $rlDb->setTable('multi_formats');
                $parent_key = $rlDb->getOne('Key', "`ID` = {$parent_id}");
                $data = $rlDb->fetch(['Key'], ['Parent_ID' => $parent_id]);
                foreach ($data as $val) {
                    $parents .= str_replace($parent_key . '_', '', $val['Key']) . ',';
                }
            }
            elseif (empty($parents)) {
                $data = $this->getFData(array("table" => $table));
                $parents = "";
                foreach ($data as $val) {
                    $parents .= $val->Key . ",";
                }
            }

            $one_ignore = !empty($one_ignore) && $one_ignore != "false" ? 1 : 0;
            $parents = explode(",", trim($parents, ","));

            unset($_SESSION['mf_parent_ids']);
            $_SESSION['mf_import']['total'] = count($parents);
            $_SESSION['mf_import']['parents'] = $parents;
            $_SESSION['mf_import']['table'] = $table;
            $_SESSION['mf_import']['one_ignore'] = $one_ignore;
            $_SESSION['mf_import']['update_phrases'] = $updatePhrases;
            $_SESSION['mf_import']['parent_id'] = $parent_id;
            $_SESSION['mf_import']['top_key'] = $rlDb->getOne('Key', "`ID` = '{$parent_id}'", "multi_formats");
            $_SESSION['mf_import']['per_run'] = 1000;
            $_SESSION['mf_import']['available_rows'] = count($parents);

            $geo_filter = $rlDb->getOne("Geo_filter", "`Key` = '" . $_SESSION['mf_import']['top_key'] . "'", "multi_formats");
            if ($geo_filter) {
                $_SESSION['mf_import']['geo_filter'] = true;
            }
        }

        $_response->script("$('#load_cont').fadeOut();");
        if ($parents) {
            $_response->script("var item_width = width = percent = percent_value = sub_width = sub_item_width = sub_percent = sub_percent_value = sub_percent_to_show = percent_to_show = 0;");
            $_response->script("$('body').animate({ scrollTop: $('#flsource_container').offset().top-90 }, 'slow', function() { MFImport.start(); });");
        } else {
            $_response->script("$('body').animate({ scrollTop: $('#flsource_container').offset().top-90 }, 'slow');");
            $_response->script("printMessage('error', 'nothing selected')");
        }

        return $_response;
    }

    /**
     * ajaxExpandSource - lists available data items
     *
     * @package ajax
     **/
    public function ajaxExpandSource($table)
    {
        global $_response;

        if (!$table) {
            return $_response;
        }

        $data = $this->getFData(array("table" => $table));

        $GLOBALS['rlSmarty']->assign('topdata', $data);
        $GLOBALS['rlSmarty']->assign('table', $table);

        $tpl = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS . 'flsource.tpl';
        $_response->assign("flsource_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#flsource_container').fadeIn('normal')");
        $_response->script("$('html, body').animate({ scrollTop: $('#flsource_container').offset().top-25 }, 'slow');");
        $_response->call('handleSourceActs');

        return $_response;
    }

    /**
     * getFData - get data from flynax source server
     *
     * @param array $params - params to get data
     * @return json string
     **/
    public function getFData($params)
    {
        global $reefless;

        set_time_limit(0);
        $reefless->time_limit = 0;

        $vps = "http://database.flynax.com/index.php?plugin=multiField";
        $vps .= "&domain={$GLOBALS['license_domain']}&license={$GLOBALS['license_number']}";

        foreach ($params as $k => $p) {
            $vps .= "&" . $k . "=" . $p;
        }
        $content = $GLOBALS['reefless']->getPageContent($vps);

        return json_decode($content);
    }

    /**
     * ajaxListSources - lists available on server databases
     *
     * @package ajax
     **/
    public function ajaxListSources()
    {
        global $_response;

        $data = $this->getFData(array("listdata" => true));
        $GLOBALS['rlSmarty']->assign("data", $data);

        $tpl = RL_PLUGINS . 'multiField' . RL_DS . 'admin' . RL_DS . 'flsource.tpl';
        $_response->assign("flsource_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("$('#flsource_container').removeClass('block_loading');");
        $_response->script("$('#flsource_container').css('height', 'auto').fadeIn('normal')");

        return $_response;
    }

    /**
     * getMultiFormats
     * @since 2.0.0
     */
    private function getMultiFormats()
    {
        $sql = "SELECT `T1`.*, `T2`.`Order_type` FROM `{db_prefix}multi_formats` AS `T1` ";
        $sql .= "JOIN `{db_prefix}data_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`Parent_ID` = 0";

        $multi_formats = $GLOBALS['rlDb']->getAll($sql, 'Key');

        return $multi_formats;
    }

    /**
     * Get format data by ID or Key
     *
     * @since 2.2.0
     *
     * @param  int    $id  - Format ID
     * @param  string $key - Format Key
     * @return array       - Format data
     */
    public function getFormatData($id = null, $key = null)
    {
        global $rlDb;

        $id = (int) $id;
        $key = Valid::escape($key);

        if (!$id && !$key) {
            return false;
        }

        $where = $id ? ['ID' => $id] : ['Key' => $key];
        $data  = $rlDb->fetch('*', $where, null, 1, 'multi_formats', 'row');

        return $data;
    }

    /**
     * Mass actions handler
     *
     * @since 2.2.0
     *
     * @param  string $ids     - List of ids to apply action to, used slash as separater, ex: 1|2|52
     * @param  string $action  - Activate or approve
     * @return bool            - "true" if the mass action applied successfully
     */
    public function massAction($ids = null, $action = 'activate')
    {
        global $rlCache, $rlDb;

        if (!$ids || !$action) {
            return false;
        }

        $ids = explode('|', $ids);

        $GLOBALS['languages'] = $GLOBALS['rlLang']->getLanguagesList();

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxMassActions', $action, $ids);

        if ($ids) {
            foreach ($ids as $id) {
                if (in_array($action, ['activate', 'approve'])) {
                    $status = $action == 'activate' ? 'active' : 'approval';

                    $sql = "
                        UPDATE `{db_prefix}multi_formats`
                        SET `Status` = '{$status}'
                        WHERE FIND_IN_SET({$id}, `Parent_IDs`) > 0 OR `ID` = {$id}
                    ";
                    $rlDb->query($sql);
                } elseif ($action == 'delete') {
                    $this->deleteFormatItem($id);
                }
            }

            $rlCache->updateForms();
        }

        return true;
    }

    /**
     * @deprecated 2.2.0
     **/
    public function ajaxDfItemsMassActions($ids = false, $action = false) {}

    /**
     * @hook  apMixConfigItem
     * @since 2.0.0
     *
     * @param array $value
     * @param array $systemSelects - Required configs with "select" type
     */
    public function hookApMixConfigItem(&$value, &$systemSelects)
    {
        $pluginOptions = ['mf_geo_subdomains_type', 'mf_urls_in_sitemap', 'mf_home_in_sitemap', 'mf_select_interface', 'mf_popular_locations_level'];

        if (!in_array($value['Key'], $pluginOptions)) {
            return;
        }

        switch ($value['Key']) {
            case 'mf_geo_subdomains_type':
                $systemSelects[] = 'mf_geo_subdomains_type';
                break;

            case 'mf_urls_in_sitemap':
            case 'mf_home_in_sitemap':
                if (!$GLOBALS['plugins']['sitemap']) {
                    $value = null;
                }
                break;

            case 'mf_select_interface':
                $systemSelects[] = 'mf_select_interface';
                break;

            case 'mf_popular_locations_level':
                if ($GLOBALS['config']['mf_geo_data_format']) {
                    $format = $this->getGeoFilterFormat();

                    $fields = $GLOBALS['rlDb']->fetch(
                        array('Key`, `Key` AS `ID'),
                        array('Condition' => $format['Key']),
                        "AND `Key` != 'citizenship' ORDER BY `Key`",
                        null, 'listing_fields'
                    );

                    if ($fields) {
                        $systemSelects[] = 'mf_popular_locations_level';
                        $value['Values'] = $GLOBALS['rlLang']->replaceLangKeys($fields, 'listing_fields', 'name');
                    } else {
                        $value['Values'][0] = $GLOBALS['lang']['not_available'];
                    }
                }
                break;
        }
    }

    /**
     * @hook apPhpAfterAddLanguage
     * @since 2.1.0
     */
    public function hookApPhpAfterAddLanguage(&$langKey, &$isoCode, &$direction, &$locale, &$dateFormat)
    {
        global $rlDb;

        if ($GLOBALS['config']['mf_multilingual_path']) {
            $rlDb->addColumnToTable("Path_{$isoCode}", 'VARCHAR(255) NOT NULL AFTER `Key`', 'multi_formats');
            $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` ADD INDEX `Path_{$isoCode}` (`Path_{$isoCode}`)");
        }

        $this->createLangTable($isoCode);
    }

    /**
     * @hook apPhpAfterDeleteLanguage
     * @since 2.1.0
     */
    public function hookApPhpAfterDeleteLanguage(&$code)
    {
        global $rlDb;

        $rlDb->dropColumnFromTable('Path_' . $code, 'multi_formats');
        $rlDb->dropTable('multi_formats_lang_' . $code);
    }

    /**
     * Prevent set location path as page path
     *
     * @hook apPhpPagesValidate
     * @since 2.3.0
     */
    public function hookApPhpPagesValidate()
    {
        global $f_path, $multiPaths, $f_page_type, $errors, $error_fields;

        if ($f_page_type != 'system' || !$f_path) {
            return;
        }

        $this->validatePath($f_path, $multiPaths, $errors, $error_fields);
    }

    /**
     * Prevent set location path as category path (in short urls listing type mode)
     *
     * @hook apPhpCategoriesDataValidate
     * @since 2.3.0
     */
    public function hookApPhpCategoriesDataValidate()
    {
        global $f_path, $multiPaths, $errors, $error_fields;

        if (!$f_path) {
            return;
        }

        $this->validatePath($f_path, $multiPaths, $errors, $error_fields);
    }

    /**
     * Validate path fields data from the POST
     *
     * @since 2.3.0
     *
     * @param  string $path         - Common system path
     * @param  array  $multiPaths   - Multilingual paths
     * @param  array  &$errors      - Page errors
     * @param  array  &$errorFields - Page error fields
     */
    public function validatePath($path, $multiPaths = [], &$errors = [], &$errorFields = [])
    {
        global $rlDb, $config, $languages;

        $paths = [$path];

        if ($config['multilingual_paths'] && $multiPaths) {
            $paths = array_merge($paths, $multiPaths);
        }

        foreach ($paths as $index => $path) {
            $sql = "SELECT `ID` FROM `{db_prefix}multi_formats` WHERE ";

            if ($config['mf_multilingual_path']) {
                foreach ($languages as $language) {
                    $sql .= "`Path_{$language['Code']}` = '{$path}' OR ";
                }

                $sql = substr($sql, 0, -4);
            } else {
                $sql .= "`Path` = '{$path}'";
            }

            if ($rlDb->getRow($sql)) {
                $errors[] = str_replace('{path}', $path, $GLOBALS['lang']['mf_path_exists_in_mf']);
                $errorFields[] = is_numeric($index) ? 'path' : 'path[' . $index . ']';
            }
        }
    }

    /**
     * Manage path fields in data formats table, create and revert multilingual path fields.
     *
     * @since 2.1.0
     *
     * @param bool $create - Manage mode: true - to create and false to revert
     */
    public function managePathFields($create)
    {
        global $rlDb, $languages, $config;

        if (!is_bool($create)) {
            die('MultiField: managePathFields method can only accepts the boolean as first parameter.');
        }

        set_time_limit(0);

        if ($create) {
            foreach ($languages as $language) {
                // Rename system Path field
                if ($config['lang'] == $language['Code']) {
                    $new_column = 'Path_' . $config['lang'];

                    if (!$rlDb->columnExists($new_column, 'multi_formats')) {
                        $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` CHANGE `Path` `{$new_column}` varchar(255)");
                    }
                }
                // Create new path fields
                else {
                    $new_column = 'Path_' . $language['Code'];

                    if (!$rlDb->columnExists($new_column, 'multi_formats')) {
                        $rlDb->addColumnToTable($new_column, "varchar(255) NOT NULL", 'multi_formats');
                        $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` ADD INDEX `{$new_column}` (`{$new_column}`)");
                    }
                }
            }
        } else {
            $columns = $rlDb->getAll("SHOW COLUMNS FROM `{db_prefix}multi_formats` WHERE `Field` LIKE 'Path_%'");
            foreach ($columns as $column) {
                $column_lang_code = explode('_', $column['Field']);

                // Rename multilingual system path field back to original
                if ($config['lang'] == $column_lang_code[1]) {
                    $rlDb->query("ALTER TABLE `{db_prefix}multi_formats` CHANGE `{$column['Field']}` `Path` varchar(255)");
                }
                // Remove multilingual path fields
                else {
                    $rlDb->dropColumnFromTable($column['Field'], 'multi_formats');
                }
            }
        }
    }

    /**
     * Save geo format data to config cache
     *
     * @since 2.2.0
     */
    public function saveGeoFormatData()
    {
        $sql = "
            SELECT `T2`.`ID`, `T1`.`Order_type`, `T2`.`Levels`, `T2`.`Key`
            FROM `{db_prefix}data_formats` AS `T1`
            JOIN `{db_prefix}multi_formats` AS `T2` ON `T2`.`Key` = `T1`.`Key`
            WHERE `T2`.`Geo_filter` = '1' AND `T2`.`Status` = 'active' AND `T2`.`Parent_ID` = 0
        ";
        $geo_format = $GLOBALS['rlDb']->getRow($sql);
        $GLOBALS['rlConfig']->setConfig('mf_geo_data_format', json_encode($geo_format));

        $this->geoFormatData = $geo_format;
    }

    /**
     * Save multifield format keys to config cache
     *
     * @since 2.2.0
     */
    public function saveFormatKeys()
    {
        global $rlDb;

        $rlDb->setTable('multi_formats');
        $rlDb->outputRowsMap = array(false, 'Key');

        $format_keys = $rlDb->fetch(['Key'], ['Status' => 'active'], "AND `Parent_ID` = 0");
        $GLOBALS['rlConfig']->setConfig('mf_format_keys', implode('|', $format_keys));

        $this->formatKeys = $format_keys;
    }

    /**
     * Create format lang table by lang code
     *
     * @since 2.2.0
     *
     * @param string $langCode - Language code
     */
    public function createLangTable($langCode)
    {
        global $rlDb;

        $new_table = 'multi_formats_lang_' . $langCode;

        $rlDb->createTable(
            $new_table,
            "`Key` varchar(100) NOT NULL,
            `Value` varchar(32) NOT NULL,
            KEY `Key` (`Key`),
            KEY `Value` (`Value`)",
            RL_DBPREFIX,
            'ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci'
        );

        if (!$rlDb->getOne('Key', "1", $new_table)) {
            $default_table = 'multi_formats_lang_' . $GLOBALS['config']['lang'];
            $rlDb->query("
                INSERT INTO {db_prefix}{$new_table}
                SELECT * FROM {db_prefix}{$default_table};
            ");
        }
    }
}
