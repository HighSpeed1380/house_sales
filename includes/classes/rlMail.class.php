<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLMAIL.CLASS.PHP
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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class rlMail extends reefless
{
    /**
     * @var PHPMailer class object
     */
    public $phpMailer;

    /**
     * @var smtpDebug
     */
    public $smtpDebug = '';

    /**
     * Replace account password to simple text or send non-secure password in email
     *
     * @since 4.7.2
     * @var   boolean
     */
    private $sendAccountPassword = false;

    /**
     * List of email templates where password must be send in original
     *
     * @since 4.7.2
     * @var   array
     */
    private $mailsTemplatesWithPassword = ['quick_account_created'];

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->phpMailer = new PHPMailer;
        $this->phpMailer->getSMTPInstance()->Timeout   = 15;
        $this->phpMailer->getSMTPInstance()->Timelimit = 5;
    }

    /**
     * Sending test email & checking SMTP configuration
     *
     * @return string - Status of sending email
     */
    public function testSMTP()
    {
        $this->smtpDebug = true;

        ob_start();

        $this->send(
            $this->getEmailTemplate('smtp_configuration_success'),
            $GLOBALS['config']['notifications_email']
        );

        return ob_get_clean();
    }

    /**
     * Send mail
     *
     * @param array $mail_tpl    - Subject and body of message
     * @param array $to          - Recipient address
     * @param array $attach_file - Local filesystem path of attached file
     * @param array $from_mail   - From mail
     * @param array $from_name   - From address
     *
     * @return bool
     */
    public function send($mail_tpl, $to, $attach_file = false, $from_mail = false, $from_name = false)
    {
        global $config, $lang;

        $lang['pages+title+home'] = preg_replace('/\{if location\}(.*?)\{\/if\}/smi', '', $lang['pages+title+home']);

        if (!$mail_tpl['body']) {
            return false;
        }

        if ($GLOBALS['config']['mail_method'] == 'smtp') {
            if ($this->smtpDebug) {
                $this->phpMailer->SMTPDebug = 3;
            }

            $this->phpMailer->isSMTP();

            preg_match('#(https?://)?([^:]+):?(\d+)?#smi', $config['smtp_server'], $smtp_server);

            $host = $smtp_server[2];
            $this->phpMailer->Host = $smtp_server[2];
            $this->phpMailer->Port = $smtp_server[3];

            if (in_array($config['smtp_method'], array('tls', 'ssl'))) {
                $this->phpMailer->SMTPSecure = $config['smtp_method'];
            }

            $this->phpMailer->Username = $config['smtp_username'];
            $this->phpMailer->Password = $config['smtp_password'];

            if (empty($config['smtp_username']) && empty($config['smtp_password'])) {
                $this->phpMailer->SMTPAuth = false;
            } else {
                $this->phpMailer->SMTPAuth = true;
            }
        }

        $subject  = $mail_tpl['subject'];
        $body     = $mail_tpl['body'];
        $template = $mail_tpl;

        /**
         * @since 4.7.0 - Added $to, $template parameters
         * @since 4.4
         */
        $GLOBALS['rlHook']->load("phpMailSend", $subject, $body, $attach_file, $from_mail, $from_name, $to, $template);

        if (!$to) {
            return false;
        }

        if ($mail_tpl['Type'] == 'html' && $mail_tpl['html_source']) {
            $body = str_replace('{content}', $body, $mail_tpl['html_source']);
        }

        $this->phpMailer->From = $config['site_main_email'];
        $this->phpMailer->FromName = $from_name ? $from_name : $config['owner_name'];

        if ($from_mail) {
            $this->phpMailer->AddReplyTo($from_mail, $from_name);

            // add real sender of email
            $body = str_replace('{from_mail}', '<a href="mailto:' . $from_mail . '">' . $from_mail . '</a>', $body);
        }

        $this->phpMailer->CharSet = 'UTF-8';
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $body;
        $this->phpMailer->AltBody = "To view the message, please use an HTML compatible email viewer!";
        $this->phpMailer->isHTML(true);
        $this->phpMailer->AddAddress($to);

        if ($attach_file) {
            $this->phpMailer->AddAttachment($attach_file);
        }

        if (!$this->phpMailer->Send()) {
            $sending_email = false;

            trigger_error($this->phpMailer->ErrorInfo, E_USER_WARNING);
            $GLOBALS['rlDebug']->logger($this->phpMailer->ErrorInfo);
        } else {
            $sending_email = true;
        }

        $this->phpMailer->ClearAddresses();
        $this->phpMailer->ClearAttachments();

        return $sending_email;
    }

    /**
     * Get email template by template key
     *
     * @param string $key  - Email template key
     * @param string $code - Language key
     *
     * @return array - Email template subject and body
     */
    public function getEmailTemplate($key, $code = false)
    {
        global $config, $rlDb;

        $lang_code = $code ?: RL_LANG_CODE;

        $mail_tpl = $this->fetch(
            array('Key', 'Type'),
            array('Key' => $key, 'Status' => 'active'),
            null,
            null,
            'email_templates',
            'row'
        );

        $rlDb->outputRowsMap = ['Key', 'Value'];
        $phrases = $rlDb->fetch(
            ['Key', 'Value'],
            null,
            "WHERE `Key` LIKE 'email_templates+%+{$key}' AND `Module` = 'email_tpl' AND `Code` = '{$lang_code}'",
            null,
            'lang_keys'
        );

        $mail_tpl['subject'] = $phrases['email_templates+subject+' . $key];
        $mail_tpl['body']    = $phrases['email_templates+body+' . $key];

        if ($code && $code != RL_LANG_CODE) {
            $mail_tpl['Diff_preff_lang'] = $code;
        }

        if ($mail_tpl['Type'] == 'html') {
            $template = defined('TPL_CORE') && TPL_CORE === true ? 'template_core' : $config['template'];
            $tpl_base = RL_ROOT . 'templates' . RL_DS . $template . RL_DS;
            $mail_tpl['html_source'] = file_get_contents($tpl_base . 'tpl' . RL_DS . 'html_email_source.html', true);
        }

        $this->sendAccountPassword = in_array($key, $this->mailsTemplatesWithPassword)
        || (bool) $config['sending_account_password'];

        $mail_tpl = $this->replaceVariables($mail_tpl);

        return $mail_tpl;
    }

    /**
     * Replace email template variables
     *
     * @param array $mail_tpl - Email template
     *
     * @return array - Replaces email template subject and body
     */
    public function replaceVariables($mail_tpl)
    {
        global $account_info, $lang, $config;

        if ($mail_tpl['Diff_preff_lang']) {
            $sql = "SELECT `Key`, `Value`  FROM `{db_prefix}lang_keys` ";
            $sql .= "WHERE (`Key` = 'email_footer' OR `Key` = 'email_site_name' OR `Key` = 'pages+title+home') ";
            $sql .= "AND `Code` = '{$mail_tpl['Diff_preff_lang']}'";
            $mail_tpl['Alt_lang'] = $GLOBALS['rlDb']->getAll($sql, array("Key", "Value"));

            $site_name = $mail_tpl['Alt_lang']['email_site_name'] ?: $mail_tpl['Alt_lang']['pages+title+home'];
        }

        if (!$site_name) {
            $site_name = $lang['email_site_name'] ?: $lang['pages+title+home'];
        }

        $tpl_vars = [
            '{site_name}'  => $site_name,
            '{site_url}'   => '<a href="' . RL_URL_HOME . '">' . RL_URL_HOME . '</a>',
            '{site_email}' => '<a href="mailto:' . $config['site_main_email'] . '">' . $config['site_main_email'] . '</a>',
        ];

        if (!$this->sendAccountPassword) {
            $tpl_vars['{password}'] = $lang['text_for_password'];
        }

        if (!empty($account_info['Full_name']) && !defined('REALM')) {
            $tpl_vars['{name}'] = $account_info['Full_name'];
        }

        if ($account_info['Username']) {
            $tpl_vars['{username}'] = $account_info['Username'];
        }

        if ($mail_tpl['Type'] == 'html' && $mail_tpl['html_source']) {
            $find = array(
                '{tpl_base}',
                '{site_name}',
                '{footer}',
                '{site_url}',
                '{rtl}',
            );
            $replace = [
                RL_URL_HOME . 'templates/' . $config['template'] . '/',
                $site_name,
                $mail_tpl['Alt_lang']['email_footer'] ?: $lang['email_footer'],
                RL_URL_HOME,
                RL_LANG_DIR == 'rtl' ? 'text-align:right; direction:rtl;' : '',
            ];

            $mail_tpl['html_source'] = str_replace($find, $replace, $mail_tpl['html_source']);
        }

        $mail_tpl['body'] = str_replace(PHP_EOL, '<br />', $mail_tpl['body']);
        foreach ($tpl_vars as $key => $value) {
            $mail_tpl['subject'] = str_replace($key, $value, $mail_tpl['subject']);
            $mail_tpl['body'] = str_replace($key, $value, $mail_tpl['body']);
            $mail_tpl['html_source'] = str_replace($key, $value, $mail_tpl['html_source']);
        }

        return $mail_tpl;
    }

    /**
     * Add custom email template to system list in which the password must be saved
     *
     * @since 4.7.2
     *
     * @param  string $emailTemplateKey
     *
     * @return bool
     */
    public function addMailTemplateForSendingPassword($emailTemplateKey)
    {
        if (!$emailTemplateKey = (string) $emailTemplateKey) {
            return false;
        }

        $this->mailsTemplatesWithPassword = array_merge($this->mailsTemplatesWithPassword, [$emailTemplateKey]);

        return true;
    }
}
