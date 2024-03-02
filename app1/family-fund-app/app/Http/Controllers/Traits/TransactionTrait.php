<?php

namespace App\Http\Controllers\Traits;

use App\Models\ScheduledJob;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait TransactionTrait
{
    public function createTransaction(array $input): ?TransactionExt
    {
        $transaction = null;
        DB::transaction(function () use ($input, &$transaction) {
            if ($input['type'] !== TransactionExt::TYPE_INITIAL) {
                $input['shares'] = null;
            }
            $transaction = $this->transactionRepository->create($input);
            $transaction->processPending();
        });
        return $transaction;
    }

    protected function transactionScheduleDue($shouldRunBy, ScheduledJob $schedule, Carbon $asOf): void {
        // get transaction from repo
        $tran = $this->transactionRepository->find($schedule->entity_id);
        if ($tran) {
            // duplicate transaction
            DB::transaction(function () use ($tran, $schedule) {
                $newTran = $tran->replicate();
                $newTran->timestamp = Carbon::now();
                $newTran->status = TransactionExt::STATUS_PENDING;
                $newTran->shares = null;
                $newTran->scheduled_job_id = "Scheduled by " . $schedule->id;
                $newTran->save();
                $newTran->processPending();
            });
        } else {
            Log::error('Scheduled Transaction ' . $schedule->entity_id . ' not found');
        }
    }
}
