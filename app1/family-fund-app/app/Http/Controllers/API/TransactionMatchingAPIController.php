<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Traits\AuthorizesApiAccess;
use App\Http\Requests\API\CreateTransactionMatchingAPIRequest;
use App\Http\Requests\API\UpdateTransactionMatchingAPIRequest;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use App\Repositories\TransactionMatchingRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Resources\TransactionMatchingResource;
use Response;

/**
 * Class TransactionMatchingController
 * @package App\Http\Controllers\API
 */

class TransactionMatchingAPIController extends AppBaseController
{
    use AuthorizesApiAccess;

    /** @var  TransactionMatchingRepository */
    protected $transactionMatchingRepository;

    public function __construct(TransactionMatchingRepository $transactionMatchingRepo)
    {
        $this->transactionMatchingRepository = $transactionMatchingRepo;
    }

    /**
     * Resolve the account that owns a transaction matching, via
     * matching → transaction → account, for object-level checks.
     */
    private function matchingAccount(?TransactionMatching $matching): ?\App\Models\AccountExt
    {
        return $matching ? $this->resolveAccount($matching->transaction?->account_id) : null;
    }

    /**
     * Display a listing of the TransactionMatching.
     * GET|HEAD /transactionMatchings
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = TransactionMatching::query();
        if (!$this->currentApiUser()?->isSystemAdmin()) {
            $authz = $this->apiAuthz();
            $query->whereHas('transaction', function ($q) use ($authz) {
                $authz->scopeByAccountRelation($q);
            });
        }

        foreach ($request->except(['skip', 'limit']) as $field => $value) {
            $query->where($field, $value);
        }

        if ($request->get('skip')) {
            $query->skip((int) $request->get('skip'));
        }

        if ($request->get('limit')) {
            $query->limit((int) $request->get('limit'));
        }

        $transactionMatchings = $query->get();

        return $this->sendResponse(TransactionMatchingResource::collection($transactionMatchings), 'Transaction Matchings retrieved successfully');
    }

    /**
     * Store a newly created TransactionMatching in storage.
     * POST /transactionMatchings
     *
     * @param CreateTransactionMatchingAPIRequest $request
     *
     * @return Response
     */
    public function store(CreateTransactionMatchingAPIRequest $request)
    {
        $input = $request->all();

        // Linking a transaction to a matching requires modify rights on the
        // transaction's account (fund-admin / financial-manager).
        $transaction = TransactionExt::find($input['transaction_id'] ?? null);
        $this->requireAccountAccess($this->resolveAccount($transaction?->account_id), modify: true);

        $transactionMatching = $this->transactionMatchingRepository->create($input);

        return $this->sendResponse(new TransactionMatchingResource($transactionMatching), 'Transaction Matching saved successfully');
    }

    /**
     * Display the specified TransactionMatching.
     * GET|HEAD /transactionMatchings/{id}
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        /** @var TransactionMatching $transactionMatching */
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            return $this->sendError('Transaction Matching not found');
        }

        $this->requireAccountAccess($this->matchingAccount($transactionMatching));

        return $this->sendResponse(new TransactionMatchingResource($transactionMatching), 'Transaction Matching retrieved successfully');
    }

    /**
     * Update the specified TransactionMatching in storage.
     * PUT/PATCH /transactionMatchings/{id}
     *
     * @param int $id
     * @param UpdateTransactionMatchingAPIRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTransactionMatchingAPIRequest $request)
    {
        $input = $request->all();

        /** @var TransactionMatching $transactionMatching */
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            return $this->sendError('Transaction Matching not found');
        }

        $this->requireAccountAccess($this->matchingAccount($transactionMatching), modify: true);

        $transactionMatching = $this->transactionMatchingRepository->update($input, $id);

        return $this->sendResponse(new TransactionMatchingResource($transactionMatching), 'TransactionMatching updated successfully');
    }

    /**
     * Remove the specified TransactionMatching from storage.
     * DELETE /transactionMatchings/{id}
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        /** @var TransactionMatching $transactionMatching */
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            return $this->sendError('Transaction Matching not found');
        }

        $this->requireAccountAccess($this->matchingAccount($transactionMatching), modify: true);

        $transactionMatching->delete();

        return $this->sendSuccess('Transaction Matching deleted successfully');
    }
}
