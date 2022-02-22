<?php
/**
 *
 * User: develop
 * Date: 03.10.2017
 */

namespace somov\appmodule\interfaces;


/**
 * Interface AppModuleInterface
 * @package somov\appmodule\interfaces
 */
interface AppModuleInterface extends AppModuleBaseInterface
{
    /**
     * @return string
     */
    public static function getAppModuleId();

    /**
     * @param ConfigInterface $config
     */
    public static function configure(ConfigInterface $config);


}