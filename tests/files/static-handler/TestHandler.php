<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 22:13
 */

namespace staticHandler;


use somov\appmodule\components\AppModuleStaticEventHandler;
use yii\base\Event;
use yii\db\ActiveQuery;

/**
 * @method boolean handle (Event $event, string $method)
 */
class TestHandler extends AppModuleStaticEventHandler
{

    /**
     * @return array
     */
    public static function getEvents()
    {
        return [
            ActiveQuery::class => [
                [
                    'name' => ActiveQuery::EVENT_INIT,
                    'method' => 'test'
                ]
            ]
        ];
    }

    protected static function test($event)
    {
        $event->sender->test  = 'test-static';
    }
}