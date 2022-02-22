<?php
/**
 *
 * User: develop
 * Date: 30.08.2018
 */

namespace mtest\components;


use Codeception\TestCase\Test;
use somov\appmodule\components\Manager;

class ManagerAdvancedTest extends Test
{

    use ManagerTrait;

    /** @var  Manager */
    private $manager;


    public function testUnzip()
    {
        $zip = $this->createZipTestModule('group-install');
        $this->manager->unzip($zip, $config);
        $this->assertConfig($config);
        $this->assertNotEmpty($config->modules);

    }


    public function testInstall()
    {
        $this->clear();

        $zip = $this->createZipTestModule('group-install');

        $this->expectExceptionMessage('Install submodule');

        $this->manager->unzip($zip, $config);
        $this->manager->install($config);

        $this->assertConfig($config);
        $this->assertNotEmpty($config->modules);

    }

}