<?php

namespace App\Http\Controllers\Traits;

use App\Http\Resources\TransactionResource;
use App\Models\ScheduledJob;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\TransactionEmail;

trait TransactionTrait
{
    use VerboseTrait, MailTrait;

    public function createTransaction(array $input): array
    {
        $dryRun = $input['dry_run'] ?? false;
        if ($input['type'] !== TransactionExt::TYPE_INITIAL) {
            $input['shares'] = null;
        }
        $this->debug('TransactionTrait::createTransaction: ' . json_encode($input));

        $transaction_data = null;
        DB::beginTransaction();
        try {
            /** @var TransactionExt $transaction */
            $transaction = $this->transactionRepository->create($input);
            $transaction_data = $transaction->processPending();
            if ($dryRun) {
                DB::rollBack();
            } else {
                $api1 = $this->getPreviewData($transaction_data);
                $this->sendTransactionConfirmation($api1);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        return $transaction_data;
    }

    protected function transactionScheduleDue($shouldRunBy, ScheduledJob $schedule, Carbon $asOf): TransactionExt {
        // get transaction from repo
        $tran = TransactionExt::find($schedule->entity_id);
        /** @var TransactionExt $newTran */
        $newTran = null;
        if ($tran) {
            // duplicate transaction
            DB::transaction(function () use ($tran, $schedule, $asOf, &$newTran) {
                $newTran = $tran->replicate();
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
            foreach ($transaction_data['matches'] as $match) {
                // Log::info('TransactionControllerExt::preview: match: ' . json_encode($match));
                // must access fields to load
                $matchTran = $match['transaction'];
                $matchTran->cashDeposit?->id;
                $matchTran->depositRequest?->id;
                $matchTran->referenceTransactionMatching?->id;
                $matchTran->account?->id;
            }
        }

        $transaction = $transaction_data['transaction'];
        
        // load fields to avoid lazy loading
        $transaction->cashDeposit?->id;
        $transaction->depositRequest?->id;
        $transaction->referenceTransactionMatching?->id;
        $transaction->account?->id;
        Log::info('TransactionTrait::getPreviewData: balance: ' . json_encode(array_keys($transaction_data)));
        $transaction_data['balance']?->account?->id;

        return $transaction_data;
    }

    protected function sendTransactionConfirmation($transaction_data) {
        $transaction = $transaction_data['transaction'];
        $to = $transaction->account->email_cc;
        $tranMail = new TransactionEmail($transaction_data);
        $this->sendMail($tranMail, $to);
    }
}
