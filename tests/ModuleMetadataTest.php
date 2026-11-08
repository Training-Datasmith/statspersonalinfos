<?php

class ModuleMetadataTest extends StatsTestCase
{
    public function testConstructorIdentity()
    {
        $module = new statspersonalinfos();
        $this->assertSame('statspersonalinfos', $module->name);
        $this->assertSame('administration', $module->tab);
        $this->assertSame('2.1.0', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertSame('1.7.1.0', $module->ps_versions_compliancy['min']);
        $this->assertSame(_PS_VERSION_, $module->ps_versions_compliancy['max']);
    }

    public function testConstructorCopiesSourceStrings()
    {
        $module = new statspersonalinfos();
        $this->assertSame('Registered customer information', $module->displayName);
        $this->assertSame(
            'Enrich your stats, add information about your registered customers and learn more about them!',
            $module->description
        );
    }

    public function testConfigXmlMatchesNameAndVersion()
    {
        $module = new statspersonalinfos();
        $xmlPath = dirname(dirname(__FILE__)) . '/config.xml';
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf('SimpleXMLElement', $xml);
        $this->assertSame('statspersonalinfos', (string) $xml->name);
        $this->assertSame('2.1.0', (string) $xml->version);
        $this->assertSame($module->version, (string) $xml->version);
    }
}
