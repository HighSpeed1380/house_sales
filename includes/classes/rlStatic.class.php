<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLSTATIC.CLASS.PHP
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

class rlStatic
{
    /**
     * @var array of page files assigned by controller name
     **/
    private $pageControllerFiles = array();

    /**
     * @var array of page files assigned by page key
     **/
    private $pageKeyFiles = array();

    /**
     * @var array of page files assigned by box key
     **/
    private $boxKeyFiles = array();

    /**
     * @var array of page files assigned by box related plugin key
     **/
    private $boxPluginFiles = array();

    /**
     * @var original array of static js code snippets
     **/
    private $jsStaticCodeOriginal = array();

    /**
     * @var array of static js code snippets
     **/
    private $jsStaticCode = array();

    /**
     * @var array of dynamic js code snippets
     **/
    private $jsDynamicCode = array();

    /**
     * @var regexp pattern to cut static js code from the dom
     **/
    private $regexpStaticJS = '/(\<script[\s]+class\="fl\-js\-static"\>.*?\<\/script\>)/sm';

    /**
     * @var regexp pattern to cut dynamic js code from the dom
     **/
    private $regexpDynamicJS = '/(\<script[\s]+class\="fl\-js\-dynamic"([\s]+)?(data\-dependency\-(before|after)\=\"([^\"]+)\")?\>.*?\<\/script\>)/sm';

    /**
     * @var regexp pattern to find php code in snippet
     **/
    private $regexpPHP = '/(\<\?php\s(.*)?\s\?\>\n?)/sm';

    /**
     * Script file path to script ID array mapping
     */
    public $IDMapping = array();

    /**
     * Scripts/styles from these domains will be ignored
     *
     * @since 4.7.1
     * @var   array
     */
    protected $deniedDomains = [];

    /**
     * get cached code snippets
     *
     **/
    public function __construct()
    {
        $this->jsStaticCode = $this->getJSCode();

        // prevent showing of the Google Maps for bots
        if ($GLOBALS['reefless']->isBot()) {
            $this->addDeniedDomain('maps.googleapis.com');
        }
    }

    /**
     * add css file in header depending on the page controller
     *
     * @param string $file - file url
     * @param mixed $pages - page controller name to show page on, allowed value types:
     *                       - string: page controller name
     *                       - array: array of page controllers
     *                       - false: show file on all pages
     * @param bool $key - set true if the file adds by page key instead of page controller
     *
     **/
    public function addHeaderCSS($file = false, $pages = false, $key = false)
    {
        $this->add($file, 'cssHeader', $pages, false, $key);
    }

    /**
     * add css file in footer depending on the page controller
     *
     * @param string $file - file url
     * @param mixed $pages - page controller name to show page on, allowed value types:
     *                       - string: page controller name
     *                       - array: array of page controllers
     *                       - false: show file on all pages
     * @param bool $key - set true if the file adds by page key instead of page controller
     *
     **/
    public function addFooterCSS($file = false, $pages = false, $key = false)
    {
        $this->add($file, 'cssFooter', $pages, false, $key);
    }

    /**
     * Add css file in footer depending on the place of implementation
     * If the file adds in the box then it will be assigned to it's box
     * If the file adds on the page then it will be assigned to it's page controller
     *
     * @package SMARTY
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $file - File url
     */
    public function smartyAddCSS($params)
    {
        global $page_info, $rlSmarty;

        // box mode
        if ($rlSmarty->_tpl_vars['block']['Key']) {
            $GLOBALS['rlStatic']->addBoxFooterCSS(
                $params['file'],
                $rlSmarty->_tpl_vars['block']['Plugin'] ?: $rlSmarty->_tpl_vars['block']['Key'],
                $rlSmarty->_tpl_vars['block']['Plugin']);
        }
        // page mode
        else {
            $GLOBALS['rlStatic']->addFooterCSS($params['file'], $page_info['Controller']);
        }
    }

    /**
     * add js file on the page depending on the page controller
     *
     * @param string $file - file url
     * @param mixed $pages - page controller name to show page on, allowed value types:
     *                       - string: page controller name
     *                       - array: array of page controllers
     *                       - false: show file on all pages
     * @param bool $key - set true if the file adds by page key instead of page controller
     * @param string $id - script dependency ID
     *
     **/
    public function addJS($file = false, $pages = false, $key = false, $id = null)
    {
        $this->add($file, 'js', $pages, false, $key, $id);
    }

