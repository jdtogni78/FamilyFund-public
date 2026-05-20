<?php

namespace Tests\Unit\Services\CreditLine\Matching;

use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountBalance;
use App\Models\CreditLinePayment;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Repositories\AccountCreditLineRepository;
use App\Services\CreditLine\Matching\CreditLineMatcher;
use App\Services\CreditLine\Matching\MatchResult;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Unit tests for CreditLineMatcher (UC-25 through UC-30).
 */
class CreditLineMatcherTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;
    private CreditLineMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new DataFactory();
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();

        // Seed CASH asset (required by fund share-value calculations)
        $this->factory->cash = \App\Models\AssetExt::getCashAsset();

        $this->matcher = new CreditLineMatcher(app(AccountCreditLineRepository::class));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Create an active loan share on the user account.
     *
     * @param  float  $outstanding  outstanding_shares value
     * @param  float  $principal    principal_shares value (defaults to outstanding)
     * @return AccountCreditLineExt
     */
    private function makeActiveLine(float $outstanding, float $principal = 0.0): AccountCreditLineExt
    {
        if ($principal <= 0.0) {
            $principal = $outstanding;
        }
        $line = AccountCreditLine::factory()->create([
            'account_id'        => $this->factory->userAccount->id,
            'status'            => AccountCreditLineExt::STATUS_ACTIVE,
            'outstanding_shares' => $outstanding,
            'principal_shares'  => $principal,
            'term_months'       => 12,
            'payment_frequency' => AccountCreditLineExt::FREQUENCY_MONTHLY,
            'origination_date'  => '2026-01-01',
            'maturity_date'     => '2027-01-01',
        ]);
        return $line;
    }

    /**
     * Create a scheduled payment row for a line.
     *
     * @param  AccountCreditLine $line
     * @param  float             $sharesDue
     * @return CreditLinePayment
     */
    private function makeScheduledPayment(AccountCreditLine $line, float $sharesDue): CreditLinePayment
    {
        return CreditLinePayment::factory()->create([
            'account_credit_line_id' => $line->id,
            'due_date'               => Carbon::now()->addMonth(),
            'shares_due'             => $sharesDue,
            'status'                 => CreditLinePayment::STATUS_SCHEDULED,
        ]);
    }

    /**
     * Create a REP transaction with the given shares on the user account.
     *
     * @param  float      $shares
     * @param  mixed|null $timestamp
     * @return TransactionExt
     */
    private function makeRepTran(float $shares, $timestamp = null): TransactionExt
    {
        $timestamp = $timestamp ?? '2026-05-01';
        /** @var TransactionExt $tran */
        $tran = Transaction::factory()->create([
            'account_id' => $this->factory->userAccount->id,
            'type'       => TransactionExt::TYPE_REPAY,
            'status'     => TransactionExt::STATUS_PENDING,
            'shares'     => $shares,
            'value'      => $shares * 1.0, // simple 1:1 proxy
            'timestamp'  => $timestamp,
        ]);
        return $tran;
    }

    // ── UC-25: Single active line → auto-matched regardless of shares ─────────

    public function test_uc25_single_active_line_auto_matched(): void
    {
        $line = $this->makeActiveLine(10.0);
        $this->makeScheduledPayment($line, 1.0); // shares_due = 1.0

        $tran = $this->makeRepTran(99.0); // arbitrary shares, doesn't matter

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $result->status);
        $this->assertEquals($line->id, $result->creditLineId);
        $this->assertCount(1, $result->candidateLineIds);
    }

    // ── UC-26: 2 active lines, REP shares matches exactly one line's shares_due

    public function test_uc26_shares_match_exactly_one_line(): void
    {
        $line1 = $this->makeActiveLine(10.0);
        $this->makeScheduledPayment($line1, 2.5000);

        $line2 = $this->makeActiveLine(20.0);
        $this->makeScheduledPayment($line2, 3.3333);

        $tran = $this->makeRepTran(2.5000);

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $result->status);
        $this->assertEquals($line1->id, $result->creditLineId);
    }

    // ── UC-26 variant: 2 active lines, REP shares matches both → ambiguous ────

    public function test_uc29_shares_match_both_lines_is_ambiguous(): void
    {
        $line1 = $this->makeActiveLine(10.0);
        $this->makeScheduledPayment($line1, 5.0000);

        $line2 = $this->makeActiveLine(20.0);
        $this->makeScheduledPayment($line2, 5.0000);

        $tran = $this->makeRepTran(5.0000);

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_AMBIGUOUS, $result->status);
        $this->assertNull($result->creditLineId);
        $this->assertCount(2, $result->candidateLineIds);
        $this->assertContains($line1->id, $result->candidateLineIds);
        $this->assertContains($line2->id, $result->candidateLineIds);
    }

    // ── UC-30: 2 active lines, REP shares matches neither → unmatched ─────────

    public function test_uc30_no_match_is_unmatched(): void
    {
        $line1 = $this->makeActiveLine(10.0);
        $this->makeScheduledPayment($line1, 2.0000);

        $line2 = $this->makeActiveLine(20.0);
        $this->makeScheduledPayment($line2, 3.0000);

        $tran = $this->makeRepTran(99.9999); // matches nothing

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_UNMATCHED, $result->status);
        $this->assertNull($result->creditLineId);
        $this->assertEmpty($result->candidateLineIds);
    }

    // ── UC-27: Cash fallback enabled, cash value matches one line ─────────────

    public function test_uc27_cash_fallback_matches_one_line(): void
    {
        // DataFactory::createFund seeds a cash PortfolioAsset (position=1000) and a
        // 1000-share INITIAL transaction, so shareValueAsOf returns 1.0 here.
        $this->assertGreaterThan(0, $this->factory->userAccount->shareValueAsOf('2022-01-01'));
        // Use a share value we can control via the fund's portfolio value.
        // Fund was seeded at value=1000 / shares=1000 → shareValue = 1.0
        // We pick shares_due = 5.0 → expectedCash = 5.0
        // REP shares = 5.05 → cash = 5.05 → diff/expected = 1 % boundary (just at limit)
        $line1 = $this->makeActiveLine(10.0);
        $this->makeScheduledPayment($line1, 5.0000);

        $line2 = $this->makeActiveLine(20.0);
        $this->makeScheduledPayment($line2, 3.0000);

        // REP with shares = 5.0 (exact cash match to line1 @ shareValue 1.0)
        $tran = $this->makeRepTran(5.0000, '2022-01-01');

        // With cash fallback OFF (default) — no match because shares_due differ
        // We feed 5.0 which is also a shares match but only line1 → auto_matched anyway
        // Let's use a shares value that doesn't match shares_due
        $tran2 = $this->makeRepTran(5.0050, '2022-01-01'); // slightly off from shares match

        // With cash fallback OFF → unmatched (shares don't match either line exactly)
        $resultOff = $this->matcher->match($tran2, false);
        $this->assertEquals(TransactionExt::MATCH_STATUS_UNMATCHED, $resultOff->status);

        // With cash fallback ON → cash = 5.0050 * 1.0 = 5.0050 vs expected 5.0000
        // diff/expected = 0.02 % which is < 1 % → matches line1
        $resultOn = $this->matcher->match($tran2, true);
        $this->assertEquals(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $resultOn->status);
        $this->assertEquals($line1->id, $resultOn->creditLineId);
    }

    // ── UC-28: Outstanding match — full payoff scenario ───────────────────────

    public function test_uc28_outstanding_match_full_payoff(): void
    {
        $line1 = $this->makeActiveLine(15.0); // outstanding = 15
        $this->makeScheduledPayment($line1, 5.0000);

        $line2 = $this->makeActiveLine(20.0); // outstanding = 20
        $this->makeScheduledPayment($line2, 5.0000);

        // REP shares = 15 (= line1.outstanding_shares) → full payoff of line1
        // Also doesn't match either line's shares_due (5.0)
        $tran = $this->makeRepTran(15.0000);

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $result->status);
        $this->assertEquals($line1->id, $result->creditLineId);
    }

    // ── Guard: non-REP transaction → no-op ───────────────────────────────────

    public function test_non_rep_transaction_returns_noop(): void
    {
        $line = $this->makeActiveLine(10.0);

        $borTran = Transaction::factory()->create([
            'account_id' => $this->factory->userAccount->id,
            'type'       => TransactionExt::TYPE_BORROW,
            'status'     => TransactionExt::STATUS_PENDING,
            'shares'     => 10.0,
            'value'      => 10.0,
            'timestamp'  => '2026-05-01',
        ]);

        $result = $this->matcher->match($borTran);

        // Should be a no-op, not unmatched or ambiguous
        $this->assertEquals(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $result->status);
    }

    // ── Guard: already-assigned FK → no-op ───────────────────────────────────

    public function test_already_assigned_transaction_returns_noop(): void
    {
        $line = $this->makeActiveLine(10.0);

        $tran = Transaction::factory()->create([
            'account_id'           => $this->factory->userAccount->id,
            'type'                 => TransactionExt::TYPE_REPAY,
            'status'               => TransactionExt::STATUS_PENDING,
            'shares'               => 99.0,
            'value'                => 99.0,
            'timestamp'            => '2026-05-01',
            'account_credit_line_id' => $line->id,
        ]);

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_AUTO_MATCHED, $result->status);
        $this->assertEquals($line->id, $result->creditLineId);
    }

    // ── No active lines on account → unmatched ───────────────────────────────

    public function test_no_active_lines_is_unmatched(): void
    {
        // No lines created → unmatched
        $tran = $this->makeRepTran(5.0);

        $result = $this->matcher->match($tran);

        $this->assertEquals(TransactionExt::MATCH_STATUS_UNMATCHED, $result->status);
    }
}
