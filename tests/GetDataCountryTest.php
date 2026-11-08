<?php

class GetDataCountryTest extends StatsTestCase
{
    public function testCountryUsesContextLanguageAndSkipsUnassignedAddresses()
    {
        Fixture::insertCountry(8, 'France', 'Frankreich');
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertAddress(1, 8);
        Fixture::insertAddress(0, 8);

        $probe = $this->newProbe();
        $probe->setOption('country', 1);
        $data = $probe->probeGetData(1);
        $this->assertSame('Country distribution', $data['title']);
        $this->assertMapSame(array('France' => '1'), $data['legend'], $data['values']);

        Context::getContext()->language->id = 2;
        $probe2 = $this->newProbe();
        $probe2->setOption('country', 1);
        $data2 = $probe2->probeGetData(1);
        $this->assertMapSame(array('Frankreich' => '1'), $data2['legend'], $data2['values']);
    }

    public function testCountryExcludesOtherShops()
    {
        Fixture::insertCountry(8, 'France', 'Frankreich');
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertAddress(1, 8);
        Fixture::insertCustomer(1, 2, 1, null);
        Fixture::insertAddress(2, 8);

        $probe = $this->newProbe();
        $probe->setOption('country', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('France' => '1'), $data['legend'], $data['values']);
    }

    public function testCountryIsEmptyWhenCustomersHaveNoAddresses()
    {
        Fixture::insertCustomer(1, 1, 1, null);

        $probe = $this->newProbe();
        $probe->setOption('country', 1);
        $data = $probe->probeGetData(1);

        $this->assertSame('Country distribution', $data['title']);
        $this->assertSame(array(), $data['values']);
        $this->assertSame(array(), $data['legend']);
    }
}
