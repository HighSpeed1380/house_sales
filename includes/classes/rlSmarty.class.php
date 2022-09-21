<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLSMARTY.CLASS.PHP
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

class rlSmarty extends Smarty
{
    /**
     * class constructor
     **/
    public function __construct()
    {
        global $config;

        //$this -> force_compile = true;

        if (defined('REALM')) {
            $this->template_dir = RL_ROOT . ADMIN_DIR . 'tpl' . RL_DS;
            $this->compile_dir = RL_TMP . 'aCompile';
        } else {
            define('FL_TPL_ROOT', RL_ROOT . 'templates' . RL_DS . $config['template'] . RL_DS);

            $this->template_dir = FL_TPL_ROOT . 'tpl' . RL_DS;
            $this->compile_dir = RL_TMP . 'compile';

            define('FL_TPL_CONTROLLER_DIR', FL_TPL_ROOT . 'controllers' . RL_DS);
            define('FL_TPL_COMPONENT_DIR', FL_TPL_ROOT . 'components' . RL_DS);

            $this->assign('controllerDir', FL_TPL_CONTROLLER_DIR);
            $this->assign('componentDir', FL_TPL_COMPONENT_DIR);
        }

        $this->cache_dir = RL_TMP . 'cache';

        // Register custom functions
        $this->register_function('customFieldHandler', [$this, 'customFieldHandler']);
    }

