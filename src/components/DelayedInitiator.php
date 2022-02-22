<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.07.20
 * Time: 22:05
 */

namespace somov\appmodule\components;


use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\ConfigInterface;
use yii\base\Application;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

/**
 * Class DelayedInitiator
 * @package somov\appmodule\components
 */
class DelayedInitiator extends BaseObject
{

    /**
     * @var array[]
     *  $handlers  => [
     *   'config'  => ConfigInterface,
     *   'handlers => AppModuleEventHandler[]
     * ]
     */
    protected $handlers = [];

    /**
     * @var ConfigInterface[]
     */
    protected $configs = [];

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var callable
     */
    public $processorHandlers;

    /**
     * @var callable
     */
    public $processorModules;

    /**
     * @var callable
     */
    public $complete;


    /**
     * DelayedInitiator constructor.
     * @param Application $application
     * @param array $config
     */
    public function __construct(Application $application, array $config = [])
    {
        $this->application = $application;
        parent::__construct($config);
    }

    /**
     * @param ConfigInterface[] $list
     */
    public function initialize(array $list)
    {
        if (!is_callable($this->processorModules)) {
            throw new InvalidConfigException('Empty modules processor');
        }
        $this->processModules($list);
        $this->initializeModules();
        if (!$this->initializeHandlers()){
            $this->complete();
        }
    }


    /**
     * @return boolean
     */
    protected function initializeModules()
    {
        if (!empty($this->configs)) {
            $this->application->on(Application::EVENT_BEFORE_ACTION, function () {
                $this->processModules($this->configs);
            });
            return true;
        }
        return false;
    }

    /**
     * @param ConfigInterface[] $configs
     */
    protected function processModules($configs)
    {
        call_user_func($this->processorModules, $configs, $this);
    }

    /**
     *
     */
    protected function complete()
    {
        if (is_callable($this->complete)) {
            call_user_func($this->complete);
        }
    }

    /**
     * @param string $uniqueId
     */
    protected function processHandlers($uniqueId)
    {
        if (isset($this->handlers[$uniqueId])) {
            $item = &$this->handlers[$uniqueId];
            call_user_func($this->processorHandlers, $item['config'], $item['handlers']);
            unset($this->handlers[$uniqueId]);
        }

    }

    /**
     * @return boolean
     */
    protected function initializeHandlers()
    {
        if (isset($this->processorHandlers) && !empty($this->handlers)) {
            $this->application->on(Application::EVENT_BEFORE_ACTION, function () {
                foreach ($this->handlers as $key => $item) {
                    $this->processHandlers($key);
                }
            });
            $this->complete();
            return true;
        }
        return false;
    }

    /**
     * @param ConfigInterface $config
     * @param AppModuleEventHandler|string $handler
     */
    public function addHandler(ConfigInterface $config, $handler)
    {
        $h =  &$this->handlers;
        $h[$config->uniqueId] = [
            'config' => $config,
            'handlers' => isset($h[$config->uniqueId]['handlers'])
                ? array_merge($h[$config->uniqueId]['handlers'], [$handler]) : [$handler]
        ];
    }

    /**
     * @param ConfigInterface $config
     */
    public function addConfig(ConfigInterface $config)
    {
        $this->configs[$config->uniqueId] = $config;
    }


}