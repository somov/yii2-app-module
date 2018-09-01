<?php

namespace subModule;

use somov\appmodule\Config;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\EventHandlerInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 */
class Module extends \yii\base\Module implements AppModuleInterface, EventHandlerInterface
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
        $config->id = 'submodule';
        $config->name = 'Test';
        $config->description = 'Test';
        $config->version = '1.0.1';
        $config->events = self::getEvents();
        $config->eventMethod = Config::METHOD_TYPE_EVENT_BY_METHOD;
        $config->category = 'Test';
        $config->parentModule = 'test-module';
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
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
        ];
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


}