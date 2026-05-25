<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Seeds the dstrader machine-to-machine service account (#82).
 *
 * dstrader (the Java trading app + the bash report scheduler) pushes asset
 * prices / positions and triggers reports through the FamilyFund `/api/*`
 * surface, which is `auth:sanctum`-locked and (per #82) admin-gated for
 * reference-data writes. This account is the system-admin principal those
 * callers authenticate as; security:service-token mints its `app` / `scheduler`
 * Sanctum tokens.
 *
 * Authenticates via token only — the password is random and unused. Idempotent;
 * safe to run on dev, CI and prod. Requires RolesAndPermissionsSeeder to have
 * created the global system-admin role first.
 */
class DstraderServiceUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('familyfund.service_user_email');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'dstrader service',
                // Token-only principal: random, never used to log in.
                'password' => Hash::make(Str::random(48)),
                'email_verified_at' => now(),
            ]
        );

        // The global system-admin role is created with fund_id=0 by
        // RolesAndPermissionsSeeder; isSystemAdmin() checks model_has_roles for
        // that row. Assign it via a direct insert (matching
        // InitialRolesAssignmentSeeder) because Spatie's team-scoped assignRole
        // can't represent the global fund_id=0 grant.
        $systemAdminRole = Role::where('name', 'system-admin')->first();
        if (! $systemAdminRole) {
            $this->command?->warn(
                'DstraderServiceUserSeeder: system-admin role not found — run RolesAndPermissionsSeeder first. Skipping role grant.'
            );
            return;
        }

        DB::table('model_has_roles')->updateOrInsert([
            'role_id' => $systemAdminRole->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ], [
            'fund_id' => 0, // global / all funds
        ]);

        $this->command?->info("dstrader service user ready: {$email} -> system-admin");
        $this->command?->info('Mint tokens with: php artisan security:service-token --name=app --rotate');
    }
}
