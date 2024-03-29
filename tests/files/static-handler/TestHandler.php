<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 22:13
 */

namespace staticHandler;


use mtest\common\TestComponent;
use somov\appmodule\components\AppModuleStaticEventHandler;
use yii\base\Event;

/**
 * @method boolean handle (Event $event, string $method)
 * @method boolean isHandlerValid()
 */
class TestHandler extends AppModuleStaticEventHandler
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
    protected static function test($event)
    {
        $event->sender->testProperty = time();
    }


}