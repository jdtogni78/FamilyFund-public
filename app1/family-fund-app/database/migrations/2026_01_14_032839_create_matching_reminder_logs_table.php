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
        Schema::create('matching_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_job_id')->constrained('scheduled_jobs');
            $table->foreignId('account_id')->constrained('accounts');
            $table->date('sent_at');
            $table->json('rule_details')->nullable();
            $table->integer('rules_count')->default(0);
            $table->timestamps();

            $table->index(['scheduled_job_id', 'sent_at']);
            $table->index(['account_id', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matching_reminder_logs');
    }
};
