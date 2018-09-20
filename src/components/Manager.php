<?php
/**
 *
 * User: develop
 * Date: 03.10.2017
 */

namespace somov\appmodule\components;


use somov\appmodule\Config;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\common\helpers\ReflectionHelper;
use somov\common\traits\ContainerCompositions;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\base\Exception;
use yii\base\Module;
use yii\base\Security;
use yii\caching\Cache;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;
use yii\web\Application;


/**
 * Class ModuleManager
 * @package app\components
 *
 * @property Cache $cache
 */
class Manager extends Component implements BootstrapInterface
{
    use ContainerCompositions;

    public $places = [
        'modules' => '@app/modules'
    ];

    public $baseNameSpace = 'app\modules';

    public $processAjax = false;

    /**
     * @var array|string
     */
    public $cacheConfig = [
        'class' => 'yii\caching\FileCache',
        'keyPrefix' => 'modules'
    ];

    /**
     * Bootstrap method to be called during application bootstrap stage.
     * @param \yii\base\Application $app the application currently running
     */
    public function bootstrap($app)
    {
        if (!$this->processAjax && $app instanceof Application && $app->request->isAjax) {
            return;
        }

        $this->addAppModulesToApplication();
        $this->registerEvents();
    }


    /**
     * @return \yii\caching\CacheInterface
     */
    protected function getCache()
    {
        if (is_string($this->cacheConfig)) {
            return \Yii::$app->{$this->cacheConfig};
        }
        $config = $this->cacheConfig;
        return $this->getComposition(ArrayHelper::remove($config, 'class'), $config);
    }

    private function getCacheKey()
    {
        return __CLASS__ . 'modulesList';
    }

    /**
     * @param string $file file name of module class
     * @param bool $reloadClass
     * @return null|Config
     */
    private function initConfig($file, $reloadClass = false)
    {

        if (!file_exists($file)) {
            return null;
        }

        ReflectionHelper::initClassInfo($file, $info);

        $path = dirname($file);

        /**@var AppModuleInterface $class */
        $class = $info['class'];

        if (class_exists($class) && $reloadClass) {
            $suffix = ucfirst(strtr((new Security())->generateRandomString(10), ['_' => '', '-' => '']));
            $content = preg_replace('/class\s*(Module)\s*extends/',
                'class Module' . $suffix . ' extends', file_get_contents($file));
            $reloadFile = $path . DIRECTORY_SEPARATOR . 'Module' . $suffix . '.php';
            file_put_contents($reloadFile, $content);
            $config = $this->initConfig($reloadFile);
            unlink($reloadFile);
            return $config;
        }

        \Yii::setAlias($info['namespace'], $path);
        \Yii::$classMap[(string)$class] = $file;

        if (in_array(AppModuleInterface::class, class_implements($class))) {

            $config = new Config([
                'runtime' => [
                    'namespace' => $info['namespace'],
                    'class' => $class,
                    'path' => $path
                ]
            ]);

            $class::configure($config);
            $config->isEnabled();
            return $config;
        };

        return null;
    }

    /** Массив конфигураций модулей = ['id' => '', 'path' => '', 'enabled', 'class']
     * если нет в кеше - обходит каталог из альяса @modulesAlias
     * @return Config[]
     */
    public function getModulesClassesList()
    {

        $places = $this->places;

        return $this->getCache()->getOrSet($this->getCacheKey(), function () use ($places) {
            $r = [];
            foreach ($places as $place => $alias) {
                foreach (FileHelper::findFiles(\Yii::getAlias($alias), [
                    'only' => ['pattern' => '*Module.php']
                ]) as $file) {
                    if ($config = $this->initConfig($file)) {
                        if (isset($config->parentModule) && isset($r[$config->parentModule])) {
                            /** @var Config $parent */
                            $parent = $r[$config->parentModule];
                            $parent->addModules([
                                $config->id => [
                                    'class' => $config->class,
                                    'version' => $config->version
                                ]
                            ]);
                        }
                        $r[$config->id] = $config;
                    }
                }
            }
            return $r;
        });
    }


