<?php

namespace testGroupModule;


use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use testGroupModule\components\TestInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 * @method bool changedState(bool $state)
 * @method bool install(bool $isReset = false)
 * @method bool uninstall(bool $isReset = false)
 * @method boolean handle (Event $event, string $method)
 * @method Boolean isHandlerValid()
 */
class Module extends \yii\base\Module implements AppModuleInterface, TestInterface, AppModuleEventHandler
{

    public static function getAppModuleId()
    {
        return 'group-install';
    }

    public function viewBeginPage($event)
    {
        /** @var \yii\web\View $view */
        $view = $event->sender;
        $view->title .= 'it is ' . $this->id;
    }

    /**
     * @param ConfigInterface|\ExtendConfigInterface $config
     */
    public static function configure(ConfigInterface $config)
    {
        $config->name = 'Test';
        $config->description = 'Test description';
        $config->version = '1.0.2';
        $config->category = 'Test';
    }


    public function getModuleEventHandler()
    {
        return $this;
    }

    static function getEvents()
    {
        return [
            \yii\base\Application::class => [
                \yii\base\Application::EVENT_AFTER_REQUEST
            ]
        ];
    }

    public function applicationAfterRequest()
    {
        \Yii::$app->params['test'] = $this->getTest();
    }

    public function upgrade()
    {
        return true;
    }


    /**
     * @param Event $event
     * @param \yii\base\Module $module
     * @return void
     * @throws Exception
     */
    public function handleModuleEvent($event, $module)
    {
        throw new Exception('111');
    }

    public function getTest()
    {
        return 'test';
    }


}