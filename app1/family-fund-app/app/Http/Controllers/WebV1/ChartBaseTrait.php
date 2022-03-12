<?php

namespace App\Http\Controllers\WebV1;

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
        $values = array_map(function ($v) {
            return $v['value'];
        }, $arr);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createLineChart($values, $labels, $file, "Performance");
    }

    public function createLineChart(array $values, array $labels, string $file, $title)
    {
        $chart = new LineChart();
        Log::debug($labels);
        $chart->values = $values;
        $chart->labels = $labels;
        $chart->titleValues = $title;
        $chart->titleLabels = "Date";
        $chart->createChart();
        $chart->saveAs($file);
    }

    public function createBarChart(array $values, array $labels, string $file)
    {
        $chart = new BarChart();
        $chart->values = $values;
        $chart->labels = $labels;
        $chart->titleValues = "Performance";
        $chart->titleLabels = "Date";
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
        $chart->values = $values;
        $chart->labels = $labels;
        $chart->createChart();
        $chart->saveAs($file);
    }
}
