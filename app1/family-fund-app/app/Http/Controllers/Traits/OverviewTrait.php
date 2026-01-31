<?php

namespace App\Http\Controllers\Traits;

use App\Models\FundExt;
use App\Models\PortfolioExt;
use App\Models\Utils;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Trait OverviewTrait
 *
 * Provides methods for the Fund Overview view with period-based
 * net worth tracking and grouped portfolio breakdowns.
 */
trait OverviewTrait
{
    /**
     * Period codes supported by the overview.
     */
    protected static array $validPeriods = ['1M', '3M', '6M', 'YTD', '1Y', 'ALL'];

    /**
     * Default period for the overview.
     */
    protected static string $defaultPeriod = '1Y';

    /**
     * Group by options for portfolio breakdown.
     */
    protected static array $validGroupBy = ['category', 'type', 'display_group'];

    /**
     * Get the start date for a given period code.
     *
     * @param string $period Period code (1M, 3M, 6M, YTD, 1Y, ALL)
     * @param string $asOf End date (defaults to today)
     * @return string Start date in Y-m-d format
     */
    protected function getPeriodStartDate(string $period, string $asOf): string
    {
        $endDate = Carbon::parse($asOf);

        switch (strtoupper($period)) {
            case '1M':
                return $endDate->copy()->subMonth()->format('Y-m-d');
            case '3M':
                return $endDate->copy()->subMonths(3)->format('Y-m-d');
            case '6M':
                return $endDate->copy()->subMonths(6)->format('Y-m-d');
            case 'YTD':
                return $endDate->copy()->startOfYear()->format('Y-m-d');
            case '1Y':
                return $endDate->copy()->subYear()->format('Y-m-d');
            case 'ALL':
                // Find oldest transaction for the fund
                return '2000-01-01'; // Will be refined in controller
            default:
                // Default to 1Y
                return $endDate->copy()->subYear()->format('Y-m-d');
        }
    }

    /**
     * Get period label for display.
     *
     * @param string $period Period code
     * @return string Human-readable label
     */
    protected function getPeriodLabel(string $period): string
    {
        $labels = [
            '1M' => '1 Month',
            '3M' => '3 Months',
            '6M' => '6 Months',
            'YTD' => 'Year to Date',
            '1Y' => '1 Year',
            'ALL' => 'All Time',
        ];

        return $labels[strtoupper($period)] ?? '1 Year';
    }

    /**
     * Create the full fund overview response.
     *
     * @param FundExt $fund The fund
     * @param string $asOf End date
     * @param string $period Period code
     * @param string $groupBy Group by field (category, type, display_group)
     * @return array Overview data
     */
    protected function createFundOverviewResponse(FundExt $fund, string $asOf, string $period, string $groupBy): array
    {
        $startDate = $this->getPeriodStartDate($period, $asOf);

        // For ALL period, try to find the oldest transaction
        if (strtoupper($period) === 'ALL') {
            $oldestTrans = $fund->findOldestTransaction();
            if ($oldestTrans) {
                $startDate = substr($oldestTrans->timestamp, 0, 10);
            }
        }

        // Get current and start values
        $currentValue = $fund->valueAsOf($asOf);
        $startValue = $fund->valueAsOf($startDate);

        // Calculate changes
        $dollarChange = $currentValue - $startValue;
        $percentChange = $startValue > 0 ? (($currentValue - $startValue) / abs($startValue)) * 100 : 0;

        return [
            'id' => $fund->id,
            'name' => $fund->name,
            'asOf' => $asOf,
            'period' => strtoupper($period),
            'periodLabel' => $this->getPeriodLabel($period),
            'startDate' => $startDate,
            'groupBy' => $groupBy,
            'summary' => [
                'currentValue' => $currentValue,
                'startValue' => $startValue,
                'dollarChange' => $dollarChange,
                'percentChange' => $percentChange,
            ],
            'chartData' => $this->createPeriodChartData($fund, $startDate, $asOf),
            'groups' => $this->createGroupedPortfolioData($fund, $startDate, $asOf, $groupBy),
            'availablePeriods' => self::$validPeriods,
            'availableGroupBy' => self::$validGroupBy,
        ];
    }

    /**
     * Create chart data points for the period.
     *
     * @param FundExt $fund The fund
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Chart data with labels and values
     */
    protected function createPeriodChartData(FundExt $fund, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end);

        // Determine interval based on period length
        if ($daysDiff <= 31) {
            // Daily for 1 month or less
            $interval = 'day';
        } elseif ($daysDiff <= 180) {
            // Weekly for up to 6 months
            $interval = 'week';
        } else {
            // Monthly for longer periods
            $interval = 'month';
        }

