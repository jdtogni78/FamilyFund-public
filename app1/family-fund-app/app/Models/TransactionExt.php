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
    // /**
    //  * Transaction types
    //  *
    //  * @var array
    //  */
    // public const TYPES = [
    //     1 => 'purchase',
    //     2 => 'sale',
    //     3 => 'borrow',
    //     4 => 'repay'
    // ];

    // /**
    //  * returns the id of a given type
    //  *
    //  * @param string $type  transaction type
    //  * @return int typeID
    //  */
    // public static function getTypeID($type)
    // {
    //     return array_search($type, self::TYPES);
    // }

    // /**
    //  * get transaction type
    //  */
    // public function getTypeAttribute()
    // {
    //     return self::TYPES[ $this->attributes['type'] ];
    // }

    // /**
    //  * set transaction type
    //  */
    // public function setTypeAttribute($value)
    // {
    //     $typeID = self::getTypeID($value);
    //     if ($typeID) {
    //        $this->attributes['type_id'] = $typeID;
    //     }
    // }
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

        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '=', '9999-12-31');
        $bal = $query->first();
        $oldShares = 0;

        if ($bal != null) {
            if ($bal->start_dt > $this->timestamp) {
                throw new Exception("Cannot add balance at " . $this->timestamp
                    . " before previous balance " . $bal->start_dt);
            }
            $oldShares = $bal->shares;
            $bal->end_dt = $this->timestamp;
            $bal->save();
            if ($this->verbose) print_r("OLD BAL " . json_encode($bal) . "\n");
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

}
