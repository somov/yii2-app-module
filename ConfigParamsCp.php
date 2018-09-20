<?php

namespace somov\appmodule;

use yii\base\Model;

/**
 * Trait ConfigParams
 *
 * @package somov\appmodule
 * @property string id
 * @property string alias
 * @property string type
 * @property string name
 * @property string eventMethod
 * @property string|Model settingsModel
 * @property string settingsRoute
 * @property string settingsView
 * @property string settingsRouteIcon = 'none'
 * @property string description
 * @property string version
 * @property string category = 'Not set'
 * @property string author
 * @property array events = []
 * @property array urlRules = []
 * @property boolean appendRoutes = false
 * @property boolean bootstrap = false
 * @property string parentModule = null
 * @property Config[] modules = []
 */
trait ConfigParams
{
    private $_cp = [];

    /**
     * @param string $attribute
     * @param mixed $default
     * @return mixed
     */
    private function getter($attribute, $default = '')
    {
        return (isset($this->_cp[$attribute])) ? $this->_cp[$attribute] : $default;
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
    public function getName()
    {
        return $this->getter('name');
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getter('id', null);
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->_cp['id'] = $id;
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
        $this->_cp['alias'] = $alias;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_cp['name'] = $name;
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
        $this->_cp['type'] = $type;
    }

    /**
     * @return string
     */
    public function getEventMethod()
    {
        return $this->getter('eventMethod', Config::METHOD_TYPE_EVENT_TO_EVENT);
    }

    /**
     * @param string $eventMethod
     */
    public function setEventMethod($eventMethod)
    {
        $this->_cp['eventMethod'] = $eventMethod;
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
        $this->_cp['settingsModel'] = $settingsModel;
    }

    /**
     * @return string
     */
    public function getSettingsRoute()
    {
        return $this->getter('settingsRoute');
    }

    /**
     * @param string $settingsRoute
     */
    public function setSettingsRoute($settingsRoute)
    {
        $this->_cp['settingsRoute'] = $settingsRoute;
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
        $this->_cp['settingsView'] = $settingsView;
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
        $this->_cp['settingsRouteIcon'] = $settingsRouteIcon;
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
        $this->_cp['description'] = $description;
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
        $this->_cp['version'] = $version;
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
        $this->_cp['category'] = $category;
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
        $this->_cp['author'] = $author;
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
        $this->_cp['events'] = $events;
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
        $this->_cp['urlRules'] = $urlRules;
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
        $this->_cp['appendRoutes '] = $appendRoutes;
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
        $this->_cp['bootstrap'] = $bootstrap;
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
        $this->_cp['parentModule'] = $parentModule;
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
        $this->_cp['modules'] = $modules;
    }

    /**
     * @param array $modules
     */
    public function addModules($modules)
    {
        if (count($this->getModules()) > 0) {
            $this->_cp['modules'] = array_merge($this->_cp['modules'], $modules);
            return;
        }
        $this->setModules($modules);
    }


}