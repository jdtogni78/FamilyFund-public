<?php

namespace App\Http\Controllers\Traits;

use App\Http\Resources\TransactionResource;
use App\Models\ScheduledJob;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait TransactionTrait
{
    use VerboseTrait;

    public function createTransaction(array $input): array
    {
        $dryRun = $input['dry_run'] ?? false;
        if ($input['type'] !== TransactionExt::TYPE_INITIAL) {
            $input['shares'] = null;
        }
        $this->debug('TransactionTrait::createTransaction: ' . json_encode($input));

        $transaction = null;
        $newBal = null;
        $oldShares = null;
        $fundCash = null;
        $match = null;
        $shareValue = null;
        DB::transaction(function () use ($input, &$transaction, &$newBal, &$oldShares, &$fundCash, &$match, &$shareValue, $dryRun) {
            /* @var TransactionExt $transaction */
            $transaction = $this->transactionRepository->create($input);
            list($newBal, $oldShares, $fundCash, $match, $shareValue) = $transaction->processPending();
            if ($dryRun) {
                DB::rollBack();
            }
        });
        return [$transaction, $newBal, $oldShares, $fundCash, $match, $shareValue];
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
}
