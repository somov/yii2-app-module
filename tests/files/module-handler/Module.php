<?php

namespace moduleHandler;

use mtest\common\TestComponent;
use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use yii\base\Event;

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
 * @method boolean isHandlerValid()
 */
class Module extends \yii\base\Module implements AppModuleInterface, AppModuleEventHandler
{

    const EVENT_INIT = 'init';


    /**
     * @return string
     */
    public static function getAppModuleId()
    {
        return 'module-handler';
    }

    /**
     * @return array
     */
    public static function getEvents()
    {
        return [
            TestComponent::class => [
                [
                    'name' => TestComponent::EVENT_INIT,
                ]
            ]
        ];
    }


    /**
     * @param ConfigInterface|\ExtendConfigInterface $config
     */
    public static function configure(ConfigInterface $config)
    {
        $config->name = 'module-handler-test';
    }

    /**
     * @param Event $event
     * @param string $method
     * @return bool|void
     */
    public function handle(Event $event, $method){
        $event->sender->testProperty = time();
    }

    /**
     * @return TestComponent
     */
    public function getTestComponent()
    {
        return new TestComponent();
    }




}