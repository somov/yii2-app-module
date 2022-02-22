<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 28.07.19
 * Time: 16:28
 */

namespace somov\appmodule\exceptions;


use somov\appmodule\interfaces\ConfigInterface;

class SubModuleException extends ManagerExceptionBase
{
    /**
     * @var ConfigInterface
     */
    public $submoduleConfig;
}