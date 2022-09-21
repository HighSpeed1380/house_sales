<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTINGS_BY_FIELD.INC.PHP
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

use Flynax\Utils\Util;

$fbb_info = $rlDb->fetch('*', array('Key' => $page_info['Key']), null, null, 'field_bound_boxes', 'row');
$field_info = $rlDb->fetch('*', array('Key' => $fbb_info['Field_key']), null, null, 'listing_fields', 'row');

$reefless->loadClass('FieldBoundBoxes', null, 'fieldBoundBoxes');

if (!$fbb_info || !$field_info) {
    $sError = true;
} else {
    $item_path = $rlValid->xSql($_GET['nvar_1'] ?: $_GET['item']);
    $rlSmarty->assign('item_path', $config['mod_rewrite'] ? $item_path : "item={$item_path}");

    if ($item_path) {
        $sql ="SELECT * FROM `{db_prefix}field_bound_items` ";
        $sql .="WHERE `Box_ID` = '{$fbb_info['ID']}' ";
        $sql .="AND `Path` = '{$item_path}' ";

        $item_info = $rlDb->getRow($sql);
    }

    if ($item_path && !$item_info) {
        $sError = true;
    } elseif ($item_info) {
        if ($rlFieldBoundBoxes->isNewMultiField() && $rlFieldBoundBoxes->isFormatBelongToMultiField($field_info['Condition'])) {
            $option_name = $rlDb->getOne('Value', "`Key` = '{$item_info['Key']}'", 'multi_formats_lang_' . RL_LANG_CODE);
            define('FBB_MULTIFIELD_MODE', true);
        } else {
            $option_name = $lang[$item_info['pName']];
        }

        $option_name_default = $lang['field_bound_items+name+' . $item_info['Key']] ?: $option_name;

        $reefless->loadClass('Listings');

        foreach ($rlFieldBoundBoxes->lang_elements['field_bound_items'] as $element) {
            if ($element == 'name') {
                continue;
            }

            $seo_data_item = $lang['field_bound_items+' . $element . '+' . $item_info['Key']];

            if (!$seo_data_item) {
                $seo_data_item = $lang['fbb_defaults+' . $element . '+' . $fbb_info['Key']];
                $seo_data_item = str_replace('{item}', $option_name_default, $seo_data_item);
            }

            if ($seo_data_item) {
                $seo_data[$element] = $seo_data_item;
            }
        }

        $page_info['meta_description'] = $seo_data['meta_description'];
        $page_info['meta_keywords'] = $seo_data['meta_keywords'];
        $page_info['h1'] = $seo_data['h1'];
        $page_info['title'] = $seo_data['title'];
        $page_info['name'] = $option_name;

        $description = $seo_data['des'];

        if ($fbb_info['Parent_page']) {
            $bread_crumbs[] = array(
                'name' => $seo_data['title'] ?: $option_name
            );
        }

        $rlSmarty->assign('description', $description);

        $pInfo['current'] = (int) $_GET['pg'];

        $sorting = array(
            'category' => array(
                'name' => $lang['category'],
                'field' => 'Category_ID',
            ),
            'status' => array(
                'name' => $lang['status'],
                'field' => 'Status',
            ),
            'date' => array(
                'name' => $lang['date'],
                'Type' => 'date',
                'Key' => 'date',
            ),
        );
        $rlSmarty->assign_by_ref('sorting', $sorting);

        $sort_by = empty($_GET['sort_by']) ? $_SESSION['ml_sort_by'] : $_GET['sort_by'];
        $sort_by = $sort_by ? $sort_by : 'date';
        if (!empty($sorting[$sort_by])) {
            $order_field = $sorting[$sort_by]['field'];
        }
        $_SESSION['ml_sort_by'] = $sort_by;
        $rlSmarty->assign_by_ref('sort_by', $sort_by);
        
        $sort_type = empty($_GET['sort_type']) ? $_SESSION['ml_sort_type'] : $_GET['sort_type'];
        $sort_type = !$sort_type && $sort_by == 'date' ? 'desc' : $sort_type;
        $sort_type = in_array($sort_type, array('asc', 'desc')) ? $sort_type : false;
        $_SESSION['ml_sort_type'] = $sort_type;
        $rlSmarty->assign_by_ref('sort_type', $sort_type);

        if ($pInfo['current'] > 1) {
            $bc_page = str_replace('{page}', $pInfo['current'], $lang['title_page_part']);
            $bread_crumbs[1]['title'] .= $bc_page;
        }
        
        $item_value = !$field_info['Condition'] && $field_info['Key'] != 'posted_by'
            ? substr($item_info['Key'], strlen($field_info['Key'])+1)
            : $item_info['Key'];

        define('FBB_MODE', true);

        $listings = $rlListings->getListings(
            0,
            $order_field,
            $sort_type,
            $pInfo['current'],
            $config['listings_per_page'],
            $fbb_info['Listing_type']
        );

        if ($listings) {
            $rlSmarty->assign_by_ref('listings', $listings);

            $pInfo['calc'] = $rlListings->calc;
            $rlSmarty->assign_by_ref('pInfo', $pInfo);
        } else {
            $listing_type = $fbb_info['Listing_type'] ? $rlListingTypes->types[$fbb_info['Listing_type']] : false;
            $add_page_path = $listing_type['Add_page'] && $pages['al_' . $listing_type['Key']] 
            ? $pages['al_' . $listing_type['Key']]
            : $pages['add_listing'];
            $add_listing_link = SEO_BASE;
            $add_listing_link .= $config['mod_rewrite'] ? $add_page_path . '.html' : '?page=' . $add_page_path;
            $rlSmarty->assign('add_listing_link', $add_listing_link);
        }
    } else {
        if ($fbb_info['Parent_page']) {
            $options = $rlDb->fetch(
                '*',
                array('Box_ID' => $fbb_info['ID'], 'Status' => 'active'),
                'ORDER BY `Position`',
                null,
                'field_bound_items'
            );

            if ($options) {
                $box_phrases = $rlFieldBoundBoxes->getMultiFieldLangPhrases($options);
                $rlSmarty->assign('fbb_box_phrases', $box_phrases);
                $rlFieldBoundBoxes->prepareOptions($options, $fbb_info);

                $rlSmarty->assign_by_ref('fbb_options', $options);

                if ($fbb_info['Show_count']) {
                    $rlFieldBoundBoxes->recountByLocation($options, $fbb_info);
                }
            }

            $rlSmarty->assign_by_ref('fbb_box', $fbb_info);

            // Simulate box side data
            $rlSmarty->assign('block', ['Side' => 'top']);
        } else {
            Util::redirect(SEO_BASE);
        }
    }
}
