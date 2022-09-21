<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLCACHE.CLASS.PHP
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

/**
 * Cache class
 *
 * Available cache resources:
 * | cache_submit_forms                  - Submit forms
 * | cache_categories_by_type            - Categories by listing type, full list
 * | cache_categories_by_parent          - Categories by listing type, by parent includes subcategories
 * | cache_categories_by_id              - Categories by id, full list
 * | cache_search_forms                  - Search forms by form key
 * | cache_search_fields                 - Search fields list by form key
 * | cache_featured_form_fields          - Featured form fields by category id
 * | cache_listing_titles_fields         - Listing titles form fields by category id
 * | cache_short_forms_fields            - Short form fields by category id
 * | cache_sorting_forms_fields          - Sorting form fields by category id
 * | cache_data_formats                  - Data formats by key
 * | cache_listing_statistics            - Listing statistics by listing type
 * | cache_categories_multilingual_paths - Paths of categories in another languages
 */
class rlCache
{
    /**
     * Memcache object
     * @var object
     */
    public $memcache_obj;

    /**
     * System cache keys
     * @var array
     * @since 4.8.0 - Added "cache_categories_multilingual_paths" resource
     * @since 4.7.2
     */
    public $cacheKeys = array(
        'cache_submit_forms',
        'cache_categories_by_type',
        'cache_categories_by_parent',
        'cache_categories_by_id',
        'cache_search_forms',
        'cache_search_fields',
        'cache_featured_form_fields',
        'cache_listing_titles_fields',
        'cache_short_forms_fields',
        'cache_sorting_forms_fields',
        'cache_data_formats',
        'cache_listing_statistics',
        'cache_categories_multilingual_paths',
    );

    /**
     * Cache keys using for cache dividing
     * @var array
     */
    public $divided_caches = array(
        'cache_short_forms_fields',
        'cache_listing_titles_fields',
        'cache_data_formats',
        'cache_submit_forms',
        'cache_sorting_forms_fields',
    );

    /**
     * class constructor
     **/
    public function __construct()
    {
        global $reefless;

        $cache_method = $GLOBALS['config']['cache_method'] ?: $GLOBALS['rlConfig']->getConfig('cache_method');

        if ($cache_method == 'memcached') {
            $this->memcacheConnect();
        }

        $reefless->loadClass('Categories');
        $reefless->loadClass('Common');

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheConstruct', $this);
    }

    /**
     * Connect to memcache server
     *
     * @since 4.5.0
     *
     * @param string $host
     * @param int    $port
     *
     * @return bool
     */
    public function memcacheConnect($host = RL_MEMCACHE_HOST, $port = RL_MEMCACHE_PORT)
    {
        $success = false;

        if (extension_loaded('memcached')) {
            $this->memcache_obj = new Memcached();
            $success = $this->memcache_obj->addServer($host, $port);
        }

        return $success;
    }

    /**
     * @since 4.6.0
     *
     * Get Cache Key
     *
     * Defines the cache key to get data from; depending on listing type settings and cache availability
     *
     * @param  string $key        - Cache key
     * @param  int    $id         - Category ID
     * @param  string $type       - Listing type data
     * @param  array  $parent_ids - Parent ids
     * @return string             - Cache key
     */
    public function getCacheKey($key, $id, $type = false, $parent_ids = [])
    {
        global $config;

        $cache_key = $config[$key];

        if ($id
            && $config['cache_divided']
            && in_array($key, $this->divided_caches)
        ) {
            if ($type['Cat_general_only']) {
                $cache_key .= '_' . $type['Cat_general_cat'];
            } else {
                if (is_numeric($id)) {
                    if ($this->isCacheFileExists($cache_key, $id)) {
                        $cache_key .= '_' . $id;
                    } else {
                        if ($parent_ids) {
                            foreach ($parent_ids as $parent_id) {
                                if ($this->isCacheFileExists($cache_key, $parent_id)) {
                                    $cache_key .= '_' . $parent_id;
                                    break;
                                }
                            }
                        } else {
                            $cache_key .= '_' . $type['Cat_general_cat'];
                        }
                    }
                } else {
                    $cache_key .= '_' . $id;
                }
            }
        }

        return $cache_key;
    }

