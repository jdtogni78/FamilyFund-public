<?php

namespace Database\Seeders;

use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeds canonical QA / browser-test users for each fund-scoped role on the
 * first fund. Idempotent — safe to re-run on dev and CI.
 *
 * Pairs with the /dev-login?as=<role-alias> route in routes/web.php.
 */
class QaTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $fund = FundExt::orderBy('id')->first();
        if (! $fund) {
            $this->command->warn('QaTestUsersSeeder: no funds found, skipping.');
            return;
        }

        $users = [
            ['fund-admin',         'qa-fund-admin@test.local',         'QA Fund Admin'],
            ['financial-manager',  'qa-financial-manager@test.local',  'QA Financial Manager'],
            ['beneficiary',        'qa-beneficiary@test.local',        'QA Beneficiary'],
            // claude@test.local is documented in CLAUDE.md as the default
            // dev-login user. Without a role it 403s on every nav link, so
            // grant it fund-admin on the first fund (matches QA-2026-05-19
            // bug #14; QA log removed from the tree, see git history).
            ['fund-admin',         'claude@test.local',                'Claude Test'],
        ];

        foreach ($users as [$roleName, $email, $name]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }

            $role = RolesAndPermissionsSeeder::createFundRole($roleName, $fund->id);

            $originalTeam = getPermissionsTeamId();
            setPermissionsTeamId($fund->id);
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
            setPermissionsTeamId($originalTeam);

            $this->command->info("QA user: {$email} -> {$roleName} on fund {$fund->id}");
        }

        // The beneficiary needs an account on this fund so per-user scoping
        // (e.g. "view own") has something to read.
        $beneficiary = User::where('email', 'qa-beneficiary@test.local')->first();
        if ($beneficiary) {
            AccountExt::firstOrCreate(
                ['fund_id' => $fund->id, 'user_id' => $beneficiary->id],
                [
                    'code' => 'QA-' . $beneficiary->id,
                    'nickname' => 'QA Beneficiary Account',
                ]
            );
        }
    }
}
