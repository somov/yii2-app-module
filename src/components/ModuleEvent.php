<?php
/**
 *
 * User: develop
 * Date: 25.09.2018
 */

namespace somov\appmodule\components;


use yii\base\Event;
use yii\base\Module;

class ModuleEvent extends Event
{

    /** @var  Manager */
    public $sender;

    /**
     * @var Module
     */
    public $module;

    /**
     * @var bool
     */
    public $isValid = true;



}