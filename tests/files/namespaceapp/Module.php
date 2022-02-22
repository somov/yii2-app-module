<?php

namespace app\modules\namespaceapp;

use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use yii\base\Event;

/**
 *  module definition class
 * @method bool upgrade()
 * @method bool changedState(bool $isEnabled)
 * @method boolean handle (Event $event, string $method)
 * @method Boolean isHandlerValid()
 */
class Module extends \yii\base\Module implements AppModuleInterface, AppModuleEventHandler
{


    public static function getAppModuleId()
    {
        return 'namespaceapp';
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


    public function applicationAfterRequest()
    {
        \Yii::$app->response->data = $this->id;
    }

    /**
     * @return array
     */
    public static function getEvents()
    {
        return [];
    }

}