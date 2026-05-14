<?php

namespace App\Services\Detection;

use App\Models\TransactionExt;
use App\Services\Detection\Contracts\Classifier;
use Illuminate\Support\Facades\Log;

/**
 * Classifies PUR (contribution/purchase) transactions.
 *
 * Phase 1 placeholder — logs the transaction and returns n_a.
 *
 * Phase 2 TODO: wire up the existing TransactionMatching flow here without
 * replacing it. The existing matching logic lives in:
 *   TransactionExt::createMatching() → called by TransactionExt::processPending()
 * Phase 2 should extract or delegate to that logic so the contribution
 * classifier can evaluate matching rules without duplicating code.
 */
class ContributionClassifier implements Classifier
{
    public function classify(TransactionExt $tran): DetectionResult
    {
        // Phase 1: no-op stub.
        // Phase 2 will invoke existing contribution-matching subsystem here.
        Log::info(
            "ContributionClassifier: transaction #{$tran->id} (PUR) — " .
            "classification deferred to Phase 2; returning n_a."
        );

        return DetectionResult::notApplicable(
            'PUR classification not yet implemented — Phase 2 will wire TransactionMatching flow.'
        );
    }
}
