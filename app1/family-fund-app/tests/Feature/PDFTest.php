<?php
namespace Tests\Feature;

use App\Http\Controllers\Traits\AccountPDF;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\FundPDF;
use App\Http\Controllers\Traits\FundTrait;
use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\TransactionExt;
use Carbon\Carbon;
use CpChart\Data;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Log;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;

class PDFTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;
    use FundTrait, AccountTrait;

    private DataFactory $factory;
    private string $asOf;

    public function setUp(): void
    {
        parent::setUp();
        $this->factory = new DataFactory();
        $this->factory->createFund();
        $this->asOf = Carbon::tomorrow()->format('Y-m-d');
    }

    public function testFundAdminPDF()
    {
        $this->_testFundPDF(true);
        $this->_testFundPDF(false);
    }

    public function _testFundPDF($isAdmin)
    {
        $fund = $this->factory->fund;

        $arr = $this->createFullFundResponse($fund, $this->asOf, $isAdmin);
        $pdf = new FundPDF();
        $pdf->createFundPDF($arr, $isAdmin, true);
        $pdfFile = $pdf->file();
        Log::debug($pdfFile);
        $this->assertNotNull($pdfFile);
    }

    public function testAccountPDF()
    {
        $this->factory->createUser();
        $tran = $this->factory->createTransaction();
        $this->factory->createBalance(100, $tran, $this->factory->userAccount);
        $account = $this->factory->userAccount;
        $this->factory->createGoal($account);

        $arr = $this->createAccountViewData($this->asOf, $account);
        $progress = $arr['goals'][0]->progress;
        $progress['current']['completed_pct'] = 50;
        $progress['expected']['completed_pct'] = 90;
        $arr['goals'][0]->progress = $progress;
        $pdf = new AccountPDF($arr, true);
        $pdfFile = $pdf->file();
        Log::debug($pdfFile);
        $this->assertNotNull($pdfFile);
    }

    // ==================== FundPDF Additional Tests ====================

    public function testFindTradePortfolioItem_ReturnsItemWhenFound()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-12-31',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                        ['symbol' => 'GOOGL', 'target_share' => 0.3],
                    ]
                ]
            ]
        ];

        $result = $pdf->findTradePortfolioItem($arr, 'AAPL', '2022-06-15');

        $this->assertNotNull($result);
        $this->assertEquals('AAPL', $result['symbol']);
        $this->assertEquals(0.5, $result['target_share']);
    }

    public function testFindTradePortfolioItem_ReturnsNullWhenSymbolNotFound()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-12-31',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                    ]
                ]
            ]
        ];

        $result = $pdf->findTradePortfolioItem($arr, 'MSFT', '2022-06-15');

        $this->assertNull($result);
    }

    public function testFindTradePortfolioItem_ReturnsNullWhenDateOutOfRange()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-12-31',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                    ]
                ]
            ]
        ];

        $result = $pdf->findTradePortfolioItem($arr, 'AAPL', '2023-01-15');

        $this->assertNull($result);
    }

    public function testFindTradePortfolioItem_HandlesMultiplePortfolios()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-06-30',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                    ]
                ],
                [
                    'start_dt' => '2022-07-01',
                    'end_dt' => '2022-12-31',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.6],
                    ]
                ]
            ]
        ];

        // Should find the item from the first portfolio
        $result1 = $pdf->findTradePortfolioItem($arr, 'AAPL', '2022-03-15');
        $this->assertEquals(0.5, $result1['target_share']);

        // Should find the item from the second portfolio
        $result2 = $pdf->findTradePortfolioItem($arr, 'AAPL', '2022-09-15');
        $this->assertEquals(0.6, $result2['target_share']);
    }

    public function testFindTradePortfolioItem_HandlesDateTimeStrings()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01 00:00:00', // With time component
                    'end_dt' => '2022-12-31 23:59:59',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                    ]
                ]
            ]
        ];

        $result = $pdf->findTradePortfolioItem($arr, 'AAPL', '2022-06-15');

        $this->assertNotNull($result);
        $this->assertEquals(0.5, $result['target_share']);
    }

    public function testCreateSharesAllocationGraph_GeneratesChart()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();

        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $tempDirProp = $reflection->getProperty('tempDir');
        $tempDirProp->setAccessible(true);
        $tempDir = $tempDirProp->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'summary' => [
                'allocated_shares_percent' => 75.5,
                'unallocated_shares_percent' => 24.5,
            ]
        ];

        $pdf->createSharesAllocationGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        $this->assertArrayHasKey('shares_allocation.png', $files);
        $this->assertFileExists($files['shares_allocation.png']);

        $pdf->destroy();
    }

    public function testCreateAssetsAllocationGraph_GeneratesChart()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'portfolio' => [
                'assets' => [
                    ['name' => 'AAPL', 'value' => 10000],
                    ['name' => 'GOOGL', 'value' => 5000],
                    ['name' => 'CASH', 'value' => 2000],
                ]
            ]
        ];

        $pdf->createAssetsAllocationGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        $this->assertArrayHasKey('assets_allocation.png', $files);
        $this->assertFileExists($files['assets_allocation.png']);

        $pdf->destroy();
    }

    public function testCreateAssetsAllocationGraph_HandlesZeroValues()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'portfolio' => [
                'assets' => [
                    ['name' => 'AAPL'], // No 'value' key
                    ['name' => 'GOOGL', 'value' => 0],
                ]
            ]
        ];

        $pdf->createAssetsAllocationGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        $this->assertArrayHasKey('assets_allocation.png', $files);

        $pdf->destroy();
    }

    public function testCreateAccountsAllocationGraph_GroupsSmallAccounts()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'summary' => [
                'shares' => 10000,
                'unallocated_shares_percent' => 5,
            ],
            'balances' => [
                ['nickname' => 'Account 1', 'shares' => 5000], // 50%
                ['nickname' => 'Account 2', 'shares' => 3000], // 30%
                ['nickname' => 'Account 3', 'shares' => 1500], // 15%
                ['nickname' => 'Account 4', 'shares' => 200],  // 2% - should be in Others
                ['nickname' => 'Account 5', 'shares' => 100],  // 1% - should be in Others
            ]
        ];

        $pdf->createAccountsAllocationGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        $this->assertArrayHasKey('accounts_allocation.png', $files);
        $this->assertFileExists($files['accounts_allocation.png']);

        $pdf->destroy();
    }

    public function testCreateForecastGraph_SkipsWhenNoPredictions()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'linear_regression' => []
        ];

        $pdf->createForecastGraph($api, $tempDir);

        // Should not create file when no predictions
        $files = $filesProp->getValue($pdf);
        $this->assertArrayNotHasKey('forecast.png', $files);

        $pdf->destroy();
    }

    public function testCreateForecastGraph_HandlesEmptyPredictions()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'linear_regression' => [
                'predictions' => []
            ]
        ];

        $pdf->createForecastGraph($api, $tempDir);

        // Should not create file when predictions are empty
        $files = $filesProp->getValue($pdf);
        $this->assertArrayNotHasKey('forecast.png', $files);

        $pdf->destroy();
    }

    public function testCreatePortfolioComparisonGraph_SkipsWhenNoPortfolios()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'tradePortfolios' => []
        ];

        $pdf->createPortfolioComparisonGraph($api, $tempDir);

        // Should not create file when no portfolios
        $files = $filesProp->getValue($pdf);
        $this->assertArrayNotHasKey('portfolio_comparison.png', $files);

        $pdf->destroy();
    }

    public function testCreatePortfolioGroupComparisonGraph_SkipsWhenNoPortfolios()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'tradePortfolios' => []
        ];

        $pdf->createPortfolioGroupComparisonGraph($api, $tempDir);

        // Should not create file when no portfolios
        $files = $filesProp->getValue($pdf);
        $this->assertArrayNotHasKey('portfolio_group_comparison.png', $files);

        $pdf->destroy();
    }

    public function testCreateAssetPositionsGraph_HandlesMultipleSymbols()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-12-31',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                    ]
                ]
            ],
            'asset_monthly_bands' => [
                'AAPL' => [
                    '2022-01-01' => ['value' => 10000, 'shares' => 100],
                    '2022-02-01' => ['value' => 11000, 'shares' => 110],
                ]
            ]
        ];

        $pdf->createAssetPositionsGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        // Should create a chart file for AAPL
        $this->assertArrayHasKey('asset_positions_AAPL.png', $files);
        $this->assertFileExists($files['asset_positions_AAPL.png']);

        $pdf->destroy();
    }

    public function testCreateAssetPositionsGraph_SkipsNonPortfolioSymbols()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-12-31',
                    'items' => [
                        ['symbol' => 'AAPL', 'target_share' => 0.5],
                    ]
                ]
            ],
            'asset_monthly_bands' => [
                'SP500' => [
                    '2022-01-01' => ['value' => 10000, 'shares' => 100],
                ],
                'CASH' => [
                    '2022-01-01' => ['value' => 5000, 'shares' => 50],
                ],
                'MSFT' => [
                    '2022-01-01' => ['value' => 8000, 'shares' => 80],
                ]
            ]
        ];

        $pdf->createAssetPositionsGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        // Should not create files for SP500, CASH, or MSFT (not in portfolio)
        $this->assertArrayNotHasKey('asset_positions_SP500.png', $files);
        $this->assertArrayNotHasKey('asset_positions_CASH.png', $files);
        $this->assertArrayNotHasKey('asset_positions_MSFT.png', $files);

        $pdf->destroy();
    }

    public function testFindTradePortfolioItem_HandlesEmptyTradePortfolios()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => []
        ];

        $result = $pdf->findTradePortfolioItem($arr, 'AAPL', '2022-06-15');

        $this->assertNull($result);
    }

    public function testFindTradePortfolioItem_HandlesEmptyItems()
    {
        $pdf = new FundPDF();

        $arr = [
            'tradePortfolios' => [
                [
                    'start_dt' => '2022-01-01',
                    'end_dt' => '2022-12-31',
                    'items' => []
                ]
            ]
        ];

        $result = $pdf->findTradePortfolioItem($arr, 'AAPL', '2022-06-15');

        $this->assertNull($result);
    }

    public function testCreateForecastGraph_WithValidPredictions()
    {
        $pdf = new FundPDF();
        $pdf->constructPDF();
        // Use reflection to access private tempDir property
        $reflection = new \ReflectionClass($pdf);
        $property = $reflection->getProperty('tempDir');
        $property->setAccessible(true);
        $tempDir = $property->getValue($pdf);

        // Use reflection to access private files property
        $filesProp = $reflection->getProperty('files');
        $filesProp->setAccessible(true);

        $api = [
            'linear_regression' => [
                'predictions' => [
                    '2023-01-01' => 10000,
                    '2023-02-01' => 11000,
                    '2023-03-01' => 12000,
                ],
                'actuals' => [
                    '2022-10-01' => 8000,
                    '2022-11-01' => 9000,
                    '2022-12-01' => 9500,
                ]
            ]
        ];

        $pdf->createForecastGraph($api, $tempDir);

        $files = $filesProp->getValue($pdf);
        $this->assertArrayHasKey('forecast.png', $files);
        $this->assertFileExists($files['forecast.png']);

        $pdf->destroy();
    }

    public function testFundPDF_WithMultipleAccounts()
    {
        // Create additional users and accounts for more comprehensive PDF testing
        $this->factory->createUser();
        $account1 = $this->factory->userAccount;

        // Create a second user with account
        $this->factory->createUser();
        $account2 = $this->factory->userAccount;

        // Create transactions and balances for both accounts
        $tran = $this->factory->createTransaction();
        $this->factory->createBalance(1000, $tran, $account1);
        $this->factory->createBalance(500, $tran, $account2);

        $fund = $this->factory->fund;
        $arr = $this->createFullFundResponse($fund, $this->asOf, true);

        $pdf = new FundPDF();
        $pdf->createFundPDF($arr, true, true);
        $pdfFile = $pdf->file();

        $this->assertNotNull($pdfFile);
        $this->assertFileExists($pdfFile);
    }

    public function testAccountPDF_WithGoalProgress()
    {
        $this->factory->createUser();
        $tran = $this->factory->createTransaction();
        $this->factory->createBalance(5000, $tran, $this->factory->userAccount);
        $account = $this->factory->userAccount;

        // Create multiple goals
        $this->factory->createGoal($account);
        $this->factory->createGoal($account);

        $arr = $this->createAccountViewData($this->asOf, $account);

        // Set different progress levels for goals
        if (count($arr['goals']) > 0) {
            $progress1 = $arr['goals'][0]->progress;
            $progress1['current']['completed_pct'] = 75;
            $progress1['expected']['completed_pct'] = 60;
            $arr['goals'][0]->progress = $progress1;
        }

        if (count($arr['goals']) > 1) {
            $progress2 = $arr['goals'][1]->progress;
            $progress2['current']['completed_pct'] = 25;
            $progress2['expected']['completed_pct'] = 40;
            $arr['goals'][1]->progress = $progress2;
        }

        $pdf = new AccountPDF($arr, true);
        $pdfFile = $pdf->file();

        $this->assertNotNull($pdfFile);
        $this->assertFileExists($pdfFile);
    }

}
