<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 21.07.20
 * Time: 20:47
 */

namespace somov\appmodule\components;

use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\ConfigInterface;
use yii\base\Event;

/**
 * Class ModuleHandledEvent
 * @package somov\appmodule\components
 * @property-read array trace
 */
class ModuleHandlerDebugEvent extends Event
{
    /**
     * @var bool
     */
    public $isHandled = false;

    /**
     * @var ConfigInterface
     */
    public $config;

    /**
     * @var AppModuleEventHandler|string
     */
    public $handler;

    /**
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $senderClass;

    /**
     * @var string
     */
    public $eventName;


    /**
     * @return array
     */
    public function getTrace()
    {

        if (empty($this->method)) {
            return [];
        }

        try {
            $reflection = new \ReflectionClass($this->handler);
            $reflectionMethod = $reflection->getMethod($this->method);
            return [
                'file' => $reflection->getFileName(),
                'line' => $reflectionMethod->getStartLine()
            ];
        } catch (\ReflectionException $exception) {
            return [
                'file' => '',
                'line' => '',
                'error' => $exception->getMessage()
            ];
        }
    }

}