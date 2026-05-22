<?php

namespace Tests\Feature\Scenarios;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S8 — AccountExt accessor consistency.
 *
 * Locks in the four-accessor contract introduced by commit 6eaf4801:
 *
 *   sharesWithoutBorrowingAsOf   - borrowedSharesAsOf  = sharesAsOf
 *   valueWithoutBorrowingAsOf    - borrowedValueAsOf   = valueAsOf
 *
 * Walks an account through its lifecycle (initial → draw → partial repay
 * → full repay) and asserts the identities hold at each step. Catches
 * accidental drift where one accessor would start counting borrowed
 * shares in a way that breaks the gross/net/borrowed triangle.
 */
class S8AccountAccessorConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    public function test_gross_minus_borrowed_equals_net_through_lifecycle(): void
    {
        $this->s->withBorrowingPower('bH', 500);
        $today = Carbon::today()->toDateString();
        $acct  = $this->s->account('bH');

        // Stage 1 — initial.
        $this->assertConsistent($acct, $today, 'initial');

        // Stage 2 — after a draw.
        $line = $this->s->openLine('bH', 200, 12, 'monthly', null, 'S8 consistency');
        $this->assertConsistent($acct, $today, 'post-draw');
        $this->assertEqualsWithDelta(200.0, (float) $acct->borrowedSharesAsOf($today), 0.0001,
            'after a draw of 200, borrowedSharesAsOf must report 200');

        // Stage 3 — partial repay (50 of 200).
        $this->s->repay($line, 50);
        $this->assertConsistent($acct, $today, 'post-partial-repay');
        $this->assertEqualsWithDelta(150.0, (float) $acct->borrowedSharesAsOf($today), 0.0001);

        // Stage 4 — full repay.
        $this->s->repay($line, 150);
        $this->assertConsistent($acct, $today, 'post-full-repay');
        $this->assertEqualsWithDelta(0.0,   (float) $acct->borrowedSharesAsOf($today), 0.0001);

        // After full repay, gross == net and borrowed == 0.
        $this->assertEqualsWithDelta(
            (float) $acct->sharesWithoutBorrowingAsOf($today),
            (float) $acct->sharesAsOf($today),
            0.0001,
            'with zero outstanding, gross and net shares must match'
        );
    }

    public function test_value_triangle_matches_shares_triangle(): void
    {
        $this->s->withBorrowingPower('bH', 500);
        $today = Carbon::today()->toDateString();
        $acct  = $this->s->account('bH');

        $this->s->openLine('bH', 300, 12, 'monthly', null, 'S8 value');

        $shareValue = (float) $acct->shareValueAsOf($today);
        $grossSh    = (float) $acct->sharesWithoutBorrowingAsOf($today);
        $borrSh     = (float) $acct->borrowedSharesAsOf($today);
        $netSh      = (float) $acct->sharesAsOf($today);

        $grossVal = (float) $acct->valueWithoutBorrowingAsOf($today);
        $borrVal  = (float) $acct->borrowedValueAsOf($today);
        $netVal   = (float) $acct->valueAsOf($today);

        // Each value accessor must equal its share accessor × share price.
        $this->assertEqualsWithDelta($grossSh * $shareValue, $grossVal, 0.0001);
        $this->assertEqualsWithDelta($borrSh  * $shareValue, $borrVal,  0.0001);
        $this->assertEqualsWithDelta($netSh   * $shareValue, $netVal,   0.0001);

        // And the value triangle: gross − borrowed = net.
        $this->assertEqualsWithDelta($grossVal - $borrVal, $netVal, 0.0001);
    }

    private function assertConsistent($acct, string $asOf, string $stage): void
    {
        $gross = (float) $acct->sharesWithoutBorrowingAsOf($asOf);
        $borr  = (float) $acct->borrowedSharesAsOf($asOf);
        $net   = (float) $acct->sharesAsOf($asOf);
        $this->assertEqualsWithDelta(
            $gross - $borr,
            $net,
            0.0001,
            "[{$stage}] gross − borrowed must equal net"
        );
    }
}
