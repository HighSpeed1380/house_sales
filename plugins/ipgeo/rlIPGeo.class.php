<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLIPGEO.CLASS.PHP
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

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Flynax\Utils\Util;

class rlIPGeo extends Flynax\Abstracts\AbstractPlugin implements Flynax\Interfaces\PluginInterface
{
    /**
     * Flynax IP database url
     */
    private $server = 'https://database.flynax.com/index.php?plugin=ipgeo&type=file';

    /**
     * @var string
     */
    public $database = RL_PLUGINS . 'ipgeo/GeoLite2-City.mmdb';

    /**
     * Plugin installation process
     */
    public function install()
    {
        $GLOBALS['rlDb']->query("
            INSERT INTO `{db_prefix}config` (`Group_ID`, `Key`, `Default`, `Plugin`) VALUES
            (0, 'ipgeo_database_version', '', 'ipgeo');
        ");
    }

    /**
     * Get user location by IP
     *
     * @since 1.4.0
     * @hook init
     */
    public function hookInit()
    {
        // Return if the location data already fetched
        if ($_SESSION['GEOLocationData']) {
            return;
        }

        if (false === $GLOBALS['reefless']->isBot() && null !== $ip = Util::getClientIP()) {
            // Localhost IP
            if ($ip === '127.0.0.1' || $ip === '::1') {
                $location = new stdClass;
                $location->Country_code = 'US';
                $location->Country_name = 'United States';
                $location->Region       = 'California';
                $location->City         = 'Marina del Rey';
            }
            // Global IP
            else {
                try {
                    require_once RL_PLUGINS . 'ipgeo/vendor/autoload.php';

                    $reader   = new Reader($this->database);
                    $record   = $reader->city($ip);
                    $location = new stdClass;

                    $location->Country_code  = $record->country->isoCode;
                    $location->Country_name  = $record->country->name;
                    $location->Country_names = $record->country->names;
                    $location->Region        = $record->mostSpecificSubdivision->name;
                    $location->Region_names  = $record->mostSpecificSubdivision->names;
                    $location->City          = $record->city->name;
                    $location->City_names    = $record->city->names;
                    $location->Latitude      = $record->location->latitude;
                    $location->Longitude     = $record->location->longitude;
                    $location->Timezone      = $record->location->timeZone;
                    $location->postalCode    = $record->postal->code;

                    unset($reader, $record);
                } catch (AddressNotFoundException $e) {
                    $location = $this->getDefaultLocation();
                }
            }
        } else {
            $location = $this->getDefaultLocation();
        }

        $_SESSION['GEOLocationData'] = $location;
    }

    /**
     * Generate values for default country option
     *
     * @since 1.3.2
     * @hook specialBlock
     */
    public function hookApMixConfigItem(&$param1)
    {
        if ($param1['Key'] == 'ipgeo_default_country') {
            $countries = $this->getCountriesList();
            $values = array();

            foreach ($countries as $country) {
                $values[] = array(
                    'name' => $country->Country_name,
                    'Key' => $country->Country_code,
                    'ID' => $country->Country_code
                );
            }

            $param1['Values'] = $values;
            unset($values);
        }
    }

    /**
     * Print notice in admin panel
     *
     * @since 1.4.0
     * @hook apNotifications
     *
     * @param array $notices - global notifications array
     */
    public function hookApNotifications(&$notices)
    {
        $this->printNotice($notices);
    }

    /**
     * Control ajax queries
     *
     * @since 1.4.0
     * @hook apAjaxRequest
     *
     * @param array  $out  - response data
     * @param string $item - request item
     */
    public function hookApAjaxRequest(&$out = null, $item = null)
    {
        global $lang, $reefless, $rlDb;

        $filesDir = RL_UPLOAD . 'ipgeo' . RL_DS;

        switch ($item) {
            case 'ipgeoCheckUpdate':
                $update = '&update=1&version=' . $GLOBALS['config']['ipgeo_database_version'];
                $response = $reefless->getPageContent($this->server . $update);

                if ($response) {
                    $data = json_decode($response, true);
                    $out['status'] = 'OK';
                    $out['data'] = $data['update_status'];
                } else {
                    $out = $this->errorLog($lang['flynax_connect_fail'], __LINE__);
                }
                break;

            case 'ipGeoPrepare':
                $reefless->rlMkdir($filesDir);

                if (!is_writable($filesDir)) {
                    $out = $this->errorLog(
                        'Unable to create directory in "'
                            . $filesDir
                            . '", make sure the directory has writable permissions.',
                        __LINE__
                    );
                    return;
                }

                $response = $reefless->getPageContent($this->server);

                if ($response) {
                    $_SESSION['ipgeo'] = ['server_data' => json_decode($response, true)];
                    $out               = ['status' => 'OK', 'data' => json_decode($response, true)];
                } else {
                    $out = $this->errorLog($lang['flynax_connect_fail'], __LINE__);
                }
                break;

            case 'ipGeoDownloadFile':
                $fileNumber  = (int) $_REQUEST['file'];
                $fileName    = 'part' . $fileNumber;
                $source      = $_SESSION['ipgeo']['server_data']['base_url'] . $fileName;
                $destination = $filesDir . $fileName;

                $reefless->time_limit = 60;

                if ($reefless->copyRemoteFile($source, $destination)) {
                    if (!file_exists($destination)) {
                        $out = $this->errorLog(
                            'Unable to copy file "' . $destination . '", please contact Flynax Support.',
                            __LINE__
                        );
                        $out['retry'] = true;
                    } else {
                        $_SESSION['ipgeo']['current_file']        = $fileName;
                        $_SESSION['ipgeo']['current_file_number'] = $fileNumber;

                        if ($_SESSION['ipgeo']['current_file_number'] + 1 < $_SESSION['ipgeo']['server_data']['calc']) {
                            $progress = 100 / $_SESSION['ipgeo']['server_data']['calc'];
                            $progress = round($progress * $_SESSION['ipgeo']['current_file_number']);

                            $out = ['status' => 'OK', 'action' => 'next_file', 'progress' => $progress];
                        } else {
                            $serverData = $_SESSION['ipgeo']['server_data'];

                            if ($this->mergeFiles($this->database, $serverData['calc'])) {
                                $GLOBALS['rlConfig']->setConfig('ipgeo_database_version', $serverData['version']);
                                $reefless->deleteDirectory(RL_UPLOAD . 'ipgeo');
                                $out = ['status' => 'OK', 'action' => 'end'];
                            } else {
                                $out = $this->errorLog(
                                    'Installing of the database has been failed, please contact Flynax Support.',
                                    __LINE__
                                );
                            }
                        }
                    }
                } else {
                    $out = $this->errorLog(
                        'Unable to copy file "'
                            . $source
                            . '" from Flynax server, please try later or contact Flynax Support.',
                        __LINE__
                    );
                }
                break;
        }
    }

    /**
     * Combine splitted binary file to one
     *
     * @since 2.0.0
     *
     * @param $mergedFileName - Path to the final file
     * @param $parts          - Count of parts
     *
     * @return bool
     */
    private function mergeFiles($mergedFileName, $parts)
    {
        if (!$mergedFileName || !$parts) {
            return false;
        }

        $content = '';
        for ($i = 0; $i < $parts; $i++) {
            if (!file_exists($partFile = RL_UPLOAD . 'ipgeo/part' . $i)) {
                return false;
            }

            $handle = fopen($partFile, 'rb');
            $content  .= fread($handle, filesize($partFile));
            fclose($handle);
        }

        $handle = fopen($mergedFileName, 'wb');
        fwrite($handle, $content);
        fclose($handle);

        return true;
    }

    /**
     * Get default location
     */
    public function getDefaultLocation()
    {
        foreach ($this->getCountriesList() as $country) {
            if ($country->Country_code == $GLOBALS['config']['ipgeo_default_country']) {
                return $country;
            }
        }
    }

    /**
     * Get countries list
     */
    public function getCountriesList()
    {
        $countries = '[
            {"Country_code":"AF","Country_name":"Afghanistan"},{"Country_code":"AX","Country_name":"Aland Islands"},{"Country_code":"AL","Country_name":"Albania"},
            {"Country_code":"DZ","Country_name":"Algeria"},{"Country_code":"AS","Country_name":"American Samoa"},{"Country_code":"AD","Country_name":"Andorra"},
            {"Country_code":"AO","Country_name":"Angola"},{"Country_code":"AI","Country_name":"Anguilla"},{"Country_code":"AQ","Country_name":"Antarctica"},
            {"Country_code":"AG","Country_name":"Antigua and Barbuda"},{"Country_code":"AR","Country_name":"Argentina"},{"Country_code":"AM","Country_name":"Armenia"},
            {"Country_code":"AW","Country_name":"Aruba"},{"Country_code":"AU","Country_name":"Australia"},{"Country_code":"AT","Country_name":"Austria"},
            {"Country_code":"AZ","Country_name":"Azerbaijan"},{"Country_code":"BS","Country_name":"Bahamas"},{"Country_code":"BH","Country_name":"Bahrain"},
            {"Country_code":"BD","Country_name":"Bangladesh"},{"Country_code":"BB","Country_name":"Barbados"},{"Country_code":"BY","Country_name":"Belarus"},
            {"Country_code":"BE","Country_name":"Belgium"},{"Country_code":"BZ","Country_name":"Belize"},{"Country_code":"BJ","Country_name":"Benin"},
            {"Country_code":"BM","Country_name":"Bermuda"},{"Country_code":"BT","Country_name":"Bhutan"},{"Country_code":"BO","Country_name":"Bolivia"},
            {"Country_code":"BA","Country_name":"Bosnia and Herzegovina"},{"Country_code":"BW","Country_name":"Botswana"},
            {"Country_code":"BV","Country_name":"Bouvet Island"},{"Country_code":"BR","Country_name":"Brazil"},{"Country_code":"IO","Country_name":"British Indian Ocean Territory"},
            {"Country_code":"BN","Country_name":"Brunei Darussalam"},{"Country_code":"BG","Country_name":"Bulgaria"},{"Country_code":"BF","Country_name":"Burkina Faso"},
            {"Country_code":"BI","Country_name":"Burundi"},{"Country_code":"KH","Country_name":"Cambodia"},{"Country_code":"CM","Country_name":"Cameroon"},
            {"Country_code":"CA","Country_name":"Canada"},{"Country_code":"CV","Country_name":"Cape Verde"},{"Country_code":"KY","Country_name":"Cayman Islands"},
            {"Country_code":"CF","Country_name":"Central African Republic"},{"Country_code":"TD","Country_name":"Chad"},{"Country_code":"CL","Country_name":"Chile"},
            {"Country_code":"CN","Country_name":"China"},{"Country_code":"CX","Country_name":"Christmas Island"},{"Country_code":"CC","Country_name":"Cocos (Keeling) Islands"},
            {"Country_code":"CO","Country_name":"Colombia"},{"Country_code":"KM","Country_name":"Comoros"},{"Country_code":"CG","Country_name":"Congo"},
            {"Country_code":"CD","Country_name":"Congo, The Democratic Republic of the"},{"Country_code":"CK","Country_name":"Cook Islands"},
            {"Country_code":"CR","Country_name":"Costa Rica"},{"Country_code":"CI","Country_name":"Cote D\'Ivoire"},{"Country_code":"HR","Country_name":"Croatia"},
            {"Country_code":"CU","Country_name":"Cuba"},{"Country_code":"CY","Country_name":"Cyprus"},{"Country_code":"CZ","Country_name":"Czech Republic"},
            {"Country_code":"DK","Country_name":"Denmark"},{"Country_code":"DJ","Country_name":"Djibouti"},{"Country_code":"DM","Country_name":"Dominica"},
            {"Country_code":"DO","Country_name":"Dominican Republic"},{"Country_code":"TL","Country_name":"East Timor"},{"Country_code":"EC","Country_name":"Ecuador"},
            {"Country_code":"EG","Country_name":"Egypt"},{"Country_code":"SV","Country_name":"El Salvador"},{"Country_code":"GQ","Country_name":"Equatorial Guinea"},
            {"Country_code":"ER","Country_name":"Eritrea"},{"Country_code":"EE","Country_name":"Estonia"},{"Country_code":"ET","Country_name":"Ethiopia"},
            {"Country_code":"FK","Country_name":"Falkland Islands (Malvinas)"},{"Country_code":"FO","Country_name":"Faroe Islands"},{"Country_code":"FJ","Country_name":"Fiji"},
            {"Country_code":"FI","Country_name":"Finland"},{"Country_code":"FR","Country_name":"France"},{"Country_code":"GF","Country_name":"French Guiana"},
            {"Country_code":"PF","Country_name":"French Polynesia"},{"Country_code":"TF","Country_name":"French Southern Territories"},{"Country_code":"GA","Country_name":"Gabon"},
            {"Country_code":"GM","Country_name":"Gambia"},{"Country_code":"GE","Country_name":"Georgia"},{"Country_code":"DE","Country_name":"Germany"},
            {"Country_code":"GH","Country_name":"Ghana"},{"Country_code":"GI","Country_name":"Gibraltar"},{"Country_code":"GR","Country_name":"Greece"},
            {"Country_code":"GL","Country_name":"Greenland"},{"Country_code":"GD","Country_name":"Grenada"},{"Country_code":"GP","Country_name":"Guadeloupe"},
            {"Country_code":"GU","Country_name":"Guam"},{"Country_code":"GT","Country_name":"Guatemala"},{"Country_code":"GG","Country_name":"Guernsey"},
            {"Country_code":"GN","Country_name":"Guinea"},{"Country_code":"GW","Country_name":"Guinea-Bissau"},{"Country_code":"GY","Country_name":"Guyana"},
            {"Country_code":"HT","Country_name":"Haiti"},{"Country_code":"HM","Country_name":"Heard Island and McDonald Islands"},
            {"Country_code":"VA","Country_name":"Holy See (Vatican City State)"},{"Country_code":"HN","Country_name":"Honduras"},{"Country_code":"HK","Country_name":"Hong Kong"},
            {"Country_code":"HU","Country_name":"Hungary"},{"Country_code":"IS","Country_name":"Iceland"},{"Country_code":"IN","Country_name":"India"},
            {"Country_code":"ID","Country_name":"Indonesia"},{"Country_code":"IR","Country_name":"Iran, Islamic Republic of"},{"Country_code":"IQ","Country_name":"Iraq"},
            {"Country_code":"IE","Country_name":"Ireland"},{"Country_code":"IM","Country_name":"Isle of Man"},{"Country_code":"IL","Country_name":"Israel"},
            {"Country_code":"IT","Country_name":"Italy"},{"Country_code":"JM","Country_name":"Jamaica"},{"Country_code":"JP","Country_name":"Japan"},
            {"Country_code":"JE","Country_name":"Jersey"},{"Country_code":"JO","Country_name":"Jordan"},{"Country_code":"KZ","Country_name":"Kazakhstan"},
            {"Country_code":"KE","Country_name":"Kenya"},{"Country_code":"KI","Country_name":"Kiribati"},{"Country_code":"KP","Country_name":"Korea, Democratic People\'s Republic of"},
            {"Country_code":"KR","Country_name":"Korea, Republic of"},{"Country_code":"KW","Country_name":"Kuwait"},{"Country_code":"KG","Country_name":"Kyrgyzstan"},
            {"Country_code":"LA","Country_name":"Lao People\'s Democratic Republic"},{"Country_code":"LV","Country_name":"Latvia"},{"Country_code":"LB","Country_name":"Lebanon"},
            {"Country_code":"LS","Country_name":"Lesotho"},{"Country_code":"LR","Country_name":"Liberia"},{"Country_code":"LY","Country_name":"Libyan Arab Jamahiriya"},
            {"Country_code":"LI","Country_name":"Liechtenstein"},{"Country_code":"LT","Country_name":"Lithuania"},{"Country_code":"LU","Country_name":"Luxembourg"},
            {"Country_code":"MO","Country_name":"Macau"},{"Country_code":"MK","Country_name":"Macedonia"},{"Country_code":"MG","Country_name":"Madagascar"},
            {"Country_code":"MW","Country_name":"Malawi"},{"Country_code":"MY","Country_name":"Malaysia"},{"Country_code":"MV","Country_name":"Maldives"},
            {"Country_code":"ML","Country_name":"Mali"},{"Country_code":"MT","Country_name":"Malta"},{"Country_code":"MH","Country_name":"Marshall Islands"},
            {"Country_code":"MQ","Country_name":"Martinique"},{"Country_code":"MR","Country_name":"Mauritania"},{"Country_code":"MU","Country_name":"Mauritius"},
            {"Country_code":"YT","Country_name":"Mayotte"},{"Country_code":"MX","Country_name":"Mexico"},{"Country_code":"FM","Country_name":"Micronesia, Federated States of"},
            {"Country_code":"MD","Country_name":"Moldova, Republic of"},{"Country_code":"MC","Country_name":"Monaco"},{"Country_code":"MN","Country_name":"Mongolia"},
            {"Country_code":"ME","Country_name":"Montenegro"},{"Country_code":"MS","Country_name":"Montserrat"},{"Country_code":"MA","Country_name":"Morocco"},
            {"Country_code":"MZ","Country_name":"Mozambique"},{"Country_code":"MM","Country_name":"Myanmar"},{"Country_code":"NA","Country_name":"Namibia"},
            {"Country_code":"NR","Country_name":"Nauru"},{"Country_code":"NP","Country_name":"Nepal"},{"Country_code":"NL","Country_name":"Netherlands"},
            {"Country_code":"AN","Country_name":"Netherlands Antilles"},{"Country_code":"NC","Country_name":"New Caledonia"},{"Country_code":"NZ","Country_name":"New Zealand"},
            {"Country_code":"NI","Country_name":"Nicaragua"},{"Country_code":"NE","Country_name":"Niger"},{"Country_code":"NG","Country_name":"Nigeria"},
            {"Country_code":"NU","Country_name":"Niue"},{"Country_code":"NF","Country_name":"Norfolk Island"},{"Country_code":"MP","Country_name":"Northern Mariana Islands"},
            {"Country_code":"NO","Country_name":"Norway"},{"Country_code":"OM","Country_name":"Oman"},{"Country_code":"PK","Country_name":"Pakistan"},
            {"Country_code":"PW","Country_name":"Palau"},{"Country_code":"PS","Country_name":"Palestinian Territory"},{"Country_code":"PA","Country_name":"Panama"},
            {"Country_code":"PG","Country_name":"Papua New Guinea"},{"Country_code":"PY","Country_name":"Paraguay"},{"Country_code":"PE","Country_name":"Peru"},
            {"Country_code":"PH","Country_name":"Philippines"},{"Country_code":"PN","Country_name":"Pitcairn"},{"Country_code":"PL","Country_name":"Poland"},
            {"Country_code":"PT","Country_name":"Portugal"},{"Country_code":"PR","Country_name":"Puerto Rico"},{"Country_code":"QA","Country_name":"Qatar"},
            {"Country_code":"RE","Country_name":"Reunion"},{"Country_code":"RO","Country_name":"Romania"},{"Country_code":"RU","Country_name":"Russian Federation"},
            {"Country_code":"RW","Country_name":"Rwanda"},{"Country_code":"SH","Country_name":"Saint Helena"},{"Country_code":"KN","Country_name":"Saint Kitts and Nevis"},
            {"Country_code":"LC","Country_name":"Saint Lucia"},{"Country_code":"PM","Country_name":"Saint Pierre and Miquelon"},
            {"Country_code":"VC","Country_name":"Saint Vincent and the Grenadines"},{"Country_code":"WS","Country_name":"Samoa"},{"Country_code":"SM","Country_name":"San Marino"},
            {"Country_code":"ST","Country_name":"Sao Tome and Principe"},{"Country_code":"SA","Country_name":"Saudi Arabia"},{"Country_code":"SN","Country_name":"Senegal"},
            {"Country_code":"RS","Country_name":"Serbia"},{"Country_code":"SC","Country_name":"Seychelles"},{"Country_code":"SL","Country_name":"Sierra Leone"},
            {"Country_code":"SG","Country_name":"Singapore"},{"Country_code":"SK","Country_name":"Slovakia"},{"Country_code":"SI","Country_name":"Slovenia"},
            {"Country_code":"SB","Country_name":"Solomon Islands"},{"Country_code":"SO","Country_name":"Somalia"},{"Country_code":"ZA","Country_name":"South Africa"},
            {"Country_code":"GS","Country_name":"South Georgia and the South Sandwich Islands"},{"Country_code":"ES","Country_name":"Spain"},
            {"Country_code":"LK","Country_name":"Sri Lanka"},{"Country_code":"SD","Country_name":"Sudan"},{"Country_code":"SR","Country_name":"Suriname"},
            {"Country_code":"SJ","Country_name":"Svalbard and Jan Mayen"},{"Country_code":"SZ","Country_name":"Swaziland"},{"Country_code":"SE","Country_name":"Sweden"},
            {"Country_code":"CH","Country_name":"Switzerland"},{"Country_code":"SY","Country_name":"Syrian Arab Republic"},
            {"Country_code":"TW","Country_name":"Taiwan (Province of China)"},{"Country_code":"TJ","Country_name":"Tajikistan"},{"Country_code":"TZ","Country_name":"Tanzania, United Republic of"},
            {"Country_code":"TH","Country_name":"Thailand"},{"Country_code":"TG","Country_name":"Togo"},{"Country_code":"TK","Country_name":"Tokelau"},{"Country_code":"TO","Country_name":"Tonga"},
            {"Country_code":"TT","Country_name":"Trinidad and Tobago"},{"Country_code":"TN","Country_name":"Tunisia"},{"Country_code":"TR","Country_name":"Turkey"},
            {"Country_code":"TM","Country_name":"Turkmenistan"},{"Country_code":"TC","Country_name":"Turks and Caicos Islands"},{"Country_code":"TV","Country_name":"Tuvalu"},
            {"Country_code":"UG","Country_name":"Uganda"},{"Country_code":"UA","Country_name":"Ukraine"},{"Country_code":"AE","Country_name":"United Arab Emirates"},
            {"Country_code":"GB","Country_name":"United Kingdom"},{"Country_code":"US","Country_name":"United States"},{"Country_code":"UM","Country_name":"United States Minor Outlying Islands"},
            {"Country_code":"UY","Country_name":"Uruguay"},{"Country_code":"UZ","Country_name":"Uzbekistan"},{"Country_code":"VU","Country_name":"Vanuatu"},
            {"Country_code":"VE","Country_name":"Venezuela"},{"Country_code":"VN","Country_name":"Vietnam"},{"Country_code":"VG","Country_name":"Virgin Islands, British"},
            {"Country_code":"VI","Country_name":"Virgin Islands, U.S."},{"Country_code":"WF","Country_name":"Wallis and Futuna"},{"Country_code":"EH","Country_name":"Western Sahara"},
            {"Country_code":"YE","Country_name":"Yemen"},{"Country_code":"ZM","Country_name":"Zambia"},{"Country_code":"ZW","Country_name":"Zimbabwe"}
        ]';
        $countries = preg_replace('/(\n|\t|\r)?/', '', $countries);

        return json_decode($countries);
    }

