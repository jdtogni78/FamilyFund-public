<?php

namespace App\Models;

use App\Http\Controllers\APIv1\PortfolioAssetAPIControllerExt;
use App\Http\Controllers\Traits\VerboseTrait;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Repositories\AccountBalanceRepository;
use App\Repositories\PortfolioAssetRepository;
use App\Repositories\ScheduledJobRepository;
use App\Repositories\TransactionRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Nette\Utils\DateTime;
use Illuminate\Support\Facades\DB;

/**
 * Class TransactionExt
 * @package App\Models
 */
class TransactionExt extends Transaction
{
    use VerboseTrait;

    public const TYPE_PURCHASE = 'PUR';
    public const TYPE_INITIAL = 'INI';
    public const TYPE_SALE = 'SAL';
    public const TYPE_MATCHING = 'MAT';
    public const TYPE_BORROW = 'BOR';
    public const TYPE_REPAY = 'REP';

    public const STATUS_PENDING = 'P';
    public const STATUS_CLEARED = 'C';
    public const STATUS_SCHEDULED = 'S';

    public const FLAGS_ADD_CASH = 'A';
    public const FLAGS_CASH_ADDED = 'C';
    public const FLAGS_NO_MATCH = 'U';

    public static array $typeMap = [
        TransactionExt::TYPE_PURCHASE => 'Purchase',
        TransactionExt::TYPE_INITIAL => 'Initial Value',
        TransactionExt::TYPE_SALE => 'Sale',
        TransactionExt::TYPE_MATCHING => 'Matching',
        TransactionExt::TYPE_BORROW => 'Borrow',
        TransactionExt::TYPE_REPAY => 'Repay',
    ];
    public static array $statusMap = [
        TransactionExt::STATUS_PENDING => 'Pending',
        TransactionExt::STATUS_CLEARED => 'Cleared',
        TransactionExt::STATUS_SCHEDULED => 'Scheduled',
    ];
    public static array $flagsMap = [
        null => 'No Flags',
        TransactionExt::FLAGS_ADD_CASH => 'Add Cash Position',
        TransactionExt::FLAGS_CASH_ADDED => 'Cash Already Added',
        TransactionExt::FLAGS_NO_MATCH => 'No matching',
    ];

    /**
     * @throws \Exception
     */
    public function createBalance($shares, $timestamp)
    {
        $this->debug("Creating Balance: " . json_encode($this->toArray()));
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
            $this->debug("OLD BAL " . json_encode($bal));
        } else if ($otherBalance != null) {
            $this->debug("BALANCE FOUND: ".json_encode($otherBalance->toArray()));
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
        $this->debug("NEW BAL " . json_encode($newBal));
        return [$newBal, $oldShares];
    }

