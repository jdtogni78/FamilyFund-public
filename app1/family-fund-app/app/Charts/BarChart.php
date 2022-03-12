<?php

namespace App\Charts;

class BarChart extends LineChart
{
    public function createChart()
    {
        $this->createBaseChart(45, SCALE_MODE_START0);

        $this->image->drawBarChart([
            "DisplayPos" => LABEL_POS_INSIDE, 
            "DisplayValues" => true, 
            "Rounded" => true, 
            "Surrounding" => 30,
        ]);
        
    }
}
