<?php

namespace Tests\Concerns;

use App\Models\User;
use Tests\Fixtures\TestFixtures;

/**
 * Authenticates the test as a system admin for every test method.
 *
 * Hooks in via Laravel's `setUp{TraitName}` convention (called by
 * TestCase::setUpTraits AFTER the DatabaseTransactions transaction begins, so
 * the created admin rolls back with the test).
 *
 * Used by the generated `tests/APIs/*ApiTest.php` CRUD suites: those run with
 * WithoutMiddleware (skipping the `auth:sanctum` route lock), but the generated
 * API controllers now enforce object-level / tenant authorization in code
 * (#13 / #49 item A) which executes regardless of middleware. A system admin
 * bypasses tenant scoping, so the generated happy-path CRUD assertions continue
 * to hold while the new SecurityApiAclMatrixTest covers the non-admin denials.
 */
trait ActsAsApiSystemAdmin
{
    protected function setUpActsAsApiSystemAdmin(): void
    {
        $admin = User::factory()->create();
        TestFixtures::makeSystemAdmin($admin);
        $this->actingAs($admin);
    }
}
