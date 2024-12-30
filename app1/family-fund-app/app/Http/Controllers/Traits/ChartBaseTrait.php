<?php

namespace App\Http\Controllers\Traits;

use App\Charts\BarChart;
use App\Charts\DoughnutChart;
use App\Charts\LineChart;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Scalar\MagicConst\Line;
use Spatie\TemporaryDirectory\TemporaryDirectory;

trait ChartBaseTrait
{
    private $files = [];
    public $chart;
    private $hasZone = false;
    private $zone_label1 = null;
    private $zone_label2 = null;

    public function createYearlyPerformanceGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'yearly_performance.png';
        $arr = $api['yearly_performance'];
        $labels = array_keys($arr);
        $title = 'Yearly Value';
        $values = $this->getGraphData($arr);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createBarChart($values, $title, $labels, $file);
    }

    public function createMonthlyPerformanceGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'monthly_performance.png';
        $labels = array_keys($api['monthly_performance']);
        $values1 = $this->getGraphData($api['monthly_performance']);
        $values2 = $this->getGraphData($api['sp500_monthly_performance']);
        $values3 = $this->getGraphData($api['cash']);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->addLineChart($labels,
            ["Monthly Value", "SP500", "Cash"],
            [$values1, $values2, $values3]);
        $this->createLineChart($file);
    }

    public function createGroupMonthlyPerformanceGraphs(array $api, TemporaryDirectory $tempDir)
    {
        $sp500Values = $this->getGraphData($api['sp500_monthly_performance']);
        $arr = $api['asset_monthly_performance'];
        $i = 0;
        foreach ($arr as $group => $perf) {
            $name = 'group' . $i . '_monthly_performance.png';

            $titles = [];
            $graphValues = [];

            $titles[] = 'S&P500';
            $graphValues[] = $sp500Values;
            $labels = array_keys($sp500Values);
            foreach ($perf as $symbol => $values) {
                $titles[] = $symbol;
                $graphValues[] = $this->getGraphData($values);
            }

            $this->files[$name] = $file = $tempDir->path($name);
            $this->addLineChart($labels, $titles, $graphValues);
            $this->createLineChart($file);
            $i++;
        }

    }

    protected function createSharesLineChart(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'shares.png';
        $arr = $api['transactions'];
        $data = [];
        Log::debug("createSharesLineChart: " . json_encode($arr));
        foreach ($arr as $v) {
            // max of last value and current value
            $data[substr($v['timestamp'], 0,10)] = max($data[substr($v['timestamp'], 0,10)] ?? 0, 
                    $v['balances']['OWN'] * $v['share_price']);
        };
        asort($data);

        $this->files[$name] = $file = $tempDir->path($name);
        $labels1 = array_keys($data);
        $this->createStepChart(array_values($data), $labels1, $file, "Shares");
    }

    public function addZone(string $label1, string $label2, 
                            array $boundary1, array $boundary2) 
    {
        $this->zone_label1 = $label1;
        $this->zone_label2 = $label2;
        $this->chart->data->addPoints($boundary1, $label1);
        $this->chart->data->addPoints($boundary2, $label2);
        // $this->chart->data->setSerieDrawable([$label1, $label2], false);
        Log::debug("addZone: " . json_encode($this->chart->data->Data));
        $this->hasZone = true;
    }

    public function createLineChart(string $file, $colorIndex = 0, $label1 = null)
    {
        $this->chart->createChart();
        // TODO: find out how to control color
        // if ($label1) {
        //     $this->chart->data->DataSet["Series"][$label1]["Color"]["R"] = $this->chart->Palette[$colorIndex];
        //     $this->chart->data->DataSet["Series"]["$label1 target"]["Color"] = $this->chart->Palette[13];
        // }
        $this->chart->image->drawLineChart();

        if ($this->hasZone) {
            $color = $this->chart->Palette[13];
            $this->chart->drawZoneChart($this->zone_label1, $this->zone_label2, 
                [
                    "AreaR" => $color['R'], "AreaG" => $color['G'], "AreaB" => $color['B'], "AreaAlpha" => 20,
                    "LineR" => $color['R'], "LineG" => $color['G'], "LineB" => $color['B'], "LineAlpha" => 20,
                    // "LineTicks" => 1,
                ]
            );
        }
        $this->chart->saveAs($file);
        return $this->chart;
    }

    public function addLineChart(array $labels, array $titles, array $values)
    {
        $this->chart = new LineChart();
        $this->chart->labels = $labels;
        $this->chart->titles = $titles;
        $this->chart->seriesValues = $values;
        return $this->chart;
    }

    public function createStepChart(array $values, array $labels, string $file, $title)
    {
        $chart = new LineChart();
        $chart->labels = $labels;
        $chart->seriesValues = [$values];
        $chart->titles = [$title];
        $chart->createStepChart();
        $chart->saveAs($file);
    }

    public function createBarChart(array $values, $title, array $labels, string $file)
    {
        $chart = new BarChart();
        $chart->labels = $labels;
        $chart->seriesValues = [$values];
        $chart->titles = [$title];
        $chart->createChart();
        $chart->saveAs($file);
    }

    /**
     * @param array $values
     * @param array $labels
     * @param string $file
     * @return void
     */
    protected function createDoughnutChart(array $values, array $labels, string $file): void
    {
        $chart = new DoughnutChart();
        $chart->labels = $labels;
        $chart->seriesValues = [$values];
        $chart->createChart();
        $chart->saveAs($file);
    }

    private function getGraphData(mixed $arr): array
    {
        return array_map(function ($v) {
            return $v['value'];
        }, $arr);
    }
}
