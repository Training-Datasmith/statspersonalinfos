<?php

class StatsTestCase extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        Shop::reset();
        Context::reset();
        Tools::reset();
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=statspersonalinfos';
        Fixture::truncateAll();
        Fixture::applySessionClock();
    }

    protected function newProbe()
    {
        return new StatsPersonalInfosProbe();
    }

    protected function probeGender($option = 'gender', $layers = 1)
    {
        $probe = $this->newProbe();
        $probe->setOption($option, $layers);
        $data = $probe->probeGetData($layers);

        return $data;
    }

    protected function assertMapSame(array $expected, array $legend, array $values)
    {
        $this->assertSame(count($expected), count($legend));
        $this->assertSame(count($expected), count($values));

        $map = array();
        $count = count($legend);
        for ($i = 0; $i < $count; $i++) {
            $this->assertInternalType('string', $legend[$i]);
            $this->assertFalse(array_key_exists($legend[$i], $map), 'Duplicate legend label: ' . $legend[$i]);
            $map[$legend[$i]] = $values[$i];
        }

        foreach ($expected as $label => $value) {
            $this->assertArrayHasKey($label, $map, 'Missing label: ' . $label);
            if (is_int($value)) {
                $this->assertInternalType('integer', $map[$label]);
            } else {
                $this->assertInternalType('string', $map[$label]);
            }
            $this->assertSame($value, $map[$label]);
        }

        ksort($expected);
        ksort($map);
        $this->assertSame($expected, $map);
    }

    protected function assertContainsString($needle, $haystack)
    {
        $this->assertInternalType('string', $haystack);
        $this->assertNotSame('', $haystack);
        $this->assertInternalType('string', $needle);
        $this->assertNotSame('', $needle);
        $pos = strpos($haystack, $needle);
        $this->assertInternalType('integer', $pos);
        $this->assertNotSame(false, $pos);
    }
}
