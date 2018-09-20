<?php
/**
 * Created by PhpStorm.
 * User: develop
 * Date: 15.11.2017
 * Time: 0:01
 */

namespace somov\appmodule\interfaces;


use yii\base\Event;
use yii\base\Module;

interface EventHandlerInterface
{
    /**
     * @param Event $event
     * @param Module $module
     * @return void
     */
    public function handleModuleEvent($event, $module);

}