    /**
     * Add js file in footer depending on the place of implementation
     * If the file adds in the box then it will be assigned to it's box
     * If the file adds on the page then it will be assigned to it's page controller
     *
     *
     * @package SMARTY
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $file - File url
     *                       - @param string $id   - Script dependency ID
     */
    public function smartyAddJS($params)
    {
        global $page_info, $rlSmarty, $rlStatic;

        // box mode
        if ($rlSmarty->_tpl_vars['block']['Key']) {
            $rlStatic->addBoxJS(
                $params['file'],
                $rlSmarty->_tpl_vars['block']['Plugin'] ?: $rlSmarty->_tpl_vars['block']['Key'],
                $rlSmarty->_tpl_vars['block']['Plugin'],
                $params['id']
            );
        }
        // page mode
        else {
            $rlStatic->addJS(
                $params['file'],
                $page_info['Controller'],
                false,
                $params['id']
            );
        }
    }

    /**
     * add css file in the header depending on the box exist
     *
     * @param string $file - file url
     * @param mixed $boxes - box key or box related plugin key
     *                       - string: box/plugin key
     *                       - array: array of box/plugin keys
     * @param bool $plugin - set true if the file adds by plugin key
     **/
    public function addBoxHeaderCSS($file = false, $boxes = false, $plugin = false)
    {
        $this->add($file, 'cssHeader', $boxes, true, $plugin);
    }

    /**
     * add css file in the footer depending on the box exist
     *
     * @param string $file - file url
     * @param mixed $boxes - box key or box related plugin key
     *                       - string: box/plugin key
     *                       - array: array of box/plugin keys
     * @param bool $plugin - set true if the file adds by plugin key
     **/
    public function addBoxFooterCSS($file = false, $boxes = false, $plugin = false)
    {
        $this->add($file, 'cssFooter', $boxes, true, $plugin);
    }

    /**
     * add js file on the page depending on the box exist
     *
     * @param string $file - file url
     * @param mixed $boxes - box key or box related plugin key
     *                       - string: box/plugin key
     *                       - array: array of box/plugin keys
     * @param bool $plugin - set true if the file adds by plugin key
     * @param string $id - script dependency ID
     **/
    public function addBoxJS($file = false, $boxes = false, $plugin = false, $id = null)
    {
        $this->add($file, 'js', $boxes, true, $plugin, $id);
    }

    /**
     * remove css file from all internal data arrays
     *
     * @param string $file - file url
     *
     **/
    public function removeCSS($file = false)
    {
        $this->remove($file, 'css');
    }

    /**
     * remove js file from all internal data arrays
     *
     * @param string $file - file url
     *
     **/
    public function removeJS($file = false, $pages = false, $key = false)
    {
        $this->remove($file, 'js');
    }

