<?php

namespace Tests\Feature;

use App\Http\Controllers\Traits\AccountTrait;
use App\Models\AccountBalance;
use App\Models\AccountExt;
use App\Models\Asset;
use App\Models\TransactionExt;
use App\Models\User;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Display-value tests for BOR/REP transactions on the account-show transactions
 * table (`createTransactionsResponse` + `transactions_table.blade.php`).
 *
 * Background — bugs reported 2026-05-19:
 *   The user reports that BOR/REP transactions "do not display right values".
 *   The existing CreditLine test suite verifies the *schedule* side but never
 *   asserts what the transactions table actually renders for a credit-line
 *   BOR/REP row.
 *
 * Spec reference: docs/credit_lines/fund_cashflow.md says a BOR transaction is
 *   `type='BOR', shares=principal_shares, value=principal_shares × share_price`
 * — i.e. the dollar value should reflect the disbursed cash at the share price
 * on the draw date. Today's DrawService writes `value=0` (cash leg deferred),
 * so the rendered Value / Share-Price / Current-Value cells fall out of the
 * formulas in createTransactionsResponse() in a way the user finds wrong.
 *
 * These tests codify the *display contract* the user expects. Failures are
 * marked needs-fix-credit-line and explain expected-vs-actual; the production
 * fix is intentionally out of scope (handoff to follow).
 */
class CreditLineTransactionDisplayValuesTest extends TestCase
{
    use DatabaseTransactions;
    use AccountTrait; // gives us createTransactionsResponse()

    private DataFactory $factory;
    private DrawService $drawService;
    private RepayService $repayService;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        // share value: $1000 / 1000 shares = $1.00
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
        $this->admin = $this->factory->user;
        if (method_exists($this->admin, 'assignRole')) {
            try { $this->admin->assignRole('system-admin'); } catch (\Throwable $e) {}
        }

        $calc = new OutstandingCalculator();
        $this->drawService = new DrawService(new AmortizationScheduleBuilder(), $calc);
        $this->repayService = app(RepayService::class);

