<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.06.20
 * Time: 12:37
 */

namespace somov\appmodule\interfaces;



/**
 * Interface ManagerConfigResolver
 * @package somov\appmodule\components
 */
interface ManagerConfigResolver 
{
    /**
     * @param bool $withSubModules
     * @return ConfigInterface[]
     */
    public function resolve($withSubModules = true);
    
}