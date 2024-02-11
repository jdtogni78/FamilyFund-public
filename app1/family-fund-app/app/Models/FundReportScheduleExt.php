<?php

namespace App\Models;

class FundReportScheduleExt extends FundReportSchedule
{
    public function isDue()
    {
        // find last run of report
        $lastRun = $this->getLastRun();
    }

    public function getLastRun()
    {
        // find last run of report
        return $lastRun;
    }
}
