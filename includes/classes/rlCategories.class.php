<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLCATEGORIES.CLASS.PHP
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

use \Flynax\Utils\Category;

class rlCategories extends reefless
{
    /**
     * @var category sections
     **/
    public $sections;

    /**
     * @var selected fields array
     **/
    public $fields;

    /**
     * get all data format
     *
     * @param string $key - format key
     * @param string $order - order type (alphabetic/position)
     *
     * @return array - data formats list
     **/
    public function getDF($key = false, $order = false)
    {
        global $rlCache, $config;

        if (!$key) {
            return false;
        }

        /* get data from cache */
        if ($config['cache']) {
            $df = $rlCache->get('cache_data_formats', $key);

            if ($df) {
                $df = $GLOBALS['rlLang']->replaceLangKeys($df, 'data_formats', array('name'));

                $order = !$order && $GLOBALS['data_formats'] ? $GLOBALS['data_formats'][$key]['Order_type'] : $order;

                if (!$order) {
                    $order = $this->getOne("Order_type", "`Key` = '{$key}'", "data_formats");
                }

                if ($order && in_array($order, array('alphabetic', 'position'))) {
                    $this->rlArraySort($df, $order == 'alphabetic' ? 'name' : 'Position');
                }

                return $df;
            }

            return false;
        }

        $GLOBALS['rlValid']->sql($key);

        $data = null;

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCategoryGetDF', $data, $key, $order);

        if ($data) {
            return $data;
        }

        /* get data from DB */
        $this->setTable('data_formats');

        /* DO NOT SET ANOTHER FIELD FOR ORDER, ID ONLY */
        $format_id = $this->getOne('ID', "`Key` = '{$key}'");

        $data = $this->fetch(array('ID', 'Parent_ID', 'Key`, CONCAT("data_formats+name+", `Key`) AS `pName', 'Default', 'Position'), array('Status' => 'active', 'Parent_ID' => $format_id), 'ORDER BY `ID`, `Key`', null);
        $data = $GLOBALS['rlLang']->replaceLangKeys($data, 'data_formats', array('name'));

        $this->resetTable();

        if (!$order) {
            $order = $this->getOne("Order_type", "`Key` = '{$key}'", "data_formats");
        }

        if ($order && in_array($order, array('alphabetic', 'position'))) {
            $this->rlArraySort($data, $order == 'alphabetic' ? 'name' : 'Position');
        }

        return $data;
    }

