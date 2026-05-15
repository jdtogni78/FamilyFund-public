<?php

namespace App\Services\Detection;

use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use App\Services\Detection\Contracts\Classifier;
use Illuminate\Support\Facades\Log;

/**
 * UC-37: routes PUR (contribution / deposit) transactions through the
 * unified detection pipeline.
 *
 * The legacy matching engine lives in `TransactionExt::createMatching()`
 * (called from `processPending()`) and already writes `TransactionMatching`
 * rows for any rule that fires. By the time the observer reaches us those
 * matches are persisted.
 *
 * Re-running the engine here would create *duplicate* MAT transactions and
 * double-count contributions, so the classifier instead inspects the
 * persisted matches and reports their state:
 *
 *   - one or more matches written  → `auto_matched` (UC-37 round-trip OK)
 *   - PUR for a fund / regular account with no rules                    → `n_a`
 *   - matching expected but produced zero rows                          → `unmatched`
 *
 * This keeps the unified pipeline observable for PUR (status surfaces in
 * `TransactionDetectedMail`-style flows / logs) without forking the
 * legacy code path.
 *
 * If the legacy engine moves into a service in a future wave, this
 * classifier becomes the natural place to invoke it; for now we stay a
 * thin read-only adapter so existing matching tests in
 * `MatchingRuleControllerExtTest` / `AccountMatchingRuleControllerExtTest`
 * continue to pass unchanged.
 */
class ContributionClassifier implements Classifier
{
    public function classify(TransactionExt $tran): DetectionResult
    {
        if ($tran->type !== TransactionExt::TYPE_PURCHASE) {
            return DetectionResult::notApplicable("type={$tran->type} ignored by ContributionClassifier");
        }

        // Fund-side PURs don't go through the contribution-matching engine.
        $account = $tran->account()->first();
        $isFundAccount = $account && $account->user_id === null;
        if ($isFundAccount) {
            return DetectionResult::notApplicable('fund-side PUR — no contribution match expected');
        }

        // A MAT row in `transaction_matchings` carries `reference_transaction_id`
        // pointing at the *originating* PUR; that's what we count.
        $matchCount = TransactionMatching::where('reference_transaction_id', $tran->id)->count();

        if ($matchCount > 0) {
            $firstMatch = TransactionMatching::where('reference_transaction_id', $tran->id)
                ->orderBy('id')
                ->first();

            Log::info(
                "ContributionClassifier: PUR #{$tran->id} matched {$matchCount} rule(s) " .
                "(via legacy createMatching path); first matching row id={$firstMatch?->id}"
            );

            // `targetCreditLineId` is overloaded here to surface the matching-rule
            // id so downstream consumers (DetectionResult inspectors / logs) can
            // trace which rule fired. The field is informational on PUR; no
            // credit line is involved.
            return new DetectionResult(
                DetectionResult::STATUS_AUTO_MATCHED,
                $firstMatch?->matching_rule_id,
                "PUR auto-matched {$matchCount} rule(s) via TransactionMatching"
            );
        }

        // No matches written. Two reasons: (a) the account has no rules
        // configured, or (b) rules exist but none fired. Both are acceptable
        // states for a deposit, so we treat this as n/a rather than unmatched —
        // an *unmatched* status would trigger a mismatch-alert email, which is
        // not desired for normal deposits.
        return DetectionResult::notApplicable(
            "PUR #{$tran->id}: no TransactionMatching rows — no rule fired or no rules configured"
        );
    }
}
