<?php

namespace instanceHandler;

use mtest\common\TestComponent;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;

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
        return 'instance-handler';
    }

    /**
     * @param ConfigInterface|\ExtendConfigInterface $config
     */
    public static function configure(ConfigInterface $config)
    {
        $config->name = 'static-instance-handler-test';
        $config->handler = TestInstanceHandler::class;

    }

    public function getTestComponent()
    {
        return new TestComponent();
    }

}