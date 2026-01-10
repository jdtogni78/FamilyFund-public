{{--
    Goals Progress Summary - Compact row layout
    Used in both PDF and Web views for the main goals overview section

    Required: $goal (with ->progress array populated)
    Optional: $format ('web'|'pdf', defaults to 'web')
--}}
@php
    $format = $format ?? 'web';
    $isPdf = $format === 'pdf';

    $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
    $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
    $currentValue = $goal->progress['current']['value'] ?? 0;
    $expectedValue = $goal->progress['expected']['value'] ?? 0;
    $targetValue = $goal->progress['current']['final_value'] ?? $goal->target_amount;

    $diff = $currentValue - $expectedValue;
    $isOnTrack = $diff >= 0;
    $progressColor = $isOnTrack ? '#16a34a' : '#d97706';
    $badgeBg = $isOnTrack ? '#dcfce7' : '#fef2f2';
    $badgeColor = $isOnTrack ? '#16a34a' : '#dc2626';

    // Calculate time ahead/behind
    $period = $goal->progress['period'] ?? [0, 1, 0];
    $totalDays = $period[1] ?? 1;
    $pctDiff = abs($currentPct - $expectedPct);
    $timeAheadDays = ($pctDiff / 100) * $totalDays;
    if ($timeAheadDays >= 365) {
        $timeAheadYears = $timeAheadDays / 365;
        $timeAheadStr = number_format($timeAheadYears, 1) . ' yr' . ($timeAheadYears >= 1.5 ? 's' : '');
    } elseif ($timeAheadDays >= 30) {
        $timeAheadMonths = round($timeAheadDays / 30);
        $timeAheadStr = $timeAheadMonths . ' mo' . ($timeAheadMonths != 1 ? 's' : '');
    } else {
        $timeAheadWeeks = max(1, round($timeAheadDays / 7));
        $timeAheadStr = $timeAheadWeeks . ' wk' . ($timeAheadWeeks != 1 ? 's' : '');
    }
@endphp

@if($isPdf)
{{-- PDF Layout --}}
<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: {{ $loop->last ?? false ? '0' : '16px' }};">
    <tr>
        <td>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="50%">
                        <div style="font-weight: 700; color: #1e40af; font-size: 14px;">{{ $goal->name }}</div>
                        <div style="font-size: 11px; color: #64748b;">Target: ${{ number_format($targetValue, 0) }} by {{ $goal->end_dt->format('M Y') }}</div>
                    </td>
                    <td width="25%" align="center">
                        <span style="font-size: 20px; font-weight: 700; color: {{ $progressColor }};">{{ number_format($currentPct, 0) }}%</span>
                    </td>
                    <td width="25%" align="right">
                        <span style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                            ${{ number_format(abs($diff), 0) }} or {{ $timeAheadStr }} {{ $isOnTrack ? 'ahead' : 'behind' }}
                        </span>
                    </td>
                </tr>
            </table>
            {{-- Progress Bar with Expected marker --}}
            <div style="background: #e2e8f0; border-radius: 4px; height: 8px; margin-top: 8px; overflow: hidden; position: relative;">
                <div style="position: absolute; left: {{ min(100, $expectedPct) }}%; top: 0; bottom: 0; width: 2px; background: #d97706; z-index: 2;"></div>
                <div style="background: {{ $progressColor }}; height: 100%; width: {{ min(100, $currentPct) }}%; border-radius: 4px;"></div>
            </div>
        </td>
    </tr>
</table>
@else
{{-- Web Layout (matching PDF style) --}}
<div class="mb-3 {{ $loop->last ?? false ? 'mb-0' : '' }}" style="background: #f8fafc; padding: 12px 16px; border-radius: 8px;">
    <div class="d-flex align-items-center mb-2">
        <div style="flex: 1;">
            <div style="font-weight: 700; color: #1e40af; font-size: 14px;">{{ $goal->name }}</div>
            <div class="small text-muted">Target: ${{ number_format($targetValue, 0) }} by {{ $goal->end_dt->format('M Y') }}</div>
        </div>
        <div style="flex: 1; text-align: center;">
            <span style="font-size: 1.75rem; font-weight: 700; color: {{ $progressColor }};">
                {{ number_format($currentPct, 0) }}%
            </span>
        </div>
        <div style="flex: 1; text-align: right;">
            <span class="px-2 py-1 rounded" style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; font-weight: 600; font-size: 12px;">
                ${{ number_format(abs($diff), 0) }} or {{ $timeAheadStr }} {{ $isOnTrack ? 'ahead' : 'behind' }}
            </span>
        </div>
    </div>
    {{-- Progress Bar with Expected marker --}}
    <div class="progress" style="height: 8px; background: #e2e8f0; position: relative;">
        <div style="position: absolute; left: {{ min(100, $expectedPct) }}%; top: 0; bottom: 0; width: 2px; background: #d97706; z-index: 2;"></div>
        <div class="progress-bar" role="progressbar"
             style="width: {{ min(100, $currentPct) }}%; background-color: {{ $progressColor }};"
             aria-valuenow="{{ $currentPct }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>
@endif
