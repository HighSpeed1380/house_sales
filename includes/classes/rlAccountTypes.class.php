<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLACCOUNTTYPES.CLASS.PHP
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

class rlAccountTypes extends reefless
{
    /**
     * @var array - Account types
     */
    public $types;

    /**
     * List of boxes which must be existed in the account type page
     *
     * @since 4.9.0
     *
     * @var array
     */
    private $systemBoxes = [
         'account_search',
         'account_alphabetic_filter',
         'account_page_search_listings',
         'account_page_info',
         'account_page_location',
    ];

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
     * Get account types
     *
     * @param  bool  $active - Get active type only
     * @return void
     */
    private function get($active = false)
    {
        global $rlSmarty;

        $sql = "SELECT `T1`.* ";
        $sql .= "FROM `{db_prefix}account_types` AS `T1` ";
        $sql .= $active ? "WHERE `T1`.`Status` = 'active' " : '';
        $sql .= "ORDER BY `Position`";

        $GLOBALS['rlHook']->load('accountTypesGetModifySql', $sql);

        $types = $this->getAll($sql);

        if ($GLOBALS['lang']) {
            $types = $GLOBALS['rlLang']->replaceLangKeys($types, 'account_types', ['name', 'desc']);
        }

        foreach ($types as $type) {
            $type['Type'] = $type['Key'];
            $type['Page_key'] = 'at_' . $type['Type'];
            $this->types[$type['Key']] = $type;
        }

        $GLOBALS['rlHook']->load('accountTypesGetAdaptValue', $this->types);

        unset($types);

        if (is_object($rlSmarty)) {
            $rlSmarty->assign_by_ref('account_types', $this->types);
        }
    }

    /**
     * Add all necessary system boxes to the new account type page
     *
     * @since 4.9.0
     *
     * @param int $pageID
     *
     * @return bool
     * @throws Exception
     */
    public function addSystemBoxesToPage(int $pageID): bool
    {
        return $this->managePageInSystemBoxes($pageID, 'add');
    }

    /**
     * Remove account type page from the system boxes
     *
     * @since 4.9.0
     *
     * @param string $key
     *
     * @return bool
     * @throws Exception
     */
    public function removePageFromSystemBoxes(string $key): bool
    {
        if (!$key) {
            return false;
        }

        $pageID = (int) $GLOBALS['rlDb']->getOne(
            'ID',
            "`Key` = 'at_{$key}' AND `Controller` = 'account_type'",
            'pages'
        );

        if (!$pageID) {
            return false;
        }

        return $this->managePageInSystemBoxes($pageID, 'remove');
    }

    /**
     * Add/remove boxes in the account type page
     *
     * @since 4.9.0
     *
     * @param int    $pageID
     * @param string $action
     *
     * @return bool
     * @throws Exception
     */
    private function managePageInSystemBoxes(int $pageID, string $action): bool
    {
        $action = Valid::escape($action);

        if (!$pageID || !in_array($action, ['add', 'remove'])) {
            throw new Exception('Missing $pageID parameter or added not allowed action (maybe "add" or "remove")');
        }

        $systemBoxes = $GLOBALS['rlDb']->getAll(
            "SELECT `Key`, `Page_ID` FROM `{db_prefix}blocks`
             WHERE `Key` IN ('" . implode("','", $this->systemBoxes) . "')"
        );

        if (!$systemBoxes) {
            return false;
        }

        foreach ($systemBoxes as $systemBox) {
            $pageIDs = explode(',', $systemBox['Page_ID']);

            if ($action === 'add') {
                $pageIDs[] = $pageID;
            } else if ($action === 'remove') {
                if (($index = array_search($pageID, $pageIDs)) !== false) {
                    unset($pageIDs[$index]);
                }
            }

            $GLOBALS['rlDb']->updateOne([
                'fields' => ['Page_ID' => implode(',', $pageIDs)],
                'where' => ['Key' => $systemBox['Key']],
            ], 'blocks');
        }

        return true;
    }

    /**
     * Get keys of system boxes in the account type page
     *
     * @since 4.9.0
     *
     * @return array
     */
    public function getSystemBoxes(): array
    {
        return $this->systemBoxes;
    }

    /**
     * Add new box to list of boxes in the account type page
     *
     * @since 4.9.0
     *
     * @param string $box - Key of the box
     *
     * @return $this
     */
    public function addBoxToSystemBoxes(string $box): rlAccountTypes
    {
        $this->systemBoxes[] = $box;
        return $this;
    }
}