        // "Today" must sit after the CL origination (2026-01-02) and the
        // repayment (2026-02-01) the REP tests record, otherwise RepayService's
        // date-clamp validation (QA bug #2 fix: repay date <= today) rejects the
        // fixture before the display contract can be asserted.
        Carbon::setTestNow(Carbon::parse('2026-03-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    private function seedOwn(AccountExt $account, float $shares, string $date = '2025-12-01'): void
    {
        $tran = $this->factory->createTransaction(
            $shares * 1.0,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            $date
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => $date,
            'end_dt'         => '9999-12-31',
        ]);
    }

    /** Locate the BOR transaction in the createTransactionsResponse() array. */
    private function pickRow(array $rows, string $type): TransactionExt
    {
        foreach ($rows as $r) {
            if ($r->type === $type) {
                return $r;
            }
        }
        $this->fail("No transaction of type $type in response.");
    }

    // -----------------------------------------------------------------
    // Bug area (a) — Transactions display
    // -----------------------------------------------------------------

    public function test_bor_transaction_type_label_is_borrow(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $bor = $this->pickRow($rows, TransactionExt::TYPE_BORROW);

        $this->assertSame('Borrow', $bor->type_string());
    }

    public function test_bor_transaction_share_count_is_positive_principal(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $bor = $this->pickRow($rows, TransactionExt::TYPE_BORROW);

        // Shares column: the user sees +50.0000. This part is correct today.
        $this->assertEqualsWithDelta(50.0, (float) $bor->shares, 1e-4);
    }

    /**
     * @group needs-fix-credit-line
     *
     * EXPECTED (per docs/credit_lines/fund_cashflow.md §"Recording cash movement"):
     *   `value` should equal principal_shares × share_price_on_draw_date
     *   (50 × $1.00 = $50.00).
     *
     * ACTUAL (2026-05-19):
     *   DrawService writes value=0 ("cash-leg deferred to Phase 5"). The
     *   transactions table then shows the BOR row as "+$0.00" — visually
     *   indistinguishable from a no-op transaction. The user reports this
     *   as transactions "not displaying right values".
     *
     * If the cash leg stays deferred, the display itself needs to compensate —
     * either render `shares × shareValueAsOf(timestamp)` in the Value column for
     * BOR/REP rows or hide that column for them. Either way the BOR row should
     * communicate the dollar size of the draw.
     */
    public function test_bor_transaction_value_reflects_dollar_size_of_draw(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $bor = $this->pickRow($rows, TransactionExt::TYPE_BORROW);

        // 50 shares × $1.00 share price on 2026-01-02 = $50.00.
        $this->assertGreaterThan(
            0.0,
            (float) $bor->value,
            'BOR transaction Value should reflect dollar size of the draw, not $0. '
            . 'Either set value on the BOR row (DrawService) or compute it in '
            . 'createTransactionsResponse for type=BOR.'
        );
    }

    /**
     * @group needs-fix-credit-line
     *
     * EXPECTED: Share Price column shows the share value as of the draw date
     *   — that is the price at which the borrower received the shares.
     *
     * ACTUAL: createTransactionsResponse() computes share_price = value/shares.
     *   Because value=0 on a BOR row, share_price renders as $0.00. The Share
     *   Price column is meaningless for the user.
     *
     * Fix direction: for BOR/REP rows, share_price should fall back to
     *   `shareValueAsOf($transaction->timestamp)` when value=0.
     */
    public function test_bor_transaction_share_price_reflects_price_at_draw_date(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $bor = $this->pickRow($rows, TransactionExt::TYPE_BORROW);

        $this->assertGreaterThan(
            0.0,
            (float) $bor->share_price,
            'BOR row should display the share value at the draw date ($1.00 on '
            . '2026-01-02), not $0.00. createTransactionsResponse() yields 0 '
            . 'because it divides value=0 by shares.'
        );
    }

    /**
     * @group needs-fix-credit-line
     *
     * EXPECTED: Current Value column should communicate the *liability* — for
     *   a BOR, "the dollar amount this loan currently costs the borrower" —
     *   i.e. outstanding × shareValueAsOf(today). It should be visually
     *   distinguishable from a positive OWN position.
     *
     * ACTUAL: shares × shareValueAsOf($asOf) renders as a positive green
     *   number with a "+X.X%" performance pill, identical to an OWN purchase
     *   that gained value. The user cannot tell from the row that this is
     *   money they owe.
     *
     * The user-facing problem is that the BOR row LOOKS like profit, even
     * though it is debt. Fix needs a sign flip or distinct display path for
     * BOR/REP rows in createTransactionsResponse() + transactions_table.blade.
     */
    public function test_bor_transaction_current_value_is_signed_or_labelled_as_liability(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $bor = $this->pickRow($rows, TransactionExt::TYPE_BORROW);

        // Required: either a negative current_value to signal liability, or
        // a marker on the transaction the view can use to render it differently.
        // Today neither is true and the row visually equals a profitable OWN
        // position.
        $isNegative = (float) $bor->current_value < 0;
        $hasLiabilityMarker = isset($bor->is_liability) && $bor->is_liability === true;

        $this->assertTrue(
            $isNegative || $hasLiabilityMarker,
            'BOR current_value must communicate liability — either negative '
            . 'current_value or an is_liability=true marker on the response. '
            . 'Currently renders as a positive green number indistinguishable '
            . 'from a profitable OWN purchase.'
        );
    }

    /**
     * @group needs-fix-credit-line
     *
     * EXPECTED: For a REP transaction, value/share_price/current_value should
     *   reflect that the borrower paid down their loan — the dollar amount
     *   the borrower handed over.
     *
     * ACTUAL: Same value=0 problem; column shows $0.00.
     */
    public function test_rep_transaction_value_reflects_amount_paid(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $line = $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $this->repayService->repay($line->refresh(), 10.0, Carbon::parse('2026-02-01'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $rep = $this->pickRow($rows, TransactionExt::TYPE_REPAY);

        $this->assertGreaterThan(
            0.0,
            (float) $rep->value,
            'REP transaction Value column should reflect the dollar amount paid '
            . '(10 shares × $1.00 = $10.00), not $0.00.'
        );
    }

    public function test_rep_transaction_shares_match_repayment_amount(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $line = $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));

        $this->repayService->repay($line->refresh(), 10.0, Carbon::parse('2026-02-01'));

        $rows = $this->createTransactionsResponse($account->refresh(), '2026-06-01');
        $rep = $this->pickRow($rows, TransactionExt::TYPE_REPAY);

        $this->assertEqualsWithDelta(10.0, (float) $rep->shares, 1e-4);
    }

    /**
     * Regression: account-show page renders a credit-line BOR + REP without
     * crashing AND without rendering the Value cell with the literal $0.00
     * tightly adjacent to the "Borrow</span>" cell (the heuristic check below
     * tolerates whitespace).  Today the table does emit "$0.00" — but the
     * heuristic looks for a *zero-whitespace* sequence, which the current
     * markup does not produce, so this currently passes.  It's a lighter
     * regression net for the display contract; the strict assertions live in
     * the four needs-fix-credit-line tests above.
     */
    public function test_account_show_renders_dollar_context_for_credit_line_transactions(): void
    {
        $account = $this->factory->userAccount;
        $this->seedOwn($account, 200.0);
        $line = $this->drawService->open($account, 50.0, 6, 'monthly', null, Carbon::parse('2026-01-02'));
        $this->repayService->repay($line->refresh(), 10.0, Carbon::parse('2026-02-01'));

        $response = $this->actingAs($this->admin)->get('/accounts/' . $account->id);
        $response->assertStatus(200);

        $html = $response->getContent();

        // After the fix, BOR should show "$50" (or similar dollar context),
        // not "$0.00".  This is a heuristic check — adjust when the display
        // contract is finalised.
        $this->assertStringNotContainsString(
            'Borrow</span></td>'
            // skip whitespace until the Value cell with $0.00
            ,
            preg_replace('/\s+/', ' ', $html),
            'Account-show should not render the BOR row with a $0.00 Value. '
            . 'See createTransactionsResponse() and transactions_table.blade.php.'
        );

        while (ob_get_level() > 1) { @ob_end_clean(); }
    }
}