    /**
     * @param array $filter
     * @return Config[]
     */
    public function getFilteredClassesList($filter = ['enabled' => true])
    {
        if (empty($filter)) {
            return $this->getModulesClassesList();
        }

        return array_filter($this->getModulesClassesList(), function ($a) use ($filter) {
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
        if ($list = $this->getFilteredClassesList(['id' => $id])) {
            return reset($list);
        }
        return null;
    }

    /**
     * @param Config $config
     */
    protected function addModule(Config $config)
    {

        \Yii::$app->setModule($config->id, [
            'class' => $config->class,
            'version' => $config->version,
            'modules' => $config->modules
        ]);

        if ($config->nameSpace !== ($this->baseNameSpace . '\\' . $config->id)) {
            \Yii::setAlias($config->nameSpace, $config->path);
        }

        if (!empty($config->urlRules)) {
            \Yii::$app->urlManager->addRules($config->urlRules, $config->appendRoutes);
        }

        if ($config->bootstrap) {
            $module = \Yii::$app->getModule($config->id);
            if ($module instanceof BootstrapInterface) {
                $module->bootstrap(\Yii::$app);
            }
        }
    }

    /** Добавляет классы модулей в конфигурацию приложения
     * @param array $filter
     */
    private function addAppModulesToApplication($filter = ['enabled' => true])
    {
        foreach ($this->getFilteredClassesList($filter) as $config) {
            /**@var  Config $config */
            $this->addModule($config);
        }
    }

    /** Регистрация событий*/
    private function registerEvents()
    {
        foreach ($this->getFilteredClassesList() as $config) {
            if ($events = $config->events) {
                foreach ($events as $class => $classEvents) {
                    foreach ($classEvents as $classEvent) {
                        Event::on($class, $classEvent, [$this, $config->eventMethod], [
                            'moduleConfig' => $config
                        ]);
                    }
                }
            }
        }
    }

    public static function generateMethodName($event)
    {
        $reflector = new \ReflectionClass($event->sender);
        $name = $reflector->getShortName();
        //Удаляем суффикс классов моделей из тестов
        $name = strtr($name, ['Clone' => '']);

        return lcfirst($name) . ucfirst($event->name);
    }

    /** Передача событий модулю
     * имя метода должно называется  имя_объекта_событияСобытие
     * не хочется чтобы клас модуля раздувался обработчиками событий
     * решил перенести обработку в специальный объект
     * @param Event $event
     */
    public function _eventByMethod($event)
    {
        $module = \Yii::$app->getModule($event->data['moduleConfig']->id);
        $method = self::generateMethodName($event);

        if (method_exists($module, $method)) {
            call_user_func_array([$module, $method], ['event' => $event]);
        } else {
            $this->_eventToEventObject($event, $module);
        }
    }

    /**
     * @param $event
     * @param AppModuleInterface $module
     * @return void
     */
    public function _eventToEventObject($event, AppModuleInterface $module = null)
    {
        $module = ($module) ? $module : \Yii::$app->getModule($event->data['moduleConfig']->id);
        if ($handler = $module->getModuleEventHandler()) {
            $handler->handleModuleEvent($event, $module);
        } else {
            throw new \RuntimeException("$module->id not valid App module");
        }
    }


    public function clearCache()
    {
        $this->getCache()->offsetUnset($this->getCacheKey());
        return $this;
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


    /**
     * @param $id
     * @param $config
     * @return null|\yii\base\Module|AppModuleInterface
     * @throws \yii\base\Exception
     */
    public function loadModule($id, &$config)
    {
        if (!$config = $this->getModuleConfigById($id)) {
            throw new Exception('Unknown module ' . $id);
        }
        $this->addAppModulesToApplication(['id' => $id]);
        return \Yii::$app->getModule($id);
    }

    /**
     * @param Config $exist
     * @param Config $new
     * @return bool
     */
    protected function upgrade(Config $exist, Config $new)
    {
        if (version_compare($exist->version, $new->version, '>=')) {
            return true;
        }
        /** @var Module $instance */
        $instance = \Yii::createObject($new->class, ['id' => $new->id, \Yii::$app]);
        $instance->version = $new->version;
        if ($instance->hasMethod('upgrade') && !$instance->upgrade()) {
            return false;
        }
        FileHelper::removeDirectory($exist->path);
        rename($new->path, $exist->path);
        $this->clearCache();
        return true;
    }

    protected function installFiles($filesPath, Config $config)
    {
        $path = $config->getInstalledPath();
        FileHelper::createDirectory($path);
        rename($filesPath, $path);
    }

    public function install($fileName, &$error)
    {
        try {
            $tmp = $this->getTmpPath($fileName);
            $zip = new \ZipArchive();
            $zip->open($fileName);
            $zip->extractTo($tmp);

            $file = $tmp . DIRECTORY_SEPARATOR . 'Module.php';

            if (!$config = $this->initConfig($file, true)) {
                throw new \RuntimeException('Error init module config');
            };

            if ($c = $this->getModuleConfigById($config->id)) {
                if ($this->upgrade($c, $config)) {
                    return true;
                }
            }

            $this->installFiles(dirname($file), $config);

            $this->clearCache();

            $config = $this->getModuleConfigById($config->id);

            $this->addModule($config);

            /** @var AppModuleInterface $module */
            $module = \Yii::$app->getModule($config->id);

            if ($module->install()) {
                $config->turnOn();
            }

        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            return false;
        }

        return true;
    }

    public function uninstall($id, &$error)
    {
        try {
            $module = $this->loadModule($id, $config);
            if ($module->uninstall()) {
                FileHelper::removeDirectory($config['path']);
            }
            $this->clearCache();
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            return false;
        }

        return true;
    }

    /**
     * @param $id
     * @param Config $config
     * @param $error
     * @return bool
     */
    public function reset($id, &$config, &$error)
    {
        try {
            $module = $this->loadModule($id, $config);
            if ($module->uninstall()) {
                $module->install();
            }
            $config->turnOn();
            $this->clearCache();
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            return false;
        }

        return true;

    }

    /**
     * @param array $filter
     * @param bool $flushCache
     * @return array
     */
    public function getCategoriesArray($filter = [], $flushCache = false)
    {
        if ($flushCache) {
            $this->clearCache();
        }
        $models = $this->getFilteredClassesList($filter);

        if (empty($models)) {
            return [];
        }

        return array_map(function ($d) {
            return [
                'count' => count($d),
                'modules' => $d,
                'caption' => $d[0]->category
            ];
        }, ArrayHelper::index($models, null, 'category'));

    }

}