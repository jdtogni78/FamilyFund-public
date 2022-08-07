<?php

namespace App\Models;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Repositories\AccountBalanceRepository;
use App\Repositories\TransactionRepository;
use Exception;
use Nette\Utils\DateTime;

/**
 * Class TransactionExt
 * @package App\Models
 */
class TransactionExt extends Transaction
{
    /**
     * @throws \Exception
     */
    public function createBalance()
    {
        if ($this->shares == null || $this->shares == 0) {
            throw new Exception("Balances need transactions with shares");
        }
        if ($this->accountBalance()->first() != null) {
            throw new Exception("Transaction already has a balance associated");
        }

        $accountBalanceRepo = \App::make(AccountBalanceRepository::class);
        $account_id = $this->account()->first()->id;

        // TODO: move it to balance classes
        $otherBalance = $this->validateBalanceOverlap($accountBalanceRepo, $account_id);
        $bal = $this->validateLatestBalance($accountBalanceRepo, $account_id);

        $oldShares = 0;
        if ($bal != null) {
            $oldShares = $bal->shares;
            $bal->end_dt = $this->timestamp;
            $bal->save();
            if ($this->verbose) print_r("OLD BAL " . json_encode($bal) . "\n");
        } else {
            // has lingering balances that is not infinity
            throw new Exception("Unexpected: There are existent balances that were end-dated - not safe to proceed");
        }

        $newBal = $accountBalanceRepo
            ->create([
                'account_id' => $account_id,
                'transaction_id' => $this->id,
                'type' => 'OWN',
                'shares' => $oldShares + $this->shares,
                'start_dt' => $this->timestamp,
                'end_dt' => '9999-12-31',
            ]);
        if ($this->verbose) print_r("NEW BAL " . json_encode($newBal)."\n");
    }

    /**
     * @throws Exception
     */
    public function processPending(): void
    {
        $account = $this->account()->first();
        $fund = $account->fund()->first();
        $share_value = $fund->shareValueAsOf($this->timestamp);

        if ($share_value == 0) {
            throw new Exception("Cannot Process Transaction: Share price not available for fund " .
                $fund->name . " at " . $this->timestamp);
        }
        $this->shares = $this->value / $share_value;
        $this->createBalance();

        $rss = new TransactionResource($this);
        $input = $rss->toArray(null);

        $input['type'] = 'MAT';
        $descr = "Match transaction $this->id";
        $repo = \App::make(TransactionRepository::class);
        foreach ($account->accountMatchingRules()->get() as $amr) {
            $matchValue = $amr->match($this);
            if ($this->verbose) {
                print_r("AMR ".json_encode($amr)." " . $matchValue . "\n");
                print_r("MR ".json_encode($amr->matchingRule()->get())."\n");
            }
            if ($matchValue > 0) {
                $mr = $amr->matchingRule()->first();
                $input['value'] = $matchValue;
                $input['status'] = 'C';
                $input['shares'] = $matchValue / $share_value;
                $input['descr'] = "$descr with $mr->name ($mr->id)";

                if ($this->verbose) print_r("MATCHTRAN ".json_encode($input)."\n");
                $matchTran = $repo->create($input);
                $matchTran->createBalance();
                $match = TransactionMatching::factory()
                    ->for($mr)
                    // ->forReferenceTransaction([$this]) // dont work??
                    // ->for($this, 'referenceTransaction') // dont work??
                    ->create([
                        'transaction_id' => $matchTran->id,
                        'reference_transaction_id' => $this->id
                    ]);
            }
        }

        $this->status = 'C';
        $this->save();
    }

    protected function validateBalanceOverlap(mixed $accountBalanceRepo, mixed $account_id): mixed
    {
        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '<=', $this->timestamp)
            ->whereDate('start_dt', '>=', $this->timestamp);
        $bal2 = $query->first();
        if ($bal2 != null) {
            print_r("There is already a balance on this period " . $this->timestamp .
                ": " . json_encode($bal2->toArray()) . " \n");
            throw new Exception("Cannot add balance at " . $this->timestamp
                . " as there is already a balance on this period " . $bal2->id);
        }

        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '<', '9999-12-31');
        $bal2 = $query->first();
        return $bal2;
    }

    protected function validateLatestBalance(mixed $accountBalanceRepo, mixed $account_id): mixed
    {
        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '=', '9999-12-31');
        $bal = $query->first();

        if ($bal != null) {
            if ($bal->start_dt > $this->timestamp) {
                throw new Exception("Cannot add balance at " . $this->timestamp
                    . " before previous balance " . $bal->start_dt);
            }
        }
        return $bal;
    }

}
