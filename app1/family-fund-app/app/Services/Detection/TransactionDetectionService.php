<?php

namespace App\Services\Detection;

use App\Mail\CreditLine\MismatchAlertMail;
use App\Mail\CreditLine\TransactionDetectedMail;
use App\Mail\CreditLine\TransactionReceivedMail;
use App\Models\TransactionExt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Unified transaction ingestion entry point (UC-35).
 *
 * Routes transactions by type to the appropriate classifier, then dispatches
 * email notifications respecting dedup (UC-38).
 *
 * HOW TO WIRE (Phase 2 TODO):
 *   Register a Transaction::saved observer / listener that calls:
 *       app(TransactionDetectionService::class)->ingest($transaction);
 *   Do NOT register this in EventServiceProvider yet — Phase 2 only.
 *
 * HOW TO CALL MANUALLY (e.g., from a Tinker or a one-off job):
 *   app(\App\Services\Detection\TransactionDetectionService::class)->ingest($tran);
 */
class TransactionDetectionService
{
    private const EMAIL_TYPE_RECEIVED  = 'transaction_received';
    private const EMAIL_TYPE_DETECTED  = 'transaction_detected';
    private const EMAIL_TYPE_MISMATCH  = 'mismatch_alert';

    public function __construct(
        private readonly CreditLineClassifier   $creditLineClassifier,
        private readonly ContributionClassifier $contributionClassifier,
        private readonly EmailDedup             $dedup,
    ) {}

    /**
     * Ingest a transaction: classify it and dispatch emails.
     * Idempotent — safe to call multiple times for the same transaction.
     */
    public function ingest(TransactionExt $tran): void
    {
        Log::info("TransactionDetectionService: ingesting transaction #{$tran->id} type={$tran->type}");

        $result = match ($tran->type) {
            TransactionExt::TYPE_BORROW,
            TransactionExt::TYPE_REPAY  => $this->creditLineClassifier->classify($tran),
            TransactionExt::TYPE_PURCHASE => $this->contributionClassifier->classify($tran),
            default => DetectionResult::notApplicable("type={$tran->type} not handled by detection pipeline"),
        };

        Log::debug("TransactionDetectionService: result for #{$tran->id} => status={$result->status}");

        // Only dispatch credit-line emails for BOR/REP transactions.
        if (!in_array($tran->type, [TransactionExt::TYPE_BORROW, TransactionExt::TYPE_REPAY], true)) {
            return;
        }

        $this->dispatchEmails($tran, $result);
    }

    // ── Email dispatch ────────────────────────────────────────────────────────

    private function dispatchEmails(TransactionExt $tran, DetectionResult $result): void
    {
        $recipientEmail = $this->resolveEmail($tran);
        if ($recipientEmail === null) {
            Log::warning("TransactionDetectionService: no email recipient for transaction #{$tran->id}; skipping emails.");
            return;
        }

        // UC-32 / UC-33: transaction received or system-detected email.
        $emailType = $this->isSystemGenerated($tran) ? self::EMAIL_TYPE_DETECTED : self::EMAIL_TYPE_RECEIVED;

        if (!$this->dedup->wasSent($tran->id, $emailType)) {
            $mailable = $emailType === self::EMAIL_TYPE_DETECTED
                ? new TransactionDetectedMail($tran, $result)
                : new TransactionReceivedMail($tran, $result);

            Mail::to($recipientEmail)->send($mailable);
            $this->dedup->markSent($tran->id, $emailType);

            Log::info("TransactionDetectionService: sent {$emailType} for #{$tran->id} to {$recipientEmail}");
        } else {
            Log::debug("TransactionDetectionService: dedup — {$emailType} already sent for #{$tran->id}");
        }

        // UC-29 / UC-30: mismatch alert when classification is ambiguous/unmatched.
        if ($result->needsReview()) {
            if (!$this->dedup->wasSent($tran->id, self::EMAIL_TYPE_MISMATCH)) {
                Mail::to($recipientEmail)->send(new MismatchAlertMail($tran, $result));
                $this->dedup->markSent($tran->id, self::EMAIL_TYPE_MISMATCH);
                Log::info("TransactionDetectionService: sent mismatch_alert for #{$tran->id} to {$recipientEmail}");
            }
        }
    }

    /**
     * Resolve the recipient email from the account.
     * Returns null if none is set (prevents silent failures).
     */
    private function resolveEmail(TransactionExt $tran): ?string
    {
        // Try account's email_cc first (consistent with TransactionEmail pattern).
        $account = $tran->account()->first();
        if ($account === null) {
            return null;
        }
        return $account->email_cc ?: null;
    }

    /**
     * Heuristic: if the transaction was created without a user (system-generated),
     * treat it as "detected" rather than "received".
     * Phase 2 TODO: use a `created_by` actor field if/when added to transactions.
     */
    private function isSystemGenerated(TransactionExt $tran): bool
    {
        // For now: PUR/MAT are system; BOR/REP submitted by a user are "received".
        // This will be refined in Phase 2 using actor metadata.
        return false;
    }
}
