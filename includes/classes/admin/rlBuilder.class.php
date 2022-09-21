<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLBUILDER.CLASS.PHP
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

class rlBuilder extends reefless
{
    /**
     * @var current table for build actions
     **/
    public $rlBuildTable;

    /**
     * @var "fields" field
     **/
    public $rlBuildField;

    /**
     * class constructor
     **/
    public function __construct()
    {
        $this->rlBuildTable = 'listing_relations';
        $this->rlBuildField = 'Fields';
    }

    /**
     * append element to form
     *
     * @package xajax
     *
     * @param int $Category_ID  - kind id
     * @param int $group_id - group id
     * @param int $field_id - field id
     *
     **/
    public function ajaxBuildForm($category_id = false, $data = false, $no_groups = false)
    {
        global $_response, $rlDb, $rlCache, $lang, $rlHook, $transfer;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (empty($category_id)) {
            $_response->script("
                build_in_progress = false;//FOR TEST
                $('#save_build_form').removeClass('bb_hover').find('span.center').html('{$lang['save']}');
            ");
            return $_response;
        }

        $category_id = (int) $category_id;

        /* assign var for data transfer to plugin, remove from 4.1.0 */
        $transfer = array(
            'category_id' => $category_id,
            'data'        => $data,
        );

        $rlHook->load('apAjaxBuildFormPreSaving'); // from 4.1.0 version

        /* remove form data for current category */
        $sql = "DELETE FROM `" . RL_DBPREFIX . $this->rlBuildTable . "` WHERE `Category_ID` = '{$category_id}'";
        $rlDb->query($sql);

        if (trim($data['ordering'], ',')) {
            unset($data['ordering']);

            $position = 1;
            foreach ($data as $item => $fields) {
                /* save group */
                if (strpos($item, 'group') !== false) {
                    $group = explode('_', $item);
                    $fields_out = array();

                    if (!empty($fields[0])) {
                        foreach ($fields as $field) {
                            $tmp_field = explode('_', $field);
                            $fields_out[] = $tmp_field[1];
                        }

                        if ($fields_out) {
                            $fields_out = implode(',', $fields_out);
                        }
                    }

                    $insert = array(
                        'Position'          => $position,
                        'Category_ID'       => $category_id,
                        'Group_ID'          => $group[1],
                        $this->rlBuildField => $fields_out ?: '',
                    );
                } else {
                    $fields_out = explode('_', $item);

                    $insert = array(
                        'Position'          => $position,
                        'Category_ID'       => $category_id,
                        'Group_ID'          => 0,
                        $this->rlBuildField => $fields_out[1],
                    );
                }

                if ($no_groups) {
                    unset($insert['Group_ID']);
                }

                $rlDb->insertOne($insert, $this->rlBuildTable);

                $position++;
            }

            $rlHook->load('apAjaxBuildFormPostSaving'); // from 4.1.0 version
        }

        /* handle general_cat_only field of listing type */
        if ($GLOBALS['category_info']['Type']) {
            $general_cat_id = $GLOBALS['rlListingTypes']->types[$GLOBALS['category_info']['Type']]['Cat_general_cat'];

            $sql = "SELECT `T1`.`ID` FROM `{db_prefix}listing_relations` AS `T1` JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
            $sql .= "WHERE `T2`.`Type` = '{$GLOBALS['category_info']['Type']}' AND `T1`.`Category_ID` !='" . $general_cat_id . "'";
            $general_only = $rlDb->getRow($sql) ? '0' : '1';

            $update['fields']['Cat_general_only'] = $general_only;
            $update['where']['Key'] = $GLOBALS['category_info']['Type'];

            $rlDb->updateOne($update, 'listing_types');
        }
        /* handle general_cat_only field of listing type end */

        $rlCache->updateForms();

        $_response->script("
            build_in_progress = false;
            $('#save_build_form').removeClass('bb_hover').find('span.center').html('{$lang['save']}');
        ");

        return $_response;
    }

    /**
     * get listing relations
     *
     * @param int $id - listing's kind ID
     *
     * @return array - listing form
     **/
    public function getRelations($id)
    {
        $form = $this->fetch(array('ID', 'Category_ID', 'Group_ID', $this->rlBuildField), array('Category_ID' => $id), "ORDER BY `Position`", null, $this->rlBuildTable);

        foreach ($form as $key => $value) {
            $group_info = null;

            if ($form[$key]['Group_ID']) {
                $group_info = $this->fetch(array('Key', 'Status'), array('ID' => $form[$key]['Group_ID']), null, null, 'listing_groups', 'row');

                if (empty($group_info)) {
                    unset($form[$key]);
                    continue;
                } else {
                    $form[$key]['Key'] = $group_info['Key'];
                    $form[$key]['Status'] = $group_info['Status'];
                }
            }

            $fields = explode(',', $form[$key][$this->rlBuildField]);

            if (!empty($fields[0])) {
                $adapt = false;

                foreach ($fields as $field) {
                    $field = intval($field);
                    $field_info = false;

                    // real field from database
                    if ($field > 0) {
                        $sql = "SELECT DISTINCT `ID`,`Key`,`Type`,`Status` FROM `{db_prefix}listing_fields` ";
                        $sql .= "WHERE `ID` = " . $field . " AND `Status` <> 'trash'";
                        $field_info = $this->getRow($sql);
                    }
                    // fake field - system
                    else if ($field < 0) {
                        $field_info = $GLOBALS['rlCommon']->getSystemFields($field);
                    }

                    if ($field_info) {
                        if ($form[$key]['Group_ID']) {
                            $adapt[] = $field_info;
                        } else {
                            $adapt = $field_info;
                        }
                    }
                }

                $adapt = $GLOBALS['rlLang']->replaceLangKeys($adapt, 'listing_fields', array('name', 'default'));
                $form[$key][$this->rlBuildField] = $adapt ?: false;
            } else {
                unset($form[$key][$this->rlBuildField]);
            }
        }

        $form = $GLOBALS['rlLang']->replaceLangKeys($form, 'listing_groups', array('name'));

        return $form;
    }

    /**
     * get available fields
     *
     * @param int $id - listing's kind ID
     * @param string $table - join table
     *
     * @return array - short form
     **/
    public function getAvailableFields($id = false)
    {
        if (!$id) {
            return false;
        }

        /* get available fields for current category */
        $groups = $this->fetch(array('Group_ID', 'Fields'), array('Category_ID' => $id), null, null, 'listing_relations');

        if (!$groups) {
            $general_cat_id = $GLOBALS['rlListingTypes']->types[$this->getOne('Type', "`ID` = '{$id}'", 'categories')]['Cat_general_cat'];

            if ($id == $general_cat_id) {
                return false;
            }

            if ($parent_id = $this->getOne('Parent_ID', "`ID` = '{$id}'", 'categories')) {
                return $this->getAvailableFields($parent_id);
            } else {
                if ($general_cat_id) {
                    return $this->getAvailableFields($general_cat_id);
                } else {
                    return false;
                }
            }
        } else {
            foreach ($groups as $group) {
                if ($group['Group_ID']) {
                    $tmp_fields = explode(',', $group['Fields']);
                    foreach ($tmp_fields as $field) {
                        if ($field) {
                            $out[] = $field;
                        }
                    }
                    unset($tmp_fields);
                } else {
                    $out[] = (int) $group['Fields'];
                }
            }
            unset($groups);

            return $out;
        }
    }

    /**
     * get form fields ( short display form builder )
     *
     * @param int $id - listing's kind ID
     * @param string $table - join table
     *
     * @return array - short form
     **/
    public function getFormRelations($id, $table = 'listing_fields')
    {
        $sql = "SELECT `T2`.`ID`, `T2`.`Key`, `T2`.`Type`, `T2`.`Status` FROM `" . RL_DBPREFIX . $this->rlBuildTable . "` AS `T1` ";
        $sql .= "RIGHT JOIN `" . RL_DBPREFIX . $table . "` AS `T2` ON `T1`.`Field_ID` = `T2`.`ID` ";
        $sql .= "WHERE `T1`.`Category_ID` = '{$id}' ";

        $sql .= "ORDER BY `T1`.`Position`";

        $fields = $this->getAll($sql);

//      if ( !$fields && $table == 'listing_fields' )
        //      {
        //          if ( $parent_id = $this -> getOne('Parent_ID', "`ID` = '{$id}'", 'categories') )
        //          {
        //              return $this -> getFormRelations($parent_id, $table);
        //          }
        //          else
        //          {
        //              return false;
        //          }
        //      }
        //      else
        //      {
        $fields = $GLOBALS['rlLang']->replaceLangKeys($fields, $table, array('name'));

        // fields adaptation
        foreach ($fields as $key => $value) {
            $relations[$key] = array(
                'ID'          => $fields[$key]['ID'],
                'Category_ID' => $id,
                'Fields'      => $fields[$key],
            );
        }
//      }

        return $relations;
    }
}
