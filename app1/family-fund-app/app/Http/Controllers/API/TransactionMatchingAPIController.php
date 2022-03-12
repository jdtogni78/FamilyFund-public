<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\CreateTransactionMatchingAPIRequest;
use App\Http\Requests\API\UpdateTransactionMatchingAPIRequest;
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
    /** @var  TransactionMatchingRepository */
    protected $transactionMatchingRepository;

    public function __construct(TransactionMatchingRepository $transactionMatchingRepo)
    {
        $this->transactionMatchingRepository = $transactionMatchingRepo;
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
        $transactionMatchings = $this->transactionMatchingRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

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

        $transactionMatching->delete();

        return $this->sendSuccess('Transaction Matching deleted successfully');
    }
}
