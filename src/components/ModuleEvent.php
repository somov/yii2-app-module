<?php
/**
 *
 * User: develop
 * Date: 25.09.2018
 */

namespace somov\appmodule\components;



use somov\appmodule\interfaces\ConfigInterface;
use yii\base\Event;
use yii\base\Module;

/**
 * Class ModuleEvent
 * @package somov\appmodule\components
 */
class ModuleEvent extends Event
{

    /** @var  Manager */
    public $sender;

    /**
     * @var Module
     */
    public $module;

    /**
     * @var bool
     */
    public $isValid = true;

    /**
     * @var bool
     */
    public $isReset = false;

    /**
     * @var ConfigInterface
     */
    public $config;


    /**
     * @return ConfigInterface|null
     */
    public function getConfig()
    {
        if (isset($this->config)) {
            return $this->config;
        }
        if (isset($this->module)) {
            return $this->sender->getModuleConfigById($this->module->getUniqueId());
        }
        return null;
    }

}