<?php

namespace Tests\Feature\Scenarios;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * S1 — Multiple draws, one paid off, no interference.
 *
 * One beneficiary opens two credit lines with different terms / frequencies.
 * L1 is repaid in full (status → paid_off, contributes 0 to BOR aggregate).
 * L2 keeps a partial repayment (1 row paid, 1 row scheduled).
 *
 * The invariants under test:
 *  - paid_off lines do NOT contribute to account_balances.BOR (only active do)
 *  - per-line outstanding is reconstructed from BOR/REP transactions scoped
 *    to that line's account_credit_line_id
 *  - sharesAsOf = OWN − BOR aggregate (== OWN − L2.outstanding once L1 paid)
 *
 * No matching/PUR/SAL noise — those interactions live in S3.
 */
class S1MultiLineOnePaidOffTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $s;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->s = CreditLineScenarioBuilder::make();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    public function test_two_lines_one_paid_off_one_partial(): void
    {
        $own = 1000.0;
        $this->s->withBorrowingPower('bA', $own);

        // L1 — 100 shares over 12 months, monthly → 12 rows of ~8.3333
        $l1 = $this->s->openLine('bA', 100, 12, 'monthly', Carbon::today()->subMonths(2), 'S1: L1 monthly');
        // L2 — 60 shares over 6 months, quarterly → 2 rows of 30
        $l2 = $this->s->openLine('bA', 60,  6,  'quarterly', Carbon::today()->subMonths(2), 'S1: L2 quarterly');

        $this->assertSame(12, $this->s->rows($l1)->count(), 'L1 monthly/12mo → 12 rows');
        $this->assertSame(2,  $this->s->rows($l2)->count(), 'L2 quarterly/6mo → 2 rows');

        // Repay L1 in full across three chunks (proves multi-REP allocation
        // doesn't leak across lines).
        $this->s->repay($l1, 30, Carbon::today()->subMonths(1));
        $this->s->repay($l1, 30, Carbon::today()->subDays(20));
        $this->s->repay($l1, 40, Carbon::today()->subDays(10));

        // Repay exactly one L2 row (the first), leaving the second scheduled.
        $this->s->repayRow($l2, 0, 30.0, Carbon::today()->subDays(5));

        $l1->refresh();
        $l2->refresh();

        // ── L1 fully paid off
        $this->assertSame(AccountCreditLineExt::STATUS_PAID_OFF, $l1->status);
        $this->assertEqualsWithDelta(0.0, (float) $l1->outstanding_shares, 0.0001);
        $this->assertSame(
            12,
            CreditLinePayment::where('account_credit_line_id', $l1->id)
                ->where('status', CreditLinePayment::STATUS_PAID)->count(),
            'every L1 row should be paid after full repayment'
        );

        // ── L2 still active with one paid row + one scheduled row
        $this->assertSame(AccountCreditLineExt::STATUS_ACTIVE, $l2->status);
        $this->assertEqualsWithDelta(30.0, (float) $l2->outstanding_shares, 0.0001);
        $l2Rows = $this->s->rows($l2);
        $this->assertSame(CreditLinePayment::STATUS_PAID,      $l2Rows->get(0)->status);
        $this->assertSame(CreditLinePayment::STATUS_SCHEDULED, $l2Rows->get(1)->status);

        // ── Aggregate BOR row equals Σ outstanding across ACTIVE lines = 30.0
        $account = $this->s->account('bA');
        $borOpen = AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->where('end_dt', '9999-12-31')
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($borOpen);
        $this->assertEqualsWithDelta(30.0, (float) $borOpen->shares, 0.0001);

        // ── Net sharesAsOf = OWN − 30 (paid-off L1 contributes nothing)
        $this->assertEqualsWithDelta(
            $own - 30.0,
            $this->s->sharesAsOf('bA'),
            0.0001,
            'sharesAsOf must net out exactly L2.outstanding'
        );
    }

    public function test_paid_off_line_is_visible_on_credit_line_show_page(): void
    {
        $this->s->withBorrowingPower('bA', 200);
        $l1 = $this->s->openLine('bA', 50, 6, 'monthly', null, 'S1: full repay');
        $this->s->repay($l1, 50);
        $l1->refresh();

        $this->assertSame(AccountCreditLineExt::STATUS_PAID_OFF, $l1->status);

        $this->actingAs($this->s->admin)
            ->get(route('credit_lines.show', ['line' => $l1->id]))
            ->assertOk()
            ->assertSee('Credit Line #' . $l1->id);
    }

    public function test_account_index_renders_with_paid_off_and_active_lines(): void
    {
        $this->s->withBorrowingPower('bA', 500);
        $paid = $this->s->openLine('bA', 30, 3, 'monthly', null, 'S1: paid');
        $this->s->repay($paid, 30);
        $active = $this->s->openLine('bA', 60, 6, 'monthly', null, 'S1: still active');

        $this->assertSame(AccountCreditLineExt::STATUS_PAID_OFF, $paid->refresh()->status);
        $this->assertSame(AccountCreditLineExt::STATUS_ACTIVE,   $active->refresh()->status);

        $account = $this->s->account('bA');

        // The index page returns OK and mentions both lines by primary key —
        // matches what the table renders (the descr column on the index view
        // is truncated and HTML-escaped, so we don't pattern-match on it).
        $this->actingAs($this->s->admin)
            ->get(route('credit_lines.index', ['account' => $account->id]))
            ->assertOk()
            ->assertSee((string) $paid->id)
            ->assertSee((string) $active->id);

        $this->assertSame(
            2,
            AccountCreditLine::where('account_id', $account->id)->count(),
            'both lines must persist regardless of status'
        );
    }
}
