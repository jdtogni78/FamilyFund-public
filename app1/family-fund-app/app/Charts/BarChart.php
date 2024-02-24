<?php

namespace App\Charts;

class BarChart extends LineChart
{
    public function createChart()
    {
        $this->createBaseChart(45, SCALE_MODE_START0);
//        $this->data->Data["Series"][$this->seriesNames[0]]["Color"] = [
//            "R" => 0,
//            "G" => 0,
//            "B" => 255,
//            "Alpha" => 100,
//        ];

        $this->image->drawBarChart([
            "DisplayPos" => LABEL_POS_INSIDE,
            "DisplayValues" => true,
            "Rounded" => true,
            "Surrounding" => 30,
        ]);

    }
}
