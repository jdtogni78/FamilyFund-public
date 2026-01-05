<?php

namespace App\Http\Controllers\Traits;

use App\Services\QuickChartService;
use Illuminate\Support\Facades\Log;
use Spatie\TemporaryDirectory\TemporaryDirectory;

trait ChartBaseTrait
{
    private array $files = [];
    private ?QuickChartService $quickChartService = null;

    // Zone data for line charts with bands
    private array $pendingLabels = [];
    private array $pendingTitles = [];
    private array $pendingValues = [];
    private array $zoneBoundary1 = [];
    private array $zoneBoundary2 = [];
    private bool $hasZone = false;

    protected function getQuickChartService(): QuickChartService
    {
        if ($this->quickChartService === null) {
            $this->quickChartService = new QuickChartService();
        }
        return $this->quickChartService;
    }

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
        $this->getQuickChartService()->generateLineChart(
            $labels,
            ["Monthly Value", "SP500", "Cash"],
            [$values1, $values2, $values3],
            $file
        );
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
            $this->getQuickChartService()->generateLineChart($labels, $titles, $graphValues, $file);
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
            $data[substr($v->timestamp, 0, 10)] = max(
                $data[substr($v->timestamp, 0, 10)] ?? 0,
                $v->balance->shares * $v->share_price
            );
        }
        asort($data);

        $this->files[$name] = $file = $tempDir->path($name);
        $labels = array_keys($data);
        $this->createStepChart(array_values($data), $labels, $file, "Shares");
    }

    public function createGoalsProgressGraph(array $api, TemporaryDirectory $tempDir)
    {
        foreach ($api['goals'] as $goal) {
            $name = 'goals_progress_' . $goal->id . '.png';
            $this->files[$name] = $file = $tempDir->path($name);

            $this->getQuickChartService()->generateProgressChart(
                $goal->progress['expected_pct'] ?? 0,
                $goal->progress['current_pct'] ?? 0,
                $goal->name,
                $file
            );
        }
    }

    public function addZone(string $label1, string $label2, array $boundary1, array $boundary2)
    {
        $this->zoneBoundary1 = $boundary1;
        $this->zoneBoundary2 = $boundary2;
        $this->hasZone = true;
    }

    public function createLineChart(string $file, $colorIndex = 0, $label1 = null)
    {
        if ($this->hasZone) {
            $this->getQuickChartService()->generateLineChartWithZone(
                $this->pendingLabels,
                $this->pendingTitles,
                $this->pendingValues,
                $this->zoneBoundary1,
                $this->zoneBoundary2,
                $file
            );
        } else {
            $this->getQuickChartService()->generateLineChart(
                $this->pendingLabels,
                $this->pendingTitles,
                $this->pendingValues,
                $file
            );
        }

        // Reset state
        $this->hasZone = false;
        $this->zoneBoundary1 = [];
        $this->zoneBoundary2 = [];

        return null;
    }

    public function addLineChart(array $labels, array $titles, array $values)
    {
        // Store for later use by createLineChart
        $this->pendingLabels = $labels;
        $this->pendingTitles = $titles;
        $this->pendingValues = $values;
        $this->hasZone = false;

        return null;
    }

    public function createStepChart(array $values, array $labels, string $file, $title)
    {
        $this->getQuickChartService()->generateStepChart($labels, $values, $title, $file);
    }

    public function createBarChart(array $values, $title, array $labels, string $file)
    {
        $this->getQuickChartService()->generateBarChart($labels, $values, $title, $file);
    }

    protected function createDoughnutChart(array $values, array $labels, string $file): void
    {
        $this->getQuickChartService()->generateDoughnutChart($labels, $values, $file);
    }

    private function getGraphData(mixed $arr): array
    {
        return array_map(function ($v) {
            return $v['value'];
        }, $arr);
    }
}
