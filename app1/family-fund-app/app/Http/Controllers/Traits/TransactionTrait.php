<?php

namespace App\Http\Controllers\Traits;

use App\Http\Resources\TransactionResource;
use App\Models\ScheduledJob;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\TransactionMail;
use App\Http\Resources\AccountResource;
use App\Http\Resources\AccountBalanceResource;
use App\Http\Resources\PortfolioAssetResource;

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

        $transaction = null;
        $newBal = null;
        $oldShares = null;
        $fundCash = null;
        $matches = null;
        $shareValue = null;
        DB::transaction(function () use ($input, &$transaction, &$newBal, &$oldShares, &$fundCash, &$matches, &$shareValue, $dryRun) {
            /* @var TransactionExt $transaction */
            $transaction = $this->transactionRepository->create($input);
            list($newBal, $oldShares, $fundCash, $matches, $shareValue) = $transaction->processPending();
            if ($dryRun) {
                DB::rollBack();
            } else {
                $api1 = $this->getPreviewData($transaction, $newBal, $oldShares, $fundCash, $matches, $shareValue);
                $this->sendTransactionConfirmation($transaction, $api1);
            }
        });
        return [$transaction, $newBal, $oldShares, $fundCash, $matches, $shareValue];
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

    protected function getPreviewData($transaction, $newBal, $oldShares, $fundCash, $matches, $shareValue) {
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
        if (isset($fundCash)) {
            $api1['fundCash'][0] = (new PortfolioAssetResource($fundCash[0]))->resolve();
        }
        // remove created_at and updated_at from api1
        unset($api1['transaction']['created_at']);
        unset($api1['transaction']['updated_at']);
        unset($api1['newBal']['created_at']);
        unset($api1['newBal']['updated_at']);
        return $api1;
    }

    protected function sendTransactionConfirmation($transaction, $api1) {
        $to = $transaction->account()->get()->first()->email_cc;
        $tranMail = new TransactionMail($transaction, $api1);
        $this->sendMail($tranMail, $to);
    }
}
