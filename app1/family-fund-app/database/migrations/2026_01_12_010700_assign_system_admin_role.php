<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create system-admin role if it doesn't exist
        $roleId = DB::table('roles')
            ->where('name', 'system-admin')
            ->where('guard_name', 'web')
            ->value('id');

        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'system-admin',
                'guard_name' => 'web',
                'fund_id' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assign system-admin (fund_id=0, global access) to the configured admin(s).
        $userIds = DB::table('users')
            ->whereIn('email', config('familyfund.admin_emails'))
            ->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $userId,
                'fund_id' => 0,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $userIds = DB::table('users')
            ->whereIn('email', config('familyfund.admin_emails'))
            ->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('model_has_roles')
                ->where('model_id', $userId)
                ->where('model_type', 'App\\Models\\User')
                ->where('fund_id', 0)
                ->delete();
        }
    }
};
