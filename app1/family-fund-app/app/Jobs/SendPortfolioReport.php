<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\PortfolioReportTrait;
use App\Models\PortfolioReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPortfolioReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use PortfolioReportTrait;

    private PortfolioReport $portfolioReport;

    /**
     * Create a new job instance.
     */
    public function __construct($portfolioReport)
    {
        $this->portfolioReport = $portfolioReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendPortfolioReport($this->portfolioReport);
    }
}
