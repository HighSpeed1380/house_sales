<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: ABSTRACTSTEPS.PHP
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

namespace Flynax\Abstracts;

/**
 * @since 4.6.0
 */
abstract class AbstractSteps
{
    /**
     * Related controller key
     *
     * @var string
     */
    public $controller = '';

    /**
     * Single step method flag
     *
     * @var string
     */
    public $singleStep = false;

    /**
     * Steps data
     *
     * @var array
     */
    public $steps = [];

    /**
     * Current step
     *
     * @var string
     */
    public $step = '';

    /**
     * Single free plan or existing MP account mode
     *
     * @var array
     */
    public $singlePlan = false;

    /**
     * Single free plan mode
     *
     * @var array
     */
    public $skipCheckout = false;

    /**
     * Post data
     *
     * @var array
     */
    public $postData = [];

    /**
     * Selected plan ID
     *
     * @var integer
     */
    public $planID = false;

    /**
     * Page key
     *
     * @var string
     * @since 4.6.1
     */
    public $pageKey = null;

    /**
     * @return get called class name
     */
    private static function getClassName()
    {
        return get_called_class();
    }

    /**
     * Get class instance
     *
     * @return object
     */
    public static function getInstance()
    {
        $class_name = self::getClassName();

        if (isset($_SESSION[$class_name])) {
            return unserialize($_SESSION[$class_name]);
        }

        return new $class_name;
    }

    /**
     * Save class instance to session
     *
     * @param class object
     */
    public static function saveInstance($object)
    {
        $class_name = self::getClassName();
        $_SESSION[$class_name] = serialize($object);
    }

    /**
     * Remove class instance from session
     */
    public static function removeInstance()
    {
        $class_name = self::getClassName();
        unset($_SESSION[$class_name]);
    }

    /**
     * Set config just once
     *
     * @param array $data - configurations data
     */
    public function setConfig(&$data)
    {
        foreach ($data as $config_key => &$config_value) {
            $this->$config_key = &$config_value;
        }
    }

    /**
     * Initialize main processes
     *
     * @param array $page_info    - current page information array
     * @param array $account_info - current account information array
     * @param array $errors       - controller errors
     */
    public function init(&$page_info = null, &$account_info = null, &$errors = null)
    {
        $this->getStep();

        // process template step
        $GLOBALS['rlSmarty']->register_function('processStep', [$this, 'processTplStep']);
        $GLOBALS['rlSmarty']->register_function('buildPrevStepURL', [$this, 'buildPrevStepURL']);
        $GLOBALS['rlSmarty']->register_function('buildFormAction', [$this, 'buildFormAction']);
    }

    /**
     * Get step from the url
     */
    public function getStep()
    {
        if ($GLOBALS['config']['mod_rewrite']) {
            $rlVareables = explode('/', $_GET['rlVareables']);
            $step = array_pop($rlVareables);
            $step_path = $step ?: false;
        } else {
            $step_path = $_GET['step'] ?: false;
        }

        // convert to step key
        if ($step_path && $step = $this->getStepByPath($step_path)) {
            $this->step = $step;
        }
        // simulate the first step
        else {
            reset($this->steps);
            $this->step = key($this->steps);
        }

        $GLOBALS['rlSmarty']->assign_by_ref('cur_step', $this->step);
    }

