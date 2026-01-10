<?php

namespace Tests\Fixtures;

use App\Models\CashDepositExt;
use App\Models\DepositRequestExt;
use App\Models\TransactionExt;
use Carbon\Carbon;
use Tests\DataFactory;

/**
 * Pre-configured test fixtures for complex test scenarios.
 * Use these to ensure consistent, complete test data setup.
 */
class TestFixtures
{
    /**
     * Creates a complete fund report fixture with all required data:
     * - Fund with portfolio and initial transaction
     * - Fund account with email_cc
     * - User account with email_cc
     * - Matching rules and transaction matching
     * - Assets with prices
     * - Multiple transactions for report generation
     */
    public static function fundReportFixture(): DataFactory
    {
        $factory = new DataFactory();

        // Create fund with portfolio (includes initial transaction)
        $factory->createFund(1000, 1000, '2022-01-01');

        // Set fund account email for report delivery
        $factory->fundAccount->email_cc = 'fund-admin@test.local';
        $factory->fundAccount->save();

        // Create user with account and matching rules
        $factory->createUser();
        $factory->userAccount->email_cc = 'user@test.local';
        $factory->userAccount->save();

        // Create matching rule and account matching
        $factory->createMatching(1000, 50, '2022-01-01', '9999-12-31');

        // Create assets with prices (required for portfolio valuation)
        $factory->createAssetWithPrice(100.00);
        $factory->createAssetWithPrice(50.00);

        // Create transactions (required for report generation)
        $factory->createTransaction(
            500,
            $factory->userAccount,
            TransactionExt::TYPE_PURCHASE,
            TransactionExt::STATUS_CLEARED,
            null,
            '2022-02-01'
        );

        // Create matching transaction
        $factory->createTransactionWithMatching(200, 100);

        return $factory;
    }

    /**
     * Creates a complete cash deposit fixture with:
     * - Fund with trade portfolio (with account_name matching CSV data)
     * - Multiple users with accounts
     * - Cash deposit with deposit requests in various states
     */
    public static function cashDepositFixture(): DataFactory
    {
        $factory = new DataFactory();

        // Create fund
        $factory->createFund(1000, 1000, '2021-01-01');

        // Create trade portfolio with account_name matching CSV test data
        $tp = $factory->createTradePortfolio(Carbon::parse('2021-01-01'));
        $tp->account_name = 'U0000001';  // Must match CSV ClientAccountID
        $tp->portfolio_id = $factory->portfolio->id;
        $tp->save();

        return $factory;
    }

    /**
     * Creates cash deposit fixture with deposit requests for testing allocation flow.
     */
    public static function cashDepositWithRequestsFixture(): DataFactory
    {
        $factory = self::cashDepositFixture();

        // Create users
        $user1 = $factory->createUser();
        $user2 = $factory->createUser();

        // Create cash deposit
        $cd = $factory->createCashDeposit(96.83);
        $cd->status = CashDepositExt::STATUS_ALLOCATED;
        $cd->date = null;
        $cd->save();

        // Create deposit requests in various states
        $dr1 = $factory->createDepositRequest($cd, $user1->accounts[0], 30.00);
        $dr1->status = DepositRequestExt::STATUS_APPROVED;
        $dr1->save();

        $dr2 = $factory->createDepositRequest($cd, $user2->accounts[0], 36.83);
        $dr2->status = DepositRequestExt::STATUS_APPROVED;
        $dr2->save();

        $dr3 = $factory->createDepositRequest($cd, $user2->accounts[0], 10.00);
        $dr3->status = DepositRequestExt::STATUS_REJECTED;
        $dr3->save();

        $dr4 = $factory->createDepositRequest($cd, $user2->accounts[0], 11.00);
        $dr4->status = DepositRequestExt::STATUS_COMPLETED;
        $dr4->save();

        $dr5 = $factory->createDepositRequest($cd, $user2->accounts[0], 12.00);
        $dr5->status = DepositRequestExt::STATUS_PENDING;
        $dr5->save();

        return $factory;
    }

    /**
     * Creates a minimal fund fixture for simple tests.
     */
    public static function minimalFundFixture(): DataFactory
    {
        $factory = new DataFactory();
        $factory->createFund(1000, 1000, '2022-01-01');
        return $factory;
    }

    /**
     * Creates a fund with user and matching rules but no transactions.
     */
    public static function fundWithMatchingFixture(): DataFactory
    {
        $factory = new DataFactory();
        $factory->createFund(1000, 1000, '2022-01-01');
        $factory->createUser();
        $factory->createMatching(1000, 50, '2022-01-01', '9999-12-31');
        return $factory;
    }
}
