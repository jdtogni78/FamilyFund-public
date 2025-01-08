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
use App\Http\Controllers\AppBaseController;
use Exception;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Illuminate\Support\Facades\Log;
use Response;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\AccountBalanceResource;
use App\Http\Resources\PortfolioAssetResource;
use App\Http\Resources\AccountResource;
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
            Log::error($e);
            Flash::error($e->getMessage());
            return back()->withError($e->getMessage())->withInput();
        }

        Log::info('TransactionControllerExt::preview: input: ' . json_encode($input));
        // transform match in resources
        $newMatches = [];
        if (isset($matches)) {
            foreach ($matches as $match) {
                Log::info('TransactionControllerExt::preview: match: ' . json_encode($match));
                $account = (new AccountResource($match[0][0]->account()->first()))->resolve();
                $match[0][0] = (new AccountBalanceResource($match[0][0]))->resolve();
                $match[0][0]['account'] = $account;
                $match[1] = (new TransactionResource($match[1]))->resolve();
                $newMatches[] = $match;
            }
        }
        $api1 = [
            'dry_run' => true,
            'transaction' => (new TransactionResource($transaction))->resolve(),
            'newBal' => (new AccountBalanceResource($newBal))->resolve(),
            'oldShares' => $oldShares,
            'fundCash' => $fundCash,
            'mtch' => $newMatches,
            'shareValue' => $shareValue,
        ];
        $api1['transaction']['account'] = (new AccountResource($transaction->account()->first()))->resolve();
        $api1['newBal']['account'] = (new AccountResource($newBal->account()->first()))->resolve();
        $api1['transaction']['status'] = $tran_status;
        if (isset($fundCash)) {
            $api1['fundCash'][0] = (new PortfolioAssetResource($fundCash[0]))->resolve();
        }
        // remove created_at and updated_at from api1
        unset($api1['transaction']['created_at']);
        unset($api1['transaction']['updated_at']);
        unset($api1['newBal']['created_at']);
        unset($api1['newBal']['updated_at']);

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
