<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 28.07.20
 * Time: 13:20
 */

namespace somov\appmodule\interfaces;


use yii\base\Application;

/**
 * Interface ContextValidatorInterface
 * @package somov\appmodule\interfaces
 */
interface ContextValidatorInterface
{
    /**
     *
     * @param Application $app
     * @param ConfigInterface $config
     * @return boolean
     */
    public static function validateContext($app, ConfigInterface $config);
}