    /**
     * add file to the internal data array
     *
     * @param string $file - file url
     * @param string $type - file type, cssHeader, cssFooter or js
     * @param mixed $targets - page or box key or box related plugin key
     *                       - string: page/box/plugin key
     *                       - array: array of page/box/plugin keys
     * @param bool $in_box - is it box dependence
     * @param bool $alternative - set true if the file adds by page key or plugin key
     * @param string $id - script dependency ID
     **/
    private function add($file = false, $type = false, $targets = false, $in_box = false, $alternative = false, $id = null)
    {
        if ($this->deniedDomains) {
            foreach ($this->deniedDomains as $domain) {
                if (false !== strpos($file, $domain)) {
                    return false;
                }
            }
        }

        /**
         * Pre add file hook
         *
         * @since 4.6.1 $this, $id
         */
        $GLOBALS['rlHook']->load('staticDataAddFile', $this, $file, $type, $targets, $in_box, $alternative, $id);

        // check for file type
        if (!in_array($type, array('cssHeader', 'cssFooter', 'js'))) {
            die('Only cssHeader, cssFooter or js types allowed to be added to the static data');
        }

        // check is local
        $plain_file = preg_replace('/^(https?\:)?\/\//', '', $file);
        $plain_host = preg_replace('/^(https?\:)?\/\//', '', RL_URL_HOME);
        $is_local = 0 === strpos($plain_file, $plain_host);
        $in_core = false;

        // check for file availability
        if ($is_local) {
            if (!is_readable(str_replace(RL_URL_HOME, RL_ROOT, $file))) {
                $core_file = str_replace($GLOBALS['config']['template'], 'template_core', $file);

                // try to find the requested file in template core
                if (TPL_CORE && is_readable(str_replace(RL_URL_HOME, RL_ROOT, $core_file))) {
                    $file = str_replace($GLOBALS['config']['template'], 'template_core', $file);
                    $in_core = true;
                }
                // no file error
                else {
                    die('The file "' . $file . '" does not exist or unreadable');
                }
            }

            // try to include RTL version of style
            $this->convertRTLStyle($file, $core_file, $type, $in_core);
        }

        // define source variable
        if (!$in_box) {
            if ($alternative) {
                $var = &$this->pageKeyFiles;
            } else {
                $var = &$this->pageControllerFiles;
            }
        } elseif ($in_box && $targets) {
            if ($alternative) {
                $var = &$this->boxPluginFiles;
            } else {
                $var = &$this->boxKeyFiles;
            }
        }

        // push file
        if (is_array($targets)) {
            foreach ($targets as $target) {
                $this->push($var, $target, $type, $file);
            }
        } elseif (is_string($targets)) {
            $this->push($var, $targets, $type, $file);
        } else {
            $this->push($var, 'all', $type, $file);
        }

        // Save script ID to the mapping
        if ($id && $type == 'js') {
            // Validate script ID
            if (is_numeric(array_search($id, $this->IDMapping))) {
                die('Script with ID (' . $id . ') already added, use another ID');
            }

            $this->IDMapping[$file] = $id;
        }
    }

    private function convertRTLStyle(&$file, &$core_file, &$type, &$in_core)
    {
        if ((defined('RL_LANG_DIR') && RL_LANG_DIR != 'rtl')
            || !in_array($type, array('cssHeader', 'cssFooter'))
        ) {
            return;
        }

        $file_ref = $in_core ? $core_file : $file;
        $rtl_file = preg_replace('/(.+)(\.[^\.]+)$/', '$1-rtl$2', $file_ref);
        $file = is_readable(str_replace(RL_URL_HOME, RL_ROOT, $rtl_file)) ? $rtl_file : $file;
    }

    /**
     * remove file from the internal data array
     *
     * @param string $file - file url
     * @param string $type - file type: cssHeader, cssFooter or js
     * @param mixed $targets - page or box key or box related plugin key
     *                       - string: page/box/plugin key
     *                       - array: array of page/box/plugin keys
     * @param bool $in_box - is it box dependence
     * @param bool $alternative - set true if the file removes by page key or plugin key
     **/
    private function remove($file = false, $type = false)
    {
        /**
         * Pre add file hook
         *
         * @since 4.6.1 $this
         */
        $GLOBALS['rlHook']->load('staticDataRemoveFile', $this, $file, $type);

        // check for file type
        if (!in_array($type, array('css', 'js'))) {
            die('Only css or js files allowed to be removed from the static data');
        }

        $types = $type == 'js' ? array('js') : array('cssHeader', 'cssFooter');

        // remove file
        foreach ($types as $type) {
            $this->pop($this->pageKeyFiles, $type, $file);
            $this->pop($this->pageControllerFiles, $type, $file);
            $this->pop($this->boxPluginFiles, $type, $file);
            $this->pop($this->boxKeyFiles, $type, $file);
        }
    }

    /**
     * push the file to the data array
     *
     * @param array $var - internal data array
     * @param string $targetKey - target (page, block, plugin) key
     * @param string $type - file type: cssHeader, cssFooter or js
     * @param string $file - file url
     *
     **/
    private function push(&$var, $targetKey = false, $type = false, $file = false)
    {
        if ($var[$targetKey][$type] && in_array($file, $var[$targetKey][$type])) {
            $message = 'The file "' . $file . '" is already added on page "' . $targetKey . '"';

            trigger_error($message, E_USER_NOTICE);
            return;
        }

        $var[$targetKey][$type][] = $file;
    }

