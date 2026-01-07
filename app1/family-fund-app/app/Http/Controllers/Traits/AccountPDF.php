<?php

namespace App\Http\Controllers\Traits;

use Spatie\TemporaryDirectory\TemporaryDirectory;

class AccountPDF
{
    use BasePDFTrait;

    public function __construct($arr, $asOf, bool $debugHtml = false)
    {
        $this->constructPDF();
        $tempDir = $this->tempDir;

        $this->createYearlyPerformanceGraph($arr, $tempDir);
        $this->createMonthlyPerformanceGraph($arr, $tempDir);
        $this->createSharesLineChart($arr, $tempDir);
        $this->createGoalsProgressGraph($arr, $tempDir);
        $this->createPortfolioComparisonGraph($arr, $tempDir);
        $this->createLinearRegressionGraph($arr, $tempDir);
        $view = 'accounts.show_pdf';
        $pdfFile = 'account.pdf';
        $this->debugHTML($debugHtml, $view, $arr, $tempDir);
        $this->createAndSavePDF($view, $arr, $tempDir, $pdfFile);
    }

    public function createPortfolioComparisonGraph(array $arr, TemporaryDirectory $tempDir)
    {
        if (!isset($arr['tradePortfolios']) || count($arr['tradePortfolios']) < 1) {
            return; // Need at least 1 portfolio
        }

        $name = 'portfolio_comparison.png';

        // Format portfolios for the chart service
        $portfolios = [];
        foreach ($arr['tradePortfolios'] as $tradePortfolio) {
            $items = [];
            $portfolioItems = $tradePortfolio->tradePortfolioItems ?? $tradePortfolio->items ?? collect();
            foreach ($portfolioItems as $item) {
                $items[] = [
                    'symbol' => $item->symbol,
                    'target_share' => $item->target_share,
                    'deviation_trigger' => $item->deviation_trigger ?? 0,
                ];
            }
            $portfolios[] = [
                'id' => $tradePortfolio->id,
                'start_dt' => $tradePortfolio->start_dt->format('Y-m-d'),
                'end_dt' => $tradePortfolio->end_dt->format('Y-m-d'),
                'items' => $items,
                'cash_target' => $tradePortfolio->cash_target,
            ];
        }

        $this->files[$name] = $file = $tempDir->path($name);
        $this->getQuickChartService()->generatePortfolioComparisonChart($portfolios, $file);
    }

}
