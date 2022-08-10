<?php
namespace Tests\Feature;

use App\Http\Controllers\Traits\AccountPDF;
use App\Http\Controllers\Traits\AccountTrait;
use App\Http\Controllers\Traits\FundPDF;
use App\Http\Controllers\Traits\FundTrait;
use App\Models\AccountExt;
use App\Models\FundExt;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;
use Tests\DataFactory;

class PDFTest extends TestCase
{
    use ApiTestTrait, WithoutMiddleware, DatabaseTransactions;
    use FundTrait, AccountTrait;

    public $startDt;
    public $endDt;
    public $fund;
    public array $post;

    public function setUp(): void
    {
        parent::setUp();
        $this->startDt = '2022-01-01';
        $this->endDt   = '2022-03-01';
        $this->verbose = false;
    }

    public function testFundAdminPDF()
    {
//        $this->_testFundPDF(true);
//        $this->_testFundPDF(false);
    }

    public function _testFundPDF($isAdmin)
    {
        $fund = FundExt::find(1);
        $asOf = date('2022-01-01');

        $arr = $this->createFullFundResponse($fund, $asOf, $isAdmin);
        $pdf = new FundPDF($arr, $isAdmin, true);
        $pdfFile = $pdf->file();
        print_r($pdfFile);
    }

    public function testAccountPDF()
    {
        $account = AccountExt::find(7);
        $asOf = date('2022-01-01');

        $arr = $this->createAccountViewData($asOf, $account);
        $pdf = new AccountPDF($arr, true);
        $pdfFile = $pdf->file();
//        print_r($pdfFile);
    }

}
