<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLFORMATS.CLASS.PHP
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

class rlFormats extends reefless
{
    /**
     * Add format item
     *
     * @package ajax
     *
     * @param string $key - key
     * @param array $names - names
     * @param string $status - status
     * @param string $format - parent format key
     * @param bool $default - is default
     *
     **/
    public function ajaxAddItem($key = false, $names = array(), $status = false, $format = false, $default = false)
    {
        global $_response, $lang, $insert, $config, $rlDb;

        // Check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        $GLOBALS['rlValid']->sql($format);

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $rlDb->setTable('data_formats');

        $key = utf8_is_ascii($key) ? $key : utf8_to_ascii($key);
        $item_key = $GLOBALS['rlValid']->str2key($key);

        // Check key
        if (strlen($item_key) < 3) {
            $errors[] = $lang['incorrect_phrase_key'];
        }

        $item_key = $format . '_' . $item_key;

        if (!utf8_is_ascii($item_key)) {
            $errors[] = $lang['key_incorrect_charset'];
        }

        $key_exist = $rlDb->getOne('ID', "`Key` = '{$item_key}'");

        if (!empty($key_exist)) {
            $errors[] = str_replace('{key}', "'<b>{$item_key}</b>'", $lang['notice_item_key_exist']);
        }

        // Check names
        $languages = $GLOBALS['languages'];
        foreach ($languages as $lang_item) {
            if ($names[$lang_item['Code']] == '') {
                $errors[] = str_replace('{field}', "'<b>{$lang['value']} ({$lang_item['name']})</b>'", $lang['notice_field_empty']);
            }
        }

        if ($errors) {
            $out = '<ul>';
            foreach ($errors as $error) {
                $out .= '<li>' . $error . '</li>';
            }
            $out .= '</ul>';

            $_response->script('printMessage("error", "' . $out . '");');
        } else {
            $parent_info = $rlDb->fetch('*', ['Key' => $format], null, 1, 'data_formats', 'row');

            if ($default) {
                $uncheckall = "UPDATE `{db_prefix}data_formats` SET `Default` = '0' WHERE `Parent_ID` = {$parent_info['ID']}";
                $rlDb->query($uncheckall);
            }

            $insert = array(
                'Parent_ID' => $parent_info['ID'],
                'Key'       => $item_key,
                'Status'    => $status,
                'Default'   => $default,
            );

            if ($parent_info['Order_type'] == 'position') {
                $position = $rlDb->getRow("
                    SELECT MAX(`Position`) AS `Position`
                    FROM `{db_prefix}data_formats`
                    WHERE `Parent_ID` = {$parent_info['ID']}
                ");
                $insert['Position'] = $position['Position']+1;
            }

            // Insert new item
            if ($rlDb->insertOne($insert, 'data_formats')) {
                foreach ($languages as $lang_item) {
                    $lang_keys[] = array(
                        'Code'   => $lang_item['Code'],
                        'Module' => 'common',
                        'Key'    => 'data_formats+name+' . $item_key,
                        'Value'  => $names[$lang_item['Code']],
                    );

                    if ($config['lang'] == $lang_item['Code']) {
                        $lang['data_formats+name+' . $item_key] = $names[$lang_item['Code']];
                    }
                }

                if ($rlDb->insert($lang_keys, 'lang_keys')) {
                    $_response->script("printMessage('notice', '{$lang['item_added']}')");

                    $_response->script("itemsGrid.reload();");
                    $_response->script("$('#new_item').slideUp('normal')");
                }

                $GLOBALS['rlCache']->updateDataFormats();
                $GLOBALS['rlCache']->updateForms();

                $GLOBALS['rlHook']->load('apPhpFormatsAjaxAddItem');
            }
        }

        $_response->script("$('input[name=item_submit]').val('{$lang['add']}');");

        return $_response;
    }

    /**
     * preparing item editing
     *
     * @package ajax
     *
     * @param string $key - key
     *
     **/
    public function ajaxPrepareEdit($key = false)
    {
        global $_response;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING'])
            ? '?session_expired'
            : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($key);

        /* get item info */
        $item = $this->fetch(
            array('ID', 'Key', 'Status', 'Default'),
            array('Key' => $key),
            null,
            1,
            'data_formats',
            'row'
        );
        $GLOBALS['rlSmarty']->assign_by_ref('item', $item);

        /* get item names */
        $tmp_names = $this->fetch(
            array('Code', 'Value'),
            array('Key' => 'data_formats+name+' . $key),
            "AND `Status` <> 'trash'",
            null,
            'lang_keys'
        );

        foreach ($tmp_names as $k => $v) {
            $names[$tmp_names[$k]['Code']] = $tmp_names[$k];
        }
        unset($tmp_names);

        $GLOBALS['rlSmarty']->assign_by_ref('names', $names);

        $tpl = 'blocks' . RL_DS . 'edit_format_block.tpl';

        $_response->assign("prepare_edit_area", 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script('flynax.tabs(); flynax.copyPhrase();');

        return $_response;
    }

    /**
     * edit format item
     *
     * @package ajax
     *
     * @param string $key - key
     * @param array $names - names
     * @param string $status - status
     * @param string $format - parent format
     *
     **/
    public function ajaxEditItem($key, $names, $status, $format, $default)
    {
        global $_response, $lang, $config, $rlValid;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        loadUTF8functions('ascii', 'utf8_to_ascii', 'unicode');

        $this->setTable('data_formats');
        $item_key = $rlValid->xSql(trim($key));
        $format = $rlValid->sql($format);

        /* check names */
        $languages = $GLOBALS['languages'];
        foreach ($languages as $key => $value) {
            if ($names[$languages[$key]['Code']] == '') {
                $errors[] = str_replace('{field}', "'<b>{$lang['value']} ({$languages[$key]['name']})</b>'", $lang['notice_field_empty']);
            }
        }

        if ($errors) {
            $out = '<ul>';

            /* print errors */
            foreach ($errors as $error) {
                $out .= '<li>' . $error . '</li>';
            }
            $out .= '</ul>';
            $_response->script("printMessage('error', '{$out}');");
        } else {
            if ($default) {
                $parent_id = (int) $this->getOne('ID', "`Key` = '{$format}'");
                $uncheckall = "UPDATE `{db_prefix}data_formats` SET `Default` = '0' WHERE `Parent_ID` = {$parent_id}";
                $this->query($uncheckall);
            }

            $update = array(
                'fields' => array(
                    'Default' => $default,
                    'Status'  => $status,
                ),
                'where'  => array(
                    'Key' => $item_key,
                ),
            );

            $GLOBALS['rlHook']->load('apPhpFormatsAjaxEditItem', $update);

            /* update item */
            if ($GLOBALS['rlActions']->updateOne($update, 'data_formats')) {
                /* update item name */
                foreach ($languages as $lang_item) {
                    if ($this->getOne('ID', "`Key` = 'data_formats+name+{$item_key}' AND `Code` = '{$lang_item['Code']}'", 'lang_keys')) {
                        $lang_keys[] = array(
                            'fields' => array(
                                'Value' => $names[$lang_item['Code']],
                            ),
                            'where'  => array(
                                'Code' => $lang_item['Code'],
                                'Key'  => 'data_formats+name+' . $item_key,
                            ),
                        );
                    } else {
                        $insert_phrase[] = array(
                            'Module' => 'common',
                            'Value'  => $names[$lang_item['Code']],
                            'Code'   => $lang_item['Code'],
                            'Key'    => 'data_formats+name+' . $item_key,
                        );
                    }

                    if ($config['lang'] == $lang_item['Code']) {
                        $GLOBALS['lang']['data_formats+name+' . $item_key] = $names[$lang_item['Code']];
                    }
                }

                $action = false;

                if (!empty($lang_keys)) {
                    $action = $GLOBALS['rlActions']->update($lang_keys, 'lang_keys');
                }
                if (!empty($insert_phrase)) {
                    $action = $GLOBALS['rlActions']->insert($insert_phrase, 'lang_keys');
                }

                $GLOBALS['rlCache']->updateDataFormats();
                $GLOBALS['rlCache']->updateForms();

                if ($action) {
                    $_response->script("printMessage('notice', '{$lang['item_edited']}')");

                    $_response->script("itemsGrid.reload()");
                    $_response->script("$('#edit_item').slideUp('normal')");
                } else {
                    trigger_error("Can't edit data_format item, MySQL problems.", E_USER_WARNING);
                    $GLOBALS['rlDebug']->logger("Can't edit data_format item, MySQL problems.");
                }
            }
        }

        $_response->script("$('input[name=item_edit]').val('{$lang['edit']}')");

        return $_response;
    }

    /**
     * delete format item
     *
     * @package ajax
     *
     * @param mixed $data - data
     *
     **/
    public function ajaxDeleteItem($data)
    {
        global $_response, $lang, $key;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        list($key, $format) = explode(',', $data, 2);

        if (!$key) {
            return $_response;
        }

        $key = $GLOBALS['rlValid']->xSql(strtolower(trim($key)));

        /* delete item */
        $this->query("DELETE FROM `{db_prefix}data_formats` WHERE `Key` = '{$key}' LIMIT 1");

        /* delete phrases */
        $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'data_formats+name+{$key}'");

        $GLOBALS['rlCache']->updateDataFormats();
        $GLOBALS['rlCache']->updateForms();

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteItem');

        $_response->script("printMessage('notice', '{$lang['item_deleted']}')");
        $_response->script("$('#loading').fadeOut('normal');");

        $_response->script("itemsGrid.reload()");
        $_response->script("$('#edit_item').slideUp('normal');");
        $_response->script("$('#new_item').slideUp('normal');");

        return $_response;
    }

    /**
     * delete data format
     *
     * @package ajax
     *
     * @param string $key - format key
     *
     **/
    public function ajaxDeleteFormat($key = false)
    {
        global $_response, $lang, $config, $id;

        // check admin session expire
        if ($this->checkSessionExpire() === false) {
            $redirect_url = RL_URL_HOME . ADMIN . "/index.php";
            $redirect_url .= empty($_SERVER['QUERY_STRING']) ? '?session_expired' : '?' . $_SERVER['QUERY_STRING'] . '&session_expired';
            $_response->redirect($redirect_url);
        }

        if (!$key) {
            return $_response;
        }

        $GLOBALS['rlValid']->sql($key);
        $this->setTable('data_formats');

        $lang_keys[] = array(
            'Key' => 'data_formats+name+' . $key,
        );

        // get format ID
        $id = (int) $this->getOne('ID', "`Key` = '{$key}'", 'data_formats');

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteFormatPreDelete');

        $GLOBALS['rlActions']->delete(array('Key' => $key), array('data_formats', 'lang_keys'), null, 1, $key, $lang_keys);
        $del_mode = $GLOBALS['rlActions']->action;

        if (!$config['trash']) {
            // get child keys
            $child_keys = $this->fetch(array('Key'), array('Parent_ID' => $id));

            // remove items lang keys
            foreach ($child_keys as $cKey => $cVal) {
                $this->query("DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = 'data_formats+name+{$cVal['Key']}'");
            }

            // remove child items
            $this->query("DELETE FROM `{db_prefix}data_formats` WHERE `Parent_ID` = '{$id}'");
        }

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxDeleteFormat');

        $GLOBALS['rlCache']->updateDataFormats();
        $GLOBALS['rlCache']->updateForms();

        $_response->script("dataFormatGrid.reload();");
        $_response->script("printMessage('notice', '{$lang['item_' . $del_mode]}')");

        return $_response;
    }

    /**
     * mass actions with data format items
     *
     * @package xAjax
     *
     * @param string $ids     - item ids
     * @param string $action  - mass action
     *
     **/
    public function ajaxDfItemsMassActions($ids = false, $action = false)
    {
        global $_response, $lang;

        if (!$ids || !$action) {
            return $_response;
        }

        $ids = explode('|', $ids);

        $GLOBALS['rlHook']->load('apPhpFormatsAjaxMassActions', $action, $ids);

        if ($action == 'activate' || $action == 'approve') {
            $status = $action == 'activate' ? 'active' : 'approval';
            $sql = "UPDATE `{db_prefix}data_formats` SET `Status` = '{$status}' WHERE FIND_IN_SET(`ID`, '" . implode(',', $ids) . "') ";
            $this->query($sql);
        } elseif ($action == 'delete') {
            $sql = "DELETE `T1`, `T2` FROM `{db_prefix}data_formats` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON `T2`.`Key` = CONCAT('data_formats+name+', `T1`.`Key`) ";
            $sql .= "WHERE FIND_IN_SET(`T1`.`ID`, '" . implode(',', $ids) . "') ";
            $this->query($sql);
        }

        $GLOBALS['rlCache']->updateDataFormats();
        $GLOBALS['rlCache']->updateForms();

        $_response->script("printMessage('notice', '{$lang['mass_action_completed']}')");

        return $_response;
    }
}
