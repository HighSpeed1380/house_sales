<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLMESSAGE.CLASS.PHP
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

class rlMessage
{
    /**
     * @var calculate news
     **/
    public $calc_news;

    /**
     * send message
     *
     * @package xAjax
     *
     * @param string $res_id  - recipient account id
     * @param string $message - message text
     * @param bool   $admin   - admin conversation mode
     *
     **/
    public function ajaxSendMessage($res_id = false, $message = false, $admin = false)
    {
        global $_response, $config, $account_info, $rlSmarty, $rlMail, $reefless, $rlDb, $rlAccount, $rlHook;

        if (!$config['messages_module']) {
            return $_response;
        }

        if (defined('IS_LOGIN') || defined('REALM')) {
            $message = trim($message);
            $res_id = (int) $res_id;

            if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
                mb_internal_encoding('UTF-8');
                $message = mb_substr($message, 0, $config['messages_length']);
            } else {
                $message = substr($message, 0, $config['messages_length']);
            }

            if (empty($message) || $message == ' ') {
                return $_response;
            }

            if ($rlAccount->isAdmin()) {
                $insert = array(
                    'To'      => $res_id,
                    'Message' => $message,
                    'Date'    => 'NOW()',
                );
            } else {
                $insert = array(
                    'From'    => $account_info['ID'],
                    'To'      => !$admin ? $res_id : 0,
                    'Message' => $message,
                    'Date'    => 'NOW()',
                );
            }

            if ($admin) {
                $insert['Admin'] = $rlAccount->isAdmin() ? (int) $account_info['ID'] : $res_id;
            }

            $rlDb->insertOne($insert, 'messages');

            $rlHook->load('rlMessagesAjaxSendMessage', $res_id, $message, $admin); // from v4.1.0

            if ($config['messages_notification_in_chat']) {
                if ($admin && !$rlAccount->isAdmin()) {
                    $contact = $rlDb->fetch(array('ID', 'Name', 'Email'), array('ID' => $res_id), null, 1, 'admins', 'row');
                    $link = RL_URL_HOME . ADMIN . '/index.php?controller=messages&id=' . $account_info['ID'];

                    $recepient_email = $contact['Email'];
                    $owner_name = $contact['Name'];
                } else {
                    $recipient = $rlAccount->getProfile($res_id);

                    $link = $reefless->getPageUrl('my_messages', null, $recipient['Lang']);
                    $link .= ($config['mod_rewrite'] ? '?' : '&') . ('id=' . $account_info['ID']);

                    $recepient_email = $recipient['Mail'];
                    $owner_name = $recipient['Full_name'];
                }
                $link = '<a href="' . $link . '">' . $link . '</a>';

                $reefless->loadClass('Mail');
                $mail_tpl = $rlMail->getEmailTemplate(
                    'contact_owner_user',
                    $recipient && $recipient['Lang'] ? $recipient['Lang'] : $config['lang']
                );

                $mail_tpl['subject'] = str_replace('{visitor_name}', $account_info['Full_name'], $mail_tpl['subject']);
                $mail_tpl['body'] = str_replace(
                    array('{owner_name}', '{message}', '{reply_link}', '{visitor_name}'),
                    array($owner_name, $message, $link, $account_info['Full_name']),
                    $mail_tpl['body']
                );

                // remove unnecessary link of listing in email
                $mail_tpl['body'] = preg_replace("/\{if listing_page\}(.*?)\{\/if\}/smi", '', $mail_tpl['body']);

                $rlMail->send($mail_tpl, $recepient_email, null, $account_info['Mail'], $account_info['Full_name']);
            }

            $messages = $this->getMessages($res_id, false, false, $admin);
            $rlSmarty->assign_by_ref('messages', $messages);

            $tpl = 'blocks' . RL_DS . 'messages_area.tpl';
            $_response->assign('messages_area', 'innerHTML', $rlSmarty->fetch($tpl, null, null, false));

            $rlHook->load('rlMessagesAjaxAfterMessageSent', $res_id, $message, $admin); // from v4.3.0

            $_response->script("
                $('#message_text').val('');
                $('#messages_cont').mCustomScrollbar('scrollTo', 'bottom');
            ");
            $_response->call("messageRemoveHandler()");
        } else {
            $_response->script("printMessage('error', '{$GLOBALS['lang']['notice_operation_inhibit']}')");
        }

        return $_response;
    }

