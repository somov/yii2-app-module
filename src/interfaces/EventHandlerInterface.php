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

/**
 * Interface EventHandlerInterface
 * @package somov\appmodule\interfaces
 * @deprecated since 1.0.58
 */
interface EventHandlerInterface
{
    /**
     * @param Event $event
     * @param Module $module
     * @return void
     * @deprecated
     */
    public function handleModuleEvent($event, $module);

}