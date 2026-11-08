<?php

class GetDataLanguageTest extends StatsTestCase
{
    public function testLanguageDistributionCountsOrdersByLanguageName()
    {
        Fixture::insertLang(1, 'English');
        Fixture::insertLang(2, 'French');
        Fixture::insertOrder(1, 1, 1, 1);

        Context::getContext()->language->id = 2;
        $probe = $this->newProbe();
        $probe->setOption('language', 1);
        $data = $probe->probeGetData(1);

        $this->assertSame('Language distribution', $data['title']);
        $this->assertMapSame(array('English' => '1'), $data['legend'], $data['values']);
    }

    public function testLanguageDistributionIsScopedToOrderShop()
    {
        Fixture::insertLang(1, 'English');
        Fixture::insertOrder(1, 1, 1, 1);
        Fixture::insertOrder(2, 1, 1, 1);

        $probe = $this->newProbe();
        $probe->setOption('language', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('English' => '1'), $data['legend'], $data['values']);
    }
}
