<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 23:41
 */

namespace somov\appmodule\components;

use yii\base\Event;
use yii\base\Exception;

/**
 * Class ModuleExceptionEvent
 * @package somov\appmodule\components
 */
class ModuleExceptionEvent extends ModuleEvent
{
    /**
     * @var Exception
     */
    public $exception;

    /**
     * @var Event
     */
    public $event;

    /**
     * @var bool
     */
    public $isValid = false;

}