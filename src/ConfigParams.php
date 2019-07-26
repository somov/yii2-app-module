<?php

namespace somov\appmodule;

use yii\base\Exception;
use yii\base\Model;

/**
 * Trait ConfigParams
 *
 * @package somov\appmodule
 * @property string id
 * @property string uniqueId
 * @property string alias
 * @property string type
 * @property string name
 * @property string|Model settingsModel
 * @property string settingsRoute
 * @property string settingsView
 * @property string settingsRouteIcon = 'none'
 * @property string description
 * @property string version
 * @property string category = 'Not set'
 * @property string author
 * @property boolean xhrActive = true
 * @property array events = []
 * @property array urlRules = []
 * @property boolean appendRoutes = false
 * @property boolean bootstrap = false
 * @property string parentModule = null
 * @property Config[] modules = []
 * @property boolean enabledOnConsole = false
 */
trait ConfigParams
{
    private $_cp = [];

    /**
     * @param $attribute
     * @return int
     * @throws Exception
     */
    private function getAid($attribute)
    {

        if (!$id = array_search($attribute, $this->attributesNames())) {
            throw new Exception('Invalid config param' . $attribute);
        }
        return (integer)$id;
    }

    /**
     * @param string $attribute
     * @param mixed $default
     * @return mixed
     */
    private function getter($attribute, $default = '')
    {
        $id = $this->getAid($attribute);
        return (isset($this->_cp[$id])) ? $this->_cp[$id] : $default;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     */
    private function setter($attribute, $value)
    {
        $this->_cp[$this->getAid($attribute)] = $value;
    }

    public function getParams()
    {
        return $this->_cp;
    }

    protected function setParams($params)
    {
        $this->_cp = $params;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getter('id', null);
    }

    public function getUniqueId()
    {
        return $this->parentModule ? ltrim($this->parentModule . '/' . $this->id, '/') : $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->setter('id', $id);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->setter('name', $name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getter('name');
    }


    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->getter('alias', '@app/modules');
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->setter('alias', $alias);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getter('type', 'module');
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->setter('type', $type);
    }

    /**
     * @return string|Model
     */
    public function getSettingsModel()
    {
        return $this->getter('settingsModel', null);
    }

    /**
     * @param string|Model $settingsModel
     */
    public function setSettingsModel($settingsModel)
    {
        $this->setter('settingsModel', $settingsModel);
    }

    /**
     * @return string
     */
    public function getSettingsRoute()
    {
        return $this->getter('settingsRoute', null);
    }

    /**
     * @param string $settingsRoute
     */
    public function setSettingsRoute($settingsRoute)
    {
        $this->setter('settingsRoute', $settingsRoute);
    }

    /**
     * @return string
     */
    public function getSettingsView()
    {
        return $this->getter('settingsView');
    }

    /**
     * @param string $settingsView
     */
    public function setSettingsView($settingsView)
    {
        $this->setter('settingsView', $settingsView);
    }

    /**
     * @return string
     */
    public function getSettingsRouteIcon()
    {
        return $this->getter('settingsRouteIcon');
    }

    /**
     * @param string $settingsRouteIcon
     */
    public function setSettingsRouteIcon($settingsRouteIcon)
    {
        $this->setter('settingsRouteIcon', $settingsRouteIcon);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getter('description');
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->setter('description', $description);
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->getter('version');
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->setter('version', $version);
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->getter('category', 'Not Set');
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->setter('category', $category);
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->getter('author');
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->setter('author', $author);
    }

    /**
     * @return boolean
     */
    public function getXhrActive()
    {
        return $this->getter('xhr', true);
    }

    /**
     * @param boolean $value
     */
    public function setXhrActive($value)
    {
        $this->setter('xhr', $value);
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->getter('events', []);
    }

    /**
     * @param array $events
     */
    public function setEvents($events)
    {
        $this->setter('events', $events);
    }

    /**
     * @return array
     */
    public function getUrlRules()
    {
        return $this->getter('urlRules', []);
    }

    /**
     * @param array $urlRules
     */
    public function setUrlRules($urlRules)
    {
        $this->setter('urlRules', $urlRules);
    }

    /**
     * @return bool
     */
    public function isAppendRoutes()
    {
        return $this->getAppendRoutes();
    }

    /**
     * @return bool
     */
    public function getAppendRoutes()
    {
        return $this->getter('appendRoutes', false);
    }

    /**
     * @param bool $appendRoutes
     */
    public function setAppendRoutes($appendRoutes)
    {
        $this->setter('appendRoutes', $appendRoutes);
    }


    public function getBootstrap()
    {
        return $this->getter('bootstrap', false);
    }

    /**
     * @return bool
     */
    public function isBootstrap()
    {
        return $this->getBootstrap();
    }

    /**
     * @param bool $bootstrap
     */
    public function setBootstrap($bootstrap)
    {
        $this->setter('bootstrap', $bootstrap);
    }

    /**
     * @return string
     */
    public function getParentModule()
    {
        return $this->getter('parentModule', null);
    }

    /**
     * @param string $parentModule
     */
    public function setParentModule($parentModule)
    {
        $this->setter('parentModule', $parentModule);
    }

    /**
     * @return boolean
     */
    public function getEnabledOnConsole()
    {
        return $this->getter('console', false);
    }

    /**
     * @param boolean $value
     */
    public function setEnabledOnConsole($value)
    {
        $this->setter('console', $value);
    }

    /**
     * @return Config[]
     */
    public function getModules()
    {
        return $this->getter('modules', []);
    }

    /**
     * @param Config[] $modules
     */
    public function setModules($modules)
    {
        $this->setter('modules', $modules);
    }

    /**
     * @param array $modules
     */
    public function addModules($modules)
    {
        if (count($this->getModules()) > 0) {
            $id = $this->getAid('modules');
            $this->_cp[$id] = array_merge($this->_cp[$id], $modules);
            return;
        }
        $this->setModules($modules);
    }


}