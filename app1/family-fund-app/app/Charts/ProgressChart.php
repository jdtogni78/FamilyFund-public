<?php

namespace App\Charts;

class ProgressChart extends BaseChart
{
    public function createProgressChart($goal, string $file, $title)
    {
        $this->titles = [$title];
        $this->labels = [$title];
        $this->height = 200;
        $this->setup();
        $this->image->setShadow(true, ["X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20]);

        $progressOptions = [
            "R" => $this->Palette[0]['R'], 
            "G" => $this->Palette[0]['G'], 
            "B" => $this->Palette[0]['B'], 
            "Surrounding" => 20, 
            // "BoxBorderR" => 0, "BoxBorderG" => 0, "BoxBorderB" => 0, 
            // "BoxBackR" => 255, "BoxBackG" => 255, "BoxBackB" => 255,
            "ShowLabel" => true, 
            "Width" => $this->width - 80,
            // "LabelPos" => LABEL_POS_CENTER
        ];

        $this->setFont("labels");
        $this->image->drawText(40, 40, "Expected Value");
        $this->image->drawProgress(40, 60, $goal['expected']['completed_pct'], $progressOptions);
        
        $progressOptions['R'] = $this->Palette[1]['R'];
        $progressOptions['G'] = $this->Palette[1]['G'];
        $progressOptions['B'] = $this->Palette[1]['B'];
        $this->image->drawText(40, 120, "Current Value");
        $this->image->drawProgress(40, 140, $goal['current']['completed_pct'], $progressOptions);
    }
}