    /**
     * Contact visitor and mark his messages by hash
     *
     * @since 4.9.0
     *
     * @param  string $message - Message for visitor 
     * @param  string $email   - Visitor email address
     * @param  string $name    - Visitor name
     * @return bool            - Success status
     */
    public function contactVisitor($message, $email, $name = '')
    {
        global $account_info, $lang, $reefless;

        if (!$GLOBALS['rlAccount']->isLogin()) {
            return false;
        }

        $reefless->loadClass('Mail');

        $mail_tpl     = $GLOBALS['rlMail']->getEmailTemplate('contact_owner_user');
        $owner_name   = $account_info['Full_name'];
        $visitor_name = $name ?: $lang['website_visitor'];
        $messages     = $this->getMessages('-1', false, $email);
        $contact      = array_pop($messages);
        $contact['Visitor_hash'] = $messages[0]['Visitor_hash'];
        $hash         = $contact['Visitor_hash'] ?: $reefless->generateHash();
        $reply_url    = $reefless->getPageUrl('login', false, false, 'message-hash=' . $hash);
        $reply_link   = sprintf('<a href="%s">%s</a>', $reply_url, $reply_url);
        $listing_link = sprintf('<a href="%s">%s</a>', $contact['listing_url'], $contact['listing_title']);

        $find         = array('{owner_name}', '{visitor_name}', '{message}', '{listing_link}', '{contact_phone}', '{reply_link}');
        $replace      = array($visitor_name, $owner_name, $message, $listing_link, '', $reply_link);

        $mail_tpl['subject'] = str_replace('{visitor_name}', $owner_name, $mail_tpl['subject']);
        $mail_tpl['body']    = str_replace($find, $replace, $mail_tpl['body']);
        $mail_tpl['body']    = preg_replace("/\{if listing_page\}(.*?)\{\/if\}/smi", '$1', $mail_tpl['body']);

        $GLOBALS['rlMail']->send($mail_tpl, $email, null, $account_info['Mail'], $owner_name);

        // Save hash
        $update = array(
            'fields' => ['Visitor_hash' => $hash],
            'where' => ['Visitor_mail' => $email],
        );
        $GLOBALS['rlDb']->updateOne($update, 'messages');

        // Save message
        $insert = array(
            'From' => $contact['To'],
            'To' => '-1',
            'Admin' => '0',
            'Message' => $message,
            'Date' => 'NOW()',
            'Visitor_mail' => $email,
            'Visitor_phone' => $contact['Visitor_phone'],
            'Visitor_name' => $contact['Visitor_name'],
            'Visitor_hash' => $hash,
            'Listing_ID' => $contact['Listing_ID']
        );
        $GLOBALS['rlDb']->insertOne($insert, 'messages');

        return true;
    }

