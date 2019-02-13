<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 13.02.19
 * Time: 13:42
 */

namespace somov\appmodule\components;


use yii\helpers\StringHelper;

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


    public $fileName;

    /**
     * @return string
     */
    public function getBaseFileName()
    {
        return StringHelper::basename($this->fileName);
    }

}