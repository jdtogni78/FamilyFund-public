<?php

namespace App\Http\Controllers\Traits;

use Spatie\TemporaryDirectory\TemporaryDirectory;

class PortfolioRebalancePDF
{
    use BasePDFTrait;

    public function __construct(array $arr, bool $debugHtml = false)
    {
        $this->constructPDF();
        $tempDir = $this->tempDir;

        // Create charts for each symbol
        $this->createRebalanceCharts($arr, $tempDir);

        $view = 'portfolios.show_rebalance_pdf';
        $pdfFile = 'portfolio_rebalance.pdf';
        $this->debugHTML($debugHtml, $view, $arr, $tempDir);
        $this->createAndSavePDF($view, $arr, $tempDir, $pdfFile);
    }

    protected function createRebalanceCharts(array $api, TemporaryDirectory $tempDir): void
    {
        $rebalance = $api['rebalance'];
        $symbols = $api['symbols'];

        if (empty($rebalance)) {
            return;
        }

        $originalLabels = array_keys($rebalance);

        foreach ($symbols as $symbolInfo) {
            $symbol = $symbolInfo['symbol'];
            $slug = \Str::slug($symbol);
            $name = "rebalance_{$slug}.png";

            // Extract data for this symbol
            $targetData = [];
            $minData = [];
            $maxData = [];
            $actualData = [];

            $lastValue = ['target' => 0, 'min' => 0, 'max' => 0, 'perc' => 0];
            $labels = $originalLabels;

            foreach ($rebalance as $date => $dayData) {
                if (isset($dayData[$symbol])) {
                    $lastValue = [
                        'target' => $dayData[$symbol]['target'],
                        'min' => $dayData[$symbol]['min'],
                        'max' => $dayData[$symbol]['max'],
                        'perc' => $dayData[$symbol]['perc'],
                    ];
                }

                $targetData[] = $lastValue['target'];
                $minData[] = $lastValue['min'];
                $maxData[] = $lastValue['max'];
                $actualData[] = $lastValue['perc'];
            }

            // Downsample if too many data points (QuickChart has payload limits)
            $maxPoints = 100;
            if (count($labels) > $maxPoints) {
                $result = $this->downsampleData($labels, [$targetData, $minData, $maxData, $actualData], $maxPoints);
                $labels = $result['labels'];
                $targetData = $result['datasets'][0];
                $minData = $result['datasets'][1];
                $maxData = $result['datasets'][2];
                $actualData = $result['datasets'][3];
            }

            // Only create chart if we have data
            if (!empty($actualData) && array_sum($actualData) > 0) {
                $this->files[$name] = $file = $tempDir->path($name);

                // Use the zone chart with target and actual allocation
                $this->addLineChart(
                    $labels,
                    ['Target', 'Actual'],
                    [$targetData, $actualData]
                );
                $this->addZone('Min', 'Max', $minData, $maxData);
                $this->createLineChart($file, 0, null, 800, 250);
            }
        }
    }

    /**
     * Create a stacked area chart showing all symbols together
     */
    protected function createStackedOverviewChart(array $api, TemporaryDirectory $tempDir): void
    {
        $rebalance = $api['rebalance'];
        $symbols = $api['symbols'];

        if (empty($rebalance)) {
            return;
        }

        $labels = array_keys($rebalance);
        $seriesNames = [];
        $datasets = [];

        foreach ($symbols as $symbolInfo) {
            $symbol = $symbolInfo['symbol'];
            $seriesNames[] = $symbol;
            $actualData = [];
            $lastValue = 0;

            foreach ($rebalance as $date => $dayData) {
                if (isset($dayData[$symbol])) {
                    $lastValue = $dayData[$symbol]['perc'];
                }
                $actualData[] = $lastValue;
            }

            $datasets[] = $actualData;
        }

        // Downsample if too many data points
        $maxPoints = 100;
        if (count($labels) > $maxPoints) {
            $result = $this->downsampleData($labels, $datasets, $maxPoints);
            $labels = $result['labels'];
            $datasets = $result['datasets'];
        }

        $name = 'rebalance_stacked.png';
        $this->files[$name] = $file = $tempDir->path($name);

        $this->getQuickChartService()->generateStackedAreaChart(
            $labels,
            $seriesNames,
            $datasets,
            $file,
            900,
            300
        );
    }

    /**
     * Downsample data arrays to reduce payload size for QuickChart
     */
    protected function downsampleData(array $labels, array $datasets, int $maxPoints): array
    {
        $totalPoints = count($labels);
        $step = ceil($totalPoints / $maxPoints);

        $newLabels = [];
        $newDatasets = array_fill(0, count($datasets), []);

        for ($i = 0; $i < $totalPoints; $i += $step) {
            $newLabels[] = $labels[$i];
            foreach ($datasets as $j => $dataset) {
                $newDatasets[$j][] = $dataset[$i];
            }
        }

        // Always include the last point
        $lastIndex = $totalPoints - 1;
        if (($totalPoints - 1) % $step !== 0) {
            $newLabels[] = $labels[$lastIndex];
            foreach ($datasets as $j => $dataset) {
                $newDatasets[$j][] = $dataset[$lastIndex];
            }
        }

        return [
            'labels' => $newLabels,
            'datasets' => $newDatasets,
        ];
    }
}
