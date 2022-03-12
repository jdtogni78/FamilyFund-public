<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTransactionMatchingRequest;
use App\Http\Requests\UpdateTransactionMatchingRequest;
use App\Repositories\TransactionMatchingRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class TransactionMatchingController extends AppBaseController
{
    /** @var  TransactionMatchingRepository */
    protected $transactionMatchingRepository;

    public function __construct(TransactionMatchingRepository $transactionMatchingRepo)
    {
        $this->transactionMatchingRepository = $transactionMatchingRepo;
    }

    /**
     * Display a listing of the TransactionMatching.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $transactionMatchings = $this->transactionMatchingRepository->all();

        return view('transaction_matchings.index')
            ->with('transactionMatchings', $transactionMatchings);
    }

    /**
     * Show the form for creating a new TransactionMatching.
     *
     * @return Response
     */
    public function create()
    {
        return view('transaction_matchings.create');
    }

    /**
     * Store a newly created TransactionMatching in storage.
     *
     * @param CreateTransactionMatchingRequest $request
     *
     * @return Response
     */
    public function store(CreateTransactionMatchingRequest $request)
    {
        $input = $request->all();

        $transactionMatching = $this->transactionMatchingRepository->create($input);

        Flash::success('Transaction Matching saved successfully.');

        return redirect(route('transactionMatchings.index'));
    }

    /**
     * Display the specified TransactionMatching.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            Flash::error('Transaction Matching not found');

            return redirect(route('transactionMatchings.index'));
        }

        return view('transaction_matchings.show')->with('transactionMatching', $transactionMatching);
    }

    /**
     * Show the form for editing the specified TransactionMatching.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            Flash::error('Transaction Matching not found');

            return redirect(route('transactionMatchings.index'));
        }

        return view('transaction_matchings.edit')->with('transactionMatching', $transactionMatching);
    }

    /**
     * Update the specified TransactionMatching in storage.
     *
     * @param int $id
     * @param UpdateTransactionMatchingRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTransactionMatchingRequest $request)
    {
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            Flash::error('Transaction Matching not found');

            return redirect(route('transactionMatchings.index'));
        }

        $transactionMatching = $this->transactionMatchingRepository->update($request->all(), $id);

        Flash::success('Transaction Matching updated successfully.');

        return redirect(route('transactionMatchings.index'));
    }

    /**
     * Remove the specified TransactionMatching from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $transactionMatching = $this->transactionMatchingRepository->find($id);

        if (empty($transactionMatching)) {
            Flash::error('Transaction Matching not found');

            return redirect(route('transactionMatchings.index'));
        }

        $this->transactionMatchingRepository->delete($id);

        Flash::success('Transaction Matching deleted successfully.');

        return redirect(route('transactionMatchings.index'));
    }
}