    /**
     * rewrite method to fix resource_name path taking into account the template core requests
     *
     * @since 4.5.2
     */
    public function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false)
    {
        $this->fixURI($resource_name);

        return parent::fetch($resource_name, $cache_id, $compile_id, $display);
    }

    /**
     * rewrite method to fix $params['smarty_include_tpl_file'] path taking into account the template core requests
     *
     * @since 4.5.2
     */
    public function _smarty_include($params)
    {
        $this->newCoreFix($params['smarty_include_tpl_file']);
        $this->fixURI($params['smarty_include_tpl_file']);

        parent::_smarty_include($params);
    }

    /**
     * fix the file path and replace default template with template core of the requested file doesn't exists
     *
     * @since 4.5.2
     *
     * @param string - requested file
     */
    private function fixURI(&$file)
    {
        if (defined('TPL_CORE')) {
            if (defined('REALM') && !strpos($file, $GLOBALS['config']['template'])) {
                return;
            }

            if (strpos($file, RL_ROOT) !== 0) {
                $file = $this->template_dir . $file;
            }

            if (!file_exists($file)) {
                $file = str_replace($GLOBALS['config']['template'], 'template_core', $file);
            }
        }
    }

    /**
     * Fix the new core file include, now the controller takes from controllers directory placed in
     * the template root.
     *
     * @since 4.6.0
     *
     * @param string &$file - path of file to be included
     */
    private function newCoreFix(&$file)
    {
        if (defined('REALM')) {
            return;
        }

        if (strpos($file, 'controllers' . RL_DS) === 0) {
            preg_match('/([^' . preg_quote(RL_DS, RL_DS) . ']+).tpl$/', $file, $matches);

            $replace = FL_TPL_ROOT . 'controllers' . RL_DS . $matches[1] . RL_DS;
            $new_file = str_replace('controllers' . RL_DS, $replace, $file);

            if (file_exists($new_file)
                || file_exists(str_replace($GLOBALS['config']['template'], 'template_core', $new_file))) {
                $file = $new_file;
            }
        }
    }

    /**
     * Create text section with the FCKEditor
     *
     * @param array $aParams - Editor options [name,width,height,value]
     */
    public function fckEditor($aParams)
    {
        global $rlSmarty;

        $rlSmarty->fckEditorJsLoad  = !$rlSmarty->fckEditorJsLoad ? true : false;
        $aParams['fckEditorJsLoad'] = $rlSmarty->fckEditorJsLoad;

        $rlSmarty->assign_by_ref('fckEditorParams', $aParams);
        $rlSmarty->display('blocks/fckEditor.tpl');
    }

    /**
     * Convert string to url path
     *
     * @param array $aParams - string
     */
    public function str2path($aParams)
    {
        $string = is_array($aParams) ? $aParams['string'] : $aParams;
        $string = $GLOBALS['rlValid']->str2path($string);

        return $string;
    }

    /**
     * Convert int format to money format
     *
     * @since 4.7.1 - Added "showCents" parameter (optional)
     *
     * @param array $aParams - String with numbers & Handler for show/hide of cents
     */
    public function str2money($aParams)
    {
        $string    = is_array($aParams) ? $aParams['string'] : $aParams;
        $showCents = is_array($aParams) && isset($aParams['showCents']) ? (bool) $aParams['showCents'] : null;

        $GLOBALS['reefless']->loadClass('Valid');

        return $GLOBALS['rlValid']->str2money($string, $showCents);
    }

    /**
     * Build paging block
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param int    $calc       - Calculated items
     *                       - @param int    $total      - Total items
     *                       - @param int    $per_page   - Per page items number (name maybe $per_page or $perPage)
     *                       - @param string $url        - Additional url
     *                       - @param string $var        - Non mod_rewrite mod variable
     *                       - @param string $controller - Controller name
     *                       - @param string $method     - Variables transfer method
     *                       - @param string $custom     - Custom url
     */
    public function paging($aParams)
    {
        global $rlSmarty;

        $calc = $aParams['calc'];
        $total = is_array($aParams['total']) ? count($aParams['total']) : $aParams['total'];
        $pagination_tpl = FL_TPL_COMPONENT_DIR . 'pagination/pagination.tpl';

        $rlSmarty->fixURI($pagination_tpl);

        // return if the tpl file doesn't exist
        if (!is_file($pagination_tpl) && $calc > $total) {
            echo "No pagination.tpl file found";
            return;
        }

        $per_page = $aParams['perPage'] ?: $aParams['per_page'];
        $aParams['pages'] = ceil($calc / $per_page);
        $aParams['current'] = $aParams['current'] == 0 ? 1 : $aParams['current'];
        $add_url = $aParams['url'];
        $custom = $aParams['custom'];
        $custom_subdomain = $aParams['customSubdomain'];

        // return if there is just one page
        if ($aParams['pages'] <= 1) {
            return;
        }

        $method = $aParams['method'];

        $paging_tpls = $GLOBALS['rlCommon']->buildPagingUrlTemplate($add_url, $custom, $method, $aParams['var'], $custom_subdomain);

        $aParams['first_url'] = $paging_tpls['first'];
        $aParams['tpl_url'] = $paging_tpls['tpl'];

        // display tpl pagination file
        $rlSmarty->assign('pagination', $aParams);
        $rlSmarty->display($pagination_tpl);
    }

    /**
     * Search form building
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $key    - Form key
     *                       - @param bool   $photos - "with photos only" check box using
     */
    public function search($aParams)
    {
        global $df, $reefless, $rlSmarty;

        $key = $_SESSION['post_form_key'] = $aParams['key'];
        $photos = isset($aParams['photos']) && (bool) $aParams['photos'] === false ? false : true;

        if (empty($df)) {
            $reefless->loadClass('Categories');
            $df = $GLOBALS['rlCategories']->getDF();
            $rlSmarty->assign_by_ref('df', $df);
        }

        unset($aParams['key'], $aParams['photos']);

        if (!empty($aParams)) {
            $GLOBALS['rlDb']->setTable('listing_fields');
            $available_fields = $GLOBALS['rlDb']->fetch(array('Key'), array('Status' => 'active'));
            $GLOBALS['rlDb']->resetTable();

            foreach ($available_fields as $afVal) {
                $a_fields[] = $afVal['Key'];
            }

            unset($available_fields);

            foreach ($aParams as $afKey => $akVal) {
                if (!in_array($afKey, $a_fields)) {
                    unset($aParams[$afKey]);
                }
            }

            $rlSmarty->assign('hidden_fields', $aParams);
        }

        /* get search forms */
        $GLOBALS['reefless']->loadClass('Search');
        $form = $GLOBALS['rlSearch']->buildSearch($key);

        $form['listing_type'] = $GLOBALS['rlDb']->getOne("Type", "`Key` ='{$key}'", "search_forms");

        $rlSmarty->assign_by_ref('form', $form);
        $rlSmarty->assign_by_ref('form_key', $key);
        $rlSmarty->assign_by_ref('use_photos', $photos);

        $rlSmarty->display('blocks' . RL_DS . 'search_block.tpl');
    }

    /**
     * Encode e-mail address to javascript code
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $email - Email to encode
     */
    public function encodeEmail($params = false)
    {
        global $rlValid, $reefless, $lang, $rlSmarty;

        $email = $params['email'];

        if (!$email || !$rlValid->isEmail($email)) {
            return false;
        }

        $out = '<a href="mailto:' . $email . '">' . $email . '</a>';
        $len = strlen($out);
        $step = rand(3, 7);
        $max = $len * $step;
        $range = range(0, $max - 1);

        for ($i = 0; $i < $len; $i++) {
            $index = $rlSmarty->encodeEmailSet($range);
            $array[$index] = $out[$i];
            $indexes[$i * $step] = $index;
        }

        for ($i = 0; $i < $max; $i++) {
            if (!isset($indexes[$i])) {
                $index = $rlSmarty->encodeEmailSet($range);
                $array[$index] = $reefless->generateHash(1, 'password');
                $indexes[$i] = $index;
            }
        }

        ksort($array);
        ksort($indexes);

        $js_l = "['" . implode("','", $array) . "']";
        $js_i = "['" . implode("','", $indexes) . "']";

        $var1 = $reefless->generateHash(7, 'lower', false) . 'c';
        $var2 = $reefless->generateHash(7, 'lower', false) . 'x';
        $var3 = $reefless->generateHash(7, 'lower', false) . 'a';
        $GLOBALS['encoded_email_index'] = $GLOBALS['encoded_email_index'] ? $GLOBALS['encoded_email_index'] + 1 : 1;
        $code = <<<VS
<span id="encoded_email_{$GLOBALS['encoded_email_index']}"></span><script type="text/javascript">//<![CDATA[
var $var1 = $js_l;var $var2 = $js_i;var $var3 = new Array(); var js_e = '';for(var i = 0; i<$var1.length;i+=$step){ $var3.push({$var1}[{$var2}[i]]); } for(var i = 0; i<$var3.length;i++){js_e += {$var3}[i]}; $('#encoded_email_{$GLOBALS['encoded_email_index']}').html(js_e).next().remove();
//]]></script><noscript>{$lang['noscript_show_email']}</noscript>
VS;
        echo $code;
    }

    /**
     * Populate array | secondary methods for encodeEmail()
     *
     * @param array $array - array in use
     */
    public function encodeEmailSet(&$range)
    {
        $i = rand(0, count($range) - 1);
        $t = $range[$i];
        unset($range[$i]);
        $range = array_values($range);

        return $t;
    }

    /**
     * Generates rss url
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $mode
     *
     * @return string - Rss page URL
     */
    public function getRssUrl($params = false)
    {
        global $config, $pages;

        $rss = $GLOBALS['rlSmarty']->get_template_vars('rss');
        $url = SEO_BASE;

        if ($config['mod_rewrite']) {
            $url .= $pages['rss_feed'] . '/' . ($rss['item'] && $rss['item'] != 'news' ? $rss['item'] . '/' : '');

            if ($params['mode'] == 'footer' && !$rss['id'] && !$rss['listing_type']) {
                $url .= 'news/';
            } else {
                $url .= $rss['id'] ? $rss['id'] . '/' : '';
                $url .= $rss['listing_type'] ? $rss['listing_type'] . '/' : '';
            }
        } else {
            $url .= '?page=' . $pages['rss_feed'];

            if ($params['mode'] == 'footer' && !$rss['id'] && !$rss['listing_type']) {
                $url .= '&item=news';
            } else {
                $url .= $rss['item'] ? '&item=' . $rss['item'] : '';
                $url .= $rss['id'] ? '&id=' . $rss['id'] : '';
                $url .= $rss['listing_type'] ? '&id=' . $rss['listing_type'] : '';
            }
        }

        return $url;
    }

    /**
     * Generates category url
     *
     * @return string - Category url
     */
    public function categoryUrl($params = false)
    {
        $data = is_array($params['category'])
        ? $params['category']['ID']
        : (int) $params['id'];
        $custom_lang = $params['custom_lang'];

        $out = $GLOBALS['reefless']->getCategoryUrl($data, $custom_lang);

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $out);
        } else {
            return $out;
        }
    }

    /**
     * Generates listing url
     *
     * @return string - Listing url
     */
    public function listingUrl($params = [])
    {
        $data = is_array($params['listing'])
        ? $params['listing']
        : (int) $params['id'];
        $custom_lang = $params['custom_lang'];

        $out = $GLOBALS['reefless']->url('listing', $data, $custom_lang);

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $out);
        } else {
            return $out;
        }
    }

    /**
     * Generates page url
     *
     * @return string - Page url
     */
    public function pageUrl($params = [])
    {
        $delimiter = '';

        if (strpos($params['add_url'], '&') !== false) {
            $delimiter = '&';
        }

        if (strpos($params['add_url'], '/') !== false) {
            $delimiter = '/';
        }

        if ($delimiter) {
            $params['add_url'] = explode($delimiter, $params['add_url']);
        }

        if (is_array($params['add_url'])) {
            foreach ($params['add_url'] as $item) {
                $param = explode('=', $item);
                if (count($param) > 1) {
                    $add_url[$param[0]] = $param[1];
                } else {
                    $add_url[] = $param[0];
                }
            }
        } else {
            $add_url = $params['add_url'] ? explode('=', $params['add_url']) : [];
            $add_url = count($add_url) > 1 ? [$add_url[0] => $add_url[1]] : $add_url;
        }

        $key = $params['page'] ?: $params['key'];
        $custom_lang = $params['custom_lang'];
        $vars = $params['vars'];

        $out = $GLOBALS['reefless']->getPageUrl($key, $add_url, $custom_lang, $vars);

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $out);
        } else {
            return $out;
        }
    }

    /**
     * Transforms compiled smarty output
     *
     * @param string compiled content
     * @return string - transformed content
     */
    public function transformSmartyOutput($compiled_content, $resource_name)
    {
        if ($GLOBALS['rlListingTypes']) {
            $compiled_content = $GLOBALS['rlListingTypes']->prepareListingTypeLinks($compiled_content);
        }

        $GLOBALS['rlHook']->load('smartyFetchHook', $compiled_content, $resource_name);

        // static data class parser
        if (!defined('REALM') || (defined('REALM') && REALM != 'admin')) {
            $compiled_content = $GLOBALS['rlStatic']->collectJSDynamicCode($compiled_content, $resource_name);
        }

        return $compiled_content;
    }

    /**
     * Displaying the banners integrated in listings (boxes with "Banner In Grid" position)
     *
     * @since 4.6.0
     *
     * @param array $params - Included following info: blocks, info about page, count of listings
     *                      - Format: array('blocks' => $blocks, 'page_nfo' => $pInfo, 'listings' => count($listings))
     * @return bool
     */
    public function showIntegratedBanner($params)
    {
        global $config, $rlSmarty;

        $blocks = $params['blocks'];
        $page_info = $params['pageinfo'];
        $listings = $params['listings'];
        $banners_in_page = $config['banner_in_grid_position'];
        $listings_per_page = $config['listings_per_page'];

        if (!$blocks || !$page_info || !$banners_in_page || !$listings_per_page) {
            return false;
        }

        $banners_in_page = (int) floor($listings_per_page / $banners_in_page);
        $show_banner = false;
        $index = 0;
        $_SESSION['count_showed_integrated_banners'] = $_SESSION['count_showed_integrated_banners'] ?: 0;

        // count total banners in page
        $_SESSION['count_integrated_banners'] = 0;
        foreach ($blocks as $block) {
            if ($block['Side'] == 'integrated_banner') {
                $_SESSION['count_integrated_banners']++;
            }
        }

        // hide banner which located under listings
        if ($banners_in_page * $config['banner_in_grid_position'] == $listings_per_page) {
            $_SESSION['hide_last_integrated_banner'] = true;

            if ($_SESSION['count_showed_integrated_banners'] + 1 == $banners_in_page) {
                unset(
                    $_SESSION['count_integrated_banners'],
                    $_SESSION['count_showed_integrated_banners'],
                    $_SESSION['last_integrated_banner'],
                    $_SESSION['hide_last_integrated_banner']
                );

                return false;
            }
        }

        // get index of last showed banner
        if ($page_info['current'] > 1 && $_SESSION['count_integrated_banners'] > $banners_in_page) {
            $count_showed_banners = ($page_info['current'] - 1) * ($_SESSION['hide_last_integrated_banner']
                ? $banners_in_page - 1
                : $banners_in_page);

            if ($count_showed_banners > $_SESSION['count_integrated_banners']) {
                $last_index = $count_showed_banners % $_SESSION['count_integrated_banners'];
            } else if ($count_showed_banners < $_SESSION['count_integrated_banners']) {
                $last_index = $count_showed_banners;
            }

            // reset index to rotate banners in same page
            if ($listings == $listings_per_page
                && ($last_index == $_SESSION['count_integrated_banners']
                    || ($_SESSION['count_showed_integrated_banners'] + 1 == ($_SESSION['hide_last_integrated_banner']
                        ? $banners_in_page - 1
                        : $banners_in_page))
                    || $last_index + 1 == $_SESSION['count_integrated_banners'] && $_SESSION['count_showed_integrated_banners']
                )
            ) {
                $last_index = 0;
            }
        } else {
            $last_index = 0;
        }

        // set index of last showed banner
        $_SESSION['last_integrated_banner'] = $_SESSION['last_integrated_banner'] ?: $last_index;

        // displaying banners
        foreach ($blocks as $block_key => $block) {
            if ($block['Side'] == 'integrated_banner') {
                // show first banner in page
                if (!$_SESSION['last_integrated_banner']) {
                    $show_banner = true;
                } else {
                    // show next banner in same page
                    if ($index == $_SESSION['last_integrated_banner']) {
                        $show_banner = true;
                    }
                }

                if ($show_banner) {
                    $rlSmarty->assign_by_ref('block', $block);
                    $rlSmarty->display('blocks' . RL_DS . 'blocks_manager.tpl');

                    break;
                } else {
                    $index++;
                }
            }
        }

        $_SESSION['count_showed_integrated_banners']++;

        // increase index for next banner
        if ($banners_in_page > 0 && $_SESSION['last_integrated_banner'] < $_SESSION['count_integrated_banners']) {
            $_SESSION['last_integrated_banner']++;
        }

        // reset index of showing banners in end of page
        if ($_SESSION['count_integrated_banners'] > $banners_in_page) {
            $count_showed_banners = ($page_info['current'] - 1) * ($_SESSION['hide_last_integrated_banner']
                ? $banners_in_page - 1
                : $banners_in_page);

            if ($page_info['current'] > 1
                && (($count_showed_banners + $_SESSION['count_showed_integrated_banners']) % $_SESSION['count_integrated_banners'] == 0)
            ) {
                unset($_SESSION['last_integrated_banner']);
            }

            if ($_SESSION['last_integrated_banner']) {
                // reset index for last page after last banner
                if ($page_info['current'] > 1 && $listings < $listings_per_page) {
                    unset($_SESSION['last_integrated_banner']);
                }

                // reset index for rotating banners
                if ($_SESSION['count_showed_integrated_banners'] == ($_SESSION['hide_last_integrated_banner']
                    ? $banners_in_page - 1
                    : $banners_in_page)
                ) {
                    unset($_SESSION['last_integrated_banner']);
                }
            }
        } else {
            // update count of banners if listings showed in one page only
            if ($page_info['current'] == 0 && $listings < $listings_per_page) {
                $banners_in_page = (int) floor($listings / $config['banner_in_grid_position']);
            }

            $count_showed_banners = $_SESSION['hide_last_integrated_banner'] && $banners_in_page > 1
            ? $banners_in_page - 1
            : $banners_in_page;

            // reset index if count of displaying banners more than count of banners in page
            if (($_SESSION['last_integrated_banner'] == $_SESSION['count_integrated_banners'])
                || ($_SESSION['count_showed_integrated_banners'] == $count_showed_banners)
            ) {
                unset($_SESSION['last_integrated_banner']);
            }
        }

        // reset of total count banners per page
        if ($_SESSION['count_showed_integrated_banners'] == ($_SESSION['hide_last_integrated_banner'] && $banners_in_page > 1
            ? $banners_in_page - 1
            : $banners_in_page)
        ) {
            unset($_SESSION['count_showed_integrated_banners']);
        }
    }

    public function preAjaxSupport()
    {
        // Prepare data
        $this->assign_by_ref('lang', $GLOBALS['lang']);

        // Register system functions
        $this->register_function('addCSS', [$GLOBALS['rlStatic'], 'smartyAddCSS']);
        $this->register_function('addJS', [$GLOBALS['rlStatic'], 'smartyAddJS']);
        $this->register_function('phrase', [$GLOBALS['rlLang'], 'getPhrase']);
        $this->register_function('rlHook', [$GLOBALS['rlHook'], 'load']);
        $this->register_function('phrase', [$GLOBALS['rlLang'], 'getPhrase']);
        $this->register_function('getTmpFile', [$GLOBALS['reefless'], 'getTmpFile']);

        // Define tpl base directory
        define('RL_TPL_BASE', RL_URL_HOME . 'templates/' . $GLOBALS['config']['template'] . '/');
        $this->assign('rlTplBase', RL_TPL_BASE);

        // Define rlBase
        $this->assign('rlBase', $GLOBALS['seo_base']);

        // Assign side bar exists flag
        if ((bool) $_REQUEST['sidebar']) {
            $this->assign('side_bar_exists', true);
        }
    }

    public function postAjaxSupport(&$results, &$page_info, &$resource_tpl_file)
    {
        $GLOBALS['rlStatic']->getJS($results, $page_info, $resource_tpl_file);
        $GLOBALS['rlStatic']->getCSS($results, $page_info, $resource_tpl_file);
    }

    /**
     * Custom templates for fields in field.tpl file
     *
     * @since 4.6.1
     *
     * @param array $params - smarty parameters
     */
    public function customFieldHandler($params)
    {
        $use_custom = false;

        $GLOBALS['rlHook']->load('smartyCustomFieldHandler', $params['field'], $use_custom);
        $GLOBALS['rlSmarty']->assign('use_custom', $use_custom);
    }

    /**
     * Require map API data
     *
     * @since 4.8.0
     *
     * @param array $params - SMARTY params
     *                        $params['assign'] - assign api data to the smarty instead of require it in the page
     */
    public function mapsAPI($params)
    {
        global $config, $rlStatic;

        $api_data = array(
            'js'  => array(
                RL_LIBS_URL . 'maps/leaflet.js',
                RL_LIBS_URL . 'maps/maps.js'
            ),
            'css' => array(
                RL_LIBS_URL . 'maps/leaflet.css',
                RL_URL_HOME . 'templates/' . $config['template'] . '/components/map-control/map-control.css'
            )
        );

        $GLOBALS['rlHook']->load('phpSmartyMapsAPI', $api_data, $params);

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $api_data);
        } else {
            foreach ($api_data['js'] as $js) {
                $rlStatic->addJS($js);
            }

            foreach ($api_data['css'] as $css) {
                $rlStatic->addFooterCSS($css);
            }
        }
    }

    /**
     * Require map API data for Admin Panel
     *
     * @since 4.8.0
     *
     * @param array $params - SMARTY params
     *                        $params['assign'] - assign api data to the smarty instead of require it in the page
     */
    public function adminMapsAPI($params)
    {
        global $config;

        if (defined('MAPS_API')) {
            return;
        }

        $api_data = array(
            'js'  => array(
                RL_LIBS_URL . 'maps/leaflet.js',
                RL_LIBS_URL . 'maps/maps.js'
            ),
            'css' => array(
                RL_LIBS_URL . 'maps/leaflet.css',
                RL_URL_HOME . 'templates/' . $config['template'] . '/components/map-control/map-control.css'
            )
        );

        $GLOBALS['rlHook']->load('apPhpSmartyMapsAPI', $api_data, $params);

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $api_data);
        } else {
            foreach ($api_data['js'] as $js) {
                echo '<script src="' . $js . '"></script>';
            }

            foreach ($api_data['css'] as $css) {
                echo '<link rel="stylesheet" href="' . $css . '" />';
            }
        }

        define('MAPS_API', true);
    }

    /**
     * Require geo autocomplete API data
     *
     * @since 4.8.0
     *
     * @param array $params - SMARTY params
     *                        $params['assign'] - assign api data to the smarty instead of require it in the page
     */
    public function geoAutocompleteAPI($params)
    {
        global $config, $rlStatic;

        $api_data = array(
            'js'  => RL_LIBS_URL . 'maps/geoAutocomplete.js',
            'css' => RL_URL_HOME . 'templates/' . $config['template'] . '/components/geo-autocomplete/geo-autocomplete.css'
        );

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $api_data);
        } else {
            $rlStatic->addJS($api_data['js']);
            $rlStatic->addFooterCSS($api_data['css']);
        }
    }

    /**
     * Require geo autocomplete API data for Admin Panel
     *
     * @since 4.8.0
     *
     * @param array $params - SMARTY params
     *                        $params['assign'] - assign api data to the smarty instead of require it in the page
     */
    public function adminGeoAutocompleteAPI($params)
    {
        if (defined('GEO_AUTOCOMPLETE_API')) {
            return;
        }

        $api_data = array(
            'js'  => RL_LIBS_URL . 'maps/geoAutocomplete.js',
            'css' => RL_TPL_BASE . '/components/geo-autocomplete/geo-autocomplete.css'
        );

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $api_data);
        } else {
            echo '<script src="' . $api_data['js'] . '"></script>';
            echo '<link rel="stylesheet" href="' . $api_data['css'] . '" />';
        }

        define('GEO_AUTOCOMPLETE_API', true);
    }

    /**
     * Static maps API request
     *
     * @since 4.8.0
     *
     * @param array $params - SMARTY params
     *                        $params['assign']   - assign picture url to smarty var
     *                        $params['location'] - location latitude and longitude devided by comma
     *                        $params['zoom']     - map zoom from 1 to 19
     *                        $params['width']    - image width
     *                        $params['height']   - image height
     *                        $params['scale']    - image scale
     */
    public function staticMap($params)
    {
        global $config;

        $location = $params['location'];
        $zoom     = $params['zoom'] ?: 14;
        $width    = $params['width'] ?: 200;
        $height   = $params['height'] ?: 200;
        $scale    = $params['scale'] ?: 1;

        if (!$location) {
            return 'No location specified';
        }

        switch ($config['static_map_provider']) {
            case 'google':
                $host   = 'https://maps.googleapis.com/maps/api/staticmap';
                $params = array(
                    'markers'  => "color:red|{$location}",
                    'zoom'     => $zoom,
                    'size'     => "{$width}x{$height}",
                    'scale'    => $scale,
                    'key'      => $config['google_map_key'],
                    'language' => RL_LANG_CODE
                );
                break;

            case 'yandex':
            default:
                $location = implode(',', array_reverse(explode(',', $location)));

                if ($scale > 1) {
                    $width  = min($width * 2, 650);
                    $height = min($height * 2, 450);
                }

                $host   = 'https://static-maps.yandex.ru/1.x/';
                $params = array(
                    'scale' => "{$scale}.0",
                    'z'     => $zoom,
                    'l'     => 'map',
                    'size'  => "{$width},{$height}",
                    'pt'    => "{$location},org",
                    'lang'  => RL_LANG_CODE
                );
                break;
        }

        $url = $host . '?' . http_build_query($params);

        if ($params['assign']) {
            $GLOBALS['rlSmarty']->assign($params['assign'], $url);
        } else {
            echo $url;
        }
    }

    /**
     * Register custom functions
     *
     * @since 4.7.0
     */
    public function registerFunctions()
    {
        $this->register_function('str2path', [$this, 'str2path']);
        $this->register_function('str2money', [$this, 'str2money']);
        $this->register_function('paging', [$this, 'paging']);
        $this->register_function('search', [$this, 'search']);
        $this->register_function('rlHook', [$GLOBALS['rlHook'], 'load']);
        $this->register_function('getTmpFile', [$GLOBALS['reefless'], 'getTmpFile']);
        $this->register_function('encodeEmail', [$this, 'encodeEmail']);
        $this->register_function('gateways', [$GLOBALS['rlPayment'], 'gateways']);
        $this->register_function('getRssUrl', [$this, 'getRssUrl']);
        $this->register_function('listingUrl', [$this, 'listingUrl']);
        $this->register_function('categoryUrl', [$this, 'categoryUrl']);
        $this->register_function('pageUrl', [$this, 'pageUrl']);
        $this->register_function('displayCSS', [$GLOBALS['rlStatic'], 'displayCSS']);
        $this->register_function('displayJS', [$GLOBALS['rlStatic'], 'displayJS']);
        $this->register_function('addCSS', [$GLOBALS['rlStatic'], 'smartyAddCSS']);
        $this->register_function('addJS', [$GLOBALS['rlStatic'], 'smartyAddJS']);
        $this->register_function('phrase', [$GLOBALS['rlLang'], 'getPhrase']);
        $this->register_function('showIntegratedBanner', [$this, 'showIntegratedBanner']);
        $this->register_function('staticMap', [$this, 'staticMap']);
        $this->register_function(
            'mapsAPI',
            [$this, defined('REALM') && REALM == 'admin' ? 'adminMapsAPI' : 'mapsAPI']
        );
        $this->register_function(
            'geoAutocompleteAPI',
            [$this, defined('REALM') && REALM == 'admin' ? 'adminGeoAutocompleteAPI' : 'geoAutocompleteAPI']
        );

        $GLOBALS['rlHook']->load('phpRegisterFunctions', $this);
    }
}
