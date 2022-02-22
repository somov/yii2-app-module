<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.06.20
 * Time: 21:32
 */

namespace somov\appmodule\components;


use somov\appmodule\interfaces\AppModuleEventHandler;
use somov\appmodule\interfaces\ConfigInterface;
use somov\common\traits\DynamicProperties;
use Yii;
use yii\base\BaseObject;
use yii\base\NotSupportedException;
use yii\base\StaticInstanceInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 * Class ConfigBase
 * @package somov\appmodule\components
 */
abstract class ConfigBase extends BaseObject implements ConfigInterface
{

    const DEFAULT_TYPE = 'module';

    use DynamicProperties;


    /**
     * @var array
     */
    private $_hI;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string|null
     */
    private $_path;


    /**
     * @return array
     */
    public function getMetaData()
    {
        $data = $this->data;
        $skipPrivateProperties = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,
                2)[1]['function'] !== 'serialize';;

        foreach ($data as $key => $item) {

            if ($skipPrivateProperties && StringHelper::startsWith($key, '_')) {
                unset($data[$key]);
            }

            if (empty($item) && $item != '0') {
                unset($data[$key]);
            }
        }
        return $data;
    }


    /**
     * @return bool
     * alias
     */
    public function getEnabled()
    {
        return $this->isEnabled();
    }

    /**
     * @return bool|string|null
     */
    public function getPath()
    {
        if ($this->_path) {
            return $this->_path;
        }
        return $this->_path = $this->getInstalledPath();
    }


    /**
     * @param string $value
     */
    public function setPath($value)
    {
        $this->_path = $value;
    }

    /**
     * @return string
     */
    public function getUniqueId()
    {
        return $this->parentModule ? ltrim($this->parentModule . '/' . $this->id, '/') : $this->id;
    }

    /**
     * @return mixed
     */
    public function getModules()
    {
        return ArrayHelper::getValue($this->data, '_modules', []);
    }

    /**
     * @param ConfigInterface[] $modules
     */
    public function setModules($modules)
    {
        ArrayHelper::setValue($this->data, '_modules', ArrayHelper::index($modules, 'id'));
    }

    /**
     * @param string $class
     * @return AppModuleEventHandler|object
     * @throws \yii\base\InvalidConfigException
     */
    public function eventHandlerInstance($class)
    {
        if (is_object($class)) {
            $key = crc32(get_class($class));
            $this->_hI[$key] = $class;
        } else {
            $key = crc32($class);
        }

        if (isset($this->_hI[$key])) {
            return $this->_hI[$key];
        }

        if (is_subclass_of($class, StaticInstanceInterface::class)) {
            /**@var StaticInstanceInterface|string $class */
            return $this->_hI[$key] = $class::instance();
        }

        return $this->_hI[$key] = Yii::createObject($class);
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
            $aliases[] = self::DEFAULT_TYPE . 's';
        }

        $aliases[] = $this->id;

        return \Yii::getAlias(implode(DIRECTORY_SEPARATOR, $aliases));
    }

    /**
     * @return array
     */
    public function getImplements()
    {
        return class_implements($this->class);
    }

    /**
     * @return array
     */
    protected function properties()
    {
        return [
            'id' => null,
            'alias' => null,
            'type' => self::DEFAULT_TYPE,
            'nameSpace' => null,
            'class' => null,
            'events_' => null, //private but serialized
            'urlRules' => [],
            'appendRoutes' => false,
            'bootstrap' => false,
            'parentModule' => null,
            '_modules' => [],
            'version' => null,
            'handler' => null, // array of handlers
            'context' => null // class name or array of class names or boolean
        ];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->getMetaData());
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->setMetData(unserialize($serialized));
    }

    /**
     * @param array $data
     */
    protected function setMetData(array $data)
    {
       $this->data = $data;
    }

    /**
     * @param string $offset
     */
    private function normalizeOffset(&$offset)
    {
        if (strpos($offset, '_') > 0) {
            $offset = lcfirst(Inflector::id2camel(str_replace('_', '-', $offset)));
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        $this->normalizeOffset($offset);

        return isset($this->data[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        $this->normalizeOffset($offset);

        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new NotSupportedException();
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new NotSupportedException();
    }

}