<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.06.20
 * Time: 23:24
 */

namespace somov\appmodule\components;



/**
 * Class ConfigLocFile
 * @package somov\appmodule\components
 */
class ConfigLocFile extends ConfigBase
{

    const LOC_FILE = 'enabled.loc';

    /**
     * @var boolean
     */
    public $_enabled;

    /**
     * @var array|null
     */
    public $extendProperties;


    /**
     * @return array
     */
    protected function properties()
    {
        if (isset($this->extendProperties) && is_array($this->extendProperties)) {
            return parent::properties() + $this->extendProperties;
        }
        return parent::properties();
    }


    /**
     * @return bool
     */
    public function isEnabled()
    {
        if (!isset($this->_enabled)) {
            $this->_enabled = file_exists($this->getLocFile());
        }

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
     * @param string  'turnOn'|'turnOff'|toggle $state
     * @return void
     */
    function changeState($state)
    {
        if ($this->hasMethod($state)) {
            call_user_func([$this, $state]);
        }
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
        $this->_enabled = false;

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

}