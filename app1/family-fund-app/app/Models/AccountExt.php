<?php

namespace App\Models;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Http\Resources\AccountBalanceResource;
use App\Models\Account;
use App\Repositories\AccountBalanceRepository;
use App\Repositories\AccountRepository;
use App\Repositories\TransactionRepository;
use DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Nette\Utils\DateTime;

/**
 * Class AccountExt
 * @package App\Models
 */
class AccountExt extends Account
{
    use VerboseTrait;

    public static function fundAccountMap()
    {
        $accountRepo = \App::make(AccountRepository::class);
        $recs = $accountRepo->all(['user_id' => null], null, null, ['id', 'nickname'])->toArray();
        $out = [null => 'Select a Fund Account'];
        foreach ($recs as $row) {
            $out[$row['id']] = $row['nickname'];
        }
        return $out;
    }

    public static function accountMap()
    {
        $accounts = self::with('user')->orderBy('nickname')->get();
        $out = [null => 'Select an Account'];
        foreach ($accounts as $account) {
            $label = $account->nickname;
            if ($account->code) {
                $label .= ' (' . $account->code . ')';
            }
            if ($account->user) {
                $label .= ' - ' . $account->user->name;
            }
            $out[$account->id] = $label;
        }
        return $out;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     **/
    public function transactions()
    {
        return $this->hasMany(\App\Models\TransactionExt::class, 'account_id')
            ->orderBy('timestamp');
    }

    public function depositedValueBetween($start, $end)
    {
        $query = TransactionExt::where('account_id', $this->id)
            ->whereDate('timestamp', '>', $start)
            ->whereDate('timestamp', '<', $end);
        $trans = $query->get();
        $value = 0;
        foreach ($trans as $tran) {
            if ($tran->status == 'P') continue;

//            'type' => 'in:PUR,BOR,SAL,REP,MAT,INI',
            switch ($tran->type) {
                case TransactionExt::TYPE_PURCHASE:
                case 'REP':
                    $value += $tran->value;
                    break;
                case 'BOR':
                case 'SAL':
                    $value -= $tran->value;
                    break;
                case 'MAT': // dont account for
                case TransactionExt::TYPE_INITIAL: // dont account for
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
        $arr = array();
        foreach ($accountBalances as $balance) {
            $arr[$balance->type] = $balance;
            $typeCount[$balance->type]++;
        }
        foreach ($typeCount as $key => $count) {
            if ($count > 1) {
                throw new \Exception("Every account can have only 1 balance active at any given day (found " . $count . ")");
            }
        }
        return $arr;
    }

    public function sharesAsOf($now) {
        $accountBalances = $this->allSharesAsOf($now);
//        Log::debug("share val as of $now " .$accountBalances->count());
        foreach ($accountBalances as $balance) {
//            Log::debug(json_encode($balance->toArray()));
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
        /** @var FundExt $fund */
        $fund = $this->fund()->first();
        return $fund->shareValueAsOf($asOf);
    }

    public function remainingMatchings() {
        return NULL;
    }

    public function findOldestTransaction() {
        $trans = $this->transactions()->get();
        $tran = $trans->sortBy('timestamp')->first();
        return $tran;
    }

    public static function calculateTWR(array $data): float
    {
        $cumulativeReturn = 1;
        // Log::debug("calculate TWR: " . json_encode($data));
        foreach ($data as $period) {
            list($startValue, $endValue, $cashFlow) = $period;
            // Log::debug("** $startValue, $endValue, $cashFlow");

            // Calculate the return for the current period
            if ($startValue == 0) {
                $periodReturn = 1;
            } else {
                $periodReturn = ($endValue - $cashFlow) / $startValue;
            }

            // Update the cumulative return
            $cumulativeReturn *= $periodReturn;
            // Log::debug("*** $periodReturn, $cumulativeReturn");
        }

        // Calculate the final TWR
        $twr = $cumulativeReturn - 1;
        // Log::debug("twr: ". ($twr * 100));

        return $twr;
    }


    public function periodPerformance($from, $to)
    {
        $this->debug("periodPerformance $from $to");

        $trans = $this->transactions()
            ->select('timestamp', DB::raw('sum(value) as value'))
            ->whereDate('timestamp', '>=', $from)
            ->whereDate('timestamp', '<', $to)
            ->orderBy('timestamp')
            ->groupBy('timestamp')
            ->get();

        $start = $from;
        $startValue = $this->valueAsOf($start);
        $this->debug("per per $start $startValue");
        $data = [];
        /** @var TransactionExt $tran */
        foreach($trans as $tran) {
            $this->debug($tran);
            $end = $tran->timestamp; // shares are added next day

            $endValue = $this->valueAsOf($end);
            $cashFlow = 0+$tran->value;
            $data[] = [$startValue, $endValue, $cashFlow];

            $startValue = $endValue;
        }
        $lastValue = $this->valueAsOf($to);
        $data[] = [$startValue, $lastValue, 0];
        return self::calculateTWR($data);
    }

    public function yearlyPerformance($year)
    {
        $from = $year.'-01-01';
        $to = ($year+1).'-01-01';
        return $this->periodPerformance($from, $to);
    }

    // validate has email
    public function validateHasEmail(): ?string
    {
        $account = $this;
        if (empty($account->email_cc)) {
            Log::error("No email_cc for " . $account->nickname);
            return $account->nickname;
        }
        return null;
    }
}
