<?php

namespace mtest\common;

/**
 * Class TestComponent
 * @package mtest\common
 */
class TestComponent extends \yii\base\Component
{

    const  EVENT_INIT = 'init';

    /**
     * @var
     */
    public $testProperty;


    public function init()
    {
        parent::init();
        $this->trigger(self::EVENT_INIT);
    }


}