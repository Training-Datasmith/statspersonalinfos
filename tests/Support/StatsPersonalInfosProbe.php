<?php

class StatsPersonalInfosProbe extends statspersonalinfos
{
    public function probeGetData($layers = 1)
    {
        $this->getData($layers);

        return array(
            'values' => $this->_values,
            'legend' => $this->_legend,
            'title' => $this->_titles['main'],
        );
    }
}
