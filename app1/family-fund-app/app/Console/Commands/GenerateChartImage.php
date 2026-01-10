<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateChartImage extends Command
{
    protected $signature = 'chart:generate {fund_id} {symbol} {--from=} {--output=}';
    protected $description = 'Generate a chart image for a fund symbol using quickchart';

    public function handle()
    {
        $fundId = $this->argument('fund_id');
        $symbol = $this->argument('symbol');
        $from = $this->option('from') ?? now()->subYear()->format('Y-m-d');
        $output = $this->option('output') ?? storage_path("app/chart_{$symbol}.png");

        // Get fund data
        $fund = \App\Models\Fund::find($fundId);
        if (!$fund) {
            $this->error("Fund {$fundId} not found");
            return 1;
        }

        // Use the trait to get data
        $controller = app(\App\Http\Controllers\WebV1\FundControllerExt::class);
        $api = $controller->createFundResponseTradeBands($fund, $from);

        if (!isset($api['asset_monthly_bands'][$symbol])) {
            $this->error("Symbol {$symbol} not found in fund data");
            $this->info("Available symbols: " . implode(', ', array_keys($api['asset_monthly_bands'])));
            return 1;
        }

        $symbolData = $api['asset_monthly_bands'][$symbol];
        $labels = array_keys($symbolData);
        $values = array_map(fn($d) => $d['value'], array_values($symbolData));

        // Build band data
        $upData = [];
        $downData = [];
        $targetData = [];
        $sumData = [];

        // Calculate sum of all assets per date
        foreach ($api['asset_monthly_bands'] as $sym => $data) {
            foreach ($data as $date => $info) {
                $sumData[$date] = ($sumData[$date] ?? 0) + $info['value'];
            }
        }

        foreach ($labels as $label) {
            $port = collect($api['tradePortfolios'])->first(function($p) use ($label) {
                $startDt = substr($p['start_dt'] ?? '', 0, 10);
                $endDt = substr($p['end_dt'] ?? '', 0, 10);
                return $startDt <= $label && $label <= $endDt;
            });

            if (!$port || !isset($port['items'])) {
                $upData[] = null;
                $downData[] = null;
                $targetData[] = null;
                continue;
            }

            $portItem = collect($port['items'])->firstWhere('symbol', $symbol);
            if (!$portItem) {
                $upData[] = null;
                $downData[] = null;
                $targetData[] = null;
                continue;
            }

            $targetShare = floatval($portItem['target_share']);
            $deviation = floatval($portItem['deviation_trigger']);
            $totalValue = $sumData[$label] ?? 0;

            $upData[] = $totalValue * ($targetShare + $deviation);
            $downData[] = $totalValue * ($targetShare - $deviation);
            $targetData[] = $totalValue * $targetShare;
        }

        // Build QuickChart config
        $chartConfig = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => "{$symbol} max",
                        'data' => $upData,
                        'borderColor' => 'rgba(150, 150, 150, 0.5)',
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                        'fill' => '+1',
                        'pointRadius' => 2,
                    ],
                    [
                        'label' => "{$symbol} min",
                        'data' => $downData,
                        'borderColor' => 'rgba(150, 150, 150, 0.5)',
                        'fill' => false,
                        'pointRadius' => 2,
                    ],
                    [
                        'label' => $symbol,
                        'data' => $values,
                        'borderColor' => 'rgb(255, 99, 132)',
                        'backgroundColor' => 'rgb(255, 99, 132)',
                        'borderWidth' => 2,
                        'pointRadius' => 3,
                        'fill' => false,
                    ],
                    [
                        'label' => "{$symbol} target",
                        'data' => $targetData,
                        'borderColor' => 'lightgray',
                        'borderDash' => [5, 5],
                        'pointRadius' => 2,
                        'fill' => false,
                    ],
                ],
            ],
            'options' => [
                'plugins' => [
                    'datalabels' => ['display' => false],
                ],
                'scales' => [
                    'x' => ['ticks' => ['maxRotation' => 45, 'minRotation' => 45]],
                    'y' => ['beginAtZero' => false],
                ],
            ],
        ];

        // Send to quickchart
        $response = Http::post('http://quickchart:3400/chart', [
            'chart' => $chartConfig,
            'width' => 800,
            'height' => 400,
            'format' => 'png',
        ]);

        if ($response->failed()) {
            $this->error("Failed to generate chart: " . $response->body());
            return 1;
        }

        // Ensure directory exists
        $dir = dirname($output);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($output, $response->body());
        $this->info("Chart saved to: {$output}");

        return 0;
    }
}
