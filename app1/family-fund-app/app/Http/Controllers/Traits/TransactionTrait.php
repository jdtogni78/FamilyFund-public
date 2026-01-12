<?php

namespace App\Http\Controllers\Traits;

use App\Http\Resources\TransactionResource;
use App\Models\ScheduledJob;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\TransactionEmail;
use Flash;
use App\Models\AccountExt;

trait TransactionTrait
{
    use VerboseTrait, MailTrait;

    public function createTransaction(array $input, bool $dry_run = false): array
    {
        if ($input['type'] !== TransactionExt::TYPE_INITIAL) {
            $input['shares'] = null;
        }
        $this->debug('TransactionTrait::createTransaction: ' . json_encode($input));

        $transaction_data = null;
        DB::beginTransaction();
        $transaction = $this->transactionRepository->create($input);
        $transaction_data = $this->processTransaction($transaction, $dry_run);
        DB::commit();
        return $transaction_data;
    }

    protected function processTransaction(TransactionExt $transaction, bool $dry_run): array {
        try {
            /** @var TransactionExt $transaction */
            $transaction_data = $transaction->processPending();
            $api = $this->getPreviewData($transaction_data);
            if ($dry_run) {
                Flash::success('Transaction preview ready.');
                DB::rollBack();
            } else {
                Flash::success('Transaction processed successfully.');
                $this->sendTransactionConfirmation($api);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        return $api;
    }

    protected function transactionScheduleDue($shouldRunBy, ScheduledJob $schedule, Carbon $asOf, bool $skipDataCheck = false): TransactionExt {
        // get transaction from repo
        $tran = TransactionExt::find($schedule->entity_id);
        /** @var TransactionExt $newTran */
        $newTran = null;
        if ($tran) {
            // duplicate transaction
            DB::transaction(function () use ($tran, $schedule, $asOf, &$newTran) {
                $newTran = $tran->replicate();
                // $newTran->verbose = true;
                $newTran->timestamp = $asOf;
                $newTran->status = TransactionExt::STATUS_PENDING;
                $newTran->shares = null;
                $newTran->scheduled_job_id = $schedule->id;
                $newTran->save();
                $newTran->processPending();
            });
        } else {
            Log::error('Scheduled Transaction ' . $schedule->entity_id . ' not found');
        }
        return $newTran;
    }

    protected function getPreviewData($transaction_data) {
        if (isset($transaction_data['matches'])) {
            foreach ($transaction_data['matches'] as $key => $match) {
                // Log::info('TransactionControllerExt::preview: match: ' . json_encode($match));
                // must access fields to load
                Log::info('TransactionTrait::getPreviewData: match: ' . json_encode($match));
                $matchTran = TransactionExt::find($match->id);
                $matchTran->cashDeposit?->id;
                $matchTran->depositRequest?->id;
                $matchTran->referenceTransactionMatching?->id;
                $matchTran->account?->id;
                $matchTran->balance?->previousBalance?->id;
                $transaction_data['matches'][$key] = $matchTran;
            }
        }

        $transaction = $transaction_data['transaction'];
        
        // load fields to avoid lazy loading
        $transaction = TransactionExt::find($transaction->id);
        $transaction->cashDeposit?->id;
        $transaction->depositRequest?->id;
        $transaction->referenceTransactionMatching?->id;
        $transaction->account?->id;
        $transaction->balance?->account?->id;
        $transaction->balance?->previousBalance?->id;
        Log::info('TransactionTrait::getPreviewData: balance: ' . json_encode($transaction->balance));
        
        /** @var AccountExt $account */
        $account = $transaction->account;
        $today = new Carbon();
        $transaction_data['today'] = $today;
        $transaction_data['shares_today'] = $account->sharesAsOf($today);
        $transaction_data['value_today'] = $account->valueAsOf($today);
        $transaction_data['share_value_today'] = $account->shareValueAsOf($today);
        $transaction_data['transaction'] = $transaction;

        // Fund shares source data (for purchase/sale visualization)
        $fund = $account->fund;
        $timestamp = $transaction->timestamp;
        $fundSharesAfter = $fund->unallocatedShares($timestamp);
        // Before = After + shares transferred to account (or - shares returned from account)
        $fundSharesBefore = $fundSharesAfter + $transaction->shares;
        $transaction_data['fundShares'] = [
            'fund_name' => $fund->name,
            'before' => $fundSharesBefore,
            'after' => $fundSharesAfter,
            'change' => -$transaction->shares, // Negative for purchase (fund loses), positive for sale
        ];

        return $transaction_data;
    }

    protected function sendTransactionConfirmation($transaction_data) {
        $transaction = $transaction_data['transaction'];
        $to = $transaction->account->email_cc;
        Log::info('TransactionTrait::sendTransactionConfirmation: ' . json_encode(array_keys($transaction_data)));
        $tranMail = new TransactionEmail($transaction_data);
        $this->sendMail($tranMail, $to);
    }
}
