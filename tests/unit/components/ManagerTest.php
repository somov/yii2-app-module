<?php
/**
 *
 * User: develop
 * Date: 30.08.2018
 */

namespace mtest\components;


use Codeception\TestCase\Test;

use PHPUnit\Framework\Error\Error;
use somov\appmodule\components\Manager;
use testModule\components\TestInterface;
use yii\base\Application;
use yii\caching\ExpressionDependency;
use yii\caching\FileCache;
use yii\helpers\FileHelper;
use ZipArchive;

class ManagerTest extends Test
{

    /** @var  Manager */
    private $manager;

    const test_module_id = 'test-module';


    protected function assertConfig($config)
    {
        foreach (['id', 'path', 'enabled', 'class', 'events', 'enabled'] as $key) {
            $this->assertArrayHasKey($key, $config);
        }
    }

    private function cleare()
    {

        $path = \Yii::getAlias('@app/modules/');

        if (file_exists($path)) {
            FileHelper::removeDirectory($path);
        }

        if (!file_exists($path)) {
            FileHelper::createDirectory($path);
        }
    }

    public function testInstallAppNm()
    {
        $this->cleare();
        $zip = $this->createZipTestModule('', 'namespaceapp');
        $r = $this->manager->install($zip, $error);
        $this->assertTrue($r, isset($error) ? $error : '');

    }

    /**
     * @expectedException \yii\base\ExitException
     */
    public function testInstall()
    {
        $this->cleare();

        $zip = $this->createZipTestModule();
        $r = $this->manager->install($zip, $error);
        $this->assertEmpty($error);
        $this->assertTrue($r);

        $module = \testModule\Module::getInstance();
        $config = $this->manager->getModuleConfigById($module->id);

        $config->type = 'eee';
        $test = $config['type'];
        $this->assertSame('eee', $test);
        $description = $config->description;


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
        $result = $this->manager->clearCache()->getFilteredClassesList();
        $this->assertNotEmpty($result);
        $this->assertConfig(reset($result));
    }


    public function testEnableDisable()
    {
        $id = self::test_module_id;
        $config = $this->manager->clearCache()->getModuleConfigById($id);
        $state = $config->enabled;

        $config->toggle();
        $this->assertNotSame($state, $config->enabled);

        $state = $config->enabled;
        $config->toggle();
        $this->assertNotSame($state, $config->enabled);

    }

    public function testGetFilterClasses()
    {

        $config = $this->manager->getFilteredClassesList([
            'category' => 'Test',
            'implements' => [TestInterface::class]
        ]);

        $config = reset($config);
        $this->assertNotEmpty($config);
        $this->assertConfig($config);

    }

    public function testGetCategoriesArray()
    {
        $this->assertCount(1, $this->manager->getCategoriesArray());
        $this->assertCount(1, $this->manager->getCategoriesArray(['enabled' => true], true));
        $this->assertCount(0, $this->manager->getCategoriesArray(['enabled' => false]));

    }

    public function testSubModule()
    {
        $zip = $this->createZipTestModule('', 'submodule');
        $this->manager->install($zip, $error);
        $list = $this->manager->getModulesClassesList();
        $this->assertCount(2, $list);
    }

    public function testUpdate()
    {
        $zip = $this->createZipTestModule('update' . DIRECTORY_SEPARATOR);
        $r = $this->manager->install($zip, $error);
        $this->assertTrue($r, isset($error) ? $error : '');
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
        $this->manager = \Yii::createObject([
                'class' => Manager::class,
                'cacheConfig' => [
                    'class' => FileCache::class,
                ],
                'cacheDependencyConfig' => [
                    'class' => ExpressionDependency::class,
                    'params' => ['lang'=>\Yii::$app->language],
                    'expression' =>  '$this->params["lang"] === \Yii::$app->language'
                ]
            ]
        );
        $this->manager->clearCache();

        parent::setUp();
    }

    /**
     * @param string $path
     * @param string $id
     * @return string zip file name
     */
    private function createZipTestModule($path = '', $id = self::test_module_id)
    {
        $path = '@ext/files/' . $path . $id;

        $zip = new ZipArchive();
        $zip->open(\Yii::getAlias($path . '/../' . $id . '.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (FileHelper::findFiles(\Yii::getAlias($path), [
            'only' => ['pattern' => '*.*']
        ]) as $file) {
            $parts = explode('/' . $id . '/', $file);
            $zip->addFile($file, '/' . $parts[1]);
        }
        $file = $zip->filename;
        $zip->close();

        return $file;
    }
}