<?php

namespace App\Charts;

use CpChart\Chart\Pie;

class DoughnutChart extends BaseChart
{
    protected $chart;
    public function createChart()
    {
        $this->setup();

//        $this->data->setPalette($this->Palette);
        $this->chart = new Pie($this->image, $this->data);

        $legendWidth = ($this->fonts["legend"]["size"] - 1) * $this->longestLabel + $this->margin;
        $chartHeight = $this->height - 2 * $this->margin;
        // labels right + left + some overlap w legend = 2.7
        $chartWidth = $this->width - 2 * $this->margin - $legendWidth * 2.7;
        $chartX = $this->width/2 - $legendWidth/2;
        $chartY = $this->height/2;
        $oR = min($chartHeight, $chartWidth)/2;

        $this->setFont("labels");
        $this->chart->draw2DRing($chartX, $chartY, [
//            "DrawLabels" => true,
            "WriteValues" => true,
            "ValueR"=>0,"ValueG"=>0,"ValueB"=>0,
            "LabelStacked" => true,
            "Border" => true,
            "OuterRadius" => $oR,
            "InnerRadius" => $oR/2
        ]);

        $this->setFont("legend");
        $this->chart->drawPieLegend($this->width - $legendWidth - $this->margin, $this->margin, [
            "Alpha" => 20,
            "BoxSize" => 20,
            "Mode" => LEGEND_VERTICAL,
        ]);
    }
}
