<?php

namespace App\Http\Controllers\Traits;

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
        $view = 'accounts.show_pdf';
        $pdfFile = 'account.pdf';
        $this->debugHTML($debugHtml, $view, $arr, $tempDir);
        $this->createAndSavePDF($view, $arr, $tempDir, $pdfFile);
    }

}
