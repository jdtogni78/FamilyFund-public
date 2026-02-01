<?php

namespace App\Http\Controllers\WebV1;

use App\Http\Controllers\Traits\TransactionTrait;
use App\Http\Controllers\Traits\AccountSelectorTrait;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\CreateTransactionRequest;
use App\Http\Requests\PreviewTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\AccountExt;
use App\Models\FundExt;
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
    use AccountSelectorTrait;

    public function __construct(TransactionRepository $transactionRepo)
    {
        $this->transactionRepository = $transactionRepo;
    }

    /**
     * Display a listing of Transactions with filtering.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $query = Transaction::with(['account.fund']);

        // Apply filters
        $filters = [];

        if ($request->filled('fund_id')) {
            $filters['fund_id'] = $request->fund_id;
            $query->whereHas('account', function($q) use ($request) {
                $q->where('fund_id', $request->fund_id);
            });
        }

        if ($request->filled('account_id')) {
            $filters['account_id'] = $request->account_id;
            $query->where('account_id', $request->account_id);
        }

        $transactions = $query->orderByDesc('id')->get();

        $api = array_merge(
            $this->getAccountSelectorData(),
            ['filters' => $filters]
        );

        return view('transactions.index')
            ->with('transactions', $transactions)
            ->with('api', $api)
            ->with('filters', $filters);
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
        // Build account data with fund_id for filtering
        $accounts = AccountExt::with(['user', 'fund'])->orderBy('nickname')->get();
        $accountMap = [null => 'Select an Account'];
        $accountFundMap = []; // account_id => fund_id
        foreach ($accounts as $account) {
            $label = $account->nickname;
            if ($account->code) {
                $label .= ' (' . $account->code . ')';
            }
            if ($account->user) {
                $label .= ' - ' . $account->user->name;
            }
            $accountMap[$account->id] = $label;
            $accountFundMap[$account->id] = $account->fund_id;
        }

        return [
            'typeMap' => TransactionExt::$typeMap,
            'statusMap' => TransactionExt::$statusMap,
            'flagsMap' => TransactionExt::$flagsMap,
            'accountMap' => $accountMap,
            'fundMap' => FundExt::fundMap(),
            'accountFundMap' => $accountFundMap,
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
     * Process all pending transactions in chronological order.
     *
     * @return Response
     */
    public function processAllPending()
    {
        $transactions = TransactionExt::where('status', TransactionExt::STATUS_PENDING)
            ->orderBy('timestamp')
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            Flash::info('No pending transactions to process.');
            return redirect(route('transactions.index'));
        }

        $processed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($transactions as $transaction) {
            try {
                DB::beginTransaction();
                $result = $transaction->processPending();
                if ($result && $result['transaction']->status == TransactionExt::STATUS_CLEARED) {
                    DB::commit();
                    $processed++;
                } else {
                    DB::rollBack();
                    $skipped++;
                }
            } catch (Exception $e) {
                DB::rollBack();
                $errors[] = "ID {$transaction->id}: " . $e->getMessage();
                Log::error("Error processing pending transaction {$transaction->id}: " . $e->getMessage());
            }
        }

        if (count($errors) > 0) {
            Flash::warning("Processed: $processed, Skipped: $skipped, Errors: " . count($errors) . ". " . implode('; ', array_slice($errors, 0, 3)));
        } elseif ($skipped > 0) {
            Flash::success("Processed $processed transaction(s). Skipped $skipped (future-dated).");
        } else {
            Flash::success("Successfully processed $processed transaction(s).");
        }

        return redirect(route('transactions.index'));
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
     * Clone a transaction with updated timestamp - opens create form with prefilled data.
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

        // Create a clone with today's date
        $clonedTransaction = new TransactionExt();
        $clonedTransaction->type = $transaction->type;
        $clonedTransaction->status = TransactionExt::STATUS_PENDING;
        $clonedTransaction->value = $transaction->value;
        $clonedTransaction->flags = $transaction->flags;
        $clonedTransaction->timestamp = Carbon::now()->format('Y-m-d');
        $clonedTransaction->account_id = $transaction->account_id;
        $clonedTransaction->descr = $transaction->descr;

        Log::info('TransactionControllerExt::clone: cloning transaction ' . $id);

        return view('transactions.create')
            ->with('transaction', $clonedTransaction)
            ->with('api', $this->getApi());
    }

    /**
     * Resend transaction confirmation email.
     *
     * @param int $id
     * @return Response
     */
    public function resendEmail($id)
    {
        /** @var TransactionExt $transaction */
        $transaction = $this->transactionRepository->find($id);

        if (empty($transaction)) {
            Flash::error('Transaction not found');
            return redirect(route('transactions.index'));
        }

        // Check if account has email configured
        if (empty($transaction->account->email_cc)) {
            Flash::error('No email address configured for this account. Set email_cc on the account first.');
            return redirect(route('transactions.show', $id));
        }

        // Build transaction data for email
        $transaction_data = [
            'transaction' => $transaction,
            'shareValue' => $transaction->account->shareValueAsOf($transaction->timestamp),
            'balance' => $transaction->balance,
            'matches' => null,
            'fundCash' => null,
        ];
        $api = $this->getPreviewData($transaction_data);

        // Send the email
        $this->sendTransactionConfirmation($api);

        Flash::success('Transaction confirmation email sent to ' . $transaction->account->email_cc);
        return redirect(route('transactions.show', $id));
    }

    /**
     * Show the form for creating transactions for multiple accounts.
     *
     * @return Response
     */
    public function bulkCreate()
    {
        $api = $this->getApi();

        // Group accounts by fund for easier selection
        $accounts = AccountExt::with(['user', 'fund'])
            ->whereNotNull('user_id')
            ->orderBy('fund_id')
            ->orderBy('nickname')
            ->get();

        $accountsByFund = [];
        foreach ($accounts as $account) {
            $fundName = $account->fund->name ?? 'Unknown Fund';
            if (!isset($accountsByFund[$fundName])) {
                $accountsByFund[$fundName] = [];
            }
            $label = $account->nickname;
            if ($account->code) {
                $label .= ' (' . $account->code . ')';
            }
            if ($account->user) {
                $label .= ' - ' . $account->user->name;
            }
            $accountsByFund[$fundName][] = [
                'id' => $account->id,
                'label' => $label,
                'email' => $account->email_cc,
                'fund_id' => $account->fund_id,
            ];
        }

        $api['accountsByFund'] = $accountsByFund;

        return view('transactions.create_bulk')
            ->with('api', $api);
    }

    /**
     * Preview bulk transactions before creating them.
     *
     * @param Request $request
     * @return Response
     */
    public function bulkPreview(Request $request)
    {
        $request->validate([
            'account_ids' => 'required|array|min:1',
            'account_ids.*' => 'exists:accounts,id',
            'type' => 'required',
            'status' => 'required',
            'value' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);

        $input = $request->all();
        $accountIds = $input['account_ids'];
        $previews = [];

        $fundSharesData = [];
        $timestamp = $input['timestamp'];

        DB::beginTransaction();
        try {
            foreach ($accountIds as $accountId) {
                $account = AccountExt::with('fund')->find($accountId);
                $transInput = [
                    'account_id' => $accountId,
                    'type' => $input['type'],
                    'status' => $input['status'],
                    'value' => $input['value'],
                    'timestamp' => $input['timestamp'],
                    'descr' => $input['descr'] ?? null,
                    'flags' => $input['flags'] ?? null,
                ];

                // Calculate shares
                $sharePrice = $account->shareValueAsOf($input['timestamp']);
                $shares = $sharePrice > 0 ? $input['value'] / $sharePrice : 0;

                $previews[] = [
                    'account' => $account,
                    'input' => $transInput,
                    'share_price' => $sharePrice,
                    'shares' => $shares,
                ];

                // Aggregate shares by fund for fund shares source display
                $fundId = $account->fund_id;
                if (!isset($fundSharesData[$fundId])) {
                    $fund = $account->fund;
                    $fundSharesData[$fundId] = [
                        'fund_name' => $fund->name ?? 'Unknown Fund',
                        'fund' => $fund,
                        'total_shares' => 0,
                    ];
                }
                $fundSharesData[$fundId]['total_shares'] += $shares;
            }

            // Calculate before/after for each fund
            foreach ($fundSharesData as $fundId => &$data) {
                $fund = $data['fund'];
                $unallocatedAfter = $fund->unallocatedShares($timestamp);
                // For purchase: fund loses shares, so before = after + total_shares
                // For sale: fund gains shares, so before = after - total_shares
                $isPurchase = in_array($input['type'], ['PUR', 'INI', 'MAT']);
                if ($isPurchase) {
                    $data['before'] = $unallocatedAfter + $data['total_shares'];
                    $data['after'] = $unallocatedAfter;
                    $data['change'] = -$data['total_shares'];
                } else {
                    $data['before'] = $unallocatedAfter - $data['total_shares'];
                    $data['after'] = $unallocatedAfter;
                    $data['change'] = $data['total_shares'];
                }
                unset($data['fund']); // Don't pass the model to view
            }
        } finally {
            DB::rollBack();
        }

        return view('transactions.preview_bulk')
            ->with('previews', $previews)
            ->with('input', $input)
            ->with('fundSharesData', $fundSharesData)
            ->with('api', $this->getApi());
    }

    /**
     * Store multiple transactions at once.
     *
     * @param Request $request
     * @return Response
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'account_ids' => 'required|array|min:1',
            'account_ids.*' => 'exists:accounts,id',
            'type' => 'required',
            'status' => 'required',
            'value' => 'required|numeric',
            'timestamp' => 'required|date',
        ]);

        $input = $request->all();
        $accountIds = $input['account_ids'];
        $created = 0;
        $errors = [];

        foreach ($accountIds as $accountId) {
            $transInput = [
                'account_id' => $accountId,
                'type' => $input['type'],
                'status' => $input['status'],
                'value' => $input['value'],
                'timestamp' => $input['timestamp'],
                'descr' => $input['descr'] ?? null,
                'flags' => $input['flags'] ?? null,
            ];

            try {
                $this->createTransaction($transInput, false);
                $created++;
            } catch (Exception $e) {
                $account = AccountExt::find($accountId);
                $errors[] = ($account->nickname ?? "Account $accountId") . ': ' . $e->getMessage();
                Log::error("Bulk transaction error for account $accountId: " . $e->getMessage());
            }
        }

        if (count($errors) > 0) {
            Flash::warning("Created $created transaction(s). Errors: " . implode(', ', $errors));
        } else {
            Flash::success("Successfully created $created transaction(s).");
        }

        return redirect(route('transactions.index'));
    }

}
