<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLHOOK.CLASS.PHP
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

class rlHook extends reefless
{
    /**
     * @deprecated 4.5.1
     * @see rlCommon::getInstalledPluginsList (initialization in control.inc.php)
     */
    public $aHooks = array();

    /**
     * @deprecated 4.5.1
     * @var index of func
     **/
    public $index = 1;

    /**
     * List of all registered hooks
     *
     * @since 4.5.1
     **/
    private $hooks = array();

    /**
     * class constructor
     **/
    public function __construct()
    {
        $this->loadHooksList();
    }

    /**
     * Prepare hooks list to invoke by name
     *
     * [init][0] => [plugin => banners, class => Banners],
     * [init][1] => [plugin => ipgeo, class => IPGeo],
     * [init][2] => [plugin => weatherForecast, code => '...'], // legacy
     * ...etc...
     *
     * @since 4.5.1
     **/
    private function loadHooksList()
    {
        $this->setTable('hooks');
        $entries = $this->fetch(array('Name', 'Class', 'Plugin', 'Code'), array('Status' => 'active'));

        foreach ($entries as $entry) {
            $_hook['plugin'] = $entry['Plugin'];

            if ($entry['Class'] != '') {
                $_hook['class'] = $entry['Class'];
            } else {
                $_hook['code'] = $entry['Code'];
            }

            $this->hooks[$entry['Name']][] = $_hook;
            unset($_hook);
        }
        unset($entries);
    }

    /**
     * Load hooks by name with params
     *
     * @since 4.5.1
     *
     * @param mixed $name   - hook name
     * @param mixed $param1 - hook param by Ref
     * @param mixed $param2 - hook param by Ref
     * @param mixed $param3 - hook param by Ref
     * @param mixed $param4 - hook param by Ref
     * @param mixed $param5 - hook param by Ref
     * @param mixed $param6 - hook param by Ref
     * @param mixed $param7 - hook param by Ref
     * @param mixed $param8 - hook param by Ref
     */
    public function load($name, &$param1 = null, &$param2 = null, &$param3 = null, &$param4 = null, &$param5 = null, &$param6 = null, &$param7 = null, &$param8 = null)
    {
        global $rlHook;

        if (defined('SKIP_HOOKS')) {
            return;
        }

        if (is_array($name)) {
            $name = $name['name'];
        }
        $hookClass = null;

        if (isset($rlHook->hooks[$name]) === false) {
            return;
        }

        foreach ($rlHook->hooks[$name] as $entry) {
            $_plugin = $entry['plugin'] ?: null;

            try {
                // new logic
                if (isset($entry['class'])) {
                    $hookClass = 'rl' . $entry['class'];
                    $classMethod = 'hook' . ucfirst($name);
                    $classDirectory = $_plugin ? RL_PLUGINS . $_plugin . RL_DS : RL_CLASSES;

                    if (!file_exists($classDirectory . $hookClass . '.class.php')) {
                        throw new LogicException(sprintf('The %s class not found', $hookClass));
                    }

                    $GLOBALS['reefless']->loadClass($entry['class'], null, $_plugin);

                    if (!method_exists($GLOBALS[$hookClass], $classMethod)) {
                        throw new BadMethodCallException(sprintf('Undefined method %s::%s', $hookClass, $classMethod));
                    }

                    $GLOBALS[$hookClass]->$classMethod($param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8);
                }
                // legacy logic
                else {
                    $rlHook->invokeLegacy($name, $entry['code'], $param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8);
                }
            } catch (Exception $e) {
                $log = sprintf('[%s:%s] thrown within the exception: "%s"', $hookClass, $name, $e->getMessage());

                if (RL_DEBUG === true) {
                    exit($log);
                } else {
                    $GLOBALS['rlDebug']->logger($log);
                }

                // TODO: Would be great to disable the plugin or something else to prevent the exception in future.
            }
        }
    }

    /**
     * Invoke legacy hook code
     *
     * @since 4.5.1
     * @param string $name  - hook name
     * @param string $code  - PHP hook code from database
     * @param mixed $param1 - hook param by Ref
     * @param mixed $param2 - hook param by Ref
     * @param mixed $param3 - hook param by Ref
     * @param mixed $param4 - hook param by Ref
     * @param mixed $param5 - hook param by Ref
     * @param mixed $param6 - hook param by Ref
     * @param mixed $param7 - hook param by Ref
     * @param mixed $param8 - hook param by Ref
     */
    public function invokeLegacy($name, $code, &$param1, &$param2, &$param3, &$param4, &$param5, &$param6, &$param7, &$param8)
    {
        if ($code == '') {
            return;
        }

        $func = "{$name}Hook" . $GLOBALS['rlHook']->index;
        $wrapper = "function {$func}(&\$param1, &\$param2, &\$param3, &\$param4, &\$param5, &\$param6, &\$param7, &\$param8) { " . PHP_EOL;
        $wrapper .= "[code]" . PHP_EOL;
        $wrapper .= "}";

        @eval(str_replace('[code]', $code, $wrapper));

        if (!function_exists($func)) {
            throw new BadFunctionCallException("Undefined function " . $func);
        }

        $func($param1, $param2, $param3, $param4, $param5, $param6, $param7, $param8);

        $GLOBALS['rlHook']->index++;
    }

    /**
     * @deprecated 4.5.1
     * @see loadHooksList
     *
     * Get all active hooks
     **/
    public function getHooks()
    {}
}
