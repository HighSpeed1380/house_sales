<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLLOCATIONFINDERAP.CLASS.PHP
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

class rlLocationFinderAP
{
    /**
     * Flynax IP database url
     */
    var $server = 'http://database.flynax.com/index.php?plugin=locationFinder';

    /**
     * Get mapping data by format key
     *
     * @hook - apAjaxRequest
     *
     * @param string $key - format key
     *
     */
    public function getMappingDataByKey($key = false)
    {
        $GLOBALS['rlValid']->sql($key);
        return $GLOBALS['rlDb']->fetch(array('Lat', 'Lng'), array('Format_key' => $key), null, 1, 'geo_mapping', 'row');
    }

    /**
     * Save mapping data
     *
     * @since 4.0.2 - The parameters order has been changed, $target parameter added after $neighborhoodPlaceID
     * @hook - apAjaxRequest
     *
     * @param string $formatKey           - format key
     * @param string $cityPlaceID         - city place ID
     * @param string $neighborhoodPlaceID - neighborhood place ID
     * @param string $target              - location target, city or region
     * @param string $lat                 - location latitude
     * @param string $lng                 - location longitude
    */
    public function saveMappingData($formatKey = false, $cityPlaceID = false, $neighborhoodPlaceID = false, $target = null, $lat = false, $lng = false)
    {
        $GLOBALS['rlValid']->sql($formatKey);
        $GLOBALS['rlValid']->sql($cityPlaceID);
        $GLOBALS['rlValid']->sql($neighborhoodPlaceID);
        $GLOBALS['rlValid']->sql($target);
        $GLOBALS['rlValid']->sql($lat);
        $GLOBALS['rlValid']->sql($lng);

        $place_id = $GLOBALS['config']['locationFinder_use_neighborhood'] && $neighborhoodPlaceID
        ? $neighborhoodPlaceID
        : $cityPlaceID;

        $fields = array(
            'Format_key' => $formatKey,
            'Place_ID' => $place_id,
            'Target' => $target,
            'Lat' => $lat,
            'Lng' => $lng,
            'Verified' => '1'
        );

        $GLOBALS['reefless']->loadClass('Actions');

        if ($GLOBALS['rlDb']->getOne('ID', "`Format_key` = '{$formatKey}'", 'geo_mapping')) {
            $update = array('fields' => $fields, 'where' => array('Format_key' => $formatKey));
            $GLOBALS['rlActions']->updateOne($update, 'geo_mapping');
        } else {
            $GLOBALS['rlActions']->insertOne($fields, 'geo_mapping');
        }

        return true;
    }