    /**
     * delete listing field's group
     *
     * @package ajax
     *
     * @param string $key - group key
     *
     **/
    public function ajaxDeleteFGroup($key = false)
    {
        global $_response, $lang;

        if (!$key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($key);
        $lang_keys[] = array(
            'Key' => 'listing_groups+name+' . $key,
        );

        // delete group field relations
        if (!$GLOBALS['config']['trash']) {
            $this->deleteGroupRelations($key);
        }

        $GLOBALS['rlActions']->delete(array('Key' => $key), array('listing_groups', 'lang_keys'), null, 1, $key, $lang_keys);
        $del_mode = $GLOBALS['rlActions']->action;

        $_response->script("
            listingGroupsGrid.reload();
            printMessage('notice', '{$lang['group_' . $del_mode]}');
        ");

        return $_response;
    }

    /**
     * delete group relations
     *
     * @param string $key - group key
     *
     **/
    public function deleteGroupRelations($key = false)
    {
        if (!$key) {
            return false;
        }

        $GLOBALS['rlValid']->sql($key);

        // get category id
        $group_id = $this->getOne('ID', "`Key` = '{$key}'", 'listing_groups');

        if ($group_id) {
            // delete field relations from main form
            $sql = "DELETE FROM `{db_prefix}listing_relations` WHERE `Group_ID` = '{$group_id}'";
            $this->query($sql);

            // delete field relations from search form
            $sql = "DELETE FROM `{db_prefix}search_forms_relations` WHERE `Group_ID` = '{$group_id}'";
            $this->query($sql);
        }
    }

    /**
     * get all categories
     *
     * @param int $id - category ID
     * @param mixed $type - listing type key
     * @param bool $include_sections - include sections
     *
     * @return array - listing types list
     **/
    public function getCategories($parent = 0, $type = false, $include_sections = false, $include_subcats = false)
    {
        global $select, $where, $rlListingTypes, $config, $rlCache;

        $parent = (int) $parent;
        $types = $type ? array($rlListingTypes->types[$type]) : $rlListingTypes->types;

        /* get categories from cache */
        if ($config['cache'] && ((defined('REALM') && REALM != 'admin') || !defined('REALM'))) {
            foreach ($types as $type) {
                $categories = $rlCache->get('cache_categories_by_parent', $parent, $type);

                if ($config['multilingual_paths'] && $config['lang'] !== RL_LANG_CODE) {
                    $multilingualPaths = $rlCache->get('cache_categories_multilingual_paths');

                    foreach ($categories as &$category) {
                        $path             = $multilingualPaths[$category['ID']]['Path_' . RL_LANG_CODE];
                        $category['Path'] = $path ?: $category['Path'];

                        foreach ($category['sub_categories'] as &$subcategory) {
                            $path                = $multilingualPaths[$subcategory['ID']]['Path_' . RL_LANG_CODE];
                            $subcategory['Path'] = $path ?: $subcategory['Path'];
                        }
                    }

                    unset($category, $subcategory, $path);
                }

                $categories = $GLOBALS['rlLang']->replaceLangKeys($categories, 'categories', array('name'));

                $GLOBALS['rlHook']->load('phpCategoriesGetCategoriesCache', $categories);

                if ($type['Cat_order_type'] == 'alphabetic') {
                    $this->rlArraySort($categories, 'name');
                }

                if ($type['Cat_show_subcats'] && $include_subcats) {
                    foreach ($categories as $key => &$value) {
                        if ($value['sub_categories']) {
                            $value['sub_categories'] = $GLOBALS['rlLang']->replaceLangKeys($value['sub_categories'], 'categories', array('name'));

                            if ($type['Cat_order_type'] == 'alphabetic') {
                                $this->rlArraySort($value['sub_categories'], 'name');
                                $categories[$key]['sub_categories'] = $value['sub_categories'];
                            }
                        }
                    }
                }

                if ($include_sections) {
                    $sections[$type['Key']] = array(
                        'ID'         => $type['ID'],
                        'name'       => $type['name'],
                        'Key'        => $type['Key'],
                        'Categories' => $categories,
                    );
                }
            }

            if ($include_sections) {
                $categories = $sections;
            }

            return $categories;
        }

        $sections = array();
        foreach ($types as $type) {
            $where = array(
                'Status'    => 'active',
                'Parent_ID' => $parent,
            );
            if ($type) {
                $where['Type'] = $type['Key'];
            }
            if ($type['Cat_hide_empty'] && REALM != 'admin') {
                $addwhere = "AND `Count` > 0";
            }

            $select = array('ID', 'Path', 'Count', "Key`, CONCAT('categories+name+', `Key`) AS `pName`, CONCAT('categories+title+', `Key`) AS `pTitle", 'Type');

            /* load hook for front-end only */
            if (!defined('REALM')) {
                $GLOBALS['rlHook']->load('getCategoriesModifySelect');
            }

            $categories = $this->fetch($select, $where, "{$addwhere} ORDER BY `Position`", null, 'categories');
            $categories = $GLOBALS['rlLang']->replaceLangKeys($categories, 'categories', array('name'));

            $GLOBALS['rlHook']->load('phpCategoriesGetCategories', $categories);

            if ($type['Cat_order_type'] == 'alphabetic') {
                $this->rlArraySort($categories, 'name');
            }

            /* get subcategories */
            if ($type['Cat_show_subcats'] && $include_subcats) {
                foreach ($categories as $key => $value) {
                    if ($type['Cat_hide_empty'] && REALM != 'admin') {
                        $addwhere = "AND `Count` > 0";
                    }
                    $this->calcRows = true;
                    $subCategories = $this->fetch(array('ID', 'Path', 'Key', 'Count'), array('Status' => 'active', 'Parent_ID' => $categories[$key]['ID']), $addwhere . " ORDER BY `Position`", null, 'categories');
                    $this->calcRows = false;
                    $subCategories = $GLOBALS['rlLang']->replaceLangKeys($subCategories, 'categories', array('name'));

                    if ($type['Cat_order_type'] == 'alphabetic') {
                        $this->rlArraySort($subCategories, 'name');
                    }

                    if (!empty($subCategories)) {
                        $categories[$key]['sub_categories'] = $subCategories;
                        $categories[$key]['sub_categories_calc'] = $this->calcRows;
                    }

                    unset($subCategories);
                }
            }

            if ($include_sections) {
                if (!empty($categories)) {
                    $sections[$type['Key']] = array(
                        'ID'         => $type['ID'],
                        'name'       => $type['name'],
                        'Key'        => $type['Key'],
                        'Categories' => $categories,
                    );
                }
            }
        }

        if ($include_sections) {
            $categories = $sections;
        }

        /* "with sections" mode */
        if ($sections && $parent == 0) {
            if (!$this->sections) {
                return $categories;
            }

            $cat_sections = $this->sections;

            foreach ($categories as $cKey => $cVal) {
                $cat_sections[$cVal['Type']]['Categories'][] = $cVal;
            }
            unset($categories);

            $categories = $cat_sections;
        }

        return $categories;
    }

    /**
     * Get categories tree
     *
     * @since 4.8.2 - Added $excludeAdminOnly parameter
     *
     * @param int          $parent_id         - Category parent_id
     * @param string|array $type              - Listing type
     * @param bool         $group_by_sections - Group categories by sections mode
     * @param bool         $active_types      - Filter categories by active listing types only
     * @param bool         $excludeAdminOnly  - Exclude listing types from result which opened for admins only
     *
     * @return array - Array with categories
     */
    public function getCatTree(
        $parent_id         = 0,
        $type              = false,
        $group_by_sections = false,
        $active_types      = false,
        $excludeAdminOnly  = false
    ) {
        global $sql, $rlListingTypes, $account_info;

        $parent_id = (int) $parent_id;
        $GLOBALS['rlValid']->sql($type);
        $sql = "SELECT `T1`.`ID`, `T1`.`Path`, `T1`.`Level`, `T1`.`Type`, `T1`.`Key`, `T1`.`Lock`, `T1`.`Add`, `T1`.`Count`, ";

        $GLOBALS['rlHook']->load('getCatTreeFields', $sql); // param1 added from > 4.3.0

        $sql .= "IF(`T2`.`ID` AND `T2`.`Status` = 'active', `T2`.`ID`, IF( `T3`.`ID`, 1, 0 )) `Sub_cat`";
        $sql .= " FROM `{db_prefix}categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`ID` = `T2`.`Parent_ID` AND `T2`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}tmp_categories` AS `T3` ON `T1`.`ID` = `T3`.`Parent_ID` AND `T3`.`Account_ID` = '{$account_info['ID']}' AND `T3`.`Status` <> 'trash' ";
        $sql .= "WHERE `T1`.`Status` = 'active' ";

        if ($type && $parent_id == 0) {
            $type = is_array($type) ? $type : explode(',', $type);

            if ($excludeAdminOnly) {
                foreach ($type as $tk => $tp) {
                    if ($rlListingTypes->types[$tp]['Admin_only']) {
                        unset($type[$tk]);
                    }
                }
            }

            if ($type) {
                $sql .= "AND (`T1`.`Type` = '" . implode("' OR `T1`.`Type` = '", $type) . "') ";
            }
        }

        $sql .= "AND `T1`.`Parent_ID` = '{$parent_id}' ";
        $sql .= "GROUP BY `T1`.`Key` ";
        $sql .= "ORDER BY `T1`.`Position` ";

        $categories = $this->getAll($sql);
        $categories = $GLOBALS['rlLang']->replaceLangKeys($categories, 'categories', array('name'));

        /* group by sections mode */
        if ($group_by_sections && $parent_id == 0) {
            $categories_grouped = $rlListingTypes->types;

            // remove inactive listing types
            if ($active_types) {
                foreach ($categories_grouped as $type_key => $l_type) {
                    if ($l_type['Status'] != 'active') {
                        unset($categories_grouped[$type_key]);
                    }
                }
            }

            if ($type) {
                foreach ($categories_grouped as $key => $value) {
                    if (!in_array($value['Key'], $type)) {
                        unset($categories_grouped[$key]);
                    }
                }
            }

            foreach ($categories as $key => $value) {
                if ($categories_grouped[$value['Type']]) {
                    $categories_grouped[$value['Type']]['Categories'][] = $value;
                }
            }

            /* ordering */
            foreach ($categories_grouped as $key => $value) {
                if ($value['Cat_order_type'] == 'alphabetic') {
                    $this->rlArraySort($value['Categories'], 'name');
                    $categories_grouped[$key]['Categories'] = $value['Categories'];
                }
            }

            $categories = $categories_grouped;
            unset($categories_grouped);
        } else {
            if ((!$type || $type == "false") && $parent_id) {
                $type = $this->getOne("Type", "`ID` = {$parent_id}", "categories");
            } elseif (is_array($type) && $type[0]) {
                $type = $type[0];
            }
            if ($type) {
                if ($rlListingTypes->types[$type]['Cat_order_type'] == 'alphabetic') {
                    $this->rlArraySort($categories, 'name');
                }
            }
        }

        return $categories;
    }

    /**
     * get category level
     *
     * @deprecated 4.6.0 - Use Flynax\Utils\Category::getCategories
     *
     * @package xajax
     *
     * @param int $category_id - category ID
     * @param string $type - listing type
     * @param string $tpl - tpl postfix name, example: category_level_{$tpl}.tpl
     * @param string $function - js callback method
     * @param string $namespace - container namespace
     * @param string $section_key - section/selector class name
     *
     * @return array - sub categories array
     **/
    public function ajaxGetCatLevel($category_id, $type = false, $tpl = false, $function = false, $namespace = false, $section_key = false, $mode = false)
    {
        global $_response, $rlSmarty, $rlListingTypes, $account_info, $lang;

        $this->loadClass('Categories');

        /* get category infor */
        $category_id = (int) $category_id;
        $category = $this->getCategory($category_id);
        $rlSmarty->assign_by_ref('category', $category);

        /* get child categories */
        $categories = $this->getCatTree($category_id, $type);

        // assign namespace mode
        $rlSmarty->assign_by_ref('mode', $mode);

        /* custom category for current user detecting */
        if ($rlListingTypes->types[$category['Type']]['Cat_custom_adding']) {
            $custom_cat_in = $this->fetch(array('ID', 'Name'), array('Account_ID' => $account_info['ID'], 'Parent_ID' => $category_id), "AND `Status` <> 'trash' ORDER BY `Date`", null, 'tmp_categories');
            if (!empty($custom_cat_in)) {
                foreach ($custom_cat_in as $key => $value) {
                    $categories[] = array(
                        'ID'   => $custom_cat_in[$key]['ID'],
                        'name' => $custom_cat_in[$key]['Name'],
                        'Tmp'  => true,
                    );
                }
            }
        }

        if ($categories || ($rlListingTypes->types[$category['Type']]['Cat_custom_adding'] && $category['Add'])) {
            $rlSmarty->assign_by_ref('categories', $categories);

            $_response->script("$('#tree_area_{$category['Parent_ID']}').parent().nextAll().remove();");

            $tpl_postfix = $tpl ? '_' . $tpl : '';
            $file = 'blocks' . RL_DS . 'category_level' . $tpl_postfix . '.tpl';

            if (in_array($tpl, array('crossed', 'checkbox'))) {
                $target = 'tree_cat_' . $category_id;
                $_response->script("xajaxFix = $('#tree_cat_{$category_id}').find('input').attr('checked');");
            } else {
                $target = 'type_section_' . $section_key;
                if ($namespace) {
                    $target .= '_' . $namespace;
                }
            }

            $_response->append($target, 'innerHTML', $rlSmarty->fetch($file, null, null, false));

            if (in_array($tpl, array('crossed', 'checkbox'))) {
                $_response->script("
                    $('#tree_cat_{$category_id} > ul').fadeIn('normal');
                    $('#tree_cat_{$category_id} > img').addClass('opened');
                    $('#tree_cat_{$category_id} > span.tree_loader').fadeOut();

                    if (xajaxFix == 'checked') {
                        $('#tree_cat_{$category_id} input:first').attr('checked', true);
                    }
                ");
            }

            $_response->script("flynax.treeLoadLevel('{$tpl}', '{$function}', '{$section_key}', '{$namespace}', '{$mode}');");

            if ($function) {
                $_response->call($function);
            }
        }

        return $_response;
    }

    /**
     * detect parent item with enabled including mode | recursive method
     *
     * @param int $id - category id
     *
     * @return bool
     **/
    public function detectParentIncludes($id)
    {
        $id = (int) $id;
        if ($id == 0) {
            return false;
        }

        /* get parent */
        $parent = $this->fetch(array('Parent_ID', 'Add_sub', 'Add'), array('ID' => $id), null, 1, 'categories', 'row');

        if (!empty($parent)) {
            /* check relations */
            if ($parent['Add_sub'] == '1') {
                return $parent['Add'];
            }
            return $this->detectParentIncludes($parent['Parent_ID']);
        } else {
            return false;
        }
    }

    /**
     * get parent points
     *
     * @param array $ids - categories ids
     * @param string $assign - assign vaiable name in SMARTY
     *
     * @assign array - parent points IDs
     **/
    public function parentPoints($ids = false, $assign = 'parentPoints')
    {
        global $rlListingTypes, $rlSmarty, $config;

        $GLOBALS['rlValid']->sql($ids);
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        if (empty($ids) || empty($ids[0])) {
            return false;
        }

        $sql = "SELECT `ID`, `Parent_ID`, `Type` FROM `{db_prefix}categories` ";
        $sql .= "WHERE (`ID` = " . implode(" OR `ID` = ", $ids) . ")";
        $parents = $this->getAll($sql);

        $checked             = array();
        $config['tmp_cache'] = $config['cache'];
        $config['cache']     = 0;

        $out = [];
        foreach ($parents as $cat) {
            if (!$cat['Parent_ID'] || in_array($cat['Parent_ID'], $checked)) {
                continue;
            }

            $bc        = $this->getBreadCrumbs($cat['Parent_ID'], false, $rlListingTypes->types[$cat['Type']]);
            $checked[] = $cat['Parent_ID'];

            $bc = array_reverse($bc);
            foreach ($bc as $bc_item) {
                if (!is_numeric(array_search($bc_item['ID'], $out))) {
                    $out[] = $bc_item['ID'];
                }
            }
        }

        $config['cache'] = $config['tmp_cache'];
        unset($config['tmp_cache']);

        $rlSmarty->assign_by_ref($assign, $out);

        return $out;
    }

    /**
     * Get category bread crumbs | recursive method
     *
     * @param  int        $parentID
     * @param  array      $path      - Path array
     * @param  array      $type      - Listing type info
     * @return array|bool
     */
    public function getBreadCrumbs($parentID = 0, $path = [], $type = [])
    {
        global $rlCache, $config;

        $parentID = (int) $parentID;

        if (!$parentID) {
            return false;
        }

        if ($config['cache']) {
            $categoryInfo = $rlCache->get('cache_categories_by_type', $parentID, $type);
        } else {
            $select = ['ID', 'Key', 'Parent_ID', 'Path', 'Position'];

            if ($config['multilingual_paths']) {
                foreach ($GLOBALS['languages'] as $languageKey => $languageData) {
                    if ($languageKey === $config['lang']) {
                        continue;
                    }

                    $select[] = 'Path_' . $languageKey;
                }
            }

            $categoryInfo = $GLOBALS['rlDb']->fetch(
                $select,
                ['ID' => $parentID, 'Type' => $type['Key']],
                null, null, 'categories', 'row'
            );
        }

        if (!empty($categoryInfo)) {
            $categoryInfo = $GLOBALS['rlLang']->replaceLangKeys($categoryInfo, 'categories', ['name']);

            if ($config['multilingual_paths'] && $config['lang'] !== RL_LANG_CODE) {
                $categoryInfo['Path'] = $categoryInfo['Path_' . RL_LANG_CODE] ?: $categoryInfo['Path'];
            }

            $path[] = $categoryInfo;
        } else {
            $path = false;
        }

        if (!empty($categoryInfo['Parent_ID'])) {
            return $this->getBreadCrumbs($categoryInfo['Parent_ID'], $path, $type);
        } else {
            return $path;
        }
    }

    /**
     * @since 4.5.1
     *
     * build category bread crumbs
     *
     * @param array $bread_crumbs - system bread crumbs array
     * @param array $category - category info array
     * @param array $listing_type - listing type info array
     *
     * @return array - path array
     **/
    public function buildCategoryBreadCrumbs(&$bread_crumbs, &$category_id, &$listing_type)
    {
        global $page_info, $lang, $config;

        $cat_bread_crumbs = $this->getBreadCrumbs($category_id, null, $listing_type);
        $cat_bread_crumbs = $cat_bread_crumbs ? array_reverse($cat_bread_crumbs) : [];

        if (!empty($cat_bread_crumbs)) {
            foreach ($cat_bread_crumbs as $bKey => $bVal) {
                $cat_bread_crumbs[$bKey]['path'] = $config['mod_rewrite'] ? $page_info['Path'] . '/' . $bVal['Path'] : $page_info['Path'] . '&category=' . $bVal['ID'];

                $cat_bread_crumbs[$bKey]['title'] = $lang[$bVal['pTitle']];
                $cat_bread_crumbs[$bKey]['category'] = true;
                $bread_crumbs[] = $cat_bread_crumbs[$bKey];
            }
        }

        unset($cat_bread_crumbs);
    }

    /**
     * category walker check/update/delete categories or listings inside subcategories | recursive method
     *
     * @param int $category_id - start category id
     * @param string $mode - action mode: detect|delete|trash|restore|replace
     * @param array $data - recurcive variable
     * @param int $new_id - new category ID | in replace mode only
     * @param int $initial_category - initial category ID
     *
     * @return array - mixed data
     **/
    public function categoryWalker($category_id = false, $mode = false, $data = array(), $new_id = false, $initial_category = false)
    {
        if (!$mode) {
            trigger_error('categoryWalker() error, no mode selected', E_WARNING);
            $GLOBALS['rlDebug']->logger("categoryWalker() error, no mode selected");

            return false;
        }

        $category_id = (int) $category_id;
        if (!$category_id) {
            return false;
        }

        $this->setTable('categories');

        switch ($mode) {
            case 'detect':
                /* get child categories */
                $child = $this->fetch(array('ID', 'Parent_ID'), array('Parent_ID' => $category_id), "AND `Status` <> 'trash'");
                $listings = $this->getRow("SELECT COUNT(`ID`) AS `count` FROM `{db_prefix}listings` WHERE (`Category_ID` = '{$category_id}' OR FIND_IN_SET('{$category_id}', `Crossed`) > 0) AND `Status` <> 'trash'");

                if ($listings['count']) {
                    $data['listings'] += $listings['count'];
                }

                if (!empty($child)) {
                    foreach ($child as $key => $value) {
                        $data['categories']++;

                        $data = $this->categoryWalker($child[$key]['ID'], 'detect', $data);
                    }
                }

                return $data;
                break;

            case 'delete':
                /* get child categories */
                $child = $this->fetch(array('ID', 'Parent_ID', 'Key', 'Type'), array('Parent_ID' => $category_id));
                $listings = $this->fetch(array('ID'), array('Category_ID' => $category_id), null, null, 'listings');

                /* delete all listings */
                if (!empty($listings)) {
                    $this->loadClass('Listings');
                    foreach ($listings as $key => $value) {
                        $GLOBALS['rlListings']->deleteListingData($listings[$key]['ID']);
                    }

                    $this->query("DELETE FROM `{db_prefix}listings` WHERE `Category_ID` = '{$category_id}'");
                }

                if (!empty($child)) {
                    foreach ($child as $key => $value) {
                        /* delete categories */
                        $this->query("DELETE FROM `{db_prefix}categories` WHERE `ID` = '{$child[$key]['ID']}' LIMIT 1");

                        /* delete lang keys */
                        $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'categories+title+{$child[$key]['Key']}' OR `Key` = 'categories+name+{$child[$key]['Key']}' OR `Key` = 'categories+des+{$child[$key]['Key']}' OR `Key` = 'categories+meta_description+{$child[$key]['Key']}' OR `Key` = 'categories+meta_keywords+{$child[$key]['Key']}'");

                        $this->categoryWalker($child[$key]['ID'], 'delete');

                        $this->deleteCatRelations($child[$key]['ID']);
                        Category::resetGeneralCategory($child[$key]['ID'], $child[$key]['Type']);
                    }
                }
                break;

            case 'trash':
                /* get child categories */
                $child = $this->fetch(array('ID', 'Parent_ID', 'Key'), array('Parent_ID' => $category_id));
                $listings = $this->fetch(array('ID`, UNIX_TIMESTAMP(`Pay_date`) AS `Pay_date', 'Category_ID', 'Account_ID'), array('Category_ID' => $category_id), null, null, 'listings');

                /* trash all listings */
                if (!empty($listings)) {
                    foreach ($listings as $key => $value) {
                        if ($listings[$key]['Pay_date']) {
                            $this->listingsDecrease($listings[$key]['Category_ID']);
                            $this->accountListingsDecrease($listings[$key]['Account_ID']);
                        }
                    }
                    $this->query("UPDATE `{db_prefix}listings` SET `Status` = 'trash' WHERE `Category_ID` = '{$category_id}'");
                }

                if (!empty($child)) {
                    foreach ($child as $key => $value) {
                        /* trash categories */
                        $this->query("UPDATE `{db_prefix}categories` SET `Status` = 'trash' WHERE `ID` = '{$child[$key]['ID']}' LIMIT 1");

                        $data = $this->categoryWalker($child[$key]['ID'], 'detect', $data);
                    }
                }
                break;

            case 'restore':
                /* get child categories */
                $child = $this->fetch(array('ID', 'Parent_ID', 'Key'), array('Parent_ID' => $category_id));
                $listings = $this->fetch(array('ID`, UNIX_TIMESTAMP(`Pay_date`) AS `Pay_date', 'Category_ID'), array('Category_ID' => $category_id), null, null, 'listings');

                /* restore all listings */
                if (!empty($listings)) {
                    foreach ($listings as $key => $value) {
                        if ($listings[$key]['Pay_date']) {
                            $this->listingsIncrease($listings[$key]['Category_ID']);
                            $this->accountListingsIncrease($listings[$key]['Account_ID']);
                        }
                    }
                    $this->query("UPDATE `{db_prefix}listings` SET `Status` = 'active' WHERE `Category_ID` = '{$category_id}'");
                }

                if (!empty($child)) {
                    foreach ($child as $key => $value) {
                        /* restore categories */
                        $this->query("UPDATE `{db_prefix}categories` SET `Status` = 'active' WHERE `ID` = '{$child[$key]['ID']}' LIMIT 1");

                        $data = $this->categoryWalker($child[$key]['ID'], 'detect', $data);
                    }
                }
                break;

            case 'replace':
                $new_id = (int) $new_id;
                if ($new_id) {
                    $find_id = intval($initial_category ? $initial_category : $category_id);
                    $initial_category_data = $this->fetch(array('Path', 'Tree'), array('ID' => $find_id), null, 1, 'categories', 'row');
                    $replace_category_data = $this->fetch(array('Path', 'Tree'), array('ID' => $new_id), null, 1, 'categories', 'row');

                    /* update sub-categories */
                    $this->query("UPDATE `{db_prefix}categories` SET `Parent_ID` = '{$new_id}', `Path` = REPLACE(`Path`, '{$initial_category_data['Path']}', '{$replace_category_data['Path']}'), `Tree` = REPLACE(`Tree`, '{$initial_category_data['Tree']}', '{$replace_category_data['Tree']}') WHERE `Parent_ID` = '{$category_id}'");

                    /* update listings */
                    $this->query("UPDATE `{db_prefix}listings` SET `Category_ID` = '{$new_id}' WHERE `Category_ID` = '{$category_id}'");
                }

                if ($child = $this->fetch(array('ID', 'Parent_ID', 'Key'), array('Parent_ID' => $category_id))) {
                    foreach ($child as $child_category) {
                        $this->categoryWalker($child_category['ID'], 'replace', false, $new_id, $category_id);
                    }
                }

                break;
        }
    }

    /**
     * Get category related paths
     *
     * @param  int    $parentID - Category parent_id
     * @param  string $path
     * @param  string $langKey  - Provide language key if you want get a multilingual path
     * @return string
     */
    public function getCatPath($parentID = 0, $path = '', $langKey = '')
    {
        global $rlDb;

        if (!$parentID = (int) $parentID) {
            return $path;
        }

        if ($langKey && $GLOBALS['config']['multilingual_paths']) {
            $sql = "SELECT IF(`T1`.`Path_{$langKey}` <> '', `T1`.`Path_{$langKey}`, `T1`.`Path`) AS `Path` ";
            $sql .= 'FROM `{db_prefix}categories` AS `T1` ';
            $sql .= "WHERE `T1`.`Status` = 'active' AND `T1`.`ID` = {$parentID}";
            $categoryPath = $rlDb->getRow($sql, 'Path');
        } else {
            $categoryPath = $rlDb->getOne('Path', "`ID` = {$parentID}", 'categories');
        }

        $path = $categoryPath ? ($path ? $categoryPath . '/' . $path : $categoryPath) : $path;

        return $path;
    }

    /**
     * Get category data by ID/Path
     *
     * @deprecated 4.8.1 - Use \Flynax\Utils\Category::getCategory()
     *
     * @since 4.8.0 - Added $getLangKeys parameter
     *
     * @param  int    $id
     * @param  string $path
     * @param  bool   $getLangKeys - Get system phrases of category data
     *                             - Set FALSE to increase loading and decrease memory usage, if phrases aren't needed
     * @return array
     */
    public function getCategory($id = 0, $path = '', $getlangKeys = true)
    {
        return Category::getCategory($id, $path, $getlangKeys);
    }

    /**
     * get parent category form relations
     *
     * @param int $id - category id
     *
     * @return array - fields form
     **/
    public function getParentCatRelations($id = false, $noRecursive = false)
    {
        $id = (int) $id;

        $sql = "SELECT `T1`.`Group_ID`, `T1`.`ID`, `T1`.`Category_ID`, `T2`.`Key`, `T1`.`Fields`, `T2`.`Display`, ";
        $sql .= "CONCAT('listing_groups+name+', `T2`.`Key`) AS `pName`, `T2`.`ID` AS `Group` ";
        $sql .= "FROM `{db_prefix}listing_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Category_ID` = '{$id}' AND (`T1`.`Group_ID` = '' OR `T2`.`Status` = 'active') ";
        $sql .= "ORDER BY `T1`.`Position`";

        $form = $this->getAll($sql);

        $count = 1;
        if ($noRecursive || !empty($form)) {
            foreach ($form as $item) {
                $index = $item['Key'] ? $item['Key'] : 'nogroup_' . $count;
                $tmp_form[$index] = $item;
                $count++;
            }
            $form = $tmp_form;
            unset($tmp_form);

            return $form;
        }

        if (empty($form)) {
            /* get parent */
            if ($parent = $this->getOne('Parent_ID', "`ID` = '{$id}'", 'categories')) {
                /* check relations */
                return $this->getParentCatRelations($parent);
            }
        }
    }

    /**
     * build the listing form by listing category id
     *
     * @deprecated 4.7.1 - Use Utils\Category::buildForm()
     *
     * @param int $id - category id
     * @param array $listing_type - listing type details
     *
     * @return array - listing form
     **/
    public function buildListingForm($id = false, $listing_type = false)
    {
        $category = array(
            'ID'         => $id,
            'Parent_IDs' => $this->getOne('Parent_IDs', "`ID` = {$id}", 'categories')
        );

        return Category::buildForm($category, $listing_type, $this->fields);
    }

    /**
     * preparation category deleting
     *
     * @package ajax
     *
     * @param int $id - category id
     *
     **/
    public function ajaxPrepareDeleting($id = false)
    {
        global $_response, $rlSmarty, $lang, $config;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $id = (int) $id;
        $delete_info = $this->categoryWalker($id, 'detect');

        $category = $this->getCategory($id);
        $rlSmarty->assign_by_ref('category', $category);

        if ($delete_info) {
            /* get the first level categoris */
            $sections = $this->getCatTree(0, false, true);
            $rlSmarty->assign_by_ref('sections', $sections);

            $rlSmarty->assign_by_ref('delete_info', $delete_info);
            $tpl = 'blocks' . RL_DS . 'delete_preparing_category.tpl';
            $_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("
                flynax.treeLoadLevel('', '', 'div#replace_content');
                flynax.slideTo('#bc_container');
                $('#delete_block').slideDown();

                category_key = '{$category['Key']}';
                category_name = '{$category['name']}';
            ");
        } else {
            $phrase = $config['trash'] ? $lang['trash_confirm'] : $lang['drop_confirm'];
            $_response->script("
                $('#delete_block').slideUp();
                rlConfirm('{$phrase}', 'xajax_deleteCategory', '{$category['Key']}');
            ");
        }

        return $_response;
    }

    /**
     * delete category
     *
     * @package ajax
     *
     * @param string $key - category key
     * @param int $replace - replace category id
     * @param bool $direct - direct method call
     *
     **/
    public function ajaxDeleteCategory($key = false, $replace = false, $direct = false)
    {
        global $_response, $rlCache, $config, $lang, $controller, $rlDb;

        // check admin session expire
        if ($this->checkSessionExpire() === false && !$direct) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlValid']->sql($key);
        $category_info = $rlDb->fetch(['ID', 'Count', 'Type'], ['Key' => $key], null, 1, 'categories', 'row');
        $id = (int) $category_info['ID'];

        if (!$id || !$key) {
            if (!$direct) {
                $_response->script("printMessage('error', 'Error detected, no category key or ID specified.');");
                return $_response;
            } else {
                return false;
            }
        }

        if ($replace && (int) $replace == (int) $id) {
            if (!$direct) {
                $message = str_replace('{category}', $lang['categories+name+' . $key], $lang['replace_category_duplicate']);
                $_response->script("printMessage('error', '{$message}');");
                return $_response;
            }
            exit;
        }

        if ($replace) {
            $replace = (int) $replace;
            $rlDb->query("UPDATE `{db_prefix}categories` SET `Count` = `Count` + {$category_info['Count']} WHERE `ID` = '{$replace}' LIMIT 1");
        }

        // delete category field relations
        if ($config['trash']) {
            if ($replace) {
                $this->categoryWalker($id, 'replace', '', $replace);
            } else {
                $this->categoryWalker($id, 'trash');
            }
        } else {
            if ($replace) {
                $this->categoryWalker($id, 'replace', '', $replace);
            } else {
                $this->categoryWalker($id, 'delete');
            }
        }

        $this->deleteCatRelations($id);
        Category::resetGeneralCategory($id, $category_info['Type']);

        // delete/trash category info
        $lang_keys = array(
            array('Key' => 'categories+name+' . $key),
            array('Key' => 'categories+title+' . $key),
            array('Key' => 'categories+des+' . $key),
            array('Key' => 'categories+meta_description+' . $key),
            array('Key' => 'categories+meta_keywords+' . $key),
        );

        $GLOBALS['rlActions']->delete(array('Key' => $key), array('categories'), null, 1, $key, $lang_keys);
        $del_mode = $GLOBALS['rlActions']->action;

        $rlCache->updateCategories();
        $rlCache->updateStatistics();

        $GLOBALS['rlHook']->load('apPhpAjaxDeleteCategory', $category_info, $replace, $direct); // > 4.3.0

        // Update single category flag
        $GLOBALS['rlListingTypes']->updateSingleID($category_info['Type']);

        // return if direct mode
        if ($direct) {
            return true;
        }

        if ($controller == 'browse') {
            $_response->redirect(RL_URL_HOME . ADMIN . "/index.php?controller=browse&id=" . $new_id);
        } else {
            $_response->script("
                categoriesGrid.reload();
                $('#replace_content').slideUp();
                $('#delete_block').fadeOut();
            ");
        }

        $_response->script("printMessage('notice', '{$lang['category_' . $del_mode]}')");

        return $_response;
    }

    /**
     * delete category field relations
     *
     * @param int $id - category ID
     *
     **/
    public function deleteCatRelations($id = false)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $tables = array('short_forms', 'listing_titles', 'featured_form', 'listing_relations');
        foreach ($tables as $key => $table) {
            $sql = "DELETE FROM `{db_prefix}{$table}` WHERE `Category_ID` = '{$id}'";
            $this->query($sql);
        }

        // Delete from crossed
        $sql = "UPDATE `{db_prefix}listings` ";
        $sql .= "SET `Crossed` = TRIM(BOTH ',' FROM REPLACE(CONCAT(',',`Crossed`,','), ',{$id},', ',')) ";
        $sql .= "WHERE FIND_IN_SET({$id}, `Crossed`) > 0";
        $this->query($sql);
    }

    /**
     * increase category listings
     *
     * @param int $id - category id
     * @param string $type - listing type key
     *
     **/
    public function listingsIncrease($id, $type = false)
    {
        global $rlCache, $rlHook;

        $id = (int) $id;
        if (empty($id)) {
            return false;
        }

        $sql = "UPDATE `{db_prefix}categories` SET `Count` = `Count`+1, `Modified` = NOW() WHERE `ID` = '{$id}'";
        $this->query($sql);

        /* get category parent */
        $parent = $this->getOne('Parent_ID', "`ID` = {$id}", 'categories');

        if ($parent > 0) {
            $this->listingsIncrease($parent, $type);
        } else {
            $rlCache->updateCategories();

            if (is_object($rlHook)) {
                $rlHook->load('categoriesListingsIncrease', $id, $type); // > 4.1.0
            }

            $type = $type ? $type : $this->getOne('Type', "`ID` = '{$id}'", 'categories');

            $sql = "UPDATE `{db_prefix}listing_types` SET `Count` = `Count`+1 WHERE `Key` = '{$type}'";
            $this->query($sql);

            $rlCache->updateStatistics($type);
        }
    }

    /**
     * decrease category listings
     *
     * @param int $id - category id
     * @param string $type - listing type key
     *
     **/
    public function listingsDecrease($id = false, $type = false)
    {
        global $rlCache, $rlHook;

        $id = (int) $id;
        if (empty($id)) {
            return false;
        }

        $sql = "UPDATE `{db_prefix}categories` SET `Count` = `Count`-1, `Modified` = NOW() WHERE `ID` = '{$id}'";
        $this->query($sql);

        /* get category parent */
        $parent = $this->getOne('Parent_ID', "`ID` = {$id}", 'categories');

        if ($parent > 0) {
            $this->listingsDecrease($parent, $type);
        } else {
            $rlCache->updateCategories();

            if (is_object($rlHook)) {
                $rlHook->load('categoriesListingsDecrease', $id, $type); // > 4.1.0
            }

            $type = $type ? $type : $this->getOne('Type', "`ID` = '{$id}'", 'categories');

            $sql = "UPDATE `{db_prefix}listing_types` SET `Count` = `Count`-1 WHERE `Key` = '{$type}'";
            $this->query($sql);

            $rlCache->updateStatistics($type);
        }
    }

    /**
     * lock/unlock category
     *
     * @package xajax
     *
     * @param int $id - category id
     * @param string $mode - mode: lock | unclock
     **/
    public function ajaxLockCategory($id, $mode = false)
    {
        global $_response, $lang;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$mode || !in_array($mode, array('lock', 'unlock'))) {
            return $_response;
        }

        $status = $mode == 'lock' ? 1 : 0;
        $id = (int) $id;

        /* update lock status */
        $update = array(
            'fields' => array(
                'Lock' => $status,
            ),
            'where'  => array(
                'ID' => $id,
            ),
        );
        $GLOBALS['rlActions']->updateOne($update, 'categories');

        $lang_key = $mode == 'lock' ? 'message_category_locked' : 'message_category_unlocked';
        $new_phrase = $mode == 'lock' ? 'unlock_category' : 'lock_category';
        $new_action = $mode == 'lock' ? 'unlock' : 'lock';

        $_response->script("
            $('#locked_button_phrase').html('{$GLOBALS['lang'][$new_phrase]}').attr('class', 'center_{$new_action}');
            $('#locked_button').attr('onClick', \"xajax_lockCategory('{$id}', '{$new_action}')\");
            printMessage('notice', '{$lang[$lang_key]}');
        ");

        return $_response;
    }

    /**
     * copy fields relations
     *
     * @param int $target_cat - target category id
     *
     * @param int $source_cat - source category id
     *
     * @param string $mode - edit or add
     **/

    public function copyFieldsRelations($target_cat, $source_cat, $mode = 'add')
    {
        $this->loadClass('Actions');

        $source_cat = (int) $source_cat;
        $target_cat = (int) $target_cat;

        // copy main form fields
        $main_relations = $this->fetch('*', array('Category_ID' => $source_cat), null, null, 'listing_relations');
        if (!empty($main_relations)) {
            foreach ($main_relations as $key => $value) {
                unset($main_relations[$key]['ID']);
                $main_relations[$key]['Category_ID'] = $target_cat;
            }
            if ($mode == 'edit') {
                //delete existing relations
                $this->query("DELETE FROM `{db_prefix}listing_relations` WHERE `Category_ID` = {$target_cat}");
            }
            $GLOBALS['rlActions']->insert($main_relations, 'listing_relations');
        }

        // copy listing title fields
        $title_relations = $this->fetch('*', array('Category_ID' => $source_cat), null, null, 'listing_titles');
        if (!empty($title_relations)) {
            foreach ($title_relations as $key => $value) {
                unset($title_relations[$key]['ID']);
                $title_relations[$key]['Category_ID'] = $target_cat;
            }

            if ($mode == 'edit') {
                //delete existing relations
                $this->query("DELETE FROM `{db_prefix}listing_titles` WHERE `Category_ID` = {$target_cat}");
            }
            $GLOBALS['rlActions']->insert($title_relations, 'listing_titles');
        }

        // copy short form fields
        $short_relations = $this->fetch('*', array('Category_ID' => $source_cat), null, null, 'short_forms');
        if (!empty($short_relations)) {
            foreach ($short_relations as $key => $value) {
                unset($short_relations[$key]['ID']);
                $short_relations[$key]['Category_ID'] = $target_cat;
            }

            if ($mode == 'edit') {
                //delete existing relations
                $this->query("DELETE FROM `{db_prefix}short_forms` WHERE `Category_ID` = {$target_cat}");
            }
            $GLOBALS['rlActions']->insert($short_relations, 'short_forms');
        }

        // copy featured form fields
        $featured_relations = $this->fetch('*', array('Category_ID' => $source_cat), null, null, 'featured_form');
        if (!empty($featured_relations)) {
            foreach ($featured_relations as $key => $value) {
                unset($featured_relations[$key]['ID']);
                $featured_relations[$key]['Category_ID'] = $target_cat;
            }

            if ($mode == 'edit') {
                //delete existing relations
                $this->query("DELETE FROM `{db_prefix}featured_form` WHERE `Category_ID` = {$target_cat}");
            }
            $GLOBALS['rlActions']->insert($featured_relations, 'featured_form');
        }
    }

    /**
     * load listing type categories to form on Add Category page
     *
     * @package xajax
     *
     * @param string $type - listing type
     *
     * @todo - add new category
     **/
    public function ajaxLoadType($type = false)
    {
        global $_response, $lang, $rlSmarty, $rlListingTypes, $pages, $languages, $config;

        $GLOBALS['rlValid']->sql($type);

        /* fetch type details */
        $rlSmarty->assign_by_ref('type', $type);

        /* fetch categories */
        $categories = $this->getCatTree(0, $type);
        $rlSmarty->assign_by_ref('categories', $categories);

        $tpl = 'blocks' . RL_DS . 'categories' . RL_DS . 'parent_cats_tree.tpl';
        $_response->assign("parent_categories", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));

        $postfix = $rlListingTypes->types[$type]['Cat_postfix'] ? '.html' : '/';
        $links_type = $rlListingTypes->types[$type]['Links_type'];

        $_response->script("
            $('span#listing_type_loading').fadeOut();
            flynax.treeLoadLevel();
            $('#cat_postfix_el').html('{$postfix}');
        ");

        if ($categories) {
            $_response->script("$('div#parent_category').slideDown();");
        } else {
            $_response->script("$('div#parent_category').slideUp();");
        }

        $pagePath = $pages["lt_{$type}"];

        if ($config['multilingual_paths']) {
            foreach ($languages as $languageKey => $languageData) {
                if ($languageKey !== $config['lang']) {
                    $multilingualPath = $GLOBALS['rlDb']->getOne(
                        "Path_{$languageKey}",
                        "`Key` = 'lt_{$type}'",
                        'pages'
                    ) ?: $pagePath;
                } else {
                    $multilingualPath = $pagePath;
                }

                $urlHome = RL_URL_HOME;
                $urlHome .= $languageData['Code'] !== $config['lang'] ? $languageData['Code'] . '/' : '';

                if ($links_type == 'full') {
                    $_response->script("$('.ab.{$languageKey}').html('{$multilingualPath}/');");
                    $_response->script("$('.abase.{$languageKey}').html('{$urlHome}');");
                    $_response->script("$('.ap.{$languageKey}').html('');");
                } elseif ($links_type == 'subdomain') {
                    $abase = preg_replace('#http(s)?://(www.)?#', "http$1://{$multilingualPath}.", $urlHome);
                    $_response->script("$('.abase.{$languageKey}').html('{$abase}');");
                    $_response->script("$('.ab.{$languageKey}').html('');");
                } else {
                    $_response->script("$('.abase.{$languageKey}').html('{$urlHome}');");
                    $_response->script("$('.ab.{$languageKey}').html('');");
                }
            }
        } else {
            if ($links_type == 'full') {
                $_response->script("$('.ab').html('{$pagePath}/');");
                $_response->script("$('.abase').html('" . RL_URL_HOME . "');");
                $_response->script("$('.ap').html('');");
            } elseif ($links_type == 'subdomain') {
                $abase = preg_replace('#http(s)?://(www.)?#', "http$1://{$pagePath}.", RL_URL_HOME);
                $_response->script("$('.abase').html('{$abase}');");
            } else {
                $_response->script("$('.abase').html('" . RL_URL_HOME . "');");
            }
        }

        return $_response;
    }

    /**
     * get parent categories IDs | RECURSIVE method
     *
     * @param int $id - category ID
     * @param array $ids - found IDs
     *
     * @return array - parent categories IDs
     **/
    public function getParentIDs($id = false, $ids = false)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $parent_id = $this->getOne('Parent_ID', "`ID` = '{$id}'", 'categories');
        if ($parent_id) {
            $ids[] = $parent_id;
            return $this->getParentIDs($parent_id, $ids);
        } else {
            return $ids;
        }
    }

    /**
     * get categories Paths
     *
     * @param string $type - listing type
     *
     * @return array containing categories paths
     **/

    public function getCatPaths($type = false)
    {
        $sql = "SELECT `ID`, `Path`, `Key`, CONCAT('categories+name+', `Key`) as `pName` FROM `{db_prefix}categories`";
        if ($type) {
            $sql .= "WHERE `Type` = '{$type}'";
        }

        return $this->getAll($sql, "ID");
    }

    /**
     * build conversion rate
     *
     **/

    public function buildConversionRates()
    {
//      if( $_SESSION['conversion_rates'] )
        //          return;

        $sql = "SELECT * FROM `{db_prefix}data_formats` WHERE `Conversion` = '1' AND `Parent_ID` = 0";
        $conversion_dfs = $this->getAll($sql);

        foreach ($conversion_dfs as $dkey => $df_item) {
            $sql = "SELECT `Rate`, `Key` FROM `{db_prefix}data_formats` WHERE `Parent_ID` = " . $df_item['ID'];
            $GLOBALS['conversion_rates'][$df_item['Key']] = $this->getAll($sql, array("Key", "Rate"));
        }

//      $_SESSION['conversion_rates'] = $GLOBALS['conversion_rates'];
    }

    /**
     * @since 4.5.0
     *
     * increase account listings count
     *
     * @param int $id - account id
     * @param int $number - number of listings to be increased
     **/
    public function accountListingsIncrease($id = false, $number = false)
    {
        $this->accountListingsCount($id, '+', $number);
    }

    /**
     * @since 4.5.0
     *
     * decrease account listings count
     *
     * @param int $id - account id
     * @param int $number - number of listings to be increased
     **/
    public function accountListingsDecrease($id = false, $number = false)
    {
        $this->accountListingsCount($id, '-', $number);
    }

    /**
     * Account listings count
     *
     * @since 4.5.0
     *
     * @param int    $id     - Account id
     * @param string $sign   - '-' or '+' - decrease or increase
     * @param int    $number - Number of listings
     */
    public function accountListingsCount($id, $sign = '+', $number = 0)
    {
        if (!$id) {
            return false;
        }

        $number = $number ?: 1;
        $sign = $sign ?: "+";

        $sql = "UPDATE `{db_prefix}accounts` SET `Listings_count` = `Listings_count` {$sign} {$number} WHERE `ID` = {$id}";
        $this->query($sql);
    }

    /**
     * @since 4.5.0
     *
     * mass actions with listings
     *
     * @package xAjax
     *
     * @param string $ids     - listings ids
     * @param string $action  - mass action
     **/
    public function ajaxCategoryMassActions($ids = false, $action = false)
    {
        global $_response, $lang, $config, $rlCache;

        if (!$ids || !$action) {
            return $_response;
        }

        $GLOBALS['rlHook']->load('apPhpCategoriesMassActions', $ids, $action); //> 4.5.0

        $ids = explode('|', $ids);
        $this->loadClass('Listings');

        if (in_array($action, array('activate', 'approve'))) {
            $status = $action == 'activate' ? 'active' : 'approval';
            foreach ($ids as $id) {
                $sql = "UPDATE `{db_prefix}categories` SET `Status` = '{$status}' ";
                $sql .= "WHERE `ID` = '{$id}' ";
                $sql .= " OR FIND_IN_SET('{$id}', `Parent_IDs`) ";
                $this->query($sql);

                $GLOBALS['rlListings']->listingStatusControl(array('Category_ID' => $id), $status);
            }
        } elseif ($action == 'delete') {
            foreach ($ids as $id) {
                $category_info = $this->fetch(array('Key', 'ID', 'Count', 'Type'), array('ID' => $id), null, 1, 'categories', 'row');
                $key = $category_info['Key'];

                if ($config['trash']) {
                    $this->categoryWalker($id, 'trash');
                } else {
                    $this->categoryWalker($id, 'delete');
                }

                $this->deleteCatRelations($id);
                Category::resetGeneralCategory($id, $category_info['Type']);

                $lang_keys = array(
                    array('Key' => 'categories+name+' . $key),
                    array('Key' => 'categories+title+' . $key),
                    array('Key' => 'categories+des+' . $key),
                    array('Key' => 'categories+meta_description+' . $key),
                    array('Key' => 'categories+meta_keywords+' . $key),
                );

                $GLOBALS['rlActions']->delete(array('Key' => $key), array('categories'), null, 1, $key, $lang_keys);
            }

            $del_mode = $GLOBALS['rlActions']->action;

            $rlCache->updateCategories();
            $rlCache->updateStatistics();
        }

        $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");
        $_response->script("categoriesGrid.store.reload();");

        return $_response;
    }

    /**
     * Update count of listings in categories
     *
     * @since 4.7.0
     *
     * @return void
     */
    public function recountCategories()
    {
        global $rlDb;

        // Reset counter before recount
        $rlDb->query("UPDATE `{db_prefix}categories` SET `Count` = 0 WHERE 1");

        $counts = $rlDb->getAll(
            "SELECT COUNT(*) AS `Count`, `T1`.`Category_ID`, `T2`.`Parent_IDs`
             FROM `{db_prefix}listings` AS `T1`
             RIGHT JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID`
             WHERE `T1`.`Status` = 'active'
             GROUP BY `T1`.`Category_ID`"
        );

        $categories = [];
        foreach ($counts as $category) {
            $categories[$category['Category_ID']] = $category['Count'];

            if ($category['Parent_IDs']) {
                foreach (explode(',', $category['Parent_IDs']) as $id) {
                    $categories[$id] += $category['Count'];
                }
            }
        }

        if ($rlDb->getOne('ID', "`Status` = 'active' AND `Crossed` <> ''", 'listings')) {
            $crossedCounts = $rlDb->getAll(
                "SELECT `Crossed` FROM `{db_prefix}listings`
                 WHERE `Status` = 'active' AND `Crossed` <> ''"
            );

            foreach ($crossedCounts as $crossedCategories) {
                foreach (explode(',', $crossedCategories['Crossed']) as $categoryID) {
                    if (isset($categories[$categoryID])) {
                        $categories[$categoryID]++;
                    } else {
                        $categories[$categoryID] = 1;
                    }
                }
            }
        }

        foreach ($categories as $categoryID => $count) {
            $rlDb->updateOne([
                'fields' => ['Count' => $count],
                'where'  => ['ID' => $categoryID]
            ], 'categories');
        }
    }
}
