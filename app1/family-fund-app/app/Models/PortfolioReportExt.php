<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Class PortfolioReportExt
 * @package App\Models
 */
class PortfolioReportExt extends PortfolioReport
{
    public static string $emailSubject = 'Portfolio Rebalance Report';

    // Report types
    const TYPE_CUSTOM = 'custom';
    const TYPE_QUARTERLY = 'quarterly';
    const TYPE_ANNUAL = 'annual';

    public static array $typeMap = [
        self::TYPE_CUSTOM => 'Custom Date Range',
        self::TYPE_QUARTERLY => 'Quarterly (Previous Quarter)',
        self::TYPE_ANNUAL => 'Annual (Previous Year)',
    ];

    /**
     * Calculate start and end dates based on report type and run date
     */
    public static function calculateDateRange(string $reportType, Carbon $runDate): array
    {
        switch ($reportType) {
            case self::TYPE_QUARTERLY:
                // Previous quarter: if run in Jan, covers Oct-Dec of prev year
                $endDate = $runDate->copy()->subQuarterNoOverflow()->endOfQuarter();
                $startDate = $endDate->copy()->startOfQuarter();
                break;

            case self::TYPE_ANNUAL:
                // Previous year: Jan 1 - Dec 31 of previous year
                $endDate = $runDate->copy()->subYear()->endOfYear();
                $startDate = $endDate->copy()->startOfYear();
                break;

            case self::TYPE_CUSTOM:
            default:
                // Default to last 3 months ending on run date
                $endDate = $runDate->copy();
                $startDate = $endDate->copy()->subMonths(3);
                break;
        }

        return [$startDate, $endDate];
    }

    /**
     * Override portfolio relationship to return PortfolioExt
     */
    public function portfolio()
    {
        return $this->belongsTo(PortfolioExt::class, 'portfolio_id');
    }

    public function scheduledJobs()
    {
        return ScheduledJobExt::scheduledJobs(ScheduledJobExt::ENTITY_PORTFOLIO_REPORT, $this->portfolio_id);
    }

    /**
     * Get the fund associated with this portfolio report
     */
    public function fund()
    {
        return $this->portfolio?->fund;
    }
}
