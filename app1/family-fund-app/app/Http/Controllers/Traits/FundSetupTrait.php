<?php

namespace App\Http\Controllers\Traits;

use App\Models\AccountExt;
use App\Models\PortfolioExt;
use App\Models\TransactionExt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait FundSetupTrait
{
    use TransactionTrait;

    /**
     * Create a fund with account, portfolio(s), and initial transaction
     *
     * @param array $input User input from request
     * @param bool $dry_run If true, rolls back changes after creation (for preview)
     * @return array Setup data including fund, account, portfolios, transaction, and balance
     * @throws \Exception
     */
    protected function setupFund(array $input, bool $dry_run = false): array
    {
        DB::beginTransaction();

        try {
            Log::info('FundSetupTrait::setupFund: ' . json_encode($input));

            // 1. Create fund
            $fund = $this->fundRepository->create([
                'name' => $input['name'],
                'goal' => $input['goal'] ?? null,
            ]);

            // 2. Create fund account (no user_id)
            $accountNickname = $input['account_nickname']
                ?? ($fund->name . ' Fund Account');

            // Truncate nickname to fit 100 character limit
            $accountNickname = substr($accountNickname, 0, 100);

            $account = AccountExt::create([
                'fund_id' => $fund->id,
                'user_id' => null,
                'nickname' => $accountNickname,
                'code' => 'F' . $fund->id,
            ]);

            // 3. Create portfolio(s)
            $portfolios = [];
            $portfolioSources = is_array($input['portfolio_source'])
                ? $input['portfolio_source']
                : [$input['portfolio_source']];

            foreach ($portfolioSources as $source) {
                $portfolios[] = PortfolioExt::create([
                    'fund_id' => $fund->id,
                    'source' => $source,
                ]);
            }

            // 4. Create initial transaction (if requested)
            $transaction = null;
            $accountBalance = null;
            $transactionData = null;

            if ($input['create_initial_transaction'] ?? true) {
                $initialShares = $input['initial_shares'] ?? 1;
                $initialValue = $input['initial_value'] ?? 0.01;
                $initialTimestamp = $input['initial_transaction_date'] ?? now();

                // Build transaction input
                // For fund initial transactions, use FLAGS_CASH_ADDED
                $tranInput = [
                    'account_id' => $account->id,
                    'type' => TransactionExt::TYPE_INITIAL,
                    'status' => TransactionExt::STATUS_PENDING,
                    'value' => $initialValue,
                    'shares' => $initialShares,
                    'timestamp' => $initialTimestamp,
                    'source' => 'API',
                    'descr' => $input['transaction_description']
                        ?? 'Initial fund setup',
                    'flags' => TransactionExt::FLAGS_CASH_ADDED,
                ];

                // Use TransactionTrait::createTransaction which calls processPending
                $transactionData = $this->createTransaction($tranInput, $dry_run);

                $transaction = $transactionData['transaction'];
                $accountBalance = $transaction->balance;

                // Eager load relations for dry_run mode (needed before rollback)
                if ($dry_run && $accountBalance) {
                    $accountBalance->load('transaction', 'account.fund');
                }
            }

            // Gather setup data
            $setupData = [
                'fund' => $fund,
                'account' => $account,
                'portfolios' => $portfolios,
                'transaction' => $transaction,
                'accountBalance' => $accountBalance,
                'transactionData' => $transactionData,
            ];

            if ($dry_run) {
                Log::info('FundSetupTrait::setupFund: dry_run mode - rolling back');
                DB::rollBack();
            } else {
                Log::info('FundSetupTrait::setupFund: committing changes');
                DB::commit();
            }

            return $setupData;

        } catch (\Exception $e) {
            Log::error('FundSetupTrait::setupFund: error - ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }
}
