<?php
/**
 *
 * User: develop
 * Date: 03.10.2017
 */

namespace somov\appmodule\components;


use somov\appmodule\Config;
use somov\appmodule\exceptions\InvalidModuleConfiguration;
use somov\appmodule\exceptions\ManagerExceptionBase;
use somov\appmodule\exceptions\ModuleNotFoundException;
use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\common\helpers\ReflectionHelper;
use somov\common\traits\ContainerCompositions;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\Security;
use yii\base\UnknownMethodException;
use yii\caching\Cache;
use yii\caching\Dependency;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\Application;
use ZipArchive;


/**
 * Class ModuleManager
 * @package app\components
 *
 * @property Cache $cache
 */
class Manager extends Component implements BootstrapInterface
{
    use ContainerCompositions;

    CONST EVENT_BEFORE_INSTALL = 'beforeInstall';
    CONST EVENT_AFTER_INSTALL = 'afterInstall';

    CONST EVENT_BEFORE_UNINSTALL = 'beforeUnInstall';
    CONST EVENT_AFTER_UNINSTALL = 'afterUnInstall';

    CONST EVENT_BEFORE_CHANGE_STATE = 'beforeChangeState';
    CONST EVENT_AFTER_CHANGE_STATE = 'afterChangeState';

    CONST EVENT_BEFORE_UPGRADE = 'beforeUpgrade';
    CONST EVENT_AFTER_UPGRADE = 'afterUpgrade';

    /** Turn on module after reset oo install
     * @var bool
     */
    public $isAutoActivate = false;

