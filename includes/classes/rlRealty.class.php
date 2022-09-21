<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLREALTY.CLASS.PHP
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

class rlRealty
{
    /**
    * @since 4.5.1
    *
    * redefine coordiantes in fields select
    *
    **/
    function hookListingsModifyFieldSearch(&$param1)
    {
        global $coordinates, $tpl_settings, $group_search;

        if (!defined('RL_SEARCH_ON_MAP')) return;

        $param1 .= "ROUND(`T1`.`Loc_latitude`, 5) AS `Loc_latitude`, ROUND(`T1`.`Loc_longitude`, 5) AS `Loc_longitude`, ";

        if ($group_search) return;

        $param1 .= "COUNT(*) AS `Group_count`, ";
    }

    /**
    * @since 4.5.1
    *
    * add where conditions
    *
    **/
    function hookListingsModifyWhereSearch(&$param1)
    {
        global $coordinates, $tpl_settings, $group_search, $group_lat, $group_lng;

        if (!defined('RL_SEARCH_ON_MAP')) return;

        if ($group_search) {
            $param1 .= "AND (ROUND(`T1`.`Loc_latitude`, 5) = {$group_lat} AND ROUND(`T1`.`Loc_longitude`, 5)= {$group_lng})";
        } else {
            $param1 .= "AND `T1`.`Loc_latitude` != 0 AND `T1`.`Loc_longitude` != 0 AND (`T1`.`Loc_latitude` BETWEEN {$coordinates['southWestLat']} AND {$coordinates['northEastLat']})";
            if ($coordinates['northEastLng'] < $coordinates['southWestLng']) {
                $param1 .= "AND (`T1`.`Loc_longitude` BETWEEN {$coordinates['southWestLng']} AND 180 OR `T1`.`Loc_longitude` BETWEEN -180 AND {$coordinates['northEastLng']}) ";
            } else {
                $param1 .= "AND (`T1`.`Loc_longitude` BETWEEN {$coordinates['southWestLng']} AND {$coordinates['northEastLng']}) ";
            }
        }
    }

    /**
    * @since 4.5.1
    *
    * add group statement
    *
    **/
    function hookListingsModifyGroupSearch()
    {
        global $sql, $group_search;

        if (!defined('RL_SEARCH_ON_MAP')) return;

        if ($group_search) return;

        if (false === strpos($sql, 'GROUP BY')) {
            $sql .= " GROUP BY `Loc_latitude`, `Loc_longitude` ";
        } else {
            $sql = str_replace("COUNT(*) AS `Group_count`, ", '', $sql);
        }
    }

    /**
     * Disable cache for build search form method
     * 
     * @return [type] [description]
     */
    function hookPhpSearchBuildSearchTop()
    {
        global $config, $tpl_settings;

        if (!$tpl_settings['home_page_map_search']) {
            return;
        }

        $dbt = debug_backtrace();

        if ($dbt[4]['function'] != 'getHomePageSearchForm') {
            return;
        }

        $GLOBALS['cache_state'] = $config['cache'];
        $config['cache'] = false;        
    }

    /**
    * Disable default search form fetching for the home page
    *
    * @since 4.5.1
    **/
    function hookPhpSearchBuildSearchGetRelations(&$sql)
    {
        global $tpl_settings, $page_info;

        if (!$tpl_settings['home_page_map_search']) {
            return;
        }

        $dbt = debug_backtrace();

        if ($dbt[4]['function'] != 'getHomePageSearchForm') {
            return;
        }

        $GLOBALS['config']['cache'] = $GLOBALS['cache_state'];
        $sql = "SELECT 1;";
    }

    /**
    * @since 4.5.1
    *
    * build home page search form
    *
    **/
    function hookHomeBottom()
    {
        global $tpl_settings, $reefless, $rlSmarty, $rlListingTypes;

        if (!$tpl_settings['home_page_map_search']) {
            return;
        }

        $reefless->loadClass('Search');

        $search_forms = [];

        foreach ($rlListingTypes->types as $type) {
            if ($type['Search_home'] && $type['On_map_search']) {
                $form_key = $type['Key'] . '_on_map';

                if ($form_data = $GLOBALS['rlSearch']->buildSearch($form_key)) {
                    $search_forms[$form_key] = array(
                        'data' => $form_data,
                        'name' => $GLOBALS['lang']['search_forms+name+' . $form_key],
                        'listing_type' => $type['Key']
                    );
                }
            }
        }

        $GLOBALS['rlSearch']->defaultMapAddressAssign();

        $rlSmarty->assign_by_ref('search_forms', $search_forms);
    }

    /**
    * @since 4.5.1
    *
    * sale/rent switcher in admin panel
    *
    **/
    function hookApTplFooter()
    {
        if ($_GET['controller'] == 'listings' && in_array($_GET['action'], array('add', 'edit'))) {
            $script = <<< VS
            <script>
            var apPropertyForHandler = function() {
                if ($('#sale_rent_table input:checked').val() == 2) {
                    $('#time_frame_table').closest('tr').fadeIn();
                } else {
                    $('#time_frame_table').closest('tr').fadeOut();
                    $('#time_frame_table input').removeAttr('checked');
                }
            }
            $(document).ready(function(){
                apPropertyForHandler();

                $('#sale_rent_table input').change(function(){
                    apPropertyForHandler();
                });
            });
            </script>
VS;
            echo $script;
        }
    }
}