    /**
     * Call the related step method, step method name should be 'step' + step key in ucwords format.
     *
     * Example: stepCheckout() or stepListingPreview()
     */
    public function processStep()
    {
        $step_info = $this->steps[$this->step];

        // Prepare plugin's class method
        if ($step_info['plugin']) {
            $GLOBALS['reefless']->loadClass(
                str_replace('rl', '', $step_info['class']),
                null,
                $step_info['plugin']
            );

            $class = $GLOBALS[$step_info['class']];
            $method = $step_info['method'];
            $class_name = $step_info['class'];
        }
        // Prepare initial class method
        else {
            $class = $this;
            $method = 'step' . str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $this->step)));
            $class_name = self::getClassName();
        }

        try {
            // Check method exists
            if (!method_exists($class_name, $method)) {
                throw new \BadMethodCallException(sprintf('Undefined method %s::%s', $class_name, $method));
            }

            // Call class method
            $class->$method($this);
        } catch (Exception $e) {
            $log = sprintf('[%s:%s] thrown within the exception: "%s"', $class_name, $method, $e->getMessage());

            if (RL_DEBUG === true) {
                exit($log);
            } else {
                $GLOBALS['rlDebug']->logger($log);
            }
        }
    }

    /**
     * Register related step tpl name in the SMARTY
     */
    public function processTplStep()
    {
        $step_info = $this->steps[$this->step];

        if ($step_info['plugin']) {
            $tpl = RL_PLUGINS . $step_info['plugin'] . RL_DS . $step_info['tpl'] . '.tpl';
        } else {
            $tpl = FL_TPL_CONTROLLER_DIR . $this->controller . RL_DS . 'step_' . $this->step . '.tpl';
        }

        /**
         * @since 4.6.0
         */
        $GLOBALS['rlHook']->load('stepsProcessTpl', $this, $tpl, $this->step);

        $GLOBALS['rlSmarty']->display($tpl);
    }

    /**
     * Initial step handler
     */
    public function step()
    {
        if ($this->singleStep) {
            return;
        }

        $GLOBALS['page_info']['name'] = $this->steps[$this->step]['name'];
    }

    /**
     * Get step by step path
     *
     * @param  string $path - step path (only key related path part)
     * @return string       - step key
     */
    public function getStepByPath($path = false)
    {
        if (!$path) {
            return false;
        }

        $step_key = false;

        foreach ($this->steps as $key => &$step) {
            if ($step['path'] == $path) {
                $step_key = $key;
            }
        }

        return $step_key;
    }

    /**
     * Save post data by requested key
     *
     * @param mixed $keys - post data key or keys array
     */
    public function savePost($keys = false)
    {
        if (!$keys) {
            return;
        }

        $keys = is_array($keys) ? $keys : array($keys);

        foreach ($keys as $key) {
            $this->postData[$key] = $_POST[$key];
        }
    }

    /**
     * Remove item from the instance post data
     *
     * @param mixed $keys - post data key or keys array
     */
    public function removePost($keys)
    {
        if (!$keys) {
            return;
        }

        $keys = is_array($keys) ? $keys : array($keys);

        foreach ($keys as $key) {
            unset($this->postData[$key]);
        }
    }

    /**
     * Simulat post data array by setting values saved in the instance
     * using mapping array
     *
     * @param mixed $data - post data key or keys array
     */
    public function simulatePost($data)
    {
        // return if data came from POST
        if ($_POST['from_post']) {
            return;
        }

        $data = is_array($data) ? $data : array($data);

        // simulate post
        foreach ($data as $key) {
            if (isset($this->postData[$key])) {
                $_POST[$key] = $this->postData[$key];
            }
        }
    }

    /**
     * Redirect to the step
     *
     * @param string $step   - step key to redirect to
     * @param mixed  $extend - data to extend url
     */
    public function redirectToStep($step = false, $extend = false)
    {
        if ($url = $this->buildStepURL(null, $step, $extend)) {
            \Flynax\Utils\Util::redirect($url, false);
        }
    }

    /**
     * Redirect to the previous step
     *
     * @param mixed $extend - data to extend url
     */
    public function redirectToPrevStep($extend = null)
    {
        if ($this->singleStep && $this->steps[$this->step]['skip_redirect']) {
            return;
        }

        $this->redirectToStep($this->getPrevStep(), $extend);
    }

    /**
     * Redirect to the next step
     *
     * @param mixed $extend - data to extend url
     */
    public function redirectToNextStep($extend = null)
    {
        if ($this->singleStep && $this->steps[$this->step]['skip_redirect']) {
            return;
        }

        $this->redirectToStep($this->getNextStep(), $extend);
    }

    /**
     * Move array pointer to current step
     *
     * @return bool - success flag
     */
    public function moveToCurrent()
    {
        reset($this->steps);

        if (!$this->steps[$this->step]) {
            return false;
        }
        while ($this->step != key($this->steps)) {
            next($this->steps);
        }

        return true;
    }

    /**
     * Get previous step data
     *
     * @return array - step data
     */
    public function getPrevStep()
    {
        if ($this->moveToCurrent()) {
            $this->skipBackward();
            prev($this->steps);
        }

        return current($this->steps);
    }

    /**
     * Get next step data
     *
     * @return array - step data
     */
    public function getNextStep()
    {
        if ($this->moveToCurrent()) {
            $this->skipForward();
            next($this->steps);
            //$this->skipForward();
        }

        return current($this->steps);
    }

    /**
     * Skip the steps which don't have submit action
     * Moving in forward direction
     */
    public function skipForward()
    {
        if (!$this->singleStep) {
            return;
        }

        if (next($this->steps)['skip_forward']) {
            $this->skipForward();
        } else {
            prev($this->steps);
        }
    }

    /**
     * Skip the steps which don't have submit action
     * Moving in backward direction
     */
    public function skipBackward()
    {
        if (!$this->singleStep) {
            return;
        }

        if (prev($this->steps)['skip_backward']) {
            $this->skipBackward();
        } else {
            next($this->steps);
        }
    }

    /**
     * Build next step url
     *
     * @param  mixed $extend - data to extend url
     * @return string        - step url
     */
    public function buildNextStepURL($extend = null)
    {
        return $this->buildStepURL(null, $this->getNextStep(), $extend);
    }

    /**
     * Build previous step url
     *
     * @param  int   $aParams['show_extended'] - show whole url with current step path
     * @param  mixed $extend                   - data to extend url
     * @return string                          - previous step url
     */
    public function buildPrevStepURL($aParams, $extend = null)
    {
        return $this->buildStepURL($aParams, $this->getPrevStep(), $extend);
    }

    /**
     * Build step url
     *
     * @param  int    $aParams['show_extended'] - show whole url with current step path
     * @param  string $step                     - step key
     * @param  mixed  $extend                   - data to extend url
     * @return string                           - step url
     */
    public function buildStepURL($aParams, $step, $extend = null)
    {
        if (!$step) {
            return;
        }

        global $page_info;

        $show_extended = !$aParams['show_extended']; // Inverse the value here, due to impossibility to do so in smarty
        $step_info = is_array($step) ? $step : $this->steps[$step];
        $allow_path = (
            $step_info['path']
            && (
                ($this->singleStep && !$step_info['edit'])
                || !$this->singleStep
            )
        );
        $url = SEO_BASE;

        if ($GLOBALS['config']['mod_rewrite']) {
            $url .= $page_info['Path'];

            if ($show_extended) {
                // Extend url by SEO path
                if ($extend['data'] && $extend['type'] == 'path') {
                    $url .= '/' . $extend['data'];
                }
            }

            // Add step path
            if ($allow_path) {
                $url .= '/' . $step_info['path'];
            }

            $url .= '.html';

            // Extend url by get parameter
            if ($show_extended && $extend['type'] == 'param') {
                $url .= $extend['data'];
            }
        } else {
            $url .= '?page=' . $page_info['Path'];

            if ($show_extended) {
                // Extend url
                if ($extend['key'] && $extend['value']) {
                    $url .= "&{$extend['key']}={$extend['value']}";
                }
            }

            // Add step path
            if ($allow_path) {
                $url .= '&step=' . $step_info['path'];
            }
        }

        /**
         * @since 4.7.1
         */
        $GLOBALS['rlHook']->load('phpAbstractStepsBuildStepUrl', $url);

        return $url;
    }

    /**
     * Build form action URL
     *
     * @param  int $aParams['show_extended'] - show whole url with current step path
     * @return string                        - form action url depending of current step
     */
    public function buildFormAction($aParams)
    {
        global $page_info, $config;

        $show_extended = !$aParams['show_extended']; // Inverse the value here, due to impossibility to do so in smarty
        $extend = $aParams['extend'];
        $action = SEO_BASE;

        if ($GLOBALS['config']['mod_rewrite']) {
            $path_field = $config['multilingual_paths'] && $page_info['Path_' . RL_LANG_CODE]
            ? 'Path_' . RL_LANG_CODE
            : 'Path';
            $action .= $page_info[$path_field];

            if ($show_extended) {
                // Extend url
                if ($extend['data'] && $extend['type'] == 'path') {
                    $action .= '/' . $extend['data'];
                }

                // Add step path
                $action .= '/' . $this->steps[$this->step]['path'];
            }

            $action .= '.html';

            // Extend url by get parameter
            if ($show_extended && $extend['type'] == 'param') {
                $action .= $extend['data'];
            }
        } else {
            $action .= '?page=' . $page_info['Path'];

            if ($show_extended) {
                // Extend url
                if ($extend['key'] && $extend['value']) {
                    $action .= '&' . $extend['key'] . '=' . $extend['value'];
                }

                // Add step path
                $action .= '&step=' . $this->steps[$this->step]['path'];
            }
        }

        return $action;
    }
}
