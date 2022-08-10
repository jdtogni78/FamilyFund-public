<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\TransactionTrait;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\AccountExt;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Http\Controllers\AppBaseController;
use Exception;
use Illuminate\Http\Request;
use Flash;
use Response;

class TransactionControllerExt extends TransactionController
{
    use TransactionTrait;

    public function __construct(TransactionRepository $transactionRepo)
    {
        $this->transactionRepository = $transactionRepo;
    }

    /**
     * Show the form for creating a new Transaction.
     *
     * @return Response
     */
    public function create()
    {
        $api = [
            'typeMap' => Transaction::$typeMap,
            'statusMap' => Transaction::$statusMap,
            'accountMap' => AccountExt::accountMap(),
        ];
        return view('transactions.create')->with('api', $api);
    }

    /**
     * Store a newly created Transaction in storage.
     *
     * @param CreateTransactionRequest $request
     *
     * @return Response
     */
    public function store(CreateTransactionRequest $request)
    {
        $input = $request->all();

        try {
            $this->createTransaction($input);
        } catch (Exception $e) {
            Flash::error($e->getMessage());
            return back()->withError($e->getMessage())->withInput();
        }

        Flash::success('Transaction saved successfully.');

        return redirect(route('transactions.index'));
    }

    /**
     * Display the specified Transaction.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            Flash::error('Transaction not found');

            return redirect(route('transactions.index'));
        }

        return view('transactions.show')->with('transaction', $transaction);
    }

    /**
     * Show the form for editing the specified Transaction.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            Flash::error('Transaction not found');

            return redirect(route('transactions.index'));
        }

        return view('transactions.edit')->with('transaction', $transaction);
    }

}
