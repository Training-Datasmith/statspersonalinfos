<?php

class GetDataOptionTest extends StatsTestCase
{
    public function testUnknownOptionLeavesSeriesEmpty()
    {
        $probe = $this->newProbe();
        $probe->setOption('nope', 1);
        $data = $probe->probeGetData(1);

        $this->assertNull($data['title']);
        $this->assertSame(array(), $data['values']);
        $this->assertSame(array(), $data['legend']);
    }

    public function testLayersArgumentDoesNotDuplicatePieSeries()
    {
        Fixture::insertGender(1, 0);
        Fixture::insertCustomer(1, 1, 1, null);

        $probe = $this->newProbe();
        $probe->setOption('gender', 5);
        $data = $probe->probeGetData(5);

        $this->assertMapSame(array('Male' => 1), $data['legend'], $data['values']);
        $this->assertCount(1, $data['values']);
    }
}
