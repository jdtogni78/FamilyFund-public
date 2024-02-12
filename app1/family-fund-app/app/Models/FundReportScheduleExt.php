<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;

class FundReportScheduleExt extends FundReportSchedule
{
    public function shouldRunBy($today)
    {
        // find last run of report
        $lastReport = $this->lastGeneratedReport();
        $lastAsOf = $lastReport?->as_of;
        Log::info('lastGeneratedReport ' . json_encode($lastReport->toArray()));

        /** @var ReportScheduleExt $schedule **/
        $schedule = $this->schedule()->first();
        $shouldRunBy = $schedule->shouldRunBy($today, $lastAsOf);

        Log::info('shouldRunBy', [
            'today' => $today->toDateString(),
            'lastAsOf' => $lastAsOf?->toDateString(),
            'shouldRunBy' => $shouldRunBy->toDateString(),
        ]);
        return $shouldRunBy;
    }

    public function lastGeneratedReport()
    {
        // find last run of report
        return $this->fundReportsGenerated()
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
