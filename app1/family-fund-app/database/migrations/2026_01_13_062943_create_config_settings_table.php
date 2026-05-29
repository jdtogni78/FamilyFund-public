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
        Schema::create('config_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();        // e.g., 'mail.admin_email'
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, integer, boolean, json, email, url, path, csv
            $table->string('category', 50)->default('general');
            $table->string('description', 255)->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->string('updated_by', 100)->nullable();
            $table->timestamps();

            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_settings');
    }
};
