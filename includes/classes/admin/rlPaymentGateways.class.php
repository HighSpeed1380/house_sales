<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLPAYMENTGATEWAYS.CLASS.PHP
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

class rlPaymentGateways
{
    /**
     * Payment gateway details
     *
     * @var array
     */
    protected $gateway_info;

    /**
     * Get gateway settings
     *
     * @return []
     */
    public function getSettings()
    {
        $prefix = $this->gateway_info['Key'] . '_';
        $configs = $GLOBALS['config'];
        $settings = array();

        foreach ($configs as $cKey => $cVal) {
            if (substr_count($cKey, $prefix) > 0 && substr_count($cKey, 'divider') <= 0) {
                $settings[] = $cKey;
            }
        }

        $sql = "SELECT `T1`.*, `T2`.`Value` AS `name`, `T3`.`Value` AS `des` ";
        $sql .= "FROM `{db_prefix}config` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T2` ON CONCAT('config+name+',`T1`.`Key`) = `T2`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "LEFT JOIN `{db_prefix}lang_keys` AS `T3` ON CONCAT('config+des+',`T1`.`Key`) = `T3`.`Key` AND `T2`.`Code` = '" . RL_LANG_CODE . "' ";
        $sql .= "WHERE `T1`.`Group_ID` = '6' AND `Type` <> 'divider' AND (`T1`.`Key` = '" . implode("' OR `T1`.`Key` = '", $settings) . "')";
        $sql .= "GROUP BY `T1`.`ID` ";

        $settings = array();
        $settings = $GLOBALS['rlDb']->getAll($sql);

        if ($settings) {
            $required = explode(',', $this->gateway_info['Required_options']);

            foreach ($settings as $key => $value) {
                if (in_array($value['Key'], $required)) {
                    $settings[$key]['required'] = true;
                }

                if ($value['Type'] == 'select' && $value['Values'] && !$value['Plugin']) {
                    $values = explode(',', $value['Values']);
                    $settings[$key]['Values'] = [];
                    foreach ($values as $k => $v) {
                        $settings[$key]['Values'][] = [
                            'ID' => $v,
                            'name' => $GLOBALS['lang'][$prefix . $v],
                        ];
                    }
                }
            }
        }

        $GLOBALS['rlHook']->load('apPhpPaymetGatewaysSettings', $settings);

        return $settings;
    }

    /**
     * Update settings
     *
     * @param array $data
     */
    public function updateSettings($data = false)
    {
        if (!$data) {
            return false;
        }

        foreach ($data as $key => $value) {
            $update = array(
                'fields' => array(
                    'Default' => $value,
                ),
                'where' => array(
                    'Key' => $key,
                ),
            );

            /**
             * @since 4.6.0
             */
            $GLOBALS['rlHook']->load('apPhpGatewayUpdateSettings', $update, $key, $value);

            $GLOBALS['rlActions']->updateOne($update, 'config');
        }
    }

    /**
     * Get gateway details by key
     *
     * @param  string $key
     * @return []
     */
    public function get($key = false)
    {
        if (!$key) {
            return false;
        }

        $sql = "SELECT * FROM `{db_prefix}payment_gateways` WHERE `Key` = '{$key}' LIMIT 1";
        $this->gateway_info = $GLOBALS['rlDb']->getRow($sql);

        if ($this->gateway_info) {
            return $this->gateway_info;
        }
    }

    /**
     * Get all payment gateways
     *
     * @return []
     */
    public function getGateways()
    {
        $sql = "SELECT * FROM `{db_prefix}payment_gateways` ORDER BY `ID` DESC";
        $gateways = $GLOBALS['rlDb']->getAll($sql);

        if ($gateways) {
            $gateways = $GLOBALS['rlLang']->replaceLangKeys($gateways, 'payment_gateways', array('name'));
        }

        return $gateways;
    }
}