    /**
     * @throws Exception
     */
    public function processPending()
    {
        Log::info("Processing Transaction: " . json_encode($this->toArray()));
        if ($this->status === TransactionExt::STATUS_SCHEDULED) {
            Log::info("Nothing to do for scheduled transaction: " . $this->id);
            return;
        }
        $timestamp = $this->timestamp;
        if ($timestamp->gt(Carbon::today())) {
            Log::warning("Keep pending state as transaction is in the future: " . $timestamp);
            return;
        }

        $verbose = false;
        /** @var AccountExt $account */
        $account = $this->account()->first();
        /** @var FundExt $fund */
        $fund = $account->fund()->first();
        /** @var float $value */
        $value = $this->value;

        $shareValue = $fund->shareValueAsOf($timestamp);
        $fundAvailableShares = $fund->unallocatedShares($timestamp);
        $acctAvailableShares = $account->sharesAsOf($timestamp);

        Log::debug("Transaction :".json_encode($this->toArray()));

        if ($shareValue == 0) {
            if ($this->type != TransactionExt::TYPE_INITIAL) {
                throw new Exception("Cannot Process Transaction: Share price not available for fund " .
                    $fund->name . " at " . $timestamp);
                // shares should have been provided
            }
        } else {
            $this->shares = $value / $shareValue;
        }

        $isFundAccount = $account->user_id == null;
        $allShares = $this->shares;
        $fundCash = null;
        $newBal = null;
        if (!$isFundAccount) {
            Log::debug("Acct Tran: Validate flags & shares: '" . $this->flags . "' " . $this->shares . " " . $allShares);
            if (!($this->flags == null || $this->flags == TransactionExt::FLAGS_NO_MATCH)) {
                throw new Exception("Unexpected: Regular transactions support no flags or no match only: " . $this->flags);
            }
            // check if fund has enough shares (unless its a fund transaction)
            if ($this->type == TransactionExt::TYPE_PURCHASE && $fundAvailableShares < $allShares) {
                throw new Exception("Fund does not have enough shares ($fundAvailableShares) to support purchase of ($allShares)");
            }
            if ($this->type == TransactionExt::TYPE_SALE) {
                if ($acctAvailableShares < $allShares) {
                    throw new Exception("Account does not have enough shares ($acctAvailableShares) to support sale of ($allShares)");
                }
                if ($this->flags == null) {
                    throw new Exception("Unexpected: Sale transactions must have the no match flag");
                }
            }
        } else {
            if ($this->type == TransactionExt::TYPE_SALE) {
                throw new Exception("Fund transactions cannot be sales");
            }
            Log::debug("Fund Tran: Validate flags & shares: '" . $this->flags . "' ");
            // validate that flags are in (A, C)
            // calculate shares, considering the value is added to the fund first
            switch ($this->flags) {
                case TransactionExt::FLAGS_ADD_CASH:
                    // share value is correct, no need to recalculate
                    // just need to add the cash to cash position
                    $fundCash = $this->addCashToFund($fund, $value, $timestamp);
                    break;

                case TransactionExt::FLAGS_CASH_ADDED: // cash already added
                    // recalculate share value, discounting the value
                    // a deposit was already made to the fund, artificially increasing its share value
                    Log::debug("Cash already added: " . $value . " " . $shareValue . " " . $timestamp);
                    if ($this->type !== TransactionExt::TYPE_INITIAL) {
                        /** @var float $fundShares */
                        $fundShares = $fund->sharesAsOf($timestamp);
                        /** @var float $fundValue */
                        $fundValue = $fund->valueAsOf($timestamp);
                        $shareValue = ($fundValue - $value) / $fundShares;
                        $this->shares = $value / $shareValue;
                    } else {
                        /** @var float $fundShares */
                        $fundShares = $this->shares;
                        /** @var float $fundValue */
                        $fundValue = $value;
                        $shareValue = $value / $fundShares;
                    }
                    $allShares = $this->shares;
                    Log::debug("Recalculated: " . $fundValue . " " . $fundShares . " " .
                        $shareValue . " " . $this->shares);
                    break;

                default:
                    throw new Exception("Unexpected: Fund transactions must have flags: A or C");
            }
        }
        if ($this->type == TransactionExt::TYPE_SALE) {
            if ($this->shares > 0) $this->shares = -$this->shares;
            if ($this->value > 0) $this->value = -$this->value;
            if ($allShares > 0) $allShares = -$allShares;
        }
        list($newBal, $oldShares) = $this->createBalance($this->shares, $timestamp);

        $noMatch = $this->flags == TransactionExt::FLAGS_NO_MATCH;
        $createMatch = !($isFundAccount || $noMatch);
        $this->debug("Create Match: '" . $createMatch . "' '" . $isFundAccount . "' '" . $noMatch . "'");
        $match = null;
        if ($createMatch) {
            $match = $this->createMatching($account, $verbose, $shareValue, $allShares, $fundAvailableShares, $acctAvailableShares);
        }

        $this->status = TransactionExt::STATUS_CLEARED;
        $this->save();
        return [$newBal, $oldShares, $fundCash, $match, $shareValue];
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
            Log::error("There is already a balance on this period " . $timestamp .
                ": " . json_encode($bal2->toArray()));
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
                Log::error("There is already a balance on this period " . $timestamp .
                    ": " . json_encode($bal->toArray()));
                throw new Exception("Cannot add balance at " . $timestamp
                    . " before previous balance " . $bal->start_dt);
            }
        }
        return $bal;
    }

    private function addCashToFund(FundExt $fund, $value, \Carbon\Carbon|string $timestamp)
    {
        $this->debug("Adding cash (" . $value . ") to fund " . $fund->id . " at " . $timestamp);
        $timestamp = $this->timestamp;
        $port = $fund->portfolios()->first();
        $source = $port->source;

        try {
            list($cashAsset, $controller, $pa) = $this->getCashPortfolioAsset($source, $timestamp);
        } catch (Exception $e) {
            $this->debug("Cash asset not found, creating it");
            self::createCashPortfolioAsset($source, $value, $timestamp);
            list($cashAsset, $controller, $pa) = $this->getCashPortfolioAsset($source, $timestamp);
            $pa->position = 0; // hack to avoid validation error
        }
        $this->debug("Cash asset found: " . json_encode($pa));

        // validate if pa end date is high date
        if (!str_starts_with($pa->end_dt, '9999-12-31')) {
            throw new Exception("Cannot add cash to fund " . $fund->id . " at " . $timestamp
                . " as its not the latest position:  " . json_encode($pa));
        }

        // update the cash port asset value
        $curValue = $pa->position;
        $this->debug("Adding cash to fund " . $fund->id . " at " . $timestamp . " " . $curValue . " " . $value);
        $ret = $controller->insertHistorical($source, $cashAsset->id, $timestamp, $value + $curValue, 'position');
        return [$ret, $curValue];
    }

    protected function createMatching(AccountExt $account, bool $verbose, float $shareValue, float $allShares, float $fundAvailableShares): array
    {
        $this->debug("Creating matching for " . $this->id . " " . $account->id . " " . $shareValue . " " . $allShares . " " . $fundAvailableShares);
        $rss = new TransactionResource($this);
        $input = $rss->toArray(null);

        $input['type'] = 'MAT';
        $descr = "Match transaction $this->id";
        $repo = \App::make(TransactionRepository::class);
        $ret = [];
        foreach ($account->accountMatchingRules()->get() as $amr) {
            $matchValue = $amr->match($this);
            if ($verbose) {
                $this->debug("AMR " . json_encode($amr) . " " . $matchValue);
                $this->debug("MR " . json_encode($amr->matchingRule()->get()));
            }
            if ($matchValue > 0) {
                $mr = $amr->matchingRule()->first();
                $input['value'] = $matchValue;
                $input['status'] = TransactionExt::STATUS_CLEARED;
                $input['shares'] = $matchValue / $shareValue;
                $input['descr'] = "$descr with $mr->name ($mr->id)";
                $allShares += $input['shares'];
                if ($fundAvailableShares < $allShares) {
                    throw new Exception("Fund does not have enough shares ($fundAvailableShares) to support purchase of ($allShares)");
                }

                $this->debug("MATCHTRAN " . json_encode($input));
                /** @var TransactionExt $matchTran */
                $matchTran = $repo->create($input);
                $matchBal = $matchTran->createBalance($matchTran->shares, $matchTran->timestamp);

                $match = TransactionMatching::factory()
                    ->for($mr)
                    ->create([
                        'transaction_id' => $matchTran->id,
                        'reference_transaction_id' => $this->id
                    ]);

                $ret[] = [$matchBal, $matchTran];
            }
        }
        return $ret;
    }

    public static function getCashPortfolioAsset(mixed $source, \Carbon\Carbon|string $timestamp): array
    {
        // get cash asset
        $cashAsset = AssetExt::getCashAsset();
//        Log::debug("Cash asset: " . json_encode($cashAsset));

        // create an portfolio assets api object
        $controller = app(PortfolioAssetAPIControllerExt::class);

        // Get the portfolio asset at the timestamp
        $pa = $controller->getPortfolioAsset($source, $cashAsset, $timestamp);

        // throws if empty
        if (empty($pa) || count($pa) == 0) {
            throw new Exception("Cannot find cash asset value: " . $cashAsset->id);
        }
        return array($cashAsset, $controller, $pa[0]);
    }

    public static function createCashPortfolioAsset(string $source, $value, \Carbon\Carbon|string $timestamp)
    {
        $cashAsset = AssetExt::getCashAsset();
        $controller = app(PortfolioAssetAPIControllerExt::class);
        $controller->insertHistorical($source, $cashAsset->id, $timestamp, $value, 'position');
    }

    public function scheduledJobs()
    {
        return ScheduledJobExt::scheduledJobs(ScheduledJobExt::ENTITY_TRANSACTION, $this->id);
    }
}