    /**
     * Print notice in admin panel if there is any issues with database exist
     *
     * @since 1.3.0
     *
     * @param array $notices - the array of the system notices to print in admin panel
     */
    public function printNotice(&$notices)
    {
        $phrase = preg_replace(
            '/(\[(.*)?\])/', '<a href="'.RL_TPL_BASE.'index.php?controller=ipgeo">$2</a>',
            $GLOBALS['rlLang']->getSystem('ipgeo_update_notice')
        );

        // Check version
        if (!$_COOKIE['lf_last_version_check']) {
            $response = $GLOBALS['reefless']->getPageContent($this->server . '&check=1');

            if ($response) {
                $data = json_decode($response, true);

                // Print update notice
                if (version_compare($data['version'], $GLOBALS['config']['ipgeo_database_version']) === 1) {
                    $notices[] = $phrase;
                }
                // Save last db check for a day
                else {
                    $expire = time() + 86400;
                    $GLOBALS['reefless']->createCookie('lf_last_version_check', 1, $expire);
                }
            } else {
                $this->errorLog($GLOBALS['lang']['flynax_connect_fail'], __LINE__);
            }
        }
        // Print system notice
        else {
            if (version_compare($GLOBALS['config']['ipgeo_database_version'], '1.0.0') < 0) {
                $notices[] = $phrase;
            }
        }
    }

