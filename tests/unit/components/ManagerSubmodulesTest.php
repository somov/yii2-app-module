<?php
/**
 *
 * User: develop
 * Date: 30.08.2018
 */

namespace mtest\components;


use Codeception\TestCase\Test;

class ManagerSubmodulesTest extends Test
{
    use ManagerTrait;

    public function testInstall()
    {
        $this->clear();

        $file = $this->createZipTestModule('group-install');

       //$this->manager->readModuleZip($file);

        //$installHandler = $this->getMockBuilder(Module::class)->getMock();
        //$installHandler->expects($this->once())->method('install');

        $this->expectExceptionMessage('Install submodule');

        $r = $this->manager->unzipAndProcess($file, $c);

        $this->assertTrue($r);


    }

    public function testUpdate(){
        $file = $this->createZipTestModule('group-install-update');
        $this->expectExceptionMessage('Update submodule');
        $this->assertTrue($this->manager->unzipAndProcess($file, $c));
    }


    public function testUninstall()
    {
        $this->expectExceptionMessage('Uninstall submodule');
        $this->manager->uninstall('group-install');
    }


}