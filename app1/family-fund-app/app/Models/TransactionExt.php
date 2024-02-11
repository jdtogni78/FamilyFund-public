<?php

namespace App\Models;

use App\Http\Controllers\APIv1\PortfolioAssetAPIControllerExt;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Repositories\AccountBalanceRepository;
use App\Repositories\PortfolioAssetRepository;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Nette\Utils\DateTime;

/**
 * Class TransactionExt
 * @package App\Models
 */
class TransactionExt extends Transaction
{
    public static array $typeMap = [
        'PUR' => 'Purchase',
        'INI' => 'Initial Value',
    ];
    public static array $statusMap = [
        'P' => 'Pending',
        'C' => 'Cleared',
    ];
    public static array $flagsMap = [
        'A' => 'Add Cash Position',
        'C' => 'Cash Already Added',
    ];

    /**
     * @throws \Exception
     */
    public function createBalance($shares, $timestamp, $verbose=false)
    {
        if ($shares == null || $shares == 0) {
            throw new Exception("Balances need transactions with shares");
        }
        if ($this->accountBalance()->first() != null) {
            throw new Exception("Transaction already has a balance associated");
        }

        $accountBalanceRepo = \App::make(AccountBalanceRepository::class);
        $account_id = $this->account()->first()->id;

        // TODO: move it to balance classes
        $otherBalance = $this->validateBalanceOverlap($accountBalanceRepo, $account_id, $timestamp);
        $bal = $this->validateLatestBalance($accountBalanceRepo, $account_id, $timestamp);

        $oldShares = 0;
        if ($bal != null) {
            $oldShares = $bal->shares;
            $bal->end_dt = $timestamp;
            $bal->save();
            if ($verbose) Log::debug("OLD BAL " . json_encode($bal) . "\n");
        } else if ($otherBalance != null) {
            Log::debug("BALANCE FOUND: ".json_encode($otherBalance->toArray())."\n");
            // has lingering balances that is not infinity
            throw new Exception("Unexpected: There are existent balances that were end-dated - not safe to proceed");
        }

        $newBal = $accountBalanceRepo
            ->create([
                'account_id' => $account_id,
                'transaction_id' => $this->id,
                'type' => 'OWN',
                'shares' => $oldShares + $shares,
                'start_dt' => $timestamp,
                'end_dt' => '9999-12-31',
            ]);
        if ($verbose) Log::debug("NEW BAL " . json_encode($newBal)."\n");
    }

    /**
     * @throws Exception
     */
    public function processPending(): void
    {
        $verbose = false;
        $account = $this->account()->first();
        $fund = $account->fund()->first();
        $timestamp = $this->timestamp;
        $value = $this->value;

        $shareValue = $fund->shareValueAsOf($timestamp);
        $availableShares = $fund->unallocatedShares($timestamp);

        if ($shareValue == 0) {
            throw new Exception("Cannot Process Transaction: Share price not available for fund " .
                $fund->name . " at " . $timestamp);
        }
        $isFundTrans = $account->user_id == null;
        $this->shares = $value / $shareValue;
        $allShares = $this->shares;
        if (!$isFundTrans) {
            if ($this->flags != null) {
                throw new Exception("Unexpected: Regular transactions dont support flags");
            }
            // check if fund has enough shares (unless its a fund transaction)
            if ($availableShares < $allShares) {
                throw new Exception("Fund does not have enough shares ($availableShares) to support purchase of ($allShares)");
            }
        } else {
            // validate that flags are in (A, C)
            // calculate shares, considering the value is added to the fund first
            switch ($this->flags) {
                case 'A': // add cash position
                    // share value is correct, no need to recalculate
                    // just need to add the cash to cash position
                    $this->addCashToFund($fund, $value, $timestamp);
                    break;

                case 'C': // cash already added
                    // recalculate share value, discounting the value
                    // a deposit was already made to the fund, artificially increasing its share value
                    $fundShares = $fund->sharesAsOf($timestamp);
                    $fundValue = $fund->valueAsOf($timestamp);
                    $shareValue = ($fundValue - $value) / $fundShares;
                    $this->shares = $value / $shareValue;
                    $allShares = $this->shares;
                    break;

                default:
                    $flagString = implode(',', array_keys(self::$flagsMap));
                    throw new Exception("Unexpected: Fund transactions must have flags: " . $flagString);
            }
        }
        $this->createBalance($this->shares, $timestamp);

        if (!$isFundTrans) {
            $this->createMatching($account, $verbose, $shareValue, $allShares, $availableShares);
        }

        $this->status = 'C';
        $this->save();
    }

