<?php

namespace staticHandler;

use somov\appmodule\Config;
use somov\appmodule\interfaces\AppModuleInterface;

/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 21:51
 *
 *
 * @method bool upgrade()
 * @method bool install(bool $isReset = false)
 * @method bool uninstall(bool $isReset = false)
 * @method bool changedState(bool $isEnabled)
 * @method null getModuleEventHandler()
 */
class Module extends \yii\base\Module implements AppModuleInterface
{

    /**
     * @return string
     */
    public static function getAppModuleId()
    {
        return 'static-handler';
    }

    /**
     * @param Config $config
     */
    public static function configure(Config $config)
    {
        $config->name = 'static-handler-test';
        $config->handler = TestHandler::class;

    }

}