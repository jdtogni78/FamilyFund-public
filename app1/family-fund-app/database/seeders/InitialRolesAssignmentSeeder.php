<?php

namespace Database\Seeders;

use App\Models\AccountExt;
use App\Models\FundExt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class InitialRolesAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Make the configured admin(s) System Admins (ADMIN_EMAILS, config/familyfund.php)
        $adminEmails = config('familyfund.admin_emails');
        $systemAdminRole = Role::where('name', 'system-admin')
            ->whereNull('fund_id')
            ->first();

        foreach ($adminEmails as $adminEmail) {
            $adminUser = User::where('email', $adminEmail)->first();

            if (!$adminUser) {
                $this->command->warn("User {$adminEmail} not found - skipping system admin assignment");
                continue;
            }

            if ($systemAdminRole) {
                // For system-admin (global role), we need to directly insert with fund_id=0
                // to work around the teams constraint (fund_id can't be NULL in pivot)
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $systemAdminRole->id,
                    'model_type' => User::class,
                    'model_id' => $adminUser->id,
                ], [
                    'fund_id' => 0, // Use 0 to indicate global/all funds
                ]);

                $this->command->info("Assigned system-admin role to {$adminEmail}");
            }
        }

        // 2. For all users with accounts: assign beneficiary role for their fund(s)
        $usersWithAccounts = User::whereHas('accounts')->get();

        foreach ($usersWithAccounts as $user) {
            // Skip admin users, they already have full access
            if (in_array($user->email, $adminEmails)) {
                continue;
            }

            // Get all unique fund IDs from user's accounts
            $fundIds = $user->accounts()->pluck('fund_id')->unique();

            foreach ($fundIds as $fundId) {
                // Create or get the beneficiary role for this fund
                $beneficiaryRole = RolesAndPermissionsSeeder::createFundRole('beneficiary', $fundId);

                // Set the team context and assign the role
                setPermissionsTeamId($fundId);

                if (!$user->hasRole('beneficiary')) {
                    $user->assignRole($beneficiaryRole);
                    $this->command->info("Assigned beneficiary role to {$user->email} for fund {$fundId}");
                }
            }
        }

        // 3. Test user claude@test.local gets beneficiary on first fund
        $testUser = User::where('email', 'claude@test.local')->first();

        if ($testUser) {
            $firstFund = FundExt::first();

            if ($firstFund) {
                $beneficiaryRole = RolesAndPermissionsSeeder::createFundRole('beneficiary', $firstFund->id);

                setPermissionsTeamId($firstFund->id);

                if (!$testUser->hasRole('beneficiary')) {
                    $testUser->assignRole($beneficiaryRole);
                    $this->command->info("Assigned beneficiary role to claude@test.local for fund {$firstFund->id}");
                }
            }
        } else {
            $this->command->info("Test user claude@test.local not found - skipping");
        }

        // Reset team context
        setPermissionsTeamId(null);

        $this->command->info('Initial role assignment completed.');
    }
}
