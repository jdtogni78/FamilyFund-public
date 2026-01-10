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
use App\Models\ScheduledJobExt;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
            ->with('api', $api);
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

    public function preview(PreviewTransactionRequest $request)
    {
        $input = $request->all();
        $tran_status = $input['status'];

        try {
            $api1 = $this->createTransaction($input, true);
        } catch (Exception $e) {
            Log::error('TransactionControllerExt::preview: error: ' . $e->getMessage());
            Log::error($e);
            Flash::error($e->getMessage());
            return back()->withError($e->getMessage())->withInput();
        }

        Log::info('TransactionControllerExt::preview: input: ' . json_encode($input));
        // $transaction_data['transaction']->status = $tran_status;
        $api1['transaction']->id = null;
        $api1['transaction']->status = $tran_status;

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
            $this->createTransaction($input, false);
        } catch (Exception $e) {
            Log::error($e);
            Flash::error($e->getMessage());
            return back()->withError($e->getMessage())->withInput();
        }

        Flash::success('Transaction saved successfully.');
        return redirect(route('transactions.index'));
    }

    public function previewPending($id)
    {
        /** @var TransactionExt $transaction */
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            Flash::error('Transaction not found');
            return redirect(route('transactions.index'));
        }

        DB::beginTransaction();
        $transaction_data = $transaction->processPending();
        $api1 = $this->getPreviewData($transaction_data);
        DB::rollBack();
        
        return view('transactions.preview')
            ->with('api1', $api1)
            ->with('api', $this->getApi());
    }

    public function processPending($id)
    {
        $transaction = TransactionExt::find($id);

        if (empty($transaction)) {
            Flash::error('Transaction not found');
            return redirect(route('transactions.index'));
        }

        DB::beginTransaction();
        $transaction_data = $this->processTransaction($transaction, false);
        DB::commit();

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

    /**
     * Clone a transaction with updated timestamp.
     *
     * @param int $id
     * @return Response
     */
    public function clone($id)
    {
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            Flash::error('Transaction not found');
            return redirect(route('transactions.index'));
        }

        // Prepare input for creating a new transaction
        $input = [
            'type' => $transaction->type,
            'status' => TransactionExt::STATUS_PENDING,
            'value' => $transaction->value,
            'flags' => $transaction->flags,
            'timestamp' => Carbon::now()->format('Y-m-d'),
            'account_id' => $transaction->account_id,
            'descr' => $transaction->descr,
        ];

        Log::info('TransactionControllerExt::clone: cloning transaction ' . $id . ' with input: ' . json_encode($input));

        try {
            $api1 = $this->createTransaction($input, true);
        } catch (Exception $e) {
            Log::error('TransactionControllerExt::clone: error: ' . $e->getMessage());
            Flash::error($e->getMessage());
            return redirect(route('transactions.show', $id));
        }

        $api1['transaction']->id = null;
        $api1['transaction']->status = TransactionExt::STATUS_PENDING;

        return view('transactions.preview')
            ->with('api1', $api1)
            ->with('api', $this->getApi());
    }

}