    public $places = [
        'modules' => '@app/modules'
    ];

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
     * Bootstrap method to be called during application bootstrap stage.
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app)
    {
        $this->addAppModulesToApplication();
        $this->registerEvents();
    }


    /**
     * @return \yii\caching\CacheInterface|object
     */
    protected function getCache()
    {
        if (is_string($this->cacheConfig)) {
            return \Yii::$app->{$this->cacheConfig};
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
     * @return $this
     */
    public function clearCache()
    {
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
     * @param string|AppModuleInterface $class
     * @param $namespace
     * @param $path
     * @return Config|null
     */
    protected function createConfig($class, $namespace, $path)
    {
        if (in_array(AppModuleInterface::class, class_implements($class))) {

            $config = new Config([
                'runtime' => [
                    'namespace' => $namespace,
                    'class' => $class,
                    'path' => $path
                ]
            ]);
            $class::configure($config);
            $config->id = $class::getAppModuleId();
            $config->isEnabled();
            return $config;
        };

        return null;
    }

    /**
     * Созает копию класса модуля загружает и читает конфигурацию
     * @param $file
     * @param array $info оригинальная информация о классе модуля
     * @return null|Config
     */
    private function readConfig($file, &$info)
    {
        ReflectionHelper::initClassInfo($file, $info);

        $path = dirname($file);


        $suffix = ucfirst(strtr((new Security())->generateRandomString(10), ['_' => '', '-' => '']));
        $class = 'ReadModule' . $suffix;

        $content = preg_replace('/class\s*(Module)\s*extends/',
            'class ' . $class . ' extends', file_get_contents($file));

        $alias = $this->aliasFromNameSpace($info['namespace']);

        \Yii::setAlias($alias, $path);

        $reloadFile = $path . DIRECTORY_SEPARATOR . $class . '.php';

        file_put_contents($reloadFile, $content);

        $config = $this->createConfig($info['namespace'] . '\\' . $class, $info['namespace'], $path);

        \Yii::setAlias($alias, null);

        unlink($reloadFile);

        return $config;

    }

    protected function aliasFromNameSpace($space)
    {
        return str_replace('\\', '/', $space);
    }


    /** Инициализурет конфигурацию усиановленного модуля
     * @param string $file file name of module class
     * @return null|Config
     */
    private function initConfig($file)
    {

        if (!file_exists($file)) {
            return null;
        }

        ReflectionHelper::initClassInfo($file, $info);

        $path = dirname($file);

        $alias = $this->aliasFromNameSpace($info['namespace']);
        \Yii::setAlias($alias, $path);

        $config = $this->createConfig($info['class'], $info['namespace'], $path);

        return $config;
    }

    /** Массив конфигураций модулей
     * поиск по директориям $this->places если нет в кеше
     * @return Config[]
     */
    public function getModulesClassesList()
    {
        return $this->getCache()->getOrSet($this->getCacheKey(), function () {
            $r = [];
            foreach ($this->places as $place => $alias) {
                $r = array_merge($r, $this->findModulesConfig(Yii::getAlias($alias)));
            }
            return $r;
        }, null, $this->getCacheDependency());
    }

    /**
     * Поиск модулей в каталоге
     * @param string $path
     * @return Config[]
     */
    protected function findModulesConfig($path)
    {
        $r = [];
        $dirs = FileHelper::findDirectories($path, ['recursive' => false]);
        foreach ($dirs as $dir) {
            if ($config = $this->initConfig($dir . DIRECTORY_SEPARATOR . 'Module.php')) {
                $r[$config->id] = $config;
                $path = $config->path . DIRECTORY_SEPARATOR . 'modules';
                if (file_exists($path)) {
                    $config->addModules($this->findModulesConfig($path));
                }
            }
        }
        return $r;
    }

    /**
     * @param array $filter
     * @param Config[] $modules
     * @return Config[]
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
     * @return mixed|null|Config
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


    /**Параметры конфигураци приложения
     * @param Config $c
     * @return array
     */
    protected function getArrayApplicationParams(Config $c)
    {
        return [
            'class' => $c->class,
            'version' => $c->version,
            'modules' => array_map([$this, 'getArrayApplicationParams'], $c->modules)
        ];
    }

    /**
     * @param Config $config
     * @param bool $bootstrap
     */
    protected function addModule(Config $config, $bootstrap = true)
    {

        \Yii::$app->setModule($config->id, $this->getArrayApplicationParams($config));

        foreach ([$config] + $config->getModules() as $config) {

            $this->setModuleAlias($config);

            if (!empty($config->urlRules)) {
                \Yii::$app->urlManager->addRules($config->urlRules, $config->appendRoutes);
            }

            if ($bootstrap && $config->bootstrap) {
                $module = $this->loadModule(null, $config);
                if ($module instanceof BootstrapInterface) {
                    $module->bootstrap(\Yii::$app);
                }
            }
        }
    }


    /** Добавляет альяс класса модуля к загрузчику
     * @param Config $config
     */
    protected function setModuleAlias(Config $config)
    {
        if ($config->nameSpace !== ($this->baseNameSpace . '\\' . $config->id)) {
            \Yii::setAlias($config->nameSpace, $config->path);
        }
    }

    /** Добавляет классы активных модулей в конфигурацию приложения
     *  выключенным модулям прописываем псевдонимы каталогов загрузчика
     */
    private function addAppModulesToApplication()
    {
        foreach ($this->getModulesClassesList() as $config) {

            //игнорирование модулей которые не раюотают при xhr
            if (Yii::$app instanceof Application && Yii::$app->request->isAjax && !$config->xhrActive) {
                continue;
            }

            /**@var  Config $config */
            if ($config->isEnabled()) {
                $this->addModule($config);
            } else {
                $this->setModuleAlias($config);
            }
        }
    }

    /**
     * @param Config $config
     */
    private function addConfigEvents($config)
    {
        foreach ($config->events as $class => $classEvents) {
            foreach ($classEvents as $classEvent) {
                $append = true;
                if (is_array($classEvent)) {
                    if (empty($classEvent['name'])) {
                        throw  new InvalidConfigException('Attribute name required for class event ' . $class . ' options. Module ' . $config->id);
                    }
                    $name = $classEvent['name'];
                    $append = ArrayHelper::getValue($classEvent, 'append', true);
                } else {
                    $name = $classEvent;
                }
                Event::on($class, $name, [$this, '_eventByMethod'], [
                    'moduleConfig' => $config
                ], $append);
            }
        }
    }

    /** Регистрация событий*/
    private function registerEvents()
    {
        foreach ($this->getFilteredClassesList() as $config) {

            $this->addConfigEvents($config);

            foreach ($config->modules as $subModule) {
                if ($subModule->isEnabled()) {
                    $this->addConfigEvents($subModule);
                }
            }
        }
    }

    /**
     * @param Event $event
     * @return string
     */
    public static function generateMethodName($event)
    {
        $reflector = new \ReflectionClass($event->sender);
        $name = $reflector->getShortName();
        return lcfirst($name) . ucfirst($event->name);
    }

    /** Передача события объекту обработчику
     * @param Event $event
     */
    public function _eventByMethod($event)
    {
        /** @var Config $config */
        $config = $event->data['moduleConfig'];

        try {
            /** @var Module|AppModuleInterface $module */
            $module = $this->loadModule(null, $config);
        } catch (ModuleNotFoundException $exception) {
            $this->clearCache();
            return;
        }

        $handler = $module->getModuleEventHandler();
        $method = self::generateMethodName($event);

        if ($handler instanceof AppModuleEventHandler) {
            if ($handler->handle($event, $method)) {
                return;
            }
        }

        $m = (method_exists($handler, $method)) ? $method : 'handleModuleEvent';

        try {
            call_user_func_array([$handler, $m], ['event' => $event]);
        } catch (UnknownMethodException $exception) {
            $message = "Unknown method $method and  method handleModuleEvent in  " . get_class($handler);
            if (YII_DEBUG) {
                throw new ManagerExceptionBase($message, $exception, $this);
            }
            Yii::error($message);
        }
    }


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


    /** Загружает или находит существующий объект модуля
     * если модуль не добален в приложение добавлет и возвращает его екземляр
     * @param $id
     * @param $config
     * @param bool $bootstrap
     * @return AppModuleInterface|Module
     * @throws ModuleNotFoundException
     */
    public function loadModule($id, &$config = null, $bootstrap = false)
    {
        if (!$config = (isset($config)) ? $config : $this->getModuleConfigById($id)) {
            throw new ModuleNotFoundException('Unknown module ' . $id, null, $this);
        }

        if (!$module = $config->getModuleInstance()) {

            if (!$module = \Yii::$app->getModule($config->getUniqueId())) {
                $this->addModule($config, $bootstrap);
                $module = \Yii::$app->getModule($id);
            }
        }

        if (empty($module)) {
            $id = (isset($config)) ? $config->id : $id;
            throw new ModuleNotFoundException('Unknown module ' . $id, null, $this);
        }

        return $module;

    }

    /** Обновление модуля
     * @param Config $exist
     * @param Config $new
     * @param string $fileName
     * @return bool
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     */
    protected function upgrade(Config $exist, Config $new, $fileName)
    {
        if (version_compare($exist->version, $new->version, '>=')) {
            return true;
        }
        /** @var Module|AppModuleInterface $instance */
        $instance = \Yii::createObject($new->class, [$new->id, \Yii::$app]);

        $this->trigger(self::EVENT_BEFORE_UPGRADE, new ModuleEvent(['module' => $instance]));

        if ($instance->hasMethod('upgrade') && !$instance->upgrade()) {
            return false;
        }
        FileHelper::removeDirectory($exist->path);
        rename($new->path, $exist->path);

        $instance->version = $new->version;

        $this->clearCache();

        $this->trigger(self::EVENT_AFTER_UPGRADE, new ModuleUpgradeEvent([
            'module' => $instance,
            'newVersion' => $new->version,
            'oldVersion' => $exist->version,
            'fileName' => $fileName
        ]));

        return true;
    }

    /**
     * @param string $filesPath
     * @param Config $config
     * @return $this
     */
    protected function installFiles($filesPath, Config $config)
    {
        $path = $config->getInstalledPath();
        FileHelper::createDirectory($path);
        rename($filesPath, $path);
        return $this;
    }


    /**
     * @param string $method
     * @param string $eventBefore
     * @param string $eventAfter
     * @param Module|AppModuleInterface $module
     * @param object|null $target
     * @return bool
     */
    private function executeMethod($method, $eventBefore, $eventAfter, $module, $target = null)
    {
        $event = new ModuleEvent(['module' => $module]);

        $target = ($target) ? $target : $module;

        if (isset($eventBefore)) {
            $this->trigger($eventBefore, $event);
        }

        if ($event->isValid) {

            try {
                $result = call_user_func([$target, $method]);
            } catch (UnknownMethodException $exception) {
                $r = new \ReflectionMethod($target, $method);
                $r->setAccessible(true);
                $result = $r->invoke($target);
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
     * @param $module \yii\base\Module|AppModuleInterface
     * @return bool
     */
    protected function internalUnInstall($module)
    {
        return $this->executeMethod('uninstall', self::EVENT_BEFORE_UNINSTALL,
            self::EVENT_AFTER_UNINSTALL, $module);
    }

    /**
     * @param $module \yii\base\Module|AppModuleInterface
     * @return bool
     */
    protected function internalInstall($module)
    {
        return $this->executeMethod('install', self::EVENT_BEFORE_INSTALL,
            self::EVENT_AFTER_INSTALL, $module);
    }

    /**
     * @param $module \yii\base\Module|AppModuleInterface
     * @param Config $config
     * @param string $state
     */
    protected function internalChangeState($module, $config, $state)
    {
        $this->executeMethod($state, self::EVENT_BEFORE_CHANGE_STATE,
            self::EVENT_AFTER_CHANGE_STATE,
            $module, $config);
    }


    /**
     * @param $fileName
     * @param Config $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function install($fileName, &$config)
    {
        try {
            $tmp = $this->getTmpPath($fileName);
            $zip = new ZipArchive();
            $zip->open($fileName);
            $zip->extractTo($tmp);

            $file = $tmp . DIRECTORY_SEPARATOR . 'Module.php';

            $config = $this->readConfig($file, $moduleClassInfo);

            if ($c = $this->getModuleConfigById($config->uniqueId)) {
                if ($this->upgrade($c, $config, $fileName)) {
                    return true;
                }
            }

            if (isset($config->parentModule)) {
                if (!$parentConfig = $this->getModuleConfigById($config->parentModule)) {
                    throw new ModuleNotFoundException('No found parent module ' . $config->parentModule,
                        null, $this, $config);
                };

                if ($config->alias !== $parentConfig->alias) {
                    throw new InvalidModuleConfiguration('Parent module alias not same with owner',
                        null, $this, $config);
                }

                $parentModule = $this->loadModule(null, $parentConfig);
                $parentModule->setModule($config->id,
                    ArrayHelper::merge(
                        $this->getArrayApplicationParams($config), ['class' => $moduleClassInfo['class']])
                );
            }

            $module =
                $this->installFiles(dirname($file), $config)
                    ->clearCache()
                    ->loadModule($config->uniqueId, $dstConfig);

            if ($this->internalInstall($module)) {
                if ($this->isAutoActivate) {
                    $this->turnOn(null, $dstConfig);
                }
            }

            $config = $dstConfig;
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
        return true;
    }

    /**
     * @param $id
     * @param $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function uninstall($id, &$config)
    {
        try {
            $module = $this->loadModule($id, $config);
            if ($this->internalUnInstall($module)) {
                FileHelper::removeDirectory($config->path);
            }
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
        return true;
    }


    /**
     * @param string $id
     * @param Config $config
     * @throws ManagerExceptionBase
     */
    public function turnOn($id, &$config)
    {
        try {
            $module = $this->loadModule($id, $config);
            $this->internalChangeState($module, $config, 'turnOn');
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @param string $id
     * @param Config $config
     * @throws ManagerExceptionBase
     */
    public function turnOff($id, &$config)
    {
        try {
            $module = $this->loadModule($id, $config);
            $this->internalChangeState($module, $config, 'turnOff');
            $this->clearCache();

        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
    }

    /**
     * @param string $id
     * @param Config $config
     * @throws ManagerExceptionBase
     */
    public function toggle($id, &$config)
    {
        try {
            $module = $this->loadModule($id, $config);
            $this->internalChangeState($module, $config, 'toggle');
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
        $this->clearCache();
    }

    /**
     * @param $id
     * @param Config $config
     * @return bool
     * @throws ManagerExceptionBase
     */
    public function reset($id, &$config)
    {
        try {
            $module = $this->loadModule($id, $config);

            if ($this->internalUnInstall($module)) {
                $this->internalInstall($module);
            }
            if ($this->isAutoActivate) {
                $this->turnOn($id, $config);
            }
            $this->clearCache();
        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
        return true;

    }

    /** Export module ti zip file
     * @param string $id module id
     * @param string $destinationPath destination path
     * @param Config $config
     * @return string zip archive file name
     * @throws ManagerExceptionBase
     */
    public function export($id, $destinationPath, &$config)
    {
        try {

            if (!$config = (isset($config)) ? $config : $this->getModuleConfigById($id)) {
                throw new ModuleNotFoundException('Unknown module ' . $id, null, $this);
            }

            $fileName = \Yii::getAlias($destinationPath) . DIRECTORY_SEPARATOR .
                $config->id . "_" . $config->version . ".zip";

            $zip = new ZipArchive();
            $zip->open($fileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            foreach (FileHelper::findFiles($config->path, [
                'except' => ['*.loc', 'tests*', '*.yml', 'composer.*', '.*', '!.gitkeep']
            ]) as $file) {
                $parts = explode('/' . $id . '/', $file);
                $zip->addFile($file, '/' . $parts[1]);
            }

            $zip->close();

        } catch (\Exception $exception) {
            if (!($exception instanceof ManagerExceptionBase)) {
                throw new ManagerExceptionBase($exception->getMessage(), $exception, $this, $config);
            } else {
                throw $exception;
            }
        }
        return $fileName;
    }


}