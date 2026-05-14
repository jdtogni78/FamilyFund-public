<?php

namespace App\Services\CreditLine\Matching;

use App\Models\AccountCreditLine;
use App\Models\TransactionExt;
use App\Models\User;
use App\Services\CreditLine\Matching\Contracts\ScheduleAdvancer;
use App\Services\CreditLine\Matching\Exceptions\InvalidMatchResolutionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Resolves a flagged (ambiguous / unmatched) REP transaction by manually
 * assigning it to a specific credit line (UC-31).
 *
 * Validations performed before persisting:
 *  1. The transaction's credit_line_match_status must be 'ambiguous' or 'unmatched'.
 *  2. The credit line's account_id must match the transaction's account_id.
 *
 * After saving, this service delegates schedule advancement to ScheduleAdvancer.
 * Phase 2 will bind the real RepayService implementation in the container.
 */
class MatchResolutionService
{
    public function __construct(
        private readonly ScheduleAdvancer $scheduleAdvancer,
    ) {}

    /**
     * Manually assign a flagged REP transaction to the given credit line.
     *
     * @param  TransactionExt    $tran   The flagged REP transaction.
     * @param  AccountCreditLine $line   The target credit line chosen by the admin.
     * @param  User|null         $admin  The resolving user (for audit; nullable for system calls).
     * @return void
     *
     * @throws InvalidMatchResolutionException
     */
    public function resolve(TransactionExt $tran, AccountCreditLine $line, ?User $admin = null): void
    {
        // ── Validation 1: only flagged transactions can be manually resolved ──
        $allowedStatuses = [
            TransactionExt::MATCH_STATUS_AMBIGUOUS,
            TransactionExt::MATCH_STATUS_UNMATCHED,
        ];
        if (!in_array($tran->credit_line_match_status, $allowedStatuses, true)) {
            throw InvalidMatchResolutionException::notFlagged(
                $tran->id,
                (string) $tran->credit_line_match_status
            );
        }

        // ── Validation 2: accounts must match ────────────────────────────────
        if ((int) $tran->account_id !== (int) $line->account_id) {
            throw InvalidMatchResolutionException::accountMismatch(
                $tran->id,
                (int) $tran->account_id,
                (int) $line->account_id
            );
        }

        DB::transaction(function () use ($tran, $line, $admin): void {
            $tran->account_credit_line_id   = $line->id;
            $tran->credit_line_match_status = TransactionExt::MATCH_STATUS_MANUAL;
            $tran->save();

            Log::info(sprintf(
                '[MatchResolutionService] tran=%d manually resolved to line=%d by user=%s',
                $tran->id,
                $line->id,
                $admin?->id ?? 'system',
            ));

            // Delegate schedule advancement to the wave-1a RepayService contract.
            // Phase 2 dependency: bind App\Services\CreditLine\Matching\Contracts\ScheduleAdvancer
            // to App\Services\CreditLine\Repay\RepayService in AppServiceProvider.
            $this->scheduleAdvancer->advance($tran, $line);
        });
    }
}
