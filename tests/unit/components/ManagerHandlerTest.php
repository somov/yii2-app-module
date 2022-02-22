<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 21:49
 */

namespace mtest\components;


use Codeception\TestCase\Test;
use Yii;
use yii\base\Application;
use yii\helpers\ArrayHelper;


class ManagerHandlerTest extends Test
{

    use ManagerTrait;


    public function providerID()
    {
        return [
           'instance' => ['instance-handler'],
            'static' => ['static-handler'],
            'object' => ['object-handler'],
            'module' => ['module-handler']
        ];
    }

    /**
     * @dataProvider providerID
     * @param $id
     * @throws \somov\appmodule\exceptions\ManagerExceptionBase
     */
    public function testHandlers($id)
    {
        $this->clear();


        $zip = $this->createZipTestModule($id);
        $this->manager->unzipAndProcess($zip);
        //$this->manager->clearCache();


        $app = Yii::$app;
        $this->manager->bootstrap($app);
        \Yii::$app->state = Application::STATE_HANDLING_REQUEST;
        $app->trigger($app::EVENT_BEFORE_ACTION);

        $module = $this->manager->loadModule($id);


        $value = ArrayHelper::getValue($module, ['testComponent', 'testProperty']);


        $this->assertNotEmpty($value);
    }


}