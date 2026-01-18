<?php

namespace App\Http\Controllers\Traits;

use App\Models\ExchangeHoliday;
use Carbon\Carbon;

trait DetectsDataIssuesTrait
{
    /**
     * Detect data issues: overlapping date ranges and gaps.
     *
     * @param \Illuminate\Support\Collection $records Collection of records with start_dt, end_dt
     * @param string $groupByField Field to group records by (e.g., 'asset_id')
     * @param string $nameField Relationship to get display name (e.g., 'asset')
     * @param int $gapThreshold Minimum trading days to flag as gap (default 1)
     * @param string $exchangeCode Exchange to use for holiday calendar (default 'NYSE')
     */
    protected function detectDataIssues($records, string $groupByField = 'asset_id', string $nameField = 'asset', int $gapThreshold = 1, string $exchangeCode = 'NYSE'): array
    {
        $overlaps = [];
        $gaps = [];
        $longSpans = [];
        $overlappingIds = [];
        $gapIds = [];
        $longSpanIds = [];

        if ($records->isEmpty()) {
            return compact('overlaps', 'gaps', 'longSpans', 'overlappingIds', 'gapIds', 'longSpanIds');
        }

        // Load exchange holidays for the date range covered by records
        $minDate = $records->min('start_dt');
        $maxDate = $records->max('end_dt');
        $holidays = $this->loadExchangeHolidays($exchangeCode, $minDate, $maxDate);

        // Group by the specified field
        $grouped = $records->groupBy($groupByField);

        foreach ($grouped as $groupId => $groupRecords) {
            $sorted = $groupRecords->sortBy('start_dt')->values();
            $displayName = $sorted->first()->{$nameField}->name ?? 'Unknown';

            for ($i = 0; $i < count($sorted); $i++) {
                $current = $sorted[$i];

                // Check if single record spans multiple trading days (days without new data)
                if ($current->end_dt && $current->end_dt->format('Y') !== '9999') {
                    $spanTradingDays = $this->calculateTradingDays($current->start_dt, $current->end_dt, $holidays);

                    if ($spanTradingDays > 1) {
                        $longSpans[] = [
                            'name' => $displayName,
                            'from' => $current->start_dt->format('Y-m-d'),
                            'to' => $current->end_dt->format('Y-m-d'),
                            'days' => $spanTradingDays,
                            'calendar_days' => $current->start_dt->diffInDays($current->end_dt),
                        ];
                        $longSpanIds[$current->id] = true;
                    }
                }

                // Check for overlaps/duplicates with subsequent records
                for ($j = $i + 1; $j < count($sorted); $j++) {
                    $other = $sorted[$j];

                    // Check for overlapping date ranges (applies to both prices and positions)
                    if ($current->start_dt < $other->end_dt && $current->end_dt > $other->start_dt) {
                        $overlaps[] = [
                            'name' => $displayName,
                            'type' => 'overlap',
                            'record1' => $current->start_dt->format('Y-m-d') . ' to ' . ($current->end_dt->format('Y') === '9999' ? 'current' : $current->end_dt->format('Y-m-d')),
                            'record2' => $other->start_dt->format('Y-m-d') . ' to ' . ($other->end_dt->format('Y') === '9999' ? 'current' : $other->end_dt->format('Y-m-d')),
                        ];
                        $overlappingIds[$current->id] = true;
                        $overlappingIds[$other->id] = true;
                    }
                }

                // Check for gaps (only if there's a next record)
                if ($i < count($sorted) - 1) {
                    $next = $sorted[$i + 1];

                    // Only check gap if current record has a real end date
                    if ($current->end_dt && $current->end_dt->format('Y') !== '9999') {
                        // Calculate trading days between records (excluding weekends and holidays)
                        $tradingDays = $this->calculateTradingDays($current->end_dt, $next->start_dt, $holidays);

                        if ($tradingDays > $gapThreshold) {
                            $gaps[] = [
                                'name' => $displayName,
                                'from' => $current->end_dt->format('Y-m-d'),
                                'to' => $next->start_dt->format('Y-m-d'),
                                'days' => $tradingDays,
                                'calendar_days' => $current->end_dt->diffInDays($next->start_dt),
                            ];
                            $gapIds[$current->id] = true;
                            $gapIds[$next->id] = true;
                        }
                    }
                }
            }
        }

        // Sort all warnings by date descending (newest first)
        usort($overlaps, fn($a, $b) => strcmp($b['record1'] ?? '', $a['record1'] ?? ''));
        usort($gaps, fn($a, $b) => strcmp($b['from'] ?? '', $a['from'] ?? ''));
        usort($longSpans, fn($a, $b) => strcmp($b['from'] ?? '', $a['from'] ?? ''));

        return [
            'overlaps' => $overlaps,
            'gaps' => $gaps,
            'longSpans' => $longSpans,
            'overlappingIds' => array_keys($overlappingIds),
            'gapIds' => array_keys($gapIds),
            'longSpanIds' => array_keys($longSpanIds),
        ];
    }

