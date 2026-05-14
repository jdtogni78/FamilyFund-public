<?php

namespace App\Services\CreditLine\Reverse\Exceptions;

use RuntimeException;

/**
 * Thrown when an attempt is made to reverse a transaction of a non-reversible type.
 *
 * Only BOR and REP transactions are reversible. PUR, SAL, INI, MAT etc. are not.
 */
class NotReversibleTypeException extends RuntimeException
{
    public function __construct(int $transactionId, string $type)
    {
        parent::__construct(
            "Transaction #{$transactionId} has type '{$type}' which is not reversible. " .
            "Only BOR and REP transactions may be reversed."
        );
    }
}
