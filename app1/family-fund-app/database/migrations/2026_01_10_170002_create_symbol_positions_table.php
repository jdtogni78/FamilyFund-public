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
        Schema::create('symbol_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->string('type', 3);
            $table->decimal('position', 20, 4);
            $table->foreignId('position_update_id')->nullable()->constrained('position_updates')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('symbol_positions');
    }
};
