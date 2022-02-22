<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.06.20
 * Time: 21:41
 */

namespace somov\appmodule\interfaces;


use yii\base\Module;

/**
 * Interface ConfigInterface
 * @package somov\appmodule\interfaces
 *
 * @property string|Module|AppModuleInterface class
 * @property string nameSpace
 * @property string path
 * @property array implements
 * @property-read boolean $enabled
 * @property string id
 * @property string uniqueId
 * @property string alias
 * @property string type
 * @property string version
 * @property array urlRules = []
 * @property boolean appendRoutes = false
 * @property boolean bootstrap = false
 * @property AppModuleEventHandler|AppModuleEventHandler[] $handler
 * @property string parentModule = null
 * @property ConfigInterface[] modules = []
 * @property string|ContextValidatorInterface|null context
 *
 */
interface ConfigInterface extends \Serializable, \ArrayAccess
{

    /**
     * @param  AppModuleEventHandler|string $class
     * @return AppModuleEventHandler|object
     */
    function eventHandlerInstance($class);


    /**
     * @return boolean
     */
    function isEnabled();

    /**
     * @return array
     */
    public function getMetaData();


    const STATE_ON = 'turnOn';
    const STATE_OFF = 'turnOff';
    const STATE_TOGGLE = 'toggle';

    /**
     * @param string  'turnOn'|'turnOff'|toggle $state
     * @return void
     */
    function changeState($state);

    /**
     * @return string
     */
    function getInstalledPath();

}