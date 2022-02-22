<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 20.07.20
 * Time: 16:53
 */

namespace somov\appmodule\exceptions;

/**
 * Class InstallationDirectoryExistsException
 * @package somov\appmodule\exceptions
 */
class InstallationDirectoryExistsException extends ManagerExceptionBase
{
    /**
     * @var string
     */
    public $path;
}