<?php
/**
 *
 * User: develop
 * Date: 03.10.2017
 */

namespace somov\appmodule\interfaces;


use somov\appmodule\Config;

interface AppModuleInterface
{


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
     * @return EventHandlerInterface
     */
    public function getModuleEventHandler();

}