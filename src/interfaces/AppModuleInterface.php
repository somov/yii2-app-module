<?php
/**
 *
 * User: develop
 * Date: 03.10.2017
 */

namespace somov\appmodule\interfaces;


use somov\appmodule\Config;

/**
 * Interface AppModuleInterface
 * @package somov\appmodule\interfaces
 * @method bool upgrade
 */
interface AppModuleInterface
{
    /**
     * @return string
     */
    public static function getAppModuleId();

    /**
     * @param Config $config
     */
    public static function configure(Config $config);

    /**
     * @return bool
     */
    public function install();

    /**
     * @return bool
     */
    public function uninstall();


    /**
     * Get a event handler object
     * @return object
     */
    public function getModuleEventHandler();

}