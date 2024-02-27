<?php

namespace App\Http\Controllers\Traits;

use CpChart\Data;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class FundPDF
{
    use BasePDFTrait;

    public function __construct(array $arr, bool $isAdmin, bool $debugHtml = false)
    {
        $this->constructPDF();
        $tempDir = $this->tempDir;

        if ($isAdmin) {
            $this->createSharesAllocationGraph($arr, $tempDir);
            $this->createAccountsAllocationGraph($arr, $tempDir);
        }
        $this->createAssetsAllocationGraph($arr, $tempDir);
        $this->createYearlyPerformanceGraph($arr, $tempDir);
        $this->createMonthlyPerformanceGraph($arr, $tempDir);
        $this->createGroupMonthlyPerformanceGraphs($arr, $tempDir);
        $this->createTradePortfoliosGraph($arr, $tempDir);
        $this->createTradePortfoliosGroupGraph($arr, $tempDir);

        $view = 'funds.show_pdf';
        $pdfFile = 'fund.pdf';
        $this->debugHTML($debugHtml, $view, $arr, $tempDir);
        $this->createAndSavePDF($view, $arr, $tempDir, $pdfFile);
    }

    public function createSharesAllocationGraph(array $api, TemporaryDirectory $tempDir): void
    {
        $name = 'shares_allocation.png';
        $values = [$api['summary']['allocated_shares_percent'], $api['summary']['unallocated_shares_percent']];
        $labels = ['Allocated', 'Unallocated'];

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createDoughnutChart($values, $labels, $file);
    }

    public function createAssetsAllocationGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'assets_allocation.png';
        $arr = $api['portfolio']['assets'];
//        print_r("arr alloc: " . json_encode($arr) . "\n");
        $labels = array_map(function ($v) {
            return $v['name'];
        }, $arr);
        $values = array_map(function ($v) {
            return array_key_exists('value', $v) ? $v['value'] : 0;
        }, $arr);

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createDoughnutChart($values, $labels, $file);
    }

    public function createAccountsAllocationGraph(array $api, TemporaryDirectory $tempDir)
    {
        $name = 'accounts_allocation.png';
        $arr = $api['balances'];
        Log::debug($arr);
        $labels = array_map(function ($v) {
            return $v['nickname'];
        }, $arr);
        $values = array_map(function ($v) {
            return $v['shares'];
        }, $arr);

        $labels[] = 'Unallocated';
        $values[] = $api['summary']['unallocated_shares'];

        $this->files[$name] = $file = $tempDir->path($name);
        $this->createDoughnutChart($values, $labels, $file);
    }

    public function createTradePortfoliosGraph(array $api, TemporaryDirectory $tempDir)
    {
        $arr = $api['tradePortfolios'];
        foreach ($arr as $tradePortfolio) {
            $name = 'trade_portfolios_' . $tradePortfolio->id . '.png';

            $labels = array_map(function ($v) {
                return $v['symbol'];
            }, $tradePortfolio->items->toArray());
            $values = array_map(function ($v) {
                return $v['target_share'];
            }, $tradePortfolio->items->toArray());

            $this->files[$name] = $file = $tempDir->path($name);
            $this->createDoughnutChart($values, $labels, $file);
        }
    }

    public function createTradePortfoliosGroupGraph(array $api, TemporaryDirectory $tempDir)
    {
        $arr = $api['tradePortfolios'];
        foreach ($arr as $tradePortfolio) {
            $name = 'trade_portfolios_group' . $tradePortfolio->id . '.png';

            $labels = array_keys($tradePortfolio->groups);
            $values = array_values($tradePortfolio->groups);

            $this->files[$name] = $file = $tempDir->path($name);
            $this->createDoughnutChart($values, $labels, $file);
        }
    }

}
