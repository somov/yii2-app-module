<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 13.02.19
 * Time: 13:42
 */

namespace somov\appmodule\components;


class ModuleUpgradeEvent extends ModuleEvent
{
    /**
     * @var string
     */
    public $newVersion;

    /**
     * @var string
     */
    public $oldVersion;
}