    protected function validateBalanceOverlap(mixed $accountBalanceRepo, mixed $account_id, $timestamp): mixed
    {
        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '<', $timestamp)
            ->whereDate('start_dt', '>=', $timestamp);
        $bal2 = $query->first();
        if ($bal2 != null) {
            Log::debug("There is already a balance on this period " . $timestamp .
                ": " . json_encode($bal2->toArray()) . " \n");
            throw new Exception("Cannot add balance at " . $timestamp
                . " as there is already a balance on this period " . $bal2->id);
        }

        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '<', '9999-12-31');
        $bal2 = $query->first();
        return $bal2;
    }

    public function balanceAsOf($asOf) {
        $accountBalanceRepo = \App::make(AccountBalanceRepository::class);
        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $this->account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '<', $asOf)
            ->whereDate('start_dt', '>=', $asOf);
        $bal = $query->first();
        return $bal;
    }

    protected function validateLatestBalance(mixed $accountBalanceRepo, mixed $account_id, $timestamp): mixed
    {
        $query = $accountBalanceRepo->makeModel()->newQuery()
            ->where('account_id', $account_id)
            ->where('type', 'OWN')
            ->whereDate('end_dt', '=', '9999-12-31');
        $bal = $query->first();

        if ($bal != null) {
            if ($bal->start_dt > $timestamp) {
                throw new Exception("Cannot add balance at " . $timestamp
                    . " before previous balance " . $bal->start_dt);
            }
        }
        return $bal;
    }

    private function addCashToFund(Fund $fund, $value, \Carbon\Carbon|string $timestamp)
    {
        Log::debug("Adding cash (" . $value . ") to fund " . $fund->id . " at " . $timestamp);
        $timestamp = $this->timestamp;
        $port = $fund->portfolios()->first();
        $source = $port->source;

        // get cash asset
        $cashAsset = AssetExt::getCashAsset();
        Log::debug("Cash asset: " . json_encode($cashAsset));

        // create an portfolio assets api object
        $controller = app(PortfolioAssetAPIControllerExt::class);

        // create a new portfolio asset
        $pa = $controller->getQuery($source, $cashAsset->id, $timestamp);
        // validate if pa end date is high date
        if ($pa->end_dt != '9999-12-31') {
            throw new Exception("Cannot add cash to fund " . $fund->id . " at " . $timestamp
                . " as its not the latest position:  " . json_encode($pa));
        }
        Log::debug("Portfolio asset: " . json_encode($pa));
        $curValue = $pa->value;
        $controller->insertHistorical($source, $cashAsset->id, $timestamp, $value + $curValue, 'position');
    }

    protected function createMatching(AccountExt $account, bool $verbose, float $shareValue, float $allShares, float $availableShares): void
    {
        $rss = new TransactionResource($this);
        $input = $rss->toArray(null);

        $input['type'] = 'MAT';
        $descr = "Match transaction $this->id";
        $repo = \App::make(TransactionRepository::class);
        foreach ($account->accountMatchingRules()->get() as $amr) {
            $matchValue = $amr->match($this);
            if ($verbose) {
                Log::debug("AMR " . json_encode($amr) . " " . $matchValue . "\n");
                Log::debug("MR " . json_encode($amr->matchingRule()->get()) . "\n");
            }
            if ($matchValue > 0) {
                $mr = $amr->matchingRule()->first();
                $input['value'] = $matchValue;
                $input['status'] = 'C';
                $input['shares'] = $matchValue / $shareValue;
                $input['descr'] = "$descr with $mr->name ($mr->id)";
                $allShares += $input['shares'];
                if ($availableShares < $allShares) {
                    throw new Exception("Fund does not have enough shares ($availableShares) to support purchase of ($allShares)");
                }

                if ($verbose) Log::debug("MATCHTRAN " . json_encode($input) . "\n");
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
    }

}
