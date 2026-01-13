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
        // Collect all available matching rules (applied + skipped)
        $availableMatching = [];

        if (isset($transaction_data['matches'])) {
            foreach ($transaction_data['matches'] as $key => $matchData) {
                // Handle new format: array with 'transaction', 'rule', 'remaining'
                $matchTran = $matchData['transaction'] ?? $matchData;
                $rule = $matchData['rule'] ?? null;
                $remaining = $matchData['remaining'] ?? 0;

                // Load relations
                Log::info('TransactionTrait::getPreviewData: match: ' . json_encode($matchTran));
                $matchTran = TransactionExt::find($matchTran->id);
                $matchTran->cashDeposit?->id;
                $matchTran->depositRequest?->id;
                $matchTran->referenceTransactionMatching?->id;
                $matchTran->account?->id;
                $matchTran->balance?->previousBalance?->id;
                $transaction_data['matches'][$key] = $matchTran;

                // Track remaining on applied rule
                if ($rule && $remaining > 0 && $rule->date_end >= now()) {
                    $availableMatching[] = [
                        'rule' => $rule,
                        'remaining' => $remaining,
                    ];
                }
            }
        }

        // Add skipped rules with remaining capacity
        if (isset($transaction_data['skippedRules'])) {
            foreach ($transaction_data['skippedRules'] as $skipped) {
                if (($skipped['remaining'] ?? 0) > 0 && $skipped['rule']->date_end >= now()) {
                    $availableMatching[] = $skipped;
                }
            }
        }

        $transaction_data['availableMatching'] = $availableMatching;

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
        // Include matching contributions in the total shares impact
        $fund = $account->fund;
        $timestamp = $transaction->timestamp;
        $fundSharesAfter = $fund->unallocatedShares($timestamp);

        // Calculate total shares including matching contributions
        $totalShares = $transaction->shares;
        $matches = $transaction_data['matches'] ?? [];
        foreach ($matches as $match) {
            $totalShares += $match->shares ?? 0;
        }

        // Before = After + total shares transferred to account
        $fundSharesBefore = $fundSharesAfter + $totalShares;
        $transaction_data['fundShares'] = [
            'fund_name' => $fund->name,
            'before' => $fundSharesBefore,
            'after' => $fundSharesAfter,
            'change' => -$totalShares, // Negative for purchase (fund loses), positive for sale
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
