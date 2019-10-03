<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 21:22
 */

namespace somov\appmodule\components;

use somov\appmodule\interfaces\AppModuleEventHandler;
use yii\base\Event;

/**
 * Class AppModuleStaticEventHandler
 * @package somov\appmodule\components
 * @method boolean handle (Event $event, string $method)
 */
abstract class AppModuleStaticEventHandler implements AppModuleEventHandler
{

    /**
     * @param Event $event
     * @param $method
     * @return bool
     */
    public static function handleStatic(Event $event, $method)
    {
        if (method_exists(static::class, $method)) {
            call_user_func([static::class, $method], $event);
            return true;
        }
        return false;
    }
}