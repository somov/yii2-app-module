<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 28.07.19
 * Time: 16:28
 */

namespace somov\appmodule\exceptions;


use somov\appmodule\Config;

class SubModuleException extends ManagerExceptionBase
{
    /**
     * @var Config
     */
    public $submoduleConfig;
}