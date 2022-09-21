<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: UTIL.PHP
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

namespace Flynax\Utils;

use Symfony\Component\Intl\Countries;

/**
 * @since 4.6.0
 */
class Util
{
    /**
     * @since 4.8.0
     * @var array
     */
    protected static $pages = [];

    /**
     * Redirect to the url
     *
     * @since 4.7.0 - Added the $http_response_code parameter
     *
     * @param string  $url                - url to redirect
     * @param boolean $exit               - is exit after redirect flag
     * @param integer $http_response_code - HTTP response code
     */
    public static function redirect($url, $exit = true, $http_response_code = 301)
    {
        global $rlHook;

        if (!$url) {
            return;
        }

        /**
         * @since 4.7.0 - Added the $exit and $http_response_code parameters
         * @since 4.6.0
         */
        $rlHook->load('utilsRedirectURL', $url, $exit, $http_response_code);

        header("Location: {$url}", true, $http_response_code);

        if ($exit) {
            exit;
        }
    }

    /**
     * Sort associative array by item
     *
     * @param array    - array to sort
     * @param string   - field name to sort by
     * @param constant - sorting type (array_multisort() function default params)
     *
     **/
    public static function arraySort(&$array, $field, $sort_type = SORT_ASC)
    {
        if (!$array || !$field) {
            return $array;
        }

        foreach ($array as &$value) {
            $sort[] = strtolower($value[$field]);
        }

        array_multisort($sort, $sort_type, $array);
        unset($sort);
    }

