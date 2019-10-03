<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 13.02.19
 * Time: 14:02
 */

namespace somov\appmodule\interfaces;


use yii\base\Event;

/**
 * Interface AppModuleEventHandler
 * @package somov\appmodule\interfaces
 *
 * @method boolean handle (Event $event, string $method)
 */
interface AppModuleEventHandler
{
    /**
     * @return array
     */
    public static function getEvents();

}