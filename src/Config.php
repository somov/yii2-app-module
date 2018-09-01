<?php


namespace somov\appmodule;


use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Конфигурация модулей приожения
 *
 *
 * @property string class
 * @property string nameSpace
 * @property string path
 * @property boolean enabled
 * @property array implements
 *
 * Class ModuleConfig
 * @package app\components
 */
class Config extends BaseObject implements \Serializable, \ArrayAccess
{

    const METHOD_TYPE_EVENT_BY_METHOD = '_eventByMethod';

    const METHOD_TYPE_EVENT_TO_EVENT = '_eventToEventObject';

    const LOC_FILE = 'enabled.loc';

    /** процедура обработки события = eventByMethod | eventToEventObject
     * @var string
     */
    public $eventMethod = self::METHOD_TYPE_EVENT_TO_EVENT;

    private const RUNTIME_FIELDS = [
        'alias',
        'path',
        'namespace',
        'class',
        'enabled'
    ];

    /** Класс настроек модуля
     * @var string
     */
    public $settingsModel;

    /** Маршрут настроек
     * @var string
     */
    public $settingsRoute;

    public $settingsView;

    public $settingsRouteIcon = 'none';

    public $name;

    public $description;

    public $version;

    public $category = 'Not set';

    public $author;

    public $events = [];

    public $urlRules = [];

    public $appendRoutes = false;

    public $bootstrap = false;

    public $parentModule;

    /** @var  Config[] */
    public $modules = [];

    public $id;

    private $path;

    /** @var  String */
    private $namespace;

    /** @var  String */
    private $class;

    /** @var  boolean */
    private $enabled;

    /**
     * @var  string
     */
    protected $alias;


    public function __construct(array $config = [])
    {
        $runtime = ArrayHelper::remove($config, 'runtime');
        $this->alias = ArrayHelper::remove($config, 'alias');

        parent::__construct($config);

        foreach ($runtime as $key => $value) {
            $this->$key = $value;
        }
    }

    public function serialize()
    {
        $array = ArrayHelper::toArray($this) + array_combine(self::RUNTIME_FIELDS, array_map(function ($a) {
                return $this->$a;
            }, self::RUNTIME_FIELDS));

        return serialize($array);
    }

    public function unserialize($serialized)
    {
        foreach (unserialize($serialized) as $property => $value) {
            $this->$property = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getPath()
    {

        return $this->path;
    }

    /**
     * @return String
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return String
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /** Проверяем и преобразуем в верблюжий регистр
     *
     * @param string $offset
     */
    private function normalizeOffset(&$offset)
    {
        if (strpos($offset, '_') > 0) {
            $offset = lcfirst(Inflector::id2camel(str_replace('_', '-', $offset)));
        }
    }

    public function offsetExists($offset)
    {
        $this->normalizeOffset($offset);
        return isset($this->$offset);
    }


    public function offsetGet($offset)
    {
        $this->normalizeOffset($offset);
        return $this->offsetExists($offset) ? $this->$offset : null;
    }


    public function offsetSet($offset, $value)
    {
        throw new NotSupportedException();
    }


    public function offsetUnset($offset)
    {
        throw new NotSupportedException();
    }


    /** Настройки модуля
     * @return Model
     * @throws InvalidConfigException
     */
    public function getSettingsInstance()
    {
        if (empty($this->settingsModel)) {
            throw new InvalidConfigException('Module not configured with settings');
        }

        $instance = call_user_func([$this->settingsModel, 'instance']);

        if ($instance->hasMethod('loadSettings')) {
            return $instance->loadSettings();
        }

        return $instance;
    }

    public function getImplements()
    {
        return class_implements($this->class);
    }

    public function getInstalledPath()
    {
        $aliases = [
            $this->alias
        ];

        if (isset($this->parentModule)) {
            $aliases[] = $this->parentModule;
            $aliases[] = 'modules';
        }

        $aliases[] = $this->id;

        return \Yii::getAlias(implode(DIRECTORY_SEPARATOR, $aliases));
    }


    public function isEnabled()
    {
        $this->enabled = file_exists($this->getLocFile());
        return $this->enabled;
    }

    /**
     * @return $this
     */
    public function turnOn()
    {
        if ($this->isEnabled()) {
            return $this;
        }
        if (file_put_contents($this->getLocFile(), '') !== false) {
            $this->enabled = true;
        };

        return $this;
    }

    /**
     * @return $this
     */
    public function turnOff()
    {
        if (!$this->isEnabled()) {
            return $this;
        }

        unlink($this->getLocFile());
        $this->isEnabled();

        return $this;
    }

    /**
     * @return $this
     */
    public function toggle()
    {
        if ($this->isEnabled()) {
            $this->turnOff();
        } else {
            $this->turnOn();
        }
        return $this;
    }

    /**
     * @return bool
     */
    private function getLocFile()
    {
        return $this->getInstalledPath() . DIRECTORY_SEPARATOR . self::LOC_FILE;
    }

}