    /**
     * refresh messages area
     *
     * @package xAjax
     *
     * @param string $res_id  - recipient account id
     * @param string $checked - checked messages ids
     * @param bool   $admin   - admin conversation mode
     **/
    public function ajaxRefreshMessagesArea($res_id = false, $checked = false, $visitor_mail = false, $admin = false)
    {
        global $_response, $pages, $config;

        $messages = $this->getMessages((int) $res_id, false, $visitor_mail, $admin);

        if (empty($messages)) {
            if (defined('REALM') && REALM == 'admin') {
                $url = RL_URL_HOME . ADMIN . '/index.php?controller=messages';
            } else {
                $url = SEO_BASE;
                $url .= $config['mod_rewrite'] ? $pages['my_messages'] . '.html' : '?page=' . $pages['my_messages'];
            }
            $_response->redirect($url);

            return $_response;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('messages', $messages);

        if ($checked) {
            $GLOBALS['rlSmarty']->assign('checked_ids', explode(',', $checked));
        }

        $tpl = 'blocks' . RL_DS . 'messages_area.tpl';
        $_response->assign('messages_area', 'innerHTML', $GLOBALS['rlSmarty']->fetch($tpl, null, null, false));
        $_response->script("checkboxControl();");

        return $_response;
    }

    /**
     * Get account contacts
     *
     * @return array - List of contacts
     */
    public function getContacts()
    {
        global $account_info, $lang, $config;

        if (!$account_id = (int) $account_info['ID']) {
            return [];
        }

        $sql = "SELECT DISTINCT `T1`.*, `T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name`, `T2`.`Photo`, ";

        if ($config['thumbnails_x2']) {
            $sql .= "`T2`.`Photo_x2`, ";
        }

        if ($GLOBALS['rlAccount']->isAdmin()) {
            $sql .= "IF(`T1`.`From` <> 0, `T1`.`From`, `T1`.`To`) AS `From`, ";
            $sql .= "IF(`T1`.`From` = 0, 1, 0) AS `Admin_message` ";
        } else {
            $sql .= "IF(`T1`.`From` = '{$account_id}', `T1`.`To`, `T1`.`From`) AS `From`, `T3`.`Name`, ";
            $sql .= "`T4`.`Thumb_width`, `T4`.`Thumb_height` ";
        }

        $sql .= "FROM `{db_prefix}messages` AS `T1` ";

        if ($GLOBALS['rlAccount']->isAdmin()) {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON IF(`T1`.`FROM` <> 0, `T1`.`From`, `T1`.`To`) = `T2`.`ID` ";
        } else {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON IF(`T1`.`From` = {$account_id}, `T1`.`To`, `T1`.`From`) = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}admins` AS `T3` ON `T1`.`Admin` = `T3`.`ID` ";

            $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T4` ON `T2`.`Type` = `T4`.`Key` ";
        }

        $sql .= "WHERE (";

        if ($GLOBALS['rlAccount']->isAdmin()) {
            $sql .= "`T1`.`Admin` = '{$account_id}' ";
        } else {
            $sql .= "`T1`.`To` = '{$account_id}' OR `T1`.`From` = '{$account_id}' ";
        }

        $sql .= ") ";

        if ($GLOBALS['rlAccount']->isAdmin()) {
            $sql .= "AND FIND_IN_SET(IF (`T1`.`From` = 0, 'from', 'to'), `T1`.`Remove`) = 0 ";
        } else {
            $sql .= "AND FIND_IN_SET(IF (`T1`.`From` = '{$account_id}', 'from', 'to'), `T1`.`Remove`) = 0 ";
        }

        $sql .= "GROUP BY `T1`.`ID` ORDER BY IF(`T1`.`Status` = 'new' AND `TO` = {$account_id}, 0, 1) ASC, `ID` DESC ";

        $GLOBALS['rlHook']->load('rlMessagesGetContactsSql', $sql); // from v4.1.0

        $messages = $GLOBALS['rlDb']->getAll($sql);

        $contacts = [];
        foreach ($messages as $key => $value) {
            // list of contacts for admin
            if ($GLOBALS['rlAccount']->isAdmin()) {
                if ($contacts[$value['From']]) {
                    $contacts[$value['From']]['Count'] += $value['Status'] == 'new'
                    && $value['To'] == $account_id && $value['From'] && !$value['Admin_message']
                    ? 1
                    : 0;
                } else {
                    $name = $value['First_name'] || $value['Last_name']
                    ? $value['First_name'] . ' ' . $value['Last_name']
                    : $value['Username'];

                    $contacts[$value['From']] = $value;
                    $contacts[$value['From']]['Full_name'] = $name;
                    $contacts[$value['From']]['Count'] = $value['Status'] == 'new'
                    && $value['Admin'] == $account_id && $value['From'] && !$value['Admin_message']
                    ? 1
                    : 0;
                }
            }
            // list of contacts for user in frontend
            else {
                if ($value['From'] == -1) {
                    $index = 'Visitor_mail'; //group visitors by email
                } else {
                    if ($value['Admin']) {
                        $index = 'Admin';
                    } else {
                        $index = $value['From'] == $account_id ? 'To' : 'From';
                    }
                }

                $value_by_index = $value['Admin'] ? $value[$index] . '_admin' : $value[$index];

                if ($contacts[$value_by_index]) {
                    $contacts[$value_by_index]['Count'] += $value['Status'] == 'new'
                    && $value['To'] == $account_id
                    && ((!$value['Admin'] && ($value['From'] == $value_by_index
                        || $value['Visitor_mail'] == $value_by_index))
                        || ($value['Admin'] && $value['Admin'] == str_replace('_admin', '', $value_by_index)))
                    ? 1
                    : 0;
                } else {
                    if ($value['From'] == -1) {
                        $name = $value['Visitor_name'];
                    } elseif ($value['Admin']) {
                        $value['From'] = $value['Admin'];

                        // get Name of admin account
                        $messages[$key]['Name'] = $messages[$key]['Name'] ?: $GLOBALS['rlDb']->getOne(
                            'Name',
                            "`ID` = '{$value['Admin']}' AND `Status` = 'active'",
                            'admins'
                        );
                        $name = $messages[$key]['Name'] ?: $lang['administrator'];
                    } else {
                        $name = $value['First_name'] || $value['Last_name']
                        ? $value['First_name'] . ' ' . $value['Last_name']
                        : $value['Username'];
                    }

                    $contacts[$value_by_index] = $value;
                    $contacts[$value_by_index]['Full_name'] = $name;
                    $contacts[$value_by_index]['Count'] = $value['Status'] == 'new'
                    && $value['To'] == $account_id
                    ? 1
                    : 0;
                }
            }
        }

        return $contacts;
    }

