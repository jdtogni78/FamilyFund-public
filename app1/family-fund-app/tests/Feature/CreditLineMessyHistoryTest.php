<?php

namespace Tests\Feature;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\CreditLineAdjustment;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Fixtures\CreditLineScenarioBuilder;
use Tests\TestCase;

/**
 * HTTP-level negative cases against deliberately messy credit-line histories.
 *
 * Where CreditLineFlowTest walks the happy path, this suite uses
 * {@see CreditLineScenarioBuilder} to manufacture tangled state — paid-off,
 * cancelled, partially-paid, backdated and reversed lines on one account —
 * and then drives the real web endpoints to prove the guards hold:
 * over-borrow, backdate-before-history, repay/register on a closed line or a
 * settled row, cancel with outstanding principal, the admin gate, and the
 * backdated late-backlog warning.
 */
class CreditLineMessyHistoryTest extends TestCase
{
    use DatabaseTransactions;

    private CreditLineScenarioBuilder $scenario;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->scenario = CreditLineScenarioBuilder::make();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }
        parent::tearDown();
    }

    /**
     * A draw that exceeds available-to-borrow (because existing lines already
     * consume the OWN balance) must be rejected with a flash error and create
     * no new line.
     */
    public function test_over_borrow_through_http_is_rejected_and_creates_no_line(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $this->scenario->messyAccount('main');           // consumes borrowing power
        $account = $this->scenario->account('main');

        $before = AccountCreditLine::where('account_id', $account->id)->count();

        $response = $this->actingAs($this->scenario->admin)
            ->from(route('credit_lines.create', ['account' => $account->id]))
            ->post(route('credit_lines.store', ['account' => $account->id]), [
                'account_id'        => $account->id,
                'nickname'          => 'Over-borrow attempt',
                'principal_shares'  => 9999,             // far beyond anything available
                'term_months'       => 12,
                'payment_frequency' => 'monthly',
                'descr'             => 'over-borrow attempt',
            ]);

        // The draw cap is enforced at validation time (CreateAccountCreditLineRequest
        // ::withValidator), so the web path returns a field error on
        // principal_shares — the controller's OverBorrowException flash is only
        // a race-condition fallback.
        $response->assertRedirect();
        $response->assertSessionHasErrors('principal_shares');
        $this->assertSame(
            $before,
            AccountCreditLine::where('account_id', $account->id)->count(),
            'over-borrow must not persist a new credit line'
        );
    }

    /**
     * Backdating an origination before already-recorded borrow activity is
     * honoured by splicing the new draw into the BOR balance chain — a closed
     * historical row covers the backdated period, and the existing open row's
     * shares are bumped to the new aggregate.
     */
    public function test_backdating_before_existing_borrow_history_splices_chain(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        // An existing line whose BOR row starts *today*.
        $this->scenario->openLine('main', 40, 12, 'monthly', Carbon::today());
        $account = $this->scenario->account('main');

        $before = AccountCreditLine::where('account_id', $account->id)->count();

        $response = $this->actingAs($this->scenario->admin)
            ->from(route('credit_lines.create', ['account' => $account->id]))
            ->post(route('credit_lines.store', ['account' => $account->id]), [
                'account_id'        => $account->id,
                'nickname'          => 'Backdate before history',
                'principal_shares'  => 10,
                'term_months'       => 6,
                'payment_frequency' => 'monthly',
                'origination_date'  => Carbon::yesterday()->toDateString(),
                'descr'             => 'backdate before history',
            ]);

        $response->assertRedirect();
        $this->assertSame(
            $before + 1,
            AccountCreditLine::where('account_id', $account->id)->count(),
            'the backdated draw should persist a new credit line'
        );

        $borRows = \App\Models\AccountBalance::where('account_id', $account->id)
            ->where('type', 'BOR')
            ->orderBy('start_dt', 'asc')
            ->get();

        // Exactly two BOR rows: a closed historical splice + the open row.
        $this->assertCount(2, $borRows, 'splice should yield one closed historical row plus the open row');

        $historical = $borRows[0];
        $open       = $borRows[1];

        $this->assertEquals(Carbon::yesterday()->toDateString(), $historical->start_dt->toDateString());
        $this->assertEquals(Carbon::today()->toDateString(),     $historical->end_dt->toDateString());
        $this->assertEquals(10.0, round((float) $historical->shares, 4));

        $this->assertEquals(Carbon::today()->toDateString(), $open->start_dt->toDateString());
        $this->assertEquals('9999-12-31',                    $open->end_dt->toDateString());
        $this->assertEquals(50.0, round((float) $open->shares, 4));
    }

    /** Repaying a paid-off line is rejected; no REP transaction is added. */
    public function test_repay_on_paid_off_line_is_rejected(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $lines = $this->scenario->messyAccount('main');
        $paidOff = $lines['paid_off'];
        $this->assertSame(AccountCreditLineExt::STATUS_PAID_OFF, $paidOff->status);

        $repsBefore = TransactionExt::where('account_credit_line_id', $paidOff->id)
            ->where('type', TransactionExt::TYPE_REPAY)->count();

        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.repay', ['line' => $paidOff->id]), [
                'account_credit_line_id' => $paidOff->id,
                'shares'                 => 5,
            ]);

        $response->assertRedirect(route('credit_lines.show', ['line' => $paidOff->id]));
        $response->assertSessionHas('flash_notification');

        $paidOff->refresh();
        $this->assertSame(AccountCreditLineExt::STATUS_PAID_OFF, $paidOff->status);
        $this->assertEquals(0.0, round((float) $paidOff->outstanding_shares, 4));
        $this->assertSame(
            $repsBefore,
            TransactionExt::where('account_credit_line_id', $paidOff->id)
                ->where('type', TransactionExt::TYPE_REPAY)->count(),
            'a rejected repay must not append a REP transaction'
        );
    }

    /** Registering a payment on an already-paid schedule row is rejected. */
    public function test_register_payment_on_settled_row_is_rejected(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $lines = $this->scenario->messyAccount('main');
        $active = $lines['active'];

        $paidRow = CreditLinePayment::where('account_credit_line_id', $active->id)
            ->where('status', CreditLinePayment::STATUS_PAID)
            ->orderBy('due_date')
            ->firstOrFail();

        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.payments.register', [
                'line'    => $active->id,
                'payment' => $paidRow->id,
            ]), [
                'shares' => (float) $paidRow->shares_due,
            ]);

        $response->assertRedirect(route('credit_lines.show', ['line' => $active->id]));
        $response->assertSessionHas('flash_notification');
        $this->assertSame(
            CreditLinePayment::STATUS_PAID,
            $paidRow->fresh()->status,
            'a settled row must not accept another registration'
        );
    }

    /** Cancelling a line that still carries outstanding principal is rejected. */
    public function test_cancel_with_outstanding_principal_is_rejected(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $lines = $this->scenario->messyAccount('main');
        $partial = $lines['partial'];
        $this->assertGreaterThan(0.0, (float) $partial->outstanding_shares);

        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.cancel', ['line' => $partial->id]), [
                'account_credit_line_id' => $partial->id,
            ]);

        $response->assertRedirect(route('credit_lines.show', ['line' => $partial->id]));
        $response->assertSessionHas('flash_notification');
        $this->assertSame(
            AccountCreditLineExt::STATUS_ACTIVE,
            $partial->fresh()->status,
            'a line with outstanding principal must stay active'
        );
    }

    /** A non-admin authenticated user cannot reach credit-line endpoints. */
    public function test_non_admin_cannot_view_credit_lines(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $lines = $this->scenario->messyAccount('main');
        $active = $lines['active'];
        $account = $this->scenario->account('main');

        $intruder = $this->scenario->nonAdminUser();

        $this->actingAs($intruder)
            ->get(route('credit_lines.show', ['line' => $active->id]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->get(route('credit_lines.index', ['account' => $account->id]))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->get(route('credit_lines.global_index'))
            ->assertForbidden();
    }

    /**
     * A backdated draw via the web form succeeds but warns the operator that
     * past-due rows exist and were flagged late (so they can reconcile them).
     */
    public function test_backdated_draw_surfaces_late_backlog_warning(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $account = $this->scenario->account('main');

        $response = $this->actingAs($this->scenario->admin)
            ->post(route('credit_lines.store', ['account' => $account->id]), [
                'account_id'        => $account->id,
                'nickname'          => 'Backdated with backlog',
                'principal_shares'  => 120,
                'term_months'       => 12,
                'payment_frequency' => 'monthly',
                'origination_date'  => Carbon::today()->subMonths(6)->toDateString(),
                'descr'             => 'backdated with backlog',
            ]);

        $line = AccountCreditLine::where('account_id', $account->id)->latest('id')->first();
        $this->assertNotNull($line);
        $response->assertRedirect(route('credit_lines.show', ['line' => $line->id]));
        $response->assertSessionHas('flash_notification');

        $lateCount = CreditLinePayment::where('account_credit_line_id', $line->id)
            ->where('status', CreditLinePayment::STATUS_LATE)
            ->count();
        $this->assertGreaterThan(0, $lateCount, 'a 6-month backdated draw must flag past-due rows late');

        $this->actingAs($this->scenario->admin)
            ->get(route('credit_lines.show', ['line' => $line->id]))
            ->assertOk();
    }

    /**
     * The readjusted line in a messy history must keep its paid / late rows,
     * cancel only the still-scheduled rows, carry a CreditLineAdjustment
     * audit row, and still render on the web show page.
     */
    public function test_readjusted_plan_preserves_paid_and_late_rows_with_audit(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $lines = $this->scenario->messyAccount('main');
        $adjusted = $lines['adjusted'];

        // Term + frequency were changed by messyAccount's readjust.
        $this->assertSame(24, (int) $adjusted->term_months);
        $this->assertSame('quarterly', $adjusted->payment_frequency);

        // Audit trail exists.
        $this->assertDatabaseHas('credit_line_adjustments', [
            'account_credit_line_id' => $adjusted->id,
            'old_payment_frequency'  => 'monthly',
            'new_payment_frequency'  => 'quarterly',
        ]);

        $rows = CreditLinePayment::where('account_credit_line_id', $adjusted->id)->get();
        // Pre-readjust paid + late rows are preserved.
        $this->assertGreaterThan(0, $rows->where('status', CreditLinePayment::STATUS_PAID)->count());
        $this->assertGreaterThan(0, $rows->where('status', CreditLinePayment::STATUS_LATE)->count());
        // Old scheduled rows were cancelled (not deleted) and a fresh
        // post-readjust schedule was generated.
        $this->assertGreaterThan(0, $rows->where('status', CreditLinePayment::STATUS_CANCELLED)->count());
        $this->assertGreaterThan(0, $rows->where('status', CreditLinePayment::STATUS_SCHEDULED)->count());

        $this->actingAs($this->scenario->admin)
            ->get(route('credit_lines.show', ['line' => $adjusted->id]))
            ->assertOk()
            ->assertSee('View schedule at this point', false);
    }

    /**
     * A no-op readjust (neither term nor frequency actually changes) on a
     * line that already lives in a messy history must be rejected and write
     * no new audit row.
     */
    public function test_no_change_readjust_is_rejected(): void
    {
        $this->scenario->withBorrowingPower('main', 500);
        $lines = $this->scenario->messyAccount('main');
        $adjusted = $lines['adjusted'];   // already term=24, frequency=quarterly

        $auditBefore = CreditLineAdjustment::where('account_credit_line_id', $adjusted->id)->count();

        $response = $this->actingAs($this->scenario->admin)
            ->from(route('credit_lines.show', ['line' => $adjusted->id]))
            ->post(route('credit_lines.readjust', ['line' => $adjusted->id]), [
                'account_credit_line_id' => $adjusted->id,
                'new_term_months'        => 24,            // unchanged
                'new_payment_frequency'  => 'quarterly',   // unchanged
                'reason'                 => 'no-op attempt',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_notification');
        $this->assertSame(
            $auditBefore,
            CreditLineAdjustment::where('account_credit_line_id', $adjusted->id)->count(),
            'a no-change readjust must not append an adjustment audit row'
        );
    }

    /**
     * available-to-borrow, served by the live create-form JSON endpoint, must
     * net out every active line's outstanding shares across a messy history —
     * not just the most recent one.
     */
    public function test_available_to_borrow_nets_messy_history_across_lines(): void
    {
        $own = 400.0;
        $this->scenario->withBorrowingPower('main', $own);
        $this->scenario->messyAccount('main');
        $account = $this->scenario->account('main');

        $response = $this->actingAs($this->scenario->admin)
            ->getJson(route('credit_lines.available_shares', ['account' => $account->id]));

        $response->assertOk();
        $available = (float) $response->json('available');

        // Independently recompute the expected cap and assert the endpoint agrees.
        $expected = round((new OutstandingCalculator())->availableToBorrow($account, Carbon::today()), 4);

        $this->assertEqualsWithDelta($expected, $available, 0.0001);
        $this->assertGreaterThanOrEqual(0.0, $available);
        $this->assertLessThan(
            $own,
            $available,
            'active lines from the messy history must reduce available-to-borrow'
        );
    }
}
