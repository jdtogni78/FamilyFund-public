<?php

namespace App\Services\CreditLine\Matching;

use App\Models\TransactionExt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Writes a MatchResult back to the transaction row.
 *
 * Wraps the save in a DB transaction so no partial writes escape.
 * Does NOT advance the credit-line schedule — that is the responsibility
 * of the RepayService (wave 1a) once it is wired in via ScheduleAdvancer.
 */
class MatcherPersister
{
    /**
     * Persist the match result onto the transaction.
     *
     * @param  TransactionExt $tran   The transaction to update.
     * @param  MatchResult    $result Result produced by CreditLineMatcher::match().
     * @return void
     */
    public function persist(TransactionExt $tran, MatchResult $result): void
    {
        DB::transaction(function () use ($tran, $result): void {
            $tran->account_credit_line_id   = $result->creditLineId;
            $tran->credit_line_match_status = $result->status;
            $tran->save();

            Log::info(sprintf(
                '[MatcherPersister] tran=%d status=%s line=%s reason="%s"',
                $tran->id,
                $result->status,
                $result->creditLineId ?? 'null',
                $result->reason,
            ));
        });
    }
}
