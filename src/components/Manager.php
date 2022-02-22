<?php
/**
 *
 * User: somov.nn
 *
 *
 */

namespace somov\appmodule\components;


use somov\appmodule\exceptions\InstallationDirectoryExistsException;
use somov\appmodule\exceptions\InvalidModuleConfiguration;
use somov\appmodule\exceptions\ManagerExceptionBase;
use somov\appmodule\exceptions\ModuleNotFoundException;
use somov\appmodule\exceptions\SubModuleException;
use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use somov\appmodule\interfaces\ManagerConfigResolver;
use somov\common\traits\ContainerCompositions;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\caching\Cache;
use yii\caching\Dependency;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use ZipArchive;


/**
 * Class ModuleManager
 * @package app\components
 *
 * @property Cache $cache
 * @property string|array|ManagerConfigResolver configResolver
 * @property-read Application $app
 */
class Manager extends Component implements BootstrapInterface
{
    use ContainerCompositions;

    CONST EVENT_LOAD = 'load';

    CONST EVENT_HANDLERS_DEBUG = 'handled';

    CONST EVENT_BEFORE_INSTALL = 'beforeInstall';
    CONST EVENT_AFTER_INSTALL = 'afterInstall';

    CONST EVENT_BEFORE_UNINSTALL = 'beforeUnInstall';
    CONST EVENT_AFTER_UNINSTALL = 'afterUnInstall';

    CONST EVENT_BEFORE_CHANGE_STATE = 'beforeChangeState';
    CONST EVENT_AFTER_CHANGE_STATE = 'afterChangeState';

    CONST EVENT_BEFORE_UPGRADE = 'beforeUpgrade';
    CONST EVENT_AFTER_UPGRADE = 'afterUpgrade';

    CONST EVENT_UNZIP = 'unzip';
    CONST EVENT_UNZIPPED = 'unzipped';

    CONST EVENT_FILTER = 'filter';

    const EVENT_ON_EXCEPTION_IN_EVENT_HANDLER = 'exceptionEventHandler';

    const DEFAULT_RESOLVER = 'somov\appmodule\components\ConfigInitializerLocFile';

    /** Turn on module after reset oo install
     * @var bool
     */
    public $isAutoActivate = false;

    /**
     * @var string|array|ManagerConfigResolver
     */
    private $_configResolver;

    /**
     * @var string|array|ManagerConfigResolver
     */
    protected $defaultConfigResolver;

    /**
     * @var array
     */
    public $configOptions = [
        'class' => 'somov\appmodule\components\ConfigLocFile'
    ];

    /**
     * If module stored at this base namespace. Manager will not added namespace from configuration
     * @see setModuleAlias
     * @var string
     */
    public $baseNameSpace = 'app\modules';

    /**
     * @var array|string
     */
    public $cacheConfig = [
        'class' => 'yii\caching\FileCache',
        'keyPrefix' => 'modules',
    ];

    /** @var array|null */
    public $cacheDependencyConfig = null;

    /**
     * @var string|callable
     */
    protected $cacheCurrentVariation = null;

    /**
     * @var array|callable
     */
    protected $cacheVariations = [];

