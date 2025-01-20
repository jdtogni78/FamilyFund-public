<?php

namespace App\Http\Controllers\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\CashDepositExt;
use App\Models\DepositRequestExt;
use App\Models\TradePortfolioExt;
use App\Models\TransactionExt;
use App\Http\Controllers\Traits\IBFlexQueriesTrait;
use App\Http\Controllers\Traits\TransactionTrait;
use Illuminate\Support\MessageBag;

trait CashDepositTrait
{
    use IBFlexQueriesTrait, TransactionTrait;
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
        
        $transactions = [];
        foreach ($data as $row) {
            try { 
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
                } else {
                    Log::info('Cash deposit ' . $descr . ' not processed yet');
                }

                $cashDeposit = CashDepositExt::where('account_id', $fundAccount->id)
                    ->where('amount', $amount)
                    ->where('date', null)
                    ->whereIn('status', [CashDepositExt::STATUS_PENDING, CashDepositExt::STATUS_ALLOCATED])
                    ->first();

                $entry = [];
                if (!$cashDeposit) {
                    Log::info('Cash deposit ' . $descr . ' not found, creating new');
                    $cashDeposit = CashDepositExt::create([
                        'account_id' => $fundAccount->id,
                        'date' => $date,
                        'amount' => $amount,
                        'description' => $descr,
                        'status' => CashDepositExt::STATUS_DEPOSITED,
                    ]);
                } else {
                    Log::info('Cash deposit ' . $descr . ' found, updating');
                    $cashDeposit->date = $date;
                    $cashDeposit->description = $descr;

                    if ($cashDeposit->status == CashDepositExt::STATUS_ALLOCATED) {
                        $deposits = $this->processDeposits($cashDeposit);
                        $transactions = array_merge($transactions, $deposits['transactions']);
                        unset($deposits['transactions']);
                        $entry = array_merge($entry, $deposits);

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
                $transactions[] = $transaction_data;
                $cashDeposit->save();

                $entry['cash_deposit'] = $cashDeposit;
                if (isset($entry['deposits'])) {
                    Log::info('deposits: ' . json_encode($entry['deposits']));
                }
                $ret[] = $entry;
                $successes[] = $cashDeposit->id;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        // add transactions from matches to transactions array
        foreach ($transactions as $transaction_data) {
            Log::info('transaction: ' . json_encode($transaction_data));
            if (isset($transaction_data['matches'])) {
                foreach ($transaction_data['matches'] as $match) {
                    Log::info('match: ' . json_encode($match));
                    $transactions[] = $match;
                }
            }
        }
        return [
            'successes' => $successes,
            'errors' => $errors,
            'data' => $ret,
            'transactions' => $transactions,
        ];
    }

    public function processDeposits(CashDepositExt $cashDeposit) {
        Log::info('processDeposits for cash deposits ' . $cashDeposit->id);
        $deposits = $cashDeposit->depositRequests;
        $data = [];
        $totalDeposits = 0;
        $transactions = [];
        foreach ($deposits as $deposit) {
            $error = null;
            Log::info('processDeposits for deposit ' . $deposit->id);
            if ($deposit->status != DepositRequestExt::STATUS_APPROVED) {
                Log::info('processDeposits for deposit ' . $deposit->id . ' is not approved (status: ' . $deposit->status_string() . ')');
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
            } else {
                Log::info('processDeposits for deposit ' . $deposit->id . ' is approved');
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
                $totalDeposits += $deposit->amount;

                $transaction_data = $transaction->processPending();
                $transactions[] = $transaction_data;
            }
            $d = ['deposit' => $deposit];
            if ($error) {
                $d['error'] = $error;
            }
            $data[] = $d;
        }
        if ($totalDeposits > $cashDeposit->amount) {
            throw new \Exception('Total amount of deposit requests (' . $totalDeposits 
                . ') exceeds the cash deposit amount (' . $cashDeposit->amount . ')');
        }
        return [
            'deposits' => $data,
            'total_deposits' => $totalDeposits,
            'unassigned' => $cashDeposit->amount - $totalDeposits,
            'transactions' => $transactions,
        ];
    }

    public function assignCashDeposit($id, $request)
    {
        Log::info('assignCashDeposit id: ' . $id);
        $cashDeposit = CashDepositExt::find($id);
        $unassigned = $request->unassigned;
        Log::info('unassigned: ' . $unassigned);
        Log::info('cashDeposit->amount: ' . $cashDeposit->amount);
        Log::info('cashDeposit->status: ' . $cashDeposit->status);
        if ($unassigned > $cashDeposit->amount) {
            throw new \Exception('Unassigned amount exceeds the cash deposit amount');
        }
        if ($cashDeposit->status == CashDepositExt::STATUS_CANCELLED || $cashDeposit->status == CashDepositExt::STATUS_COMPLETED) {
            throw new \Exception('Cash deposit is already completed or cancelled');
        }

        DB::transaction(function () use ($cashDeposit, $unassigned, $request) {
            $totalAmount = $unassigned;
            Log::info('unassigned: ' . $unassigned);
            Log::info('totalAmount: ' . $totalAmount);
            if (isset($request->deposits) && count($request->deposits) > 0) {
                Log::info('request->deposits: ' . count($request->deposits));
                foreach ($request->deposits as $deposit) {
                    Log::info('deposit: ' . json_encode($deposit));
                    $deposit['date'] = $cashDeposit->date;
                    $deposit['status'] = DepositRequestExt::STATUS_APPROVED;
                    $deposit['cash_deposit_id'] = $cashDeposit->id;
                    $cashDeposit->depositRequests()->create($deposit);
                    $totalAmount += $deposit['amount'];
                    Log::info('totalAmount: ' . $totalAmount);
                }
            }
            Log::info('totalAmount: ' . $totalAmount);
            if (isset($request->deposit_ids) && count($request->deposit_ids) > 0) {
                Log::info('deposit_ids: ' . count($request->deposit_ids));
                foreach ($request->deposit_ids as $depositId) {
                    $depositRequest = DepositRequestExt::find($depositId);
                    $depositRequest->status = DepositRequestExt::STATUS_APPROVED;
                    $depositRequest->cash_deposit_id = $cashDeposit->id;
                    $depositRequest->save();
                    $totalAmount += $depositRequest->amount;
                    Log::info('totalAmount: ' . $totalAmount);
                }
            }
            // round to 2 decimal places
            $totalAmount = round($totalAmount, 2);
            $cashDeposit->amount = round($cashDeposit->amount, 2);
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
        });
    }


    public function getCashDeposits($tws_query_id, $tws_token) {
        $response = $this->getIBFlexQuery($tws_query_id, $tws_token);
        $content = $response->body();
        
        // save response to file, under /storage/app/cash_deposits
        $filename = 'cash_deposits_' . date('Y-m-d_H-i-s') . '.txt';
        file_put_contents(storage_path('app/cash_deposits/' . $filename), $content); 

        // delete file after 3 months
        $this->deleteFileAfter($filename, 3 * 30 * 24 * 60 * 60);
        return $content;
    }

    public function deleteFileAfter($filename, $delta = 3 * 30 * 24 * 60 * 60) {
        $path = storage_path('app/cash_deposits/' . $filename);
        if (file_exists($path) && time() - filemtime($path) > $delta) {
            unlink($path);
        }
    }   

    private function executeCashDeposits($tradePortfolio, $dry_run=true)
    {
        $ret = [];
        DB::beginTransaction();
        try {
            $content = $this->getCashDeposits($tradePortfolio->tws_query_id, $tradePortfolio->tws_token);
            Log::info('TradePortfolioControllerExt::preview: content: ' . $content);
            $ret = $this->parseCashDepositString($content);
            $data = $ret['data'];
            $transactions = $ret['transactions'];

            foreach ($transactions as $key => $transaction) {
                $api1 = $this->getPreviewData($transaction);
                $transactions[$key] = $api1;
            }
            $ret['transactions'] = $transactions;

            Log::info('TradePortfolioControllerExt::preview: data: ' . json_encode($data));
            Log::info('TradePortfolioControllerExt::preview: transactions: ' . json_encode($transactions));
            if ($dry_run) {
                DB::rollBack();
            } else {
                // send emails for each transaction
                foreach ($transactions as $transaction_data) {
                    $api1 = $this->getPreviewData($transaction_data);
                    $this->sendTransactionConfirmation($api1);
                }
                foreach ($data as $item) {
                    // send emails for each cash deposit
                    // $this->sendCashDepositConfirmation($item);
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        return $ret;
    }
}
