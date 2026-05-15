<?php

namespace App\Services\CreditLine\Matching;

use App\Models\AccountCreditLineExt;
use App\Models\CreditLinePayment;
use App\Models\TransactionExt;
use App\Repositories\AccountCreditLineRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Stateless matcher: given a REP transaction, returns the best-fit credit line
 * using the priority rules in §5 rule 3 of credit_lines_plan.md.
 *
 * Priority (for accounts with ≥2 active lines):
 *   1. Exact share match against any scheduled CreditLinePayment.shares_due.
 *   2. Cash match fallback (feature-flagged; default OFF):
 *      cash_value ≈ shares_due × share_value_at_transaction_date (tolerance 1 %).
 *   3. Outstanding match: REP shares == line.outstanding_shares (full payoff).
 *
 * For accounts with exactly 1 active line the transaction is always matched to it.
 *
 * Coordination note:
 *   Wave 1a (DrawService) sets account_credit_line_id explicitly on BOR transactions.
 *   This matcher should only run when that FK is NULL.  A non-NULL FK causes an
 *   immediate no-op return so the matcher is safe to call from anywhere.
 */
class CreditLineMatcher
{
    private const SHARE_TOLERANCE = 1e-4; // decimal(19,4) equality
    private const CASH_TOLERANCE_PCT = 0.01; // 1 %

    public function __construct(
        private readonly AccountCreditLineRepository $creditLineRepo,
    ) {}

    /**
     * Match a REP transaction to a credit line.
     *
     * @param  TransactionExt $tran                The REP transaction to match.
     * @param  bool           $cashFallbackEnabled  Enable priority-2 cash matching (default: false per v1 spec).
     * @return MatchResult
     */
    public function match(TransactionExt $tran, bool $cashFallbackEnabled = false): MatchResult
    {
        // ── Guard: only REP transactions are matched ──────────────────────
        if ($tran->type !== TransactionExt::TYPE_REPAY) {
            return MatchResult::noOp(
                $tran->account_credit_line_id ?? 0,
                'Not a REP transaction — matcher is a no-op'
            );
        }

        // ── Guard: already assigned by wave 1a / manual resolution ────────
        if ($tran->account_credit_line_id !== null) {
            return MatchResult::noOp(
                $tran->account_credit_line_id,
                'account_credit_line_id already set — skipping matcher'
            );
        }

        $repShares = (float) $tran->shares;
        $accountId = (int) $tran->account_id;

        // ── Fetch all active lines for this account ────────────────────────
        /** @var Collection<AccountCreditLineExt> $activeLines */
        $activeLines = $this->creditLineRepo
            ->makeModel()
            ->newQuery()
            ->where('account_id', $accountId)
            ->where('status', AccountCreditLineExt::STATUS_ACTIVE)
            ->get();

        if ($activeLines->isEmpty()) {
            return MatchResult::unmatched('No active credit lines on account ' . $accountId);
        }

        // ── Priority 0: single active line → trivial match ────────────────
        if ($activeLines->count() === 1) {
            $line = $activeLines->first();
            return MatchResult::autoMatched(
                $line->id,
                'Single active line on account — auto-matched (UC-25)'
            );
        }

        // ── Priority 1: exact share match against scheduled payments ───────
        $shareMatchLineIds = [];
        foreach ($activeLines as $line) {
            $hasMatch = CreditLinePayment::where('account_credit_line_id', $line->id)
                ->where('status', CreditLinePayment::STATUS_SCHEDULED)
                ->whereRaw('ABS(shares_due - ?) < ?', [$repShares, self::SHARE_TOLERANCE])
                ->exists();
            if ($hasMatch) {
                $shareMatchLineIds[] = $line->id;
            }
        }

        if (count($shareMatchLineIds) === 1) {
            return MatchResult::autoMatched(
                $shareMatchLineIds[0],
                sprintf('Shares %.4f matched exactly one scheduled payment (UC-26)', $repShares)
            );
        }

        if (count($shareMatchLineIds) > 1) {
            return MatchResult::ambiguous(
                $shareMatchLineIds,
                sprintf('Shares %.4f matched %d scheduled payments — ambiguous (UC-29)', $repShares, count($shareMatchLineIds))
            );
        }

        // ── Priority 2: cash match fallback (feature-flagged, default OFF) ─
        if ($cashFallbackEnabled) {
            $shareValue = $tran->account->shareValueAsOf($tran->timestamp);
            if ($shareValue > 0) {
                $repCashValue = $repShares * $shareValue;
                $cashMatchLineIds = [];

                foreach ($activeLines as $line) {
                    $scheduledPayment = CreditLinePayment::where('account_credit_line_id', $line->id)
                        ->where('status', CreditLinePayment::STATUS_SCHEDULED)
                        ->first();
                    if ($scheduledPayment === null) {
                        continue;
                    }
                    $expectedCash = $scheduledPayment->shares_due * $shareValue;
                    if ($expectedCash <= 0) {
                        continue;
                    }
                    $diff = abs($repCashValue - $expectedCash);
                    if ($diff / $expectedCash <= self::CASH_TOLERANCE_PCT) {
                        $cashMatchLineIds[] = $line->id;
                    }
                }

                if (count($cashMatchLineIds) === 1) {
                    return MatchResult::autoMatched(
                        $cashMatchLineIds[0],
                        sprintf('Cash value $%.2f matched one scheduled payment (UC-27, cash-fallback)', $repCashValue)
                    );
                }

                if (count($cashMatchLineIds) > 1) {
                    return MatchResult::ambiguous(
                        $cashMatchLineIds,
                        sprintf('Cash value $%.2f matched %d lines — ambiguous (cash-fallback)', $repCashValue, count($cashMatchLineIds))
                    );
                }
            }
        }

        // ── Priority 3: outstanding match — full payoff ────────────────────
        $outstandingMatchLineIds = [];
        foreach ($activeLines as $line) {
            if (abs((float) $line->outstanding_shares - $repShares) < self::SHARE_TOLERANCE) {
                $outstandingMatchLineIds[] = $line->id;
            }
        }

        if (count($outstandingMatchLineIds) === 1) {
            return MatchResult::autoMatched(
                $outstandingMatchLineIds[0],
                sprintf('Shares %.4f equals outstanding_shares — full payoff match (UC-28)', $repShares)
            );
        }

        if (count($outstandingMatchLineIds) > 1) {
            return MatchResult::ambiguous(
                $outstandingMatchLineIds,
                sprintf('Shares %.4f matches outstanding_shares of %d lines — ambiguous (UC-29)', $repShares, count($outstandingMatchLineIds))
            );
        }

        // ── No match found ─────────────────────────────────────────────────
        return MatchResult::unmatched(
            sprintf('Shares %.4f did not match any active line by any priority (UC-30)', $repShares)
        );
    }
}
