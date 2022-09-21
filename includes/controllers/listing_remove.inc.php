<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LISTING_REMOVE.INC.PHP
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

$id = (int) $_GET['id'];
$hash = $rlValid->xSql($_GET['hash']);
$md5hash = md5($hash);

if (!isset($_GET['complete'])) {
    if (!$id || !$hash) {
        $sError = true;
    } else {
        $sql = "SELECT `T1`.`ID`, `T1`.`Last_step`, `T2`.`Type` AS `Listing_type` ";
        $sql .= "FROM `{db_prefix}listings` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`ID` = '{$id}' AND `T1`.`Loc_address` = '{$md5hash}' AND `T1`.`Status` = 'incomplete' LIMIT 1";
        $listing = $rlDb->getRow($sql);

        if ($listing) {
            if (isset($_GET['confirm'])) {
                $rlListings->deleteListingData($listing['ID']);
                $rlDb->query("DELETE FROM `{db_prefix}listings` WHERE `ID` = '{$listing['ID']}' LIMIT 1");

                $reefless->loadClass('Notice');
                $rlNotice->saveNotice($lang['remote_delete_listing_removed']);

                $url = SEO_BASE;
                $url = $config['mod_rewrite'] ? $pages['listing_remove'] . '.html?complete' : '?page=' . $pages['listing_remove'] . '&complete';
                $reefless->redirect(null, $url);
            } else {
                $rlSmarty->assign_by_ref('listing', $listing);
                $rlSmarty->assign('show_form', true);
                $rlSmarty->assign_by_ref('listing_type', $rlListingTypes->types[$listing['Listing_type']]);
            }
        } else {
            $pAlert = $lang['remote_delete_listing_alert'];
            $rlSmarty->assign('pAlert', $pAlert);
        }
    }
}
