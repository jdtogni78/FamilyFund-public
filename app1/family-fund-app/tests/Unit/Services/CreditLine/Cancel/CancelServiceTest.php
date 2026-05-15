<?php

namespace Tests\Unit\Services\CreditLine\Cancel;

use App\Models\AccountBalance;
use App\Models\AccountCreditLineExt;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Services\CreditLine\Cancel\CancelService;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Exceptions\CancelNotAllowedException;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Tests for CancelService (UC-12).
 */
class CancelServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;
    private CancelService $cancelService;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        $calculator      = new OutstandingCalculator();
        $scheduleBuilder = new AmortizationScheduleBuilder();
        $this->drawService   = new DrawService($scheduleBuilder, $calculator);
        $this->repayService  = new RepayService($calculator);
        $this->cancelService = new CancelService();
    }

    // -----------------------------------------------------------------
    // UC-12: Cancel unused line (no draw)
    // -----------------------------------------------------------------

    public function test_cancel_line_with_no_draws_succeeds(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 12, 'monthly');

        // Manually reset to simulate no-draw scenario — override the BOR that open() created.
        // In practice, an "open with zero draw" would not exist, but cancel should also allow
        // lines that were drawn and then fully repaid. We test the "fully repaid" path below
        // and test the "no BOR transaction" path here by directly removing the transaction.
        TransactionExt::where('account_credit_line_id', $line->id)
            ->where('type', TransactionExt::TYPE_BORROW)
            ->delete();
        $line->outstanding_shares = 0;
        $line->save();

        $this->cancelService->cancel($line);

        $line->refresh();
        $this->assertEquals(AccountCreditLineExt::STATUS_CANCELLED, $line->status);
    }

    public function test_cancel_fully_repaid_line_succeeds(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');
        $this->repayService->repay($line, 50.0);

        $line->refresh();

        $this->cancelService->cancel($line);

        $line->refresh();
        $this->assertEquals(AccountCreditLineExt::STATUS_CANCELLED, $line->status);
    }

    // -----------------------------------------------------------------
    // UC-12 guard: Cancel blocked when outstanding > 0
    // -----------------------------------------------------------------

    public function test_cancel_with_outstanding_throws_exception(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 12, 'monthly');

        $this->expectException(CancelNotAllowedException::class);

        $this->cancelService->cancel($line);
    }

    public function test_cancel_exception_contains_outstanding_amount(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 12, 'monthly');

        try {
            $this->cancelService->cancel($line);
            $this->fail('Expected CancelNotAllowedException');
        } catch (CancelNotAllowedException $e) {
            $this->assertEquals(50.0, $e->getOutstandingShares());
        }
    }

    public function test_cancel_partial_repayment_still_throws(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwnBalance($account, 100.0);

        $line = $this->drawService->open($account, 50.0, 3, 'monthly');
        $this->repayService->repay($line, 20.0);

        $this->expectException(CancelNotAllowedException::class);

        $this->cancelService->cancel($line);
    }

    // -----------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------

    private function seedOwnBalance($account, float $shares): void
    {
        $tran = $this->factory->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            Carbon::today()->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => Carbon::today()->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }
}
