<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 22:13
 */

namespace objectHandler;


use mtest\common\TestComponent;
use somov\appmodule\interfaces\AppModuleEventHandler;
use yii\base\BaseObject;
use yii\base\Event;

/**
 * @method boolean handle (Event $event, string $method)
 * @method boolean isHandlerValid()
 */
class TestHandler extends BaseObject implements AppModuleEventHandler
{

    /**
     * @return array
     */
    public static function getEvents()
    {
        return [
            TestComponent::class => [
                [
                    'name' => TestComponent::EVENT_INIT,
                    'method' => 'test'
                ]
            ]
        ];
    }

    /**
     * @param $event
     */
    public function test($event)
    {
        $event->sender->testProperty = time();
    }


}