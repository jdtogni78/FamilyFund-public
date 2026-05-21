<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Walks the standard scaffolded web resources and exercises common
 * branches that the per-resource ApiTest files miss: the "empty
 * model" path on show/edit/destroy. Index and create are also hit
 * because they're trivial happy paths that lots of scaffolded
 * controllers leave un-asserted.
 *
 * Web controllers (unlike the API ones) flash an error and redirect
 * rather than returning JSON, so the assertions accept any non-2xx
 * status as long as the route is reachable.
 */
class WebControllerSmokeTest extends TestCase
{
    use WithoutMiddleware, DatabaseTransactions;

    private const MISSING_ID = 99_999_999;

    /** @return array<string,array{string}> */
    public static function resources(): array
    {
        return [
            'addresses'            => ['addresses'],
            'assetChangeLogs'      => ['assetChangeLogs'],
            'assets'               => ['assets'],
            'changeLogs'           => ['changeLogs'],
            'id_documents'         => ['id_documents'],
            'people'               => ['people'],
            'phones'               => ['phones'],
            'portfolios'           => ['portfolios'],
            'schedules'            => ['schedules'],
            'tradeBandReports'     => ['tradeBandReports'],
            'transactionMatchings' => ['transactionMatchings'],
            'users'                => ['users'],
        ];
    }

    #[DataProvider('resources')]
    public function test_index_is_reachable(string $path): void
    {
        $resp = $this->get("/{$path}");

        // Index sometimes 200s, sometimes redirects to a filtered view;
        // either is fine — we're exercising the controller branch.
        $this->assertContains(
            $resp->getStatusCode(),
            [200, 302],
            "GET /{$path} returned {$resp->getStatusCode()}"
        );
    }

    #[DataProvider('resources')]
    public function test_create_form_is_reachable(string $path): void
    {
        $resp = $this->get("/{$path}/create");

        $this->assertContains(
            $resp->getStatusCode(),
            [200, 302],
            "GET /{$path}/create returned {$resp->getStatusCode()}"
        );
    }

    #[DataProvider('resources')]
    public function test_show_missing_id_redirects_with_flash(string $path): void
    {
        $resp = $this->get("/{$path}/" . self::MISSING_ID);

        // Standard scaffold: Flash::error(...) + redirect to index → 302.
        $this->assertContains(
            $resp->getStatusCode(),
            [302, 404],
            "GET /{$path}/{missing} expected 302 (flash+redirect) or 404, got {$resp->getStatusCode()}"
        );
    }

    #[DataProvider('resources')]
    public function test_edit_missing_id_redirects_with_flash(string $path): void
    {
        $resp = $this->get("/{$path}/" . self::MISSING_ID . "/edit");

        $this->assertContains(
            $resp->getStatusCode(),
            [302, 404],
            "GET /{$path}/{missing}/edit expected 302 or 404, got {$resp->getStatusCode()}"
        );
    }

    #[DataProvider('resources')]
    public function test_destroy_missing_id_redirects_with_flash(string $path): void
    {
        $resp = $this->delete("/{$path}/" . self::MISSING_ID);

        $this->assertContains(
            $resp->getStatusCode(),
            [302, 404],
            "DELETE /{$path}/{missing} expected 302 or 404, got {$resp->getStatusCode()}"
        );
    }
}
