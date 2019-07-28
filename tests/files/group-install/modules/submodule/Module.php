<?php

namespace testGroupModuleSubModule;

use somov\appmodule\Config;
use somov\appmodule\interfaces\AppModuleInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 * @method bool changedState(bool $isEnabled)
 */
class Module extends \yii\base\Module implements AppModuleInterface
{

    public static function getAppModuleId()
    {
        return 'submodule';
    }

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
        $config->name = 'Test';
        $config->description = 'Test';
        $config->version = '1.0.1';
        $config->events = self::getEvents();
        $config->category = 'Test';
        $config->parentModule = \testGroupModule\Module::getAppModuleId();
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