<?php
/**
 * Created by PhpStorm.
 * User: develop
 * Date: 29.09.2018
 * Time: 14:35
 */

namespace somov\appmodule\exceptions;


use somov\appmodule\components\Manager;
use somov\appmodule\Config;
use yii\base\Exception;

class ManagerExceptionBase extends Exception
{
    /**
     * @var Manager
     */
    public $manager;

    /**
     * @var Config
     */
    public $config;

    public function __construct($message = "",  \Throwable $previous = null, Manager $manager, $config = null)
    {
        $this->manager = $manager;

        $this->config = $config;

        parent::__construct($message, 0, $previous);
    }


    public function getName()
    {
        $name = (isset($this->config)) ? $this->config->name : '';
        return 'Module Exception ' . $name;
    }
}