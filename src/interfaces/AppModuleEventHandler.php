<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 13.02.19
 * Time: 14:02
 */

namespace somov\appmodule\interfaces;


use yii\base\Event;

interface AppModuleEventHandler
{
    /**
     * @return array
     */
    public static function getEvents();

    /**
     * @param Event $event
     * @param $method
     * @return boolean
     */
    public function handle(Event $event, $method);

}