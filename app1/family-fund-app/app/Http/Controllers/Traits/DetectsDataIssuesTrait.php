<?php

namespace App\Http\Controllers\Traits;

trait DetectsDataIssuesTrait
{
    /**
     * Detect data issues: overlapping date ranges and gaps.
     *
     * @param \Illuminate\Support\Collection $records Collection of records with start_dt, end_dt
     * @param string $groupByField Field to group records by (e.g., 'asset_id')
     * @param string $nameField Relationship to get display name (e.g., 'asset')
     * @param int $gapThreshold Minimum days to flag as gap (default 1)
     */
    protected function detectDataIssues($records, string $groupByField = 'asset_id', string $nameField = 'asset', int $gapThreshold = 1): array
    {
        $overlaps = [];
        $gaps = [];
        $overlappingIds = [];
        $gapIds = [];

        if ($records->isEmpty()) {
            return compact('overlaps', 'gaps', 'overlappingIds', 'gapIds');
        }

        // Group by the specified field
        $grouped = $records->groupBy($groupByField);

        foreach ($grouped as $groupId => $groupRecords) {
            $sorted = $groupRecords->sortBy('start_dt')->values();
            $displayName = $sorted->first()->{$nameField}->name ?? 'Unknown';

            for ($i = 0; $i < count($sorted); $i++) {
                $current = $sorted[$i];

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
                        $gapDays = $current->end_dt->diffInDays($next->start_dt);

                        if ($gapDays > $gapThreshold) {
                            $gaps[] = [
                                'name' => $displayName,
                                'from' => $current->end_dt->format('Y-m-d'),
                                'to' => $next->start_dt->format('Y-m-d'),
                                'days' => $gapDays,
                            ];
                            $gapIds[$current->id] = true;
                            $gapIds[$next->id] = true;
                        }
                    }
                }
            }
        }

        return [
            'overlaps' => $overlaps,
            'gaps' => $gaps,
            'overlappingIds' => array_keys($overlappingIds),
            'gapIds' => array_keys($gapIds),
        ];
    }

    /**
     * Collect dates with issues for chart visualization.
     * Returns array of dates that have overlaps or gaps.
     */
    protected function collectIssueDates(array $dataWarnings): array
    {
        $dates = [];

        // Collect dates from overlaps
        foreach ($dataWarnings['overlaps'] ?? [] as $overlap) {
            // Extract dates from "YYYY-MM-DD to YYYY-MM-DD" format
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $overlap['record1'], $m)) {
                $dates[$m[1]] = 'overlap';
            }
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $overlap['record2'], $m)) {
                $dates[$m[1]] = 'overlap';
            }
        }

        // Collect dates from gaps
        foreach ($dataWarnings['gaps'] ?? [] as $gap) {
            if (!empty($gap['from'])) {
                $dates[$gap['from']] = 'gap';
            }
            if (!empty($gap['to'])) {
                $dates[$gap['to']] = 'gap';
            }
        }

        return $dates;
    }
}
