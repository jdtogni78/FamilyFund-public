<?php
namespace App\Charts;

use CpChart\Data;
use CpChart\Image;

class BaseChart
{
    public $titles = ["title1", "title2", "title3"];
    public $seriesValues = [];
    public $labels;

    public $titleLabels = "Date";

    protected $seriesNames = [];

    public $fonts =
    [
        "labels" => [
            "size" => 12,
            "font" => "verdana.ttf",
        ],
        "legend" => [
            "size" => 12,
            "font" => "verdana.ttf",
        ],
        "ticks" => [ // line graph
            "size" => 12,
            "font" => "verdana.ttf",
        ],
    ];

    public $width = 900;
    public $height = 400;

    public $margin = 20;

    protected $image;
    protected $data;

    protected $longestLabel = 0;
    protected $longestValue = 0;

    public $Palette = [
        "0" => ["R" => 0, "G" => 0, "B" => 255, "Alpha" => 100], // 'blue'
        "1" => ["R" => 255, "G" => 0, "B" => 0, "Alpha" => 100], // 'red'
        "2" => ["R" => 0, "G" => 128, "B" => 0, "Alpha" => 100], // 'green'
        "3" => ["R" => 255, "G" => 165, "B" => 0, "Alpha" => 100], // 'orange'
        "4" => ["R" => 0, "G" => 128, "B" => 128, "Alpha" => 100], // 'teal'
        "5" => ["R" => 128, "G" => 0, "B" => 0, "Alpha" => 100], // 'maroon'
        "6" => ["R" => 255, "G" => 0, "B" => 255, "Alpha" => 100], // 'magenta'
        "7" => ["R" => 0, "G" => 255, "B" => 0, "Alpha" => 100], // 'lime'
        "8" => ["R" => 128, "G" => 128, "B" => 128, "Alpha" => 100], // 'gray'
        "9" => ["R" => 0, "G" => 255, "B" => 255, "Alpha" => 100], // 'cyan'
        "10" => ["R" => 255, "G" => 255, "B" => 0, "Alpha" => 100], // 'yellow'
        "11" => ["R" => 192, "G" => 192, "B" => 192, "Alpha" => 100], // 'silver'
    ];

    protected function setup()
    {
        /* Create and populate the Data object */
        $this->data = new Data();
        $this->data->Palette = $this->Palette;

        $i = 0;
        foreach ($this->seriesValues as $values) {
            $name = "Series" . $i;
            $this->seriesName[$i] = $name;
            $this->data->addPoints($values, $name);
            $this->data->setSerieDescription($name, $this->titles[$i]);
            if ($i == 0)
                $this->data->setAxisName(0, $this->titles[$i]);
            $i ++;
        }

        /* Define the absissa serie */
        $seriesLabels = "Labels";
        $this->data->addPoints($this->labels, $seriesLabels);
        $this->data->setSerieDescription($seriesLabels, $this->titleLabels);
        $this->data->setAbscissa($seriesLabels);

        $this->image = new Image($this->width, $this->height, $this->data);

        $this->longestLabel = 0;
        foreach($this->labels as $label) {
            $this->longestLabel = max($this->longestLabel, strlen("" . $label));
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


