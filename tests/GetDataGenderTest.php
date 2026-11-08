<?php

class GetDataGenderTest extends StatsTestCase
{
    public function testGenderDistributionSumsCustomersByGenderType()
    {
        Fixture::insertGender(1, 0);
        Fixture::insertGender(2, 1);
        Fixture::insertGender(3, 0);
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertCustomer(3, 1, 1, null);
        Fixture::insertCustomer(2, 1, 1, null);
        Fixture::insertCustomer(0, 1, 1, null);

        $probe = $this->newProbe();
        $probe->setOption('gender', 1);
        $data = $probe->probeGetData(1);

        $this->assertSame('Gender distribution', $data['title']);
        $this->assertMapSame(
            array('Male' => 3, 'Female' => 1, 'Unknown' => 1),
            $data['legend'],
            $data['values']
        );
    }

    public function testGenderDistributionExcludesOtherShopsWhenSharingIsOff()
    {
        Fixture::insertGender(1, 0);
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertCustomer(1, 2, 1, null);

        $probe = $this->newProbe();
        $probe->setOption('gender', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('Male' => 1), $data['legend'], $data['values']);
    }

    public function testGenderDistributionUsesShopGroupWhenSharingIsOn()
    {
        Fixture::insertGender(1, 0);
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertCustomer(1, 2, 1, null);
        Fixture::insertCustomer(1, 3, 2, null);
        Shop::setShareCustomer(true);

        $probe = $this->newProbe();
        $probe->setOption('gender', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('Male' => 2), $data['legend'], $data['values']);
    }

    public function testGenderDistributionIsEmptyWhenNoCustomers()
    {
        $probe = $this->newProbe();
        $probe->setOption('gender', 1);
        $data = $probe->probeGetData(1);

        $this->assertSame('Gender distribution', $data['title']);
        $this->assertSame(array(), $data['values']);
        $this->assertSame(array(), $data['legend']);
    }
}
