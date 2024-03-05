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
        $title = 'Yearly Performance';
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
        $this->createLineChart($file, $labels,
            ["Monthly Performance", "SP500", "Cash"],
            [$values1, $values2, $values3]);
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
            $this->createLineChart($file, $labels, $titles, $graphValues);
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
            $data[substr($v['timestamp'], 0,10)] = $v['balances']['OWN'] * $v['share_price'];
        };
        asort($data);
//        Log::debug("data");
//        Log::debug($data);

        $this->files[$name] = $file = $tempDir->path($name);
        $labels1 = array_keys($data);
        $this->createStepChart(array_values($data), $labels1, $file, "Shares");
    }

    public function createLineChart(string $file, array $labels,
                                    array $titles, array $values)
    {
        $chart = new LineChart();
        $chart->labels = $labels;
        $chart->titles = $titles;
        $chart->seriesValues = $values;
        $chart->createChart();
        $chart->saveAs($file);
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
