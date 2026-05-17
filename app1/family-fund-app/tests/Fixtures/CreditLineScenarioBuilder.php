<?php

namespace Tests\Fixtures;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\Asset;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Models\User;
use App\Services\CreditLine\Draw\DrawService;
use App\Services\CreditLine\Repay\RepayService;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\LateDetector;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Tests\DataFactory;

/**
 * Fluent data generator for credit-line scenarios.
 *
 * Mirrors the role of {@see TestFixtures} for the credit-line domain: it
 * boots a fund + system-admin once, then lets a test compose *messy*
 * histories (multiple lines per account, backdated draws, partial and late
 * rows, paid-off and cancelled lines, reversed transactions) with a few
 * readable calls — instead of every credit-line test re-implementing its own
 * `seedOwnBalance()` helper and hand-rolling state.
 *
 * Setup runs through the domain services (DrawService / RepayService /
 * LateDetector) so the generated state is exactly what production would
 * produce, including amortization rows, BOR/REP transactions and the
 * temporal balance ledger. Tests then exercise the HTTP layer for the
 * behaviour under test.
 *
 * Usage:
 *   $s = CreditLineScenarioBuilder::make()
 *          ->withBorrowingPower('alice', 500)
 *          ->messyAccount('alice');
 *   $this->actingAs($s->admin)->get(route('credit_lines.show', ...));
 */
class CreditLineScenarioBuilder
{
    public DataFactory $df;
    public User $admin;

    public DrawService $draw;
    public RepayService $repay;
    public LateDetector $late;

    /** @var array<string,\App\Models\AccountExt> key => account */
    private array $accounts = [];

    private function __construct() {}

    /**
     * Boot a fund, a system-admin user (whose account is keyed 'main'), and
     * the credit-line services. $fundDate is intentionally far in the past so
     * deeply backdated originations still fall inside fund history.
     */
    public static function make(string $fundDate = '2016-01-01'): self
    {
        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $b = new self();
        $b->df = new DataFactory();
        $b->df->createFund(1_000_000, 1_000_000, $fundDate);
        $b->df->createUser();
        $b->admin = $b->df->user;

        // Same role-assignment pattern as CreditLineFlowTest (team_id = 0).
        $originalTeamId = getPermissionsTeamId();
        setPermissionsTeamId(0);
        $b->admin->assignRole('system-admin');
        setPermissionsTeamId($originalTeamId);

        $b->draw  = new DrawService(new AmortizationScheduleBuilder(), new OutstandingCalculator());
        $b->repay = new RepayService(new OutstandingCalculator());
        $b->late  = new LateDetector();

        // The admin's own account is the default subject account.
        $b->accounts['main'] = $b->df->userAccount;

        return $b;
    }

    /** A second, non-admin authenticated user (for ACL / gate negative cases). */
    public function nonAdminUser(): User
    {
        $this->df->createUser();
        return $this->df->user;
    }

    public function account(string $key = 'main'): \App\Models\AccountExt
    {
        if (!isset($this->accounts[$key])) {
            throw new \InvalidArgumentException("Unknown account key '{$key}'. Call withBorrowingPower('{$key}', …) first.");
        }
        return $this->accounts[$key];
    }

    /**
     * Register an account under $key with an OWN share balance effective from
     * $since (default 4 years ago, so even deep backdated draws pass the
     * as-of borrow-cap check). Re-using an existing key tops the same account
     * up with an additional OWN tranche.
     */
    public function withBorrowingPower(string $key, float $ownShares, ?Carbon $since = null): self
    {
        $since = $since ?? Carbon::today()->subYears(4);

        if (!isset($this->accounts[$key])) {
            if ($key === 'main') {
                $this->accounts['main'] = $this->df->userAccount;
            } else {
                $this->df->createUser();
                $this->accounts[$key] = $this->df->userAccount;
            }
        }

        $this->seedOwnBalance($this->accounts[$key], $ownShares, $since);
        return $this;
    }

    /** Open a credit line through DrawService (returns the persisted line). */
    public function openLine(
        string $accountKey,
        float $principal,
        int $termMonths = 12,
        string $frequency = 'monthly',
        ?Carbon $originationDate = null,
        ?string $descr = null
    ): AccountCreditLine {
        return $this->draw->open(
            $this->account($accountKey),
            $principal,
            $termMonths,
            $frequency,
            $descr,
            $originationDate
        );
    }

    /** Full-line repayment (applies oldest-first). */
    public function repay(AccountCreditLine $line, float $shares, ?Carbon $date = null): TransactionExt
    {
        return $this->repay->repay($line, $shares, $date);
    }

    /** Per-row registration against the Nth open schedule row (0-based, due-date order). */
    public function repayRow(AccountCreditLine $line, int $index, ?float $shares = null, ?Carbon $date = null): TransactionExt
    {
        $row = $this->rows($line)->values()->get($index);
        if (!$row) {
            throw new \InvalidArgumentException("Line #{$line->id} has no schedule row at index {$index}.");
        }
        return $this->repay->repayRow($row, $shares ?? (float) $row->shares_due, $date);
    }

    /** Schedule rows for a line, due-date ascending. */
    public function rows(AccountCreditLine $line): \Illuminate\Database\Eloquent\Collection
    {
        return CreditLinePayment::where('account_credit_line_id', $line->id)
            ->orderBy('due_date')
            ->get();
    }

