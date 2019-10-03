<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 29.09.19
 * Time: 21:49
 */

namespace mtest\components;


use Codeception\TestCase\Test;
use staticHandler\ActiveTestQuery;

class ManagerStaticHandlerTest extends Test
{

    use ManagerTrait;

    const  ID = 'static-handler';

    public function testHandle()
    {


        if ($c = $this->manager->getModuleConfigById(self::ID)) {
            $this->manager->uninstall(self::ID, $c);
        }

        $zip = $this->createZipTestModule('static-handler');

        $this->manager->install($zip);

        $this->manager->clearCache();
        $this->manager->bootstrap(\Yii::$app);

        $q = new ActiveTestQuery('');
        $q->init();


        $this->assertSame('test-static', $q->test);


    }


}