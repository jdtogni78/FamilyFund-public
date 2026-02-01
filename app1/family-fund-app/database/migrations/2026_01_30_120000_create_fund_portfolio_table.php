<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create pivot table for many-to-many relationship between funds and portfolios
        Schema::create('fund_portfolio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained()->onDelete('cascade');
            $table->foreignId('portfolio_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['fund_id', 'portfolio_id']);
        });

        // Migrate existing data: copy fund_id from portfolios to pivot table
        DB::statement("INSERT INTO fund_portfolio (fund_id, portfolio_id, created_at, updated_at)
            SELECT fund_id, id, NOW(), NOW() FROM portfolios WHERE fund_id IS NOT NULL");

        // Make fund_id nullable (keep for backward compatibility during transition)
        Schema::table('portfolios', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore fund_id from pivot table (take first fund)
        DB::statement("UPDATE portfolios p SET fund_id = (
            SELECT fund_id FROM fund_portfolio fp WHERE fp.portfolio_id = p.id LIMIT 1
        )");

        Schema::dropIfExists('fund_portfolio');

        // Make fund_id required again
        Schema::table('portfolios', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_id')->nullable(false)->change();
        });
    }
};
