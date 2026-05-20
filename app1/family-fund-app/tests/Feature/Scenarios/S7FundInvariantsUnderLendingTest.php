<?php

namespace Tests\Feature\Scenarios;

use App\Models\FundExt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S7 — Fund invariants under lending.
 *
 * Locks in the contract introduced by commit 12ef9b38: outstanding share
 * loans are a slice of the unallocated pool, NOT new assets. Issuing a
 * credit-line draw must leave fund-level AUM (valueAsOf), total minted
 * shares (sharesAsOf), and share price (shareValueAsOf) unchanged. The
 * three buckets reported on the fund summary must always sum to the fund's
 * total shares: Allocated + Borrowed + Available unallocated.
 *
 * Confirms the design promise that "lending doesn't change AUM" — easy to
 * accidentally break by adding loaned shares to NAV or by widening the
 * allocated bucket to include borrowed shares.
 */
class S7FundInvariantsUnderLendingTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_aum_and_share_price_unchanged_by_borrow_and_repay(): void
    {
        $this->s->withBorrowingPower('bG', 500);
        $today = Carbon::today()->toDateString();
        $fund  = FundExt::find($this->s->df->fund->id);

        $sharesBefore = $fund->sharesAsOf($today);
        $valueBefore  = $fund->valueAsOf($today);
        $priceBefore  = $fund->shareValueAsOf($today);

        $line = $this->s->openLine('bG', 200, 12, 'monthly', null, 'S7 invariants');

        $this->assertEqualsWithDelta($sharesBefore, $fund->sharesAsOf($today), 0.0001,
            'fund total minted shares must not change on a draw');
        $this->assertEqualsWithDelta($valueBefore,  $fund->valueAsOf($today),  0.0001,
            'fund AUM (portfolio value) must not change on a draw');
        $this->assertEqualsWithDelta($priceBefore,  $fund->shareValueAsOf($today), 0.0001,
            'share price must not change on a draw');

        // Repay in full and re-assert the invariants.
        $this->s->repay($line, 200);

        $this->assertEqualsWithDelta($sharesBefore, $fund->sharesAsOf($today), 0.0001);
        $this->assertEqualsWithDelta($valueBefore,  $fund->valueAsOf($today),  0.0001);
        $this->assertEqualsWithDelta($priceBefore,  $fund->shareValueAsOf($today), 0.0001);
    }

    public function test_three_bucket_sum_reconciles_to_fund_total(): void
    {
        $this->s->withBorrowingPower('bG', 500);
        $today = Carbon::today()->toDateString();
        $fund  = FundExt::find($this->s->df->fund->id);

        // Before any draw: allocated + borrowed + available_unallocated == total.
        $this->assertReconciles($fund, $today, 'pre-draw');

        $this->s->openLine('bG', 150, 12, 'monthly', null, 'S7 bucket sum');

        // After draw: borrowed bucket grew by 150, allocated shrank by 150
        // (allocated uses net sharesAsOf), available_unallocated unchanged.
        $borrowed = $fund->borrowedShares($today);
        $this->assertEqualsWithDelta(150.0, (float) $borrowed, 0.0001);
        $this->assertReconciles($fund, $today, 'post-draw');
    }

    public function test_borrowing_shifts_allocation_from_allocated_to_borrowed_bucket(): void
    {
        $this->s->withBorrowingPower('bG', 500);
        $today = Carbon::today()->toDateString();
        $fund  = FundExt::find($this->s->df->fund->id);

        $allocatedBefore       = (float) $fund->allocatedShares($today);
        $availableUnallocBefore = (float) $fund->availableUnallocatedShares($today);

        $this->s->openLine('bG', 120, 12, 'monthly', null, 'S7 shift');

        $this->assertEqualsWithDelta(
            $allocatedBefore - 120.0,
            (float) $fund->allocatedShares($today),
            0.0001,
            'allocated should drop by the borrowed amount (allocated uses net sharesAsOf)'
        );
        $this->assertEqualsWithDelta(120.0, (float) $fund->borrowedShares($today), 0.0001);
        $this->assertEqualsWithDelta(
            $availableUnallocBefore,
            (float) $fund->availableUnallocatedShares($today),
            0.0001,
            'available_unallocated = unallocated − borrowed, so the +120 in unallocated cancels'
        );
    }

    private function assertReconciles($fund, string $asOf, string $stage): void
    {
        $allocated  = (float) $fund->allocatedShares($asOf);
        $borrowed   = (float) $fund->borrowedShares($asOf);
        $available  = (float) $fund->availableUnallocatedShares($asOf);
        $total      = (float) $fund->sharesAsOf($asOf);

        $this->assertEqualsWithDelta(
            $total,
            $allocated + $borrowed + $available,
            0.0001,
            "[{$stage}] Allocated + Borrowed + Available_unallocated must equal fund total"
        );
    }
}
