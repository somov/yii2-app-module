<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 13.02.19
 * Time: 14:02
 */

namespace somov\appmodule\interfaces;

use yii\base\Application;
use yii\base\Event;

/**
 * Interface AppModuleEventHandler
 * @package somov\appmodule\interfaces
 *
 * @method boolean handle (Event $event, string $method)
 * @method static boolean isHandlerValid(Application $app, ConfigInterface $config)
 */
interface AppModuleEventHandler
{
    /**
     * @return array
     */
    public static function getEvents();

}