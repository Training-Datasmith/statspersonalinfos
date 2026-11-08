<?php

class GetDataAgeTest extends StatsTestCase
{
    public function testAgeBucketsFollowBirthdayBoundariesOnFrozenDate()
    {
        $birthdays = array(
            '2016-06-15',
            '1998-06-16',
            '1998-06-15',
            '1991-06-16',
            '1991-06-15',
            '1981-06-16',
            '1981-06-15',
            '1966-06-16',
            '1966-06-15',
            '1956-06-16',
            '1956-06-15',
        );
        foreach ($birthdays as $birthday) {
            Fixture::insertCustomer(1, 1, 1, $birthday);
        }

        $probe = $this->newProbe();
        $probe->setOption('age', 1);
        $data = $probe->probeGetData(1);

        $this->assertSame('Age range', $data['title']);
        $this->assertMapSame(
            array(
                '0-18' => '2',
                '18-24' => '2',
                '25-34' => '2',
                '35-49' => '2',
                '50-59' => '2',
                '60+' => '1',
            ),
            $data['legend'],
            $data['values']
        );
    }

    public function testNullAndZeroBirthdaysCountAsUnknown()
    {
        Fixture::insertCustomer(1, 1, 1, null);
        Fixture::insertCustomer(1, 1, 1, '0000-00-00');

        $probe = $this->newProbe();
        $probe->setOption('age', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('Unknown' => '2'), $data['legend'], $data['values']);
    }

    public function testZeroBucketsAreOmitted()
    {
        Fixture::insertCustomer(1, 1, 1, '1991-06-15');

        $probe = $this->newProbe();
        $probe->setOption('age', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('25-34' => '1'), $data['legend'], $data['values']);
    }

    public function testAgeShopRestriction()
    {
        Fixture::insertCustomer(1, 1, 1, '1991-06-15');
        Fixture::insertCustomer(1, 2, 1, '1956-06-15');
        Fixture::insertCustomer(1, 2, 1, null);

        $probe = $this->newProbe();
        $probe->setOption('age', 1);
        $data = $probe->probeGetData(1);

        $this->assertMapSame(array('25-34' => '1'), $data['legend'], $data['values']);
    }
}
