<?php

namespace objectHandler;

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
 * @property-read TestHandler $customHandler
 */
class Module extends \yii\base\Module implements AppModuleInterface
{

    const EVENT_INIT = 'init';

    /**
     * @return string
     */
    public static function getAppModuleId()
    {
        return 'object-handler';
    }

    /**
     * @param ConfigInterface|\ExtendConfigInterface $config
     */
    public static function configure(ConfigInterface $config)
    {
        $config->name = 'object-handler-test';
        $config->handler = 'customHandler';

    }

    /**
     * @return TestComponent
     */
    public function getTestComponent()
    {
        return new TestComponent();
    }

    /**
     *
     */
    public function getCustomHandler()
    {
        return new TestHandler();
    }

}