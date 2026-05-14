<?php

namespace App\Services\CreditLine\Draw;

use App\Models\AccountBalance;
use App\Models\AccountCreditLine;
use App\Models\AccountCreditLineExt;
use App\Models\AccountExt;
use App\Models\TransactionExt;
use App\Services\CreditLine\Exceptions\OverBorrowException;
use App\Services\CreditLine\Support\AmortizationScheduleBuilder;
use App\Services\CreditLine\Support\CreditLineBalanceTracker;
use App\Services\CreditLine\Support\OutstandingCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * DrawService — opens a new credit line on an account.
 *
 * Responsibilities:
 * - Validate draw cap (UC-03): principal_shares ≤ available-to-borrow.
 * - Race-condition protection (UC-04): SELECT … FOR UPDATE on the account row.
 * - Create the AccountCreditLine record.
 * - Create the BOR Transaction (type=BOR, status=C).
 * - Generate the amortization schedule via AmortizationScheduleBuilder.
 * - Update the aggregate BOR AccountBalance row.
 *
 * Phase 2 dependency: Controller must enforce admin-only auth before calling open().
 */
class DrawService
{
    public function __construct(
        private AmortizationScheduleBuilder $scheduleBuilder,
        private OutstandingCalculator $calculator,
        private ?CreditLineBalanceTracker $balanceTracker = null,
    ) {
        $this->balanceTracker = $this->balanceTracker ?? new CreditLineBalanceTracker();
    }

    /**
     * Open a new credit line on the given account.
     *
     * @param  AccountExt   $account
     * @param  float        $principalShares  Number of shares to borrow.
     * @param  int          $termMonths       Loan term in calendar months.
     * @param  string       $frequency        Payment frequency: monthly | quarterly | annual.
     * @param  string|null  $descr            Optional free-text description.
     * @param  Carbon|null  $originationDate  Defaults to today.
     * @return AccountCreditLine              The newly created (and persisted) credit line.
     *
     * @throws OverBorrowException  If principalShares > available-to-borrow.
     */
    public function open(
        AccountExt $account,
        float $principalShares,
        int $termMonths,
        string $frequency,
        ?string $descr = null,
        ?Carbon $originationDate = null
    ): AccountCreditLine {
        $originationDate = $originationDate ?? Carbon::today();

        return DB::transaction(function () use ($account, $principalShares, $termMonths, $frequency, $descr, $originationDate) {
            // Lock the account row to prevent concurrent draws exceeding the cap (UC-04).
            DB::table('accounts')->where('id', $account->id)->lockForUpdate()->first();

            // Validate draw cap (UC-03).
            $available = $this->calculator->availableToBorrow($account, $originationDate);
            if ($principalShares > $available) {
                throw new OverBorrowException($principalShares, $available);
            }

            $maturityDate = $originationDate->copy()->addMonths($termMonths);

            // Create the credit line record.
            $line = AccountCreditLine::create([
                'account_id'         => $account->id,
                'principal_shares'   => round($principalShares, 4),
                'outstanding_shares' => round($principalShares, 4),
                'term_months'        => $termMonths,
                'origination_date'   => $originationDate->toDateString(),
                'maturity_date'      => $maturityDate->toDateString(),
                'payment_frequency'  => $frequency,
                'status'             => AccountCreditLineExt::STATUS_ACTIVE,
                'descr'              => $descr,
            ]);

            // Create the BOR transaction.
            // credit_line_match_status is NULL — the matcher is not involved for explicit draws.
            // The share value is not tracked on the transaction for now (Phase 5 will handle fund-side
            // cash movement when FundExt::valueAsOf() is extended per fund_cashflow.md).
            $borTransaction = TransactionExt::create([
                'account_id'              => $account->id,
                'account_credit_line_id'  => $line->id,
                'type'                    => TransactionExt::TYPE_BORROW,
                'status'                  => TransactionExt::STATUS_CLEARED,
                'value'                   => 0,   // cash-leg deferred to Phase 5 (fund cashflow)
                'shares'                  => round($principalShares, 4),
                'timestamp'               => $originationDate->toDateTimeString(),
                'credit_line_match_status' => null,
                'reversed'                => false,
                'descr'                   => $descr ?? 'Credit line draw',
            ]);

            // Build the amortization schedule.
            $this->scheduleBuilder->build($line, $principalShares, $originationDate);

            // Update the aggregate BOR balance row on the account.
            $this->calculator->updateAggregateBorBalance($account, $originationDate->toDateString(), $borTransaction->id);

            // Record the initial outstanding-shares balance for historical receivable
            // reconstruction (Phase 4).
            $this->balanceTracker->recordChange(
                $line,
                (float) $line->outstanding_shares,
                $borTransaction,
                $originationDate
            );

            // Refresh so callers see the persisted state.
            $line->refresh();

            return $line;
        });
    }
}
