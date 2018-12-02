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

    public function testAppNmInstall()
    {
        $this->cleare();
        $zip = $this->createZipTestModule('', 'namespaceapp');

        $this->assertTrue($this->manager->install($zip, $config));
    }

    /**
     * @expectedException \yii\base\ExitException
     */
    public function testInstall()
    {
        $this->cleare();

        $zip = $this->createZipTestModule();
        $r = $this->manager->install($zip, $config);
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
            'category' => 'Test',
            'implements' => [TestInterface::class]
        ]);

        $config = reset($config);
        $this->assertNotEmpty($config);
        $this->assertConfig($config);

    }


    public function testSubModuleInstall()
    {
        $this->cleare();

        $zip = $this->createZipTestModule('', 'test-module');
        $this->manager->install($zip, $config);

        $zip = $this->createZipTestModule('', 'submodule');
        $this->manager->install($zip, $config);

        $module = $this->manager->loadModule('test-module/submodule');

        $this->assertInstanceOf(\subModule\Module::class, $module);

    }

    public function testUpdate()
    {
        $zip = $this->createZipTestModule('update' . DIRECTORY_SEPARATOR);
        $r = $this->manager->install($zip, $config);
        $this->assertTrue($r);
    }

    public function testUninstall()
    {

        $this->assertTrue($this->manager->uninstall(self::test_module_id, $config));
        $this->expectException(ModuleNotFoundException::class);
        $this->manager->loadModule(self::test_module_id, $c);
    }


    protected function setUp()
    {
        $this->manager = \Yii::createObject([
                'class' => Manager::class,
                'isAutoActivate' => true,
                'cacheConfig' => [
                    'class' => FileCache::class,
                ],

                'cacheVariations' => [
                    'uk',
                    'en'
                ],
                'cacheCurrentVariation' => 'en',
                'cacheDependencyConfig' => [
                    'class' => ExpressionDependency::class,
                    'params' => ['lang' => \Yii::$app->language],
                    'expression' => '$this->params["lang"] === \Yii::$app->language'
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