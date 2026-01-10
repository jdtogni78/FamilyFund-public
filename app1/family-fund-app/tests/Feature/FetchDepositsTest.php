<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\FetchDeposits;
use Illuminate\Support\Facades\Http;

/**
 * @group needs-data-refactor
 * Test has HTTP mock setup issues
 */
class FetchDepositsTest extends TestCase
{
    /**
     * Unit test with mocked IBFlex API responses.
     * Always runs in CI/CD.
     */
    public function test_fetch_deposits() {
        // Mock the IBFlex API responses
        Http::fake([
            // First request returns a reference code
            'ndcdyn.interactivebrokers.com/AccountManagement/FlexWebService/SendRequest*' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?>
                <FlexStatementResponse timestamp="01 January, 2024 10:00 AM EDT">
                    <Status>Success</Status>
                    <ReferenceCode>123456789</ReferenceCode>
                    <Url>https://ndcdyn.interactivebrokers.com/AccountManagement/FlexWebService/GetStatement</Url>
                </FlexStatementResponse>',
                200
            ),
            // Second request returns empty statement (no deposits)
            'ndcdyn.interactivebrokers.com/AccountManagement/FlexWebService/GetStatement*' => Http::response(
                '<?xml version="1.0" encoding="utf-8"?>
                <FlexQueryResponse queryName="cash_transactions" type="AF">
                    <FlexStatements count="1">
                        <FlexStatement accountId="TEST123" fromDate="2024-01-01" toDate="2024-01-31" period="LastMonth" whenGenerated="2024-01-31">
                            <CashTransactions />
                        </FlexStatement>
                    </FlexStatements>
                </FlexQueryResponse>',
                200
            ),
        ]);

        $fd = new FetchDeposits();
        $fd->handle();

        // Verify HTTP calls were made
        Http::assertSentCount(2);
    }

    /**
     * Integration test with real IBFlex API calls.
     * Skipped unless valid credentials are configured.
     *
     * Run manually with: php artisan test --filter=test_fetch_deposits_integration
     * Requires TWS_TOKEN and TWS_QUERY_ID in .env
     */
    public function test_fetch_deposits_integration() {
        $token = config('services.tws.token') ?? env('TWS_TOKEN');
        $queryId = config('services.tws.query_id') ?? env('TWS_QUERY_ID');

        if (empty($token) || empty($queryId)) {
            $this->markTestSkipped(
                'IBFlex integration test skipped: TWS_TOKEN and TWS_QUERY_ID not configured. ' .
                'Set these in .env to run real API calls.'
            );
        }

        $fd = new FetchDeposits();
        $fd->handle();

        // If we get here without exception, the API call succeeded
        $this->assertTrue(true);
    }
}