    /**
     * Error handler
     * Logs error to the errorLog file and return error response
     *
     * @since 1.3.0
     *
     * @param string $msg  - Error message
     * @param string $line - Related code line
     */
    private function errorLog($msg = '', $line = '')
    {
        $GLOBALS['rlDebug']->logger('ipGEO Plugin Error: ' . $msg . ' On ' . __FILE__ . '(line #' . $line . ')');
        return ['status' => 'ERROR', 'data' => $msg];
    }

    /**
     * Update to 1.3.0 version
     */
    public function update130()
    {
        $GLOBALS['rlDb']->query(
            "INSERT INTO `{db_prefix}config` (`Group_ID`, `Key`, `Default`, `Plugin`)
             VALUES (0, 'ipgeo_database_version', '', 'ipgeo')"
        );
    }

    /**
     * Update to 1.4.0 version
     */
    public function update140()
    {
        $GLOBALS['rlDb']->query(
            "DELETE FROM `{db_prefix}hooks`
             WHERE `Plugin` = 'ipgeo' AND `Name` = 'apTplHomeBottom' LIMIT 1"
        );
    }

    /**
     * Update to 2.0.0 version
     */
    public function update200()
    {
        global $rlDb, $languages;

        $rlDb->query(
            "DELETE FROM `{db_prefix}lang_keys`
             WHERE `Key` IN ('ipgeo_remote_update_notice', 'ipgeo_file_upload_info') AND `Plugin` = 'ipgeo'"
        );

        $GLOBALS['rlConfig']->setConfig('ipgeo_database_version', '');

        (new Flynax\Component\Filesystem())->copyTo(RL_UPLOAD . 'ipgeo/vendor', RL_PLUGINS . 'ipgeo/vendor');

        $rlDb->dropTables(['ipgeo', 'ipgeo_locations']);

        if (in_array('ru', array_keys($languages))) {
            $russianTranslation = json_decode(file_get_contents(RL_UPLOAD . 'ipgeo/i18n/ru.json'), true);
            $phraseKey          = 'config+des+ipgeo_default_country';

            $rlDb->updateOne([
                'fields' => ['Value' => $russianTranslation[$phraseKey]],
                'where'  => ['Key'   => $phraseKey, 'Code' => 'ru'],
            ], 'lang_keys');
        }
    }

