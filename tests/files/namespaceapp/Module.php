<?php

namespace app\modules\namespaceapp;

use somov\appmodule\Config;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\EventHandlerInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 */
class Module extends \yii\base\Module implements AppModuleInterface
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
        $config->id = 'namespaceapp';
        $config->name = 'Test';
        $config->description = 'Test';
        $config->version = '1.0.1';
        $config->category = 'Test';
        $test = ModuleComponent::getFoo();
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

    }

    public function getTest()
    {

    }


}