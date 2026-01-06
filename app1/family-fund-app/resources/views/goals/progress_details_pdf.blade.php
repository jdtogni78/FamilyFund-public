@php
    $progress = $goal->progress;
    $current = $progress['current'] ?? [];
    $expected = $progress['expected'] ?? [];
    $period = $progress['period'] ?? [0, 1, 0];

    $currentValue = $current['value'] ?? 0;
    $expectedValue = $expected['value'] ?? 0;
    $finalValue = $current['final_value'] ?? $goal->target_amount;

    $currentYield = $current['value_4pct'] ?? 0;
    $expectedYield = $expected['value_4pct'] ?? 0;
    $finalYield = $current['final_value_4pct'] ?? ($goal->target_amount * $goal->target_pct);

    $diff = $currentValue - $expectedValue;
    $isOnTrack = $diff >= 0;

    $currentPct = $current['completed_pct'] ?? 0;
    $expectedPct = $expected['completed_pct'] ?? 0;

    $yearsElapsed = $period[0] / 365;
    $totalYears = $period[1] / 365;
    $timePct = $period[2];

    $isTargetTotal = $goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL;

    // Color classes based on track status
    $trackColor = $isOnTrack ? '#16a34a' : '#dc2626';
    $trackBg = $isOnTrack ? 'background: #dcfce7; border: 1px solid #16a34a;' : 'background: #fef2f2; border: 1px solid #dc2626;';

    // Calculate time ahead/behind
    $pctDiff = abs($currentPct - $expectedPct);
    $timeAheadYears = ($pctDiff / 100) * $totalYears;
    if ($timeAheadYears >= 1) {
        $timeAheadStr = number_format($timeAheadYears, 1) . ' year' . ($timeAheadYears >= 1.5 ? 's' : '');
    } else {
        $timeAheadMonths = $timeAheadYears * 12;
        $timeAheadStr = number_format($timeAheadMonths, 0) . ' month' . ($timeAheadMonths != 1 ? 's' : '');
    }
@endphp

<div style="background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 12px;">
    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
        <strong style="font-size: 13px;">Target:</strong>
        @if($isTargetTotal)
            Reach <strong style="color: #1e40af;">${{ number_format($finalValue, 0) }}</strong> account value
            <span style="color: #64748b;">(generating ${{ number_format($finalYield, 0) }}/year at {{ $goal->target_pct * 100 }}% yield)</span>
        @else
            Generate <strong style="color: #1e40af;">${{ number_format($goal->target_amount, 0) }}/year</strong> passive income
            <span style="color: #64748b;">(requires ${{ number_format($finalValue, 0) }} at {{ $goal->target_pct * 100 }}% yield)</span>
        @endif
        by <strong>{{ $goal->end_dt->format('M Y') }}</strong>
    </div>

    <table style="width: 100%; margin-bottom: 12px; border-collapse: collapse;">
        <thead>
            <tr style="background: #e2e8f0;">
                <th style="padding: 8px; text-align: left; width: 25%;"></th>
                <th style="padding: 8px; text-align: right; width: 25%;">Current</th>
                <th style="padding: 8px; text-align: right; width: 25%;">Expected Now</th>
                <th style="padding: 8px; text-align: right; width: 25%;">Final Goal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 6px 8px;"><strong>Account Value</strong></td>
                <td style="padding: 6px 8px; text-align: right; color: {{ $trackColor }}; font-weight: 600;">
                    ${{ number_format($currentValue, 0) }}
                </td>
                <td style="padding: 6px 8px; text-align: right; color: #64748b;">
                    ${{ number_format($expectedValue, 0) }}
                </td>
                <td style="padding: 6px 8px; text-align: right; font-weight: 600;">
                    ${{ number_format($finalValue, 0) }}
                </td>
            </tr>
            <tr style="background: #f1f5f9;">
                <td style="padding: 6px 8px;"><strong>Annual Yield ({{ $goal->target_pct * 100 }}%)</strong></td>
                <td style="padding: 6px 8px; text-align: right; color: {{ $trackColor }}; font-weight: 600;">
                    ${{ number_format($currentYield, 0) }}/yr
                </td>
                <td style="padding: 6px 8px; text-align: right; color: #64748b;">
                    ${{ number_format($expectedYield, 0) }}/yr
                </td>
                <td style="padding: 6px 8px; text-align: right; font-weight: 600;">
                    ${{ number_format($finalYield, 0) }}/yr
                </td>
            </tr>
            <tr>
                <td style="padding: 6px 8px;"><strong>Progress</strong></td>
                <td style="padding: 6px 8px; text-align: right; color: {{ $trackColor }}; font-weight: 600;">
                    {{ number_format($currentPct, 1) }}%
                </td>
                <td style="padding: 6px 8px; text-align: right; color: #64748b;">
                    {{ number_format($expectedPct, 1) }}%
                </td>
                <td style="padding: 6px 8px; text-align: right; font-weight: 600;">
                    100%
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 12px; color: #64748b;">
        <strong>Time:</strong> {{ number_format($yearsElapsed, 1) }} of {{ number_format($totalYears, 1) }} years elapsed ({{ number_format($timePct, 0) }}%)
    </div>

    <div style="padding: 10px; border-radius: 6px; {{ $trackBg }}">
        @if($isOnTrack)
            <span style="color: #16a34a; font-weight: 700; font-size: 14px;">ON TRACK</span>
            <span style="color: #16a34a; margin-left: 10px;">
                Ahead by <strong>${{ number_format($diff, 0) }}</strong>
                ({{ $timeAheadStr }} ahead of schedule)
            </span>
        @else
            <span style="color: #dc2626; font-weight: 700; font-size: 14px;">BEHIND</span>
            <span style="color: #dc2626; margin-left: 10px;">
                Behind by <strong>${{ number_format(abs($diff), 0) }}</strong>
                ({{ $timeAheadStr }} behind schedule)
            </span>
        @endif
    </div>
</div>
