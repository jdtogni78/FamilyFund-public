<?php

namespace App\Jobs;

use App\Http\Controllers\Traits\AccountTrait;
use App\Models\AccountReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        $this->sendAccountReport($this->accountReport);
    }
}
