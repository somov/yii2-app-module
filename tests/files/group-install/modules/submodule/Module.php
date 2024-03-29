<?php

namespace testGroupModuleSubModule;

use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 * @method bool changedState(bool $isEnabled)
 * @method boolean handle (Event $event, string $method)
 * @method Boolean isHandlerValid()
 */
class Module extends \yii\base\Module implements AppModuleInterface, AppModuleEventHandler
{

    public static function getAppModuleId()
    {
        return 'group-install/submodule';
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
    }

    public function install($isReset)
    {
        throw new \Exception('Install submodule');

    }

    public function uninstall()
    {
        throw new \Exception('Uninstall submodule');
    }

    public function upgrade()
    {
        throw new \Exception('Update submodule');
    }


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