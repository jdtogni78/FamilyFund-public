<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\TradeBandReportTrait;
use App\Http\Controllers\Traits\FundTrait;
use App\Models\TradeBandReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTradeBandReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use FundTrait, TradeBandReportTrait;

    private TradeBandReport $tradeBandReport;

    /**
     * Create a new job instance.
     */
    public function __construct(TradeBandReport $tradeBandReport)
    {
        $this->tradeBandReport = $tradeBandReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendTradeBandReport($this->tradeBandReport);
    }
}