    /*** DEPRECATED METHODS ***/

    /**
     * Create main `ipgeo` table
     *
     * @deprecated 2.0.0
     * @since 1.4.0
     */
    private function createMainTable()
    {}

    /**
     * Remove plugins db tables
     *
     * @deprecated 2.0.0
     * @since 1.4.0
     */
    public function uninstall()
    {}

    /**
     * Check if table exists
     *
     * @deprecated 2.0.0
     * @since 1.4.2
     *
     * @param  string $table  - Name of the table
     * @param  string $prefix - Prefix for the table if necessary; by default RL_DBPREFIX constant
     * @return bool
     */
    private function tableExists($table, $prefix = RL_DBPREFIX)
    {}

    /**
     * Count file lines
     *
     * @deprecated 2.0.0
     * @since 1.3.0
     *
     * @param  string $file - file path
     * @return int          - count of lines
     */
    private function countFileLines($file)
    {}

    /**
     * Clear session data
     *
     * @deprecated 2.0.0
     * @since 1.3.0
     */
    private function clearDumpData()
    {}

    /**
     * Unzip gzip file
     *
     * @deprecated 2.0.0
     *
     * @param  string $file - file path
     * @return boolean      - is operation successful
     */
    private function gUnZip($file)
    {}

    /**
     * Ajax queries handler
     *
     * @deprecated 2.0.0
     * @since 1.3.0
     *
     * @param array  $out  - response data
     * @param string $item - request item
     */
    public function ajax(&$out, &$item)
    {}

    /**
     * Import dump file
     *
     * @deprecated 2.0.0
     * @since 1.3.0
     *
     * @param  string $dump_file - file path
     * @return array             - results data array
     */
    private function importDump($dump_file = false)
    {}

    /**
     * Run sql query
     *
     * @deprecated 2.0.0
     * @since 1.3.0
     *
     * @param  string $query - mysql query
     * @return mixed         - error or true
     */
    private function importDumpRunQuery($query = false)
    {}

    /**
     * Sort countries by accuracy
     *
     * @deprecated 2.0.0
     */
    public function sortByAccuracy($a, $b)
    {}
}
