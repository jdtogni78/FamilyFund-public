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
        ?int $height = null
    ): string {
        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'label' => $title,
                    'data' => $values,
                    'backgroundColor' => $this->generateBarColors(count($values)),
                    'borderColor' => $this->colors['primary'],
                    'borderWidth' => 1,
                ]],
            ],
            'options' => $this->getBaseOptions($title),
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
        bool $stepped = false
    ): string {
        $chartDatasets = [];
        foreach ($datasets as $i => $data) {
            $color = $this->datasetColors[$i % count($this->datasetColors)];
            $chartDatasets[] = [
                'label' => $titles[$i] ?? "Series $i",
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $this->hexToRgba($color, 0.1),
                'fill' => false,
                'tension' => 0.1,
                'borderWidth' => 2,
                'pointRadius' => 3,
                'pointBackgroundColor' => $color,
                'stepped' => $stepped,
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
        ?int $height = null
    ): string {
        $chartDatasets = [];

        // Add zone as a filled area between boundaries
        $zoneColor = $this->hexToRgba($this->colors['gray'], 0.2);
        $chartDatasets[] = [
            'label' => 'Target Zone',
            'data' => $zoneBoundary1,
            'borderColor' => $this->hexToRgba($this->colors['gray'], 0.5),
            'backgroundColor' => $zoneColor,
            'fill' => '+1',
            'borderWidth' => 1,
            'pointRadius' => 0,
        ];
        $chartDatasets[] = [
            'label' => '_hidden',
            'data' => $zoneBoundary2,
            'borderColor' => $this->hexToRgba($this->colors['gray'], 0.5),
            'backgroundColor' => 'transparent',
            'fill' => false,
            'borderWidth' => 1,
            'pointRadius' => 0,
        ];

        // Add main data series
        foreach ($datasets as $i => $data) {
            $color = $this->datasetColors[$i % count($this->datasetColors)];
            $chartDatasets[] = [
                'label' => $titles[$i] ?? "Series $i",
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => 'transparent',
                'fill' => false,
                'tension' => 0.1,
                'borderWidth' => 2,
                'pointRadius' => 3,
                'pointBackgroundColor' => $color,
            ];
        }

        $options = $this->getBaseOptions($titles[0] ?? '');
        $options['plugins']['legend']['labels']['filter'] = "function(item) { return item.text !== '_hidden'; }";

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
     * Generate a doughnut chart
     */
    public function generateDoughnutChart(
        array $labels,
        array $values,
        string $filePath,
        ?int $width = null,
        ?int $height = null
    ): string {
        $colors = array_slice($this->datasetColors, 0, count($values));

        $config = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
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
                            'font' => [
                                'family' => $this->fontFamily,
                                'size' => $this->fontSize,
                            ],
                        ],
                    ],
                    'datalabels' => [
                        'display' => true,
                        'formatter' => "function(value, context) {
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = ((value / total) * 100).toFixed(1);
                            return percentage > 5 ? percentage + '%' : '';
                        }",
                        'color' => '#ffffff',
                        'font' => [
                            'weight' => 'bold',
                        ],
                    ],
                ],
            ],
        ];

        return $this->generateChart($config, $filePath, $width ?? 500, $height ?? 400);
    }

    /**
     * Generate a progress bar chart (horizontal bar showing expected vs current)
     */
    public function generateProgressChart(
        float $expectedPct,
        float $currentPct,
        string $title,
        string $filePath,
        ?int $width = null,
        ?int $height = null
    ): string {
        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => ['Progress'],
                'datasets' => [
                    [
                        'label' => 'Expected',
                        'data' => [$expectedPct],
                        'backgroundColor' => $this->hexToRgba($this->colors['secondary'], 0.5),
                        'borderColor' => $this->colors['secondary'],
                        'borderWidth' => 1,
                    ],
                    [
                        'label' => 'Current',
                        'data' => [$currentPct],
                        'backgroundColor' => $currentPct >= $expectedPct
                            ? $this->colors['success']
                            : $this->colors['warning'],
                        'borderColor' => $currentPct >= $expectedPct
                            ? $this->colors['success']
                            : $this->colors['warning'],
                        'borderWidth' => 1,
                    ],
                ],
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => false,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => $title,
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->titleFontSize,
                            'weight' => 'bold',
                        ],
                    ],
                    'legend' => [
                        'position' => 'bottom',
                    ],
                ],
                'scales' => [
                    'x' => [
                        'min' => 0,
                        'max' => 100,
                        'ticks' => [
                            'callback' => "function(value) { return value + '%'; }",
                        ],
                    ],
                ],
            ],
        ];

        return $this->generateChart(
            $config,
            $filePath,
            $width ?? $this->width,
            $height ?? config('quickchart.progress_height')
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
        // Pattern: "function(...) { ... }"
        $json = preg_replace_callback(
            '/"(function\s*\([^)]*\)\s*\{[^}]+\})"/s',
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
                    'font' => [
                        'family' => $this->fontFamily,
                        'size' => $this->titleFontSize,
                        'weight' => 'bold',
                    ],
                ],
                'legend' => [
                    'labels' => [
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->fontSize,
                        ],
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->fontSize,
                        ],
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'font' => [
                            'family' => $this->fontFamily,
                            'size' => $this->fontSize,
                        ],
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
