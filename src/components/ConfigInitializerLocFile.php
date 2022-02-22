<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.06.20
 * Time: 13:08
 */

namespace somov\appmodule\components;


use somov\appmodule\exceptions\ManagerExceptionBase;
use somov\appmodule\interfaces\AppModuleInterface;
use somov\appmodule\interfaces\ConfigInterface;
use somov\appmodule\interfaces\ManagerConfigResolver;
use somov\common\helpers\ArrayHelper;
use somov\common\helpers\FileHelper;
use somov\common\helpers\ReflectionHelper;
use yii\base\BaseObject;
use yii\base\Security;

/**
 * Class ConfigInitializer
 * @package somov\appmodule\components
 */
class ConfigInitializerLocFile extends BaseObject implements ManagerConfigResolver
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var string
     */
    public $moduleFileName = 'Module.php';

    /**
     * @var string|boolean
     */
    public $subModulesFolder = 'modules';


    /**
     * ConfigInitializer constructor.
     * @param Manager $manager
     * @param array $config
     */
    public function __construct(Manager $manager, array $config = [])
    {
        $this->manager = $manager;
        parent::__construct($config);
    }

    /**
     * @param $space
     * @return mixed
     */
    public static function aliasFromNameSpace($space)
    {
        return str_replace('\\', '/', $space);
    }


    /**
     * @param bool $withSubModules
     * @return ConfigInterface[]
     */
    public function resolve($withSubModules = true)
    {
        $r = [];

        foreach ($this->manager->places as $place => $alias) {
            $r = array_merge($r, $this->findModulesConfig(\Yii::getAlias($alias), $withSubModules));
        }
        return $r;

    }

    /**
     * Поиск модулей в каталоге
     * @param string $path
     * @param $withSubModules
     * @param ConfigInterface }$parentConfig
     * @return ConfigInterface[]
     * @throws ManagerExceptionBase
     */
    protected function findModulesConfig($path, $withSubModules, $parentConfig = null)
    {
        $r = [];
        if (empty($path)) {
            return $r;
        }
        $dirs = FileHelper::findDirectories($path, ['recursive' => false]);
        foreach ($dirs as $dir) {
            if ($config = $this->readConfig($dir, $withSubModules, $parentConfig)) {
                $r[] = $config;
            }
        }
        return $r;
    }

    /**
     * @param string $path
     * @param bool $withSubmodules
     * @param ConfigInterface|null $parentConfig
     * @return ConfigInterface
     * @throws ManagerExceptionBase
     */
    public function readConfig($path, $withSubmodules = true, $parentConfig = null)
    {
        $fileName = $path . DIRECTORY_SEPARATOR . $this->moduleFileName;

        if (!file_exists($fileName)) {
            return null;
        }

        ReflectionHelper::initClassInfo($fileName, $info);

        $alias = self::aliasFromNameSpace($info['namespace']);

        \Yii::setAlias($alias, $path);

        $config = $this->createConfig($info['class'], $info['namespace'], $path, $parentConfig);

        if ($this->subModulesFolder && $withSubmodules) {
            $subPath = $path . DIRECTORY_SEPARATOR . $this->subModulesFolder;
            if (is_dir($subPath)) {
                $config->modules = $this->findModulesConfig($subPath, false, $config);
            }
        }

        return $config;

    }

    /**
     * Создает копию класса модуля загружает и читает конфигурацию
     * @param $path
     * @param array $info оригинальная информация о классе модуля
     * @return null|ConfigInterface
     */
    public function readTemporaryConfig($path, &$info = null)
    {
        $fileName = $path . DIRECTORY_SEPARATOR . $this->moduleFileName;
        ReflectionHelper::initClassInfo($fileName, $info);


        $suffix = ucfirst(strtr((new Security())->generateRandomString(10), ['_' => '', '-' => '']));
        $class = 'ReadModule' . $suffix;

        $content = preg_replace('/class\s*(Module)\s*extends/',
            'class ' . $class . ' extends', file_get_contents($fileName));

        $info['alias'] = $alias = self::aliasFromNameSpace($info['namespace']);

        \Yii::setAlias($alias, $path);

        $reloadFile = $path . DIRECTORY_SEPARATOR . $class . '.php';

        file_put_contents($reloadFile, $content);

        $config = $this->createConfig($info['namespace'] . '\\' . $class, $info['namespace'], $path);

        \Yii::setAlias($alias, null);

        unlink($reloadFile);

        return $config;

    }

    /**
     * @param string|AppModuleInterface $class
     * @param $namespace
     * @param $path
     * @param ConfigInterface $parentConfig
     * @return ConfigInterface|null
     * @throws ManagerExceptionBase
     * @throws \yii\base\InvalidConfigException
     */
    protected function createConfig($class, $namespace, $path, $parentConfig = null)
    {

        if (in_array(AppModuleInterface::class, class_implements($class))) {
            /** @var ConfigInterface $config */

            $config = \Yii::createObject($this->manager->configOptions, [$this->manager]);

            $config->class = $class;
            $config->nameSpace = $namespace;
            $config->path = $path;

            try {

                $class::configure($config);
                $config->alias = ArrayHelper::getValue($this->manager->places, $config->type, ConfigBase::DEFAULT_TYPE);

                $id = $class::getAppModuleId();

                if (is_null($config->handler)) {
                    $config->handler = [];
                }


                if (strpos($id, '/') !== false) {
                    list ($parent, $id) = explode('/', $id);

                    if (is_null($parentConfig)) {
                        if (!$parentConfig = $this->manager->getModuleConfigById($parent)) {
                            throw new \Exception('Parent module not found');
                        }

                    }
                    $config->alias = $parentConfig->alias;
                    $config->id = $id;
                    $config->parentModule = $parentConfig->id;
                } else {
                    $config->id = $id;
                }
                $config->isEnabled();

            } catch (\Exception $exception) {
                throw  new ManagerExceptionBase($this->manager, 'Unable configure module '
                    . $config->class .'.'. $exception->getMessage(), $exception, $config);
            } catch (\Throwable $exception) {
                throw  new ManagerExceptionBase($this->manager, 'Unable configure module'
                    . $config->class .'.'. $exception->getMessage(), $exception, $config);
            }

            return $config;
        };

        return null;
    }

}