    /** Sweep overdue scheduled rows to LATE across all lines. */
    public function sweepLate(?Carbon $asOf = null): int
    {
        return $this->late->detectAll($asOf);
    }

    /**
     * Compose a deliberately tangled history on $accountKey and return the
     * lines it created, keyed by role:
     *
     *  - 'paid_off'  : a short line fully repaid (status = paid_off).
     *  - 'partial'   : a backdated line with a part-paid first row and a
     *                  late backlog (some rows past due, one partial).
     *  - 'cancelled' : a line opened then cancelled (no draw activity left).
     *  - 'active'    : a current line with mixed paid / partial / scheduled
     *                  rows and a reversed repayment in its transaction log.
     *  - 'adjusted'  : a backdated line with a paid row and a late backlog
     *                  that was then readjusted (term + frequency changed),
     *                  so its scheduled rows were cancelled and a fresh
     *                  schedule + a CreditLineAdjustment audit row exist
     *                  on top of preserved paid/late rows.
     *
     * @return array<string,AccountCreditLine>
     */
    public function messyAccount(string $accountKey): array
    {
        $today = Carbon::today();

        // The account keeps a single open BOR balance row, and the temporal
        // guard refuses any BOR/REP effective date earlier than that row's
        // start_dt. So every draw and repayment below is sequenced on a
        // strictly non-decreasing timeline.

        // 1) paid_off: open 30 over 3 months (T-12mo), repay it all (T-11mo).
        $paidOff = $this->openLine($accountKey, 30, 3, 'monthly', $today->copy()->subMonths(12), 'messy: paid off');
        $this->repay($paidOff, 30, $today->copy()->subMonths(11));

        // 2) partial: backdated draw (T-9mo), 12 monthly rows → ~6 past due.
        //    Short-pay the first row (T-8mo); the rest become a late backlog.
        $partial = $this->openLine($accountKey, 120, 12, 'monthly', $today->copy()->subMonths(9), 'messy: partial + late');
        $firstRow = $this->rows($partial)->first();
        $this->repay->repayRow($firstRow, round((float) $firstRow->shares_due / 3, 4), $today->copy()->subMonths(8));

        // 3a) adjusted: backdated (T-7mo), one row paid (T-7mo), the rest a
        //     late backlog — readjusted (term + frequency change) at the end.
        //     The readjust cancels only the remaining `scheduled` rows and
        //     leaves paid / late rows intact, writing an audit row. Readjust
        //     is date-agnostic (uses today()), so it runs after the timeline.
        $adjusted = $this->openLine($accountKey, 60, 12, 'monthly', $today->copy()->subMonths(7), 'messy: adjusted plan');
        $adjRows = $this->rows($adjusted);
        $this->repay->repayRow($adjRows->get(0), (float) $adjRows->get(0)->shares_due, $today->copy()->subMonths(7));

        // 3b) active: current line (T-6mo) with mixed paid / partial /
        //    scheduled rows plus a repayment that is later reversed.
        $active = $this->openLine($accountKey, 90, 9, 'monthly', $today->copy()->subMonths(6), 'messy: active mixed');
        $rows = $this->rows($active);
        $this->repay->repayRow($rows->get(0), (float) $rows->get(0)->shares_due, $today->copy()->subMonths(5));
        $this->repay->repayRow($rows->get(1), round((float) $rows->get(1)->shares_due / 2, 4), $today->copy()->subMonths(4));

        // 4) cancelled: opened (T-3mo), fully repaid (T-2mo), then cancelled.
        $cancelled = $this->openLine($accountKey, 10, 6, 'monthly', $today->copy()->subMonths(3), 'messy: cancelled');
        $this->repay($cancelled, 10, $today->copy()->subMonths(2));
        (new \App\Services\CreditLine\Cancel\CancelService())->cancel($cancelled->refresh());

        // Reversed repayment on the active line (T-10d): a stray REP left in
        // the transaction log with reversed = true.
        $reversible = $this->repay($active->refresh(), 5, $today->copy()->subDays(10));
        $reversible->reversed = true;
        $reversible->save();

        $this->sweepLate();

        // Readjust the adjusted line over its now-messy state (paid row +
        // late backlog): change both term and frequency so the schedule is
        // rebuilt and an audit row is written.
        (new \App\Services\CreditLine\Adjust\ReadjustService(
            new OutstandingCalculator(),
            new AmortizationScheduleBuilder()
        ))->readjust($adjusted->refresh(), 24, 'quarterly', null, 'messy: term + frequency change');

        return [
            'paid_off'  => $paidOff->refresh(),
            'partial'   => $partial->refresh(),
            'cancelled' => $cancelled->refresh(),
            'active'    => $active->refresh(),
            'adjusted'  => $adjusted->refresh(),
        ];
    }

    /** Seed an OWN balance tranche (same shape as the per-test helpers). */
    private function seedOwnBalance(\App\Models\AccountExt $account, float $shares, Carbon $startDate): void
    {
        $tran = $this->df->createTransaction(
            $shares * 10,
            $account,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            $startDate->toDateString()
        );
        $tran->shares = $shares;
        $tran->save();

        AccountBalance::create([
            'account_id'     => $account->id,
            'transaction_id' => $tran->id,
            'type'           => 'OWN',
            'shares'         => $shares,
            'start_dt'       => $startDate->toDateString(),
            'end_dt'         => '9999-12-31',
        ]);
    }
}
