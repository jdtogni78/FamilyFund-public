<?php

namespace App\Services\Detection;

/**
 * Value object returned by Classifier::classify().
 *
 * Distinct from App\Services\CreditLine\Matching\MatchResult to avoid tight
 * coupling between the detection pipeline and the lower-level matcher.
 */
class DetectionResult
{
    public const STATUS_MATCHED       = 'matched';
    public const STATUS_AUTO_MATCHED  = 'auto_matched';
    public const STATUS_AMBIGUOUS     = 'ambiguous';
    public const STATUS_UNMATCHED     = 'unmatched';
    public const STATUS_NA            = 'n_a';

    public readonly string $status;
    public readonly ?int   $targetCreditLineId;
    public readonly string $notes;

    public function __construct(string $status, ?int $targetCreditLineId, string $notes = '')
    {
        $this->status             = $status;
        $this->targetCreditLineId = $targetCreditLineId;
        $this->notes              = $notes;
    }

    public static function matched(int $lineId, string $notes = ''): self
    {
        return new self(self::STATUS_MATCHED, $lineId, $notes);
    }

    public static function autoMatched(int $lineId, string $notes = ''): self
    {
        return new self(self::STATUS_AUTO_MATCHED, $lineId, $notes);
    }

    public static function ambiguous(string $notes = ''): self
    {
        return new self(self::STATUS_AMBIGUOUS, null, $notes);
    }

    public static function unmatched(string $notes = ''): self
    {
        return new self(self::STATUS_UNMATCHED, null, $notes);
    }

    public static function notApplicable(string $notes = ''): self
    {
        return new self(self::STATUS_NA, null, $notes);
    }

    public function needsReview(): bool
    {
        return in_array($this->status, [self::STATUS_AMBIGUOUS, self::STATUS_UNMATCHED], true);
    }
}
