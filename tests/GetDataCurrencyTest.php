<?php

class GetDataCurrencyTest extends StatsTestCase
{
    public function testCurrencyLabelsLocalizedNameAndIsoCode()
    {
        Fixture::insertCurrency(1, 'EUR', 'Euro', 'Eur');
        Fixture::insertOrder(1, 1, 1, 1);

        $probe = $this->newProbe();
        $probe->setOption('currency', 1);
        $data = $probe->probeGetData(1);
        $this->assertSame('Currency distribution', $data['title']);
        $this->assertMapSame(array('Euro (EUR)' => '1'), $data['legend'], $data['values']);

        Context::getContext()->language->id = 2;
        $probe2 = $this->newProbe();
        $probe2->setOption('currency', 1);
        $data2 = $probe2->probeGetData(1);
        $this->assertMapSame(array('Eur (EUR)' => '1'), $data2['legend'], $data2['values']);
    }

    public function testCurrencyDistributionIsScopedToOrderShop()
    {
        Fixture::insertCurrency(1, 'EUR', 'Euro', 'Eur');
        Fixture::insertOrder(1, 1, 1, 1);
        Fixture::insertOrder(2, 1, 1, 1);

        $probe = $this->newProbe();
        $probe->setOption('currency', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('Euro (EUR)' => '1'), $data['legend'], $data['values']);
    }
}
