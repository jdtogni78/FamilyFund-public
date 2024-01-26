<?php
namespace App\Charts;

use CpChart\Data;
use CpChart\Image;

class BaseChart
{
    public $title1="title1";
    public $series1Values;
    public $labels;

    public $title2="title2";
    public $series2Values;

    private $title1Labels = "Date";

    protected string $seriesName1;
    protected string $seriesName2;

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
        "3" => ["R" => 255, "G" => 255, "B" => 0, "Alpha" => 100], // 'yellow'
        "4" => ["R" => 0, "G" => 255, "B" => 255, "Alpha" => 100], // 'cyan'
        "5" => ["R" => 255, "G" => 165, "B" => 0, "Alpha" => 100], // 'orange'
        "6" => ["R" => 128, "G" => 128, "B" => 128, "Alpha" => 100], // 'gray'
        "7" => ["R" => 255, "G" => 0, "B" => 255, "Alpha" => 100], // 'magenta'
        "8" => ["R" => 0, "G" => 255, "B" => 0, "Alpha" => 100], // 'lime'
        "9" => ["R" => 0, "G" => 128, "B" => 128, "Alpha" => 100], // 'teal'
        "10" => ["R" => 128, "G" => 0, "B" => 0, "Alpha" => 100], // 'maroon'
        "11" => ["R" => 192, "G" => 192, "B" => 192, "Alpha" => 100], // 'silver'
    ];

    protected function setup()
    {
        /* Create and populate the Data object */
        $this->data = new Data();
        $this->data->Palette = $this->Palette;
        $this->seriesName1 = "Series1";
        $this->data->addPoints($this->series1Values, $this->seriesName1);
        $this->data->setSerieDescription($this->seriesName1, $this->title1);
        $this->data->setAxisName(0, $this->title1);

        if (isset($this->series2Values)) {
            $this->seriesName2 = "Series2";
            $this->data->addPoints($this->series2Values, $this->seriesName2);
            $this->data->setSerieDescription($this->seriesName2, $this->title2);
//            $this->data->setAxisName(1, $this->title2);
        }

        /* Define the absissa serie */
        $seriesLabels = "Labels";
        $this->data->addPoints($this->labels, $seriesLabels);
        $this->data->setSerieDescription($seriesLabels, $this->title1Labels);
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


