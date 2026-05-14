<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Services\Detection\TransactionDetectionService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Drives the credit-line / contribution detection pipeline on every Transaction save.
 *
 * Recursion guard
 * ───────────────
 * Detection services may themselves persist the Transaction (e.g. the matcher
 * sets `account_credit_line_id` and `credit_line_match_status`). To prevent
 * infinite recursion we use a static reentrancy flag — once we're inside ingest()
 * we ignore further `saved` events for the duration of that call.
 *
 * In addition we only ingest:
 *  - on creation, OR
 *  - when one of the load-bearing columns changed: type, shares, value, status.
 *  (Matcher updates touch `account_credit_line_id` / `credit_line_match_status` only,
 *  which fall outside this set and are therefore safely ignored.)
 */
class TransactionObserver
{
    private static bool $reentrant = false;

    private const RELEVANT_TYPES = [
        TransactionExt::TYPE_BORROW,
        TransactionExt::TYPE_REPAY,
        TransactionExt::TYPE_PURCHASE,
    ];

    private const TRIGGER_COLUMNS = ['type', 'shares', 'value', 'status'];

    public function saved(Transaction $tran): void
    {
        if (self::$reentrant) {
            return;
        }

        if (!in_array($tran->type, self::RELEVANT_TYPES, true)) {
            return;
        }

        // On update, only fire when a load-bearing column changed.
        if ($tran->wasRecentlyCreated === false) {
            $changed = false;
            foreach (self::TRIGGER_COLUMNS as $col) {
                if ($tran->wasChanged($col)) {
                    $changed = true;
                    break;
                }
            }
            if (!$changed) {
                return;
            }
        }

        self::$reentrant = true;
        try {
            // Promote to TransactionExt so the service receives the expected type.
            $ext = $tran instanceof TransactionExt
                ? $tran
                : TransactionExt::find($tran->id);

            if ($ext === null) {
                return;
            }

            app(TransactionDetectionService::class)->ingest($ext);
        } catch (Throwable $e) {
            // Detection should never break the originating save path.
            Log::error('TransactionObserver: ingest failed for transaction #' . $tran->id . ' — ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        } finally {
            self::$reentrant = false;
        }
    }
}