    /**
     * Check is the cache file exists
     *
     * @since 4.7.1
     *
     * @param  string  $key - Cache key
     * @param  integer $id  - Cache item ID
     * @return boolean      - Exists status
     */
    private function isCacheFileExists($key, $id)
    {
        return is_readable(RL_CACHE . $key . '_' . $id);
    }

    /**
     * Get cache item
     *
     * @since 4.7.1 - $parent_ids parameter added
     *
     * @param  string       $key        - Cache item key
     * @param  integer      $id         - Cache item id
     * @param  array        $type       - Listing type data
     * @param  array|string $parent_ids - Parent ids as array or string of comma separated ids: 12,51,61
     * @return array|bool               - Cache data
     */
    public function get($key = false, $id = false, $type = false, $parent_ids = null)
    {
        static $content = null;
        static $storage = [];

        global $config;

        if ($parent_ids && is_string($parent_ids)) {
            $parent_ids = explode(',', $parent_ids);
        }

        $cache_key = $this->getCacheKey($key, $id, $type, $parent_ids);

        if (!$key || !$cache_key) {
            return false;
        }

        $out = null;

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheGetBeforeFetch', $out, $key, $id, $type, $parent_ids);

        if ($out) {
            return $out;
        }

        switch ($config['cache_method']) {
            case 'apc':
                $content = apc_fetch($cache_key);
                break;
            case 'memcached':
                $content = $this->memcache_obj->get($cache_key);
                break;
            case 'file':
            default:
                $file = RL_CACHE . $cache_key;

                if (!is_readable($file) && $id) {
                    $file = RL_CACHE . $config[$key];
                }

                if (!is_readable($file)) {
                    return false;
                }

                if (!$storage[$cache_key]) {
                    $content = json_decode(file_get_contents($file), true);
                    $storage[$cache_key] = $content;
                } else {
                    $content = $storage[$cache_key];
                }
                break;
        }

        if ($id === false) {
            $out = $content;
        } elseif ($config['cache_divided'] && in_array($key, $this->divided_caches)) {
            $out = $content;
        } else {
            $out = $content[$type['Key']] ? $content[$type['Key']][$id] : $content[$id];

            if ($type
                && !$out
                && in_array(
                    $key,
                    array(
                        'cache_featured_form_fields',
                        'cache_listing_titles_fields',
                        'cache_short_forms_fields',
                        'cache_submit_forms',
                        'cache_sorting_forms_fields',
                    )
                )
            ) {
                if ($type['Cat_general_only']) {
                    $out = $content[$type['Cat_general_cat']];
                } elseif (isset($parent_ids)) {
                    foreach ($parent_ids as $parent_id) {
                        if ($out = $content[$parent_id]) {
                            break;
                        }
                    }
                } else {
                    $main_content = $content;
                    $categories_by_type = $this->get('cache_categories_by_type', false, $type);
                    $categories_by_type = $categories_by_type[$type['Key']];
                    $out = $this->matchParent($id, 'Parent_ID', $categories_by_type, $main_content);
                    $content = $main_content;
                    unset($main_content, $categories_by_type);
                }

                if (!$out) {
                    $out = $content[$type['Cat_general_cat']];
                }
            }
        }

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheGetAfterFetch', $out, $content, $key, $id, $type, $parent_ids);

        return $out;
    }

    /**
     * Remove cache files by cache item key
     *
     * @since 4.7.2 - Default value added to $key parameter
     * @since 4.7.1
     *
     * @param  string $key - Cache item key
     */
    public function removeFiles($key = null)
    {
        global $config;

        if ($config['cache_method'] != 'file') {
            return;
        }

        foreach ($GLOBALS['reefless']->scanDir(RL_CACHE) as $file) {
            if ($key) {
                if (strpos($file, $key) === 0) {
                    unlink(RL_CACHE . $file);
                }
            } else {
                foreach ($this->cacheKeys as $cache_key) {
                    if (strpos($file, $cache_key) === 0) {
                        unlink(RL_CACHE . $file);
                        break 1;
                    }
                }
            }
        }
    }

