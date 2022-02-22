<?php /** @noinspection ALL */

namespace testModule;


use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use testModule\components\TestInterface;
use yii\base\Event;
use yii\base\Exception;

/**
 *  module definition class
 */
class Module extends \yii\base\Module implements AppModuleInterface, TestInterface, AppModuleEventHandler
{

    public static function getAppModuleId()
    {
        return 'test-module';
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
        $config->version = '9.9.9';
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

    public function upgrade()
    {
        return true;
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


    /**
     * @param Event $event
     * @param $method
     * @return boolean
     */
    public function handle(Event $event, $method)
    {
        return false;
    }
}