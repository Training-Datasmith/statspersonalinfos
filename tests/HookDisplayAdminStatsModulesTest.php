<?php

class HookDisplayAdminStatsModulesTest extends StatsTestCase
{
    private function seedOneCustomer()
    {
        Fixture::insertCustomer(1, 1, 1, null);
    }

  private function expectedEngineCalls()
  {
      return array(
          array('type' => 'pie', 'option' => 'gender'),
          array('type' => 'pie', 'option' => 'age'),
          array('type' => 'pie', 'option' => 'country'),
          array('type' => 'pie', 'option' => 'currency'),
          array('type' => 'pie', 'option' => 'language'),
      );
  }

    public function testHookWithoutCustomersShowsEmptyMessage()
    {
        $module = new statspersonalinfos();
        $html = $module->hookDisplayAdminStatsModules();
        $this->assertInternalType('string', $html);
        $this->assertContainsString('Registered customer information', $html);
        $this->assertContainsString('Target your audience', $html);
        $this->assertContainsString('No customers have registered yet.', $html);
        $this->assertSame(array(), $module->engineCalls);
        $this->assertSame(array(), $module->csvCalls);
        $pos = strpos($html, 'exportType=');
        $this->assertSame(false, $pos);
    }

    public function testHookRendersFivePiesAndExportLinks()
    {
        $this->seedOneCustomer();
        $module = new statspersonalinfos();
        $html = $module->hookDisplayAdminStatsModules();

        $this->assertSame($this->expectedEngineCalls(), $module->engineCalls);
        $this->assertInternalType('string', $html);
        $this->assertContainsString('GRAPH:gender', $html);
        $this->assertContainsString('GRAPH:age', $html);
        $this->assertContainsString('GRAPH:country', $html);
        $this->assertContainsString('GRAPH:currency', $html);
        $this->assertContainsString('GRAPH:language', $html);
        $this->assertContainsString('Target your audience', $html);
        $this->assertContainsString(
            'Gender distribution allows you to determine the percentage of men and women shoppers on your store.',
            $html
        );
        $this->assertContainsString('Age ranges allow you to better understand target demographics.', $html);
        $this->assertContainsString(
            'Country distribution allows you to analyze which part of the World your customers are shopping from.',
            $html
        );
        $this->assertContainsString(
            'Currency range allows you to determine which currency your customers are using.',
            $html
        );
        $this->assertContainsString(
            'Language distribution allows you to analyze the browsing language used by your customers.',
            $html
        );
        $this->assertContainsString('CSV Export', $html);
        $this->assertContainsString('export=1', $html);
        $this->assertContainsString('exportType=gender', $html);
        $this->assertContainsString('exportType=age', $html);
        $this->assertContainsString('exportType=country', $html);
        $this->assertContainsString('exportType=currency', $html);
        $this->assertContainsString('exportType=language', $html);
        $pos = strpos($html, 'No customers have registered yet.');
        $this->assertSame(false, $pos);
        $this->assertSame(array(), $module->csvCalls);
    }

    /**
     * @dataProvider exportTypeProvider
     */
    public function testHookExportDispatchesPieOption($exportType)
    {
        $this->seedOneCustomer();
        Tools::setValues(array('export' => '1', 'exportType' => $exportType));
        $module = new statspersonalinfos();
        try {
            $module->hookDisplayAdminStatsModules();
            $this->fail('Expected StatsExportInterrupt');
        } catch (StatsExportInterrupt $e) {
            $this->assertSame(array('type' => 'pie', 'option' => $exportType), $e->exportPayload);
        }
        $this->assertSame(array(), $module->engineCalls);
    }

    public function exportTypeProvider()
    {
        return array(
            array('gender'),
            array('age'),
            array('country'),
            array('currency'),
            array('language'),
        );
    }

    public function testHookUnknownExportTypeStillRenders()
    {
        $this->seedOneCustomer();
        Tools::setValues(array('export' => '1', 'exportType' => 'city'));
        $module = new statspersonalinfos();
        $html = $module->hookDisplayAdminStatsModules();
        $this->assertInternalType('string', $html);
        $this->assertSame($this->expectedEngineCalls(), $module->engineCalls);
        $this->assertSame(array(), $module->csvCalls);
    }

    public function testHookExportIgnoredWithoutCustomers()
    {
        Tools::setValues(array('export' => '1', 'exportType' => 'gender'));
        $module = new statspersonalinfos();
        $html = $module->hookDisplayAdminStatsModules();
        $this->assertContainsString('No customers have registered yet.', $html);
        $this->assertSame(array(), $module->engineCalls);
        $this->assertSame(array(), $module->csvCalls);
    }

    public function testHookEscapesExportHref()
    {
        $this->seedOneCustomer();
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&q="<\'';
        $module = new statspersonalinfos();
        $html = $module->hookDisplayAdminStatsModules();
        $this->assertInternalType('string', $html);
        $this->assertContainsString('exportType=gender', $html);
        $this->assertContainsString('&quot;', $html);
        $pos = strpos($html, '<\'');
        $this->assertSame(false, $pos);
    }
}