    /**
     * Match parent
     *
     * @param string $key - cache srouce
     * @param string $field - parent field name
     * @param array $search - search resurce
     * @param array $content - main content from cache
     */
    public function matchParent(&$id, $field = false, &$search = [], &$content = [])
    {
        if (!$id || !$field || !$search || !$content) {
            return false;
        }

        if ($search[$id][$field]) {
            if (!empty($content[$search[$id][$field]])) {
                return $content[$search[$id][$field]];
            } else {
                return $this->matchParent($search[$id][$field], $field, $search, $content);
            }
        }

        return false;
    }

    /**
     *
     * update submit forms
     * | cache_submit_forms
     *
     **/
    public function updateSubmitForms()
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_submit_forms');

        /* submit forms cache */
        $sql = "SELECT `T1`.`Group_ID`, `T1`.`ID`, `T2`.`ID` AS `Category_ID`, `T3`.`Key` AS `Key`, `T3`.`Display` AS `Display`, ";
        $sql .= "`T1`.`Fields`, CONCAT('listing_groups+name+', `T3`.`Key`) AS `pName`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listing_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T3` ON `T1`.`Group_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T1`.`Group_ID` = '' OR `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `T1`.`Position`";

        $rows = $rlDb->getAll($sql);

        if (!$rows) {
            return false;
        }

        $form = [];
        foreach ($rows as $key => $value) {
            if (!empty($value['Fields'])) {
                $sql = "SELECT *, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order`, ";
                $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, CONCAT('listing_fields+description+', `Key`) AS `pDescription`, ";
                $sql .= "CONCAT('listing_fields+default+', `Key`) AS `pDefault`, `Multilingual` ";
                $sql .= "FROM `{db_prefix}listing_fields` ";
                $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
                $sql .= "ORDER BY `Order`";
                $fields = $rlDb->getAll($sql, 'Key');

                if (empty($fields)) {
                    unset($rows[$key]);
                } else {
                    $rows[$key]['Fields'] = $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);
                }
            } else {
                $rows[$key]['Fields'] = false;
            }

            unset($field_ids, $fields, $field_info);