    /**
     * get contact messages
     *
     * @param int    $user_id      - contact id
     * @param bool   $no_update    - do not set message status as read
     * @param string $visitor_mail - visitor mail
     * @param bool   $admin        - admin conversation mode
     *
     * @return array messages
     **/
    public function getMessages($user_id = false, $no_update = false, $visitor_mail = false, $admin = false)
    {
        global $account_info;

        $user_id = (int) $user_id;
        $account_id = (int) $account_info['ID'];

        if (!$user_id) {
            return false;
        }

        $sql = "SELECT `T1`.*, `T2`.`Username`, `T2`.`First_name`, `T2`.`Last_name`";

        if ($_COOKIE['client_utc_time']) {
            $_COOKIE['client_utc_time'] = str_replace(
                array('plus', 'minus'),
                array('+', '-'),
                $_COOKIE['client_utc_time']
            );

            $server_tz = $GLOBALS['l_timezone'][$GLOBALS['config']['timezone']][0] ?: "SYSTEM";
            $sql .= ", IFNULL(CONVERT_TZ(`T1`.`Date`, '" . $server_tz . "', '";
            $sql .= $_COOKIE['client_utc_time'] . "'), `T1`.`Date`) AS `Date` ";
        }

        $sql .= " FROM `{db_prefix}messages` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T1`.`From` = `T2`.`ID` ";
        $sql .= "WHERE ";

        if (!$GLOBALS['rlAccount']->isAdmin()) {
            $sql .= "(";
            if ($admin) {
                $sql .= "(`T1`.`To` = {$account_id} AND `T1`.`Admin` = {$user_id})";
            } else {
                $sql .= "(`T1`.`To` = {$account_id} AND `T1`.`From` = {$user_id})";
            }

            if ($admin) {
                $sql .= "OR (`T1`.`Admin` = {$user_id} AND `T1`.`From` = {$account_id}) ";
            } else {
                $sql .= "OR (`T1`.`To` = {$user_id} AND `T1`.`From` = {$account_id}) ";
            }

            $sql .= ") ";

            if ($visitor_mail) {
                $sql .= "AND `Visitor_mail` = '{$visitor_mail}' ";
            }
        } else {
            $sql .= "((`T1`.`To` = {$user_id} OR `T1`.`From` = {$user_id}) AND `T1`.`Admin` = {$account_id}) ";
        }

        $GLOBALS['rlHook']->load('rlMessagesGetMessagesSql', $sql); // from v4.1.0

        $sql .= "ORDER BY `T1`.`ID` ASC";

        $messages = $GLOBALS['rlDb']->getAll($sql);

        foreach ($messages as $key => $value) {
            if ($GLOBALS['rlAccount']->isAdmin()) {
                $current = $value['Admin'] == $account_id && $value['From'] == 0 ? 'from' : 'to';
            } else {
                $current = $value['From'] == $account_id ? 'from' : 'to';
            }

            if (in_array($current, explode(',', $value['Remove']))) {
                unset($messages[$key]);
                continue;
            } elseif (!empty($value['Remove']) && !in_array($current, explode(',', $value['Remove']))) {
                $messages[$key]['Hide'] = true;
            }

            // build listing link
            if ($value['Listing_ID']) {
                $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type` FROM `{db_prefix}listings` AS `T1` ";
                $sql .= "JOIN `{db_prefix}categories` AS `T2` ON `T2`.`ID` = `T1`.`Category_ID` ";
                $sql .= "WHERE `T1`.`ID` = {$value['Listing_ID']} LIMIT 1";
                $listing_info = $GLOBALS['rlDb']->getRow($sql);

                $listing_info['listing_title'] = $GLOBALS['rlListings']->getListingTitle(
                    $listing_info['Category_ID'],
                    $listing_info,
                    $listing_info['Listing_type']
                );

                $messages[$key]['listing_url'] = $GLOBALS['reefless']->url('listing', $listing_info);
                $messages[$key]['listing_title'] = $listing_info['listing_title'];
            }
        }

        // set messages as readed
        if (!$no_update) {
            $update['fields'] = array('Status' => 'readed');

            if ($GLOBALS['rlAccount']->isAdmin()) {
                $update['where'] = array('Admin' => $account_id, 'From' => $user_id);
            } else {
                if ($admin) {
                    $update['where'] = array('Admin' => $user_id, 'To' => $account_id);
                } else {
                    $update['where'] = array('From' => $user_id, 'To' => $account_id);
                }
            }

            if ($user_id == -1 && $visitor_mail) {
                $update['where']['Visitor_mail'] = $visitor_mail;
            }

            $GLOBALS['rlDb']->updateOne($update, 'messages');
        }

        return $messages;
    }

    /**
     * contact owner Admin Panel
     *
     * @package xAjax
     *
     * @param string $id      - owner account ID
     * @param string $message - message
     *
     **/
    public function ajaxContactOwnerAP($id = false, $message = false)
    {
        global $_response, $pages, $config, $lang, $rlHook, $sql, $rlListingTypes, $rlSmarty, $seller_info;

        if (!$config['messages_module'] || !$id) {
            return $_response;
        }

        $GLOBALS['reefless']->loadClass('Mail');

        $insert = array(
            'To'      => $id,
            'Admin'   => $_SESSION['sessAdmin']['user_id'],
            'Message' => $message,
            'Date'    => 'NOW()',
        );
        $GLOBALS['rlDb']->insertOne($insert, 'messages');

        /**
         * @since 4.5.2
         */
        $GLOBALS['rlHook']->load('rlMessagesAjaxContactOwnerAP', $insert, $seller_info);

        if ($config['messages_notification']) {
            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('contact_owner_admin', $seller_info['Lang']);

            $reply_link = defined('REALM') && REALM == 'admin' ? RL_URL_HOME : SEO_BASE;
            $reply_link .= $config['mod_rewrite'] ? $pages['my_messages'] . '.html' : '?page=' . $pages['my_messages'];
            $reply_link = '<a href="' . $reply_link . '">' . $reply_link . '</a>';

            $find = array('{owner_name}', '{message}', '{reply_link}');
            $replace = array($seller_info['Full_name'], $message, $reply_link);

            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

            $GLOBALS['rlMail']->send($mail_tpl, $seller_info['Mail'], null, $_SESSION['sessAdmin']['mail'], $_SESSION['sessAdmin']['name']);
        }

        $_response->script("printMessage('notice', '{$lang['notice_message_sent']}')");

        return $_response;
    }

    /**
     * remove messages
     *
     * @package xAjax
     *
     * @param string $ids         - message ids
     * @param string $contact_id  - contact id
     * @param int $admin          - admin conversation mode
     *
     **/
    public function ajaxRemoveMsg($ids = false, $contact_id = false, $admin = false)
    {
        global $_response, $account_info, $rlDb;

        $contact_id = (int) $contact_id;
        $account_id = (int) $account_info['ID'];

        // check message owner
        $ids = explode(',', $ids);

        if (!empty($ids[0])) {
            $GLOBALS['rlHook']->load('rlMessagesAjaxRemoveMsg', $ids, $contact_id); // from v4.1.0

            foreach ($ids as $id) {
                $id = (int) $id;
                $select = array('ID', 'From', 'To', 'Admin', 'Remove', 'Visitor_mail');

                if ($GLOBALS['rlAccount']->isAdmin()) {
                    $where = "WHERE `Admin` = {$account_id} ";
                    $where .= "AND (`To` = {$contact_id} OR `From` = {$contact_id}) AND `ID` = {$id}";
                } else {
                    if ($admin) {
                        $where = "WHERE (`From` = {$account_id} OR `To` = {$account_id}) ";
                        $where .= "AND `Admin` = '{$contact_id}' AND `ID` = {$id}";
                    } else {
                        $where = "WHERE ((`From` = {$account_id} AND `To` = {$contact_id}) ";
                        $where .= "OR (`From` = {$contact_id} AND `To` = {$account_id})) AND `ID` = {$id}";
                    }
                }

                if ($res = $rlDb->fetch($select, null, $where, 1, 'messages', 'row')) {
                    if ($res['From'] == -1 || $res['Remove']) {
                        //in visitor mode delete completely
                        $rlDb->query("DELETE FROM `{db_prefix}messages` WHERE `ID` = {$id}");
                    } else {
                        if ($GLOBALS['rlAccount']->isAdmin()) {
                            $request = $account_id == $res['Admin'] && $res['From'] == 0 ? 'from' : 'to';
                        } else {
                            if ($admin) {
                                $request = $account_id == $res['From'] && $res['To'] == 0 ? 'from' : 'to';
                            } else {
                                $request = $account_id == $res['From'] ? 'from' : 'to';
                            }
                        }

                        $update[] = array(
                            'fields' => array('Remove' => $request),
                            'where'  => array('ID' => $id),
                        );
                    }
                }
            }

            if (!empty($update)) {
                $GLOBALS['rlDb']->update($update, 'messages');
            }

            $_response->script("xajax_refreshMessagesArea('{$contact_id}', false, '{$res['Visitor_mail']}', '{$admin}')");
            $_response->call("messageRemoveHandler()");
        }

        return $_response;
    }

    /**
     * remove contacts
     *
     * @package xAjax
     *
     * @param string $ids - contacts ids
     *
     **/
    public function ajaxRemoveContacts($ids = false)
    {
        global $_response, $pages, $account_info, $lang, $config;

        // check message owner
        $ids = explode(',', $ids);

        if (isset($ids[0])) {
            $GLOBALS['rlHook']->load('rlMessagesAjaxRemoveContacts', $ids); // from v4.1.0

            // get contacts messages
            foreach ($ids as $contact_id) {
                // visitor mode
                $contact_visitor_email = false;

                // admin conversation mode
                $admin = false;

                if ($GLOBALS['rlValid']->isEmail($contact_id)) {
                    $contact_visitor_email = $contact_id;
                    $contact_id = -1;
                }
                // admin conversation mode
                elseif (is_numeric(strpos($contact_id, '_admin'))) {
                    $contact_id = (int) str_replace('_admin', '', $contact_id);
                    $admin = true;
                }

                $messages = $this->getMessages($contact_id, true, $contact_visitor_email, $admin);

                foreach ($messages as $key => $value) {
                    if ($contact_visitor_email || $value['Remove']) {
                        $GLOBALS['rlDb']->query("DELETE FROM `{db_prefix}messages` WHERE `ID` = '{$value['ID']}' LIMIT 1");
                        $deleted = true;
                    } else {
                        if (in_array($account_info['ID'], array($value['From'], $value['To']))
                            || ($GLOBALS['rlAccount']->isAdmin() && $account_info['ID'] == $value['Admin'])
                        ) {
                            if ($GLOBALS['rlAccount']->isAdmin()) {
                                $request = $account_info['ID'] == $value['Admin'] && $value['From'] == 0 ? 'from' : 'to';
                            } else {
                                $request = $account_info['ID'] == $value['From'] ? 'from' : 'to';
                            }

                            $update[] = array(
                                'fields' => array(
                                    'Remove' => $request,
                                    'Status' => 'readed',
                                ),
                                'where'  => array('ID' => $value['ID']),
                            );
                        }
                    }
                }
            }

            if ($update || $deleted) {
                if ($update) {
                    $GLOBALS['rlDb']->update($update, 'messages');
                }

                $_response->script("setTimeout(function(){ printMessage('notice', '{$lang['notice_items_deleted']}') }, 500)");

                if ($GLOBALS['rlAccount']->isAdmin()) {
                    $_response->script("
                        $('#item_" . implode(",#item_", $ids) . "').fadeOut('slow', function(){
                            $(this).remove();
                            $('#content table.table input#check_all').attr('checked', false);

                            if ($('#content table.table tr[id*=\"item_\"]').length <= 0) {
                                $('#content table.table').after('<div>{$lang['no_messages']}</div>');
                                $('#content table.table').remove();
                                $('div.mass_actions_light').remove();
                            }
                        });
                    ");
                } else {
                    $ids = str_replace(array('.', '@'), '', $ids);

                    $_response->script("
                        $('#item_" . implode(",#item_", $ids) . "').fadeOut('slow', function(){
                            $(this).remove();

                            if ($('#controller_area table.list input.del_mess').length <= 0) {
                                var info_content = '<div class=\"info\">{$lang['no_messages']}</div>';
                                $('#controller_area table.list').after(info_content);
                                $('#controller_area table.list').remove();
                                $('.mass-actions').remove();
                            }
                        });
                    ");
                }
            }
        }

        return $_response;
    }

    /**
     * Contact owner
     * @param  string $name       Name
     * @param  string $email      Email
     * @param  string $phone      Phone
     * @param  string $message    Message text
     * @param  string $code       Security code
     * @param  int    $listing_id Owner listing ID
     * @param  int    $box_index  Index of the similar kind of boxes on page
     * @param  int    $account_id Seller account id
     * @return array              Status and message
     */
    public function contactOwner($name = false, $email = false, $phone = false, $message = false, $code = false, $listing_id = false, $box_index = false, $account_id = false)
    {
        global $config, $lang, $rlMembershipPlan, $rlAccount, $rlListingTypes, $account_info, $pages, $attach_file, $rlDb, $reefless;

        if (!$config['messages_module']) {
            $res['status'] = 'failure';
            $res['message_text'] = $lang['send_message_not_available'];

            return $res;
        }

        if ($config['membership_module'] && !$rlMembershipPlan->is_send_message_allowed) {
            $res['status'] = 'failure';
            $res['message_text'] = $lang['send_message_not_available'];

            return $res;
        }

        $errors = array();
        $error_fields = array();
        $name = trim($name);
        $box_postfix = $box_index ? '_' . $box_index : '';

        if (function_exists('mb_substr') && function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
            $message = mb_substr($message, 0, $config['messages_length']);
        } else {
            $message = substr($message, 0, $config['messages_length']);
        }

        if (!$rlAccount->isLogin()) {
            if (empty($name)) {
                $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['name'] . '"</span>', $lang['notice_field_empty']);
                $error_fields[] = '#contact_name' . $box_postfix;
            }

            if (empty($email)) {
                $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['mail'] . '"</span>', $lang['notice_field_empty']);
                $error_fields[] = '#contact_email' . $box_postfix;
            }

            if (!empty($email) && !$GLOBALS['rlValid']->isEmail($email)) {
                $error_fields[] = '#contact_email' . $box_postfix;
                $errors[] = $lang['notice_bad_email'];
            }

            if ($config['security_img_contact_seller'] && ($code != $_SESSION['ses_security_code_contact_code' . $box_postfix] || !$_SESSION['ses_security_code_contact_code' . $box_postfix])) {
                $errors[] = $lang['security_code_incorrect'];
                $error_fields[] = '#contact_code' . $box_postfix . '_security_code';
            }
        }

        /**
         * @since 4.7.0 - Added arguments: $errors, $error_fields
         * @since 4.1.0
         */
        $GLOBALS['rlHook']->load(
            'rlMessagesAjaxContactOwnerValidate',
            $name,
            $email,
            $phone,
            $message,
            $listing_id,
            $errors,
            $error_fields
        );

        if (empty($message)) {
            $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['message'] . '"</span>', $lang['notice_field_empty']);
            $error_fields[] = '#contact_owner_message' . $box_postfix;
        }

        if ($errors) {
            $res['status'] = 'failure';
            $res['message_text'] = '<ul>';
            foreach ($errors as $error) {
                $res['message_text'] .= '<li>' . $error . '</li>';
            }
            $res['message_text'] .= '</ul>';
            $res['error_fields'] = implode(",", $error_fields);

            return $res;
        }

        $reefless->loadClass('Mail');
        $reefless->loadClass('Listings');

        if ($listing_id) {
            /* get listing/owner details */
            $sql = "SELECT `T1`.*, `T2`.`Type` AS `Listing_type_key`, `T2`.`Path` AS `Category_path`, `T3`.`Mail` AS `Owner_email`, `T3`.`Lang`, ";
            $sql .= "`T3`.`Username` AS `Owner_username`, `T3`.`First_name` AS `Owner_first_name`, `T3`.`Last_name` AS `Owner_last_name` ";
            $sql .= "FROM `{db_prefix}listings` AS `T1` ";
            $sql .= "LEFT JOIN `{db_prefix}categories` AS `T2` ON `T1`.`Category_ID` = `T2`.`ID` ";
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T3` ON `T1`.`Account_ID` = `T3`.`ID` ";
            $sql .= "WHERE `T1`.`ID` = {$listing_id} AND `T1`.`Status` = 'active' AND `T2`.`Status` = 'active' AND `T3`.`Status` = 'active'";

            $GLOBALS['rlHook']->load('contactOwnerInfoSql');

            $info = $rlDb->getRow($sql);

            $owner_name = $info['Owner_first_name'] || $info['Owner_last_name'] ? $info['Owner_first_name'] . ' ' . $info['Owner_last_name'] : $info['Owner_username'];

            $listing_type = $rlListingTypes->types[$info['Listing_type_key']];
            $listing_title = $GLOBALS['rlListings']->getListingTitle($info['Category_ID'], $info, $info['Listing_type_key']);

            $link = $reefless->url('listing', $info, $info['Lang']);

            $link = '<a href="' . $link . '">' . $listing_title . '</a>';
        } elseif ($account_id) {
            $account = $rlAccount->getProfile(intval($account_id));

            $info['Account_ID'] = $account['ID'];
            $info['Owner_email'] = $account['Mail'];
            $info['Owner_username'] = $account['Username'];
            $info['Owner_first_name'] = $account['First_name'];
            $info['Owner_last_name'] = $account['Last_name'];
            $info['Lang'] = $account['Lang'];
            $owner_name = $info['Owner_first_name'] || $info['Owner_last_name']
            ? trim($info['Owner_first_name'] . ' ' . $info['Owner_last_name'])
            : $info['Owner_username'];
            $link = $lang['not_available'];
        } else {
            $link = $lang['not_available'];
        }

        $GLOBALS['rlHook']->load('rlMessagesAjaxContactOwnerSend', $name, $email, $phone, $message, $listing_id); // from v4.1.0

        // logged in user mode
        if ($rlAccount->isLogin() || $config['messages_save_visitor_message']) {
            $insert = array(
                'From'          => $account_info['ID'] ? $account_info['ID'] : -1,
                'To'            => $info['Account_ID'],
                'Message'       => $message,
                'Date'          => 'NOW()',
                'Visitor_mail'  => $email,
                'Visitor_phone' => $phone,
                'Visitor_name'  => $name,
                'Listing_ID'    => $listing_id,
            );

            // Set hash to new message if exists
            if (!$account_info['ID']) {
                $insert['Visitor_hash'] = $rlDb->getOne('Visitor_hash', "`Visitor_mail` = '{$email}'", 'messages');
            }

            $rlDb->insertOne($insert, 'messages');

            // send notification only when user logged in
            if ($config['messages_notification'] && $rlAccount->isLogin()) {
                $message = preg_replace("/(\\n|\\t|\\r)/", '<br />', $message);
                $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('contact_owner_user', $info['Lang']);

                $reply_link = $reefless->getPageUrl('my_messages', null, $info['Lang']);
                $reply_link .= ($config['mod_rewrite'] ? '?' : '&') . ('id=' . $insert['From']);
                $reply_link = '<a href="' . $reply_link . '">' . $reply_link . '</a>';

                $find = array('{owner_name}', '{listing_link}', '{message}', '{reply_link}', '{visitor_name}');
                $replace = array(trim($owner_name), $link, $message, $reply_link, $account_info['Full_name']);

                $mail_tpl['subject'] = str_replace('{visitor_name}', $account_info['Full_name'], $mail_tpl['subject']);
                $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

                // display the link of listing in email what has been sent from listing details page only
                $mail_tpl['body'] = preg_replace(
                    "/\{if listing_page\}(.*?)\{\/if\}/smi",
                    $listing_id && $link ? '$1' : '',
                    $mail_tpl['body']
                );

                $GLOBALS['rlMail']->send($mail_tpl, $info['Owner_email'], $attach_file, $account_info['Mail'], $account_info['Full_name']);
            }
        }

        if (!$rlAccount->isLogin()) {
            $message = preg_replace("/(\\n|\\t|\\r)/", '<br />', $message);
            $phone_line = $lang['contact_phone'] . ': ';
            $phone_line = $phone ? $phone : $lang['not_available'];

            $mail_tpl = $GLOBALS['rlMail']->getEmailTemplate('contact_owner', $info['Lang']);

            $find = array('{owner_name}', '{visitor_name}', '{message}', '{listing_link}', '{contact_phone}');
            $replace = array(trim($owner_name), $name, $message, $link, $phone_line);
            $mail_tpl['subject'] = str_replace('{visitor_name}', $name, $mail_tpl['subject']);
            $mail_tpl['body'] = str_replace($find, $replace, $mail_tpl['body']);

            // display the link of listing in email what has been sent from listing details page only
            $mail_tpl['body'] = preg_replace(
                "/\{if listing_page\}(.*?)\{\/if\}/smi",
                $listing_id && $link ? '$1' : '',
                $mail_tpl['body']
            );

            // send e-mail for friend
            $GLOBALS['rlMail']->send($mail_tpl, $info['Owner_email'], $attach_file, $email, $name);
        }

        $GLOBALS['rlHook']->load('rlMessagesAjaxContactOwnerAfterSend', $name, $email, $phone, $message, $listing_id); // from v4.5.0

        $res['status'] = 'ok';
        $res['message_text'] = $lang['notice_message_sent'];

        return $res;
    }
}