    /**
     * Load exchange holidays for a date range.
     *
     * @param string $exchangeCode Exchange code (e.g., 'NYSE')
     * @param \Carbon\Carbon|string|null $minDate Start of date range
     * @param \Carbon\Carbon|string|null $maxDate End of date range
     * @return array Array of holiday dates as strings (Y-m-d)
     */
    protected function loadExchangeHolidays(string $exchangeCode, $minDate = null, $maxDate = null): array
    {
        $query = ExchangeHoliday::active()->forExchange($exchangeCode);

        if ($minDate) {
            $query->where('holiday_date', '>=', $minDate);
        }

        if ($maxDate) {
            // Filter out far-future dates (9999-12-31)
            $maxDateCarbon = Carbon::parse($maxDate);
            if ($maxDateCarbon->year < 9999) {
                $query->where('holiday_date', '<=', $maxDate);
            }
        }

        return $query->pluck('holiday_date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();
    }

    /**
     * Calculate trading days between two dates, excluding weekends and holidays.
     *
     * @param \Carbon\Carbon $startDate Start date (exclusive)
     * @param \Carbon\Carbon $endDate End date (exclusive)
     * @param array $holidays Array of holiday dates as strings (Y-m-d)
     * @return int Number of trading days
     */
    protected function calculateTradingDays(Carbon $startDate, Carbon $endDate, array $holidays): int
    {
        $tradingDays = 0;
        $current = $startDate->copy()->addDay();

        while ($current < $endDate) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if (!$current->isWeekend()) {
                // Skip holidays
                if (!in_array($current->format('Y-m-d'), $holidays)) {
                    $tradingDays++;
                }
            }
            $current->addDay();
        }

        return $tradingDays;
    }

    /**
     * Collect dates with issues for chart visualization.
     * Returns array of dates with issue details (type, days count).
     */
    protected function collectIssueDates(array $dataWarnings): array
    {
        $dates = [];

        // Collect dates from overlaps
        foreach ($dataWarnings['overlaps'] ?? [] as $overlap) {
            // Extract dates from "YYYY-MM-DD to YYYY-MM-DD" format
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $overlap['record1'], $m)) {
                $dates[$m[1]] = ['type' => 'overlap'];
            }
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $overlap['record2'], $m)) {
                $dates[$m[1]] = ['type' => 'overlap'];
            }
        }

        // Collect dates from gaps
        foreach ($dataWarnings['gaps'] ?? [] as $gap) {
            $gapInfo = ['type' => 'gap', 'days' => $gap['days'] ?? 0];
            if (!empty($gap['from'])) {
                $dates[$gap['from']] = $gapInfo;
            }
            if (!empty($gap['to'])) {
                $dates[$gap['to']] = $gapInfo;
            }
        }

        // Collect dates from long spans (days without new data)
        foreach ($dataWarnings['longSpans'] ?? [] as $span) {
            $spanInfo = ['type' => 'long_span', 'days' => $span['days'] ?? 0];
            if (!empty($span['from'])) {
                $dates[$span['from']] = $spanInfo;
            }
            if (!empty($span['to'])) {
                $dates[$span['to']] = $spanInfo;
            }
        }

        return $dates;
    }
}
