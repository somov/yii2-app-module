<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 22:13
 */

namespace instanceHandler;


use mtest\common\TestComponent;
use somov\appmodule\interfaces\AppModuleEventHandler;
use yii\base\Event;
use yii\base\StaticInstanceInterface;
use yii\base\StaticInstanceTrait;
use yii\web\Application;

/**
 * @method boolean handle (Event $event, string $method))
 */
class TestInstanceHandler implements StaticInstanceInterface, AppModuleEventHandler
{
    use StaticInstanceTrait;


    /**
     * @return array
     */
    public static function getEvents()
    {
        return [
            TestComponent::class => [
                TestComponent::EVENT_INIT
            ]
        ];
    }

    /**
     * @return bool
     */
    public static function isHandlerValid()
    {

      return \Yii::$app->state === Application::STATE_HANDLING_REQUEST;
    }

    /**
     * @param $event
     */
    public function testComponentInit($event)
    {
        $event->sender->testProperty = time();
    }

}