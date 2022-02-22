<?php
/**
 *
 * User: develop
 * Date: 30.08.2018
 */

namespace mtest\components;


use Codeception\TestCase\Test;
use somov\appmodule\components\Manager;
use somov\appmodule\exceptions\ModuleNotFoundException;
use testModule\components\TestInterface;
use yii\base\Application;

class ManagerTest extends Test
{

    use ManagerTrait;


    const test_module_id = 'test-module';

    public function testAppNmInstall()
    {
        $this->clear();
        $zip = $this->createZipTestModule('namespaceapp');
        $r=  $this->manager->unzipAndProcess($zip, $config);
        $this->assertTrue($r);;
    }

    /**
     * @expectedException  \yii\base\ExitException
     */
    public function testInstall()
    {
        $this->clear();

        $zip = $this->createZipTestModule(self::test_module_id);

        $r = $this->manager->unzipAndProcess($zip, $config);
        $this->assertTrue($r);

        $module = \testModule\Module::getInstance();

        /** @var \ExtendConfigInterface $config */
        $config = $this->manager->getModuleConfigById($module->id);
        $config->type = 'eee';
        $test = $config['type'];
        $this->assertSame('eee', $test);
        $description = $config->description;

        $app = \Yii::$app;
        $app->state = Application::STATE_HANDLING_REQUEST;
        $this->manager->bootstrap($app);

        \Yii::$app->end(Application::STATE_BEFORE_REQUEST);

        $this->assertEquals(\Yii::$app->params['test'], 'test');

    }

    public function testReset()
    {
        $this->manager->reset(self::test_module_id, $config);
        $this->assertConfig($config);
    }

    public function testGetListClasses()
    {
        $result = $this->manager->clearCache()->getFilteredClassesList();
        $this->assertNotEmpty($result);
        $this->assertConfig(reset($result));
    }


    public function testEnableDisable()
    {
        $id = self::test_module_id;
        $config = $this->manager->clearCache()->getModuleConfigById($id);
        $state = $config->enabled;

        $this->manager->toggle($id, $config);
        $this->assertNotSame($state, $config->enabled);

        $state = $config->enabled;
        $this->manager->toggle($id, $config);
        $this->assertNotSame($state, $config->enabled);

    }

    public function testGetFilterClasses()
    {

        $config = $this->manager->getFilteredClassesList([
            'implements' => [TestInterface::class]
        ]);

        $config = reset($config);
        $this->assertNotEmpty($config);
        $this->assertConfig($config);

    }


    public function testSubModuleInstall()
    {
        $this->clear();

        $zip = $this->createZipTestModule(self::test_module_id);
        $this->manager->unzipAndProcess($zip, $config);

        $zip = $this->createZipTestModule('submodule');
        $this->manager->unzipAndProcess($zip, $config);

        $module = $this->manager->loadModule('test-module/submodule');

        $this->assertInstanceOf(\subModule\Module::class, $module);

    }

    public function testUpgrade()
    {
        $v = null;

        $zip = $this->createZipTestModule('update' );

        $this->manager->on(Manager::EVENT_AFTER_UPGRADE, function ($event) use (&$v) {
            $v = $event->newVersion;
        });

        $r = $this->manager->unzipAndProcess($zip, $config);
        $this->assertSame('9.9.9', $v);
        $this->assertTrue($r);
    }


    public function testExportImport()
    {
        $zip = $this->manager->export(self::test_module_id, '@ext/_output', true,  $config);
        $this->manager->uninstall(self::test_module_id, $config);
        $r = $this->manager->unzipAndProcess($zip, $config);
        $this->assertTrue($r);
    }


    public function testUninstall()
    {

        $this->assertTrue($this->manager->uninstall(self::test_module_id, $config));
        $this->expectException(ModuleNotFoundException::class);
        $this->manager->loadModule(self::test_module_id, $c);
    }


}