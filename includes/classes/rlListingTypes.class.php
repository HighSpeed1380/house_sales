<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLLISTINGTYPES.CLASS.PHP
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

class rlListingTypes extends reefless
{
    /**
     * @var listing types
     **/
    public $types;

    /**
     * class constructor
     *
     * @param $active - use active type only
     *
     **/
    public function __construct($active = false)
    {
        $this->get($active);
    }

    /**
     * get listing types
     *
     * @param $active - use active type only
     *
     * @return array
     **/
    public function get($active = false)
    {
        global $rlSmarty;

        $sql = "SELECT `T1`.*, IF(`T2`.`Status` = 'active', 1, 0) AS `Advanced_search_availability` ";
        $sql .= "FROM `{db_prefix}listing_types` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}search_forms` AS `T2` ON `T1`.`Key` = `T2`.`Type` AND `T2`.`Mode` = 'advanced' ";
        $sql .= $active ? "WHERE `T1`.`Status` = 'active' " : '';
        $sql .= "ORDER BY `Order` ";

        $GLOBALS['rlHook']->load('listingTypesGetModifySql', $sql); // >= v4.3

        $types = $this->getAll($sql);

        if ($GLOBALS['lang']) {
            $types = $GLOBALS['rlLang']->replaceLangKeys($types, 'listing_types', ['name']);
        }

        if (!empty($this->types)) {
            unset($this->types);
        }

        foreach ($types as $type) {
            $type['Type'] = $type['Key'];
            $type['Page_key'] = 'lt_' . $type['Type'];

            $type['My_key'] = $GLOBALS['config']['one_my_listings_page'] ? 'my_all_ads' : 'my_' . $type['Type'];

            $this->types[$type['Key']] = $type;
        }

        $GLOBALS['rlHook']->load('listingTypesGetAdaptValue', $types, $this->types); // >= v4.3

        unset($types);

        if (is_object($rlSmarty)) {
            $rlSmarty->assign_by_ref('listing_types', $this->types);
        }
    }

