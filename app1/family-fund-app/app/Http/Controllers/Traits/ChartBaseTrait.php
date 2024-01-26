<?php

namespace App\Http\Controllers\Traits;

use App\Charts\BarChart;
use App\Charts\DoughnutChart;
use App\Charts\LineChart;
use Illuminate\Support\Facades\Log;
use Spatie\TemporaryDirectory\TemporaryDirectory;

trait ChartBaseTrait
{
    private $files = [];

    public function createYearlyPerformanceGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'yearly_performance.png';
        $arr = $api['yearly_performance'];
        $labels = array_keys($arr);
        $values = array_map(function ($v) {
            return $v['value'];
        }, $arr);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createBarChart($values, $labels, $file);
    }

    public function createMonthlyPerformanceGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'monthly_performance.png';
        $arr = $api['monthly_performance'];
        $labels = array_keys($arr);
        $values1 = array_map(function ($v) {
            return $v['value'];
        }, $arr);

        $arr = $api['sp500_monthly_performance'];
        $values2 = array_map(function ($v) {
            return $v['value'];
        }, $arr);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createLineChart($file, $labels, "Performance", $values1, "SP500", $values2);
    }

    protected function createSharesLineChart(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'shares.png';
        $arr = $api['transactions'];
        $data = [];
        foreach ($arr as $v) {
            $data[substr($v['timestamp'], 0,10)] = $v['balances']['OWN'] * $v['share_price'];
        };
        asort($data);
//        Log::debug("data");
//        Log::debug($data);

        $this->files[$name] = $file = $tempDir->path($name);
        $labels1 = array_keys($data);
        $this->createStepChart(array_values($data), $labels1, $file, "Shares");
    }

    public function createLineChart(string $file, array $labels, $title1, array $values1, $title2, array $values2=null)
    {
        $chart = new LineChart();
//        Log::debug($labels);
        $chart->labels = $labels;

        $chart->title1 = $title1;
        $chart->series1Values = $values1;

        $chart->title2 = $title2;
        $chart->series2Values = $values2;

        $chart->createChart();
        $chart->saveAs($file);
    }

    public function createStepChart(array $values, array $labels, string $file, $title)
    {
        $chart = new LineChart();
//        Log::debug($labels);
        $chart->series1Values = $values;
        $chart->labels = $labels;
        $chart->title1 = $title;
        $chart->createStepChart();
        $chart->saveAs($file);
    }

    public function createBarChart(array $values, array $labels, string $file)
    {
        $chart = new BarChart();
        $chart->series1Values = $values;
        $chart->labels = $labels;
        $chart->title1 = "Performance";
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
        $chart->series1Values = $values;
        $chart->labels = $labels;
        $chart->createChart();
        $chart->saveAs($file);
    }
}
