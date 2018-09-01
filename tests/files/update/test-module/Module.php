<?php

namespace testModule;


use testModule\components\TestInterface;
use somov\appmodule\Config;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\EventHandlerInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 */
class Module extends \yii\base\Module implements AppModuleInterface, EventHandlerInterface, TestInterface
{


    public function viewBeginPage($event)
    {
        /** @var \yii\web\View $view */
        $view = $event->sender;
        $view->title .= 'it is ' . $this->id;
    }

    /**
     * @inheritdoc
     */
    public static function configure(Config $config)
    {
        $config->id = 'test-module';
        $config->name = 'Test';
        $config->description = 'Test';
        $config->version = '1.0.2';
        $config->events = self::getEvents();
        $config->eventMethod = Config::METHOD_TYPE_EVENT_BY_METHOD;
        $config->category = 'Test';
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function upgrade(){
        return true;
    }


    /**@return EventHandlerInterface */
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
        \Yii::$app->response->data = $this->id;
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