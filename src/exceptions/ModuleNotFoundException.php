<?php
/**
 * Created by PhpStorm.
 * User: develop
 * Date: 29.09.2018
 * Time: 15:34
 */

namespace somov\appmodule\exceptions;


class ModuleNotFoundException extends ManagerExceptionBase
{
    public function getName()
    {
        return parent::getName() . ' ' . 'module not found';
    }
}