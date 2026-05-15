<?php

namespace App\Services\Detection;

use App\Models\TransactionExt;
use App\Services\Detection\Contracts\Classifier;
use Illuminate\Support\Facades\Log;

/**
 * Classifies BOR / REP transactions against credit lines.
 *
 * Delegates to App\Services\CreditLine\Matching\CreditLineMatcher (wave 1b).
 * If that class is not yet present at runtime the exception is caught and
 * logged so this service remains loadable during parallel development.
 */
class CreditLineClassifier implements Classifier
{
    public function classify(TransactionExt $tran): DetectionResult
    {
        Log::debug("CreditLineClassifier: classifying transaction #{$tran->id} (type={$tran->type})");

        try {
            /** @var \App\Services\CreditLine\Matching\CreditLineMatcher $matcher */
            $matcher = app(\App\Services\CreditLine\Matching\CreditLineMatcher::class);
            $matchResult = $matcher->match($tran);

            // Map MatchResult → DetectionResult and persist the match status on the transaction.
            $detectionResult = $this->mapAndPersist($tran, $matchResult);
        } catch (\Throwable $e) {
            // CreditLineMatcher not yet available (parallel wave) — log and return unmatched.
            Log::warning("CreditLineClassifier: CreditLineMatcher unavailable — {$e->getMessage()}");
            $detectionResult = DetectionResult::unmatched('CreditLineMatcher not available: ' . $e->getMessage());
        }

        Log::info("CreditLineClassifier: result for #{$tran->id} => status={$detectionResult->status}");
        return $detectionResult;
    }

    /**
     * Map App\Services\CreditLine\Matching\MatchResult to DetectionResult and
     * persist credit_line_match_status + account_credit_line_id on the transaction row.
     *
     * @param TransactionExt $tran
     * @param \App\Services\CreditLine\Matching\MatchResult $matchResult
     */
    private function mapAndPersist(TransactionExt $tran, $matchResult): DetectionResult
    {
        $status   = $matchResult->status;           // already one of MATCH_STATUS_* constants
        $lineId   = $matchResult->creditLineId;
        $reason   = $matchResult->reason ?? '';

        // Persist back to the transaction (only if values actually changed to avoid spurious updates).
        $dirty = false;
        if ($tran->credit_line_match_status !== $status) {
            $tran->credit_line_match_status = $status;
            $dirty = true;
        }
        if ($tran->account_credit_line_id !== $lineId) {
            $tran->account_credit_line_id = $lineId;
            $dirty = true;
        }
        if ($dirty) {
            // Use saveQuietly if available (Laravel 8+) so no model events re-fire.
            method_exists($tran, 'saveQuietly') ? $tran->saveQuietly() : $tran->save();
        }

        // Convert match status string → DetectionResult factory method.
        return match ($status) {
            TransactionExt::MATCH_STATUS_AUTO_MATCHED => DetectionResult::autoMatched((int) $lineId, $reason),
            TransactionExt::MATCH_STATUS_MANUAL       => DetectionResult::matched((int) $lineId, $reason),
            TransactionExt::MATCH_STATUS_AMBIGUOUS    => DetectionResult::ambiguous($reason),
            TransactionExt::MATCH_STATUS_UNMATCHED    => DetectionResult::unmatched($reason),
            default                                   => DetectionResult::unmatched("unknown status: {$status}"),
        };
    }
}
