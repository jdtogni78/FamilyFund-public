<?php

namespace Tests\Feature\Scenarios;

use App\Models\FundExt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S12 — Cross-fund isolation.
 *
 * Two funds, two beneficiaries. A draw on a credit line in Fund A must not
 * change Fund B's NAV, share price, total minted shares, allocated bucket,
 * borrowed bucket, or any of Fund B's account-level balances.
 *
 * Defends the credit-line subsystem against accidental fund-level coupling
 * (e.g., a global aggregate that sums BOR across funds, or a portfolio
 * update path that fires for every account regardless of fund).
 */
class S12CrossFundIsolationTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $a;
    private CreditLineScenarioBuilder $b;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        // Two completely independent builders → two funds + two admin users.
        $this->a = CreditLineScenarioBuilder::make();
        $this->b = CreditLineScenarioBuilder::make();
    }

    public function test_borrowing_in_fund_a_leaves_fund_b_aum_and_buckets_intact(): void
    {
        $this->a->withBorrowingPower('alice', 500);
        $this->b->withBorrowingPower('bob',   500);

        $today  = Carbon::today()->toDateString();
        $fundA  = FundExt::find($this->a->df->fund->id);
        $fundB  = FundExt::find($this->b->df->fund->id);

        // Capture Fund B's complete pre-state.
        $b_aum         = $fundB->valueAsOf($today);
        $b_shares      = $fundB->sharesAsOf($today);
        $b_price       = $fundB->shareValueAsOf($today);
        $b_allocated   = $fundB->allocatedShares($today);
        $b_borrowed    = $fundB->borrowedShares($today);
        $b_available   = $fundB->availableUnallocatedShares($today);
        $b_aliceShares = $this->b->sharesAsOf('bob');

        // Big draw on Fund A.
        $this->a->openLine('alice', 250, 12, 'monthly', null, 'S12 isolation draw');

        // Fund B: every metric must be byte-identical.
        $this->assertEqualsWithDelta($b_aum,        $fundB->valueAsOf($today),               0.0001, 'Fund B AUM must not change');
        $this->assertEqualsWithDelta($b_shares,     $fundB->sharesAsOf($today),              0.0001, 'Fund B total minted shares must not change');
        $this->assertEqualsWithDelta($b_price,      $fundB->shareValueAsOf($today),          0.0001, 'Fund B share price must not change');
        $this->assertEqualsWithDelta($b_allocated,  $fundB->allocatedShares($today),         0.0001, 'Fund B allocated bucket must not change');
        $this->assertEqualsWithDelta($b_borrowed,   $fundB->borrowedShares($today),          0.0001, 'Fund B borrowed bucket must remain 0');
        $this->assertEqualsWithDelta($b_available,  $fundB->availableUnallocatedShares($today), 0.0001, 'Fund B available unallocated bucket must not change');
        $this->assertEqualsWithDelta(0.0,           (float) $fundB->borrowedShares($today),  0.0001, 'Fund B must not pick up a phantom borrowed balance');

        // Fund B's beneficiary balance untouched.
        $this->assertEqualsWithDelta($b_aliceShares, $this->b->sharesAsOf('bob'), 0.0001,
            'Fund B beneficiary balance must not be affected by Fund A activity');

        // Fund A: borrowed bucket reflects the draw (sanity check that the
        // setup actually exercised something).
        $this->assertEqualsWithDelta(
            250.0,
            (float) $fundA->borrowedShares($today),
            0.0001,
            'sanity: Fund A must show the 250-share borrowed balance'
        );
    }

    public function test_borrowing_in_fund_a_doesnt_create_balances_in_fund_b(): void
    {
        $this->a->withBorrowingPower('alice', 500);
        $this->b->withBorrowingPower('bob',   500);

        $bobAccountId = $this->b->account('bob')->id;
        $balancesBefore = \App\Models\AccountBalance::where('account_id', $bobAccountId)
            ->orderBy('id')->pluck('id')->all();

        $this->a->openLine('alice', 100, 12, 'monthly', null, 'S12 no leak');

        $balancesAfter = \App\Models\AccountBalance::where('account_id', $bobAccountId)
            ->orderBy('id')->pluck('id')->all();

        $this->assertSame(
            $balancesBefore,
            $balancesAfter,
            'Fund A draws must not insert any AccountBalance rows on Fund B\'s beneficiary'
        );
    }
}
