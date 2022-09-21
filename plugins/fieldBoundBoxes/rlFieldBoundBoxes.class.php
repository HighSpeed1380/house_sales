<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLFIELDBOUNDBOXES.CLASS.PHP
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

class rlFieldBoundBoxes extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Auto generating items limit
     */
    public $itemsLimit = 40;

    /**
     *  lang keys table elements
     */
    public $lang_elements = array(
        'pages'             => array('name', 'h1', 'title', 'meta_description', 'meta_keywords', 'des'),
        'fbb_defaults'      => array('h1', 'title', 'des', 'meta_description', 'meta_keywords'),
        'blocks'            => array('name'),
        'field_bound_items' => array('name', 'title', 'h1', 'des', 'meta_description', 'meta_keywords'),
    );

    /**
     *  Plugin install
     *
     *  @since 2.0.0
     */
    public function install()
    {
        global $rlDb, $reefless;

        $rlDb->addColumnToTable('Fbb_hidden', "ENUM('0', '1') NOT NULL DEFAULT '0' AFTER `Readonly`", 'pages');

        $rlDb->createTable(
            'field_bound_boxes',
            "`ID` INT NOT NULL AUTO_INCREMENT,
            `Key` VARCHAR( 255 ) NOT NULL,
            `Field_key` VARCHAR( 255 ) NOT NULL,
            `Multiple_items` ENUM('0','1') NOT NULL DEFAULT '0',
            `Columns` ENUM('auto', '1', '2', '3', '4') NOT NULL DEFAULT 'auto',
            `Page_columns` ENUM('auto', '2', '3', '4') NOT NULL DEFAULT 'auto',
            `Show_count` ENUM('0','1') NOT NULL DEFAULT '0',
            `Postfix` ENUM('0','1') NOT NULL DEFAULT '0',
            `Parent_page` ENUM('0','1') NOT NULL DEFAULT '0',
            `Listing_type` VARCHAR( 255 ) NOT NULL,            
            `Icons_position` ENUM('left','right','top','bottom') NOT NULL DEFAULT 'top',
            `Icons_width` INT(5) NOT NULL DEFAULT '0',
            `Icons_height` INT(5) NOT NULL DEFAULT '0',
            `Resize_icons` ENUM('0','1') NOT NULL DEFAULT '1',
            `Orientation` enum('landscape','portrait') NOT NULL DEFAULT 'landscape',
            `Show_empty` ENUM('0','1') NOT NULL DEFAULT '1',
            `Style` ENUM('text','text_pic','icon','responsive') NOT NULL default 'text',
            `Sorting` enum('position','alphabet') NOT NULL DEFAULT 'position',
            `Status` ENUM('active','approval','trash') NOT NULL default 'active',
            PRIMARY KEY (`ID`),
            KEY `Key` (`Key`)",
            RL_DBPREFIX,
            'ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci'
        );

        $rlDb->createTable(
            'field_bound_items',
            "`ID` INT(11) NOT NULL AUTO_INCREMENT,
            `Box_ID` INT(11) NOT NULL DEFAULT '0',
            `Position` INT(5) NOT NULL DEFAULT '0',
            `Key` VARCHAR(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `Path` VARCHAR(100) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `pName` VARCHAR(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `Count` INT(5) NOT NULL DEFAULT 0,
            `Icon` VARCHAR(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
            `Status` ENUM('active','approval','trash') NOT NULL DEFAULT 'active',
            PRIMARY KEY (`ID`),
            KEY `Box_ID` (`Box_ID`)",
            RL_DBPREFIX,
            'ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci'
        );

        // Copy svg icons
        $source_dir = RL_PLUGINS . 'fieldBoundBoxes/static/icons/';
        $dist_dir = RL_FILES . 'fieldBoundBoxes/svg_icons/';
        $icons = $reefless->scanDir($source_dir);

        $reefless->rlMkdir($dist_dir);

        foreach ($icons as $name) {
            copy($source_dir . $name, $dist_dir . $name);
        }
    }

    /**
     *  Plugin unInstall
     *
     *  @since 2.0.0
     */
    public function uninstall()
    {
        global $rlDb;

        if ($GLOBALS['config']['trash']) {
            $rlDb->outputRowsMap = [false, 'Key'];
            $boxes = $rlDb->fetch('Key', null, null, null, 'field_bound_boxes');

            foreach ($boxes as $box_key) {
                $this->deleteBox($box_key);
            }
        }

        $rlDb->dropTables(array('field_bound_boxes', 'field_bound_items'));

        $rlDb->dropColumnFromTable('Fbb_hidden', 'pages');

        $GLOBALS['reefless']->deleteDirectory(RL_FILES . 'fieldBoundBoxes');
    }

    /**
     * @hook apTplListingPlansForm
     *
     * @param array  $out   - ajax request response
     * @param string $item  - request action
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        switch ($item) {
            case 'fbbDeleteBox':
                Valid::escape($_REQUEST['box_key']);

                $fbbResult = $this->deleteBox($_REQUEST['box_key']);
                break;

            case 'fbbDeleteItem':
                Valid::escape($_REQUEST['box_key']);
                Valid::escape($_REQUEST['item_key']);

                $fbbResult = $this->deleteItem($_REQUEST['item_key'], $_REQUEST['box_key']);
                break;

            case 'fbbRecopyBoxItems':
                Valid::escape($_REQUEST['box_key']);
                $this->buildBoxItems($_REQUEST['box_key']);
                $fbbResult = true;

                break;

            case 'fbbDeleteIcon':
                Valid::escape($_REQUEST['item_id']);

                $fbbResult = $this->deleteIcon($_REQUEST['item_id']);
                break;

            case 'fbbRecount':
                $this->recount();
                $this->updateBoxContent();

                $fbbResult = true;
                break;

            case 'fbbGetIcons':
                $limit = 55;
                $start = $_REQUEST['start'] ? $_REQUEST['start'] * $limit : 0;
                $dir = RL_FILES . 'fieldBoundBoxes/svg_icons/';
                $icons = $GLOBALS['reefless']->scanDir($dir);

                if ($q = $_REQUEST['q']) {
                    foreach ($icons as $index => $name) {
                        if (!is_numeric(strpos($name, $q))) {
                            unset($icons[$index]);
                        }
                    }
                }

                $total = count($icons);

                $out = array(
                    'results' => array_slice($icons, $start, $limit),
                    'total'   => $total,
                    'next'    => $total > $start + $limit
                );

                $fbbResult = true;
                break;

            case 'fbbAddItem':
                $box_id = (int) $_REQUEST['boxID'];
                $item_key = Valid::escape($_REQUEST['itemKey']);
                $item_name = Valid::escape($_REQUEST['itemName']);

                if ($box_id && $item_key) {
                    $out = $this->addItem($box_id, $item_key, $item_name);
                } else {
                    $out['status'] = 'error';
                }
                break;
        }

        if ($fbbResult) {
            $out['status'] = 'ok';
        }
    }

    /**
     * Delete box item
     *
     * @since 2.0.0
     *
     * @param  string $keyKey   - Box key
     * @param  string $itemKey  - Item key
     * @param  string $itemName - Item name
     * @return array            - Results data
     */
    public function addItem($boxID, $itemKey, $itemName = null)
    {
        global $rlDb, $lang;

        if ($rlDb->getOne('ID', "`Key` = '{$itemKey}' AND `Box_ID` = {$boxID}", 'field_bound_items')) {
            $out = array(
                'message' => $GLOBALS['rlLang']->getSystem('fbb_item_exists_in_box'),
                'status' => 'ERROR'
            );
        } else {
            $phrase_key = 'data_formats+name' . $itemKey;
            $item_name = $itemName ?: $GLOBALS['lang'][$phrase_key];
            $path = $GLOBALS['rlValid']->str2path($item_name ?: $itemKey);
            $position = $rlDb->getRow("
                SELECT MAX(`Position`) AS `Position`
                FROM `{db_prefix}field_bound_items`
                WHERE `Box_ID` = {$boxID}
            ");

            $insert = array(
                'Key' => $itemKey,
                'pName' => $lang[$phrase_key] ? $phrase_key : '',
                'Path' => $path,
                'Box_ID' => $boxID,
                'Position' => $position['Position'] + 1
            );
            $rlDb->insertOne($insert, 'field_bound_items');

            $box_key = $rlDb->getOne('Key', "`ID` = {$boxID}", 'field_bound_boxes');

            $this->recount($box_key);
            $this->updateBoxContent($box_key);

            $out = array(
                'status' => 'OK',
                'message' => $lang['item_added']
            );
        }

        return $out;
    }

    /**
     * Delete box item
     *
     * @param string $key - item key
     * @param string $box - box key
     */
    public function deleteItem($key, $box_key)
    {
        global $rlDb;

        if (!$key || !$box_key) {
            return;
        }

        $box_id = $rlDb->getOne('ID', "`Key` = '{$box_key}'", 'field_bound_boxes');
        $icon = $rlDb->getOne('Icon', "`Key` = '{$key}'", 'field_bound_items');

        if ($icon) {
            unlink(RL_FILES . $icon);
        }

        foreach ($this->lang_elements['pages'] as $area) {
            $sql = "
                DELETE FROM `{db_prefix}lang_keys`
                WHERE `Key` = 'field_bound_items+{$area}+{$key}'
            ";
            $rlDb->query($sql);
        }

        $sql = "
            DELETE FROM `{db_prefix}field_bound_items`
            WHERE `Key` = '{$key}' AND `Box_ID` = {$box_id}
            LIMIT 1
        ";
        $rlDb->query($sql);

        $this->updateBoxContent($box_key);

        return true;
    }

    /**
     * Delete item
     *
     * @param string $key - box key
     */
    public function deleteBox($key)
    {
        global $rlDb;

        if (!$key) {
            return;
        }

        $GLOBALS['reefless']->loadClass('Actions');
        $GLOBALS['rlValid']->sql($key);
        $box_info = $rlDb->fetch(['ID', 'Field_key'], ['Key' => $key], null,  null, 'field_bound_boxes', 'row');

        $lang_keys[] = 'blocks+name+' . $key;
        foreach ($this->lang_elements['pages'] as $area) {
            $lang_keys[] = 'pages+' . $area . '+' . $key;
        }
        foreach ($this->lang_elements['fbb_defaults'] as $area) {
            $lang_keys[] = 'fbb_defaults+' . $area . '+' . $key;
        }
        foreach ($this->lang_elements['field_bound_items'] as $area) {
            $lang_keys[] = 'field_bound_items+' . $area . '+' . $box_info['Field_key'] .'_%';
        }

        $rlDb->delete(['Key' => $key], 'field_bound_boxes');
        $rlDb->delete(['Key' => $key], 'blocks');
        $rlDb->delete(['Key' => $key], 'pages');
        $rlDb->delete(['Box_ID' => $box_info['ID']], 'field_bound_items', null, null);

        foreach ($lang_keys as $lang_key) {
            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE '{$lang_key}'");
        }

        $GLOBALS['reefless']->deleteDirectory(RL_FILES . 'fieldBoundBoxes' . RL_DS . $key);

        return true;
    }

    /**
     * Delete icon
     *
     * @param int $item_id - item id
     */
    public function deleteIcon($item_id)
    {
        global $rlDb;

        $item_id = (int) $item_id;
        $item_info = $rlDb->fetch(
            array('Box_ID', 'Icon'),
            array('ID' => $item_id),
            null,
            null,
            'field_bound_items',
            'row'
        );

        if ($item_info['Icon'] && false === strpos($item_info['Icon'], '/svg_icons/')) {
            unlink(RL_FILES . $item_info['Icon']);
        }

        $sql = "UPDATE `{db_prefix}field_bound_items` SET `Icon`= '' WHERE `ID` = {$item_id}";
        $rlDb->query($sql);

        $box_key = $rlDb->getOne('Key', "`ID` = {$item_info['Box_ID']}", 'field_bound_boxes');
        $this->updateBoxContent($box_key);

        return true;
    }

    /**
     * Generate content of a php box
     *
     * @param int $box_id - box id
     *
     * @return string $content
     */
    public function generateBoxContent($box_key)
    {
        global $rlDb;

        $sql = "SELECT `T2`.`Path` as `Page_path`, `T1`.* ";
        $sql .= "FROM `{db_prefix}field_bound_boxes` AS `T1` ";
        $sql .= "JOIN `{db_prefix}pages` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
        $sql .= "WHERE `T1`.`Key` = '{$box_key}' ";

        $box_info = $rlDb->getRow($sql);
        $box_info['Path'] = $box_info['Page_path'];

        $condition = !$box_info['Show_empty'] ? ' AND `Count` > 0 ' : '';
        $items = $rlDb->fetch('*',
            array('Box_ID' => $box_info['ID'], 'Status' => 'active'),
            $condition . 'ORDER BY `Position`',
            null,
            'field_bound_items'
        );

        $field_condition = $rlDb->getOne("Condition", "`Key` = '" . $box_info['Field_key'] . "'", "listing_fields");
        $replacement = $field_condition ? $field_condition . '_' : $box_info['Field_key'] . '_';

        $content = 'global $rlSmarty;';

        if ($items) {
            $content .= '$options = json_decode(\'' . json_encode($items) . '\', true);';
        }

        // Prepare multifield phrases
        if ($items && $this->isNewMultiField() && $this->isFormatBelongToMultiField($field_condition)) {
            $lang_phrases = $this->getMultiFieldLangPhrases($items);

            $content .= '$json_phrases = <<< JSON' . PHP_EOL;
            $content .= json_encode($lang_phrases) . PHP_EOL;
            $content .= 'JSON;' . PHP_EOL;
            $content .= '$box_phrases = json_decode($json_phrases, true);';
            $content .= '$rlSmarty->assign("fbb_box_phrases", $box_phrases);';
        }

        $content .= '$GLOBALS["reefless"]->loadClass("FieldBoundBoxes", null, "field_bound_boxes");';
        $content .= '$box_info = json_decode(\'' . json_encode($box_info) . '\', true);';

        $content .= '$GLOBALS["rlFieldBoundBoxes"]->prepareOptions($options, $box_info);';
        $content .= '$GLOBALS["rlFieldBoundBoxes"]->recountByLocation($options, $box_info);';
        $content .= '$rlSmarty->assign("fbb_options", $options);';
        $content .= '$rlSmarty->assign("fbb_box", $box_info);';

        $content .= '$rlSmarty->display(RL_PLUGINS . "fieldBoundBoxes" . RL_DS . "field-bound_box.tpl");';

        return $content;
    }

    /**
     * Get phrases of multifield related items
     *
     * @since 2.0.0
     *
     * @param  array $items - Box items array
     * @return array        - Phrases
     */
    public function getMultiFieldLangPhrases($items)
    {
        $languages = $GLOBALS['languages'] ?: $GLOBALS['rlLang']->getLanguagesList();
        $lang_phrases = null;

        foreach ($languages as $lang_item) {
            $sql = "
                SELECT `T2`.`Value`, `T1`.`Key`
                FROM `{db_prefix}multi_formats` AS `T1`
                LEFT JOIN `{db_prefix}multi_formats_lang_{$lang_item['Code']}` AS `T2` ON `T1`.`Key` = `T2`.`Key`
                WHERE 
            ";

            foreach ($items as $item) {
                $sql .= "`T1`.`Key` = '{$item['Key']}' OR ";
            }

            $sql = substr($sql, 0, -4);

            $lang_phrases[$lang_item['Code']] = $GLOBALS['rlDb']->getAll($sql, ['Key', 'Value']);
        }

        return $lang_phrases;
    }

    /**
     * Prepare options data
     *
     * @since 2.0.0
     *
     * @param array $options - Options data
     * @param array $boxInfo - Realated box data
     */
    public function prepareOptions(&$options, $boxInfo)
    {
        global $reefless;

        foreach ($options as &$option) {
            // Set link
            $link = $reefless->getPageUrl($boxInfo['Key'], ['item' => $option['Path']]);

            if (!$boxInfo['Postfix']) {
                $link = str_replace('.html', '/', $link);
            }

            $option['link'] = $link;

            // Set icon
            if ($option['Icon']) {
                $option['Icon'] = RL_FILES_URL . $option['Icon'];
            }

            // Set name
            if ($GLOBALS['lang']['field_bound_items+name+' . $option['Key']]) {
                $option['name'] = $GLOBALS['lang']['field_bound_items+name+' . $option['Key']];
            } elseif ($option['pName'] && $GLOBALS['lang'][$option['pName']]) {
                $option['name'] = $GLOBALS['lang'][$option['pName']];
            } else {
                $option['name'] = $GLOBALS['rlSmarty']->_tpl_vars['fbb_box_phrases'][RL_LANG_CODE][$option['Key']];
            }
        }

        if ($boxInfo['Sorting'] == 'alphabet') {
            $reefless->rlArraySort($options, 'name');
        }
    }

    /**
     * Update content of box or boxes
     *
     * @param string $box_keys - a single key string or set of keys "key1, key2"
     *                           if no keys passed will update all existing boxes
     *
     * @since 2.0.0 - Parameters updated ($box_keys set as 1-st and others removed)
     */
    public function updateBoxContent($box_keys = false)
    {
        global $rlDb;

        if ($box_keys) {
            $add_where = "AND FIND_IN_SET(`Key`, '{$box_keys}')";
        }

        $boxes = $rlDb->fetch(array('Key'), array('Status' => 'active'), $add_where, null, 'field_bound_boxes');

        if (!$boxes) {
            return false;
        }

        foreach ($boxes as $key => $box) {
            $update[$key]['fields']['Content'] = $this->generateBoxContent($box['Key']);
            $update[$key]['where']['Key'] = $box['Key'];
        }

        $rlDb->rlAllowHTML = true;

        return $rlDb->update($update, 'blocks');
    }

    /**
     * Build Box Items - copy (re-copy) field bound items from field values
     *
     * @since 2.0.0 - Added the third parameter $unlinkMedia
     * @since 2.0.0 - Added the second parameter $actionMode
     *
     * @param string $boxKey     - Box key
     * @param string $actionMode - add or edit
     * @param bool   $unlinkMedia - Unset media data from items if new selected type does not support pictures
     */
    public function buildBoxItems($boxKey, $actionMode = 'add', $unlinkMedia = false)
    {
        global $rlDb, $config;

        $box_info = $rlDb->fetch('*', array('Key' => $boxKey), null, null, 'field_bound_boxes', 'row');

        if (!$box_info) {
            return false;
        }

        // Remove media files if new type (edit mode) of box does not support it
        if ($unlinkMedia) {
            foreach ($rlDb->fetch('*', array('Box_ID' => $box_info['ID']), null, null, 'field_bound_items') as $value) {
                if ($value['Icon']) {
                    unlink(RL_FILES . $value['Icon']);
                }
            }
        }

        $tmp_fields[0] = $rlDb->fetch('*', array('Key' => $box_info['Field_key']), null, null, 'listing_fields', 'row');
        $tmp_values = $GLOBALS['rlCommon']->fieldValuesAdaptation($tmp_fields, 'listing_fields');

        $multiple_items = false;

        if ($this->isNewMultiField() && $this->isFormatBelongToMultiField($tmp_fields[0]['Condition'])) {
            $multiple_items = true;

            $tmp_values[0]['Values'] = $this->getPopularMultiFieldItems($tmp_fields[0]['Condition']);
        }

        if ($multiple_items) {
            // Limit items
            $tmp_values[0]['Values'] = array_slice($tmp_values[0]['Values'], 0, $this->itemsLimit);

            // Mark box as multiple items entry
            if ($actionMode == 'add') {
                $multiple_update = array(
                    'fields' => array('Multiple_items' => '1'),
                    'where' => array('Key' => $boxKey)
                );
                $rlDb->updateOne($multiple_update, 'field_bound_boxes');
            }
        }

        $pos = 1;
        $actual_keys = [];
        $paths = [];

        foreach ($tmp_values[0]['Values'] as $key => $value) {
            $name = $value['name'] ?: $GLOBALS['lang'][$value['pName']];
            $path = $GLOBALS['rlValid']->str2path($name);
            $key = $value['Key'] ?: $value['ID'];
            $actual_keys[] = $key;

            if (strlen($path) < 3) {
                $key_parts = explode('_', $value['Key']);
                $path = $GLOBALS['rlValid']->str2path(implode('_', array_reverse($key_parts)));
            }

            if (!$rlDb->getOne('ID', "`Key` = '{$key}' AND `Box_ID` = {$box_info['ID']}", 'field_bound_items')) {
                if ($paths[$path]) {
                    $path = ($paths[$path] + 1) . '-' . $path;
                }

                $insert_values[] = array(
                    'Key' => $key,
                    'pName' => $value['pName'],
                    'Path' => $path,
                    'Box_ID' => $box_info['ID'],
                    'Position' => $pos
                );

                $paths[$path] += 1;
            }
            $pos++;
        }
        unset($tmp_values, $tmp_fields);

        if (!$multiple_items) {
            $sql = "
                DELETE FROM `{db_prefix}field_bound_items`
                WHERE `Box_ID` = {$box_info['ID']} AND `Key` NOT IN ('" . implode("','", $actual_keys) . "')
            ";
            $rlDb->query($sql);
        }

        if ($insert_values) {
            $rlDb->insert($insert_values, 'field_bound_items');
        }

        $this->recount($boxKey);
        $this->updateBoxContent($boxKey);
    }

    /**
     * Get popular multifField items by format key
     *
     * @since 2.0.0
     *
     * @param  string $key - Format key
     * @return array       - Popular items
     */
    public function getPopularMultiFieldItems($key)
    {
        global $rlDb;

        $GLOBALS['reefless']->loadClass('GeoFilter', null, 'multiField');

        $listing_fields = $rlDb->fetch(
            array('Key'),
            array(
                'Condition' => $key,
                'Status'    => 'active',
            ),
            "ORDER BY `Key`",
            null,
            'listing_fields'
        );

        end($listing_fields);
        $last_level = current($listing_fields);
        $last_level = $last_level['Key'];

        $locale = defined('RL_LANG_CODE') ? RL_LANG_CODE : $GLOBALS['config']['lang'];

        $sql = "
            SELECT `T2`.`Key`, COUNT(`T1`.`{$last_level}`) AS `count`, `T3`.`Value` AS `name`
            FROM `{db_prefix}listings` AS `T1`
            LEFT JOIN `{db_prefix}multi_formats` AS `T2` ON `T1`.`{$last_level}` = `T2`.`Key`
            LEFT JOIN `{db_prefix}multi_formats_lang_{$locale}` AS `T3` ON `T2`.`Key` = `T3`.`Key`
            WHERE `T1`.`{$last_level}` != '' AND `T1`.`{$last_level}` != '0' AND `T1`.`Status` = 'active'
            AND `T2`.`Status` = 'active'
            GROUP BY `T1`.`{$last_level}`
            ORDER BY `count` DESC
            LIMIT {$this->itemsLimit}
        ";

        $cities = $rlDb->getAll($sql);

        return $cities;
    }

    /**
     * Is Image - function to check if input  is the valid image
     *
     * @param string $image - image url or system path
     */
    public function isImage($image)
    {
        $allowed_types = array(
            'image/gif',
            'image/jpeg',
            'image/jpg',
            'image/png',
        );

        $img_details = getimagesize($image);

        if (in_array($img_details['mime'], $allowed_types)) {
            return true;
        }

        return false;
    }

    /**
     * Get multifield format related fields
     *
     * @since 2.0.0
     *
     * @param  string $formatKey - Format key
     * @param  string $fieldKey  - Format key
     * @return array             - Fields data
     */
    public function getRelatedFields($formatKey, $fieldKey)
    {
        return $GLOBALS['rlDb']->fetch(
            array('Key'),
            array('Condition' => $formatKey),
            "AND `Key` LIKE '{$fieldKey}%'",
            null,
            'listing_fields'
        );
    }

    /**
     * Recount item in box or all boxes
     *
     * @param string $boxKey - Box key
     */
    public function recount($boxKey = '')
    {
        global $rlDb;

        $sql = "SELECT `T1`.`ID`, `T1`.`Listing_type`, `T2`.`Condition`, `T1`.`Field_key` ";
        $sql .= "FROM `{db_prefix}field_bound_boxes` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Field_key` ";
        $sql .= "WHERE `T1`.`Status` = 'active' AND `Show_count` = '1' ";

        if ($boxKey) {
            $sql .= " AND `T1`.`Key` = '{$boxKey}'";
        }

        $boxes = $rlDb->getAll($sql);

        foreach ($boxes as $key => $box) {
            $sql = "
                SELECT `Key` AS `Values`
                FROM `{db_prefix}field_bound_items`
                WHERE `Status` = 'active' AND `Box_ID` = {$box['ID']}
            ";
            $items = $rlDb->getAll($sql, [false, 'Values']);
            $counts = array();

            if (!$box['Condition']) {
                foreach ($items as &$item) {
                    $item = str_replace($box['Field_key'] . '_', '', $item);
                }
            }

            if ($box['Field_key'] == 'posted_by') {
                $sql = "SELECT COUNT(*) AS `Count`, `T7`.`Type` AS `Field` FROM `{db_prefix}listings` AS `T1` ";

                $GLOBALS['rlHook']->load('listingsModifyJoin', $sql);

                $sql .= "JOIN `{db_prefix}accounts` AS `T7` ON `T1`.`Account_ID` = `T7`.`ID` ";
                $sql .= "WHERE `T1`.`Status` = 'active' ";
                $sql .= "AND FIND_IN_SET(`T7`.`Type`, '" . implode(',', $items) . "') ";

                $GLOBALS['rlHook']->load('listingsModifyWhere', $sql);

                $sql .= "GROUP BY `T7`.`Type`";
                $counts = $rlDb->getAll($sql);
            } else {
                if ($this->isNewMultiField() && $this->isFormatBelongToMultiField($box['Condition'])) {
                    $fields = $this->getRelatedFields($box['Condition'], $box['Field_key']);

                    foreach ($fields as $field) {
                        $sql = "SELECT COUNT(*) AS `Count`, `T1`.`{$field['Key']}` AS `Field` FROM `{db_prefix}listings` AS `T1` ";

                        if ($box['Listing_type']) {
                            $sql .= "JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
                        }

                        $GLOBALS['rlHook']->load('listingsModifyJoin', $sql);

                        $sql .= "WHERE `T1`.`Status` = 'active' ";
                        $sql .= "AND FIND_IN_SET(`T1`.`{$field['Key']}`, '" . implode(',', $items) . "') ";

                        if ($box['Listing_type']) {
                            $sql .= "AND `T3`.`Type` = '{$box['Listing_type']}' ";
                        }

                        $GLOBALS['rlHook']->load('listingsModifyWhere', $sql);

                        $sql .= "GROUP BY `T1`.`{$field['Key']}`";

                        if ($group_counts = $rlDb->getAll($sql)) {
                            $counts += $group_counts;
                        }
                    }
                } else {
                    $sql = "SELECT COUNT(*) AS `Count`, `T1`.`{$box['Field_key']}` AS `Field` FROM `{db_prefix}listings` AS `T1` ";

                    if ($box['Listing_type']) {
                        $sql .= "JOIN `{db_prefix}categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
                    }

                    $GLOBALS['rlHook']->load('listingsModifyJoin', $sql);

                    $sql .= "WHERE `T1`.`Status` = 'active' ";
                    $sql .= "AND FIND_IN_SET(`T1`.`{$box['Field_key']}`, '" . implode(',', $items) . "') ";

                    if ($box['Listing_type']) {
                        $sql .= "AND `T3`.`Type` = '{$box['Listing_type']}' ";
                    }

                    $GLOBALS['rlHook']->load('listingsModifyWhere', $sql);

                    $sql .= "GROUP BY `T1`.`{$box['Field_key']}`";
                    $counts = $rlDb->getAll($sql);
                }
            }

            $rlDb->query("UPDATE `{db_prefix}field_bound_items` SET `Count` = 0 WHERE `Box_ID` = {$box['ID']}");

            foreach ($counts as $row) {
                $sql = "UPDATE `{db_prefix}field_bound_items` SET `Count` = {$row['Count']} ";

                if (!$box['Condition']) {
                    if ($box['Field_key'] == 'posted_by') {
                        $sql .= "WHERE `Key` = '{$row['Field']}' ";
                    } else {
                        $sql .= "WHERE `Key` = '" . $box['Field_key'] . "_" . $row['Field'] . "' ";
                    }
                } else {
                    $sql .= "WHERE `Key` = '{$row['Field']}' ";
                }
                $sql .= "AND `Box_ID` = {$box['ID']}";

                $rlDb->query($sql);
            }
        }
    }

    /**
     * Is installed MultiField plugin has new data structure
     *
     * @since 2.0.0
     *
     * @return bool - is new or not
     */
    public function isNewMultiField()
    {
        return version_compare($GLOBALS['plugins']['multiField'], '2.2.0', '>=');
    }

    /**
     * Defines is the format key belongs to the multifield fields
     *
     * @since 2.0.0
     *
     * @param  string $formatKey - Format key
     * @return bool              - Is belong
     */
    public function isFormatBelongToMultiField($formatKey) {
        if (!$GLOBALS['config']['mf_format_keys'] || !$formatKey) {
            return false;
        }

        $belong = false;

        $multifield_keys = explode('|', $GLOBALS['config']['mf_format_keys']);

        if ($multifield_keys && in_array($formatKey, $multifield_keys)) {
            $belong = true;
        }

        return $belong;
    }

    /**
     * @deprecated 2.0.0
     */
    public function getListings($data = false, $listing_type = '', $order = '', $order_type = 'ASC', $start = 0, $limit = 10)
    {}

    /**
     * @hook apTplControlsForm
     *
     * @since 2.0.0
     */
    public function hookApTplControlsForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'fieldBoundBoxes' . RL_DS . 'admin' . RL_DS . 'refreshEntry.tpl');
    }

    /**
     * Decrease Related Items
     *
     * @param int $listing_id
     */
    public function decreaseRelatedItems($listing_id = 0)
    {
        return $this->affectRelatedItems($listing_id, 'decrease');
    }

    /**
     * Increase Related Items
     *
     * @param int $listing_id
     */
    public function increaseRelatedItems($listing_id = 0)
    {
        return $this->affectRelatedItems($listing_id, 'increase');
    }

    /**
     * Affect Related Items
     *
     * @param  int    $listingID - Listing ID
     * @param  string $mode      - Mode: 'increase' or 'decrease'
     * @return array             - Success status
     */
    public function affectRelatedItems($listingID = false, $mode = false)
    {
        global $rlDb;

        if (!$listingID || !$mode) {
            return false;
        }

        $cat_id = $rlDb->getOne("Category_ID", "`ID` = '{$listingID}'", 'listings');
        $listing_type = $rlDb->getOne("Type", "`ID` = '{$cat_id}'", 'categories');

        $fields = $this->getBoxesListingFields($cat_id);

        if (!$fields) {
            return false;
        }

        $fields_keys = array_keys($fields);

        $sql = "
            SELECT `T1`.`" . implode("`, `T1`.`", $fields_keys) . "`, `T2`.`Type` AS `posted_by`
            FROM `{db_prefix}listings` AS `T1`
            LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`Account_ID` = `T2`.`ID`
            WHERE `T1`.`ID` = {$listingID}
        ";
        $listing_info = $rlDb->getRow($sql);

        // Add 'posted_by' field to the fields array because the field may not be added to the submit listing form
        $fields['posted_by'] = ['Key' => 'posted_by'];
        $fields_keys[] = 'posted_by';

        foreach ($fields as $field) {
            if (!$listing_info[$field['Key']]) {
                continue;
            }

            $sql = "UPDATE `{db_prefix}field_bound_items` AS `T1` ";
            $sql .= "JOIN `{db_prefix}field_bound_boxes` AS `T2` ON `T2`.`ID` = `T1`.`Box_ID` ";
            if ($mode == 'increase') {
                $sql .= "SET `T1`.`Count` = `T1`.`Count` + 1 ";
            } else {
                $sql .= "SET `T1`.`Count` = IF(`T1`.`Count`, `T1`.`Count` - 1, 0) ";
            }
            if ($field['Key'] == 'posted_by') {
                $sql .= "WHERE `T1`.`Key` = '" . $listing_info[$field['Key']] . "' ";
            }
            elseif (!$field['Condition']) {
                $sql .= "WHERE `T1`.`Key` = '" . $field['Key'] . "_" . $listing_info[$field['Key']] . "' ";
            } else {
                $sql .= "WHERE `T1`.`Key` = '" . $listing_info[$field['Key']] . "' ";
            }

            $sql .= "AND (`T2`.`Listing_type` = '{$listing_type}' OR `T2`.`Listing_type` = '') ";

            $rlDb->query($sql);
        }

        $sql = "SELECT GROUP_CONCAT(`Key`) as `Keys` FROM `{db_prefix}field_bound_boxes` ";
        $sql .= "WHERE FIND_IN_SET(`Field_key`, '" . implode(",", $fields_keys) . "')";
        $box_keys = $rlDb->getRow($sql, 'Keys');

        return $this->updateBoxContent($box_keys);
    }

    /**
     * Get available fields
     *
     * @param  int $categoryID - Category ID
     * @return array           - Fields data array as [['Key1', 'Condition1'], ['Key2', 'Condition2']...]
     */
    public function getBoxesListingFields($categoryID = false)
    {
        global $rlDb;

        if (!$categoryID) {
            return false;
        }

        $cat_info = $rlDb->fetch('*', array('ID' => $categoryID), null, null, 'categories', 'row');

        $listing_type = $GLOBALS['rlListingTypes']->types[$cat_info['Type']];
        $general_cat = $listing_type['Cat_general_cat'];
        $general_only = $listing_type['Cat_general_only'];

        if ($general_only) {
            $relations = Flynax\Utils\Category::getFormRelations($general_cat, $listing_type);
        } else {
            $relations = Flynax\Utils\Category::getFormRelations($cat_info['ID'], $listing_type);

            if (!$relations && $general_cat) {
                $relations = Flynax\Utils\Category::getFormRelations($general_cat, $listing_type);
            }
        }

        if (!$relations) {
            return false;
        }

        $out = [];
        $field_ids = [];

        foreach ($relations as $group) {
            if ($group['Group_ID']) {
                if ($ids = explode(',', $group['Fields'])) {
                    $field_ids = array_merge($field_ids, $ids);
                }
            } elseif ($group['Fields']) {
                $field_ids[] = $group['Fields'];
            }
        }

        if (!$field_ids) {
            return false;
        }

        $fields = $rlDb->getAll("
            SELECT `Key`, `Condition` FROM `{db_prefix}listing_fields`
            WHERE `ID` IN ('" . implode("','", $field_ids) . "')
        ", 'Key');

        $rlDb->setTable('field_bound_boxes');
        $rlDb->outputRowsMap = [false, 'Field_key'];
        foreach ($rlDb->fetch('Field_key') as $box_field_key) {
            if ($result = $this->preg_grep_keys('~' . $box_field_key . '~', $fields)) {
                $out = array_merge($out, $result);
            }
        }

        return $out;
    }

    /**
     * Returns the array consisting of the elements of the array that match the given pattern by keys.
     *
     * @since 2.0.0
     *
     * @param  string $pattern - Search pattern
     * @param  array  $input   - Input array to search in
     * @param  array  $flags   - Flags, if set to PREG_GREP_INVERT, it returns the elements of the input array that DO NOT match the given pattern
     * @return array           - Returns an array indexed using the keys from the array.
     */
    public function preg_grep_keys($pattern, $input, $flags = 0) {
        return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
    }

    /**
     * Recount neccessary fields after listing editing
     *
     * @param int $listingID      - Listing ID
     * @param array $diff         - Fields difference as array with sub-arrays
     *                              array(field => (new => new_value, old => old_value), field2 ..)
     */
    public function editListing($listingID, $diff)
    {
        global $rlDb;

        $fields = [];

        $sql = "SELECT `T2`.`Type` FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
        $sql .= "WHERE `T1`.`ID` = {$listingID} ";
        $listing_type = $rlDb->getRow($sql, 'Type');

        if (!$listing_type) {
            return false;
        }

        foreach ($diff as $field_key => $diff_data) {
            if ($diff_data['new']) {
                $this->increaseCount($diff_data['field'], $diff_data['new'], $listing_type);
            }

            if ($diff_data['old']) {
                $this->decreaseCount($diff_data['field'], $diff_data['old'], $listing_type);
            }

            $fields[] = $field_key;
        }

        $sql = "SELECT GROUP_CONCAT(`Key`) as `Keys` FROM `{db_prefix}field_bound_boxes` ";
        $sql .= "WHERE FIND_IN_SET(`Field_key`, '" . implode(',', $fields) . "')";
        $sql .= "AND (`Listing_type` = '{$listing_type}' OR `Listing_type` ='') ";
        $box_keys = $rlDb->getRow($sql, 'Keys');

        return $this->updateBoxContent($box_keys);
    }

    /**
     * Change count of box item
     *
     * @since 2.0.0
     *
     * @param array  $fieldData   - Related box field data
     * @param string $value       - Key of item value
     * @param string $listingType - Box related listing type key
     * @param string $mode        - Mode, 'increase' or 'decrease'
     */
    private function changeCount($fieldData, $value, $listingType, $mode)
    {
        $key = ($fieldData['Condition'] ? '' : $fieldData['Key'] . '_') . $value;

        if (false === strpos($fieldData['Key'], '_level')) {
            $box_key = $fieldData['Key'];
        } else {
            $key_parts = explode('_level', $fieldData['Key']);
            $box_key = $key_parts[0];
        }

        $sql = "UPDATE `{db_prefix}field_bound_items` AS `T1` ";
        $sql .= "JOIN `{db_prefix}field_bound_boxes` AS `T2` ON `T2`.`ID` = `T1`.`Box_ID` ";
        $sql .= "SET `T1`.`Count` = " . ($mode == 'increase' ? '`T1`.`Count` + 1 ' : 'IF(`T1`.`Count`, `T1`.`Count` - 1, 0) ');
        $sql .= "WHERE `T1`.`Key` ='" . $key . "' ";
        $sql .= "AND `T2`.`Field_key` = '" . $box_key . "' ";
        $sql .= "AND (`T2`.`Listing_type` = '{$listingType}' OR `T2`.`Listing_type` = '') ";

        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Increase count of box item
     *
     * @since 2.0.0
     *
     * @param array  $fieldData   - Related box field data
     * @param string $value       - Key of item value
     * @param string $listingType - Box related listing type key
     */
    public function increaseCount($fieldData, $value, $listingType)
    {
        $this->changeCount($fieldData, $value, $listingType, 'increase');
    }

    /**
     * Decrease count of box item
     *
     * @since 2.0.0
     *
     * @param array  $fieldData   - Related box field data
     * @param string $value       - Key of item value
     * @param string $listingType - Box related listing type key
     */
    public function decreaseCount($fieldData, $value, $listingType)
    {
        $this->changeCount($fieldData, $value, $listingType, 'decrease');
    }

    /**
     * @hook apPhpListingsAfterAdd
     *
     * @since 2.0.0
     */
    public function hookApPhpListingsAfterAdd()
    {
        $this->increaseRelatedItems($GLOBALS['listing_id']);
    }

    /**
     * @hook apPhpListingsAfterEdit
     *
     * @since 2.0.0
     */
    public function hookApPhpListingsAfterEdit()
    {
        global $listing, $data, $rlDb, $info;

        if ($info['Status'] == 'active' && $listing['Status'] != 'active') {
            $this->increaseRelatedItems($listing['ID']);
        } else if ($info['Status'] != 'active' && $listing['Status'] == 'active') {
            $this->decreaseRelatedItems($listing['ID']);
        } else {
            $diff = [];
            $fields = $this->getBoxesListingFields($listing['Category_ID']);

            foreach ($fields as $field) {
                if ($data[$field['Key']] != $listing[$field['Key']]) {
                    $diff[$field['Key']] = array(
                        'old' => $listing[$field['Key']],
                        'new' => $data[$field['Key']],
                        'field' => $field
                    );
                }

                if ($diff) {
                    $this->editListing($listing['ID'], $diff);
                }
            }
        }
    }

    /**
     * @hook phpListingsAjaxDeleteListing
     *
     * @since 2.0.0
     */
    public function hookPhpListingsAjaxDeleteListing($info)
    {
        if ($info['Status'] != 'pending' && $info['Status'] != 'incomplete') {
            $this->decreaseRelatedItems($info['ID']);
        }
    }

    /**
     * @hook apPhpListingFieldsAfterEdit
     *
     * @since 2.0.0
     */
    public function hookApPhpListingFieldsAfterEdit()
    {
        global $rlDb, $f_data;

        if ($GLOBALS['f_type'] == 'select') {
            if (!$f_data['data_format']) {
                $sql = "SELECT `ID`, `Key`, `Field_key` FROM `{db_prefix}field_bound_boxes` ";
                $sql .= "WHERE `Field_key` = '{$f_data['key']}'";
                $boxes = $rlDb->getAll($sql);

                foreach ($boxes as $bk => $box) {
                    $items = $_POST['select'];

                    $sql = "DELETE FROM `{db_prefix}field_bound_items` ";
                    $sql .= "WHERE NOT FIND_IN_SET(
                        REPLACE(`Key`, '" . $box['Field_key'] . "_',''), '" . implode(",", array_keys($items)) . "') ";
                    $sql .= "AND `Box_ID` = {$box['ID']}";
                    $rlDb->query($sql);

                    $sql = "SELECT GROUP_CONCAT(REPLACE(`Key`,'" . $box['Field_key'] . "_','')) as `items` ";
                    $sql .= "FROM `{db_prefix}field_bound_items` ";
                    $sql .= "WHERE `Box_ID` = {$box['ID']}";
                    $fb_items = $rlDb->getRow($sql);

                    $k = 0;
                    foreach ($items as $key => $value) {
                        if (!in_array($key, explode(',', $fb_items['items']))) {
                            $position = $rlDb->getOne(
                                "Position",
                                "`Box_ID` = {$box['ID']} ORDER BY `Position` DESC",
                                'field_bound_items'
                            );

                            $fb_insert[$k]['Key'] = $box['Field_key'] . "_" . $key;
                            $fb_insert[$k]['pName'] = 'listing_fields+name+' . $fb_insert[$k]['Key'];
                            $fb_insert[$k]['Box_ID'] = $box['ID'];
                            $fb_insert[$k]['Position'] = ++$position;
                            $fb_insert[$k]['Status'] = 'active';
                            $k++;
                        }
                    }

                    if ($fb_insert) {
                        $rlDb->insert($fb_insert, 'field_bound_items');
                    }
                }
            }
        }
    }

    /**
     * @hook apExtDataFormatsUpdate
     *
     * @since 2.0.0
     */
    public function hookApExtDataFormatsUpdate()
    {
        global $rlDb;

        $item_id = $GLOBALS['id'];
        $item_value = $GLOBALS['value'];
        $field = $GLOBALS['field'];

        $item_info = $rlDb->fetch(
            array('Key', 'Parent_ID'),
            array("ID" => $item_id),
            null,
            null,
            'data_formats',
            'row'
        );

        if ($field == 'Status' && $item_info['Parent_ID']) {
            $sql = "UPDATE `{db_prefix}field_bound_items` ";
            $sql .= "SET `Status` ='{$item_value}' ";
            $sql .= "WHERE `Key` = '{$item_info['Key']}'";
            $rlDb->query($sql);

            $this->updateBoxContent();
        } elseif ($field == 'Status' && !$item_info['Parent_ID']) {
            $fields = $rlDb->fetch(
                array('Key'),
                array("Condition" => $item_info['Key']),
                null,
                null,
                'listing_fields'
            );

            foreach ($fields as $fk => $field) {
                $sql = "SELECT GROUP_CONCAT(`Key`) as `keys` FROM `{db_prefix}field_bound_boxes` ";
                $sql .= "WHERE `Field_key` = '{$field['Key']}'";
                $box_keys = $rlDb->getRow($sql);

                $sql = "UPDATE `{db_prefix}field_bound_boxes` SET `Status` ='{$item_value}' ";
                $sql .= "WHERE FIND_IN_SET(`Key`, '{$box_keys['keys']}')";
                $rlDb->query($sql);

                $sql = "UPDATE `{db_prefix}blocks` SET `Status` ='{$item_value}' ";
                $sql .= "WHERE FIND_IN_SET(`Key`, '{$box_keys['keys']}')";
                $rlDb->query($sql);
            }
        }
    }

    /**
     * @hook apExtListingsUpdate
     *
     * @since 2.0.0
     */
    public function hookApExtListingsUpdate()
    {
        if ($GLOBALS['field'] == 'Status') {
            $new_status = $GLOBALS['value'];
            $old_status = $GLOBALS['listing_info']['Status'];
            $listing_id = $GLOBALS['id'];

            if ($new_status == 'active' && $old_status != 'active') {
                $this->increaseRelatedItems($listing_id);
            } elseif ($new_status != 'active' && $old_status == 'active') {
                $this->decreaseRelatedItems($listing_id);
            }
        }
    }

    /**
     * @hook apPhpFormatsAjaxDeleteItem
     *
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxDeleteItem()
    {
        global $rlDb;

        $df_item = $GLOBALS['key'];

        $sql = "SELECT GROUP_CONCAT(`T1`.`Key`) AS `Keys` FROM `{db_prefix}field_bound_boxes` AS `T1` ";
        $sql .= "JOIN `{db_prefix}field_bound_items` AS `T2` ON `T2`.`Box_ID` = `T1`.`ID` ";
        $sql .= "WHERE `T2`.`Key` = '{$df_item}'";
        $box_keys = $rlDb->getRow($sql, 'Keys');

        if (!$box_keys) {
            return;
        }

        foreach (explode(',', $box_keys) as $box_key) {
            $this->deleteItem($df_item, $box_key);
        }
    }

    /**
     * @hook apPhpFormatsAjaxMassActions
     *
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxMassActions($action, $ids)
    {
        global $rlDb;

        if ($action == 'delete') {
            $sql = "SELECT GROUP_CONCAT(`T1`.`Key`) AS `Keys` FROM `{db_prefix}field_bound_boxes` AS `T1` ";
            $sql .= "JOIN `{db_prefix}field_bound_items` AS `T2` ON `T2`.`Box_ID` = `T1`.`ID` ";
            $sql .= "JOIN `{db_prefix}data_formats` AS `T3` ON `T3`.`Key` = `T2`.`Key` ";
            $sql .= "WHERE FIND_IN_SET(`T3`.`ID`,'" . implode(',', $ids) . "') ";

            $box_keys = $rlDb->getRow($sql, 'Keys');

            $sql = "DELETE `T2` FROM `{db_prefix}data_formats` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}field_bound_items` AS `T2` ON `T2`.`Key` = `T1`.`Key` ";
            $sql .= "WHERE FIND_IN_SET(`T1`.`ID`, '" . implode(',', $ids) . "') ";
            $rlDb->query($sql);

            $this->updateBoxContent($box_keys);
        }
    }

    /**
     * @hook apPhpFormatsAjaxAddItem
     *
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxAddItem()
    {
        global $rlDb, $insert;

        $sql = "SELECT `T1`.`ID`, `T1`.`Key`, `T3`.`Key` AS `Format_key` FROM `{db_prefix}field_bound_boxes` AS `T1` ";
        $sql .= "JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Field_key` ";
        $sql .= "JOIN `{db_prefix}data_formats` AS `T3` ON `T3`.`Key` = `T2`.`Condition` ";
        $sql .= "WHERE `T3`.`ID` = {$insert['Parent_ID']}";
        $boxes = $rlDb->getAll($sql);

        $box_keys = '';
        foreach ($boxes as $bk => $box) {
            $max_position = $rlDb->getOne(
                "Position",
                "`Box_ID` = {$box['ID']} ORDER BY `Position` DESC",
                'field_bound_items');

            $max_position++;

            $key = str_replace($box['Format_key'] . '_', '', $insert['Key']);
            $path = $GLOBALS['rlValid']->str2path($key);

            $fb_insert = array(
                'Key' => $insert['Key'],
                'pName' => 'data_formats+name+' . $insert['Key'],
                'Path' =>  $path,
                'Box_ID' => $box['ID'],
                'Position' => $max_position,
                'Status' => $insert['Status']
            );

            $rlDb->insertOne($fb_insert, 'field_bound_items');
            $box_keys .= $box['Key'] . ',';
        }

        $box_keys = substr($box_keys, 0, -1);

        $this->updateBoxContent($box_keys);
    }

    /**
     * @hook apPhpFormatsAjaxEditItem
     *
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxEditItem($update)
    {
        $fb_update = $update;
        unset($fb_update['fields']['Default']);

        $GLOBALS['rlDb']->updateOne($fb_update, 'field_bound_items');

        $this->updateBoxContent();
    }

    /**
     * @hook apPhpFieldsAjaxDeleteField
     *
     * @since 2.0.0
     */
    public function hookApPhpFieldsAjaxDeleteField()
    {
        global $rlDb, $field, $rlActions;

        if (!$field['Key']) {
            $field['Key'] = $rlDb->getOne('Key', "`ID` = '{$field['ID']}'", 'listing_fields');
        }

        $boxes = $rlDb->fetch(
            array('Key', 'ID'),
            array('Field_key' => $field['Key']),
            null,
            null,
            'field_bound_boxes'
        );

        if ($boxes) {
            foreach ($boxes as $bk => $box) {
                $this->deleteBox($box['Key']);
            }
        }
    }

    /**
     * @hook apPhpFormatsAjaxDeleteFormat
     *
     * @since 2.0.0
     */
    public function hookApPhpFormatsAjaxDeleteFormat()
    {
        global $rlDb;

        $df_item_key = $GLOBALS['key'];

        $sql = "SELECT `T1`.`Key`, `T1`.`ID` FROM `{db_prefix}field_bound_boxes` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Field_key` ";
        $sql .= "WHERE `T2`.`Condition` = '{$df_item_key}'";

        $boxes = $rlDb->getAll($sql);

        if ($boxes) {
            foreach ($boxes as $bk => $box) {
                $this->deleteBox($box['Key']);
            }
        }
    }

    /**
     * @hook apExtListingFieldsUpdate
     *
     * @since 2.0.0
     */
    public function hookApExtListingFieldsUpdate()
    {
        global $rlDb;

        $field_id = $GLOBALS['id'];
        $field_info = $rlDb->fetch(
            array('Type', 'Key'),
            array('ID' => $field_id),
            null,
            null,
            'listing_fields',
            'row'
        );

        if ($field_info['Type'] == 'select' && $GLOBALS['field'] == 'Status') {
            $new_status = $GLOBALS['value'];

            $sql = "SELECT GROUP_CONCAT(`T1`.`Key`) as `Keys` ";
            $sql .= "FROM `{db_prefix}field_bound_boxes` AS `T1` ";
            $sql .= "JOIN `{db_prefix}listing_fields` AS `T2` ON `T2`.`Key` = `T1`.`Field_key` ";
            $sql .= "WHERE `T2`.`ID` = '{$field_id}'";

            $box_keys = $rlDb->getRow($sql, 'Keys');

            $sql = "UPDATE `{db_prefix}field_bound_boxes` SET `Status` = '{$new_status}' ";
            $sql .= "WHERE FIND_IN_SET(`Key`, '{$box_keys}') ";
            $rlDb->query($sql);

            $sql = "UPDATE `{db_prefix}blocks` SET `Status` = '{$new_status}' ";
            $sql .= "WHERE FIND_IN_SET(`Key`, '{$box_keys}')";
            $rlDb->query($sql);
        }
    }

    /**
     * @hook apExtBlocksUpdate
     *
     * @since 2.0.0
     */
    public function hookApExtBlocksUpdate()
    {
        global $rlDb, $field, $value, $id;

        if ($field == 'Status') {
            $key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'blocks');
            $this->relatedItemsAction($key, $value);
        }
    }

    /**
     * @hook apPhpBlocksAfterEdit
     *
     * @since 2.0.0
     */
    public function hookApPhpBlocksAfterEdit()
    {
        global $update_data;

        $this->setStatus($update_data['fields']['Status'], $update_data['where']['Key']);
    }

    /**
     * Update page related FBB entry and box
     *
     * @since 2.0.0
     *
     * @param string $pageKey - Page key
     * @param string $status  - New status to set
     */
    public function relatedItemsAction($pageKey, $status)
    {
        global $rlDb;

        if (!$rlDb->getOne('Key', "`Key` = '{$pageKey}'", 'field_bound_boxes')) {
            return;
        }

        $update = array(
            'fields' => array('Status' => $status),
            'where' => array('Key' => $pageKey),
        );
        $rlDb->updateOne($update, 'field_bound_boxes');
        $rlDb->updateOne($update, 'blocks');
    }

    /**
     * @hook apExtPagesUpdate
     *
     * @since 2.0.0
     */
    public function hookApExtPagesUpdate()
    {
        global $rlDb, $field, $value, $id;

        if ($field == 'Status') {
            $key = $rlDb->getOne('Key', "`ID` = '{$id}'", 'pages');
            $this->relatedItemsAction($key, $value);
        }
    }

    /**
     * @hook apPhpPagesAfterEdit
     *
     * @since 2.0.0
     */
    public function hookApPhpPagesAfterEdit()
    {
        global $rlDb, $update_data;

        $this->relatedItemsAction($update_data['where']['Key'], $update_data['fields']['Status']);
    }

    /**
     * @hook apPhpPagesBeforeEdit
     *
     * @since 2.0.0
     */
    public function hookApPhpPagesBeforeEdit()
    {
        global $update_data;

        $this->setStatus($update_data['fields']['Status'], $update_data['where']['Key']);
    }

    /**
     * @hook afterListingDone
     *
     * @since 2.0.0
     */
    public function hookAfterListingDone(&$instance, $update, $is_free)
    {
        if ($update['fields']['Status'] == 'active') {
            $this->increaseRelatedItems($instance->listingID);
        }
    }

    /**
     * @hook apPhpListingsMassActions
     *
     * @since 2.0.0
     */
    public function hookApPhpListingsMassActions($ids, $action)
    {
        global $rlDb;

        $ids = explode('|', $ids);
        if (!$ids) {
            return false;
        }

        if ($action == 'activate' || $action == 'approve' || $action == 'renew') {
            $mode = $action == 'approve' ? 'decrease' : 'increase';
            $new_status = $action == 'approve' ? 'approval' : 'active';

            $sql = "SELECT `ID`, `Status` FROM `{db_prefix}listings` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '" . implode($ids, ',') . "')";

            $statuses = $rlDb->getAll($sql, array("ID", "Status"));

            foreach ($ids as $id) {
                if ($action == 'delete' && $statuses[$id] == 'active') {
                    $this->affectRelatedItems($id, 'decrease');
                } elseif ($statuses[$id] != $new_status) {
                    $this->affectRelatedItems($id, $mode);
                }
            }
        }
    }

    /**
     * @hook editListingAdditionalInfo
     *
     * @since 2.0.0
     */
    public function hookEditListingAdditionalInfo(&$instance, $data, $info)
    {
        global $rlDb;

        $old_listing = $instance->listingData;
        $new_listing = $data;
        $diff        = [];
        $fields      = $this->getBoxesListingFields($instance->listingData['Category_ID']);

        foreach ($fields as $field) {
            if ($old_listing[$field['Key']] != $new_listing[$field['Key']]) {
                $diff[$field['Key']]['old'] = $old_listing[$field['Key']];
                $diff[$field['Key']]['new'] = $new_listing[$field['Key']];

                $diff[$field['Key']] = array(
                    'old' => $old_listing[$field['Key']],
                    'new' => $new_listing[$field['Key']],
                    'field' => $field
                );
            }
        }

        if ($diff) {
            $this->editListing($instance->listingID, $diff);
        }
    }

    /**
     * @hook phpListingsUpgradeListing
     *
     * @since 2.0.0
     */
    public function hookPhpListingsUpgradeListing($plan_info, $plan_id, $listing_id)
    {
        global $rlDb;

        if ($GLOBALS['listing_info']['Status'] != 'active') {
            $cur_status = $rlDb->getOne("Status", "`ID` = '{$listing_id}'", "listings");

            if ($cur_status == 'active') {
                $this->increaseRelatedItems($listing_id);
            }
        }
    }

    /**
     * Filter listings by fbb field
     *
     * @hook listingsModifyWhere
     *
     * @since 2.0.0
     */
    public function hookListingsModifyWhere()
    {
        if (!defined('FBB_MODE')) {
            return;
        }

        global $sql, $field_info, $item_value, $fbb_info;

        if (!$fbb_info['Listing_type']) {
            $sql = str_replace(" AND `T3`.`Type` = ''", '', $sql);
        }

        if (defined('FBB_MULTIFIELD_MODE')) {
            $fields = $this->getRelatedFields($field_info['Condition'], $field_info['Key']);

            if ($fields) {
                $sql .= "AND (";
                foreach ($fields as $field) {
                    $sql .= "`T1`.`{$field['Key']}` = '{$item_value}' OR ";
                }
                $sql = substr($sql, 0, -4);
                $sql .= ") ";
            }
        } else {
            $field = $field_info['Key'] == 'posted_by' ? '`TA`.`Type`' : '`T1`.`' . $field_info['Key'] . '`';
            $sql .= "AND {$field} = '{$item_value}' ";
        }
    }

    /**
     * Disable dbCount mode
     *
     * @hook listingsModifyPreSelect
     *
     * @since 2.0.0
     */
    public function hookListingsModifyPreSelect(&$dbcount)
    {
        if (!defined('FBB_MODE')) {
            return;
        }

        $dbcount = 0;
    }

    /**
     * Join accounts table if the filter field equals 'posted_by'
     *
     * @hook listingsModifyJoin
     *
     * @since 2.0.0
     */
    public function hookListingsModifyJoin(&$dbcount)
    {
        if (!defined('FBB_MODE')) {
            return;
        }

        global $sql, $field_info, $item_value;

        if ($field_info['Key'] == 'posted_by') {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `TA` ON `T1`.`Account_ID` = `TA`.`ID` ";
        }
    }

    /**
     * @hook cronAdditional
     *
     * @since 2.0.0
     */
    public function hookCronAdditional()
    {
        $this->recount();
        $this->updateBoxContent();
    }

    /**
     * @hook afterImport
     *
     * @since 2.0.0
     */
    public function hookAfterImport()
    {
        $this->recount();
        $this->updateBoxContent();
    }

    /**
     * @hook  sitemapExcludedPages
     *
     * @since 2.0.0
     */
    public function hookSitemapExcludedPages(&$urls)
    {
        global $rlDb;

        $rlDb->outputRowsMap = 'Key';
        $where = array('Plugin' => 'fieldBoundBoxes', 'Fbb_hidden' => '1');

        foreach ($rlDb->fetch(array('Key'), $where, null, null, 'pages') as $page_key => $page) {
            $urls = array_merge($urls, array($page_key));
        }
    }

    /**
     * @hook  sitemapAddPluginUrls
     * @since 2.0.0
     */
    public function hookSitemapAddPluginUrls(&$urls = array())
    {
        global $rlDb;

        $items_urls  = array();

        foreach ($rlDb->fetch('*', array('Status' => 'active'), null, null, 'field_bound_boxes') as $box) {
            $items_where = array('Status' => 'active', 'Box_ID' => $box['ID']);

            foreach ($rlDb->fetch('*', $items_where, null, null, 'field_bound_items') as $item) {
                $url = $GLOBALS['reefless']->getPageUrl(
                    $box['Key'],
                    array($GLOBALS['pages'][$box['Key']] => $item['Path'])
                );

                if ($GLOBALS['config']['mod_rewrite'] && !$box['Postfix']) {
                    $url = str_replace('.html', '/', $url);
                }

                $items_urls[] = $url;
            }
        }

        if ($items_urls) {
            $urls = array_merge($urls, $items_urls);
        }
    }

    /**
     * Unique key by name
     */
    public function uniqKeyByName($name = false, $table = false, $prefix = false)
    {
        if (false === function_exists('utf8_is_ascii')) {
            loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');
        }

        if (!utf8_is_ascii($name)) {
            $name = utf8_to_ascii($name);
        }
        $name = strtolower($GLOBALS['rlValid']->str2key($name));

        if ($prefix !== false) {
            $name = $prefix . $name;
        }

        $sql = "SELECT COUNT(`Key`) AS `count` FROM `{db_prefix}{$table}` WHERE `Key` REGEXP '^{$name}(_[0-9]+)*$'";
        $exists = $GLOBALS['rlDb']->getRow($sql);

        if ($exists['count'] > 0) {
            return "{$name}_" . intval($exists['count'] + 1);
        }

        return $name;
    }

    /**
     * Add/Edit box action
     *
     * @since 2.0.0
     *
     * @param array  $data   - Box, page and default data array
     * @param string $key    - Box key
     * @param string $action - add or edit
     */
    public function addEditAction($data, $key, $action = 'add')
    {
        global $rlDb, $config;

        // Insert/Update field bound box data
        $fbb_data = array(
            'Key'    => $key,
            'Status' => $data['status'],
            'Sorting' => $data['sorting'],
        );

        $fbb_data = array_merge($fbb_data, array_change_key_case($data['fbb']));

        if ($action == 'edit') {
            $update['where']['Key'] = $key;
            $update['fields'] = $fbb_data;

            $rlDb->updateOne($update, 'field_bound_boxes');
        } else {
            $rlDb->insertOne($fbb_data, 'field_bound_boxes');
        }

        // Insert/Update site box data
        $box_data = array(
            'Key'           => $key,
            'Status'        => $data['status'],
            'Position'      => 1,
            'Side'          => $data['box']['side'],
            'Type'          => 'php',
            'Tpl'           => $data['box']['tpl'],
            'Header'        => $data['box']['header'],
            'Page_ID'       => $data['box']['pages'] ? implode(',', $data['box']['pages']) : '',
            'Category_ID'   => $data['categories'] ? implode(',', $data['categories']) : '',
            'Subcategories' => empty($data['box']['subcategories']) ? 0 : 1,
            'Sticky'        => empty($data['box']['show_on_all']) ? 0 : 1,
            'Cat_sticky'    => empty($data['box']['cat_sticky']) ? 0 : 1,
            'Plugin'        => 'fieldBoundBoxes',
        );

        if ($action == 'edit') {
            unset($box_data['Position']);

            $update['where']['Key'] = $key;
            $update['fields'] = $box_data;

            $rlDb->updateOne($update, 'blocks');
        } else {
            $rlDb->insertOne($box_data, 'blocks');
        }

        $this->addEditLangData($key, $data, $action, 'blocks');

        // Insert/Update page data
        $page_data = array(
            'Page_type'  => 'system',
            'Login'      => '0',
            'Key'        => $key,
            'Path'       => $data['page']['path'][$config['lang']],
            'Get_vars'   => '',
            'Controller' => 'listings_by_field',
            'Tpl'        => '1',
            'Menus'      => '',
            'Deny'       => '0',
            'Plugin'     => 'fieldBoundBoxes',
            'No_follow'  => '0',
            'Modified'   => '',
            'Status'     => $data['status'],
            'Readonly'   => '1',
            'Fbb_hidden'  => $fbb_data['parent_page'] == '0' ? '1' : '0',
        );

        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['allLangs'] as $language) {
                if ($language['Code'] == $config['lang']) {
                    continue;
                }

                $page_data['Path_' . $language['Code']] = $data['page']['path'][$language['Code']];
            }
        }

        if ($action == 'edit') {
            $update['where']['Key'] = $key;
            $update['fields'] = $page_data;

            $rlDb->updateOne($update, 'pages');
        } else {
            $position = $rlDb->getRow("SELECT MAX(`Position`) AS `Max` FROM `{db_prefix}pages` WHERE FIND_IN_SET('1', `Menus`)");
            $page_data['Position'] = ++$position['Max'];

            $rlDb->insertOne($page_data, 'pages');
        }

        $this->addEditLangData($key, $data, $action, 'pages');

        $this->addEditLangData($key, $data, $action, 'fbb_defaults');
    }

    /**
     * Add/Edit Language Data
     *
     * @since 2.0.0
     *
     * @param string $key    - Item key
     * @param array  $data   - Lang phrases data array
     * @param string $action - Action: 'add', 'edit' or 'edit_item'
     * @param string $table  - Table (module) name
     */
    public function addEditLangData($key, $data, $action, $table)
    {
        global $rlDb;

        $phrase_module = 'frontEnd';
        $phrase_target = '';

        switch ($table) {
            case 'blocks':
                $post_data_stack = 'box';
                $phrase_module = 'box';
                $phrase_target = $key;
                break;
            case 'pages':
                $post_data_stack = 'page';
                $phrase_target = 'listings_by_field';
                break;
            case 'fbb_defaults':
                $post_data_stack = 'defaults';
                $phrase_target = 'listings_by_field';
                break;
            case 'field_bound_items':
                $post_data_stack = 'item';
                $phrase_target = 'listings_by_field';
                break;
        }

        foreach ($this->lang_elements[$table] as $area) {
            $set_phrase_target = $phrase_target;
            $set_phrase_module = $phrase_module;

            if ($table == 'pages') {
                if (in_array($area, ['name', 'title'])) {
                    $set_phrase_target = '';
                }
                if ($area == 'name') {
                    $set_phrase_module = 'common';
                }
            } elseif ($table == 'field_bound_items' && $area == 'name') {
                $set_phrase_target = '';
            }

            foreach ($GLOBALS['languages'] as $lang_item) {
                $lang_entry_key = $table . '+' . $area . '+' . $key;
                $lang_entry_value = $data[$post_data_stack][$area][$lang_item['Code']];

                if ($lang_entry_value) {
                    $lang_keys_entry = array(
                        'Code'       => $lang_item['Code'],
                        'Module'     => $set_phrase_module,
                        'Status'     => 'active',
                        'Key'        => $lang_entry_key,
                        'Value'      => $lang_entry_value,
                        'Plugin'     => 'fieldBoundBoxes',
                        'Target_key' => $set_phrase_target
                    );

                    if (in_array($action, ['edit', 'edit_item'])) {
                        $existing_entry = $rlDb->fetch(
                            array('Key', 'Value'),
                            array('Key' => $lang_entry_key, 'Code' => $lang_item['Code']),
                            null,
                            null,
                            'lang_keys',
                            'row'
                        );
                    }

                    if ($existing_entry) {
                        if ($existing_entry['Value'] != $lang_entry_value) {
                            $update = array(
                                'fields' => $lang_keys_entry,
                                'where' => array(
                                    'Key' => $existing_entry['Key'],
                                    'Code' => $lang_item['Code']
                                )
                            );
                            $rlDb->updateOne($update, 'lang_keys');
                        }
                    } else {
                        $rlDb->insertOne($lang_keys_entry, 'lang_keys');
                    }
                } else {
                    $sql = "
                        DELETE FROM `{db_prefix}lang_keys`
                        WHERE `Key` = '{$lang_entry_key}' AND `Code` = '{$lang_item['Code']}'
                        LIMIT 1
                    ";
                    $rlDb->query($sql);
                }
            }
        }
    }

    /**
     * Rel Prev Next
     * Add rel-prev, rel-next attributes to page meta tags for proper pages indexing
     *
     * @since 2.0.0
     *
     * @param $item_path - item path to add to the paging url
     */
    public function metaRelPrevNext($item_path)
    {
        global $page_info, $config;

        if ($page_info['robots']['noindex']) {
            return false;
        }

        $page_url = SEO_BASE;
        $add_url = $item_path;

        $count = $this->calc;
        $per_page = $config['listings_per_page'];

        $paging_tpls = $GLOBALS['rlCommon']->buildPagingUrlTemplate($add_url);

        $current_page = $page_info['current'] ?: $_GET['pg'];

        if ($count && $count > $per_page) {
            $next_pg = $current_page ? $current_page + 1 : 2;
            $prev_pg = $current_page > 1 ? $current_page - 1 : 0;

            if ($count >= $per_page * $current_page) {
                $page_info['rel_next'] = str_replace('[pg]', $next_pg, $paging_tpls['tpl']);
            }

            if ($current_page == 2) {
                $page_info['rel_prev'] = $paging_tpls['first'];
            } elseif ($current_page) {
                $page_info['rel_prev'] = str_replace('[pg]', $prev_pg, $paging_tpls['tpl']);
            }
        }
    }

    /**
     * Php Meta Rel prev-next
     *
     * @param $item_path - item path to add to the paging url
     *
     * @param $add_url   - additional url
     * @param $custom    - custom page url
     * @param $count     - items count
     * @param $per_page  - items per page
     */
    public function hookPhpMetaRelPrevNext(&$add_url, $custom, &$count, &$per_page)
    {
        if ($GLOBALS['page_info']['Controller'] == 'listings_by_field' && $GLOBALS['item_path']) {
            $add_url = $GLOBALS['item_path'];
            $count = $this->calc;
            $per_page = $GLOBALS['config']['listings_per_page'];
        }
    }

    /**
     * Coorect hreflang links
     *
     * @hook phpMetaTags
     * @since 2.0.0
     */
    public function hookPhpMetaTags(&$pageInfo)
    {
        $name = $GLOBALS['tpl_settings']['name'];
        $GLOBALS['rlSmarty']->assign('fbb_is_nova', boolval(strpos($name, '_nova')));
        $GLOBALS['rlSmarty']->assign('fbb_flex_layout', boolval(strpos($name, 'cragslist') || strpos($name, 'craigslist') || strpos($name, 'brand')));

        if ($pageInfo['Plugin'] != 'fieldBoundBoxes') {
            return;
        }

        global $fbb_info, $item_info;

        if (!$item_info) {
            return;
        }

        $hreflang = &$GLOBALS['rlSmarty']->_tpl_vars['hreflang'];

        if (is_array($hreflang) && count($hreflang) <= 1) {
            return;
        }

        foreach ($hreflang as $code => &$link) {
            $url = $GLOBALS['reefless']->getPageUrl($pageInfo['Key'], array('item' => $item_info['Path']), $code);

            if (!$fbb_info['Postfix']) {
                $url = str_replace('.html', '/', $url);
            }

            $link = $url;
        }
    }

    /**
     * Hook ApExtPagesSql
     *
     * @since 2.0.0
     */
    public function hookApExtPagesSql()
    {
        global $sql;

        $sql = str_replace("WHERE", "WHERE `Fbb_hidden` != '1' AND", $sql);
    }

    /**
     * Add custom plugin styles
     *
     * @hook tplHeader
     * @since 2.0.0
     */
    public function hookTplHeader()
    {
        global $rlSmarty;

        $display_header = false;

        foreach ($GLOBALS['blocks'] as $block) {
            if ($block['Plugin'] == 'fieldBoundBoxes') {
                $display_header = true;
                break;
            }
        }

        if ($rlSmarty->_tpl_vars['home_page_special_block']['Plugin'] == 'fieldBoundBoxes') {
            $display_header = true;
        }

        if ($GLOBALS['page_info']['Plugin'] == 'fieldBoundBoxes') {
            $display_header = true;
        }

        if ($display_header) {
            $rlSmarty->display(RL_PLUGINS . 'fieldBoundBoxes' . RL_DS . 'header.tpl');
        }
    }

    /**
     * Delete related fbb data on box removing
     * Add missing pages names on settings page for multifield filtration section
     *
     * @since 2.0.0
     *
     * @hook apPhpIndexBottom
     */
    public function hookApPhpIndexBottom()
    {
        // Add missing pages names
        if ($GLOBALS['plugins']['multiField'] && $GLOBALS['cInfo']['Controller'] == 'settings') {
            global $rlDb, $lang;

            $rlDb->outputRowsMap = [false, 'Key'];
            if ($pages = $rlDb->fetch(['Key'], ['Parent_page' => '0'], null, null, 'field_bound_boxes')) {
                $rlDb->outputRowsMap = ['Key', 'Value'];
                $phrases = $rlDb->fetch(
                    ['Key', 'Value'],
                    ['Code' => RL_LANG_CODE],
                    "AND `Key` IN ('blocks+name+" . implode("','blocks+name+", $pages) . "')",
                    null,
                    'lang_keys'
                );

                foreach ($phrases as $key => $value) {
                    $lang[str_replace('blocks+', 'pages+', $key)] = $value;
                }
            }
        }

        // Delete related fbb data on box removing
        if ($GLOBALS['cInfo']['Controller'] == 'blocks') {
            if ($_POST['xjxfun'] == 'ajaxDeleteBlock' && $_POST['xjxargs'][0]) {
                $this->deleteBox($_POST['xjxargs'][0]);
            }
        }
    }

    /**
     * Remove hidden parent pages
     *
     * @since 2.0.0
     *
     * @hook apPhpBlocksGetPageWhere
     */
    public function hookApPhpBlocksGetPageWhere(&$where)
    {
        $where .= "AND `Fbb_hidden` = '0' ";
    }

    /**
     * Add plugin pages to filtration options of MultiField plugin
     *
     * @hook apPhpMultifieldGetAvailablePages
     * @since 2.0.0
     */
    public function hookApPhpMultifieldGetAvailablePages(&$multiFieldObject)
    {
        $multiFieldObject->addAvailablePage('listings_by_field');
    }

    /**
     * Set Status
     *
     * @since 2.0.0
     *
     * @param $status - status
     * @param $key    - item key
     */
    public function setStatus($status, $key)
    {
        global $rlDb;

        $if_fbb = $rlDb->getOne('Key', "`Key` = '{$key}'", 'field_bound_boxes');
        if (!$if_fbb) {
            return false;
        }

        foreach (array('field_bound_boxes', 'blocks', 'pages') as $table) {
            $rlDb->query("UPDATE `{db_prefix}{$table}` SET `Status` = '{$status}' WHERE `Key` = '{$key}'");
        }
    }

    /**
     * Recount count option by applied location condition
     *
     * @since 2.0.0 - The second parameter changed from $fieldKey string to $boxInfo array
     * @since 1.3.0
     *
     * @param array $options  - Options array to recount items in
     * @param array $boxInfo - FBB box data
     */
    public function recountByLocation(&$options = null, $boxInfo = null)
    {
        if (!$options || !$boxInfo || !$GLOBALS['plugins']['multiField']) {
            return;
        }

        $geo_data = $GLOBALS['rlGeoFilter']->geo_filter_data;

        if (!$geo_data['applied_location'] || !in_array($boxInfo['Key'], $geo_data['filtering_pages'])) {
            return;
        }

        $sql = "
            SELECT COUNT(*) AS `Count`, `T1`.`{$boxInfo['Field_key']}`
            FROM `{db_prefix}listings` AS `T1`
            WHERE `T1`.`{$boxInfo['Field_key']}` != ''
        ";

        $GLOBALS['rlGeoFilter']->modifyWhere($sql);

        $sql .= "GROUP BY `T1`.`{$boxInfo['Field_key']}`";

        $items = $GLOBALS['rlDb']->getAll($sql, [$boxInfo['Field_key'], 'Count']);

        foreach ($options as $key => &$option) {
            $option_key = str_replace('data_formats+name+', '', $option['pName']);
            if ($items[$option_key]) {
                $option['Count'] = $items[$option_key];
            } else {
                $option['Count'] = 0;
            }
        }
    }

    /**
     * Update to 1.0.1 version
     */
    public function update101()
    {
        $GLOBALS['rlDb']->query("ALTER TABLE `{db_prefix}field_bound_boxes` ADD `Page_columns` INT( 2 ) NOT NULL");
        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}field_bound_boxes` SET `Page_columns`= 3 WHERE 1");
    }

    /**
     * Update to 1.1.0 version
     */
    public function update110()
    {
        $GLOBALS['rlDb']->query("UPDATE `{db_prefix}pages` SET `Readonly`= '1' WHERE `Key` = 'listings_by_field'");
        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}hooks` WHERE `Name` = 'pageinfoArea' AND `Plugin` = 'fieldBoundBoxes'");
        $GLOBALS['rlDb']->query("ALTER TABLE `{db_prefix}field_bound_boxes` ADD `Show_empty` ENUM('0','1') NOT NULL DEFAULT '1'");
    }

    /**
     * Update to 1.2.0 version
     */
    public function update120()
    {
        // Remove hook
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'fieldBoundBoxes' AND `Name` = 'seoBase'
        ");

        // Update page name
        $sql = "UPDATE `{db_prefix}lang_keys` SET `Value` = 'Field Bound Boxes' WHERE `Key` LIKE 'pages+%+listings_by_field'";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $rlDb, $reefless;

        // Remove unnecessary phrases
        $phrases = [
            'fb_icons_sizes_hint',
            'fb_enable_icons',
            'fb_listings_count',
            'fb_cols',
            'fb_page_cols',
            'fb_block_name',
            'fb_add',
            'fb_edit',
            'fb_notice_path_exist',
            'fb_boxes_list',
            'ext_field_bound_manager',
            'ext_field_bound_items_manager',
            'fb_icon_added',
            'fb_manage_icon',
            'fb_edit_item',
            'pages+name+listings_by_field',
            'pages+title+listings_by_field',
        ];
        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
            WHERE `Plugin` = 'fieldBoundBoxes' AND `Key` IN ('" . implode("','", $phrases) . "')"
        );

        // Remove hook
        $hooks = [
            'apPhpControlsBottom',
            'init',
            'apPhpListingsAjaxDeleteListing',
            'afterListingEdit'
        ];
        $GLOBALS['rlDb']->query("
            DELETE FROM `{db_prefix}hooks`
            WHERE `Plugin` = 'fieldBoundBoxes' AND `Name` IN ('" . implode("','", $hooks) . "')
        ");

        // Copy svg icons
        $source_dir = RL_PLUGINS . 'fieldBoundBoxes/static/icons/';
        $dist_dir = RL_FILES . 'fieldBoundBoxes/svg_icons/';
        $icons = $reefless->scanDir($source_dir);

        $reefless->rlMkdir($dist_dir);

        foreach ($icons as $index => $name) {
            copy($source_dir . $name, $dist_dir . $name);
        }

        // Remove styles file
        unlink(RL_PLUGINS . 'fieldBoundBoxes/static/style.css');

        // Add new columns
        $columns = array(
            'Parent_page' => "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `Postfix`",
            'Orientation' => "ENUM('landscape','portrait') NOT NULL DEFAULT 'landscape' AFTER `Icons_height`",
            'Resize_icons' => "ENUM('0','1') NOT NULL DEFAULT '1' AFTER `Icons_height`",
            'Multiple_items' => "ENUM('0','1') NOT NULL DEFAULT '0' AFTER `Field_key`",
            'Style' => "ENUM('text','text_pic','icon','responsive') NOT NULL default 'text' AFTER `Show_empty`",
        );
        $rlDb->addColumnsToTable($columns, 'field_bound_boxes');
        $rlDb->addColumnToTable('Fbb_hidden', "ENUM('0', '1') NOT NULL DEFAULT '0' AFTER `Readonly`", 'pages');
        $rlDb->addColumnToTable('Path', "VARCHAR(100) CHARACTER SET utf8 NOT NULL DEFAULT '' AFTER `Key`", 'field_bound_items');

        $rlDb->query("
            ALTER TABLE `{db_prefix}field_bound_boxes`
            CHANGE `Columns` `Columns`
            ENUM('auto', '1', '2', '3', '4') NOT NULL DEFAULT 'auto'
        ");

        $rlDb->query("
            ALTER TABLE `{db_prefix}field_bound_boxes`
            CHANGE `Page_columns` `Page_columns`
            ENUM('auto', '1', '2', '3', '4') NOT NULL DEFAULT 'auto'
        ");

        $rlDb->query("UPDATE `{db_prefix}field_bound_boxes` SET `Columns` = 'auto', `Page_columns` = 'auto'");

        // Remove unnecessary legacy page
        $rlDb->delete(['Key' => 'listings_by_field'], 'pages');

        // Rework boxes
        $rlDb->setTable('field_bound_boxes');

        foreach ($rlDb->fetch('*') as $box) {
            $page_data = array(
                'Page_type'  => 'system',
                'Login'      => '0',
                'Key'        => $box['Key'],
                'Path'       => $box['Path'],
                'Get_vars'   => '',
                'Controller' => 'listings_by_field',
                'Tpl'        => '1',
                'Menus'      => '',
                'Deny'       => '0',
                'Plugin'     => 'fieldBoundBoxes',
                'No_follow'  => '0',
                'Modified'   => '',
                'Status'     => 'active',
                'Readonly'   => '1',
                'Fbb_hidden'  => '0',
            );
            $rlDb->insertOne($page_data, 'pages');

            $style = $box['Icons'] ? 'text_pic' : 'text';
            $style = $box['Key'] == 'body_style' ? 'icon' : $style;

            $update_box = array(
                'fields' => array('Style' => $style),
                'where' => array('Key' => $box['Key'])
            );
            $rlDb->updateOne($update_box, 'field_bound_boxes');

            $field_condition = $rlDb->getOne('Condition', "`Key` = '{$box['Field_key']}'", "listing_fields");
            $replacement = ($field_condition ?: $box['Field_key']) . '_';

            $rlDb->query("UPDATE `{db_prefix}field_bound_items` SET `Path` = REPLACE(`Key`, '{$replacement}', '')");

            foreach ($GLOBALS['languages'] as $lang_item) {
                $name_phrase_key = 'pages+name+' . $box['Key'];
                $insert_phrases[] = array(
                    'Code'   => $lang_item['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => $name_phrase_key,
                    'Value'  => $GLOBALS['rlLang']->getPhrase($name_phrase_key, $lang_item['Code'], false, true),
                    'Plugin' => 'fieldBoundBoxes',
                );

                $title_phrase_key = 'pages+title+' . $box['Key'];
                $insert_phrases[] = array(
                    'Code'   => $lang_item['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => $title_phrase_key,
                    'Value'  => $GLOBALS['rlLang']->getPhrase($title_phrase_key, $lang_item['Code'], false, true),
                    'Plugin' => 'fieldBoundBoxes',
                );
            }

            if ($insert_phrases) {
                $rlDb->insert($insert_phrases, 'lang_keys');
            }
        }

        $rlDb->dropColumnFromTable('Icons', 'field_bound_boxes');

        $this->updateBoxContent();
    }

    /**
     * Update to 2.2.0 version
     */
    public function update220()
    {
        $GLOBALS['rlDb']->addColumnToTable(
            'Sorting',
            "ENUM('position','alphabet') NOT NULL DEFAULT 'position' AFTER `Style`",
            'field_bound_boxes'
        );
    }
}
