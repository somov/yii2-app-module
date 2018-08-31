<?php
/**
 *
 * User: develop
 * Date: 30.08.2018
 */

namespace mtest\components;


use Codeception\TestCase\Test;

use somov\appmodule\components\Manager;
use somov\appmodule\interfaces\AppModuleInterface;
use yii\base\Application;
use yii\base\Module;
use yii\helpers\FileHelper;
use ZipArchive;

class ManagerTest extends Test
{

    /** @var  Manager */
    private $manager;

    const test_module_id = 'test-module';


    protected function assertModule($error)
    {
        $this->assertNull($error);
        /** @var AppModuleInterface|Module $instance */
        $instance = $this->manager->loadModule(self::test_module_id, $config);
        $this->assertInstanceOf(AppModuleInterface::class, $instance);
        $this->assertConfig($config);

    }

    protected function assertConfig($config)
    {
        foreach (['id', 'path', 'enabled', 'class', 'events', 'enabled'] as $key) {
            $this->assertArrayHasKey($key, $config);
        }
    }

    /**
     * @expectedException \yii\base\ExitException
     */
    public function testInstall()
    {
        $path = \Yii::getAlias('@app/modules/' . self::test_module_id);

        if (file_exists($path)) {
            FileHelper::removeDirectory($path);
        }

        $zip = $this->createZipTestModule();
        $this->manager->install($zip, $error);
        $this->assertModule($error);

        $module = \app\modules\test\Module::getInstance();
        $config = $this->manager->getModuleConfigById($module->id);

        $app = \Yii::$app;
        $app->state = Application::STATE_HANDLING_REQUEST;
        $this->manager->bootstrap($app);

        \Yii::$app->end(Application::STATE_BEFORE_REQUEST);

        $this->assertEquals(\Yii::$app->response->data, $config->id);

    }

    public function testReset()
    {
        $this->manager->reset(self::test_module_id, $config, $error);
        $this->assertConfig($config);
        $this->assertEmpty($error);
    }


    public function testGetListClasses()
    {
        $result = $this->manager->getFilteredClassesList();
        $this->assertNotEmpty($result);
        $this->assertConfig(reset($result));
    }


    public function testEnableDisable()
    {
        $id = self::test_module_id;

        $config = $this->manager->clearCache()->getModuleConfigById($id);

        $this->manager->toggle($id);

        $this->assertNotSame($this->manager->isEnabled($id), $config['enabled']);

        $this->manager->toggle($id);

        $this->assertSame($this->manager->isEnabled($id), $config['enabled']);
    }


    public function testGetCategoriesArray()
    {
        $this->assertCount(1, $this->manager->getCategoriesArray());
        $this->assertCount(1, $this->manager->getCategoriesArray(['enabled' => true], true));
        $this->assertCount(0, $this->manager->getCategoriesArray(['enabled' => false]));
    }

    public function testUninstall()
    {
        $this->manager->uninstall(self::test_module_id, $e);
        $this->assertEmpty($e);
        $this->expectException('yii\base\Exception');
        $this->manager->loadModule(self::test_module_id, $config);
    }


    protected function setUp()
    {
        $this->manager = \Yii::createObject(
            Manager::class
        );
        $this->manager->clearCache();

        parent::setUp();
    }

    /**
     * @return string zip file name
     */
    private function createZipTestModule()
    {
        $path = '@ext/files/' . self::test_module_id;

        $zip = new ZipArchive();
        $zip->open(\Yii::getAlias($path . '/../test-module.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (FileHelper::findFiles(\Yii::getAlias($path), [
            'only' => ['pattern' => '*.*']
        ]) as $file) {
            $parts = explode('/' . self::test_module_id . '/', $file);
            $zip->addFile($file, '/' . self::test_module_id . '/' . $parts[1]);
        }
        $file = $zip->filename;
        $zip->close();

        return $file;
    }
}