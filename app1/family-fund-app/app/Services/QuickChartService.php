<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class QuickChartService
{
    protected string $baseUrl;
    protected int $width;
    protected int $height;
    protected array $colors;
    protected array $datasetColors;
    protected string $backgroundColor;
    protected string $fontFamily;
    protected int $fontSize;
    protected int $titleFontSize;
    protected string $fontColor;
    protected int $legendFontSize;

    public function __construct()
    {
        $this->baseUrl = config('quickchart.base_url');
        $this->width = config('quickchart.width');
        $this->height = config('quickchart.height');
        $this->colors = config('quickchart.colors');
        $this->datasetColors = config('quickchart.dataset_colors');
        $this->backgroundColor = config('quickchart.background_color');
        $this->fontFamily = config('quickchart.font_family');
        $this->fontSize = config('quickchart.font_size');
        $this->titleFontSize = config('quickchart.title_font_size');
        $this->fontColor = config('quickchart.font_color', '#1e293b');
        $this->legendFontSize = config('quickchart.legend_font_size', 13);
    }

    /**
     * Generate a bar chart and save to file
     */
    public function generateBarChart(
        array $labels,
        array $values,
        string $title,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        ?array $performances = null
    ): string {
        $options = $this->getBaseOptions($title);
        // Add data labels inside bars
        $options['plugins']['datalabels'] = [
            'display' => true,
            'color' => '#ffffff',
            'anchor' => 'center',
            'align' => 'center',
            'font' => [
                'weight' => '900',
                'size' => 16,
                'family' => 'Arial Black, sans-serif',
            ],
            'formatter' => "function(v) { var n = Math.round(v).toString(); var r = ''; for(var i=0; i<n.length; i++) { if(i>0 && (n.length-i)%3===0) r+=','; r+=n[i]; } return '$'+r }",
        ];

        // Generate colors based on performance if provided
        $barColors = $performances !== null
            ? $this->generatePerformanceBarColors($performances)
            : $this->generateBarColors(count($values));

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => $title,
                    'data' => $values,
                    'backgroundColor' => $barColors,
                    'borderColor' => $barColors,
                    'borderWidth' => 1,
                ]],
            ],
            'options' => $options,
        ];

        return $this->generateChart($config, $filePath, $width, $height);
    }

    /**
     * Generate a line chart with multiple datasets
     */
    public function generateLineChart(
        array $labels,
        array $titles,
        array $datasets,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        bool $stepped = false,
        int $maxLabels = 24
    ): string {
        // Reduce labels to show only maxLabels while keeping all data points
        $totalLabels = count($labels);
        if ($totalLabels > $maxLabels) {
            $step = ceil($totalLabels / $maxLabels);
            $sparseLabels = [];
            foreach ($labels as $i => $label) {
                $sparseLabels[] = ($i % $step === 0) ? $label : '';
            }
            $labels = $sparseLabels;
        }

        $chartDatasets = [];
        foreach ($datasets as $i => $data) {
            $color = $this->datasetColors[$i % count($this->datasetColors)];
            $chartDatasets[] = [
                'label' => $titles[$i] ?? "Series $i",
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $this->hexToRgba($color, 0.1),
                'fill' => false,
                'lineTension' => $stepped ? 0 : 0.1,
                'borderWidth' => 2,
                'pointRadius' => 2,
                'pointBackgroundColor' => $color,
                'steppedLine' => $stepped ? 'before' : false,
            ];
        }

        $config = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => $chartDatasets,
            ],
            'options' => $this->getBaseOptions($titles[0] ?? ''),
        ];

        return $this->generateChart($config, $filePath, $width, $height);
    }

    /**
     * Generate a step/line chart (for shares over time)
     */
    public function generateStepChart(
        array $labels,
        array $values,
        string $title,
        string $filePath,
        ?int $width = null,
        ?int $height = null
    ): string {
        return $this->generateLineChart(
            $labels,
            [$title],
            [$values],
            $filePath,
            $width,
            $height,
            true  // stepped
        );
    }

    /**
     * Generate a line chart with zone areas (for trade bands)
     */
    public function generateLineChartWithZone(
        array $labels,
        array $titles,
        array $datasets,
        array $zoneBoundary1,
        array $zoneBoundary2,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        int $maxLabels = 24
    ): string {
        // Reduce labels to show only maxLabels while keeping all data points
        $totalLabels = count($labels);
        if ($totalLabels > $maxLabels) {
            $step = ceil($totalLabels / $maxLabels);
            $sparseLabels = [];
            foreach ($labels as $i => $label) {
                $sparseLabels[] = ($i % $step === 0) ? $label : '';
            }
            $labels = $sparseLabels;
        }

        $chartDatasets = [];

        // Add zone as a filled area between boundaries (no legend labels)
        $zoneColor = $this->hexToRgba($this->colors['gray'], 0.2);
        $chartDatasets[] = [
            'label' => '',
            'data' => $zoneBoundary1,
            'borderColor' => $this->hexToRgba($this->colors['gray'], 0.5),
            'backgroundColor' => $zoneColor,
            'fill' => '+1',
            'borderWidth' => 1,
            'pointRadius' => 0,
        ];
        $chartDatasets[] = [
            'label' => '',
            'data' => $zoneBoundary2,
            'borderColor' => $this->hexToRgba($this->colors['gray'], 0.5),
            'backgroundColor' => 'transparent',
            'fill' => false,
            'borderWidth' => 1,
            'pointRadius' => 0,
        ];

        // Add main data series
        foreach ($datasets as $i => $data) {
            $label = $titles[$i] ?? "Series $i";
            $isTarget = stripos($label, 'target') !== false;

            if ($isTarget) {
                // Target line: gray, dashed, no dots
                $chartDatasets[] = [
                    'label' => $label,
                    'data' => $data,
                    'borderColor' => $this->hexToRgba($this->colors['gray'], 0.7),
                    'backgroundColor' => 'transparent',
                    'fill' => false,
                    'tension' => 0,
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                ];
            } else {
                // Other lines: colored with dots
                $color = $this->datasetColors[$i % count($this->datasetColors)];
                $chartDatasets[] = [
                    'label' => $label,
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => 'transparent',
                    'fill' => false,
                    'tension' => 0.1,
                    'borderWidth' => 2,
                    'pointRadius' => 2,
                    'pointBackgroundColor' => $color,
                ];
            }
        }

        $options = $this->getBaseOptions($titles[0] ?? '');
        $options['plugins']['legend']['labels']['filter'] = "function(item) { return item.text && item.text.trim() !== ''; }";
        // Format Y-axis as percentages
        $options['scales']['y']['ticks']['callback'] = "function(v) { return (v * 100).toFixed(1) + '%'; }";

        $config = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => $chartDatasets,
            ],
            'options' => $options,
        ];

        return $this->generateChart($config, $filePath, $width, $height);
    }

    /**
     * Generate a forecast chart with prediction lines (conservative, predicted, aggressive)
     * Uses Chart.js v2 syntax for QuickChart compatibility
     */
    public function generateForecastChart(
        array $predictions,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        int $maxLabels = 12
    ): string {
        $labels = array_keys($predictions);
        $predictedData = array_values($predictions);
        $conservativeData = array_map(fn($v) => $v * 0.8, $predictedData);
        $aggressiveData = array_map(fn($v) => $v * 1.2, $predictedData);

        // Reduce labels to show only maxLabels while keeping all data points
        $totalLabels = count($labels);
        if ($totalLabels > $maxLabels) {
            $step = ceil($totalLabels / $maxLabels);
            $sparseLabels = [];
            foreach ($labels as $i => $label) {
                $sparseLabels[] = ($i % $step === 0) ? substr($label, 0, 4) : '';
            }
            $labels = $sparseLabels;
        } else {
            // Just show year for cleaner display
            $labels = array_map(fn($l) => substr($l, 0, 4), $labels);
        }

        $chartDatasets = [
            [
                'label' => 'Conservative (80%)',
                'data' => $conservativeData,
                'borderColor' => $this->datasetColors[2],
                'backgroundColor' => 'transparent',
                'fill' => false,
                'borderWidth' => 2,
                'pointRadius' => 3,
                'pointBackgroundColor' => $this->datasetColors[2],
                'borderDash' => [5, 5],
            ],
            [
                'label' => 'Predicted Value',
                'data' => $predictedData,
                'borderColor' => $this->datasetColors[0],
                'backgroundColor' => 'transparent',
                'fill' => false,
                'borderWidth' => 3,
                'pointRadius' => 4,
                'pointBackgroundColor' => $this->datasetColors[0],
            ],
            [
                'label' => 'Aggressive (120%)',
                'data' => $aggressiveData,
                'borderColor' => $this->datasetColors[1],
                'backgroundColor' => 'transparent',
                'fill' => false,
                'borderWidth' => 2,
                'pointRadius' => 3,
                'pointBackgroundColor' => $this->datasetColors[1],
                'borderDash' => [5, 5],
            ],
        ];

        // Use Chart.js v2 syntax for QuickChart
        $config = [
            'type' => 'line',
            'data' => [
                'labels' => $labels,
                'datasets' => $chartDatasets,
            ],
            'options' => [
                'responsive' => false,
                'scales' => [
                    'yAxes' => [[
                        'ticks' => [
                            'fontColor' => $this->fontColor,
                            'callback' => "function(v) { var n = Math.round(v).toString(); var r = ''; for(var i=0; i<n.length; i++) { if(i>0 && (n.length-i)%3===0) r+=','; r+=n[i]; } return '$'+r }",
                        ],
                        'gridLines' => ['color' => 'rgba(0,0,0,0.1)'],
                    ]],
                    'xAxes' => [[
                        'ticks' => [
                            'fontColor' => $this->fontColor,
                        ],
                        'gridLines' => ['display' => false],
                    ]],
                ],
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'fontColor' => '#000000',
                        'fontFamily' => $this->fontFamily,
                        'fontSize' => 12,
                        'fontStyle' => 'bold',
                        'padding' => 15,
                    ],
                ],
            ],
        ];

        return $this->generateChart($config, $filePath, $width ?? 800, $height ?? 400);
    }

    /**
     * Generate a stacked area chart (for showing all allocations together)
     */
    public function generateStackedAreaChart(
        array $labels,
        array $seriesNames,
        array $datasets,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        int $maxLabels = 24
    ): string {
        // Reduce labels to show only maxLabels while keeping all data points
        $totalLabels = count($labels);
        if ($totalLabels > $maxLabels) {
            $step = ceil($totalLabels / $maxLabels);
            $sparseLabels = [];
            foreach ($labels as $i => $label) {
                $sparseLabels[] = ($i % $step === 0) ? $label : '';
            }
            $labels = $sparseLabels;
        }

        $chartDatasets = [];
        foreach ($datasets as $i => $data) {
            $color = $this->datasetColors[$i % count($this->datasetColors)];
            $chartDatasets[] = [
                'label' => $seriesNames[$i] ?? "Series $i",
                'data' => $data,
                'backgroundColor' => $this->hexToRgba($color, 0.8),
                'borderColor' => $color,
                'borderWidth' => 0,
            ];
        }

        $options = $this->getBaseOptions('');
        $options['scales']['y']['stacked'] = true;
        $options['scales']['x']['stacked'] = true;
        // Format Y-axis as percentages
        $options['scales']['y']['ticks']['callback'] = "function(v) { return (v * 100).toFixed(0) + '%'; }";
        $options['scales']['y']['max'] = 1.0;
        $options['scales']['y']['min'] = 0;
        // Remove gaps between bars for area-like appearance
        $options['barPercentage'] = 1.0;
        $options['categoryPercentage'] = 1.0;

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => $chartDatasets,
            ],
            'options' => $options,
        ];

        return $this->generateChart($config, $filePath, $width, $height);
    }

    /**
     * Generate a doughnut chart
     */
    public function generateDoughnutChart(
        array $labels,
        array $values,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        bool $showPercentages = true
    ): string {
        $colors = array_slice($this->datasetColors, 0, count($values));

        // Format labels to show percentages
        $total = array_sum($values);
        $formattedLabels = [];
        foreach ($labels as $i => $label) {
            $pct = $total > 0 ? ($values[$i] / $total) * 100 : 0;
            $formattedLabels[] = $showPercentages ? "$label (" . number_format($pct, 1) . "%)" : $label;
        }

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $formattedLabels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                        'labels' => [
                            'color' => '#000000',
                            'font' => [
                                'family' => $this->fontFamily,
                                'size' => 14,
                                'weight' => 'bold',
                            ],
                            'padding' => 12,
                        ],
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#000000',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold',
                        ],
                        'formatter' => "function(value, context) { var total = context.dataset.data.reduce((a,b) => a+b, 0); var pct = total <= 1.5 ? (value*100).toFixed(1) : (value/total*100).toFixed(1); return pct > 5 ? pct + '%' : ''; }",
                    ],
                ],
            ],
        ];

        return $this->generateChart(
            $config,
            $filePath,
            $width ?? config('quickchart.doughnut_width', 600),
            $height ?? config('quickchart.doughnut_height', 450)
        );
    }

    /**
     * Generate a horizontal bar chart (good for many categories)
     * Uses Chart.js v2 'horizontalBar' type
     */
    public function generateHorizontalBarChart(
        array $labels,
        array $values,
        string $title,
        string $filePath,
        ?int $width = null,
        ?int $height = null
    ): string {
        $colors = array_slice($this->datasetColors, 0, count($values));

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ]],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'title' => [
                        'display' => !empty($title),
                        'text' => $title,
                        'color' => $this->fontColor,
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->titleFontSize,
                            'weight' => 'bold',
                        ],
                    ],
                    'legend' => [
                        'display' => false,
                    ],
                ],
                'scales' => [
                    'x' => [
                        'ticks' => [
                            'color' => $this->fontColor,
                            'font' => [
                                'family' => $this->fontFamily,
                                'size' => $this->fontSize,
                            ],
                        ],
                        'grid' => [
                            'color' => 'rgba(0,0,0,0.1)',
                        ],
                    ],
                    'y' => [
                        'ticks' => [
                            'color' => $this->fontColor,
                            'font' => [
                                'family' => $this->fontFamily,
                                'size' => 12,
                            ],
                        ],
                        'grid' => [
                            'display' => false,
                        ],
                    ],
                ],
            ],
        ];

        // Calculate height based on number of items
        $calculatedHeight = max(400, count($labels) * 28 + 80);

        return $this->generateChart(
            $config,
            $filePath,
            $width ?? 900,
            $height ?? $calculatedHeight
        );
    }

    /**
     * Generate a stacked horizontal bar chart (for accounts allocation)
     * Uses Chart.js v2 'horizontalBar' type
     */
    public function generateStackedBarChart(
        array $labels,
        array $values,
        string $title,
        string $filePath,
        ?int $width = null,
        ?int $height = null
    ): string {
        // Cycle through colors if more items than colors
        $colors = [];
        $colorCount = count($this->datasetColors);
        foreach ($values as $i => $value) {
            $colors[] = $this->datasetColors[$i % $colorCount];
        }

        // Create separate datasets for each segment (for stacked effect)
        $datasets = [];
        foreach ($values as $i => $value) {
            $datasets[] = [
                'label' => $labels[$i],
                'data' => [$value],
                'backgroundColor' => $colors[$i],
                'borderWidth' => 0,
            ];
        }

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => [''],
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'title' => [
                        'display' => !empty($title),
                        'text' => $title,
                        'color' => $this->fontColor,
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->titleFontSize,
                            'weight' => 'bold',
                        ],
                    ],
                    'legend' => [
                        'position' => 'bottom',
                        'labels' => [
                            'color' => '#000000',
                            'font' => [
                                'family' => $this->fontFamily,
                                'size' => 12,
                                'weight' => 'bold',
                            ],
                            'boxWidth' => 14,
                            'padding' => 10,
                        ],
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#000000',
                        'font' => [
                            'weight' => 'bold',
                            'size' => 12,
                        ],
                        'formatter' => "function(value, context) { return value > 5 ? value.toFixed(1) + '%' : ''; }",
                    ],
                ],
                'scales' => [
                    'x' => [
                        'stacked' => true,
                        'display' => false,
                    ],
                    'y' => [
                        'stacked' => true,
                        'display' => false,
                    ],
                ],
            ],
        ];

        return $this->generateChart(
            $config,
            $filePath,
            $width ?? 900,
            $height ?? 200
        );
    }

    /**
     * Generate a progress bar chart (horizontal bars showing expected vs current)
     * Style: Two separate progress bars, each 0-100% scale like the old CpChart version
     * Uses Chart.js v2 'horizontalBar' type
     */
    public function generateProgressChart(
        float $expectedPct,
        float $currentPct,
        string $title,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        ?float $yearsElapsed = null,
        ?float $totalYears = null,
        ?float $timePct = null
    ): string {
        // Determine colors based on whether on track
        $isOnTrack = $currentPct >= $expectedPct;
        $currentColor = $isOnTrack ? $this->colors['success'] : $this->colors['danger'];

        // Build labels with time info if available
        $expectedLabel = 'Expected';
        $currentLabel = 'Current';
        if ($yearsElapsed !== null && $totalYears !== null) {
            $expectedLabel = sprintf('Expected (%.1f of %.1f years)', $yearsElapsed, $totalYears);
            $currentLabel = sprintf('Current (%.0f%% of time elapsed)', $timePct ?? 0);
        }

        $config = [
            'type' => 'horizontalBar',
            'data' => [
                'labels' => [$expectedLabel, $currentLabel],
                'datasets' => [
                    [
                        'label' => 'Progress',
                        'data' => [$expectedPct, $currentPct],
                        'backgroundColor' => [$this->colors['primary'], $currentColor],
                        'borderColor' => ['#000000', '#000000'],
                        'borderWidth' => 1,
                        'barPercentage' => 0.6,
                    ],
                ],
            ],
            'options' => [
                'responsive' => false,
                'plugins' => [
                    'legend' => [
                        'display' => false,
                    ],
                    'datalabels' => [
                        'display' => true,
                        'color' => '#000000',
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold',
                        ],
                        'formatter' => "function(value) { return value.toFixed(1) + '%'; }",
                        'anchor' => 'end',
                        'align' => 'right',
                    ],
                ],
                'scales' => [
                    'xAxes' => [[
                        'ticks' => [
                            'min' => 0,
                            'max' => 100,
                            'stepSize' => 20,
                            'callback' => "function(value) { return value + '%'; }",
                        ],
                        'gridLines' => [
                            'color' => 'rgba(0,0,0,0.1)',
                            'drawBorder' => true,
                        ],
                    ]],
                    'yAxes' => [[
                        'gridLines' => [
                            'display' => false,
                        ],
                        'ticks' => [
                            'fontColor' => '#000000',
                            'fontStyle' => 'bold',
                            'fontSize' => 14,
                        ],
                    ]],
                ],
            ],
        ];

        return $this->generateChart(
            $config,
            $filePath,
            $width ?? $this->width,
            $height ?? config('quickchart.progress_height', 200)
        );
    }

    /**
     * Generate a vertical stacked bar chart for comparing trade portfolios
     * Each bar represents a portfolio, segments represent asset allocations
     * Optionally includes current assets as the last bar
     */
    public function generatePortfolioComparisonChart(
        array $portfolios,
        string $filePath,
        ?int $width = null,
        ?int $height = null,
        ?array $currentAssets = null
    ): string {
        // Get all unique symbols across all portfolios
        $allSymbols = [];
        foreach ($portfolios as $portfolio) {
            foreach ($portfolio['items'] as $item) {
                if (!in_array($item['symbol'], $allSymbols)) {
                    $allSymbols[] = $item['symbol'];
                }
            }
        }
        // Also get symbols from current assets if provided (skip Cash - added at end)
        if ($currentAssets) {
            foreach ($currentAssets as $asset) {
                // Skip Cash variants - we'll add it at the end
                if (strtoupper($asset['symbol']) === 'CASH') {
                    continue;
                }
                if (!in_array($asset['symbol'], $allSymbols)) {
                    $allSymbols[] = $asset['symbol'];
                }
            }
        }
        $allSymbols[] = 'Cash';

        // Create datasets - one per symbol
        $datasets = [];
        foreach ($allSymbols as $i => $symbol) {
            $data = [];
            // Add data for each trade portfolio
            foreach ($portfolios as $portfolio) {
                if ($symbol === 'Cash') {
                    $data[] = ($portfolio['cash_target'] ?? 0) * 100;
                } else {
                    $found = false;
                    foreach ($portfolio['items'] as $item) {
                        if ($item['symbol'] === $symbol) {
                            $data[] = $item['target_share'] * 100;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $data[] = 0;
                    }
                }
            }

            // Add data for current assets if provided
            if ($currentAssets) {
                if ($symbol === 'Cash') {
                    // Find cash in current assets
                    $found = false;
                    foreach ($currentAssets as $asset) {
                        if (strtoupper($asset['symbol']) === 'CASH') {
                            $data[] = $asset['percent'];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $data[] = 0;
                    }
                } else {
                    $found = false;
                    foreach ($currentAssets as $asset) {
                        if ($asset['symbol'] === $symbol) {
                            $data[] = $asset['percent'];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $data[] = 0;
                    }
                }
            }

            $color = $this->datasetColors[$i % count($this->datasetColors)];
            $datasets[] = [
                'label' => $symbol,
                'data' => $data,
                'backgroundColor' => $color,
                'borderColor' => '#ffffff',
                'borderWidth' => 1,
            ];
        }

        // Portfolio labels (dates)
        $labels = array_map(function($p) {
            return '#' . $p['id'] . ': ' . $p['start_dt'] . ' to ' . $p['end_dt'];
        }, $portfolios);

        // Add "Current" label if current assets provided
        if ($currentAssets) {
            $labels[] = 'Current';
        }

        // QuickChart uses Chart.js v2, which requires xAxes/yAxes array syntax for stacking
        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
            'options' => [
                'responsive' => false,
                'scales' => [
                    'xAxes' => [[
                        'stacked' => true,
                        'gridLines' => ['display' => false],
                        'ticks' => [
                            'fontColor' => $this->fontColor,
                            'fontFamily' => $this->fontFamily,
                            'fontSize' => 10,
                        ],
                    ]],
                    'yAxes' => [[
                        'stacked' => true,
                        'ticks' => [
                            'max' => 100,
                            'min' => 0,
                            'fontColor' => $this->fontColor,
                            'callback' => "function(v) { return v + '%'; }",
                        ],
                        'gridLines' => ['color' => 'rgba(0,0,0,0.05)'],
                    ]],
                ],
                'plugins' => [
                    'datalabels' => [
                        'display' => true,
                        'color' => '#ffffff',
                        'font' => [
                            'size' => 9,
                            'weight' => 'bold',
                        ],
                        'formatter' => "function(value, context) { if (value < 2) return ''; return context.dataset.label + ' ' + value.toFixed(0) + '%'; }",
                        'textShadowColor' => 'rgba(0,0,0,0.5)',
                        'textShadowBlur' => 3,
                    ],
                ],
                'legend' => [
                    'position' => 'top',
                    'labels' => [
                        'fontColor' => '#000000',
                        'fontFamily' => $this->fontFamily,
                        'fontSize' => 11,
                        'fontStyle' => 'bold',
                        'padding' => 8,
                    ],
                ],
            ],
        ];

        return $this->generateChart(
            $config,
            $filePath,
            $width ?? 800,
            $height ?? 400
        );
    }

    /**
     * Generate chart from config and save to file
     */
    protected function generateChart(
        array $config,
        string $filePath,
        ?int $width = null,
        ?int $height = null
    ): string {
        $width = $width ?? $this->width;
        $height = $height ?? $this->height;

        // Convert config to JSON, handling JS functions
        $chartJson = $this->configToJson($config);

        Log::debug("QuickChart JSON: " . substr($chartJson, 0, 1500));

        $url = $this->baseUrl . '/chart';

        try {
            $response = Http::timeout(30)->post($url, [
                'chart' => $chartJson,
                'width' => $width,
                'height' => $height,
                'backgroundColor' => $this->backgroundColor,
                'format' => 'png',
            ]);

            if ($response->successful()) {
                file_put_contents($filePath, $response->body());
                return $filePath;
            }

            Log::error('QuickChart API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('QuickChart API returned error: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('QuickChart request failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw $e;
        }
    }

    /**
     * Convert config array to JSON, preserving JavaScript functions
     */
    protected function configToJson(array $config): string
    {
        // First, encode to JSON
        $json = json_encode($config, JSON_UNESCAPED_SLASHES);

        // Replace quoted function strings with actual functions
        // Pattern: "function(...) { ... }" - handles nested braces
        $json = preg_replace_callback(
            '/"(function\s*\([^)]*\)\s*\{(?:[^{}]|\{(?:[^{}]|\{[^{}]*\})*\})*\})"/s',
            function ($matches) {
                return $matches[1];
            },
            $json
        );

        return $json;
    }

    /**
     * Generate colors for bar chart (highlight current year)
     */
    protected function generateBarColors(int $count): array
    {
        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            // Last bar is highlighted (current period)
            if ($i === $count - 1) {
                $colors[] = $this->colors['primary'];
            } else {
                $colors[] = $this->colors['secondary'];
            }
        }
        return $colors;
    }

    /**
     * Generate colors for bar chart based on performance values
     * Green for positive, red for negative, blue for current (last) bar
     */
    protected function generatePerformanceBarColors(array $performances): array
    {
        $colors = [];
        $count = count($performances);
        for ($i = 0; $i < $count; $i++) {
            // Last bar is always blue (current period)
            if ($i === $count - 1) {
                $colors[] = $this->colors['primary'];
            } elseif ($performances[$i] >= 0) {
                $colors[] = $this->colors['success'];
            } else {
                $colors[] = $this->colors['danger'];
            }
        }
        return $colors;
    }

    /**
     * Get base chart options
     */
    protected function getBaseOptions(string $title): array
    {
        return [
            'responsive' => false,
            'plugins' => [
                'title' => [
                    'display' => !empty($title),
                    'text' => $title,
                    'color' => $this->fontColor,
                    'font' => [
                        'family' => $this->fontFamily,
                        'size' => $this->titleFontSize,
                        'weight' => 'bold',
                    ],
                ],
                'legend' => [
                    'labels' => [
                        'color' => $this->fontColor,
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->legendFontSize,
                            'weight' => '600',
                        ],
                        'padding' => 15,
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'color' => $this->fontColor,
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->fontSize,
                            'weight' => '500',
                        ],
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'autoSkip' => true,
                        'autoSkipPadding' => 100,
                        'maxTicksLimit' => 8,
                    ],
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.1)',
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'color' => $this->fontColor,
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->fontSize,
                            'weight' => '500',
                        ],
                        'callback' => "function(v) { var n = Math.round(v).toString(); var r = ''; for(var i=0; i<n.length; i++) { if(i>0 && (n.length-i)%3===0) r+=','; r+=n[i]; } return r }",
                    ],
                    'grid' => [
                        'color' => 'rgba(0,0,0,0.1)',
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert hex color to rgba
     */
    protected function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r, $g, $b, $alpha)";
    }
}
