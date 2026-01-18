<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\AccountTrait;
use App\Models\AccountReport;
use App\Models\OperationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAccountReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use AccountTrait;

    private AccountReport $accountReport;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($accountReport)
    {
        //
        $this->accountReport = $accountReport->withoutRelations();
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
            AccountReport::class,
            $this->accountReport->id
        )) {
            Log::info("SendAccountReport for report {$this->accountReport->id} already completed, skipping");
            return;
        }

        $this->sendAccountReport($this->accountReport);
    }
}
