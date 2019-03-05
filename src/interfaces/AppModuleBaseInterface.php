<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 27.02.19
 * Time: 13:54
 */

namespace somov\appmodule\interfaces;

/**
 * Interface AppModuleBaseInterface
 * @package somov\appmodule\interfaces
 *
 * @method bool upgrade()
 * @method bool install(bool $isReset = false)
 * @method bool uninstall(bool $isReset = false)
 * @method bool changedState(bool $isEnabled)
 */
interface AppModuleBaseInterface
{

}