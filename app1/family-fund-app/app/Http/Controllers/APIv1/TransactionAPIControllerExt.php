<?php

namespace App\Http\Controllers\APIv1;

use App\Http\Controllers\AppBaseController;
use App\Http\Controllers\Traits\AuthorizesApiAccess;
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
use App\Http\Resources\TransactionResource;
use App\Http\Resources\TransactionMatchingResource;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TransactionControllerExt
 * @package App\Http\Controllers\API
 */

class TransactionAPIControllerExt extends AppBaseController
{
    use TransactionTrait;
    use AuthorizesApiAccess;

    /** @var  TransactionRepository */
    protected $transactionRepository;

    public function __construct(TransactionRepository $transactionRepo)
    {
        $this->transactionRepository = $transactionRepo;
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
        // Creating transactions requires full access to a fund (TransactionPolicy::create).
        $this->requireFullAccessToAnyFund();

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

    // --- inlined from former base ---

    public function index(Request $request)
    {
        $query = $this->apiAuthz()->scopeByAccountRelation(TransactionExt::query());

        foreach ($request->except(['skip', 'limit']) as $field => $value) {
            $query->where($field, $value);
        }

        if ($request->get('skip')) {
            $query->skip((int) $request->get('skip'));
        }

        if ($request->get('limit')) {
            $query->limit((int) $request->get('limit'));
        }

        $transactions = $query->get();

        return $this->sendResponse(TransactionResource::collection($transactions), 'Transactions retrieved successfully');
    }

    public function show($id)
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            return $this->sendError('Transaction not found');
        }

        $this->requireAccountAccess($this->resolveAccount($transaction->account_id));

        return $this->sendResponse(new TransactionResource($transaction), 'Transaction retrieved successfully');
    }

    public function update($id, UpdateTransactionAPIRequest $request)
    {
        $input = $request->all();

        /** @var Transaction $transaction */
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            return $this->sendError('Transaction not found');
        }

        $this->requireAccountAccess($this->resolveAccount($transaction->account_id), modify: true);

        $transaction = $this->transactionRepository->update($input, $id);

        return $this->sendResponse(new TransactionResource($transaction), 'Transaction updated successfully');
    }

    public function destroy($id)
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            return $this->sendError('Transaction not found');
        }

        $this->requireAccountAccess($this->resolveAccount($transaction->account_id), modify: true);

        $transaction->delete();

        return $this->sendSuccess('Transaction deleted successfully');
    }

}
