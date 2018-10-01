<?php
/**
 * Created by PhpStorm.
 * User: develop
 * Date: 29.09.2018
 * Time: 15:38
 */

namespace somov\appmodule\exceptions;


class InvalidModuleConfiguration extends ManagerExceptionBase
{
    public function getName()
    {
        return parent::getName() . 'Invalid module configuration ';
    }
}