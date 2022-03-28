<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\FundTrait;
use App\Models\FundReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFundReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use FundTrait;

    private FundReport $fundReport;
    protected $verbose = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fundReport)
    {
        //
        $this->fundReport = $fundReport;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->sendFundReport($this->fundReport);
    }
}
