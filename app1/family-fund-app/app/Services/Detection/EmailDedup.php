<?php

namespace App\Services\Detection;

use Illuminate\Support\Facades\Cache;

/**
 * Lightweight email de-duplication using Laravel's cache layer.
 *
 * Prevents duplicate emails when ingest() is retried (e.g. queued jobs
 * that fail and re-execute). The cache key is a hash of (transaction_id,
 * email_type) with a 24-hour TTL.
 *
 * Phase 2: replace with a DB-backed log table if audit trail is needed.
 * The interface is intentionally tiny so the swap is transparent to callers.
 */
class EmailDedup
{
    /**
     * TTL for the dedup key in seconds (24 hours).
     */
    private const TTL_SECONDS = 86400;

    /**
     * Cache store to use. 'default' works for all supported drivers.
     */
    private const CACHE_STORE = 'default';

    /**
     * Has an email of this type already been sent for this transaction?
     */
    public function wasSent(int $tranId, string $emailType): bool
    {
        return Cache::store()->has($this->key($tranId, $emailType));
    }

    /**
     * Record that an email of this type was sent for this transaction.
     */
    public function markSent(int $tranId, string $emailType): void
    {
        Cache::store()->put(
            $this->key($tranId, $emailType),
            true,
            self::TTL_SECONDS
        );
    }

    /**
     * Build a deterministic cache key.
     */
    private function key(int $tranId, string $emailType): string
    {
        return 'credit_line_email_dedup_' . sha1("{$tranId}:{$emailType}");
    }
}
