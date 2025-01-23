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

}
