<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 25.07.20
 * Time: 18:35
 */

namespace somov\appmodule\components;


use somov\appmodule\interfaces\ContextValidatorInterface;
use yii\base\Behavior;

/**
 * Class ContextFilterBehavior
 * @package somov\appmodule\components
 */
class ContextFilterBehavior extends Behavior
{
    /**
     * @var Manager
     */
    public $owner;

    /**
     * @return array
     */
    public function events()
    {
        return [
            Manager::EVENT_FILTER => '_filter'
        ];
    }

    /**
     * @param ModuleEvent $event
     */
    public function _filter(ModuleEvent $event)
    {
        $config = $event->getConfig();

        if (isset($config->context) && $config->context !== false) {
            $event->isValid = false;

            if (is_subclass_of($config->context, ContextValidatorInterface::class)) {
                /** @var ContextValidatorInterface $class */
                $class = $config->context;
                $event->isValid = $class::validateContext($event->sender->getApp(), $config);
                return;
            }
        }
    }

}