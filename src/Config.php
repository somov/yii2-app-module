<?php


namespace somov\appmodule;


use somov\appmodule\interfaces\AppModuleInterface;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Module;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

/**
 * Конфигурация модулей приложения
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

    use ConfigParams;

    /**
     *
     */
    const LOC_FILE = 'enabled.loc';

    /**
     * @var string
     */
    private $_path;

    /**
     * @var  String
     */
    private $_namespace;

    /**
     * @var  String
     */
    private $_class;

    /**
     * @var  boolean
     */
    private $_enabled;


    public function __construct(array $config = [])
    {
        $runtime = ArrayHelper::remove($config, 'runtime');

        parent::__construct($config);

        foreach ($runtime as $key => $value) {
            $this->{'_' . $key} = $value;
        }
    }

    public function serialize()
    {
        $array = [];
        foreach (['path', 'namespace', 'class', 'enabled'] as $property) {
            $array['_' . $property] = $this->$property;
        }
        $array['params'] = $this->getParams();

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

        return $this->_path;
    }

    /**
     * @return String
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * @return String
     */
    public function getClass()
    {
        return $this->_class;
    }

    /**
     * @return bool
     */
    public function getEnabled()
    {
        return $this->_enabled;
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

    /**
     * @return array
     */
    public function getImplements()
    {
        return class_implements($this->class);
    }

    /**
     * @return bool|string
     */
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

    /**
     * @return bool
     */
    public function isEnabled()
    {
        $this->_enabled = file_exists($this->getLocFile());
        return $this->_enabled;
    }

    /**
     * @return $this
     */
    protected function turnOn()
    {
        if ($this->isEnabled()) {
            return $this;
        }
        if (file_put_contents($this->getLocFile(), '') !== false) {
            $this->_enabled = true;
        };

        return $this;
    }

    /**
     * @return $this
     */
    protected function turnOff()
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
    protected function toggle()
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

    /** Возвращает уже созданный экземпляр модуля
     * @return Module|AppModuleInterface|null
     */
    public function getModuleInstance()
    {
        if (class_exists($this->class, false)) {
            return call_user_func([$this->class, 'getInstance']);
        }
        return null;
    }

}