<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLLISTINGSBOX.CLASS.PHP
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

class rlListingsBox
{
    public $rejectedBoxSides = array('header_banner', 'lon_top');
    /**
     * Plugin installer
     **/
    public function install()
    {
        // create listing box table
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . RL_DBPREFIX . "listing_box` (
              `ID` int(11) NOT NULL AUTO_INCREMENT,
              `Type` varchar(255) NOT NULL,
              `Box_type` enum('top_rating','popular','recently_added','random','featured') NOT NULL DEFAULT 'recently_added',
              `Count` varchar(10) NOT NULL,
              `Unique` enum('1','0') NOT NULL DEFAULT '0',
              `By_category` enum('1','0') NOT NULL DEFAULT '0',
              `Display_mode` enum('default','grid') NOT NULL DEFAULT 'default',
              PRIMARY KEY (`ID`)
            ) DEFAULT CHARSET=utf8;";

        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Plugin un-installer
     **/
    public function uninstall()
    {
        // DROP TABLE
        $GLOBALS['rlDb']->query("DROP TABLE IF EXISTS `" . RL_DBPREFIX . "listing_box`");
    }

    /**
     * Remove listing box
     *
     * @hook apAjaxRequest
     **/
    public function hookApAjaxRequest()
    {
        global $rlDb;

        $item = $GLOBALS['rlValid']->xSql($_REQUEST['item']);
        $id = (int) $_REQUEST['id'];
        $key = 'listing_box_' . $id;

        switch ($item) {
            case 'deleteListingsBox':

                // delete listing box
                $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "listing_box` WHERE `ID` = {$id} LIMIT 1");
                $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "blocks` WHERE `Key` = '{$key}' LIMIT 1");
                $rlDb->query("DELETE FROM `" . RL_DBPREFIX . "lang_keys` WHERE `Key` = 'blocks+name+{$key}'");
                $GLOBALS['out']['status'] = "ok";
                $GLOBALS['out']['message'] = $GLOBALS['lang']['block_deleted'];
                break;
        }
    }

    /**
     * Set content box
     *
     * @param array $info  - array info
     * @param array $field - fields for update in grid
     *
     * @return array - box information
     **/
    public function checkContentBlock($info = false, $field = false)
    {
        if (is_array($field)) {
            $data = $GLOBALS['rlDb']->fetch(
                array('Type', 'Box_type', 'Count', 'Unique', 'By_category', 'Display_mode'),
                array('ID' => $field[2]),
                null,
                null,
                'listing_box',
                'row'
            );

            if ($field[0] == 'Type') {
                $type = $field[1];
                $box_type = $data['Box_type'];
                $limit = $data['Count'];
            } elseif ($field[0] == 'Box_type') {
                $type = $data['Type'];
                $box_type = $field[1];
                $limit = $data['Count'];
            } elseif ($field[0] == 'Count') {
                $type = $data['Type'];
                $box_type = $data['Box_type'];
                $limit = $field[1];
            }
            $unique = $data['Unique'];
            $by_category = $data['By_category'];
            $box_option['display_mode'] = $data['Display_mode'];
        } else {
            $type = $info['type'];
            $box_type = $info['box_type'];
            $limit = $info['count'];
            $unique = $info['unique'];
            $by_category = $info['by_category'];
            $box_option['display_mode'] = $info['display_mode'];
        }

        $content = '
                global $rlSmarty;
                $GLOBALS["reefless"]->loadClass("ListingsBox", null, "listings_box");
                $listings_box = $GLOBALS["rlListingsBox"] -> getListings( "' . $type . '", "' . $box_type . '", "' . $limit . '", "' . $unique . '", "' . $by_category . '" );
                $rlSmarty->assign_by_ref("listings_box", $listings_box);
                $rlSmarty->assign("type", "' . $type . '");';
        foreach ($box_option as $key => $val) {
            $content .= '$box_option["' . $key . '"] = "' . $val . '";';
        }
        $content .= '$rlSmarty->assign("box_option", $box_option);
                $rlSmarty->display(RL_PLUGINS . "listings_box" . RL_DS . "listings_box.block.tpl");
            ';
        return $content;
    }

    /**
     * Get listings
     *
     * @param string $type        - type
     * @param string $order       - field name for order
     * @param int    $limit       - listing number per request
     * @param int    $Unique      - Unique listings in box
     * @param int    $by_category - by category
     *
     * @return array - listings information
     **/
    public function getListings($type, $order, $limit = 0, $unique = 0, $by_category = 0)
    {
        global $sql, $config, $category, $rlDb, $rlHook, $rlListings;

        $sql = "SELECT ";

        $dbcount = false;
        /**
         * @since 3.0.3
         */
        $rlHook->load('listingsModifyPreSelect', $dbcount);

        $sql .= " {hook} ";
        $sql .= "`T1`.*, `T3`.`Path` AS `Path`, `T3`.`Key` AS `Key`, `T3`.`Type` AS `Listing_type`, `T3`.`Parent_keys`, ";

        // add multilingual
        if ($config['multilingual_paths']) {
            foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                if ($languageKey === $config['lang']) {
                    continue;
                }

                $sql .= "`T3`.`Path_{$languageKey}`, ";
            }
        }

        // add option for rating plugin
        if ($order == 'top_rating' && $GLOBALS['rlHook']->aHooks['rating']) {
            $sql .= "(`T1`.`lr_rating` / `T1`.`lr_rating_votes`) AS `Middle_rating`, ";
        }

        // add option by category
        if ($category['ID'] && $by_category) {
            $sql .= "IF(`T1`.`Category_ID` = {$category['ID']} OR FIND_IN_SET('{$category['ID']}', `T3`.`Parent_IDs`) > 0, 1, 0) ";
            $sql .= "AS `Category_match`, ";
        }

        $rlHook->load('listingsModifyField');

        $sql .= "IF(`T1`.`Featured_date` <> '0000-00-00 00:00:00', '1', '0') `Featured` ";

        $sql .= "FROM `" . RL_DBPREFIX . "listings` AS `T1` ";
        $sql .= "LEFT JOIN `" . RL_DBPREFIX . "categories` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";

        $rlHook->load('listingsModifyJoin');

        $sql .= "WHERE `T1`.`Status` = 'active' ";

        // featured option
        if ($order == 'featured') {
            $sql .= "AND `T1`.`Featured_date` <> '0000-00-00 00:00:00' ";
        }

        // select by type or types
        if ($type) {
            $GLOBALS['rlValid']->sql($type);

            if (false !== strpos($type, ',')) {
                $sql .= "AND `T3`.`Type` IN('" . str_replace(",", "','", $type) . "') ";
            } else {
                $sql .= "AND `T3`.`Type` = '{$type}' ";
            }
        }

        if ($unique && $rlListings->selectedIDs) {
            $sql .= "AND `T1`.`ID` NOT IN('" . implode("','", $rlListings->selectedIDs) . "') ";
        }

        $plugin_name = "listings_box";
        $rlHook->load('listingsModifyWhere', $sql, $plugin_name); // > 4.1.0
        $rlHook->load('listingsModifyGroup');

        $sql .= "ORDER BY ";
        if ($category['ID'] && $by_category) {
            $sql .= "`Category_match` DESC, ";
        }
        switch ($order) {
            case 'popular':
                $sql .= "`T1`.`Shows` DESC ";
                break;
            case 'top_rating':
                $sql .= " `Middle_rating` DESC, `T1`.`lr_rating_votes` DESC ";
                break;
            case 'random':
                $sql .= "RAND() ";
                break;
            case 'featured':
                $sql .= "`T1`.`Last_show` ASC, RAND() ";
                break;
            case 'recently_added':
                $date_field = $config['recently_added_order_field'] ?: 'Date';
                $sql .= "`T1`.`{$date_field}` DESC ";
                break;
            default:
                $sql .= "`T1`.`ID` DESC ";
                break;
        }

        $sql .= "LIMIT " . intval($limit);
        $sql = str_replace('{hook}', $hook, $sql);

        $listings = $rlDb->getAll($sql);
        $listings = $GLOBALS['rlLang']->replaceLangKeys($listings, 'categories', 'name');

        if (empty($listings)) {
            return false;
        }

        /**
         * @since 3.0.3
         */
        $block_key = $GLOBALS['rlSmarty']->_tpl_vars['block']['Key'];

        $rlHook->load('listingsAfterSelectFeatured', $sql, $block_key, $listings);

        foreach ($listings as $key => $value) {
            // add id in selected array
            $rlListings->selectedIDs[] = $value['ID'];
            $IDs[] = $value['ID'];

            // populate fields
            $fields = $rlListings->getFormFields($value['Category_ID'], 'featured_form', $value['Listing_type']);

            foreach ($fields as $fKey => $fValue) {
                $fields[$fKey]['value'] = $GLOBALS['rlCommon']->adaptValue(
                    $fValue,
                    $value[$fKey],
                    'listing',
                    $value['ID'],
                    true,
                    false,
                    false,
                    false,
                    $value['Account_ID'],
                    'short_form',
                    $value['Listing_type']
                );
            }

            $listings[$key]['fields'] = $fields;

            $listings[$key]['listing_title'] = $rlListings->getListingTitle($value['Category_ID'], $value, $value['Listing_type']);
            $listings[$key]['url'] = $GLOBALS['reefless']->getListingUrl($listings[$key]);
        }
        // save show date
        if ($IDs && $order == 'featured') {
            $sql = "UPDATE `" . RL_DBPREFIX . "listings` SET `Last_show` = NOW() ";
            $sql .= "WHERE `ID` = " . implode(" OR `ID` = ", $IDs);
            $rlDb->shutdownQuery($sql);
        }

        return $listings;
    }

    /**
     * @hook tplHeader
     */
    public function hookTplHeader()
    {
        foreach ($GLOBALS['blocks'] as &$block) {
            if ($block['Plugin'] == 'listings_box') {
                $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'listings_box' . RL_DS . 'header.tpl');
                break;
            }
        }
    }

    /**
     *  Define plugin related boxes and remove not supported box positions
     *  in edit box mode
     *
     *  @hook apPhpBlocksPost
     */
    public function hookApPhpBlocksPost()
    {
        global $block_info;

        if ($block_info['Plugin'] != 'listings_box') {
            return;
        }

        $this->rejectBoxSides();
    }

    /**
     *  Remove not supported box positions for plugin related boxes
     */
    public function rejectBoxSides()
    {
        global $l_block_sides;

        foreach ($this->rejectedBoxSides as $side) {
            unset($l_block_sides[$side]);
        }
    }
}