    /**
     * Get client IP address
     *
     * @return string - IP
     */
    public static function getClientIP()
    {
        static $clientIP = null;

        if (is_null($clientIP)) {
            $potential_keys = array(
                'HTTP_X_REAL_IP',
                'HTTP_CLIENT_IP',
                'REMOTE_ADDR',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
            );

            foreach ($potential_keys as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        if (filter_var($ip, FILTER_VALIDATE_IP)) {
                            $clientIP = $ip;
                            break 2; // Exit both foreach
                        }
                    }
                }
            }
        }

        return $clientIP;
    }

    /**
     * Get page key by url
     *
     * @param  string $url    - requested page url
     * @param  array  &$pages - pages mapping data (key => path)
     * @return string         - requested page key
     */
    public static function getPageKeyFromURL($url, &$pages)
    {
        if (!$url) {
            return false;
        }

        $path = false;
        $url = str_replace(RL_URL_HOME, '', $url);
        $pattern = $GLOBALS['config']['mod_rewrite'] ? '/^([^\/]+)/' : '/page\=([^\=\&]+)/';

        preg_match($pattern, $url, $matches);

        if ($matches[1]) {
            $path = $matches[1];

            if (is_array($pages) && $key = array_search($path, $pages)) {
                return $key;
            } else {
                return $GLOBALS['rlDb']->getOne('Key', "`Path` = '{$path}'", 'pages');
            }
        } else {
            return false;
        }
    }

    /**
     * Get content by URL
     *
     * @param string $url     - source url
     * @param int $time_limit - time limit
     *
     * @return string - content
     **/
    public static function getContent($url, $time_limit = 10)
    {
        $content = null;
        $user_agent = 'Flynax Bot';

        if (extension_loaded('curl')) {
            $ch = curl_init();

            // localhost usage mode
            if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time_limit);
            curl_setopt($ch, CURLOPT_TIMEOUT, $time_limit);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_REFERER, RL_URL_HOME);
            $content = curl_exec($ch);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $default = ini_set('default_socket_timeout', $time_limit);
            $stream = fopen($url, "r");
            ini_set('default_socket_timeout', $default);

            if ($stream) {
                while (!feof($stream)) {
                    $content .= fgets($stream, 4096);
                }
                fclose($stream);
            }
        } else {
            $GLOBALS['rlDebug']->logger("Unable to get content from: {$url}");
            return 'Unable to get content from: ' . $url;
        }

        return $content;
    }

    /**
     * Prepare error response and write it to logs
     *
     * @since 4.6.1
     *
     * @param  string $msg - error message
     * @param  bool   $log - write the message to the logs
     * @return array       - error response
     */
    public static function errorResponse($msg, $log = true)
    {
        if ($log && $msg && is_object($GLOBALS['rlDebug'])) {
            $GLOBALS['rlDebug']->logger($msg);
        }

        $msg = $msg ?: 'No error response message specified';

        return array(
            'status'  => 'ERROR',
            'message' => $msg,
        );
    }

    /**
     * Convert string size to bytes, etc: 2M to 2097152
     *
     * @since 4.6.1
     *
     * @param  string $size - Size string
     * @return integer      - Converted size
     */
    public static function stringToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    /**
     * Get upload max file size depending of the server limits
     *
     * @since 4.6.1
     *
     * @return integer - Max upload size bytes
     */
    public static function getMaxFileUploadSize()
    {
        return min(
            self::stringToBytes(ini_get('post_max_size')),
            self::stringToBytes(ini_get('upload_max_filesize'))
        );
    }

    /**
     * Generate random number with selected length
     *
     * @since 4.7.0
     *
     * @param  int $length        - Length of needed number
     * @param  int $excluded_rand - Excluded number from result
     * @return int                - Random number
     */
    public static function getRandomNumber($length = 3, $excluded_rand = 0)
    {
        $rand = '';

        for ($i = 1; $i <= $length; $i++) {
            $rand .= mt_rand($i > 1 ? 0 : 1, 9);
        }

        $rand          = (int) $rand;
        $excluded_rand = (int) $excluded_rand;

        if ($excluded_rand && $excluded_rand === $rand) {
            return self::getRandomNumber($length, $excluded_rand);
        }

        return $rand;
    }

    /**
     * Get list of pages
     *
     * @since 4.7.2 - Added $customLang, $force parameter
     * @since 4.7.1
     *
     * @param string $select        - List of necessary data
     * @param array  $where         - Condition of selection pages from database
     * @param string $options       - Additional SQL condition, like "ORDER BY" and etc.
     * @param array  $outputRowsMap - Mapping in output ['Key' => 'Value']
     * @param string $customLang    - Force to get multilingual paths by necessary language
     * @param bool   $force         - Force to get multilingual paths from database
     *
     * @return array
     */
    public static function getPages(
        $select        = '*',
        $where         = [],
        $options       = '',
        $outputRowsMap = [],
        $customLang    = '',
        $force         = false
    ) {
        global $rlDb, $config;

        $customLang = $customLang ?: (RL_LANG_CODE ?: $config['lang']);
        $condition  = array_merge(
            (array) $select,
            (array) $where,
            (array) $options,
            (array) $outputRowsMap,
            ['lang' => $customLang]
        );
        $condition = json_encode($condition);

        if (self::$pages[$condition] && !$force) {
            return self::$pages[$condition];
        }

        $additionalSelect = '';

        if ($config['multilingual_paths']
            && $customLang !== $config['lang']
            && $pathSelectIndex = array_search('Path', $select)
        ) {
            unset($select[$pathSelectIndex]);
            $additionalSelect = ", IF(`Path_{$customLang}` <> '', `Path_{$customLang}`, `Path`) AS `Path`";
        }

        /**
         * @since 4.8.1 - Added $additionalSelect parameter
         */
        $GLOBALS['rlHook']->load('phpGetPages', $select, $where, $options, $outputRowsMap, $additionalSelect);

        $sql = 'SELECT `' . implode('`, `', $select) . "`{$additionalSelect} FROM `{db_prefix}pages` ";

        $sql .= 'WHERE ';
        foreach ($where as $field => $value) {
            Valid::escape($value);
            $sql .= " (`{$field}` = '{$value}') AND";
        }
        $sql = substr($sql, 0, -3);

        if ($options !== null) {
            $sql .= ' ' . $options . ' ';
        }

        $pages = $rlDb->getAll($sql, $outputRowsMap);

        return self::$pages[$condition] = $pages;
    }

    /**
     * Get pages with "Active" status only
     *
     * @since 4.7.1
     *
     * @return array
     */
    public static function getActivePages()
    {
        return self::getPages('*', array('Status' => 'active'));
    }

    /**
     * Get pages with "Active" status and low priority in system.
     * They are not required the login of users and from list excluded major system pages.
     *
     * @since 4.7.1
     *
     * @return array
     */
    public static function getMinorPages()
    {
        $select       = array('ID', 'Key');
        $where        = array('Status' => 'active', 'Login'  => '0');
        $excludedKeys = array(
            'add_listing',
            'edit_listing',
            'remind',
            'confirm',
            'payment',
            'upgrade_listing',
            'listing_remove',
            'payment_history',
            '404',
            'view_details',
            'my_favorites',
            'print',
            'rss_feed',
        );

        $GLOBALS['rlHook']->load('phpGetMinorPages', $select, $where, $excludedKeys);

        return self::getPages($select, $where, "AND `Key` NOT IN ('" . implode("', '", $excludedKeys) . "')");
    }

    /**
     * Get location data by location query
     *
     * @since 4.8.0
     *
     * @param  mixed  $query          - Location search as a string, ex: "San Francisco, FL", or as an array with possible keys:
     *                                  REVERSE LOOKUP
     *                                  * latlng     - latitude and longitude as string '30.3390,10.9870' or as an array [30.3390,10.9870]
     *
     *                                  FILTRATION
     *                                  * country    - filter results by country or country code
     *                                  * state      - filter results by state
     *                                  * county     - filter results by county
     *                                  * city       - filter results by city
     *                                  * street     - filter results by street
     *                                  * postalcode - filter results by postal code
     *                                  * query      - additional filtration by query but nominatim recommend against combine it with other filters
     *
     * @param  bool   $addressDetails - Include address details data
     * @param  string $lang           - Response data language, ex: "en" or "en_GB"
     * @param  string $provider       - Force service provider, available: 'nominatim', 'google' and 'googlePlaces', googlePlaces case uses for js place autocomplete only
     * @return array                  - Location data (single or multiple levels), [0] index contains the most accurate data
     */
    public static function geocoding($query = null, $addressDetails = false, $lang = null, $provider = null)
    {
        global $config, $rlConfig;

        if (!$query) {
            return [];
        }

        if ($config['geocoding_restrict_by_country']) {
            if (is_string($query)) {
                $query = [
                    'query'   => $query,
                    'country' => $config['geocoding_restrict_by_country'],
                ];
            } elseif (is_array($query) && !$query['country']) {
                $query['country'] = $config['geocoding_restrict_by_country'];
            }
        }

        // Google => Nominatim location keys mapping
        $google_mapping = array(
            'route'                       => 'road',
            'neighborhood'                => 'suburb',
            'locality'                    => 'city',
            'administrative_area_level_2' => 'county',
            'administrative_area_level_1' => 'state',
            'postal_code'                 => 'postalcode',
        );

        // ArcGis => Nominatim location keys mapping
        $arcgis_mapping = array(
            'StAddr'                      => 'road',
            'District'                    => 'suburb',
            'City'                        => 'city',
            'Subregion'                   => 'county',
            'Region'                      => 'state',
            'Country'                     => 'country',
            'Postal'                      => 'postalcode',
        );

        $lang    = $lang ?: $config['lang'];
        $service = in_array($provider, array('arcgis', 'nominatim', 'google', 'googlePlaces'))
        ? $provider
        : $config['geocoding_provider'];

        switch ($service) {
            case 'arcgis':
            default:
                $host = 'https://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/';
                $mode = 'findAddressCandidates';

                $params = array(
                    'f' => 'json',
                    'outFields'    => $addressDetails ? 'Addr_type,LongLabel,PlaceName,Type,Country,District,City,Postal' : 'LongLabel',
                    'langCode'     => $lang,
                    'maxLocations' => 5,
                );

                if (isset($query['latlng'])) {
                    $mode   = 'reverseGeocode';
                    $latlng = strpos($query['latlng'], ',') ? explode(',', $query['latlng']) : $query['latlng'];
                    $params['location'] = implode(',', array_reverse($latlng));

                    unset($params['outFields']);
                } elseif (is_array($query)) {
                    if ($query['query']) {
                        $params['singleLine'] = $query['query'];
                        unset($query['query']);
                    }
                    if ($query['country']) {
                        $params['countryCode'] = $query['country'];
                        unset($query['country']);
                    }

                    foreach ($query as $key => $value) {
                        $index = array_search($key, $arcgis_mapping) ?: $key;
                        $params[$index] = $value;
                    }
                } else {
                    $params['singleLine'] = $query;
                }

                $host .= $mode;
                break;

            case 'nominatim':
                $host   = 'https://nominatim.openstreetmap.org/';
                $mode   = 'search';
                $params = array(
                    'format'          => 'json',
                    'addressdetails'  => $addressDetails ? 1 : 0,
                    'accept-language' => $lang,
                );

                if (isset($query['latlng'])) {
                    $mode   = 'reverse';
                    $latlng = strpos($query['latlng'], ',') ? explode(',', $query['latlng']) : $query['latlng'];

                    list($params['lat'], $params['lon']) = array_map('trim', $latlng);
                } elseif (is_array($query)) {
                    if ($query['country']) {
                        $params['countrycodes'] = $query['country'];
                        unset($query['country']);
                    }

                    if (count($query) > 1 && $query['query']) {
                        unset($query['query']); // Combining with q parameter is not allowed
                    } else {
                       $params['q'] = $query['query'];
                       unset($query['query']);
                    }

                    $params = array_merge($params, $query);
                } else {
                    $params['q'] = $query;
                }

                $host .= $mode;
                break;

            case 'google':
                $host   = 'https://maps.googleapis.com/maps/api/geocode/json';
                $params = array(
                    'key'      => $config['google_server_map_key'],
                    'language' => $lang
                );

                if (isset($query['latlng'])) {
                    $latlng = is_array($query['latlng']) ? implode(',', $query['latlng']) : $query['latlng'];
                    $params['latlng'] = $latlng;
                } elseif (is_array($query)) {
                    if ($query['query']) {
                        $params['address'] = $query['query'];
                        unset($query['query']);
                    }

                    foreach ($query as $key => $value) {
                        $index = array_search($key, $google_mapping) ?: $key;
                        $filtes[$index] = $value;
                    }

                    $params['components'] = str_replace('=', ':', http_build_query($filtes, '', '|'));
                } else {
                    $params['address'] = $query;
                }
                break;

            case 'googlePlaces':
                $host = 'https://maps.googleapis.com/maps/api/place/autocomplete/json';
                $params = array(
                    'key'      => $config['google_server_map_key'],
                    'language' => $lang,
                );

                if (is_array($query)) {
                    if ($query['query']) {
                        $params['input'] = $query['query'];
                        unset($query['query']);
                    }

                    $filters = [];
                    foreach ($query as $key => $value) {
                        $index = array_search($key, $google_mapping) ?: $key;
                        $filters[$index] = $value;
                    }

                    if ($filters) {
                        $params['components'] = str_replace('=', ':', http_build_query($filters, '', '|'));
                    }
                } else {
                    $params['input'] = $query;
                }
                break;
        }

        $request  = $host . '?' . http_build_query($params);

        if ($service == 'nominatim') {
            $url_chars = array('%23' => '#');
            $request = strtr($request, $url_chars);
        }

        $response = self::getContent($request);

        if (!$response) {
            return [];
        }

        $data = json_decode($response);

        if (!$data) {
            return [];
        }

        $out = [];

        // Prepare data
        switch ($service) {
            case 'arcgis':
            default:
                $attr = $mode == 'reverseGeocode' ? $data->address : '';
                $data = $mode == 'reverseGeocode' ? array($data) : $data->candidates;

                foreach ($data as $location) {
                    if ($mode == 'findAddressCandidates') {
                        $attr = $location->attributes;
                    }

                    if ($addressDetails) {
                        foreach ($attr as $key => $item) {
                            if (!$item) {
                                continue;
                            }

                            $index = $arcgis_mapping[$key] ?: $key;
                            $address[$index] = $item;

                            if ($key == 'Country') {
                                $code = strtolower($item);
                            }
                        }

                        if ($code) {
                            $address['country_code'] = $code;
                        }
                    }

                    $out[] = array(
                        'place_id' => '',
                        'lat'      => $location->location->y,
                        'lng'      => $location->location->x,
                        'type'     => $attr->Addr_type,
                        'location' => $attr->LongLabel ?: $location->address,
                        'address'  => $address
                    );

                    unset($address, $code);
                }

                break;

            case 'nominatim':
                // Add [0] index in reverse mode to foreach the data properly
                $data = $mode == 'reverse' ? array($data) : $data;

                foreach ($data as $location) {
                    $out[] = array(
                        'place_id' => $location->place_id,
                        'lat'      => $location->lat,
                        'lng'      => $location->lon,
                        'type'     => $location->type,
                        'location' => $location->display_name,
                        'address'  => get_object_vars($location->address)
                    );
                }
                break;

            case 'google':
                if (strtolower($data->status) == 'ok') {
                    if ($config['geocode_request_limit_reached']) {
                        $rlConfig->setConfig('geocode_request_limit_reached', '');
                    }

                    foreach ($data->results as $location) {
                        if ($addressDetails) {
                            foreach ($location->address_components as $item) {
                                $index = $google_mapping[$item->types[0]] ?: $item->types[0];
                                $address[$index] = $item->long_name;

                                if ($item->types[0] == 'country') {
                                    $code = strtolower($item->short_name);
                                }
                            }

                            if ($code) {
                                $address['country_code'] = $code;
                            }
                        }

                        $out[] = array(
                            'place_id' => $location->place_id,
                            'lat'      => $location->geometry->location->lat,
                            'lng'      => $location->geometry->location->lng,
                            'type'     => $google_mapping[$location->types[0]] ?: $location->types[0],
                            'location' => $location->address_components[0]->long_name,
                            'address'  => $address
                        );

                        unset($address, $code);
                    }
                } else {
                    $save_log = false;

                    if ($data->status == 'OVER_QUERY_LIMIT') {
                        $rlConfig->setConfig('geocode_request_limit_reached', '1');

                        if (!$config['geocode_request_limit_reached']) {
                            $config['geocode_request_limit_reached'] = 1;
                            $save_log = true;
                        }
                    } else {
                        $save_log = true;
                    }

                    if ($save_log) {
                        $error_message = 'Google Geocoding API request failed with status: "' . $data->status . '", ';
                        $error_message .= 'message: "' . $data->error_message . '"';
                        $GLOBALS['rlDebug']->logger($error_message);
                    }
                }
                break;

            case 'googlePlaces':
                if (strtolower($data->status) == 'ok') {
                    foreach ($data->predictions as $location) {
                        $out[] = array(
                            'place_id' => $location->place_id,
                            'lat'      => '',
                            'lng'      => '',
                            'type'     => $google_mapping[$location->types[0]] ?: $location->types[0],
                            'location' => $location->description,
                            'address'  => ''
                        );

                        unset($address, $code);
                    }
                }
                break;
        }

        return $out;
    }

    /**
     * parse_url() function for multi-bytes character encodings
     *
     * @since 4.8.1
     *
     * @param  string $url       - Url to parse
     * @param  int    $component - Components to retrieve
     * @return array             - Parsed url data
     */
    public static function parseURL($url, $component = -1)
    {
        $encodedUrl = preg_replace_callback('%[^:/@?&=#]+%usD', function($matches) {
            return urlencode($matches[0]);
        }, $url);

        $parts = parse_url($encodedUrl, $component);

        if (is_array($parts) && count($parts) > 0) {
            foreach ($parts as $name => $value) {
                $parts[$name] = urldecode($value);
            }
        }

        return $parts;
    }

    /**
     * Convert idn host to utf8 format
     *
     * @since 4.8.1
     *
     * @param  string $host - Host to convert
     * @return string       - Converted host
     */
    public static function idnToUtf8($host = '')
    {
        if (!$host = (string) $host) {
            return $host;
        }

        if (defined('INTL_IDNA_VARIANT_UTS46')) {
            $host = idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        } else {
            $host = idn_to_utf8($host);
        }

        return $host;
    }

    /**
     * Get countries list with ISO codes
     *
     * @since 4.8.2
     *
     * @param  string $lang
     * @return array
     */
    public static function getCountries($lang = 'en')
    {
        return Countries::getNames($lang);
    }

    /**
     * Indicator of the request from the Google Page Speed Insights / Lighthouse
     *
     * @since 4.9.0
     *
     * @return bool
     */
    public static function isLighthouseRequest()
    {
        $googleUserAgents = ['Chrome-Lighthouse', 'Speed Insights'];

        foreach ($googleUserAgents as $googleUserAgent) {
            if (false !== strpos($_SERVER['HTTP_USER_AGENT'], $googleUserAgent)) {
                return true;
            }
        }

        return false;
    }
}
