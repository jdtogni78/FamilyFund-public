<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Requests\API\CreateTransactionAPIRequest;
use App\Http\Requests\API\UpdateTransactionAPIRequest;
use App\Models\Transaction;
use App\Models\FundExt;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use App\Repositories\TransactionRepository;
use App\Repositories\TransactionAssetRepository;
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

        if ($input['type'] == 'PUR' && $input['status'] == 'P') {
            if (Arr::exists($input, 'shares') && $input['shares'] != null) {
                return $this->sendError("Pending Purchase must NOT have shares as they will be calculated", Response::HTTP_OK);
            }
            $input['shares'] = null;
            $transaction = $this->transactionRepository->create($input);
            try {
                $transaction->processPending();
            } catch (Exception $e) {
                $transaction->delete();
                return $this->sendError($e->getMessage(), Response::HTTP_OK);
            }
//            print_r("STORED: " . json_encode($transaction)."\n");
        } else {
            return $this->sendError('Only Pending Purchase transactions are supported at the moment', Response::HTTP_OK);
        }
        return $this->sendResponse(new TransactionResource($transaction), 'Transaction saved successfully');
    }


}
