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
        // Use array_values to convert associative array to indexed array for Chart.js
        $values = array_values($this->getGraphData($arr));
        // Get performance values for color coding
        $performances = array_values(array_map(fn($v) => floatval($v['performance'] ?? 0), $arr));

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createBarChart($values, $title, $labels, $file, $performances);
    }

    public function createMonthlyPerformanceGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'monthly_performance.png';

        Log::debug("createMonthlyPerformanceGraph: Starting chart generation");

        if (empty($api['monthly_performance'])) {
            Log::warning("createMonthlyPerformanceGraph: No monthly_performance data available");
            return;
        }

        $labels = array_keys($api['monthly_performance']);
        // Use array_values to convert associative arrays to indexed arrays for Chart.js
        $values1 = array_values($this->getGraphData($api['monthly_performance']));
        $values2 = array_values($this->getGraphData($api['sp500_monthly_performance'] ?? []));
        $values3 = array_values($this->getGraphData($api['cash'] ?? []));

        $this->files[$name] = $file = $tempDir->path($name);

        try {
            $this->getQuickChartService()->generateLineChart(
                $labels,
                ["Fund", "SP500", "Cash"],
                [$values1, $values2, $values3],
                $file
            );
            Log::debug("createMonthlyPerformanceGraph: Chart saved to $file");
        } catch (\Exception $e) {
            Log::error("createMonthlyPerformanceGraph failed: " . $e->getMessage());
        }
    }

    public function createGroupMonthlyPerformanceGraphs(array $api, TemporaryDirectory $tempDir)
    {
        $sp500Variants = ['S&P500', 'SP500', 'SPY', '^GSPC'];
        $hasSp500 = !empty($api['sp500_monthly_performance']);
        $sp500Data = $hasSp500 ? $this->getGraphData($api['sp500_monthly_performance']) : [];
        $arr = $api['asset_monthly_performance'];
        $i = 0;
        foreach ($arr as $group => $perf) {
            $name = 'group' . $i . '_monthly_performance.png';

            $titles = [];
            $graphValues = [];
            $labels = null;

            // Add S&P500 from dedicated data if available
            if ($hasSp500) {
                $titles[] = 'S&P500';
                $graphValues[] = array_values($sp500Data);
                $labels = array_keys($sp500Data);
            }

            foreach ($perf as $symbol => $values) {
                // Skip S&P500 variants only if we already added it from sp500_monthly_performance
                if ($hasSp500 && in_array(strtoupper($symbol), $sp500Variants)) {
                    continue;
                }
                $titles[] = $symbol;
                $graphValues[] = array_values($this->getGraphData($values));
                if ($labels === null) {
                    $labels = array_keys($this->getGraphData($values));
                }
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
        // Sort by date (key) not by value
        ksort($data);

        $this->files[$name] = $file = $tempDir->path($name);
        $labels = array_keys($data);
        $this->createStepChart(array_values($data), $labels, $file, "Shares Holdings");
    }

    public function createGoalsProgressGraph(array $api, TemporaryDirectory $tempDir)
    {
        foreach ($api['goals'] as $goal) {
            $name = 'goals_progress_' . $goal->id . '.png';
            $this->files[$name] = $file = $tempDir->path($name);

            // Get completed_pct from nested structure
            $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
            $currentPct = $goal->progress['current']['completed_pct'] ?? 0;

            // Get time period info
            $period = $goal->progress['period'] ?? [0, 1, 0];
            $yearsElapsed = $period[0] / 365;
            $totalYears = $period[1] / 365;
            $timePct = $period[2];

            $this->getQuickChartService()->generateProgressChart(
                $expectedPct,
                $currentPct,
                $goal->name,
                $file,
                null,
                null,
                $yearsElapsed,
                $totalYears,
                $timePct
            );
        }
    }

    public function addZone(string $label1, string $label2, array $boundary1, array $boundary2)
    {
        $this->zoneBoundary1 = $boundary1;
        $this->zoneBoundary2 = $boundary2;
        $this->hasZone = true;
    }

    public function createLineChart(string $file, $colorIndex = 0, $label1 = null, ?int $width = null, ?int $height = null)
    {
        if ($this->hasZone) {
            $this->getQuickChartService()->generateLineChartWithZone(
                $this->pendingLabels,
                $this->pendingTitles,
                $this->pendingValues,
                $this->zoneBoundary1,
                $this->zoneBoundary2,
                $file,
                $width,
                $height
            );
        } else {
            $this->getQuickChartService()->generateLineChart(
                $this->pendingLabels,
                $this->pendingTitles,
                $this->pendingValues,
                $file,
                $width,
                $height
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

    public function createBarChart(array $values, $title, array $labels, string $file, ?array $performances = null)
    {
        $this->getQuickChartService()->generateBarChart($labels, $values, $title, $file, null, null, $performances);
    }

    protected function createDoughnutChart(array $values, array $labels, string $file): void
    {
        $this->getQuickChartService()->generateDoughnutChart($labels, $values, $file);
    }

    protected function createDoughnutChartLarge(array $values, array $labels, string $file): void
    {
        $this->getQuickChartService()->generateDoughnutChart(
            $labels,
            $values,
            $file,
            config('quickchart.doughnut_large_width', 1000),
            config('quickchart.doughnut_large_height', 600)
        );
    }

    protected function createHorizontalBarChart(array $values, array $labels, string $title, string $file): void
    {
        $this->getQuickChartService()->generateHorizontalBarChart($labels, $values, $title, $file);
    }

    protected function createStackedBarChart(array $values, array $labels, string $title, string $file): void
    {
        $this->getQuickChartService()->generateStackedBarChart($labels, $values, $title, $file);
    }

    private function getGraphData(mixed $arr): array
    {
        return array_map(function ($v) {
            return $v['value'];
        }, $arr);
    }

    public function createLinearRegressionGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'linear_regression.png';

        if (empty($api['linear_regression']['predictions'])) {
            Log::debug("createLinearRegressionGraph: No predictions available");
            return;
        }

        $predictions = $api['linear_regression']['predictions'];
        $labels = array_keys($predictions);
        $predictedValues = array_values($predictions);
        $conservativeValues = array_map(fn($v) => $v * 0.8, $predictedValues);
        $aggressiveValues = array_map(fn($v) => $v * 1.2, $predictedValues);

        $this->files[$name] = $file = $tempDir->path($name);

        try {
            $this->getQuickChartService()->generateLineChart(
                $labels,
                ["Conservative (80%)", "Predicted", "Aggressive (120%)"],
                [$conservativeValues, $predictedValues, $aggressiveValues],
                $file
            );
            Log::debug("createLinearRegressionGraph: Chart saved to $file");
        } catch (\Exception $e) {
            Log::error("createLinearRegressionGraph failed: " . $e->getMessage());
        }
    }
}
