<?php

class InstallAndUpgradeTest extends StatsTestCase
{
    public function testInstallRegistersDisplayHook()
    {
        $module = new statspersonalinfos();
        $module->parentInstallResult = true;
        $this->assertTrue($module->install());
        $this->assertSame(array(array('register', 'displayAdminStatsModules')), $module->hookCalls);
    }

    public function testInstallShortCircuitsWhenParentFails()
    {
        $module = new statspersonalinfos();
        $module->parentInstallResult = false;
        $this->assertFalse($module->install());
        $this->assertSame(array(), $module->hookCalls);
    }

    public function testUpgrade204RewiresHook()
    {
        $module = new statspersonalinfos();
        $this->assertTrue(upgrade_module_2_0_4($module));
        $this->assertSame(
            array(
                array('unregister', 'AdminStatsModules'),
                array('register', 'displayAdminStatsModules'),
            ),
            $module->hookCalls
        );
    }
}
