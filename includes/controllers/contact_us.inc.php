<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CONTACT_US.INC.PHP
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

if ($_POST['action'] == 'contact_us') {
    $errors = array();

    /* check required fields */
    $your_name = $_POST['your_name'];
    $your_email = $_POST['your_email'];
    $message = nl2br($_POST['message']);
    $security_code = $_POST['security_code'];

    if (empty($your_name)) {
        $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['your_name'] . '"</span>', $lang['notice_field_empty']);
        $error_fields .= 'your_name';
    }

    if (empty($your_email)) {
        $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['your_email'] . '"</span>', $lang['notice_field_empty']);
        $error_fields .= ',your_email';
    } elseif (!$rlValid->isEmail($your_email)) {
        $errors[] = $lang['notice_bad_email'];
        $error_fields .= ',your_email';
    }

    if (empty($message)) {
        $errors[] = str_replace('{field}', '<span class="field_error">"' . $lang['message'] . '"</span>', $lang['notice_field_empty']);
        $error_fields .= ',message';
    }

    if ($config['security_img_contact_us'] && ($security_code != $_SESSION['ses_security_code'] || !$security_code)) {
        $errors[] = $lang['security_code_incorrect'];
        $error_fields .= ',security_code';
    }

    $rlHook->load('contactsCheckData');

    if (!empty($errors)) {
        $rlSmarty->assign_by_ref('errors', $errors);
    } else {
        // write request to DB
        $insert = array(
            'Name'    => $your_name,
            'Email'   => $your_email,
            'Message' => $message,
            'Date'    => 'NOW()',
        );

        /**
         * @since 4.7.1 - Added $insert parameter
         */
        $rlHook->load('contactsInsert', $insert);

        $rlDb->insertOne($insert, 'contacts', array('Message'));

        /* send e-mail for administrator */
        $reefless->loadClass('Mail');

        $mail_tpl = $rlMail->getEmailTemplate('contact_us', $config['lang']);
        $mail_tpl['body'] = str_replace(array('{name}', '{message}'), array($your_name, $message), $mail_tpl['body']);
        $mail_tpl['subject'] = str_replace('{name}', $your_name, $mail_tpl['subject']);

        $rlMail->send($mail_tpl, $config['site_main_email'], null, $your_email, $your_name);

        $reefless->loadClass('Notice');

        $aUrl = array("sending" => "complete");
        $rlNotice->saveNotice($lang['notice_message_sent']);
        $reefless->redirect($aUrl);
    }
}