            // reassign to form, collect by category ID
            $set = $form[$value['Category_ID']] ? count($form[$value['Category_ID']]) + 1 : 1;
            $index = $value['Key'] ? $value['Key'] : 'nogroup_' . $set;
            $form[$value['Category_ID']][$index] = $rows[$key];
        }

        unset($rows);

        $this->set('cache_submit_forms', $form);
    }

    /**
     *
     * update categories by listing type
     * | cache_categories_by_type
     *
     **/
    public function updateCategoriesByType()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        if ($rlListingTypes->types) {
            foreach ($rlListingTypes->types as $key => $value) {
                $sql = "SELECT *, CONCAT('categories+name+', `Key`) AS `pName`, CONCAT('categories+title+', `Key`) AS `pTitle` ";
                $sql .= "FROM `{db_prefix}categories` ";
                $sql .= "WHERE `Type` = '{$value['Key']}' AND `Status` = 'active' ";
                if ($value['Cat_hide_empty']) {
                    $sql .= "AND `Count` > 0 ";
                }
                $out[$value['Key']] = $rlDb->getAll($sql, 'ID');
            }

            $this->set('cache_categories_by_type', $out);
        }
    }

    /**
     *
     * update categories by listing type, organized by parent
     * | cache_categories_by_parent
     *
     **/
    public function updateCategoriesByParent()
    {
        global $config, $rlListingTypes;

        if (!$config['cache']) {
            return false;
        }

        if ($rlListingTypes->types) {
            foreach ($rlListingTypes->types as $key => $value) {
                $out[$value['Key']] = $this->getChildCat(array(0), $value);
            }
            $this->set('cache_categories_by_parent', $out);
        }
    }

    /**
     *
     * update categories by id, full list
     * | cache_categories_by_id
     *
     **/
    public function updateCategoriesByID()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT *, `Modified`, CONCAT('categories+name+', `Key`) AS `pName`, CONCAT('categories+title+', `Key`) AS `pTitle` ";
        $sql .= "FROM `{db_prefix}categories` ";
        $sql .= "WHERE `Status` = 'active'";
        $categories = $rlDb->getAll($sql, 'ID');

        $this->set('cache_categories_by_id', $categories);
    }

    /**
     * Update multilingual paths of categories
     *
     * @since  4.8.0
     *
     * @return bool
     */
    public function updateCategoriesMultiLingualPaths()
    {
        global $config;

        if (!$config['cache'] || !$config['multilingual_paths']) {
            return false;
        }

        $sql = "SELECT `T1`.`ID`";

        foreach ($GLOBALS['languages'] as $langKey => $langData) {
            if ($langKey === $config['lang']) {
                continue;
            }

            $sql .= ", `T1`.`Path_{$langKey}`";
        }

        $sql .= " FROM `{db_prefix}categories` AS `T1` ";
        $sql .= "WHERE `T1`.`Status` = 'active'";
        $categories = $GLOBALS['rlDb']->getAll($sql, 'ID');

        $this->set('cache_categories_multilingual_paths', $categories);
    }

    /**
     * Call all related methods for updating categories data
     */
    public function updateCategories()
    {
        $this->updateCategoriesByType();
        $this->updateCategoriesByParent();
        $this->updateCategoriesByID();
        $this->updateCategoriesMultiLingualPaths();
    }

    /**
     * get children categories by parent | recursive method
     *
     * @param array $parent - parent category ids
     * @param array $type - listing type info
     *
     **/
    public function getChildCat($parent = array(0), $type = false, $data = false)
    {
        global $rlDb;

        foreach ($parent as $parent_id) {
            $parent_id = (int) $parent_id;

            $sql = "SELECT *, `Modified` ";
            $sql .= "FROM `{db_prefix}categories` ";
            $sql .= "WHERE `Type` = '{$type['Key']}' AND `Status` = 'active' AND `Parent_ID` = '{$parent_id}'";
            if ($type['Cat_hide_empty']) {
                $sql .= "AND `Count` > 0 ";
            }
            $sql .= "ORDER BY `Position`";

            if ($tmp_categories = $rlDb->getAll($sql)) {
                foreach ($tmp_categories as $cKey => $cValue) {
                    $ids[] = $cValue['ID'];

                    $categories[$cValue['ID']] = $cValue;
                    $categories[$cValue['ID']]['pName'] = 'categories+name+' . $cValue['Key'];
                    $categories[$cValue['ID']]['pTitle'] = 'categories+title+' . $cValue['Key'];

                    /* get subcategories */
                    if ($type['Cat_show_subcats']) {
                        // TODO - add this condition in output if needs
                        $rlDb->calcRows = true;
                        $subCategories = $rlDb->fetch(array('ID', 'Count', 'Path`, CONCAT("categories+name+", `Key`) AS `pName`, CONCAT("categories+title+", `Key`) AS `pTitle', 'Key'), array('Status' => 'active', 'Parent_ID' => $cValue['ID']), "ORDER BY `Position`", null, 'categories');
                        $rlDb->calcRows = false;

                        if (!empty($subCategories)) {
                            $categories[$cValue['ID']]['sub_categories'] = $subCategories;
                            $categories[$cValue['ID']]['sub_categories_calc'] = $rlDb->foundRows;
                        }

                        unset($subCategories);
                    }
                }
                unset($tmp_categories);

                $data[$parent_id] = $categories;

                unset($categories);
            } else {
                continue;
            }
        }

        if ($parent) {
            return $this->getChildCat($ids, $type, $data);
        } else {
            return $data;
        }
    }

    /**
     * Update search forms by form key (cache_search_forms)
     */
    public function updateSearchForms()
    {
        global $config, $reefless, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`Group_ID`, `T1`.`Fields`, ";
        $sql .= "`T2`.`Key` AS `Group_key`, `T2`.`Display`, ";
        $sql .= "`T3`.`Type` AS `Listing_type`, `T3`.`Key` AS `Form_key`, `T3`.`With_picture` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}listing_groups` AS `T2` ON `T1`.`Group_ID` = `T2`.`ID` AND `T2`.`Status` = 'active' ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T3` ON `T1`.`Category_ID` = `T3`.`ID` ";
        $sql .= "WHERE `T3`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";

        $GLOBALS['rlHook']->load('phpCacheUpdateSearchFormsGetRelations', $sql); // >= v4.3

        $relations = $rlDb->getAll($sql);

        if (!$relations) {
            $out = array(1);
        }

        $reefless->loadClass('Categories');

        /* populate field information */
        foreach ($relations as $key => $value) {
            if (!$value) {
                continue;
            }

            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Autocomplete`, ";
            $sql .= "CONCAT('listing_fields+name+', `Key`) AS `pName`, ";
            $sql .= "`Multilingual`, `Opt1`, `Opt2`, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $rlDb->getAll($sql);

            if ($value['Group_key']) {
                $relations[$key]['pName'] = 'listing_groups+name+' . $value['Group_key'];
            }
            $relations[$key]['Fields'] = empty($fields) ? false : $GLOBALS['rlCommon']->fieldValuesAdaptation($fields, 'listing_fields', $value['Listing_type']);

            $out[$value['Form_key']][] = $relations[$key];
        }

        $GLOBALS['rlHook']->load('phpCacheUpdateSearchFormsBeforeSave', $out, $relations); // >= v4.3

        unset($relations);

        $this->set('cache_search_forms', $out);
    }

    /**
     *
     * update search fields list by form key
     * | cache_search_fields
     *
     **/
    public function updateSearchFields()
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $sql = "SELECT `T1`.`Category_ID`, `T1`.`ID`, `T1`.`Fields`, `T2`.`Key` AS `Form_key` ";
        $sql .= "FROM `{db_prefix}search_forms_relations` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Status` = 'active' ";
        $sql .= "ORDER BY `Position` ";
        $relations = $rlDb->getAll($sql);

        if (!$relations) {
            return false;
        }

        $out = [];
        foreach ($relations as $key => $value) {
            $sql = "SELECT `ID`, `Key`, `Type`, `Default`, `Values`, `Condition`, `Details_page`, `Opt1`, `Opt2`, ";
            $sql .= "`Multilingual`, FIND_IN_SET(`ID`, '{$value['Fields']}') AS `Order` ";
            $sql .= "FROM `{db_prefix}listing_fields` ";
            $sql .= "WHERE FIND_IN_SET(`ID`, '{$value['Fields']}' ) > 0 AND `Status` = 'active' ";
            $sql .= "ORDER BY `Order`";
            $fields = $rlDb->getAll($sql, 'Key');

            $out[$value['Form_key']] = array_merge($out[$value['Form_key']] ?: array(), $fields);
            unset($fields);
        }
        unset($relations);

        $this->set('cache_search_fields', $out);
    }

    /**
     *
     * update featured form fields by category id
     * | cache_featured_form_fields
     *
     **/
    public function updateFeaturedFormFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $rlDb->setTable('categories');
        $categories = $rlDb->fetch(array('ID', 'Key'));

        foreach ($categories as $key => $value) {
            $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, ";
            $sql .= "`T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact`, `T2`.`Hidden` ";
            $sql .= "FROM `{db_prefix}featured_form` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = '{$value['ID']}' ORDER BY `T1`.`Position`";

            $fields = $rlDb->getAll($sql, 'Key');
            if ($fields) {
                $out[$value['ID']] = $fields;
            }
        }
        unset($categories);

        $this->set('cache_featured_form_fields', $out);
    }

    /**
     *
     * update listing title form fields by category id
     * | cache_listing_titles_fields
     *
     **/
    public function updateTitlesFormFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_listing_titles_fields');

        $tables = array('', '', 'short_forms');
        $rlDb->setTable('categories');
        $categories = $rlDb->fetch(array('ID', 'Key'));

        foreach ($categories as $key => $value) {
            $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, ";
            $sql .= "`T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact`, `T2`.`Hidden` ";
            $sql .= "FROM `{db_prefix}listing_titles` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = '{$value['ID']}' ORDER BY `T1`.`Position`";

            $fields = $rlDb->getAll($sql, 'Key');
            if ($fields) {
                $out[$value['ID']] = $fields;
            }
        }
        unset($categories);

        $this->set('cache_listing_titles_fields', $out);
    }

    /**
     *
     * update listing title form fields by category id
     * | cache_short_forms_fields
     *
     **/
    public function updateShortFormFields()
    {
        global $config, $rlListingTypes, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_short_forms_fields');

        $rlDb->setTable('categories');
        $categories = $rlDb->fetch(array('ID', 'Key'));

        foreach ($categories as $key => $value) {
            $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, ";
            $sql .= "`T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact`, `T2`.`Hidden` ";
            $sql .= "FROM `{db_prefix}short_forms` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
            $sql .= "WHERE `T1`.`Category_ID` = '{$value['ID']}' ORDER BY `T1`.`Position`";

            $fields = $rlDb->getAll($sql, 'Key');
            if ($fields) {
                $out[$value['ID']] = $fields;
            }
        }
        unset($categories);

        $this->set('cache_short_forms_fields', $out);
    }

    /**
     * Update listing sorting form fields by category id
     * @cache - cache_sorting_forms_fields
     * @since 4.5.2
     **/
    public function updateSortingFormFields()
    {
        global $rlDb;

        if (!$GLOBALS['config']['cache']) {
            return false;
        }

        $this->removeFiles('cache_sorting_forms_fields');

        $rlDb->setTable('categories');

        if ($categories = $rlDb->fetch(array('ID', 'Key'))) {
            foreach ($categories as $value) {
                $category_id = (int) $value['ID'];

                $sql = "SELECT `T2`.`Key`, `T2`.`Type`, `T2`.`Default`, `T2`.`Condition`, `T2`.`Details_page`, ";
                $sql .= "`T2`.`Multilingual`, `T2`.`Opt1`, `T2`.`Opt2`, `T2`.`Contact` ";
                $sql .= "FROM `{db_prefix}sorting_forms` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}listing_fields` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
                $sql .= "WHERE `T1`.`Category_ID` = {$category_id} ORDER BY `T1`.`Position`";

                if ($fields = $rlDb->getAll($sql, 'Key')) {
                    $out[$category_id] = $fields;
                }
            }

            $this->set('cache_sorting_forms_fields', $out);
        }
    }

    /**
     * call all methods related to forms
     **/
    public function updateForms()
    {
        $this->updateSubmitForms();
        $this->updateSearchForms();
        $this->updateSearchFields();
        $this->updateFeaturedFormFields();
        $this->updateTitlesFormFields();
        $this->updateShortFormFields();
        $this->updateSortingFormFields();
    }

    /**
     * Update data formats by key | cache_data_formats
     */
    public function updateDataFormats()
    {
        global $config, $rlDb;

        if (!$config['cache']) {
            return false;
        }

        $this->removeFiles('cache_data_formats');

        $rlDb->setTable('data_formats');

        /* DO NOT SET ANOTHER FIELD FOR ORDER, ID ONLY */
        $data = $rlDb->fetch(
            ['ID', 'Parent_ID', 'Key`, CONCAT("data_formats+name+", `Key`) AS `pName', 'Position', 'Default'],
            ['Status' => 'active', 'Plugin' => ''],
            'ORDER BY `ID`, `Key`'
        );

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheUpdateDataFormats', $this, $data);

        $out = [];
        foreach ($data as $key => $value) {
            if (!$value['Key']) {
                continue;
            }

            if (!array_key_exists($data[$key]['Key'], $out) && empty($data[$key]['Parent_ID'])) {
                $out[$data[$key]['Key']] = array();
                $df_info[$data[$key]['ID']] = $data[$key]['Key'];
            } else {
                if (!$df_info[$data[$key]['Parent_ID']]) {
                    continue;
                }

                $out[$df_info[$data[$key]['Parent_ID']]][] = $data[$key];
            }
        }

        unset($data, $df_info);
        $this->set('cache_data_formats', $out);
    }

    /**
     * @deprecated 4.8.2 - Use updateStatistics() instead
     **/
    public function updateListingStatistics($listing_type = false)
    {
        $this->updateStatistics($listing_type);
    }

    /**
     * Update statistics box data
     * | cache_listing_statistics
     *
     * @since 4.8.2
     *
     * @param string $listingType - Listing type key
     **/
    public function updateStatistics($listingType = false)
    {
        if (!$GLOBALS['config']['cache']) {
            return false;
        }

        $out = $GLOBALS['rlListingTypes']->statisticsBlock(true, $listingType);

        $this->set('cache_listing_statistics', $out, $listingType);
    }

    /**
     * @since 4.5.0
     *
     * function set
     * @param string $key - cache item key
     * @param array  $data - data array
     * @param string $listing_type - used when need to update only specific listing type in bunch of listing types
     */
    public function set($key, $data, $listing_type = null)
    {
        global $config, $rlDebug, $rlConfig, $reefless, $rlDb;

        if (!$key || !$data) {
            return true;
        }

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheSet', $this, $key, $data, $listing_type);

        if (empty($config[$key])) {
            $hash = $reefless->generateHash();
            if (!$hash) {
                $rlDebug->logger("Can't create cache file, generateHash() doesn't generate anything.");
            } else {
                $cache_key = $key . '_' . $hash;
                if ($config['cache_method'] == 'file' && (!file_exists(RL_CACHE . $config[$key]) || !is_writable(RL_CACHE . $config[$key]))) {
                    $file_dir = RL_CACHE . $cache_key;

                    $fh = fopen($file_dir, 'w') or $rlDebug->logger("Can't create new file, fopen() fail.");
                    fclose($fh);

                    $reefless->rlChmod($file_dir);
                }

                /* save file name */
                $rlConfig->setConfig($key, $cache_key);
                $config[$key] = $cache_key;
            }
        }

        /* save only one and don't affect others */
        if ($listing_type) {
            if ($listing_type && $GLOBALS['rlListingTypes']->types[$listing_type]) {
                $tmp = $this->get($key);
                $tmp[$listing_type] = $data[$listing_type];
                $data = $tmp;
            }
        }

        switch ($config['cache_method']) {
            case 'apc':
                /* store cache as parts (by id) to retrieve only these parts and not whole data */
                if ($config['cache_divided'] && in_array($key, $this->divided_caches)) {
                    apc_delete($config[$key]);

                    foreach ($data as $item_id => $item) {
                        if ($item) {
                            apc_store($config[$key] . '_' . $item_id, $item);
                        }
                    }
                } else {
                    apc_store($config[$key], $data);
                }
                break;
            case 'memcached':
                /* store cache as parts (by id) to retrieve only these parts and not whole data */
                if ($config['cache_divided'] && in_array($key, $this->divided_caches) && is_array($data)) {
                    foreach ($data as $item_id => $item) {
                        if ($item) {
                            $this->memcache_obj->set($config[$key] . '_' . $item_id, $item);
                        }
                    }
                } else {
                    $this->memcache_obj->set($config[$key], $data);
                }
                break;
            case 'file':
            default:
                if ($config['cache_divided'] && in_array($key, $this->divided_caches)) {
                    foreach ($data as $item_id => $item) {
                        if ($item) {
                            $file = RL_CACHE . $config[$key] . '_' . $item_id;

                            $fh = fopen($file, 'w');
                            fwrite($fh, json_encode($item));
                            fclose($fh);
                        }
                    }
                } else {
                    $file = RL_CACHE . $config[$key];

                    $fh = fopen($file, 'w');
                    fwrite($fh, json_encode($data));
                    fclose($fh);
                }
                break;
        }

        unset($data);
    }

    /**
     *
     * update all system cache
     *
     **/
    public function update()
    {
        if ($GLOBALS['config']['cache_method'] == 'memcached') {
            $this->memcache_obj->flush();
        } elseif ($GLOBALS['config']['cache_method'] == 'apc') {
            apc_clear_cache('user');
        }

        $this->removeFiles();

        $this->updateDataFormats();

        $this->updateSubmitForms();
        $this->updateCategoriesByType();
        $this->updateCategoriesByParent();
        $this->updateCategoriesByID();
        $this->updateCategoriesMultiLingualPaths();
        $this->updateSearchForms();
        $this->updateSearchFields();

        $this->updateFeaturedFormFields();
        $this->updateTitlesFormFields();
        $this->updateShortFormFields();
        $this->updateSortingFormFields();

        $this->updateStatistics();

        /**
         * @since 4.8.1
         */
        $GLOBALS['rlHook']->load('phpCacheUpdate', $this);
    }

    /*** DEPRECATE METHODS ***/
    /**
     * Previous get request cache key
     * @deprecated 4.9.0
     * @var string
     */
    private $prev_key = null;
}
