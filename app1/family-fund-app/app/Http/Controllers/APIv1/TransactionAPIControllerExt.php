<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Requests\API\CreateTransactionAPIRequest;
use App\Http\Requests\API\UpdateTransactionAPIRequest;
use App\Models\Transaction;
use App\Models\TransactionMatching;
use App\Repositories\TransactionRepository;
use App\Repositories\TransactionAssetRepository;
use App\Repositories\AssetPriceRepository;
use App\Repositories\AssetRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\API\TransactionAPIController;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionMatchingResource;
use Response;

/**
 * Class TransactionControllerExt
 * @package App\Http\Controllers\API
 */

class TransactionAPIControllerExt extends TransactionAPIController
{
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
     * @return Response
     */
    public function store(CreateTransactionAPIRequest $request)
    {
        $input = $request->all();

        if ($input['type'] == 'PUR') {
            $transaction = $this->transactionRepository->create($input);
            $account = $transaction->account()->first();

            foreach ($account->accountMatchingRules()->get() as $matchings) {
                $input['source'] = 'MAT';
                $input['value'] = $transaction->value/2;
    
                $matchTran = $this->transactionRepository->create($input);
                $match = TransactionMatching::factory()
                    ->for($matchings->matchingRule()->first())
                    // ->forReferenceTransaction([$transaction]) // dont work??
                    // ->for($transaction, 'referenceTransaction') // dont work??
                    ->create([
                        'transaction_id' => $matchTran->id,
                        'reference_transaction_id' => $transaction->id
                    ]);
            }
        } else {
            $this->sendError();
        }
        return $this->sendResponse(new TransactionResource($transaction), 'Transaction saved successfully');
    }


}