    /**
     * @var array
     */
    public $places = [
        ConfigBase::DEFAULT_TYPE => '@app/modules',
    ];

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var boolean Manager will add a module configuration in controller action is ready.
     * If context is valid.
     */
    public $useDelayedInitialization = true;


    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->application;
    }


    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app)
    {
        $this->application = $app;
        Yii::beginProfile('Bootstrap', static::class);

        $list = $this->getModulesClassesList();

        foreach ($list as $config) {
            $this->setModuleAlias($config);
            foreach ($config->modules as $SubConfig) {
                $this->setModuleAlias($SubConfig);
            }
        }

        if ($this->useDelayedInitialization) {
            Yii::createObject([
                'class' => DelayedInitiator::class,
                'processorHandlers' => function (ConfigInterface $config, array $handlers) {
                    $this->addEventsToApp($config, null, $handlers);
                },
                'processorModules' => function (array $configs, DelayedInitiator $initiator) {
                    $this->addModulesToApp($configs, $initiator);
                },
                'complete' => function () {
                    Yii::endProfile('Bootstrap', static::class);
                }
            ], [$this->app])->initialize($list);
        } else {
            $this->addModulesToApp($list);
            Yii::endProfile('Bootstrap', static::class);
        }

    }


    /**
     * @param ConfigInterface[] $configs
     * @param DelayedInitiator $collectorDelayedItems
     * @throws InvalidConfigException
     * @throws ModuleNotFoundException
     */
    protected function addModulesToApp(array $configs, $collectorDelayedItems = null)
    {
        $hasFilterHandler = $this->hasEventHandlers(self::EVENT_FILTER);

        foreach ($configs as $config) {
            if ($config->isEnabled()) {
                $isValid = true;
                if ($hasFilterHandler) {
                    $event = new ModuleEvent(['config' => $config]);
                    $this->trigger(self::EVENT_FILTER, $event);
                    $isValid = $event->isValid;
                }
                if ($isValid) {
                    $this->addModule($config, true, $collectorDelayedItems);
                } else {
                    $collectorDelayedItems->addConfig($config);
                }
            }
        }
    }

    /**
     * @return array|ManagerConfigResolver|string
     */
    public function getConfigResolver()
    {
        return isset($this->_configResolver) ? $this->_configResolver : self::DEFAULT_RESOLVER;
    }

    /**
     * @param array|ManagerConfigResolver|string $configResolver
     */
    public function setConfigResolver($configResolver)
    {
        $this->_configResolver = $configResolver;
    }

    /**
     * Revert to 'somov\appmodule\components\ConfigInitializerLocFile'
     * @return $this
     */
    public function resetToDefaultResolver()
    {
        $this->setConfigResolver(
            isset($this->defaultConfigResolver) ? $this->defaultConfigResolver : self::DEFAULT_RESOLVER
        );
        $this->clearCache();
        return $this;
    }

    /**
     * @return \yii\caching\CacheInterface|object
     */
    protected function getCache()
    {
        if (is_string($this->cacheConfig)) {
            return $this->application->{$this->cacheConfig};
        }
        return $this->getCompositionYii($this->cacheConfig);
    }

    /**
     * @param array $variation
     * @return array
     */
    private function getCacheKey($variation = null)
    {
        $variation = (!empty($variation)) ? $variation : $this->cacheCurrentVariation;
        return array_merge([__CLASS__], (array)$variation);
    }


    /**
     * @param string|callable $value
     * @throws InvalidConfigException
     */
    public function setCacheCurrentVariation($value)
    {
        $this->cacheCurrentVariation = (is_callable($value))
            ? call_user_func($value) : $value;

        if (isset($this->cacheCurrentVariation) && !is_array($this->cacheVariations) &&
            in_array($this->cacheCurrentVariation, $this->cacheVariations)) {
            throw new InvalidConfigException('Unknown cache variation ');
        }
    }

    /**
     * @param array|callable $cacheVariations
     */
    public function setCacheVariations($cacheVariations)
    {
        $this->cacheVariations = (is_callable($cacheVariations))
            ? call_user_func($cacheVariations) : $cacheVariations;
    }


    /**
     * @return Dependency|object
     */
    private function getCacheDependency()
    {
        if (is_array($this->cacheDependencyConfig)) {
            $this->cacheDependencyConfig = \Yii::createObject($this->cacheDependencyConfig);
        }
        return $this->cacheDependencyConfig;
    }

    /**
     * @var ConfigInterface[]
     */
    private $_list;

    /** Массив конфигураций модулей
     * @return ConfigInterface[]
     */
    public function getModulesClassesList()
    {
        if (empty($this->_list)) {
            return $this->_list = $this->getCache()->getOrSet($this->getCacheKey(), function () {
                $resolver = $this->configResolver;
                if (!$resolver instanceof ManagerConfigResolver) {
                    $resolver = Yii::createObject($this->configResolver, [$this]);
                }
                return $resolver->resolve();
            }, null, $this->getCacheDependency());
        }
        return $this->_list;
    }

    /**
     * @return $this
     */
    public function clearCache()
    {
        $this->_list = null;
        if (empty($this->cacheVariations)) {
            $this->getCache()->offsetUnset($this->getCacheKey());;
            return $this;
        }
        foreach ($this->cacheVariations as $variation) {
            $this->getCache()->offsetUnset($this->getCacheKey($variation));
        }
        return $this;
    }


    /**
     * @param array $filter
     * @param ConfigInterface[] $modules
     * @return ConfigInterface[]
     */
    public function getFilteredClassesList($filter = ['enabled' => true], $modules = null)
    {
        if (empty($filter)) {
            return $this->getModulesClassesList();
        }

        return array_filter((isset($modules)) ? $modules : $this->getModulesClassesList(),
            function ($a) use ($filter) {
                foreach ($filter as $attribute => $value) {
                    if (is_scalar($value)) {
                        if ($a->$attribute != $value) {
                            return false;
                        }
                    } else {
                        foreach ($value as $item) {
                            if (!in_array($item, $a->$attribute)) {
                                return false;
                            };
                        }
                    }
                }
                return true;
            });
    }

    /**
     * @param string $id
     * @return ConfigInterface
     */
    public function getModuleConfigById($id)
    {
        $list = null;
        $config = null;

        foreach (explode('/', $id) as $part) {
            if ($config = $this->getFilteredClassesList(['id' => $part], $list)) {
                $config = reset($config);
                $list = $config->modules;
            }
        }
        return $config;
    }


    /**
     * Convert ConfigInterface object to Yii2 module settings
     * @param ConfigInterface $config
     * @return array
     * @recursive
     */
    protected function getArrayApplicationParams(ConfigInterface $config)
    {
        return [
            'class' => $config->class,
            'version' => $config->version,
            'modules' => array_map([$this, 'getArrayApplicationParams'], $config->modules),
        ];
    }

    /**
     * @param ConfigInterface $config
     * @param bool $bootstrap
     * @param DelayedInitiator $collectorDelayedItems
     * @throws InvalidConfigException
     * @throws ModuleNotFoundException
     */
    protected function addModule(ConfigInterface $config, $bootstrap = true, $collectorDelayedItems = null)
    {
        $app = $this->application;
        $params = $this->getArrayApplicationParams($config);

        if ($config->parentModule) {
            if (!$module = ArrayHelper::getValue($app->modules, [$config->parentModule, 'modules', $config->id])) {
                $module = (isset($app->modules[$config->parentModule])) ?
                    $app->modules[$config->parentModule] : null;
                if ($module instanceof Module) {
                    $module->setModule($config->id, $params);
                } elseif (is_array($module)) {
                    $parent = $this->getModuleConfigById($config->parentModule);
                    $parent->modules[$config->id] = $params;
                    $app->setModules([
                        $parent->id  => $this->getArrayApplicationParams($parent)
                    ]);
                }
            }
        } else {
            $app->setModule($config->id, $params);
        }

        if ($config->isEnabled()) {
            $this->addEventsToApp($config, $collectorDelayedItems);
            if (!empty($config->urlRules)) {
                $app->urlManager->addRules($config->urlRules, $config->appendRoutes);
            }
            if ($bootstrap && $config->bootstrap) {
                $module = $this->loadModule(null, $config);
                if ($module instanceof BootstrapInterface) {
                    $module->bootstrap($app);
                }
            }
            if ($modules = $config->modules) {
                $this->addModulesToApp($modules, $collectorDelayedItems);
            }
        }

    }


    /**
     * @param ConfigInterface $config
     */
    protected function setModuleAlias(ConfigInterface $config)
    {
        if ($config->nameSpace !== ($this->baseNameSpace . '\\' . $config->id)) {
            \Yii::setAlias(str_replace('\\', '/', $config->nameSpace), $config->path);
        }
    }

    /**
     * @param ConfigInterface $config
     * @param DelayedInitiator $collectorDelayedItems
     * @param AppModuleEventHandler[] $handlers
     * @throws InvalidConfigException
     */
    private function addEventsToApp($config, $collectorDelayedItems = null, $handlers = null)
    {
        $handlers = isset($handlers) ? $handlers : (array)$config->handler;

        $debug = $this->hasEventHandlers(self::EVENT_HANDLERS_DEBUG);

        if (is_subclass_of($config->class, AppModuleEventHandler::class)) {
            $handlers[] = $config->class;
        }

        foreach ($handlers as $handler) {

            if (!is_subclass_of($handler, AppModuleEventHandler::class)) {

                $module = $this->load($config->class);

                if ($config->class === $handler) {
                    $handler = $module;
                } elseif ($module->has($handler)) {
                    $handler = $module->get($handler);
                } elseif ($module->hasProperty($handler) && $module->canGetProperty($handler)) {
                    $handler = $module->{$handler};
                }

                if (!($handler instanceof AppModuleEventHandler)) {
                    Yii::warning('Unknown handler type .
                    ' . (is_object($handler) ? get_class($handler) : $handler), static::class);
                    continue;
                }
            }

            $valid = (method_exists($handler, 'isHandlerValid')) ?
                $handler::isHandlerValid($this->application, $config) : true;

            if ($valid) {

                foreach ($handler::getEvents() as $class => $classEvents) {
                    foreach ($classEvents as $classEvent) {
                        $append = true;
                        $method = null;
                        if (is_array($classEvent)) {
                            if (empty($classEvent['name'])) {
                                throw  new InvalidConfigException('Attribute name required for class event '
                                    . $class . ' options. Module ' . $config->id);
                            }
                            $name = $classEvent['name'];
                            $method = ArrayHelper::getValue($classEvent, 'method');
                            $append = ArrayHelper::getValue($classEvent, 'append', true);
                        } else {
                            $name = $classEvent;
                        }
                        if ($debug) {
                            $this->trigger(self::EVENT_HANDLERS_DEBUG, new ModuleHandlerDebugEvent([
                                'config' => $config,
                                'handler' => $handler,
                                'method' => $method,
                                'eventName' => $name,
                                'senderClass' => $class,
                            ]));
                        }

                        Event::on($class, $name, [$this, '_eventByMethodHandler'], [
                            'moduleConfig' => $config,
                            'h' => $handler,
                            'm' => $method,
                        ], $append);
                    }
                }
            } elseif (isset($collectorDelayedItems)) {
                $collectorDelayedItems->addHandler($config, $handler);
            }

        }
    }


    /** Modules Event handler
     * @param Event $event
     */
    public function _eventByMethodHandler($event)
    {

        /** @var ConfigInterface $config */
        $config = $event->data['moduleConfig'];

        /** @var AppModuleEventHandler|string $handler */
        $handler = ArrayHelper::remove($event->data, 'h');

        if (!$method = ArrayHelper::remove($event->data, 'm')) {
            $method = lcfirst(StringHelper::basename(get_class($event->sender)) . ucfirst($event->name));
        }

        $handlerName = (is_object($handler) ? get_class($handler) : $handler) . '::' . $method;

        Yii::beginProfile('Handle: ' . $handlerName, static::class);

        $hasDebugListeners = $this->hasEventHandlers(self::EVENT_HANDLERS_DEBUG);

        try {
            $handled = false;
            if (is_subclass_of($handler, AppModuleEventHandler::class)) {

                if (is_subclass_of($handler, AppModuleStaticEventHandler::class)) {
                    /** @var AppModuleStaticEventHandler $handler */
                    $handled = $handler::handleStatic($event, $method);
                }

                if (!$handled) {
                    $handler = ($config->class === $handler) ? $this->load($config->class)
                        : $config->eventHandlerInstance($handler);
                    if (method_exists($handler, 'handle')) {
                        $handled = $handler->handle($event, $method);
                    } elseif (method_exists($handler, $method)) {
                        $handled = call_user_func([$handler, $method], $event);
                    }
                }
            }

            if ($handled !== false) {
                if ($hasDebugListeners) {
                    $this->trigger(self::EVENT_HANDLERS_DEBUG, new ModuleHandlerDebugEvent([
                        'isHandled' => true,
                        'config' => $config,
                        'handler' => $handler,
                        'method' => $method,
                        'eventName' => $event->name,
                        'senderClass' => get_class($event->sender),
                    ]));
                }
                Yii::endProfile('Handle: ' . $handlerName, static::class);
                return;
            }

            throw new InvalidModuleConfiguration($this, (is_object($handler) ? get_class($handler) : $handler) .
                ' Unknown handler method for event ' . $event->name . ' method ' .
                $method . ' not found in ' . $config->uniqueId);

        } catch (\Exception $exception) {
            $this->onEventHandlerException($exception, null, $event);
            Yii::endProfile('Handle: ' . $handlerName, static::class);
            return;
        } catch (\Throwable $exception) {
            $this->onEventHandlerException($exception, null, $event);
            Yii::endProfile('Handle: ' . $handlerName, static::class);
            return;
        }
    }


    /**
     * Events exception handler
     * @param \Exception $exception
     * @param Module $module
     * @param Event $event
     */
    private function onEventHandlerException($exception, $module, $event)
    {

        if (YII_ENV_TEST) {
            throw $exception;
        }

        if (empty($module)) {
            /** @var ConfigInterface $config */
            if ($config = ArrayHelper::getValue($event, 'data.moduleConfig')) {
                try {
                    $module = $this->loadModule($config->id, $config, true);
                } catch (ModuleNotFoundException $exception) {
                }
            }
        }

        $event = new ModuleExceptionEvent(['module' => $module, 'exception' => $exception]);

        $this->trigger(self::EVENT_ON_EXCEPTION_IN_EVENT_HANDLER, $event);

        if ($event->isValid) {
            return;
        }

        Yii::error($exception->getMessage(), static::class);

        if (YII_DEBUG) {
            throw  $exception;
        }

    }

    /**
     * @param null $forFile
     * @return bool|string
     * @throws \yii\base\ErrorException
     */
    private function getTmpPath($forFile = null)
    {
        $path = \Yii::getAlias('@runtime/modules');
        if (!file_exists($path)) {
            mkdir($path);
        }

        if (isset($forFile)) {
            $path .= DIRECTORY_SEPARATOR . basename($forFile, '.zip');
            if (file_exists($path)) {
                FileHelper::removeDirectory($path);
            }
            mkdir($path);
        }

        return $path;
    }

    /**
     * Load a module by module class
     *
     * @param AppModuleInterface|string $class
     * @param bool $bootstrap
     * @return AppModuleInterface|Module
     */
    public function load($class, $bootstrap = false)
    {
        return $this->loadModule($class::getAppModuleId(), $config, $bootstrap);
    }


    /**
     * Load module by id.
     * If module configuration not exists in application then will added
     * @param $id
     * @param ConfigInterface|null $config
     * @param bool $bootstrap
     * @return AppModuleInterface|Module
     * @throws ModuleNotFoundException
     */
    public function loadModule($id, &$config = null, $bootstrap = false)
    {
        if (!$config = (isset($config)) ? $config : $this->getModuleConfigById($id)) {
            throw new ModuleNotFoundException($this, 'Unknown module ' . $id, null);
        }

        Yii::beginProfile('Loading module ' . $config->uniqueId, static::class);

        if (!$module = $this->application->getModule($config->uniqueId, false)) {
            $this->setModuleAlias($config);

            if ($config->parentModule && empty($this->application->modules[$config->parentModule])) {
                $this->addModule($this->getModuleConfigById($config->parentModule), $bootstrap);
            }

            if (!$module = $this->application->getModule($config->uniqueId)) {
                $this->addModule($config, $bootstrap);
                $module = $this->application->getModule($config->uniqueId);
            }
        }

        if (empty($module)) {
            $id = (isset($config)) ? $config->id : $id;
            throw new ModuleNotFoundException($this, 'Unknown module ' . $id, null);
        }

        $this->trigger(self::EVENT_LOAD, new ModuleEvent([
                'module' => $module,
                'config' => $config
            ]
        ));

        Yii::endProfile('Loading module ' . $config->uniqueId, static::class);

        return $module;

    }

    /** Upgrade a module
     * @param ConfigInterface $exist
     * @param ConfigInterface|string $new
     * @param string $fileName
     * @return bool
     * @throws ModuleNotFoundException
     * @throws \yii\base\ErrorException
     */
    public function upgrade(ConfigInterface $exist, $new, $fileName = null)
    {
        $isUpdateSourceMode = $new instanceof ConfigInterface;

        if ($isUpdateSourceMode) {
            if (version_compare($exist->version, $new->version, '>=')) {
                return true;
            }
            $alias = dirname(ConfigInitializerLocFile::aliasFromNameSpace($new->class));
            Yii::setAlias($alias, $new->path);
        }

        /** @var Module|AppModuleInterface $instance */
        $instance = $this->loadModule($isUpdateSourceMode ? $new->uniqueId : $exist->uniqueId);;
        $event = new ModuleEvent(['module' => $instance]);
        $this->trigger(self::EVENT_BEFORE_UPGRADE, $event);

        if ((!$event->isValid) || ($instance->hasMethod('upgrade') && !$instance->upgrade())) {
            if (isset($alias)) {
                Yii::setAlias($alias, null);
            }
            return false;
        }

        if ($isUpdateSourceMode) {
            if (isset($alias)) {
                Yii::setAlias($alias, null);
            }
            FileHelper::removeDirectory($exist->path);
            rename($new->path, $exist->path);
            $instance->version = $new->version;
        }

        $this->clearCache();

        $this->trigger(self::EVENT_AFTER_UPGRADE, new ModuleUpgradeEvent([
            'module' => $instance,
            'newVersion' => $isUpdateSourceMode ? $new->version : $new,
            'oldVersion' => $exist->version,
            'fileName' => $fileName,
        ]));

        foreach ($exist->modules as $subConfig) {
            try {
                $instance = $this->loadModule($subConfig->uniqueId);
                $this->executeMethod(self::EVENT_BEFORE_UPGRADE, self::EVENT_AFTER_UPGRADE,
                    $instance, function () use ($instance) {
                        if ($instance->hasMethod('upgrade')) {
                            return $instance->upgrade();
                        }
                        return true;
                    }, ['config' => $subConfig]);

            } catch (ModuleNotFoundException $exception) {
                Yii::warning($exception->getMessage());
                continue;
            }
        }

        return true;
    }

    /**
     * @param string $filesPath
     * @param ConfigInterface $config
     * @return $this
     * @throws InstallationDirectoryExistsException
     */
    protected function installFiles($filesPath, &$config)
    {
        $path = $config->getInstalledPath();

        $this->trigger(self::EVENT_UNZIP, new ModuleEvent(['config' => $config]));

        if (is_dir($path)) {
            $exception = new InstallationDirectoryExistsException($this,
                'Cannot install module. Installation directory already exists ' . $path);
            $exception->path = $path;
            throw  $exception;
        }

        FileHelper::createDirectory($path);

        rename($filesPath, $path);

        $config = $this->getConfigInitializerLocFile()->readConfig($config->getInstalledPath());
        $this->trigger(self::EVENT_UNZIPPED, new ModuleEvent([
            'config' => $config
        ]));

        return $this;
    }


    /**
     * @param \yii\base\Module|AppModuleInterface $module
     * @param boolean $isReset
     * @return bool
     */
    protected function internalUnInstall($module, $isReset)
    {
        return $this->executeMethod(self::EVENT_BEFORE_UNINSTALL,
            self::EVENT_AFTER_UNINSTALL, $module, function () use ($module, $isReset) {
                return ($module->hasMethod('uninstall') ? $module->uninstall($isReset) : true);
            }, ['isReset' => $isReset]);
    }


    /**
     * @param string $eventBefore
     * @param string $eventAfter
     * @param Module|AppModuleInterface $module
     * @param \Closure $callback
     * @param $eventProperties = [];
     * @return bool
     */
    private function executeMethod($eventBefore, $eventAfter, $module, \Closure $callback, $eventProperties = [])
    {
        $event = new ModuleEvent(array_merge(['module' => $module], $eventProperties));

        if (isset($eventBefore)) {
            $this->trigger($eventBefore, $event);
        }

        if ($event->isValid) {
            $result = call_user_func($callback);

            if (is_null($result)) {
                $result = true;
            }

            if ($result) {
                if (isset($eventAfter)) {
                    $event->handled = false;
                    $this->trigger($eventAfter, $event);
                }
                return true;
            }
        }

        return false;
    }

    /**
     * @param \yii\base\Module|AppModuleInterface $module
     * @param ConfigInterface $config
     * @param boolean $isReset
     * @param bool $isActivate
     * @return bool
     * @throws ManagerExceptionBase
     */
    protected function internalInstall($module, ConfigInterface $config, $isReset, $isActivate = false)
    {
        $result = $this->executeMethod(self::EVENT_BEFORE_INSTALL,
            self::EVENT_AFTER_INSTALL, $module, function () use ($module, $isReset) {
                return ($module->hasMethod('install') ? $module->install($isReset) : true);
            }, ['isReset' => $isReset, 'config' => $config]);

        if ($result && $isActivate) {
            $this->turnOn($module->uniqueId);
        }

        return $result;
    }

    /**
     * @param \yii\base\Module|AppModuleInterface $module
     * @param ConfigInterface $config
     * @param string $state
     * @return bool
     */
    protected function internalChangeState($module, $config, $state)
    {
        return $this->executeMethod(self::EVENT_BEFORE_CHANGE_STATE,
            self::EVENT_AFTER_CHANGE_STATE,
            $module, function () use ($config, $state, $module) {

                $config->changeState($state);

                if ($module->hasMethod('changedState')) {
                    $module->changedState($config->isEnabled());
                }
                return true;
            }, ['config' => $config]);

    }

    /**
     * @param $fileName
     * @param string|null $tmpPath
     * @param array|null $moduleClassInfo
     * @return ConfigInterface|null
     */
    public function readModuleZip($fileName, &$tmpPath = null, &$moduleClassInfo = null)
    {

        try {
            $tmpPath = $this->getTmpPath($fileName);

            $zip = new ZipArchive();
            $zip->open($fileName);
            $zip->extractTo($tmpPath);
            return $this->getConfigInitializerLocFile()
                ->readTemporaryConfig($tmpPath, $moduleClassInfo);
        } catch (\Exception $exception) {
            FileHelper::removeDirectory($tmpPath);
            throw  $exception;
        } catch (\Throwable $exception) {
            FileHelper::removeDirectory($tmpPath);
            throw  $exception;
        } finally {
            if (isset($zip)) {
                $zip->close();
            }

        }
    }


    /**
     * @return ConfigInitializerLocFile
     * @throws InvalidConfigException
     */
    public function getConfigInitializerLocFile()
    {
        return Yii::createObject(ConfigInitializerLocFile::class, [$this]);
    }

    /**
     * @param $fileName
     * @param ConfigInterface $config
     * @param bool|callable $onExists
     * @return bool
     * @throws ManagerExceptionBase
     * @throws \Throwable
     */
    public function unzip($fileName, &$config = null, $onExists = true)
    {
        try {

            $config = $this->readModuleZip($fileName, $tmp, $moduleClassInfo);

            if ($existsConfig = $this->getModuleConfigById($config->uniqueId)) {
                try {
                    if (is_callable($onExists)) {
                        $onExists = $onExists($config, $existsConfig);
                    }
                    if ($onExists) {
                        $r = $this->upgrade($existsConfig, $config, $fileName);
                        $config = $existsConfig;
                        return $r;
                    } else {
                        FileHelper::removeDirectory($existsConfig->path);
                    }
                } finally {
                    if ($onExists) {
                        FileHelper::removeDirectory($tmp);
                    }
                }
            }

            $this->installFiles($tmp, $config)->clearCache();

        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }

        return true;

    }


    /**
     * @param ConfigInterface|null $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function install($config)
    {
        try {
            $module = $this->loadModule(null, $config);
            if ($this->internalInstall($module, $config, false)) {
                /** @var Module $module */
                foreach ($config->modules as $id => $subModuleConfig) {
                    try {
                        $subModule = $this->loadModule(null, $subModuleConfig);
                        $this->internalInstall($subModule, $subModuleConfig, false);
                    } catch (\Exception $exception) {
                        $exception = new SubModuleException($this, 'Error on installation sub module ' . $subModuleConfig->uniqueId
                            . ' ' . $exception->getMessage(), $exception, $subModuleConfig);
                        $exception->submoduleConfig = $subModuleConfig;
                        throw $exception;
                    }
                }
            } else {
                return false;
            }
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
        return true;
    }


    /**
     * @param $fileName
     * @param ConfigInterface|null $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function unzipAndProcess($fileName, &$config = null)
    {
        try {

            $config = $this->readModuleZip($fileName, $tmp, $moduleClassInfo);

            if ($c = $this->getModuleConfigById($config->uniqueId)) {
                try {
                    $r = $this->upgrade($c, $config, $fileName);
                } finally {
                    FileHelper::removeDirectory($tmp);
                }
                $config = $c;
                return $r;
            }

            if (isset($config->parentModule)) {
                if (!$parentConfig = $this->getModuleConfigById($config->parentModule)) {
                    throw new ModuleNotFoundException($this, 'No found parent module ' . $config->parentModule,
                        null, $config);
                };

                if ($config->alias !== $parentConfig->alias) {
                    throw new InvalidModuleConfiguration($this, 'Parent module alias not same with owner',
                        null, $config);
                }

                $parentModule = $this->loadModule(null, $parentConfig);
                $parentModule->setModule($config->id,
                    ArrayHelper::merge(
                        $this->getArrayApplicationParams($config), ['class' => $moduleClassInfo['class']])
                );
            }
            /** @var ConfigInterface $dstConfig */

            $module =
                $this->installFiles($tmp, $config)
                    ->clearCache()
                    ->loadModule($config->uniqueId, $dstConfig);

            /** @var string  check module base path, after reading zip archive $path */
            $path = $config->getInstalledPath();
            if ($module->getBasePath() !== $path) {
                $module->setBasePath($path);
                //call init to re init defaults
                $module->init();
            }

            if ($this->internalInstall($module, $dstConfig, false, $this->isAutoActivate)) {

                foreach ($dstConfig->modules as $subConfig) {
                    try {
                        $this->internalInstall($this->loadModule($subConfig->uniqueId), $subConfig, false, true);
                    } catch (\Exception $exception) {
                        $exception = new SubModuleException($this, 'Error installed sub module ' . $subConfig->uniqueId
                            . ' ' . $exception->getMessage(), $exception, $dstConfig);
                        $exception->submoduleConfig = $subConfig;
                        throw $exception;
                    }
                }
            }

            $config = $dstConfig;

        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
        return true;
    }

    /**
     * @param $id
     * @param ConfigInterface|null $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function uninstall($id, &$config = null)
    {
        try {
            $module = $this->loadModule($id, $config);
            /** @var Module $module */
            foreach ($module->modules as $id => $sm) {
                try {
                    $subModule = $module->getModule($id);
                    if ($this->internalUnInstall($subModule, false)) {
                        FileHelper::removeDirectory($subModule->basePath);
                    }
                } catch (\Exception $exception) {
                    $config = $this->getModuleConfigById($module->getUniqueId());
                    $exception = new SubModuleException($this, 'Error Uninstalled sub module ' . $config->uniqueId
                        . ' ' . $exception->getMessage(), $exception, $config);
                    $exception->submoduleConfig = $config;
                    throw $exception;
                }
            }

            if ($this->internalUnInstall($module, false)) {
                FileHelper::removeDirectory($config->path);
            } else {
                return false;
            }
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
        return true;
    }


    /**
     * @param string $id
     * @param ConfigInterface|null $config
     * @throws ManagerExceptionBase
     */
    public function turnOn($id, &$config = null)
    {
        try {
            $module = $this->loadModule($id, $config);
            $this->internalChangeState($module, $config, ConfigInterface::STATE_ON);
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @param string $id
     * @param ConfigInterface|null $config
     * @throws ManagerExceptionBase
     */
    public function turnOff($id, &$config = null)
    {
        try {
            $module = $this->loadModule($id, $config);
            $this->internalChangeState($module, $config, ConfigInterface::STATE_OFF);
            $this->clearCache();

        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @param string $id
     * @param ConfigInterface|null $config
     * @throws ManagerExceptionBase
     */
    public function toggle($id, &$config = null)
    {
        try {
            $module = $this->loadModule($id, $config);
            $this->internalChangeState($module, $config, ConfigInterface::STATE_TOGGLE);
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
        $this->clearCache();
    }

    /**
     * @param $id
     * @param ConfigInterface|null $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function reset($id, &$config = null)
    {
        try {

            $this->resetToDefaultResolver();
            $config = $this->getModuleConfigById($id);

            $module = $this->loadModule($id);

            if ($this->internalUnInstall($module, true)) {
                $this->internalInstall($module, $config, true);
                $this->internalChangeState($module, $config, ($config->isEnabled()) ? ConfigInterface::STATE_ON : ConfigInterface::STATE_OFF);
            }
            if ($this->isAutoActivate) {
                $this->turnOn($id, $config);
            }
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
            return true;

    }

    /** Export module ti zip file
     * @param string $id module id
     * @param string $destinationPath destination path
     * @param boolean $withSubModules
     * @param ConfigInterface|null $config
     * @return string zip archive file name
     * @throws ManagerExceptionBase
     */
    public function export($id, $destinationPath, $withSubModules = true, &$config = null)
    {
        try {

            if (!$config = (isset($config)) ? $config : $this->getModuleConfigById($id)) {
                throw new ModuleNotFoundException($this, 'Unknown module ' . $id, null);
            }

            $fileName = \Yii::getAlias($destinationPath) . DIRECTORY_SEPARATOR .
                str_replace('/', '-', $config->uniqueId) . "_" . $config->version . ".zip";

            $zip = new ZipArchive();
            $zip->open($fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $except = ['*.loc', 'tests*', '*.yml', 'composer.*', '.*', '!.gitkeep', '/runtime'];

            if (!$withSubModules) {
                $except[] = '/modules';
            }

            foreach (FileHelper::findFiles($config->path, ['except' => $except]) as $file) {
                $parts = explode(DIRECTORY_SEPARATOR . $config->id . DIRECTORY_SEPARATOR, $file, 2);
                $zip->addFile($file, $parts[1]);
            }
            $zip->close();

        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($this, $exception->getMessage(), $exception, $config);
            } else {
                throw $exception;
            }
        }
        return $fileName;
    }


}