    /**
     * pop the file from the data array
     *
     * @param array $var - internal data array
     * @param string $type - file type: cssHeader, cssFooter or js
     * @param string $file - file url
     *
     **/
    private function pop(&$var, $type = false, $file = false)
    {
        foreach ($var as $key => &$item) {
            if ($item[$type] && false !== $index = array_search($file, $item[$type])) {
                unset($var[$key][$type][$index]);
            }
        }
    }

    /**
     * Display css files in smarty by page controller and present boxes
     *
     * @param array $aParams - Array with all internal parameters:
     *                       - @param string $mode  - Place to display styles in, the possible value: header or footer
     */
    public function displayCSS($params)
    {
        global $page_info, $blocks, $rlStatic;

        /**
         * Before display css hook
         *
         * @since 4.6.1 $params
         */
        $GLOBALS['rlHook']->load('staticDataDisplayCSS', $page_info, $blocks, $rlStatic, $params);

        if (!in_array($params['mode'], array('header', 'footer'))) {
            die("The mode parameter can be header or footer only");
        }

        foreach ($rlStatic->get('css' . ucfirst($params['mode']), $page_info, $blocks) as $file) {
            echo '<link rel="stylesheet" href="' . $file . '" />' . PHP_EOL;
        }
    }

    /**
     * Display js files in smarty by page controller and present boxes.
     * Display js code snippets.
     */
    public function displayJS()
    {
        global $page_info, $blocks, $rlStatic;

        $GLOBALS['rlHook']->load('staticDataDisplayJS', $page_info, $blocks, $rlStatic);

        // display js files
        foreach ($rlStatic->get('js', $page_info, $blocks) as $file) {
            // Get dependency
            if ($id = $rlStatic->IDMapping[$file]) {
                $dynamic = $rlStatic->getDynamicCode($id);
            }

            // Display code before the script
            if ($dynamic['position'] == 'before') {
                echo $dynamic['code'] . PHP_EOL;
            }

            echo '<script src="' . $file . '"></script>' . PHP_EOL;

            // Display code after the script
            if ($dynamic['position'] == 'after') {
                echo $dynamic['code'] . PHP_EOL;
            }

            unset($dynamic);
        }

        // Initialize main js util class
        echo '<script>flUtil.init();</script>' . PHP_EOL;

        // display dynamic js code snippets
        foreach ($rlStatic->jsDynamicCode as &$item) {
            echo $item['code'] . PHP_EOL;
        }

        // display static js code snippets
        foreach ($rlStatic->jsStaticCode as $resource_name => &$snippets) {
            if ($GLOBALS['rlSmarty']->_included_compile_files[$resource_name]) {
                foreach ($snippets as &$code) {
                    echo $code . PHP_EOL;
                }
            }
        }

        // save js code snippets
        $rlStatic->saveJS();
    }

    /**
     * Append js code and js files found in tpl to the ajax response array
     *
     * @param array  &$results           - Return array to add data to
     * @param array  &$page_info         - Current page data
     * @param string &$resource_tpl_file - Current tpl file
     * @param array  $blocks             - Current page blocks data
     */
    public function getJS(&$results, &$page_info, &$resource_tpl_file, $blocks = null)
    {
        // Get js files
        foreach ($this->get('js', $page_info, $blocks) as $file) {
            $results['js_files'][] = $file;
        }

        $results['js_scripts'] = array();

        foreach ($this->jsDynamicCode as &$item) {
            $results['js_scripts'][] = $item['code'];
        }

        // Get static js code snippets
        $ptr = '/^.*templates\\' . RL_DS . '.*?\\' . RL_DS . '/';

        foreach ($this->jsStaticCode as $resource_name => &$snippets) {
            if (preg_replace($ptr, '', $resource_tpl_file) == preg_replace($ptr, '', $resource_name)) {
                if (is_array($snippets)) {
                    $results['js_scripts'] = array_merge($results['js_scripts'], $snippets);
                } else {
                    $results['js_scripts'][] = $snippets;
                }
            }
        }

        // Save js code snippets
        $this->saveJS();
    }

    /**
     * Append css files found in tpl to the ajax response array
     *
     * @since 4.8.0
     *
     * @param array  &$results           - Return array to add data to
     * @param array  &$page_info         - Current page data
     * @param string &$resource_tpl_file - Current tpl file
     * @param array  $blocks             - Current page blocks data
     */
    public function getCSS(&$results, &$page_info, &$resource_tpl_file, $blocks = null)
    {
        foreach ($this->get('cssHeader', $page_info, $blocks) as $file) {
            $results['css_files'][] = $file;
        }
        foreach ($this->get('cssFooter', $page_info, $blocks) as $file) {
            $results['css_files'][] = $file;
        }
    }

