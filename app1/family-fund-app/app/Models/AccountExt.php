<?php

namespace App\Models;

use App\Http\Resources\AccountBalanceResource;
use App\Models\Account;
use App\Repositories\AccountBalanceRepository;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Date;
use Nette\Utils\DateTime;

/**
 * Class AccountExt
 * @package App\Models
 */
class AccountExt extends Account
{
    // public function fund()
    // {
    //     return parent::fund()->get()->first();
    // }
    public static function accountMap()
    {
        $accountRepo = \App::make(AccountRepository::class);
        $recs = $accountRepo->all([], null, null, ['id', 'nickname'])->toArray();
        $out = [];
        foreach ($recs as $row) {
            $out[$row['id']] = $row['nickname'];
        }
        return $out;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function transactions()
    {
        return $this->hasMany(\App\Models\TransactionExt::class, 'account_id')
            ->orderBy('created_at')
            ;
    }

    public function depositedValueBetween($start, $end)
    {
        $transactionsRepo = \App::make(TransactionRepository::class);
        $query = $transactionsRepo->makeModel()->newQuery()
            ->where('account_id', $this->id)
            ->whereDate('timestamp', '>', $start)
            ->whereDate('timestamp', '<', $end);
        $trans = $query->get();
        $value = 0;
        foreach ($trans as $tran) {
            if ($tran->status == 'P') continue;

//            'type' => 'in:PUR,BOR,SAL,REP,MAT,INI',
            switch ($tran->type) {
                case 'PUR':
                case 'REP':
                    $value += $tran->value;
                    break;
                case 'BOR':
                case 'SAL':
                    $value -= $tran->value;
                    break;
                case 'MAT': // dont account for
                case 'INI': // dont account for
                    break;

            }
        }
        return $value;
    }

    /**
     **/
    public function allSharesAsOf($now)
    {
        $accountBalanceRepo = \App::make(AccountBalanceRepository::class);
        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $this->id)
            ->whereDate('start_dt', '<=', $now)
            ->whereDate('end_dt', '>', $now)
            ->orderBy('start_dt', 'asc')
            ->orderBy('end_dt', 'asc');
        $accountBalances = $query->get();
        $typeCount = array();
        $typeCount['OWN'] = 0;
        $typeCount['BOR'] = 0;
        foreach ($accountBalances as $balance) {
            $typeCount[$balance->type]++;
        }
        foreach ($typeCount as $key => $count) {
            if ($count > 1) {
                throw new \Exception("Every account can have only 1 balance active at any given day (found " . $count . ")");
            }
        }
        return $accountBalances;
    }

    public function sharesAsOf($now) {
        $accountBalances = $this->allSharesAsOf($now);
//        print_r($accountBalances->count());
        foreach ($accountBalances as $balance) {
//            print_r(json_encode($balance->toArray()));
            if ($balance->type == 'OWN') {
                return $balance->shares;
            }
            // TODO: discount BORROW!!
        }
        return 0;
    }

    public function valueAsOf($now) {
        $shareValue = $this->shareValueAsOf($now);
        $shares = $this->sharesAsOf($now);
        $value = $shareValue * $shares;
        return $value;
    }

    public function shareValueAsOf($asOf) {
        return $this->fund()->first()->shareValueAsOf($asOf);
    }

    public function remainingMatchings() {
        return NULL;
    }

    public function periodPerformance($from, $to)
    {
        $shareValueFrom = $this->fund()->first()->shareValueAsOf($from);
        $shareValueTo = $this->fund()->first()->shareValueAsOf($to);

        $sharesFrom = $this->sharesAsOf($from);
        $sharesTo = $this->sharesAsOf($to);

        $valueFrom = $shareValueFrom * $sharesFrom;
        $valueTo = $shareValueTo * $sharesTo;

        // var_dump(array($from, $to, $shareValueFrom, $shareValueTo, $sharesFrom, $sharesTo, $valueFrom, $valueTo));
        if ($valueFrom == 0) return 0;
        return $valueTo/$valueFrom - 1;
    }

    public function yearlyPerformance($year)
    {
        $from = $year.'-01-01';
        $to = ($year+1).'-01-01';
        return $this->periodPerformance($from, $to);
    }}
