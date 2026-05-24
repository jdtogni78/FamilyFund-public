<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original create_personal_access_tokens_table migration predates Sanctum's
 * token-expiration support, so the table lacks `expires_at` while the installed
 * Sanctum's HasApiTokens::createToken() writes it — making Sanctum personal
 * access tokens unusable (needed for the authenticated DAST/ZAP scan, #13/#49).
 * Add the nullable column idempotently.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('last_used_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};