    /**
     * activate/deactivate components
     *
     * @param $key - listing type key
     * @param $value - new status value
     *
     * @return array
     **/
    public function activateComponents($key = false, $value = 'active')
    {
        global $rlActions;

        /* activate or deactivate related listings */
        $this->loadClass('Listings');
        $GLOBALS['rlListings']->listingStatusControl(array("Listing_type" => $key), $value);

        $rlActions->rlAllowLikeMatch = true;

        // individual page
        $individual_page = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'lt_' . $key,
            ),
        );
        $rlActions->updateOne($individual_page, 'pages');

        // my listings page
        $my_listings_page = array(
            'fields' => array(
                'Status' => $value == 'active' && !$GLOBALS['config']['one_my_listings_page'] ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'my_' . $key,
            ),
        );
        $rlActions->updateOne($my_listings_page, 'pages');

        // quick search form
        $quick_search = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => $key . '_quick',
            ),
        );
        $rlActions->updateOne($quick_search, 'search_forms');

        // advanced search form
        $advanced_search = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => $key . '_advanced',
            ),
        );
        $rlActions->updateOne($advanced_search, 'search_forms');

        // categories block
        $categories_block = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'ltcb_' . $key,
            ),
        );
        $rlActions->updateOne($categories_block, 'blocks');

        // featured block
        $featured_block = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'ltfb_' . $key . '%',
            ),
        );
        $rlActions->updateOne($featured_block, 'blocks');

        /* activate/deactivate listing type related lang phrases */
        // suspend phrases
        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'pages+name+lt_' . $key,
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'pages+title+lt_' . $key,
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'pages+name+my_' . $key,
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'pages+title+my_' . $key,
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'blocks+name+ltcb_' . $key,
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'blocks+name+ltfb_' . $key . '%',
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'search_forms+name+' . $key . '%',
            ),
        );

        $rlActions->update($update_phrases, 'lang_keys');
        $rlActions->rlAllowLikeMatch = false;
    }

    /**
     * activate/deactivate components related to Admin Only option
     *
     * @param $key - listing type key
     * @param $value - new status value
     *
     * @return array
     **/
    public function adminOnly($key = false, $value = 'active')
    {
        global $rlActions;

        // my listings page
        $my_listings_page = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'my_' . $key,
            ),
        );
        $rlActions->updateOne($my_listings_page, 'pages');

        /* activate/deactivate listing type related lang phrases */
        // suspend phrases
        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'pages+name+my_' . $key,
            ),
        );

        $update_phrases[] = array(
            'fields' => array(
                'Status' => $value == 'active' ? $value : 'trash',
            ),
            'where'  => array(
                'Key' => 'pages+title+my_' . $key,
            ),
        );

        $rlActions->update($update_phrases, 'lang_keys');
    }

    /**
     * arrange type by field values
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange($key = false)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions, $lang;

        /* add field mode */
        if ($key && $fields[$key] && !$type_info['Arrange_field']) {
            /* arrange search tabs | add */
            if ($_POST['is_arrange_search'] && !$type_info['Arrange_search']) {
                $this->arrange_search_add($key);
            }

            /* arrange featured box | add */
            if ($_POST['is_arrange_featured'] && !$type_info['Arrange_featured']) {
                $this->arrange_featured_add($key);
            }
        }
        // edit field mode
        if ($key && $fields[$key] && $type_info['Arrange_field'] && $type_info['Arrange_field'] == $key) {
            /* arrange search tabs | add */
            if ($_POST['is_arrange_search'] && !$type_info['Arrange_search']) {
                $this->arrange_search_add($key);
            }
            /* arrange search tabs | edit */
            elseif ($_POST['is_arrange_search'] && $type_info['Arrange_search']) {
                $this->arrange_search_edit($key);
            }
            /* arrange search tabs | remove */
            elseif (!$_POST['is_arrange_search'] && $type_info['Arrange_search']) {
                $this->arrange_search_remove($key);
            }

            /* arrange featured boxes | add */
            if ($_POST['is_arrange_featured'] && !$type_info['Arrange_featured']) {
                $this->arrange_featured_add($key);
            }
            /* arrange featured boxes | edit */
            elseif ($_POST['is_arrange_featured'] && $type_info['Arrange_featured']) {
                $this->arrange_featured_edit($key);
            }
            /* arrange featured boxes | remove */
            elseif (!$_POST['is_arrange_featured'] && $type_info['Arrange_featured']) {
                $this->arrange_featured_remove($key);
            }
        }
        // change field mode
        if ($key && $fields[$key] && $type_info['Arrange_field'] && $type_info['Arrange_field'] != $key) {
            if ($type_info['Arrange_search']) {
                $this->arrange_search_remove($type_info['Arrange_field']);
                $this->arrange_search_add($key);
            }

            if ($type_info['Arrange_featured']) {
                $this->arrange_featured_remove($type_info['Arrange_field']);
                $this->arrange_featured_add($key);
            }
        }
        // remove field mode
        if (!$key && $type_info['Arrange_field']) {
            // remove all related modules
            if ($type_info['Arrange_search']) {
                $this->arrange_search_remove($type_info['Arrange_field']);
            }

            if ($type_info['Arrange_featured']) {
                $this->arrange_featured_remove($type_info['Arrange_field']);
            }
        }
    }

    /**
     * arrange search tabs | ADD MODE (secondary method)
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange_search_add($key)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions, $lang;

        $field_info = $fields[$key];
        $field_values = explode(',', $field_info['Values']);

        $order = 1;
        foreach ($field_values as $value) {
            $search_key = $f_key . '_tab' . $value;

            $insert[] = array(
                'Key'      => $search_key,
                'Type'     => $f_key,
                'In_tab'   => 1,
                'Value'    => $value,
                'Order'    => $order,
                'Mode'     => 'quick',
                'Groups'   => 0,
                'Readonly' => 1,
            );
            $order++;

            foreach ($allLangs as $lang_key => $lang_value) {
                $phrase = $_POST['arrange_search'][$key][$value][$lang_value['Code']];
                $phrase = $phrase ? $phrase : $lang['search_forms+name+' . $type_info['Key'] . '_tab' . $value];

                $lang_keys[] = array(
                    'Code'   => $lang_value['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => 'search_forms+name+' . $search_key,
                    'Value'  => $phrase,
                );
            }
        }

        if ($insert && $lang_keys) {
            $rlActions->insert($insert, 'search_forms');
            $rlActions->insert($lang_keys, 'lang_keys');
        }
    }

    /**
     * arrange search tabs | EDIT MODE (secondary method)
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange_search_edit($key)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions;

        $field_info = $fields[$key];
        $field_values = explode(',', $field_info['Values']);

        foreach ($field_values as $value) {
            $search_key = $f_key . '_tab' . $value;

            foreach ($allLangs as $lang_key => $lang_value) {
                $phrase = $_POST['arrange_search'][$key][$value][$lang_value['Code']];
                if ($phrase) {
                    $lang_keys[] = array(
                        'fields' => array(
                            'Value' => $phrase,
                        ),
                        'where'  => array(
                            'Code' => $lang_value['Code'],
                            'Key'  => 'search_forms+name+' . $search_key,
                        ),
                    );
                }
            }
        }

        if ($lang_keys) {
            $rlActions->update($lang_keys, 'lang_keys');
        }
    }

    /**
     * arrange search tabs | REMOVE MODE (secondary method)
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange_search_remove($key)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions;

        $field_info = $fields[$key];
        $field_values = explode(',', $field_info['Values']);

        foreach ($field_values as $value) {
            $search_key = $f_key . '_tab' . $value;
            $form_id = $this->getOne('ID', "`Key` = '{$search_key}' AND `Type` = '{$f_key}'", 'search_forms');

            $this->query("DELETE FROM `{db_prefix}search_forms` WHERE `Key` = '{$search_key}' AND `Type` = '{$f_key}' LIMIT 1");
            $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'search_forms+name+{$search_key}'");
            $this->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$form_id}'");
        }
    }

    /**
     * arrange featured boxes | ADD MODE (secondary method)
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange_featured_add($key)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions, $lang;

        $field_info = $fields[$key];
        $field_values = explode(',', $field_info['Values']);

        $parent_box = $this->fetch(array('Page_ID', 'Category_ID', 'Subcategories', 'Sticky', 'Cat_sticky', 'Position', 'Side'), array('Key' => 'ltfb_' . $f_key), null, 1, 'blocks', 'row');
        $order = $parent_box['Position'];

        foreach ($field_values as $value) {
            $box_key = 'ltfb_' . $f_key . '_box' . $value;
            $order++;

            $insert[] = array(
                'Page_ID'  => $parent_box['Page_ID'],
                'Sticky'   => $parent_box['Sticky'],
                'Key'      => $box_key,
                'Position' => $order,
                'Side'     => $parent_box['Side'],
                'Type'     => 'smarty',
                'Content'  => '{include file=\'blocks\'|cat:$smarty.const.RL_DS|cat:\'featured.tpl\' listings=$featured_' . $f_key . '_' . $value . ' type=\'' . $f_key . '\' field=\'' . $key . '\' value=\'' . $value . '\'}',
                'Tpl'      => 1,
                'Status'   => 'active',
                'Readonly' => 1,
            );

            foreach ($allLangs as $lang_key => $lang_value) {
                $phrase = $_POST['arrange_featured'][$key][$value][$lang_value['Code']];
                $phrase = $phrase ? $phrase : str_replace('{type}', $lang['listing_types+name+' . $f_key], $lang['featured_block_pattern']) . "({$value})";

                $lang_keys[] = array(
                    'Code'   => $lang_value['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => 'blocks+name+' . $box_key,
                    'Value'  => $phrase,
                );
            }
        }

        /* move current general featured box to trash */
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'trash' WHERE `Key` = 'ltfb_{$f_key}' LIMIT 1");
        $this->query("UPDATE `{db_prefix}lang_keys` SET `Status` = 'trash' WHERE `Key` = 'blocks+name+ltfb_{$f_key}'");

        if ($insert && $lang_keys) {
            $rlActions->insert($insert, 'blocks');
            $rlActions->insert($lang_keys, 'lang_keys');
        }
    }

    /**
     * arrange featured boxes | EDIT MODE (secondary method)
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange_featured_edit($key)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions;

        $field_info = $fields[$key];
        $field_values = explode(',', $field_info['Values']);

        foreach ($field_values as $value) {
            $box_key = 'ltfb_' . $f_key . '_box' . $value;

            foreach ($allLangs as $lang_key => $lang_value) {
                $phrase = $_POST['arrange_featured'][$key][$value][$lang_value['Code']];
                if ($phrase) {
                    $lang_keys[] = array(
                        'fields' => array(
                            'Value' => $phrase,
                        ),
                        'where'  => array(
                            'Code' => $lang_value['Code'],
                            'Key'  => 'blocks+name+' . $box_key,
                        ),
                    );
                }
            }
        }

        if ($lang_keys) {
            $rlActions->update($lang_keys, 'lang_keys');
        }
    }

    /**
     * arrange featured boxes | REMOVE MODE (secondary method)
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function arrange_featured_remove($key)
    {
        global $fields, $type_info, $f_key, $allLangs, $rlActions;

        $field_info = $fields[$key];
        $field_values = explode(',', $field_info['Values']);

        foreach ($field_values as $value) {
            $box_key = 'ltfb_' . $f_key . '_box' . $value;
            $this->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = '{$box_key}' LIMIT 1");
            $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'blocks+name+{$box_key}'");
        }

        /* move current general featured box to active */
        if ($type_info['Featured_blocks']) {
            $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'active' WHERE `Key` = 'ltfb_{$f_key}' LIMIT 1");
            $this->query("UPDATE `{db_prefix}lang_keys` SET `Status` = 'active' WHERE `Key` = 'blocks+name+ltfb_{$f_key}'");
        }
    }

    /**
     * @deprecated 4.8.2
     **/
    public function arrange_statistics_add($key)
    {}

    /**
     * @deprecated 4.8.2
     **/
    public function arrange_statistics_edit($key)
    {}

    /**
     * @deprecated 4.8.2
     **/
    public function arrange_statistics_remove($key)
    {}

    /**
     * simulate arrange post data
     *
     * @param $key - field key
     *
     * @return bool
     **/
    public function simulate($key = false)
    {
        global $type_info, $fields, $allLangs;

        // arrange search
        $values = explode(',', $fields[$key]['Values']);
        if ($type_info['Arrange_search']) {
            foreach ($values as $value) {
                foreach ($allLangs as $lang_key => $lang_value) {
                    $_POST['arrange_search'][$key][$value][$lang_value['Code']] = $this->getOne('Value', "`Key` = 'search_forms+name+{$type_info['Key']}_tab{$value}' AND `Code` = '{$lang_value['Code']}'", 'lang_keys');
                }
            }
        }

        // arrange featured
        if ($type_info['Arrange_featured']) {
            foreach ($values as $value) {
                foreach ($allLangs as $lang_key => $lang_value) {
                    $_POST['arrange_featured'][$key][$value][$lang_value['Code']] = $this->getOne('Value', "`Key` = 'blocks+name+ltfb_{$type_info['Key']}_box{$value}' AND `Code` = '{$lang_value['Code']}'", 'lang_keys');
                }
            }
        }
    }

    /**
     * Get listings statistics
     *
     * @since 4.8.2 - $listingTypeKey parameter added
     * @since 4.8.2 - $buildMode parameter added
     *
     * @param  bool $buildMode      - Build fresh and return data if true passed
     * @param  bool $listingTypeKey - Build statistics for specified listing type only
     * @return array                - Statistics data for saving into the cache
     **/
    public function statisticsBlock($buildMode = false, $listingTypeKey = false)
    {
        global $rlCache, $config, $rlSmarty, $rlListingTypes, $rlDb, $rlHook;

        $rlHook->load('phpStatisticsBlockTop', $buildMode, $listingTypeKey);

        if ($config['cache'] && !$buildMode) {
            $statistics = $rlCache->get('cache_listing_statistics');
        } else {
            // Get listing statistics
            $types = $listingTypeKey ? array($rlListingTypes->types[$listingTypeKey]) : $rlListingTypes->types;

            foreach ($types as $type) {
                if (!$type['Statistics']) {
                    continue;
                }

                $total = array();
                $new_period = $config['new_period'];

                $sql = "SELECT COUNT(*) AS `Count` ";
                $rlHook->load('phpStatisticsBlockListingsModifySelect', $sql, $type);
                $sql .= "FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
                $rlHook->load('phpStatisticsBlockListingsModifyJoin', $sql, $type);
                $sql .= "WHERE `T2`.`Type` = '{$type['Key']}' AND `T1`.`Status` = 'active' ";
                $rlHook->load('phpStatisticsBlockListingsModifyWhere', $sql, $type);
                $rlHook->load('phpStatisticsBlockListingsModifyGroup', $sql, $type);

                $data = $rlDb->getRow($sql);

                $statistics[$type['Key']] = array(
                    'phrase_key' => 'listing_types+name+' . $type['Key'],
                    'page_key' => $type['Page_key'],
                    'total' => $data['Count'] ?: 0
                );
            }

            unset($data, $sql);

            // Get account statistics
            if (!$listingTypeKey) {
                $sql = "SELECT COUNT(*) AS `Count` ";
                $rlHook->load('phpStatisticsBlockAccountsModifySelect', $sql);
                $sql .= "
                    FROM `{db_prefix}accounts` AS `T1`
                    LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` 
                ";
                $rlHook->load('phpStatisticsBlockAccountsModifyJoin', $sql);
                $sql .= "WHERE `T1`.`Status` = 'active' AND `T2`.`Page` = '1' ";
                $rlHook->load('phpStatisticsBlockAccountsModifyWhere', $sql);
                $rlHook->load('phpStatisticsBlockAccountsModifyGroup', $sql);

                $account_total = $rlDb->getRow($sql);

                if ($account_total['Count']) {
                    $popular_account = $rlDb->getRow("
                        SELECT `T1`.`Type`
                        FROM `{db_prefix}accounts` AS `T1`
                        LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key`
                        WHERE `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' AND `T2`.`Page` = '1'
                        GROUP BY `T1`.`Type`
                        ORDER BY COUNT(*) DESC
                    ");

                    $statistics['accounts'] = array(
                        'is_account' => true,
                        'phrase_key' => 'total_accounts',
                        'page_key' => 'at_' . $popular_account['Type'],
                        'total' => $account_total['Count'] ?: 0
                    );
                }
            }

            $rlHook->load('phpStatisticsBlockBottom', $statistics, $listingTypeKey);
        }

        if ($buildMode) {
            return $statistics;
        } else {
            $rlSmarty->assign_by_ref('statistics_block', $statistics);
        }
    }

    /**
     * delete listing type preparation
     *
     * @package ajax
     *
     * @param int $key - listing type key
     *
     **/
    public function ajaxPrepareDeleting($key = false)
    {
        global $_response, $rlSmarty, $rlHook, $delete_details, $lang, $delete_total_items, $config, $rlListingTypes;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (count($rlListingTypes->types) <= 1) {
            $_response->script("printMessage('alert', '{$lang['limit_listing_types_remove']}')");
            return $_response;
        }

        /* get listing type details */
        $type_info = $rlListingTypes->types[$key];
        $rlSmarty->assign_by_ref('type_info', $type_info);

        /* check listings */
        $sql = "SELECT COUNT(`T1`.`ID`) AS `Count` FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$key}' AND `T1`.`Status` <> 'trash' AND `T2`.`Status` <> 'trash' ";
        $listings = $this->getRow($sql);

        $delete_details[] = array(
            'name'  => $lang['listings'],
            'items' => $listings['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=listings&amp;listing_type=' . $key,
        );
        $delete_total_items += $listings['Count'];

        /* check categories */
        $categories = $this->getRow("SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}categories` WHERE `Type` = '{$key}' AND `Status` <> 'trash'");
        $delete_details[] = array(
            'name'  => $lang['categories'],
            'items' => $categories['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=categories&amp;listing_type=' . $key,
        );
        $delete_total_items += $categories['Count'];

        /* check custom categories */
        $sql = "SELECT COUNT(`T1`.`ID`) AS `Count` FROM `{db_prefix}tmp_categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$key}' AND `T2`.`Status` <> 'trash' ";
        $custom_categories = $this->getRow($sql);
        $delete_details[] = array(
            'name'  => $lang['admin_controllers+name+custom_categories'],
            'items' => $custom_categories['Count'],
            'link'  => RL_URL_HOME . ADMIN . '/index.php?controller=custom_categories',
        );
        $delete_total_items += $custom_categories['Count'];

        $rlHook->load('deleteListingTypeDataCollection');

        $rlSmarty->assign_by_ref('delete_details', $delete_details);

        if ($delete_total_items) {
            $tpl = 'blocks' . RL_DS . 'delete_preparing_listing_type.tpl';
            $_response->assign("delete_container", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
            $_response->script("
                $('#delete_block').slideDown();
            ");
        } else {
            $phrase = $config['trash'] ? str_replace('{type}', $type_info['name'], $lang['notice_drop_empty_listing_type']) : str_replace('{type}', $type_info['name'], $lang['notice_delete_empty_listing_type']);
            $_response->script("
                $('#delete_block').slideUp();
                rlPrompt('{$phrase}', 'xajax_deleteListingType', '{$type_info['Key']}');
            ");
        }

        return $_response;
    }

    /**
     * delete listing type
     *
     * @package ajax
     *
     * @param string $key - listing type Key
     * @param string $reason - remove type reason message
     * @param string $replace_key - new listing type key to replace with
     *
     **/
    public function ajaxDeletingType($key = false, $reason = false, $replace_key = false)
    {
        global $_response, $lang, $config, $rlActions, $rlListingTypes, $rlCache, $rlCategories, $rlDb;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        if (is_array($key)) {
            $replace_key = $key[1];
            $key = $key[0];
        }

        /* delete/replace categories */
        if ($replace_key) {
            $rlDb->query("UPDATE `{db_prefix}categories` SET `Type` = '{$replace_key}' WHERE `Type` = '{$key}'");
        } else {
            $rlDb->setTable('categories');
            if ($categories = $rlDb->fetch(array('Key'), array('Type' => $key))) {
                foreach ($categories as $category) {
                    $rlCategories->ajaxDeleteCategory($category['Key'], false, true);
                }
            }
        }

        /* get listing type details */
        $type_info = $rlListingTypes->types[$key];

        // Delete related phrases with the listing type
        $lang_keys = array(
            array('Key' => 'listing_types+name+' . $key),
            array('Key' => 'pages+name+lt_' . $key),
            array('Key' => 'pages+title+lt_' . $key),
            array('Key' => 'pages+name+my_' . $key),
            array('Key' => 'pages+title+my_' . $key),
            array('Key' => 'blocks+name+ltcb_' . $key),
            array('Key' => 'blocks+name+ltfb_' . $key),
            array('Key' => 'search_forms+name+' . $key . '_quick'),
            array('Key' => 'search_forms+name+' . $key . '_advanced'),
            array('Key' => 'blocks+name+ltsb_' . $key),
            array('Key' => 'blocks+name+ltpb_' . $key),
            array('Key' => 'blocks+name+ltcategories_' . $key),
            array('Key' => 'blocks+name+ltma_' . $key),
        );

        if ($type_info['Arrange_field']) {
            $arrange_values = explode(',', $type_info['Arrange_values']);
            foreach ($arrange_values as $arrange_value) {
                $lang_keys[] = array(
                    'Key' => 'search_forms+name+' . $key . '_tab' . $arrange_value,
                );
                $lang_keys[] = array(
                    'Key' => 'blocks+name+ltfb_' . $key . '_box' . $arrange_value,
                );
                $lang_keys[] = array(
                    'Key' => 'stats+name+' . $key . '_column' . $arrange_value,
                );
            }
        }

        /* trash all related data */
        if ($config['trash']) {
            $this->trashListingTypeData($key, $type_info);
        }

        /* delete listing type */
        $rlActions->delete(array('Key' => $key), array('listing_types'), null, null, $key, $lang_keys, 'ListingTypes', 'deleteListingTypeData', 'restoreListingTypeData');
        $del_mode = $rlActions->action;

        /* unset requested type from globals */
        unset($this->types[$key]);

        /* update cache */
        $rlCache->update();

        /* print message, update grid */
        $_response->script("
            listingTypesGrid.reload();
            printMessage('notice', '{$lang['item_' . $del_mode]}');
            $('#delete_block').slideUp();
        ");

        return $_response;
    }

    /**
     * Delete listing type data
     *
     * @package ajax
     *
     * @param  string $key - Listing type key
     * @return bool
     */
    public function deleteListingTypeData($key = '')
    {
        global $rlActions, $type_info, $rlDb;

        if (!$key) {
            return false;
        }

        // Delete custom categories page
        $sql = "DELETE `T1` FROM `{db_prefix}tmp_categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T2`.`Type` = '{$key}' ";
        $rlDb->query($sql);

        // Delete individual page
        $rlDb->query("DELETE FROM `{db_prefix}pages` WHERE `Key` = 'lt_{$key}' LIMIT 1");

        // Delete individual add listing page
        $rlDb->query("DELETE FROM `{db_prefix}pages` WHERE `Key` = 'al_{$key}' LIMIT 1");
        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Key` = 'pages+name+al_{$key}'
                OR `Key` = 'pages+title+al_{$key}'
                OR `Key` = 'pages+h1+al_{$key}'"
        );

        // Delete my listings page
        $rlDb->query("DELETE FROM `{db_prefix}pages` WHERE `Key` = 'my_{$key}' LIMIT 1");

        // Delete quick search form
        $search_form_id = $rlDb->getOne('ID', "`Key` = '{$key}_quick'", 'search_forms');
        if ($search_form_id) {
            $rlDb->query("DELETE FROM `{db_prefix}search_forms` WHERE `Key` = '{$key}_quick' LIMIT 1");
            $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$search_form_id}'");
        }

        // Delete advanced search form
        $adv_search_form_id = $rlDb->getOne('ID', "`Key` = '{$key}_advanced'", 'search_forms');
        if ($adv_search_form_id) {
            $rlDb->query("DELETE FROM `{db_prefix}search_forms` WHERE `Key` = '{$key}_advanced' LIMIT 1");
            $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$adv_search_form_id}'");
        }

        // Delete my listings form
        if ($my_search_form_id = $rlDb->getOne('ID', "`Key` = '{$key}_myads'", 'search_forms')) {
            $rlDb->query("DELETE FROM `{db_prefix}search_forms` WHERE `Key` = '{$key}_myads' LIMIT 1");
            $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$my_search_form_id}'");
            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'search_forms+name+{$key}_myads'");
        }

        // Delete "Search On Map" form
        if ($onMapFormID = $rlDb->getOne('ID', "`Key` = '{$key}_on_map'", 'search_forms')) {
            $rlDb->query("DELETE FROM `{db_prefix}search_forms` WHERE `ID` = {$onMapFormID} LIMIT 1");
            $rlDb->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$onMapFormID}'");
            $rlDb->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'search_forms+name+{$key}_on_map'");
        }

        // Delete arranged search form
        if ($type_info['Arrange_field']) {
            $arranged_search_forms = $rlDb->getOne('ID', "`Key` LIKE '{$key}_tab%'", 'search_forms');
            if ($arranged_search_forms) {
                foreach ($arranged_search_forms as $arranged_search_form_id) {
                    $rlDb->query(
                        "DELETE FROM `{db_prefix}search_forms`
                         WHERE `ID` = '{$arranged_search_form_id}' LIMIT 1"
                    );
                    $rlDb->query(
                        "DELETE FROM `{db_prefix}search_forms_relations`
                         WHERE `Category_ID` = '{$arranged_search_form_id}'"
                    );
                }
            }
        }

        // Delete "In Category" search forms
        $inCategoryForms = $rlDb->fetch(
            ['ID', 'Key'],
            ['Type' => $key, 'Mode' => 'in_category'],
            null,
            null,
            'search_forms'
        );

        if ($inCategoryForms) {
            foreach ($inCategoryForms as $inCategoryForm) {
                $rlDb->query(
                    "DELETE FROM `{db_prefix}search_forms`
                     WHERE `ID` = {$inCategoryForm['ID']} LIMIT 1"
                );
                $rlDb->query(
                    "DELETE FROM `{db_prefix}search_forms_relations`
                     WHERE `Category_ID` = '{$inCategoryForm['ID']}'"
                );
                $rlDb->query(
                    "DELETE FROM `{db_prefix}lang_keys`
                     WHERE `Key` = 'search_forms+name+{$inCategoryForm['Key']}'"
                );
            }
        }

        // Delete categories block
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'ltcb_{$key}' LIMIT 1");

        // Delete categories block
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'ltcategories_{$key}' LIMIT 1");

        // Delete featured block
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'ltfb_{$key}' LIMIT 1");

        // Delete search block
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'ltsb_{$key}' LIMIT 1");

        // Delete my {type} search block
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'ltma_{$key}' LIMIT 1");

        // Delete {type} search block
        $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = 'ltpb_{$key}' LIMIT 1");

        // Delete arranged featured blocks
        if ($type_info['Arrange_field']) {
            $arranged_blocks = $rlDb->getOne('ID', "`Key` LIKE 'ltfb_{$key}_box%'", 'blocks');
            if ($arranged_blocks) {
                foreach ($arranged_blocks as $arranged_block_id) {
                    $rlDb->query("DELETE FROM `{db_prefix}blocks` WHERE `ID` = '{$arranged_block_id}' LIMIT 1");
                }
            }
        }

        $rlActions->enumRemove('search_forms', 'Type', $key);
        $rlActions->enumRemove('categories', 'Type', $key);
        $rlActions->enumRemove('account_types', 'Abilities', $key);
        $rlActions->enumRemove('saved_search', 'Listing_type', $key);

        return true;
    }

    /**
     * Trash listing type data
     *
     * @since 4.9.0 - Added $type_info parameter
     *
     * @package ajax
     *
     * @param  string $key       - Listing type Key
     * @param  array  $type_info - Data of listing type
     * @return bool
     */
    public function trashListingTypeData($key = false, $type_info = [])
    {
        if (!$key) {
            return false;
        }

        // trash custom categories page
        $sql = "UPDATE `{db_prefix}tmp_categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "SET `T1`.`Status` = 'trash' ";
        $sql .= "WHERE `T2`.`Type` = '{$key}' ";
        $this->query($sql);

        // trash individual page
        $this->query("UPDATE `{db_prefix}pages` SET `Status` = 'trash' WHERE `Key` = 'lt_{$key}' LIMIT 1");

        // update my listings page
        $this->query("UPDATE `{db_prefix}pages` SET `Status` = 'trash' WHERE `Key` = 'my_{$key}' LIMIT 1");

        // delete individual add listing page
        $this->query("UPDATE `{db_prefix}pages` SET `Status` ='trash' WHERE `Key` = 'al_{$key}' LIMIT 1");
        $this->query("UPDATE `{db_prefix}lang_keys` SET `Status` = 'trash' WHERE `Key` = 'pages+name+al_{$key}' OR `Key` = 'pages+title+al_{$key}' OR `Key` = 'pages+h1+al_{$key}'");

        // trash quick search form
        $search_form_id = $this->getOne('ID', "`Key` = '{$key}_quick'", 'search_forms');
        if ($search_form_id) {
            $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'trash' WHERE `Key` = '{$key}_quick' LIMIT 1");
        }

        // trash advanced search form
        $adv_search_form_id = $this->getOne('ID', "`Key` = '{$key}_advanced'", 'search_forms');
        if ($adv_search_form_id) {
            $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'trash' WHERE `Key` = '{$key}_advanced' LIMIT 1");
        }

        // trash my listings form
        $my_search_form_id = $this->getOne('ID', "`Key` = '{$key}_myads'", 'search_forms');
        if ($my_search_form_id) {
            $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'trash' WHERE `Key` = '{$key}_myads' LIMIT 1");
        }

        // trash arranged search form
        if ($type_info['Arrange_field']) {
            $arranged_search_forms = $this->getOne('ID', "`Key` = '{$key}_tab%'", 'search_forms');
            if ($arranged_search_forms) {
                foreach ($arranged_search_forms as $arranged_search_form_id) {
                    $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'trash' WHERE `ID` = '{$arranged_search_form_id}' LIMIT 1");
                }
            }
        }

        // trash categories block
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'trash' WHERE `Key` = 'ltcb_{$key}' LIMIT 1");

        // trash featured block
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'trash' WHERE `Key` = 'ltfb_{$key}' LIMIT 1");

        // trash search block
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'trash' WHERE `Key` = 'ltsb_{$key}' LIMIT 1");

        // delete arranged featured blocks
        if ($type_info['Arrange_field']) {
            $arranged_blocks = $this->getOne('ID', "`Key` = 'ltfb_{$key}_box%'", 'blocks');
            if ($arranged_blocks) {
                foreach ($arranged_blocks as $arranged_block_id) {
                    $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'trash' WHERE `ID` = '{$arranged_block_id}' LIMIT 1");
                }
            }
        }

        return true;
    }

    /**
     * Restore listing type data
     *
     * @package ajax
     *
     * @param  string $key - Listing type Key
     * @return bool
     */
    public function restoreListingTypeData($key = false)
    {
        if (!$key) {
            return false;
        }

        // restore custom categories page
        $sql = "UPDATE `{db_prefix}tmp_categories` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Parent_ID` = `T2`.`ID` ";
        $sql .= "SET `T1`.`Status` = 'approval' ";
        $sql .= "WHERE `T2`.`Type` = '{$key}' ";
        $this->query($sql);

        // restore individual page
        $this->query("UPDATE `{db_prefix}pages` SET `Status` = 'active' WHERE `Key` = 'lt_{$key}' LIMIT 1");

        // restore my listings page
        $this->query("UPDATE `{db_prefix}pages` SET `Status` = 'active' WHERE `Key` = 'my_{$key}' LIMIT 1");

        // delete individual add listing page
        $this->query("UPDATE `{db_prefix}pages` SET `Status` ='active' WHERE `Key` = 'al_{$key}' LIMIT 1");
        $this->query("UPDATE `{db_prefix}lang_keys` SET `Status` = 'active' WHERE `Key` = 'pages+name+al_{$key}' OR `Key` = 'pages+title+al_{$key}' OR `Key` = 'pages+h1+al_{$key}'");

        // restore quick search form
        $search_form_id = $this->getOne('ID', "`Key` = '{$key}_quick'", 'search_forms');
        if ($search_form_id) {
            $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'active' WHERE `Key` = '{$key}_quick' LIMIT 1");
        }

        // restore advanced search form
        $adv_search_form_id = $this->getOne('ID', "`Key` = '{$key}_advanced'", 'search_forms');
        if ($adv_search_form_id) {
            $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'active' WHERE `Key` = '{$key}_advanced' LIMIT 1");
        }

        // trash my listings form
        $my_search_form_id = $this->getOne('ID', "`Key` = '{$key}_myads'", 'search_forms');
        if ($my_search_form_id) {
            $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'active' WHERE `Key` = '{$key}_myads' LIMIT 1");
        }

        $type_info = $GLOBALS['rlListingTypes']->types[$key];

        // restore arranged search form
        if ($type_info['Arrange_field']) {
            $arranged_search_forms = $this->getOne('ID', "`Key` = '{$key}_tab%'", 'search_forms');
            if ($arranged_search_forms) {
                foreach ($arranged_search_forms as $arranged_search_form_id) {
                    $this->query("UPDATE `{db_prefix}search_forms` SET `Status` = 'active' WHERE `ID` = '{$arranged_search_form_id}' LIMIT 1");
                }
            }
        }

        // restore categories block
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'active' WHERE `Key` = 'ltcb_{$key}' LIMIT 1");

        // restore featured block
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'active' WHERE `Key` = 'ltfb_{$key}' LIMIT 1");

        // restore search block
        $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'active' WHERE `Key` = 'ltsb_{$key}' LIMIT 1");

        // restore arranged featured blocks
        if ($type_info['Arrange_field']) {
            $arranged_blocks = $this->getOne('ID', "`Key` = 'ltfb_{$key}_box%'", 'blocks');
            if ($arranged_blocks) {
                foreach ($arranged_blocks as $arranged_block_id) {
                    $this->query("UPDATE `{db_prefix}blocks` SET `Status` = 'active' WHERE `ID` = '{$arranged_block_id}' LIMIT 1");
                }
            }
        }

        return true;
    }

    /**
     * update arranged field relations
     *
     * @param string $key - field key
     * @param string $type - field type
     * @param string $values - new arrange calues
     *
     **/
    public function editArrangeField($key = false, $type = false, $values = false)
    {
        global $allLangs, $rlActions, $rlCache, $rlListingTypes, $lang;

        if (!$key || !$values) {
            $GLOBALS['rlDebug']->logger("Returned from editArrangeField(), no key or valies specified");
            return false;
        }

        $arrange_data = $this->fetch(array('Arrange_values', 'Key'), array('Arrange_field' => $key), null, null, 'listing_types');
        foreach ($arrange_data as $arrange_info) {
            if ($arrange_info && strcmp($values, $arrange_info['Arrange_values']) !== 0) {
                $arr1 = explode(',', $arrange_info['Arrange_values']);
                $arr2 = explode(',', $values);

                /* update ararnge data in listng type */
                $update = array(
                    'fields' => array('Arrange_values' => $values),
                    'where'  => array('Key' => $arrange_info['Key']),
                );
                $rlActions->updateOne($update, 'listing_types');

                /* remove mode */
                foreach ($arr1 as $item1) {
                    if (false === array_search($item1, $arr2)) {
                        /* remove search forms */
                        $search_key = $arrange_info['Key'] . '_tab' . $item1;
                        $form_id = $this->getOne('ID', "`Key` = '{$search_key}' AND `Type` = '{$arrange_info['Key']}'", 'search_forms');

                        $this->query("DELETE FROM `{db_prefix}search_forms` WHERE `Key` = '{$search_key}' AND `Type` = '{$arrange_info['Key']}' LIMIT 1");
                        $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'search_forms+name+{$search_key}'");
                        $this->query("DELETE FROM `{db_prefix}search_forms_relations` WHERE `Category_ID` = '{$form_id}'");

                        /* remove features boxes */
                        $box_key = 'ltfb_' . $arrange_info['Key'] . '_box' . $item1;
                        $this->query("DELETE FROM `{db_prefix}blocks` WHERE `Key` = '{$box_key}' LIMIT 1");
                        $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'blocks+name+{$box_key}'");

                        /* remove statistic entry */
                        $column_key = $arrange_info['Key'] . '_column' . $item1;
                        $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'stats+name+{$column_key}'");
                    }
                }

                /* create mode */
                foreach ($arr2 as $item2) {
                    if (false === array_search($item2, $arr1)) {
                        /* create search forms */
                        $search_key = $arrange_info['Key'] . '_tab' . $item2;

                        $insert = array(
                            'Key'      => $search_key,
                            'Type'     => $arrange_info['Key'],
                            'In_tab'   => 1,
                            'Value'    => $item2,
                            'Order'    => $item2,
                            'Mode'     => 'quick',
                            'Groups'   => 0,
                            'Readonly' => 1,
                        );
                        $rlActions->insertOne($insert, 'search_forms');

                        foreach ($allLangs as $lang_value) {
                            $phrase = $_POST[$type][$item2][$lang_value['Code']];
                            $phrase = $phrase ? $phrase : $lang['search_forms+name+' . $arrange_info['Key'] . '_tab' . $item2];

                            $lang_keys[] = array(
                                'Code'   => $lang_value['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'search_forms+name+' . $search_key,
                                'Value'  => $phrase,
                            );
                        }

                        /* create featured boxes */
                        $parent_box = $this->fetch(array('Page_ID', 'Category_ID', 'Subcategories', 'Sticky', 'Cat_sticky', 'Position', 'Side'), array('Key' => 'ltfb_' . $arrange_info['Key']), null, 1, 'blocks', 'row');
                        $order = $parent_box['Position'] + $item2;
                        $box_key = 'ltfb_' . $arrange_info['Key'] . '_box' . $item2;

                        $insert = array(
                            'Page_ID'  => $parent_box['Page_ID'],
                            'Sticky'   => $parent_box['Sticky'],
                            'Key'      => $box_key,
                            'Position' => $order,
                            'Side'     => $parent_box['Side'],
                            'Type'     => 'smarty',
                            'Content'  => '{include file=\'blocks\'|cat:$smarty.const.RL_DS|cat:\'featured.tpl\' listings=$featured_' . $arrange_info['Key'] . '_' . $item2 . ' type=\'' . $arrange_info['Key'] . '\' field=\'' . $key . '\' value=\'' . $item2 . '\'}',
                            'Tpl'      => 1,
                            'Status'   => 'active',
                            'Readonly' => 1,
                        );
                        $rlActions->insertOne($insert, 'blocks');

                        foreach ($allLangs as $lang_value) {
                            $phrase = $_POST[$type][$item2][$lang_value['Code']];
                            $phrase = $phrase ? $phrase : str_replace('{type}', $lang['listing_types+name+' . $arrange_info['Key']], $lang['featured_block_pattern']) . "({$item2})";

                            $lang_keys[] = array(
                                'Code'   => $lang_value['Code'],
                                'Module' => 'common',
                                'Status' => 'active',
                                'Key'    => 'blocks+name+' . $box_key,
                                'Value'  => $phrase,
                            );
                        }

                        if ($lang_keys) {
                            $rlActions->insert($lang_keys, 'lang_keys');
                        }
                    }
                }

                $rlListingTypes->get();

                /* update cache */
                $rlCache->updateStatistics($arrange_info['Key']);
                $rlCache->updateCategories();
                $rlCache->updateSearchForms();
                $rlCache->updateSearchFields();
            }
        }
    }

    /**
     * Description
     * @param array $box_options
     * @return void
     */
    public function apBlocksTracker($box_options = array())
    {
        global $rlActions, $languages, $lang;

        if (!$box_options['key']) {
            return false;
        }

        // re-assign options
        $lt_key = $box_options['key'];
        $prefix = $box_options['prefix'];
        $page_ids = $box_options['page_ids'] ?: false;
        $box_name_pattern = $box_options['box_name_pattern'];
        $suspend = $box_options['suspend'] ?: false;

        // prevent mySQL errors (be a careful with the array)
        unset($box_options['key'],
            $box_options['prefix'],
            $box_options['page_ids'],
            $box_options['box_name_pattern'],
            $box_options['suspend']);

        // convert array ids to string
        if (is_array($page_ids)) {
            $page_ids = implode(',', $page_ids);
        }

        // prevent unnecessary query to database
        if (!$suspend) {
            $box_id = (int) $this->getOne('ID', "`Key` = '{$prefix}{$lt_key}'", 'blocks');
        }

        /* create a new box if necessary */
        if (!$box_id && !$suspend) {
            // fetch max box position if necessary
            if (!array_key_exists('Position', $box_options)) {
                $sql = "SELECT MAX(`Position`) AS `max` FROM `{db_prefix}blocks`";
                $box_position = $this->getRow($sql, 'max');
            }

            // prepare box details
            $box_info = array(
                'Page_ID'  => $page_ids,
                'Sticky'   => 0,
                'Key'      => $prefix . $lt_key,
                'Position' => $box_position,
                'Side'     => 'left',
                'Type'     => 'smarty',
                'Content'  => $lt_key,
                'Tpl'      => 1,
                'Status'   => 'active',
                'Readonly' => 1,
            );
            $box_info = array_merge($box_info, $box_options);

            $rlActions->insertOne($box_info, 'blocks');

            // add a box names
            $lang_keys = array();
            foreach ($languages as $key => $language) {
                $lang_keys[] = array(
                    'Code'   => $language['Code'],
                    'Module' => 'common',
                    'Status' => 'active',
                    'Key'    => 'blocks+name+' . $prefix . $lt_key,
                    'Value'  => str_replace(
                        '{type}',
                        $_POST['name'][$language['Code']],
                        $lang[$box_name_pattern]),
                );
            }
            $rlActions->insert($lang_keys, 'lang_keys');
        }
        /* create a new box if necessary END */

        /* suspend box */
        else if ($suspend) {
            $suspend_box = array(
                'fields' => array(
                    'Status' => 'trash',
                ),
                'where'  => array(
                    'Key' => $prefix . $lt_key,
                ),
            );
            $rlActions->updateOne($suspend_box, 'blocks');

            // suspend phrases
            $suspend_phrases = array(
                'fields' => array(
                    'Status' => 'trash',
                ),
                'where'  => array(
                    'Key' => 'blocks+name+' . $prefix . $lt_key,
                ),
            );
            $rlActions->updateOne($suspend_phrases, 'lang_keys');
        }
        /* suspend box END */

        /* activate box */
        else {
            $activate_block = array(
                'fields' => array(
                    'Status' => 'active',
                ),
                'where'  => array(
                    'Key' => $prefix . $lt_key,
                ),
            );

            if ($page_ids !== false) {
                $activate_block['fields']['Page_ID'] = $page_ids;
            }

            if ($box_options['Side']) {
                $activate_block['fields']['Side'] = $box_options['Side'];
            }

            $rlActions->updateOne($activate_block, 'blocks');

            // activate phrases
            $activate_phrases = array(
                'fields' => array(
                    'Status' => 'active',
                ),
                'where'  => array(
                    'Key' => 'blocks+name+' . $prefix . $lt_key,
                ),
            );
            $rlActions->updateOne($activate_phrases, 'lang_keys');
        }
        /* activate box END */
    }

    /**
     * Description
     * @param array $type_info
     * @param string $form_field
     * @param string $form_mode
     * @param array $form_options
     * @return void
     */
    public function apSearchFormsTracker($type_info, $form_field, $form_mode, $form_options = array())
    {
        global $rlActions, $languages;

        $post_field = $form_field == 'Search' ? 'search_form' : $form_field;
        $form_active = intval($_POST[strtolower($post_field)]);
        $form_key = $type_info['Key'] . '_' . $form_mode;
        $f_name = $_POST['name'];

        if (intval($type_info[$form_field]) && !$form_active) {
            // suspend form
            $suspend_form = array(
                'fields' => array(
                    'Status' => 'trash',
                ),
                'where'  => array(
                    'Key' => $form_key,
                ),
            );
            $rlActions->updateOne($suspend_form, 'search_forms');

            // suspend phrases
            $suspend_phrases = array(
                'fields' => array(
                    'Status' => 'trash',
                ),
                'where'  => array(
                    'Key' => 'search_forms+name+' . $form_key,
                ),
            );
            $rlActions->updateOne($suspend_phrases, 'lang_keys');
        }

        /* activate the form */
        elseif (!intval($type_info[$form_field]) && $form_active) {
            // check form exists
            $form_id = (int) $this->getOne('ID', "`Key` = '" . $form_key . "'", 'search_forms');

            // create a new search form
            if (!$form_id) {
                $search_form = array(
                    'Key'      => $form_key,
                    'Type'     => $type_info['Key'],
                    'Mode'     => $form_mode,
                    'Groups'   => 1,
                    'Status'   => 'active',
                    'Readonly' => 1,
                );
                $search_form = array_merge($search_form, $form_options);

                $rlActions->insertOne($search_form, 'search_forms');

                // add phrases
                foreach ($languages as $key => $value) {
                    $lang_keys[] = array(
                        'Code'   => $languages[$key]['Code'],
                        'Module' => 'common',
                        'Status' => 'active',
                        'Key'    => 'search_forms+name+' . $form_key,
                        'Value'  => $f_name[$languages[$key]['Code']],
                    );
                }
                $rlActions->insert($lang_keys, 'lang_keys');
            } else {
                // activate search form
                $activate_form = array(
                    'fields' => array(
                        'Status' => 'active',
                    ),
                    'where'  => array(
                        'Key' => $form_key,
                    ),
                );
                $rlActions->updateOne($activate_form, 'search_forms');

                // activate phrases
                $activate_phrases = array(
                    'fields' => array(
                        'Status' => 'active',
                    ),
                    'where'  => array(
                        'Key' => 'search_forms+name+' . $form_key,
                    ),
                );
                $rlActions->updateOne($activate_phrases, 'lang_keys');
            }
        }
    }

    /**
     * @since 4.5.0
     *
     * prepareListingTypeLinks
     *
     * @param - compiled content - ready for output html content
     * @return - transformed html content
     **/
    public function prepareListingTypeLinks($compiled_content)
    {
        global $ltypes_to_transform_links;

        if (!$ltypes_to_transform_links) {
            return $compiled_content;
        }

        foreach ($ltypes_to_transform_links as $lk => $ltype) {
            if ($ltype['Links_type'] == 'subdomain') {
                $domain = $GLOBALS['domain_info']['domain'];
                if (!$domain) {
                    $domain = parse_url(RL_URL_HOME);
                    $domain = "." . preg_replace("/^(www.)?/", "", $domain['host']);
                }

                $new_base = $GLOBALS['domain_info']['scheme'] . "://" . $ltype['Path'] . $domain . "/";

                if ($GLOBALS['pages'][$GLOBALS['listing_type']['Page_key']] && $_GET['listing_id']) {
                    $new_home = $GLOBALS['domain_info']['scheme'] . "://" . $GLOBALS['pages'][$GLOBALS['listing_type']['Page_key']] . $domain . "/";
                    $compiled_content = str_replace(RL_URL_HOME . "request.ajax.php", $new_home . "request.ajax.php", $compiled_content);
                }

                if (RL_LANG_CODE != $GLOBALS['config']['lang']) {
                    $new_base .= RL_LANG_CODE . "/";
                }

                $compiled_content = str_replace(SEO_BASE . $ltype['Path'] . "/", $new_base, $compiled_content);
                $compiled_content = str_replace(SEO_BASE . $ltype['Path'] . ".html", $new_base, $compiled_content);
            } elseif ($ltype['Links_type'] == 'short') {
                $compiled_content = preg_replace('#' . SEO_BASE . $ltype['Path'] . '/(?!' . $GLOBALS['search_results_url'] . '|' . $GLOBALS['advanced_search_url'] . ')#smi', SEO_BASE, $compiled_content);
                $compiled_content = preg_replace('#' . SEO_BASE . 'index(\[pg\]|[0-9]*)\.html#', SEO_BASE . $ltype['Path'] . "/index$1.html", $compiled_content);
                $compiled_content = preg_replace('#' . SEO_BASE . '([a-zA-Z0-9-]+:[a-zA-Z0-9-_,+%]+)/#', SEO_BASE . $ltype['Path'] . "/$1/", $compiled_content);
            }
        }

        return $compiled_content;
    }

    /**
     * Manage the phrases related to the multi-category dropdowns in search forms
     *
     * @since 4.5.1
     *
     * @param  string $typeKey     - Requested listing type key
     * @param  array  $allLangs    - Languages array
     * @param  bool   $useOption   - Use custom phrases for requested type or not
     * @param  array  $postPhrases - Phrases data from the post
     * @return bool
     */
    public function multiCategoryLevel($typeKey = false, $allLangs = [], $useOption = false, $postPhrases = false)
    {
        global $rlDb;

        if (!$typeKey) {
            $GLOBALS['rlDebug']->logger('No key passed in ' . __METHOD__ . ' on ' . __FILE__ . '(line #' . __LINE__ . ')');
            return false;
        }
        if (!$allLangs) {
            $GLOBALS['rlDebug']->logger('No languages array passed in ' . __METHOD__ . ' on ' . __FILE__ . '(line #' . __LINE__ . ')');
            return false;
        }

        if ($useOption) {
            for ($i = 1; $i <= 4; $i++) {
                foreach ($allLangs as $lang_item) {
                    $phrase_key = 'multilevel_category+' . $typeKey . '+' . $lang_item['Code'] . '+' . $i;
                    $phrase_exists = $rlDb->getOne('ID', "`Key` = '{$phrase_key}'", 'lang_keys');

                    if ($postPhrases[$i][$lang_item['Code']]) {
                        $fields = array(
                            'Code'   => $lang_item['Code'],
                            'Module' => 'common',
                            'Status' => 'active',
                            'Key'    => $phrase_key,
                            'Value'  => $postPhrases[$i][$lang_item['Code']],
                        );

                        // update phrases
                        if ($phrase_exists) {
                            $update[] = array(
                                'fields' => $fields,
                                'where'  => array('Key' => $phrase_key),
                            );
                        }
                        // add phrase
                        else {
                            $insert[] = $fields;
                        }
                    }
                    // remove phrases
                    else {
                        $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = '{$phrase_key}'";
                        $rlDb->query($sql);
                    }
                }
            }

            $GLOBALS['reefless']->loadClass('Actions');

            if ($update) {
                $GLOBALS['rlActions']->update($update, 'lang_keys');
            }
            if ($insert) {
                $GLOBALS['rlActions']->insert($insert, 'lang_keys');
            }
        } else {
            // remove all related phrases
            $sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` LIKE 'multilevel_category+{$typeKey}+%'";
            $rlDb->query($sql);
        }

        return true;
    }

    /**
     * Simulate post multi-category levels phrases
     *
     * @since 4.5.1
     *
     * @param string $typeKey      - Requested listing type key
     * @param array  $allLangs     - Languages array
     * @param int    $levelsNumber - Number of levels
     */
    public function simulateMultiCategoryLevel($typeKey = false, $allLangs = [], $levelsNumber = 2)
    {
        global $rlDb;

        if (!$typeKey) {
            $GLOBALS['rlDebug']->logger('No key passed in ' . __METHOD__ . ' on ' . __FILE__ . '(line #' . __LINE__ . ')');
            return false;
        }

        $_POST['multicat_phrases'] = array();

        for ($i = 1; $i <= $levelsNumber; $i++) {
            $_POST['multicat_phrases'][$i] = array();

            foreach ($allLangs as $lang_item) {
                $phrase_key = 'multilevel_category+' . $typeKey . '+' . $lang_item['Code'] . '+' . $i;
                $_POST['multicat_phrases'][$i][$lang_item['Code']] = $rlDb->getOne('Value', "`Code` = '{$lang_item['Code']}' AND `Key` = '{$phrase_key}'", 'lang_keys');
            }
        }
    }

    /**
     * Update count of listings by types
     *
     * @since 4.6.0
     */
    public function updateCountListings()
    {
        $sql = "UPDATE `{db_prefix}listing_types` AS `TCAT` ";
        $sql .= "LEFT JOIN (SELECT COUNT(`T1`.`ID`) as `cnt`, `TCP`.`Type` as `Ltype` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "JOIN `{db_prefix}categories` AS `TCP` ON `TCP`.`ID` = `T1`.`Category_ID` ";
        $sql .= "WHERE `T1`.`Status` = 'active' GROUP BY `TCP`.`Type` ";
        $sql .= ") AS `CT` ON `TCAT`.`Key` = `CT`.`Ltype` ";
        $sql .= "SET `TCAT`.`Count` = IF(`CT`.`cnt`, `CT`.`cnt`, 0)";
        $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Adapt listingType array, remove unavailable types and reorder items by order field
     *
     * @since  4.6.0
     *
     * @param  array &$allowed_type_keys - current user allowed listing type keys
     * @param  array &$types             - listing types array
     * @return array                     - adapte and sorted types array
     */
    public function adaptTypes(&$allowed_type_keys)
    {
        // adapt types, proper order is required
        foreach ($allowed_type_keys as &$allowed_type) {
            if ($this->types[$allowed_type]) {
                $allowed_types[$allowed_type] = $this->types[$allowed_type];

                if ($single_id = $this->types[$allowed_type]['Cat_single_ID']) {
                    $allowed_types[$allowed_type]['Single_category'] = Category::getCategory($single_id);
                }
            }
        }

        $GLOBALS['reefless']->rlArraySort($allowed_types, 'Order');

        return $allowed_types;
    }

    /**
     * Update single category ID flag
     *
     * @since 4.8.0
     *
     * @param string $type - Type key
     */
    public function updateSingleID($type = null)
    {
        if (!$type) {
            return;
        }

        $categories = Category::getCategories($type, 0, 1, false, true);

        $id = count($categories) === 1 && reset($categories)['sub_categories_calc'] === 0
            ? current($categories)['ID']
            : 0;

        $update = [
            'fields' => ['Cat_single_ID' => $id],
            'where'  => ['Key' => $type]
        ];

        $GLOBALS['rlDb']->updateOne($update, 'listing_types');
    }
}
