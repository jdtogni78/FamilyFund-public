<?php

namespace App\Charts;

class LineChart extends BaseChart
{
    public function createBaseChart($rotation, $mode)
    {
        $this->setup();

        $legendHeight = $this->fonts["legend"]["size"];
        $labelHeight = $this->fonts["labels"]["size"] * $this->longestLabel / 3;
        $chartHeight = $this->height - 2 * $this->margin - $legendHeight - $labelHeight;
        $chartWidth = $this->width - 2 * $this->margin;
        $chartX = $this->margin + ($this->longestValue * $this->fonts["labels"]["size"])/2;
        $chartY = $this->margin * 1.5 + $legendHeight;

        $this->image->setGraphArea($chartX, $chartY, $chartWidth, $chartHeight);

        $this->setFont("ticks");
        $this->image->drawScale([
            "DrawSubTicks" => true,
            "LabelRotation" => $rotation,
            "CycleBackground" => true,
            "GridR" => 0, "GridG" => 0, "GridB" => 0, "GridAlpha" => 10,
            "Mode" => $mode,
        ]);

        // $this->data->Data["Series"]["Title"]["Color"] = [
        //     "R" => 155,
        //     "G" => 155,
        //     "B" => 155,
        // ];
        $legendWidth = $this->longestLabel * $this->fonts["legend"]["size"];
        $legendX = ($this->width - $legendWidth)/2;
        $this->setFont("legend");
        $this->image->drawLegend($legendX, $this->margin, [
            "Style" => LEGEND_NOBORDER,
            "Mode" => LEGEND_HORIZONTAL,
        ]);
    }

    public function createChart()
    {
        $this->createBaseChart(45, SCALE_MODE_FLOATING);

        // $this->data->setSerieTicks("Title", 4);
        $this->data->setSerieWeight("Title", 1.5);
        $this->setFont("labels");
        $this->image->drawLineChart([
            // "DisplayValues" => true,
            // "DisplayColor" => DISPLAY_AUTO
        ]);
    }

    public function createStepChart()
    {
        $this->createBaseChart(45, SCALE_MODE_FLOATING);

        // $this->data->setSerieTicks("Title", 4);
        $this->data->setSerieWeight("Title", 1.5);
        $this->setFont("labels");
        $this->image->drawStepChart([
            // "DisplayValues" => true,
            // "DisplayColor" => DISPLAY_AUTO
        ]);
    }
}
