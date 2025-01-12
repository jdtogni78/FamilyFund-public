<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\TransactionTrait;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Requests\PreviewTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\AccountExt;
use App\Models\Transaction;
use App\Models\TransactionExt;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Illuminate\Support\Facades\Log;
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
        $api = $this->getApi();
        return view('transactions.create')
            ->with('api', $api)
            ->with('api1', ['dry_run' => false]);
    }

    protected function getApi()
    {
        return [
            'typeMap' => TransactionExt::$typeMap,
            'statusMap' => TransactionExt::$statusMap,
            'flagsMap' => TransactionExt::$flagsMap,
            'accountMap' => AccountExt::accountMap(),
        ];
    }

    /**
     * Preview a newly created Transaction.
     *
     * @param CreateTransactionRequest $request
     *
     * @return Response
     */
    public function preview(PreviewTransactionRequest $request)
    {
        $input = $request->all();
        $tran_status = $input['status'];

        try {
            list($transaction, $newBal, $oldShares, $fundCash, $matches, $shareValue) = $this->createTransaction($input);
        } catch (Exception $e) {
            Log::error('TransactionControllerExt::preview: error: ' . $e->getMessage());
            Log::error($e);
            Flash::error($e->getMessage());
            return back()->withError($e->getMessage())->withInput();
        }

        Log::info('TransactionControllerExt::preview: input: ' . json_encode($input));
        $transaction->status = $tran_status;
        $api1 = $this->getPreviewData($transaction, $newBal, $oldShares, $fundCash, $matches, $shareValue);

        Log::info('TransactionControllerExt::preview: api: ' . json_encode($api1));
        return view('transactions.preview')
            ->with('api1', $api1)
            ->with('api', $this->getApi());
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
        Log::info('TransactionControllerExt::store: input: ' . json_encode($input));

        try {
            $this->createTransaction($input);
        } catch (Exception $e) {
            Log::error($e);
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

        return view('transactions.show')
            ->with('transaction', $transaction);
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
        $api = [
            'typeMap' => TransactionExt::$typeMap,
            'statusMap' => TransactionExt::$statusMap,
            'flagsMap' => TransactionExt::$flagsMap,
            'accountMap' => AccountExt::accountMap(),
        ];

        return view('transactions.edit')
            ->with('transaction', $transaction)
            ->with('api', $api);
    }

}
