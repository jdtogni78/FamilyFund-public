<?php
namespace App\Charts;

use CpChart\Data;
use CpChart\Image;

class BaseChart
{
    public $values;
    public $labels;

    public $title="";
    public $titleValues;
    public $titleLabels;

    public $fonts =
    [
        "labels" => [
            "size" => 8,
            "font" => "verdana.ttf",
        ],
        "legend" => [
            "size" => 8,
            "font" => "verdana.ttf",
        ],
        "ticks" => [ // line graph
            "size" => 8,
            "font" => "verdana.ttf",
        ],
    ];

    public $width = 600;
    public $height = 400;

    public $margin = 20;

    protected $image;
    protected $data;

    protected $longestLabel = 0;
    protected $longestValue = 0;

    protected function setup()
    {
        /* Create and populate the Data object */
        $this->data = new Data();
        $this->data->addPoints($this->values, "Title");

        // doughnut
        $this->data->setSerieDescription("Title", $this->titleValues);
        // line
        $this->data->setAxisName(0, $this->title);

        /* Define the absissa serie */
        $this->data->addPoints($this->labels, "Labels");
        $this->data->setSerieDescription("Labels", $this->titleLabels);
        $this->data->setAbscissa("Labels");

        $this->image = new Image($this->width, $this->height, $this->data);

        $this->longestLabel = 0;
        foreach($this->labels as $label) {
            $this->longestLabel = max($this->longestLabel, strlen("" . $label));
        }
        $this->longestValue = 0;
        foreach($this->values as $value) {
            $this->longestValue = max($this->longestValue, strlen("" . $value));
        }
    }

    protected function setFont($what = "labels")
    {
        $this->image->setFontProperties([
            "FontName" => $this->fonts[$what]["font"],
            "FontSize" => $this->fonts[$what]["size"],
            "R" => 80, "G" => 80,"B" => 80
        ]);
    }

    public function saveAs($file)
    {
        $this->image->Render($file);
    }

}


