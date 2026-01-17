<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class RunScheduledJobs extends Command
{
    protected $signature = 'schedule_jobs:run {--as-of=}';
    protected $description = 'Check and run scheduled jobs that are due';

    public function handle()
    {
        $asOf = $this->option('as-of') ?? Carbon::now()->toDateString();

        $this->info("Running scheduled jobs for {$asOf}...");

        // Call internal API endpoint
        $response = Http::post(config('app.url') . '/api/schedule_jobs', [
            'as_of' => $asOf,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->info("✓ Scheduled jobs executed successfully");
            if (isset($data['data']) && is_array($data['data'])) {
                $this->info("  Executed " . count($data['data']) . " job(s)");
            }
            return 0;
        } else {
            $this->error("✗ Failed to execute scheduled jobs");
            $this->error("  Status: " . $response->status());
            return 1;
        }
    }
}
