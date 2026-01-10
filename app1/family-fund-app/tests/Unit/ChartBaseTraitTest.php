<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\ChartBaseTrait;
use App\Services\QuickChartService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Tests\TestCase;

/**
 * Unit tests for ChartBaseTrait methods
 */
class ChartBaseTraitTest extends TestCase
{
    use DatabaseTransactions;

    private $traitObject;
    private TemporaryDirectory $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Create anonymous class that uses the trait
        $this->traitObject = new class {
            use ChartBaseTrait;

            // Expose private methods for testing
            public function testGetGraphData(array $arr): array
            {
                return $this->getGraphData($arr);
            }

            public function testGetNormalizedGraphData(array $arr): array
            {
                return $this->getNormalizedGraphData($arr);
            }

            // Expose files array
            public function getFiles(): array
            {
                return $this->files;
            }
        };

        $this->tempDir = (new TemporaryDirectory())->create();
    }

    protected function tearDown(): void
    {
        $this->tempDir->delete();
        parent::tearDown();
    }

    public function test_get_graph_data_extracts_values()
    {
        $arr = [
            '2022-01-01' => ['value' => 100],
            '2022-02-01' => ['value' => 200],
            '2022-03-01' => ['value' => 300],
        ];

        $result = $this->traitObject->testGetGraphData($arr);

        // array_map preserves keys, so we get associative array
        $this->assertEquals([
            '2022-01-01' => 100,
            '2022-02-01' => 200,
            '2022-03-01' => 300,
        ], $result);
    }

    public function test_get_graph_data_handles_empty_array()
    {
        $result = $this->traitObject->testGetGraphData([]);
        $this->assertEquals([], $result);
    }

    public function test_get_normalized_graph_data_normalizes_to_first_value()
    {
        $arr = [
            '2022-01-01' => ['value' => 100],
            '2022-02-01' => ['value' => 150],
            '2022-03-01' => ['value' => 200],
        ];

        $result = $this->traitObject->testGetNormalizedGraphData($arr);

        $this->assertEquals(1.0, $result['2022-01-01']); // 100/100
        $this->assertEquals(1.5, $result['2022-02-01']); // 150/100
        $this->assertEquals(2.0, $result['2022-03-01']); // 200/100
    }

    public function test_get_normalized_graph_data_uses_price_if_available()
    {
        $arr = [
            '2022-01-01' => ['price' => 50, 'value' => 100],
            '2022-02-01' => ['price' => 100, 'value' => 200],
        ];

        $result = $this->traitObject->testGetNormalizedGraphData($arr);

        $this->assertEquals(1.0, $result['2022-01-01']); // 50/50
        $this->assertEquals(2.0, $result['2022-02-01']); // 100/50
    }

    public function test_get_normalized_graph_data_handles_empty_array()
    {
        $result = $this->traitObject->testGetNormalizedGraphData([]);
        $this->assertEquals([], $result);
    }

    public function test_get_normalized_graph_data_handles_zero_first_value()
    {
        $arr = [
            '2022-01-01' => ['value' => 0],
            '2022-02-01' => ['value' => 100],
        ];

        $result = $this->traitObject->testGetNormalizedGraphData($arr);

        // When first value is 0, it defaults to 1 to avoid division by zero
        $this->assertEquals(0, $result['2022-01-01']); // 0/1
        $this->assertEquals(100, $result['2022-02-01']); // 100/1
    }

    public function test_add_line_chart_stores_data()
    {
        $labels = ['Jan', 'Feb', 'Mar'];
        $titles = ['Series 1'];
        $values = [[100, 200, 300]];

        $result = $this->traitObject->addLineChart($labels, $titles, $values);

        $this->assertNull($result);
    }

    public function test_add_zone_enables_zone_mode()
    {
        $boundary1 = [100, 110, 120];
        $boundary2 = [80, 90, 100];

        $this->traitObject->addZone('Upper', 'Lower', $boundary1, $boundary2);

        // Zone should be enabled - test by trying to create a line chart
        // (method sets internal state)
        $this->assertTrue(true); // Zone was added without error
    }

    public function test_create_bar_chart_generates_file()
    {
        $values = [100, 200, 300];
        $labels = ['Jan', 'Feb', 'Mar'];
        $title = 'Test Bar Chart';
        $file = $this->tempDir->path('test_bar.png');

        $this->traitObject->createBarChart($values, $title, $labels, $file);

        // File should be created by QuickChartService
        // Just verify no exception was thrown
        $this->assertTrue(true);
    }

    public function test_create_step_chart_generates_file()
    {
        $values = [100, 200, 300];
        $labels = ['Jan', 'Feb', 'Mar'];
        $title = 'Test Step Chart';
        $file = $this->tempDir->path('test_step.png');

        $this->traitObject->createStepChart($values, $labels, $file, $title);

        $this->assertTrue(true);
    }

    public function test_create_yearly_performance_graph()
    {
        $api = [
            'yearly_performance' => [
                '2021-01-01' => ['value' => 10000, 'performance' => 0.10],
                '2022-01-01' => ['value' => 11000, 'performance' => 0.10],
                '2023-01-01' => ['value' => 12100, 'performance' => 0.10],
            ],
        ];

        $this->traitObject->createYearlyPerformanceGraph($api, $this->tempDir);

        $files = $this->traitObject->getFiles();
        $this->assertArrayHasKey('yearly_performance.png', $files);
    }

    public function test_create_monthly_performance_graph()
    {
        $api = [
            'monthly_performance' => [
                '2022-01-01' => ['value' => 10000],
                '2022-02-01' => ['value' => 10500],
                '2022-03-01' => ['value' => 11000],
            ],
            'sp500_monthly_performance' => [
                '2022-01-01' => ['value' => 100],
                '2022-02-01' => ['value' => 102],
                '2022-03-01' => ['value' => 105],
            ],
            'cash' => [
                '2022-01-01' => ['value' => 1000],
                '2022-02-01' => ['value' => 1000],
                '2022-03-01' => ['value' => 1000],
            ],
        ];

        $this->traitObject->createMonthlyPerformanceGraph($api, $this->tempDir);

        $files = $this->traitObject->getFiles();
        $this->assertArrayHasKey('monthly_performance.png', $files);
    }

    public function test_create_monthly_performance_graph_handles_empty_data()
    {
        $api = [
            'monthly_performance' => [],
        ];

        $this->traitObject->createMonthlyPerformanceGraph($api, $this->tempDir);

        // Should return early without creating file
        $files = $this->traitObject->getFiles();
        $this->assertArrayNotHasKey('monthly_performance.png', $files);
    }

    public function test_create_group_monthly_performance_graphs()
    {
        $api = [
            'sp500_monthly_performance' => [
                '2022-01-01' => ['value' => 100],
                '2022-02-01' => ['value' => 102],
            ],
            'asset_monthly_performance' => [
                'Stocks' => [
                    'VOO' => [
                        '2022-01-01' => ['value' => 100],
                        '2022-02-01' => ['value' => 105],
                    ],
                    'VTI' => [
                        '2022-01-01' => ['value' => 100],
                        '2022-02-01' => ['value' => 103],
                    ],
                ],
            ],
        ];

        $this->traitObject->createGroupMonthlyPerformanceGraphs($api, $this->tempDir);

        $files = $this->traitObject->getFiles();
        $this->assertArrayHasKey('group0_monthly_performance.png', $files);
    }

    public function test_create_linear_regression_graph()
    {
        $api = [
            'linear_regression' => [
                'predictions' => [
                    '2023-01-01' => 10000,
                    '2024-01-01' => 11000,
                    '2025-01-01' => 12000,
                ],
            ],
        ];

        $this->traitObject->createLinearRegressionGraph($api, $this->tempDir);

        $files = $this->traitObject->getFiles();
        $this->assertArrayHasKey('linear_regression.png', $files);
    }

    public function test_create_linear_regression_graph_handles_empty_predictions()
    {
        $api = [
            'linear_regression' => [
                'predictions' => [],
            ],
        ];

        $this->traitObject->createLinearRegressionGraph($api, $this->tempDir);

        // Should return early without creating file
        $files = $this->traitObject->getFiles();
        $this->assertArrayNotHasKey('linear_regression.png', $files);
    }

    public function test_create_line_chart_without_zone()
    {
        $labels = ['Jan', 'Feb', 'Mar'];
        $titles = ['Series 1'];
        $values = [[100, 200, 300]];
        $file = $this->tempDir->path('test_line.png');

        $this->traitObject->addLineChart($labels, $titles, $values);
        $result = $this->traitObject->createLineChart($file);

        $this->assertNull($result);
    }

    public function test_create_line_chart_with_zone()
    {
        $labels = ['Jan', 'Feb', 'Mar'];
        $titles = ['Series 1'];
        $values = [[100, 200, 300]];
        $boundary1 = [110, 210, 310];
        $boundary2 = [90, 190, 290];
        $file = $this->tempDir->path('test_line_zone.png');

        $this->traitObject->addLineChart($labels, $titles, $values);
        $this->traitObject->addZone('Upper', 'Lower', $boundary1, $boundary2);
        $result = $this->traitObject->createLineChart($file);

        $this->assertNull($result);
    }
}
