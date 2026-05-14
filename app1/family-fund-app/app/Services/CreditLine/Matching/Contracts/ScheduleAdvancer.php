<?php

namespace App\Services\CreditLine\Matching\Contracts;

use App\Models\AccountCreditLine;
use App\Models\TransactionExt;

/**
 * Contract: advance the payment schedule of a credit line after a REP is matched.
 *
 * This interface is the seam between wave 1b (Matcher) and wave 1a (RepayService).
 * Phase 2 will bind the concrete RepayService implementation in the service container.
 *
 * Until then, MatchResolutionService uses a NullScheduleAdvancer stub.
 */
interface ScheduleAdvancer
{
    /**
     * Apply a matched REP transaction to the given credit line's schedule.
     *
     * @param  TransactionExt    $tran  The matched (or manually resolved) REP transaction.
     * @param  AccountCreditLine $line  The target credit line.
     * @return void
     */
    public function advance(TransactionExt $tran, AccountCreditLine $line): void;
}
