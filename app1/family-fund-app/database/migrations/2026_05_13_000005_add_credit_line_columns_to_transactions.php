<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_credit_line_id')
                ->nullable()
                ->after('account_id')
                ->constrained('account_credit_lines');
            $table->enum('credit_line_match_status', ['auto_matched', 'manual', 'ambiguous', 'unmatched'])
                ->nullable()
                ->after('account_credit_line_id');
            $table->boolean('reversed')
                ->default(false)
                ->after('credit_line_match_status');

            $table->index('credit_line_match_status');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['credit_line_match_status']);
            $table->dropColumn('reversed');
            $table->dropColumn('credit_line_match_status');
            $table->dropConstrainedForeignId('account_credit_line_id');
        });
    }
};
