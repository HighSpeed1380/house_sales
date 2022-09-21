<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLNOTICE.CLASS.PHP
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

class rlNotice extends reefless
{
    /**
     * save notices in session
     *
     * @param array $message - notice message
     * @param string $message - notice message
     *
     * @todo save notice
     **/
    public function saveNotice($message = false, $type = 'notice')
    {
        $sesVar = 'notice';
        $sesVarType = 'notice_type';

        if (defined('REALM')) {
            $type = $type != 'notice' ? $type : 'pNotice';

            $sesVar = REALM . "_" . $sesVar;
            $sesVarType = REALM . "_" . $sesVarType;
        }

        if (!empty($message)) {
            $_SESSION[$sesVar] = $message;
            $_SESSION[$sesVarType] = $type;
        } else {
            return false;
        }
    }

    /**
     * reset notices from session
     *
     * @param array $message - notice message
     *
     * @todo save notice
     **/
    public function resetNotice()
    {
        $sesVar = 'notice';
        $sesVarType = 'type';

        if (defined('REALM')) {
            $sesVar = REALM . "_" . $sesVar;
            $sesVarType = REALM . "_" . $sesVarType;
        }

        unset($_SESSION[$sesVar], $_SESSION[$sesVarType]);
        return true;
    }

    /**
     * create notice block
     *
     * @param string $message - Notice message
     *
     **/
    public function createNotice($message)
    {
        echo 'rlNotice -> createNotice()'; // deprecated?
        $tpl = 'blocks' . RL_DS . 'notice_block_start.tpl';
        $block = $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);

        $block .= $message;

        $tpl = 'blocks' . RL_DS . 'notice_block_end.tpl';
        $block .= $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);

        return $block;
    }

    /**
     * create error block
     *
     * @param string $message - Error message
     *
     **/
    public function createError($message)
    {
        echo 'rlNotice -> createError()'; // deprecated?
        $tpl = 'blocks' . RL_DS . 'error_block_start.tpl';
        $block = $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);

        $mess_content = null;

        if (is_array($message)) {
            foreach ($message as $error) {
                $mess_content .= '- ' . $error . '<br />';
            }

            $block .= $mess_content;
        } else {
            $block .= $message;
        }

        $mess_content = substr($mess_content, 0, -6);

        $tpl = 'blocks' . RL_DS . 'error_block_end.tpl';
        $block .= $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);

        return $block;
    }

    /**
     * create alert notification
     *
     * @param array/string $message - Alert message(s)
     *
     **/
    public function createAlert($message)
    {
        echo 'rlNotice -> createAlert()'; // deprecated?
        $tpl = 'blocks' . RL_DS . 'alert_block_start.tpl';
        $block = $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);

        $mess_content = null;

        if (is_array($message)) {
            foreach ($message as $alert) {
                $mess_content .= '- ' . $alert . '<br />';
            }

            $block .= $mess_content;
        } else {
            $block .= $message;
        }

        $mess_content = substr($mess_content, 0, -6);

        $tpl = 'blocks' . RL_DS . 'alert_block_end.tpl';
        $block .= $GLOBALS['rlSmarty']->fetch($tpl, null, null, false);

        return $block;
    }
}
