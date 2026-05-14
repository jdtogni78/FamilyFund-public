<?php

namespace App\Services\CreditLine\Reverse\Exceptions;

use RuntimeException;

/**
 * Thrown when an attempt is made to reverse a transaction that is already reversed.
 *
 * The transactions table has a unique constraint on transaction_reversals.transaction_id,
 * so a second reversal would violate DB integrity. This exception surfaces the problem
 * before we hit the DB constraint.
 */
class AlreadyReversedException extends RuntimeException
{
    public function __construct(int $transactionId)
    {
        parent::__construct("Transaction #{$transactionId} has already been reversed.");
    }
}