    /**
     * Get dynamyc code snippet by dependency ID
     *
     * @param  integer $id - dependency ID
     * @return array       - code snippet data
     */
    private function getDynamicCode($id)
    {
        if (!$id) {
            return false;
        }

        foreach ($this->jsDynamicCode as $key => &$item) {
            if ($item['id'] == $id) {
                unset($this->jsDynamicCode[$key]);
                return $item;
            }
        }
    }

    /**
     * get files by requested file type assigned to it's page or present boxes
     *
     * @param string $type - file type: cssHeader, cssFooter or js
     * @param array $pageInfo - page information
     * @param array $blocks - boxes array related to the page
     *
     * @return array - files
     *
     **/
    private function get($type, $pageInfo, $blocks = null)
    {
        // default action trigger
        $do_default = true;

        // pre get
        $GLOBALS['rlHook']->load('staticDataPreGet', $type, $pageInfo, $blocks, $do_default);

        if ($do_default) {
            // get all pages files
            $files = $this->pageControllerFiles['all'][$type] ?: array();

            // merge with current page files assigned by controller name
            if ($this->pageControllerFiles[$pageInfo['Controller']][$type]) {
                $files = array_merge($files, $this->pageControllerFiles[$pageInfo['Controller']][$type]);
            }

            // merge with current page files assigned by page key
            if ($this->pageKeyFiles[$pageInfo['Key']][$type]) {
                $files = array_merge($files, $this->pageKeyFiles[$pageInfo['Key']][$type]);
            }

            // Assign special box to existing blocks array to allow class uses static files added inside
            if ($GLOBALS['tpl_settings']['home_page_special_block']
                && $GLOBALS['rlSmarty']->_tpl_vars['home_page_special_block']) {
                $blocks[] = $GLOBALS['rlSmarty']->_tpl_vars['home_page_special_block'];
            }

            if ($blocks && ($this->boxKeyFiles || $this->boxPluginFiles)) {
                foreach ($blocks as $block) {
                    // get box files by box key
                    if ($this->boxKeyFiles && $this->boxKeyFiles[$block['Key']][$type]) {
                        $files = array_merge($files, $this->boxKeyFiles[$block['Key']][$type]);
                    }

                    // get box files by plugin key
                    if ($block['Plugin'] && $this->boxPluginFiles && $this->boxPluginFiles[$block['Plugin']][$type]) {
                        $files = array_merge($files, $this->boxPluginFiles[$block['Plugin']][$type]);
                    }
                }
            }
        }

        // post get
        $GLOBALS['rlHook']->load('staticDataPostGet', $files, $type, $pageInfo, $blocks, $do_default);

        return array_unique($files);
    }

    /**
     * add system files to data array
     *
     **/
    public function addSystemFiles()
    {
        /**
         * Register super first
         *
         * @since 4.6.1 $this
         */
        $GLOBALS['rlHook']->load('staticDataPreAddSystem', $this);

        // add system css
        $this->addHeaderCSS(RL_TPL_BASE . 'css/bootstrap' . (RL_LANG_DIR == 'rtl' ? '-rtl' : '') . '.css');
        $this->addHeaderCSS(RL_TPL_BASE . 'css/style.css');
        $this->addFooterCSS(RL_TPL_BASE . 'css/jquery.ui.css', array('add_listing', 'edit_listing'));
        $this->addFooterCSS(RL_TPL_BASE . 'css/fancybox.css', array('listing_details', 'add_video', 'add_listing'));
        if (RL_LANG_DIR == 'rtl') {
            $this->addHeaderCSS(RL_TPL_BASE . 'css/rtl.css');
        }

        // add system js
        $this->addJS(RL_TPL_BASE . 'js/util.js');
        $this->addJS(RL_TPL_BASE . 'js/jquery.flModal.js');
        $this->addJS(RL_LIBS_URL . 'jquery/cookie.js');
        $this->addJS(RL_LIBS_URL . 'jquery/numeric.js');

        /**
         * Register other
         *
         * @since 4.6.1 $this
         */
        $GLOBALS['rlHook']->load('staticDataRegister', $this);
    }

