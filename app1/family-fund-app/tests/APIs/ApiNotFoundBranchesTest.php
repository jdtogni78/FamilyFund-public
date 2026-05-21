<?php

namespace Tests\APIs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Walks the standard scaffolded API resources and exercises the
 * "if (empty($model)) sendError('not found', 404)" branch on show /
 * update / destroy. This is what most of the 32% gap in the standard
 * App\Http\Controllers\API\* classes is — the happy paths are covered
 * by the per-resource ApiTest classes, but the not-found branches are
 * silently missed. The assertion is loose (404 OR a non-2xx with the
 * standard error envelope) so the test is robust to slight controller
 * variations.
 */
class ApiNotFoundBranchesTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

    private const MISSING_ID = 99_999_999;

    /** @return array<string,array{string}> */
    public static function resources(): array
    {
        // Only the routes whose controller is the plain
        // App\Http\Controllers\API\* class — the *Ext / APIv1 variants
        // override show/update/destroy with their own logic and are
        // exercised by dedicated tests.
        return [
            'funds'                  => ['funds'],
            'accounts'               => ['accounts'],
            'assets'                 => ['assets'],
            'account_balances'       => ['account_balances'],
            'account_matching_rules' => ['account_matching_rules'],
            'matching_rules'         => ['matching_rules'],
            'portfolio_assets'       => ['portfolio_assets'],
            'users'                  => ['users'],
            'asset_change_logs'      => ['asset_change_logs'],
            'transaction_matchings'  => ['transaction_matchings'],
            'change_logs'            => ['change_logs'],
            'trade_portfolio_items'  => ['trade_portfolio_items'],
            'schedules'              => ['schedules'],
            'addresses'              => ['addresses'],
            'people'                 => ['people'],
            'id_documents'           => ['id_documents'],
            'phones'                 => ['phones'],
        ];
    }

    #[DataProvider('resources')]
    public function test_show_returns_not_found_for_missing_id(string $path): void
    {
        $resp = $this->json('GET', "/api/{$path}/" . self::MISSING_ID);

        $resp->assertStatus(404);
        $resp->assertJson(['success' => false]);
    }

    #[DataProvider('resources')]
    public function test_update_returns_not_found_for_missing_id(string $path): void
    {
        $resp = $this->json('PUT', "/api/{$path}/" . self::MISSING_ID, []);

        $this->assertContains(
            $resp->getStatusCode(),
            [404, 422],
            "PUT /api/{$path}/{missing} expected 404 (not found) or 422 (validation rejects empty), got {$resp->getStatusCode()}"
        );
    }

    #[DataProvider('resources')]
    public function test_destroy_returns_not_found_for_missing_id(string $path): void
    {
        $resp = $this->json('DELETE', "/api/{$path}/" . self::MISSING_ID);

        $resp->assertStatus(404);
        $resp->assertJson(['success' => false]);
    }
}