        $labels = [];
        $values = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $labels[] = $dateStr;
            $values[] = $fund->valueAsOf($dateStr);

            switch ($interval) {
                case 'day':
                    $current->addDay();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                case 'month':
                default:
                    $current->addMonth();
                    break;
            }
        }

        // Always include the end date if not already included
        $lastLabel = end($labels);
        if ($lastLabel !== $endDate) {
            $labels[] = $endDate;
            $values[] = $fund->valueAsOf($endDate);
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'interval' => $interval,
        ];
    }

    /**
     * Create grouped portfolio data with period changes.
     *
     * @param FundExt $fund The fund
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param string $groupBy Group by field
     * @return array Grouped portfolio data
     */
    protected function createGroupedPortfolioData(FundExt $fund, string $startDate, string $endDate, string $groupBy): array
    {
        $portfolios = $fund->portfolios()->get();
        $groups = [];

        /** @var PortfolioExt $portfolio */
        foreach ($portfolios as $portfolio) {
            $currentValue = $portfolio->valueAsOf($endDate);
            $startValue = $portfolio->valueAsOf($startDate);
            $dollarChange = $currentValue - $startValue;
            $percentChange = $startValue != 0 ? (($currentValue - $startValue) / abs($startValue)) * 100 : 0;

            // Determine group key based on groupBy
            $groupKey = $this->getPortfolioGroupKey($portfolio, $groupBy);
            $groupLabel = $this->getGroupLabel($groupKey, $groupBy);
            $groupColor = $this->getGroupColor($groupKey, $groupBy);

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'label' => $groupLabel,
                    'color' => $groupColor,
                    'currentValue' => 0,
                    'startValue' => 0,
                    'dollarChange' => 0,
                    'portfolios' => [],
                ];
            }

            $groups[$groupKey]['currentValue'] += $currentValue;
            $groups[$groupKey]['startValue'] += $startValue;
            $groups[$groupKey]['dollarChange'] += $dollarChange;

            $groups[$groupKey]['portfolios'][] = [
                'id' => $portfolio->id,
                'name' => $portfolio->display_name ?: $portfolio->source,
                'source' => $portfolio->source,
                'type' => $portfolio->type,
                'category' => $portfolio->category,
                'currentValue' => $currentValue,
                'startValue' => $startValue,
                'dollarChange' => $dollarChange,
                'percentChange' => $percentChange,
            ];
        }

        // Calculate percent change for each group after summing
        foreach ($groups as &$group) {
            $group['percentChange'] = $group['startValue'] != 0
                ? (($group['currentValue'] - $group['startValue']) / abs($group['startValue'])) * 100
                : 0;

            // Sort portfolios by absolute current value descending
            usort($group['portfolios'], function ($a, $b) {
                return abs($b['currentValue']) <=> abs($a['currentValue']);
            });
        }

        // Sort groups by absolute current value descending
        uasort($groups, function ($a, $b) {
            return abs($b['currentValue']) <=> abs($a['currentValue']);
        });

        return array_values($groups);
    }

    /**
     * Get the group key for a portfolio based on groupBy field.
     *
     * @param PortfolioExt $portfolio
     * @param string $groupBy
     * @return string
     */
    protected function getPortfolioGroupKey(PortfolioExt $portfolio, string $groupBy): string
    {
        switch ($groupBy) {
            case 'category':
                return $portfolio->category ?: 'unknown';
            case 'type':
                return $portfolio->type ?: 'unknown';
            case 'display_group':
                // Aggregate assets by display_group would require different logic
                // For now, use category as fallback
                return $portfolio->category ?: 'unknown';
            default:
                return $portfolio->category ?: 'unknown';
        }
    }

    /**
     * Get display label for a group key.
     *
     * @param string $key Group key
     * @param string $groupBy Group by field
     * @return string
     */
    protected function getGroupLabel(string $key, string $groupBy): string
    {
        if ($groupBy === 'category') {
            return PortfolioExt::CATEGORY_LABELS[$key] ?? ucfirst($key);
        }

        if ($groupBy === 'type') {
            return PortfolioExt::TYPE_LABELS[$key] ?? ucfirst($key);
        }

        return ucfirst($key);
    }

    /**
     * Get color for a group key.
     *
     * @param string $key Group key
     * @param string $groupBy Group by field
     * @return string
     */
    protected function getGroupColor(string $key, string $groupBy): string
    {
        if ($groupBy === 'category') {
            return PortfolioExt::CATEGORY_COLORS[$key] ?? '#6b7280';
        }

        if ($groupBy === 'type') {
            return PortfolioExt::TYPE_COLORS[$key] ?? '#6b7280';
        }

        return '#6b7280';
    }
}
