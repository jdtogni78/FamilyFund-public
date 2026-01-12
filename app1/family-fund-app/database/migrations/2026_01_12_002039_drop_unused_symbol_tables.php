<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop child tables first (FK constraints)
        Schema::dropIfExists('symbol_positions');
        Schema::dropIfExists('symbol_prices');
        // Drop parent tables
        Schema::dropIfExists('position_updates');
        Schema::dropIfExists('price_updates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables were unused scaffolding - no need to recreate
    }
};
