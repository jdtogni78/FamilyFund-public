<?php

namespace App\Services\Detection\Contracts;

use App\Models\TransactionExt;
use App\Services\Detection\DetectionResult;

interface Classifier
{
    /**
     * Classify a BOR/REP/PUR transaction and return a detection result.
     * Implementations must be idempotent: calling twice on the same transaction
     * must produce the same result and must not create duplicate side-effects.
     */
    public function classify(TransactionExt $tran): DetectionResult;
}
