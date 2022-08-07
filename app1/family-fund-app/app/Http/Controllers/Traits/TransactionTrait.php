<?php

namespace App\Http\Controllers\Traits;

use App\Models\Transaction;
use App\Models\TransactionExt;
use Exception;
use Illuminate\Support\Arr;

trait TransactionTrait
{
    public function createTransaction(array $input): ?TransactionExt
    {
        $input['shares'] = null;
        $transaction = $this->transactionRepository->create($input);
        try {
            $transaction->processPending();
        } catch (Exception $e) {
            $transaction->delete();
            throw $e;
        }
        return $transaction;
//            print_r("STORED: " . json_encode($transaction)."\n");
    }
}
