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
        // Alter ENUM to include all status values
        DB::statement("ALTER TABLE cash_deposits MODIFY COLUMN status ENUM('PENDING', 'DEPOSITED', 'ALLOCATED', 'COMPLETED', 'CANCELLED') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if any rows have COMPLETED or CANCELLED status
        DB::statement("ALTER TABLE cash_deposits MODIFY COLUMN status ENUM('PENDING', 'DEPOSITED', 'ALLOCATED') NOT NULL");
    }
};