    /**
     * collects fl-js-static scripts
     *
     * @package SMARTY
     *
     **/
    public function collectJSStaticCode(&$resource_name, &$source_content)
    {
        // remove code from the cache if it doesn't exist in the file anymore
        if ($this->jsStaticCode[$resource_name]) {
            unset($this->jsStaticCode[$resource_name]);
        }

        $eval_source_content = $source_content;

        // remove displayJS method call to availd double call of this method
        if ((!TPL_CORE && $resource_name == 'footer.tpl') || (bool) preg_match('/(\/|\\\)footer\.tpl$/', $resource_name)) {
            $eval_source_content = preg_replace('/(\<\?php echo rlStatic\:\:displayJS\(array\(\)\, \$this\)\;\?\>)/sm', '', $source_content);
        }

        // Save current dynamic code | experemental
        $tmp = $this->jsDynamicCode;

        // Execute the code
        ob_start();
        $GLOBALS['rlSmarty']->_eval(' ?>' . $eval_source_content . '<?php ');
        $content = ob_get_clean();

        // Restore dynamic code
        $this->jsDynamicCode = $tmp;

        unset($eval_source_content);

        // find fl-js-static code snippets in file content
        if ((bool) preg_match_all($this->regexpStaticJS, $content, $matches)) {
            if ($matches[1]) {
                // remove code from the source
                $source_content = preg_replace($this->regexpStaticJS, '', $source_content);

                // save snippets
                foreach ($matches[1] as &$match) {
                    // remove class attribute
                    $code = preg_replace('([\s+]?class="fl\-js\-static"[\s+]?)', '', $match);

                    // add code to the array
                    $this->jsStaticCode[$resource_name][] = $code;
                }
            }
        }
    }

    public function collectJSDynamicCode(&$content)
    {
        // default action trigger
        $do_default = true;

        /**
         * Before dynamic js parsing hook
         *
         * @since 4.6.1 $this
         */
        $GLOBALS['rlHook']->load('staticDataJSDynamic', $this, $content, $do_default);

        // find fl-js-dynamic code snippets in file content
        if ($do_default && (bool) preg_match_all($this->regexpDynamicJS, $content, $matches)) {
            if ($matches[1]) {
                // remove code from the source
                $content = preg_replace($this->regexpDynamicJS, '', $content);

                // save snippets
                foreach ($matches[1] as &$match) {
                    preg_match('/data\-dependency\-(before|after)\=\"([^\"]+)\"/sm', $match, $dependency);

                    if ($dependency[1] && $dependency[2]) {
                        $data = array(
                            'position' => $dependency[1],
                            'id'       => $dependency[2],
                        );
                    }

                    // remove class attribute
                    $data['code'] = preg_replace([
                        '([\s+]?class="fl\-js\-dynamic"[\s+]?)',
                        '(data\-dependency\-(before|after)\=\"([^\"]+)\")',
                    ], '', $match);

                    // add code to the array
                    $this->jsDynamicCode[] = $data;
                }
            }
        }

        return $content;
    }

    /**
     * Get JS code scripts from the cache
     *
     */
    private function getJSCode()
    {
        // get cache data
        $code = $GLOBALS['rlCache']->get('cache_js_code') ?: '';

        if ($code) {
            $this->jsStaticCodeOriginal = $code;
            $code = json_decode($code, true);
        }

        return is_array($code) ? $code : array();
    }

    /**
     * save js code scripts
     *
     **/
    public function saveJS()
    {
        $code = json_encode($this->jsStaticCode);

        if ($this->jsStaticCodeOriginal != $code) {
            $GLOBALS['rlCache']->set('cache_js_code', $code);
        }

        unset($code, $this->jsStaticCode, $this->jsStaticCodeOriginal);
    }

    /**
     * Add domain of script/style which must be ignored
     *
     * @since 4.7.1
     *
     * @param  string $domain
     * @return bool
     */
    public function addDeniedDomain($domain)
    {
        $domain = (string) $domain;

        if (!$domain) {
            return false;
        }

        if (!in_array($domain, $this->deniedDomains)) {
            $this->deniedDomains[] = $domain;
            return true;
        }

        return false;
    }
}
