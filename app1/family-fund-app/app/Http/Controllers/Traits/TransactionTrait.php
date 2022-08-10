<?php

namespace App\Http\Controllers\Traits;

use App\Models\TransactionExt;
use Illuminate\Support\Facades\DB;

trait TransactionTrait
{
    public function createTransaction(array $input): ?TransactionExt
    {
        $transaction = null;
        DB::transaction(function () use ($input, &$transaction) {
            $input['shares'] = null;
            $transaction = $this->transactionRepository->create($input);
            $transaction->processPending();
        });
        return $transaction;
    }
}
