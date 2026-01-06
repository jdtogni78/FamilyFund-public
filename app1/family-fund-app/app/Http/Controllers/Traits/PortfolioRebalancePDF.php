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

        $labels = array_keys($rebalance);

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
                $this->createLineChart($file);
            }
        }
    }
}
