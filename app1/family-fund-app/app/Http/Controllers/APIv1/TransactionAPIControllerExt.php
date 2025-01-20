<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Requests\API\CreateTransactionAPIRequest;
use App\Http\Requests\API\UpdateTransactionAPIRequest;
use App\Models\AccountExt;
use App\Models\Transaction;
use App\Models\FundExt;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use App\Repositories\TransactionRepository;
use App\Http\Controllers\Traits\TransactionTrait;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\API\TransactionAPIController;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionMatchingResource;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TransactionControllerExt
 * @package App\Http\Controllers\API
 */

class TransactionAPIControllerExt extends TransactionAPIController
{
    use TransactionTrait;

    public function __construct(TransactionRepository $transactionRepo)
    {
        parent::__construct($transactionRepo);
    }

    /**
     * Store a newly created Transactions in storage.
     * POST /transactions
     *
     * @param CreateTransactionAPIRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function store(CreateTransactionAPIRequest $request)
    {
        $input = $request->all();
        $transaction = null;
        try {
            $transaction_data = $this->createTransaction($input, false);
            $transaction = $transaction_data['transaction'];
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), Response::HTTP_OK);
        }
        return $this->sendResponse(new TransactionResource($transaction), 'Transaction saved successfully');
    }

}