    private function gUnZip($file = false)
    {
        if (!$file) {
            return false;
        }

        $buffer_size = 4096;
        $out_file = str_replace('.gz', '', $file);

        // open files (in binary mode)
        $file = gzopen($file, 'rb');
        $out_file = fopen($out_file, 'wb');

        // set writable permissions
        $GLOBALS['reefless']->rlChmod($out_file);

        // read source file and write to destination one
        while(!gzeof($file)) {
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // close files
        fclose($out_file);
        gzclose($file);

        return true;
    }

    /**
     * @deprecated 4.0.0 - See self::hookApAjaxRequest()
     */
    public function ajax(&$out, &$item) {}

    /**
     * Import dump file
     *
     * @since 1.3.0
     *
     * @param  string $dump_file - file path
     * @return array             - results data array
     */
    private function importDump($dump_file = false)
    {
        $file = fopen($dump_file, 'r');

        $line_per_session = 15000;
        $data_chunk_lenght = 16384;
        $start_line = $_SESSION['extra_dumps_start_line'];
        $session_line = 0;

        $query = '';
        $current_line = $start_line;
        $ret = array();

        fseek($file, $_SESSION['extra_dumps_pointer']);

        while (!feof($file) && $current_line <= $start_line+$line_per_session) {
            $line = fgets($file, $data_chunk_lenght);
            $session_line++;

            // skip commented lines
            if ((bool) preg_match('/^(\-\-|\#|\/\*)/', $line)) {
                continue;
            }

            $query .= $line;
            if ((bool) preg_match('/\;(\r\n?|\n)$/', $line) || feof($file)) {
                $query_result = $this->importDumpRunQuery($query);
                if ($query_result !== true) {
                    $ret = array('error' => $query_result);
                }

                $query = '';
            }

            if (feof($file)) {
                fclose($file);
                unlink($dump_file);
                $current_line = 0;

                if ($_SESSION['locationFinder']['current_file_number'] < $_SESSION['locationFinder']['server_data']['calc']) {
                    $ret['action'] = 'next_file';
                } else {
                    $ret['action'] = 'end';

                    // update databsae version
                    $GLOBALS['reefless']->loadClass('Actions');
                    $GLOBALS['rlConfig']->setConfig('locationFinder_db_version', $_SESSION['locationFinder']['server_data']['version']);
                }

                break;
            }

            // last line
            if ($current_line == $start_line+$line_per_session && !(bool) preg_match('/\;(\r\n?|\n)$/', $line)) {
                $line_per_session++; // go one more line forward
            }

            $current_line++;
            $ret['action'] = 'next_stack';
        }

        $_SESSION['extra_dumps_progress_line'] += $session_line;
        $_SESSION['extra_dumps_start_line'] = $current_line;
        $_SESSION['extra_dumps_pointer'] = ftell($file);

        $ret['lines'] = $session_line;
        $ret['line_num'] = $_SESSION['extra_dumps_progress_line'];
        $progress = (100 / $_SESSION['locationFinder']['server_data']['calc']) * $_SESSION['locationFinder']['current_file_number'];
        $progress_stack = (100 / $_SESSION['locationFinder']['server_data']['calc']);

        $ret['progress'] = round(($progress - $progress_stack) + ceil(($_SESSION['extra_dumps_progress_line'] * $progress_stack) / $_SESSION['extra_dumps_lines']), 0);

        if ($ret['action'] == 'end') {
            $this->clearDumpData();
            unset($_SESSION['locationFinder']);
        } elseif ($ret['action'] == 'next_file') {
            $this->clearDumpData();
        }

        return $ret;
    }

    /**
     * Run sql query
     *
     * @since 1.3.0
     *
     * @param  string $query - mysql query
     * @return mixed         - error or true
     */
    private function importDumpRunQuery($query = false)
    {
        $query = trim($query);

        if (!$query) {
            return true;
        }
        $query = str_replace(array('{db_prefix}', PHP_EOL), array(RL_DBPREFIX, ''), $query);

        $GLOBALS['rlDb']->dieIfError = false;
        $GLOBALS['rlDb']->query($query);

        if ($GLOBALS['rlDb']->lastErrno()) {
            $error  = "Can not run sql query." . PHP_EOL;
            $error .= "Error: " . $GLOBALS['rlDb']->lastError() . '; '. PHP_EOL;
            $error .= "Query: " . $query;
        }

        return $error ? $error : true;
    }

    /**
     * Error hander, adds error to global errors array and logs error to the errorLog file
     *
     * @since 3.1.0
     *
     * @param string $msd    - error message
     * @param array  $errors - global errors array
     * @param string $line   - related code line
     */
    private function errorLog($msg, &$errors, $line)
    {
        $errors[] = $msg;
        $GLOBALS['rlDebug']->logger('Location Finder Plugin Error: ' . $msg . ' On ' . __FILE__ . '(line #' . $line . ')');
    }

    /**
     * @deprecated 4.0.2 - Replaced with system copyRemoteFile()
     *
     * @since 3.1.0
     *
     * @param  string $source      - source file path
     * @param  string $destination - destination file path
     * @return bool                - is file coppied or not
     */
    private function copyFile($source = false, $destination = false)
    {}

    /**
     * count file lines
     *
     * @since 3.1.0
     *
     * @param  string $file - file path
     * @return int          - count of lines
     */
    private function countFileLines($file)
    {
        $count = 0;
        $fp = fopen($file, 'r');

        while (!feof($fp)) {
            fgets($fp);
            $count++;
        }

        fclose($fp);
        return $count;
    }

    /**
     * clear session data
     *
     * @since 3.1.0
     */
    private function clearDumpData() {
        unset($_SESSION['extra_dumps_start_line'],
            $_SESSION['extra_dumps_pointer'],
            $_SESSION['extra_dumps_progress_line'],
            $_SESSION['extra_dumps_total_lines'],
            $_SESSION['extra_dumps_current']);
    }

    /**
     * Set map zoom
     *
     * @since 4.0.0
     * @hook apPhpListingsView
     */
    public function hookApPhpListingsView()
    {
        global $config, $listing_data;

        if ($listing_data['lf_zoom']) {
            $config['map_default_zoom'] = $listing_data['lf_zoom'];
        }
    }

    /**
     * Display map on edit listing page
     *
     * @since 4.0.0
     * @hook - apAjaxRequest
     */
    public function hookApTplListingsFormEdit()
    {
        if ($GLOBALS['rlListingTypes']->types[$GLOBALS['listing']['Listing_type']]['Location_finder']) {
            $this->getGeoFormatFields();
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'locationFinder' . RL_DS . 'admin' . RL_DS . 'map.tpl');
        }
    }

