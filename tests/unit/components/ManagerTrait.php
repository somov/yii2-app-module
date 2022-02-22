<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 27.07.19
 * Time: 13:53
 */

namespace mtest\components;


use somov\appmodule\components\ConfigLocFile;
use somov\appmodule\components\Manager;
use yii\caching\ExpressionDependency;
use yii\caching\FileCache;
use yii\helpers\FileHelper;
use ZipArchive;

trait ManagerTrait
{
    /** @var  Manager */
    private $manager;


    protected function assertConfig($config)
    {
        foreach (['id', 'class',  'name_space'] as $key) {
            $this->assertArrayHasKey($key, $config);
        }
    }

    private function clear()
    {

        $path = \Yii::getAlias('@app/modules/');

        if (file_exists($path)) {
            FileHelper::removeDirectory($path);
        }

        if (!file_exists($path)) {
            FileHelper::createDirectory($path);
        }

        $this->manager->clearCache();
    }


    protected function setUp()
    {
        $this->manager = \Yii::createObject([
                'class' => Manager::class,
                'isAutoActivate' => true,
                'cacheConfig' => [
                    'class' => FileCache::class,
                ],
                'configOptions' => [
                    'class' => ConfigLocFile::class,
                    'extendProperties' => [
                        'name' => null,
                        'description' => null,
                        'category' => null,
                    ]
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

        $this->manager->bootstrap(\Yii::$app);

        parent::setUp();
    }

    /**
     * @param string $id
     * @return string zip file name
     */
    private function createZipTestModule($id)
    {
        $path = \Yii::getAlias("@ext/files/$id");

        $zip = new ZipArchive();

        $zip->open(dirname($path) . '/' . $id . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (FileHelper::findFiles($path, [
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