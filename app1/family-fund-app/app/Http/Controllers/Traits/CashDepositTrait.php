<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\CashDepositExt;
use App\Models\DepositRequestExt;
use App\Models\TradePortfolioExt;
use App\Models\TransactionExt;

trait CashDepositTrait
{
    public function csvFromString($str) {
        $data = explode("\n", $str);
        $headers = array_shift($data);
        // use str_getcsv to parse the csv
        $data = array_map(function($row) use ($headers) {
            // remove duplicated headers
            if ($row == $headers) {
                return null;
            }
            return str_getcsv($row);
        }, $data);

        $data = array_filter($data, function($row) {
            return $row !== null && count($row) > 1;
        });

        // combine headers and data
        $headers = str_getcsv($headers);
        $data = array_map(function($row) use ($headers) {
            return array_combine($headers, $row);
        }, $data);

        // reindex data
        $data = array_values($data);
        return [$headers, $data];
    }

    public function csvFromFile($file) {
        $handle = fopen($file, "r");
        $data = [];
        $headers = [];
        while (($row = fgetcsv($handle)) !== FALSE) {
            // do something with row values
            if (empty($headers)) {
                $headers = $row;
            } else {
                if ($row == $headers) {
                    continue;
                }
                $data[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
        return [$headers, $data];
    }

    public function parseCashDepositString($str) {
        list($headers, $data) = $this->csvFromString($str);
        return $this->parseCashDeposit($headers, $data);
    }

    public function parseCashDepositFile($file) {
        list($headers, $data) = $this->csvFromFile($file);
        return $this->parseCashDeposit($headers, $data);
    }

    public function parseCashDeposit($headers, $data) {
        /* Sample data:        
"ClientAccountID","Description","Date/Time","SettleDate","Amount","Type","AccountAlias","CurrencyPrimary","FXRateToBase","Multiplier","AvailableForTradingDate","TransactionID","ClientReference","LevelOfDetail"
"U0000010","CASH RECEIPTS / ELECTRONIC FUND TRANSFERS","2024-12-24","2024-12-24","20000","Deposits/Withdrawals","FamilyFund1","USD","1","0","2024-12-30","30656602765","","DETAIL"
        */
        $errors = [];
        $successes = [];
        $ret = [];
        
        foreach ($data as $row) {
            $deposits = [];
            $tradePort = TradePortfolioExt::where('account_name', $row['ClientAccountID'])->first();
            if (!$tradePort) {
                $errors[] = 'Trade portfolio not found for fund account: ' . $row['ClientAccountID'];
                continue;
            }

            $fund = $tradePort->portfolio->fund;
            $fundAccount = $fund->fundAccount();
            $descr = $row['TransactionID'] . ' - ' . $row['ClientReference'];
            $date = Carbon::parse($row['SettleDate'])->format('Y-m-d');
            $amount = floatval($row['Amount']);

            // check if it was already processed
            $alreadyProcessed = CashDepositExt::where('account_id', $fundAccount->id)
                ->where('amount', $amount)
                ->where('date', $date)
                ->where('description', $descr)
                ->whereIn('status', [CashDepositExt::STATUS_COMPLETED, CashDepositExt::STATUS_DEPOSITED])
                ->first();
            if ($alreadyProcessed) {
                Log::info('Cash deposit ' . $alreadyProcessed->id . ' already processed');
                continue;
            }

            $cashDeposit = CashDepositExt::where('account_id', $fundAccount->id)
                ->where('amount', $amount)
                ->where('date', null)
                ->whereIn('status', [CashDepositExt::STATUS_PENDING, CashDepositExt::STATUS_ALLOCATED])
                ->first();

            if (!$cashDeposit) {
                $cashDeposit = CashDepositExt::create([
                    'account_id' => $fundAccount->id,
                    'date' => $date,
                    'amount' => $amount,
                    'description' => $descr,
                    'status' => CashDepositExt::STATUS_DEPOSITED,
                ]);
            } else {
                $cashDeposit->date = $date;
                $cashDeposit->description = $descr;

                if ($cashDeposit->status == CashDepositExt::STATUS_ALLOCATED) {
                    $deposits = $this->processDeposits($cashDeposit);
                    $cashDeposit->status = CashDepositExt::STATUS_COMPLETED;
                } else {
                    $cashDeposit->status = CashDepositExt::STATUS_DEPOSITED;
                }

                $cashDeposit->save();
            }
            
            $transaction = TransactionExt::create([
                'account_id' => $fundAccount->id,
                'timestamp' => $date,
                'value' => $amount,
                'type' => TransactionExt::TYPE_PURCHASE,
                'flags' => TransactionExt::FLAGS_CASH_ADDED, // assumes deposit shows after cash is settled/added
                'descr' => "Cash Deposit " . $cashDeposit->id,
                'status' => TransactionExt::STATUS_PENDING,
            ]);
            $cashDeposit->transaction_id = $transaction->id;
            $transaction_data = $transaction->processPending();
            
            $cashDeposit->save();
            $entry = [
                'cash_deposit' => $cashDeposit,
                'transaction' => $transaction,
                'transaction_data' => $transaction_data,
                'deposits' => $deposits,
            ];
            $ret[] = $entry;
            $successes[] = $cashDeposit->id;
        }
        return [$successes, $errors, $ret];
    }

    public function processDeposits(CashDepositExt $cashDeposit) {
        $deposits = $cashDeposit->depositRequests;
        $data = [];
        $totalAmount = 0;
        foreach ($deposits as $deposit) {
            if ($deposit->status != DepositRequestExt::STATUS_APPROVED) {
                switch ($deposit->status) {
                    case DepositRequestExt::STATUS_PENDING:
                        $error = 'Deposit request ' . $deposit->id . 
                        ' rejected as was not approved before processing (status: ' . $deposit->status_string() . ')';
                        $deposit->status = DepositRequestExt::STATUS_REJECTED;
                        break;
                    case DepositRequestExt::STATUS_COMPLETED:
                        $error = 'Deposit request ' . $deposit->id . 
                        ' already completed - this is an inconsistent state (status: ' . $deposit->status_string() . ')';
                        break;
                    case DepositRequestExt::STATUS_REJECTED:
                        continue 2;
                }
                $data[] = [
                    'error' => $error,
                    'deposit' => $deposit,
                ];
            } else {
                $transaction = TransactionExt::create([
                    'account_id' => $deposit->account_id,
                    'value' => $deposit->amount,
                    'timestamp' => $cashDeposit->date,
                    'type' => TransactionExt::TYPE_PURCHASE,
                'descr' => "Cash Deposit " . $cashDeposit->id,
                'status' => TransactionExt::STATUS_PENDING,
                ]);
                $deposit->date = $cashDeposit->date;
                $deposit->status = DepositRequestExt::STATUS_COMPLETED;
                $deposit->transaction_id = $transaction->id;
                
                $deposit->save();
                $transaction_data = $transaction->processPending();
                $totalAmount += $deposit->amount;
                $data[] = [
                    'deposit' => $deposit,
                    'transaction' => $transaction,
                    'transaction_data' => $transaction_data,
                ];
            }
        }
        if ($totalAmount > $cashDeposit->amount) {
            throw new \Exception('Total amount of deposit requests (' . $totalAmount 
                . ') exceeds the cash deposit amount (' . $cashDeposit->amount . ')');
        }
        return [$data, $totalAmount, $cashDeposit->amount - $totalAmount];
    }

    public function assignCashDeposit($id, $request)
    {
        $cashDeposit = CashDepositExt::find($id);
        $unassigned = $request->unassigned;
        if ($unassigned > $cashDeposit->amount) {
            throw new \Exception('Unassigned amount exceeds the cash deposit amount');
        }
        if ($cashDeposit->status == CashDepositExt::STATUS_CANCELLED || $cashDeposit->status == CashDepositExt::STATUS_COMPLETED) {
            throw new \Exception('Cash deposit is already completed or cancelled');
        }

        DB::beginTransaction();
        $totalAmount = $unassigned;
        foreach ($request->deposits as $deposit) {
            $deposit['date'] = $cashDeposit->date;
            $deposit['status'] = DepositRequestExt::STATUS_APPROVED;
            $deposit['cash_deposit_id'] = $cashDeposit->id;
            $cashDeposit->depositRequests()->create($deposit);
            $totalAmount += $deposit['amount'];
        }
        foreach ($request->deposit_ids as $depositId) {
            $depositRequest = DepositRequestExt::find($depositId);
            $depositRequest->status = DepositRequestExt::STATUS_APPROVED;
            $depositRequest->cash_deposit_id = $cashDeposit->id;
            $depositRequest->save();
            $totalAmount += $depositRequest->amount;
        }
        if ($totalAmount != $cashDeposit->amount) {
            DB::rollBack();
            throw new \Exception('Total amount of deposit requests (' . $totalAmount 
                . ') does not match the cash deposit amount (' . $cashDeposit->amount . ')');
        }

        if ($cashDeposit->status == CashDepositExt::STATUS_DEPOSITED) {
            $cashDeposit->status = CashDepositExt::STATUS_COMPLETED;
        } else {
            $cashDeposit->status = CashDepositExt::STATUS_ALLOCATED;
        }
        $cashDeposit->save();
        DB::commit();
    }
}
