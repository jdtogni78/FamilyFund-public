<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\FundTrait;
use App\Http\Controllers\Traits\VerboseTrait;
use App\Models\FundReport;
use App\Models\OperationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFundReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use FundTrait;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes for PDF generation

    private FundReport $fundReport;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fundReport)
    {
        $this->fundReport = $fundReport;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check if this job already completed for this report (prevents duplicates on retry)
        if (OperationLog::jobCompletedForModel(
            self::class,
            FundReport::class,
            $this->fundReport->id
        )) {
            Log::info("SendFundReport for report {$this->fundReport->id} already completed, skipping");
            return;
        }

        $this->sendFundReport($this->fundReport);
    }
}
