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
use yii\caching\Cache;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
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

    const LOC_FILE = 'enabled.loc';

    public $modulesAlias = '@app/modules';

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


    /** Массив конфигураций модулей = ['id' => '', 'path' => '', 'enabled', 'class']
     * если нет в кеше - обходит каталог из альяса @modulesAlias
     * @return Config[]
     */

    public function getModulesClassesList()
    {
        $alias = $this->modulesAlias;

        $configs = $this->getCache()->getOrSet($this->getCacheKey(), function () use ($alias) {
            $r = [];
            foreach (FileHelper::findFiles(\Yii::getAlias($this->modulesAlias), [
                'only' => ['pattern' => '*/Module.php']
            ]) as $file) {
                ReflectionHelper::initClassInfo($file, $info);
                $id = basename(dirname($file));
                $path = dirname($file);

                if ($info['namespace'] !== ($this->baseNameSpace . '\\' . $id)) {
                    \Yii::setAlias($info['namespace'], $path);
                }

                /**@var AppModuleInterface $class */
                $class = $info['class'];

                if (in_array(AppModuleInterface::class, class_implements($class))) {

                    $config = new Config([
                        'runtime' => [
                            'namespace' => $info['namespace'],
                            'class' => $class,
                            'id' => $id,
                            'path' => $path,
                            'enabled' => $this->isEnabled($id),
                        ]
                    ]);

                    $class::configure($config);

                    $r[] = $config;
                };
            }
            return $r;
        });

        return $configs;
    }


    /**
     * @param $id
     * @return AppModuleInterface|string $class
     */
    protected function getModuleClass($id)
    {
        return $this->baseNameSpace . '\\' . $id . '\\Module';
    }

    /**
     * @param array $filter
     * @return Config[]
     */
    public function getFilteredClassesList($filter = ['enabled' => true])
    {
        $key = key($filter);
        $val = reset($filter);
        return array_filter($this->getModulesClassesList(), function ($a) use ($key, $val) {
            return $a->$key == $val;
        });
    }

    public function getModuleConfigById($id)
    {
        if ($list = $this->getFilteredClassesList(['id' => $id])) {
            return reset($list);
        }
        return null;
    }


    /** Добавляет классы модулей в конфигурацию приложения
     * @param array $filter
     */
    private function addAppModulesToApplication($filter = ['enabled' => true])
    {

        foreach (ArrayHelper::index($this->getFilteredClassesList($filter), 'id')
                 as $id => $config) {
            /**@var  Config $config */
            \Yii::$app->setModule($id, [
                'class' => $config->class
            ]);

            if ($config->nameSpace !== ($this->baseNameSpace . '\\' . $id)) {
                \Yii::setAlias($config->nameSpace, $config->path);
            }

            if (!empty($config->urlRules)) {
                \Yii::$app->urlManager->addRules($config->urlRules, $config->appendRoutes);
            }

            if ($config->bootstrap) {
                $module = \Yii::$app->getModule($id);
                if ($module instanceof BootstrapInterface) {
                    $module->bootstrap(\Yii::$app);
                }
            }
        }
    }

    /** Фабрика объектов активных или не активных модулей
     * @return array
     */
    public function getModulesInstances()
    {
        $modules = [];
        foreach ($this->getModulesClassesList() as $list) {
            if ($module = \Yii::$app->getModule($list['id'])) {
                $modules[] = $module;
            }
        }
        return $modules;
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
        $module = \Yii::$app->getModule($event->data['moduleConfig']['id']);

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

    /**
     * @param $id
     * @return bool
     */
    public function isEnabled($id)
    {
        return file_exists($this->getLocFile($id));
    }

    /**
     * @param $id
     * @return $this
     */
    protected function turnOn($id)
    {
        if ($this->isEnabled($id)) {
            return $this;
        }
        file_put_contents($this->getLocFile($id), '');
        $this->clearCache();
        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    protected function turnOff($id)
    {
        if (!$this->isEnabled($id)) {
            return $this;
        }
        unlink($this->getLocFile($id));
        $this->clearCache();
        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    public function toggle($id)
    {
        if ($this->isEnabled($id)) {
            $this->turnOff($id);
        } else {
            $this->turnOn($id);
        }
        return $this;
    }

    /**
     * @param $id
     * @return bool|string
     */
    private function getLocFile($id)
    {
        return \Yii::getAlias($this->modulesAlias . DIRECTORY_SEPARATOR .
            $id . DIRECTORY_SEPARATOR . self::LOC_FILE);
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

    public function upgrade($id, $file)
    {
        //TODO upgrade
        return true;
    }

    public function install($fileName, &$error)
    {

        try {
            $tmp = $this->getTmpPath($fileName);
            $zip = new \ZipArchive();
            $zip->open($fileName);
            $zip->extractTo($tmp);

            $id = basename(glob($tmp . '/*', GLOB_ONLYDIR)[0]);
            $file = $tmp . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'Module.php';
            $class = $this->getModuleClass($id);

            if (!class_exists($class)) {
                include $file;
            } else {
                return $this->upgrade($id, $file);
            }

            rename(dirname($file), \Yii::getAlias($this->modulesAlias . DIRECTORY_SEPARATOR .  $id));

            $this->clearCache()->addAppModulesToApplication(['id' => $id]);

            /** @var AppModuleInterface $module */
            $module = \Yii::$app->getModule($id);

            if ($module->install()) {
                $this->turnOn($id);
            }

            $this->clearCache();

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
     * @param $config
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
            $config = $this->turnOn($id)
                ->clearCache()
                ->getModuleConfigById($id);
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            return false;
        }

        return true;

    }


}