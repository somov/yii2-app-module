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
 *
 * @method object|AppModuleEventHandler|null getModuleEventHandler()
 */
interface AppModuleInterface extends AppModuleBaseInterface
{
    /**
     * @return string
     */
    public static function getAppModuleId();

    /**
     * @param Config $config
     */
    public static function configure(Config $config);


}