    /**
     * Display map on add listing page
     *
     * @since 4.0.0
     * @hook apTplListingsFormAdd
     */
    public function hookApTplListingsFormAdd()
    {
        if ($GLOBALS['listing_type']['Location_finder']) {
            $this->getGeoFormatFields();
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'locationFinder' . RL_DS . 'admin' . RL_DS . 'map.tpl');
        }
    }

    /**
     * Prepare and assign to SMARTY the listing fields array uses by geo filter
     *
     * @since 5.0.1
     */
    public function getGeoFormatFields()
    {
        global $config, $rlDb;

        if ($GLOBALS['plugins']['multiField'] && $config['mf_geo_data_format']) {
            if ($geo_format = json_decode($config['mf_geo_data_format'], true)) {
                $rlDb->outputRowsMap = [false, 'Key'];
                $fields = $rlDb->fetch(
                    array('Key'),
                    array(
                        'Condition' => $geo_format['Key'],
                        'Status'    => 'active',
                    ),
                    "AND `Key` NOT LIKE 'citizenship%' ORDER BY `Key`",
                    null,
                    'listing_fields'
                );
                $GLOBALS['rlSmarty']->assign('geo_fields', $fields);
            }
        }
    }

    /**
     * Post data simulation
     *
     * @since 4.0.0
     * @hook apPhpListingsPost
     */
    public function hookApPhpListingsPost()
    {
        global $listing;

        $_POST['f']['lf'] = array(
            'lat'  => $listing['Loc_latitude'],
            'lng'  => $listing['Loc_longitude'],
            'zoom' => $listing['lf_zoom'],
            'use'  => $listing['lf_use'],
        );
    }

    /**
     * Update location data in the listing
     *
     * @since 4.0.0
     * @hook apPhpListingsAfterEdit
     */
    public function hookApPhpListingsAfterEdit()
    {
        global $reefless, $listing_id, $data;

        $reefless->loadClass('LocationFinder', false, 'locationFinder');
        $GLOBALS['rlLocationFinder']->assignLocation($listing_id, $data);
    }

    /**
     * Update location data in the listing
     *
     * @since 4.0.0
     * @hook apPhpListingsAfterAdd
     */
    public function hookApPhpListingsAfterAdd()
    {
        global $reefless, $listing_id, $data;

        $reefless->loadClass('LocationFinder', false, 'locationFinder');
        $GLOBALS['rlLocationFinder']->assignLocation($listing_id, $data);
    }

    /**
     * Prepare plugin configs values
     *
     * @since 4.0.0
     * @hook apMixConfigItem
     *
     * @param array $option - config item
     */
    public function hookApMixConfigItem(&$option)
    {
        global $rlDb, $lang, $rlLang;

        if ($option['Plugin'] != 'locationFinder') {
            return;
        }

        switch ($option['Key']) {
            case 'locationFinder_mapping_country':
            case 'locationFinder_mapping_state':
            case 'locationFinder_mapping_city':
                $rlDb->setTable('listing_fields');
                $option['Values'] = array();

                foreach ($rlDb->fetch(array('Key'), array('Status' => 'active', 'Map' => '1'), "AND `Type` IN ('text','select')") as $item) {
                    $option['Values'][] = array('ID' => $item['Key'], 'name' => $lang['listing_fields+name+'.$item['Key'] ]);
                }
                break;

            case 'locationFinder_position':
                $option['Values'] = array(
                    array(
                        'ID' => 'top',
                        'name' => $lang['locationFinder_form_top']
                    ),
                    array(
                        'ID' => 'bottom',
                        'name' => $lang['locationFinder_form_bottom']
                    ),
                    array(
                        'ID' => 'in_group',
                        'name' => $lang['locationFinder_place_in_form']
                    )
                );
                break;

            case 'locationFinder_type':
                $option['Values'] = array('prepend', 'append');
                $option['Display'] = array($lang['locationFinder_prepend'], $lang['locationFinder_append']);
                break;

            case 'locationFinder_group':
                $rlDb->setTable('listing_groups');
                $groups = $rlDb->fetch(array('Key`, `Key` AS `ID'), array('Status' => 'active'));
                $option['Values'] = $rlLang->replaceLangKeys($groups, 'listing_groups', array('name'), RL_LANG_CODE, 'admin');
                break;

            case 'locationFinder_map_zoom':
                $option['Values'] = array();

                $map = array(
                    1  => 'zoom_world',
                    11 => 'zoom_city',
                    19 => 'zoom_street',
                );

                foreach (range(1, 19) as $item) {
                    $set_name = in_array($item, array(1, 11, 19))
                    ? $item . ' (' . $lang[$map[$item]] . ')'
                    : $item;
                    $option['Values'][] = array('ID' => $item, 'name' => $set_name);
                }
                break;

            case 'locationFinder_mapping':
                if (isset($GLOBALS['config']['geocoding_provider']) && $GLOBALS['config']['geocoding_provider'] != 'google') {
                    $option['Default'] = 0;
                    $GLOBALS['rlSmarty']->assign('disallow_sync', true);
                }
                break;
        }
    }

    /**
     * Include configs js handlers
     *
     * @since 4.0.0
     * @hook apTplContentBottom
     */
    public function hookApTplContentBottom()
    {
        global $config, $cInfo;

        if ($cInfo['Key'] == 'config') {
            $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'locationFinder' . RL_DS . 'admin' . RL_DS . 'js.tpl');
        }
    }

    /**
     * Include config styles
     *
     * @since 4.0.0
     * @hook apTplHeader
     */
    public function hookApTplHeader()
    {
        global $cInfo;

        if ($cInfo['Controller'] == 'locationFinder') {
            echo '<link type="text/css" rel="stylesheet" href="' . RL_PLUGINS_URL . 'locationFinder/static/apStyle.css" />';
        } elseif ($cInfo['Controller'] == 'listings') {
            echo <<< HTML
            <style>
            .lf-location-search {
                right: 50px !important;
                bottom: 23px !important;
                width: 300px !important;
                max-width: 70%;
            }
            </style>
HTML;
        }
    }

    /**
     * Admin Panel ajax requests handler
     *
     * @since 4.0.0
     * @hook apAjaxRequest
     */
    public function hookApAjaxRequest(&$out, $item)
    {
        global $lang, $reefless;

        $errors = false;
        $files_dir = RL_UPLOAD . 'locationFinder' . RL_DS;

        $item = $item ?: $_REQUEST['mode'];

        switch ($item) {
            case 'locationFinder':
                $reefless->loadClass('LocationFinder', false, 'locationFinder');
                $GLOBALS['rlLocationFinder']->hookAjaxRequest($out, $item);
                break;

            case 'locationFinderGetMapping':
                if ($mapping_data = $this->getMappingDataByKey($_REQUEST['key'])) {
                    $out['status'] = 'OK';
                    $out['results'] = $mapping_data;
                } else {
                    $out['status'] = 'ERROR';
                    $out['message'] = ''; // no message required in this case
                }
                break;

            case 'locationFinderSaveMapping':
                if ($this->saveMappingData(
                    $_REQUEST['formatKey'],
                    $_REQUEST['cityPlaceID'],
                    $_REQUEST['neighborhoodPlaceID'],
                    $_REQUEST['target'],
                    $_REQUEST['lat'],
                    $_REQUEST['lng']
                )) {
                    $out['status'] = 'OK';
                } else {
                    $out['status'] = 'ERROR';
                    $out['message'] = ''; // no message required in this case
                }
                break;

            case 'locationFinderCheckUpdate':
                $response = $reefless->getPageContent($this->getServerURL());
                if ($response) {
                    $data = json_decode($response, true);
                    $out['status'] = 'OK';
                    $out['data'] = $data;
                } else {
                    $this->errorLog($lang['flynax_connect_fail'], $errors, __LINE__);
                }
                break;

            case 'locationFinderPrepare':
                // create the directory
                $reefless->rlMkdir($files_dir);

                // check for dir
                if (!is_writable($files_dir)) {
                    $this->errorLog('Unable to create directory in "<b>' . $files_dir . '</b>", make sure the directory has writable permisitions.', $errors, __LINE__);
                }

                // download files
                if (!$errors) {
                    $response = $reefless->getPageContent($this->getServerURL());
                    if ($response) {
                        $_SESSION['locationFinder'] = array(
                            'server_data' => json_decode($response, true),
                            'file_number' => 1
                        );
                    } else {
                        $this->errorLog($lang['flynax_connect_fail'], $errors, __LINE__);
                    }
                }

                // prepare response
                if (!$errors) {
                    $out = array(
                        'status' => 'OK',
                        'data' => json_decode($response, true)
                    );
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'data' => $errors
                    );
                }
                break;

            case 'locationFinderUploadFile':
                $file_number = (int) $_REQUEST['file'];

                $file_name = 'part' . $file_number . '.sql';
                $source = $_SESSION['locationFinder']['server_data']['base_url'] . $file_name . '.gz';
                $destination = $files_dir . $file_name . '.gz';

                if ($reefless->copyRemoteFile($source, $destination)) {
                    // unzip file
                    if (!$this->gUnZip($destination)) {
                        $this->errorLog('Unable to ungzip the archive "<b>' . $destination . '</b>" gzopen() method failed, please contact Flynax Support.', $errors, __LINE__);
                    } else {
                        unlink($destination);
                    }

                    if (!$errors) {
                        $_SESSION['locationFinder']['current_file'] = $file_name;
                        $_SESSION['locationFinder']['current_file_number'] = $file_number;

                        // count current file lines
                        $_SESSION['extra_dumps_lines'] = $this->countFileLines($files_dir . $file_name);

                        if ($file_number == 1) {
                            // clear dump data
                            $this->clearDumpData();
                        }
                    }
                } else {
                    $this->errorLog('Unable to copy file "<b>' . $source . '</b>" from Flynax server, please try later or contact Flynax Support.', $errors, __LINE__);
                }

                // prepare response
                if (!$errors) {
                    $out = array(
                        'status' => 'OK',
                        'data' => ''
                    );
                } else {
                    $out = array(
                        'status' => 'ERROR',
                        'data' => $errors
                    );
                }
                break;

            case 'locationFinderImport':
                $dump_file = $files_dir . $_SESSION['locationFinder']['current_file'];

                if (is_readable($dump_file)) {
                    $out = $this->importDump($dump_file);
                } else {
                    $out = array('error' => "Can not find/read SQL dump: {$dump_file}, please contact Flynax support");
                }
                break;
        }
    }

    /**
     * Display plugin option in listing type form
     *
     * @since 4.0.0
     * @hook apTplListingTypesForm
     */
    public function hookApTplListingTypesForm()
    {
        $GLOBALS['rlSmarty']->display(RL_PLUGINS . 'locationFinder' . RL_DS . 'admin' . RL_DS . 'row.tpl');
    }

    /**
     * Simulate post data
     *
     * @since 4.0.0
     * @hook apPhpListingTypesPost
     */
    public function hookApPhpListingTypesPost()
    {
        $_POST['location_finder'] = $GLOBALS['type_info']['Location_finder'];
    }

    /**
     * Assign post data
     *
     * @since 4.0.0
     * @hook apPhpListingTypesBeforeAdd
     */
    public function hookApPhpListingTypesBeforeAdd()
    {
        $GLOBALS['data']['Location_finder'] = (int) $_POST['location_finder'];
    }

    /**
     * Assign post data
     *
     * @since 4.0.0
     * @hook apPhpListingTypesBeforeEdit
     */
    public function hookApPhpListingTypesBeforeEdit()
    {
        $GLOBALS['update_date']['fields']['Location_finder'] = (int) $_POST['location_finder'];
    }

    /**
     * Remove '- Select -' option from settings
     *
     * @since 5.0.0
     * @hook apPhpConfigBottom
     */
    public function hookApPhpConfigBottom()
    {
        global $rlSmarty;

        $rlSmarty->_tpl_vars['systemSelects'] = array_merge(
            $rlSmarty->_tpl_vars['systemSelects'],
            array('locationFinder_map_zoom', 'locationFinder_position')
        );
    }

    /**
     * Set default location coordinates depending on the search location value
     *
     * @since 4.0.0
     * @hook apPhpConfigBeforeUpdate
     */
    public function hookApPhpConfigBeforeUpdate()
    {
        global $update, $config;

        $set_value = $_POST['post_config']['locationFinder_search']['value']
        ? $_POST['locationFinder_default_location']
        : '';
        $row['where']['Key'] = 'locationFinder_default_location';
        $row['fields']['Default'] = $set_value;
        array_push($update, $row);
    }

    /**
     * Create `geo_mapping` table
     *
     * @since 4.0.0
     * @return bool - success status
     */
    public function createMainTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `{db_prefix}geo_mapping` (
              `ID` int(11) NOT NULL AUTO_INCREMENT,
              `Format_key` varchar(255) NOT NULL,
              `Place_ID` varchar(128) DEFAULT NULL,
              `Lat` double NOT NULL,
              `Lng` double NOT NULL,
              `Target` enum('region','city') NOT NULL DEFAULT 'city',
              `Verified` enum('1','0') NOT NULL DEFAULT '1',
              PRIMARY KEY (`ID`),
              KEY `Format_key` (`Format_key`),
              KEY `Verified` (`Verified`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";

        return $GLOBALS['rlDb']->query($sql);
    }

    /**
     * Build server URL
     *
     * @since 4.0.0
     * @return string - server URL
     */
    private function getServerURL()
    {
        $plugin_version = $GLOBALS['rlDb']->getOne('Version', "`Key` = 'locationFinder'", 'plugins');
        $update = '&update=1&version='. $GLOBALS['config']['locationFinder_db_version'];
        $update .= '&plugin_version=' . $plugin_version;
        $update .= '&mf_db_version=' . $GLOBALS['config']['mf_db_version'];

        return $this->server . $update;
    }
}
