{{--
    Goals Progress Details - Unified layout for PDF and Web
    Shows Expected vs Current comparison with 4% yield explanation, time ahead/behind, and On Track badge

    Required: $goal (with ->progress array populated)
    Optional: $format ('web'|'pdf', defaults to 'web')
--}}
@php
    $format = $format ?? 'web';
    $isPdf = $format === 'pdf';

    $progress = $goal->progress;
    $current = $progress['current'] ?? [];
    $expected = $progress['expected'] ?? [];
    $period = $progress['period'] ?? [0, 1, 0];

    $currentValue = $current['value'] ?? 0;
    $expectedValue = $expected['value'] ?? 0;
    $finalValue = $current['final_value'] ?? $goal->target_amount;

    // 4% yield calculations
    $yieldRate = $goal->target_pct ?? 0.04;
    $yieldPct = $yieldRate * 100;
    $currentYield = $current['value_4pct'] ?? ($currentValue * $yieldRate);
    $expectedYield = $expected['value_4pct'] ?? ($expectedValue * $yieldRate);
    $finalYield = $current['final_value_4pct'] ?? ($finalValue * $yieldRate);

    $diff = $currentValue - $expectedValue;
    $isOnTrack = $diff >= 0;

    $currentPct = $current['completed_pct'] ?? 0;
    $expectedPct = $expected['completed_pct'] ?? 0;

    // Time calculations
    $daysElapsed = $period[0] ?? 0;
    $totalDays = $period[1] ?? 1;
    $timePct = $period[2] ?? 0;
    $yearsElapsed = $daysElapsed / 365;
    $totalYears = $totalDays / 365;

    // Calculate time ahead/behind
    $pctDiff = abs($currentPct - $expectedPct);
    $timeAheadDays = ($pctDiff / 100) * $totalDays;
    if ($timeAheadDays >= 365) {
        $timeAheadYears = $timeAheadDays / 365;
        $timeAheadStr = number_format($timeAheadYears, 1) . ' year' . ($timeAheadYears >= 1.5 ? 's' : '');
    } elseif ($timeAheadDays >= 30) {
        $timeAheadMonths = $timeAheadDays / 30;
        $timeAheadStr = number_format($timeAheadMonths, 0) . ' month' . ($timeAheadMonths != 1 ? 's' : '');
    } else {
        $timeAheadWeeks = max(1, round($timeAheadDays / 7));
        $timeAheadStr = number_format($timeAheadWeeks, 0) . ' week' . ($timeAheadWeeks != 1 ? 's' : '');
    }

    $isTargetTotal = $goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL;

    // Colors
    $trackColor = $isOnTrack ? '#16a34a' : '#dc2626';
    $trackBg = $isOnTrack ? '#dcfce7' : '#fef2f2';
    $trackBorder = $isOnTrack ? '#16a34a' : '#dc2626';
@endphp

@if($isPdf)
{{-- PDF Layout --}}
<div style="background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 12px;">
    {{-- Target explanation with 4% rule --}}
    <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
        <strong style="font-size: 13px;">Target:</strong>
        @if($isTargetTotal)
            Reach <strong style="color: #1e40af;">${{ number_format($finalValue, 0) }}</strong> account value
            <span style="color: #64748b;">(generating ${{ number_format($finalYield, 0) }}/year at {{ $yieldPct }}% yield)</span>
        @else
            Generate <strong style="color: #1e40af;">${{ number_format($goal->target_amount, 0) }}/year</strong> passive income
            <span style="color: #64748b;">(requires ${{ number_format($finalValue, 0) }} at {{ $yieldPct }}% yield)</span>
        @endif
        by <strong>{{ $goal->end_dt->format('M Y') }}</strong>
    </div>

    {{-- Expected vs Current comparison (2 columns) --}}
    <table style="width: 100%; margin-bottom: 12px;">
        <tr>
            {{-- Expected Box --}}
            <td width="48%" style="background: #fef9c3; border: 1px solid #fcd34d; border-radius: 8px; padding: 12px; text-align: center; vertical-align: top;">
                <div style="font-size: 10px; text-transform: uppercase; color: #92400e; margin-bottom: 4px;">Expected ({{ $goal->as_of ?? now()->format('Y-m-d') }})</div>
                <div style="font-size: 20px; font-weight: 700; color: #d97706;">${{ number_format($expectedValue, 0) }}</div>
                <div style="font-size: 10px; color: #92400e;">${{ number_format($expectedYield, 0) }}/yr yield</div>
            </td>
            <td width="4%"></td>
            {{-- Current Box --}}
            <td width="48%" style="background: {{ $trackBg }}; border: 1px solid {{ $trackBorder }}; border-radius: 8px; padding: 12px; text-align: center; vertical-align: top;">
                <div style="font-size: 10px; text-transform: uppercase; color: {{ $isOnTrack ? '#166534' : '#991b1b' }}; margin-bottom: 4px;">Current ({{ $goal->as_of ?? now()->format('Y-m-d') }})</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $trackColor }};">${{ number_format($currentValue, 0) }}</div>
                <div style="font-size: 10px; color: {{ $isOnTrack ? '#166534' : '#991b1b' }};">${{ number_format($currentYield, 0) }}/yr yield</div>
            </td>
        </tr>
    </table>

    {{-- Time progress --}}
    <div style="margin-bottom: 12px; color: #64748b;">
        <strong>Time:</strong> {{ number_format($yearsElapsed, 1) }} of {{ number_format($totalYears, 1) }} years elapsed ({{ number_format($timePct, 0) }}%)
    </div>

    {{-- On Track Badge with time ahead/behind --}}
    <div style="padding: 10px; border-radius: 6px; background: {{ $trackBg }}; border: 1px solid {{ $trackBorder }};">
        @if($isOnTrack)
            <span style="color: #16a34a; font-weight: 700; font-size: 14px;">ON TRACK!</span>
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
@else
{{-- Web Layout --}}
<div class="bg-light p-3 rounded">
    {{-- Target explanation with 4% rule --}}
    <div class="mb-3 pb-3 border-bottom">
        <strong>Target:</strong>
        @if($isTargetTotal)
            Reach <strong style="color: #2563eb;">${{ number_format($finalValue, 0) }}</strong> account value
            <span class="text-muted">(generating ${{ number_format($finalYield, 0) }}/year at {{ $yieldPct }}% yield)</span>
        @else
            Generate <strong style="color: #2563eb;">${{ number_format($goal->target_amount, 0) }}/year</strong> passive income
            <span class="text-muted">(requires ${{ number_format($finalValue, 0) }} at {{ $yieldPct }}% yield)</span>
        @endif
        by <strong>{{ $goal->end_dt->format('M Y') }}</strong>
    </div>

    {{-- Expected vs Current comparison (2 columns) --}}
    <div class="row text-center g-3 mb-3">
        {{-- Expected Box --}}
        <div class="col-md-6">
            <div class="p-3 rounded h-100" style="background: #fef3c7; border-left: 4px solid #d97706;">
                <small class="text-muted d-block">Expected ({{ $goal->as_of ?? now()->format('Y-m-d') }})</small>
                <h5 class="mb-0" style="color: #d97706;">${{ number_format($expectedValue, 0) }}</h5>
                <small class="text-muted">${{ number_format($expectedYield, 0) }}/yr yield</small>
            </div>
        </div>
        {{-- Current Box --}}
        <div class="col-md-6">
            <div class="p-3 rounded h-100" style="background: {{ $trackBg }}; border-left: 4px solid {{ $trackBorder }};">
                <small class="text-muted d-block">Current ({{ $goal->as_of ?? now()->format('Y-m-d') }})</small>
                <h5 class="mb-0" style="color: {{ $trackColor }};">${{ number_format($currentValue, 0) }}</h5>
                <small class="text-muted">${{ number_format($currentYield, 0) }}/yr yield</small>
            </div>
        </div>
    </div>

    {{-- Time progress --}}
    <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
            <span><strong>Time Progress:</strong></span>
            <span>{{ number_format($yearsElapsed, 1) }} / {{ number_format($totalYears, 1) }} years</span>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-info" role="progressbar"
                 style="width: {{ min(100, $timePct) }}%;"
                 aria-valuenow="{{ $timePct }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <small class="text-muted">{{ number_format($timePct, 1) }}% of time elapsed</small>
    </div>

    {{-- On Track Badge with time ahead/behind --}}
    <div class="alert {{ $isOnTrack ? 'alert-success' : 'alert-danger' }} mb-0 d-flex align-items-center">
        <i class="fa {{ $isOnTrack ? 'fa-check-circle' : 'fa-exclamation-triangle' }} fa-2x me-3"></i>
        <div>
            @if($isOnTrack)
                <strong>On Track!</strong> Currently ahead by <span class="badge bg-success">${{ number_format($diff, 0) }}</span>
                <span class="ms-2 text-muted">({{ $timeAheadStr }} ahead of schedule)</span>
            @else
                <strong>Behind Schedule</strong> Currently behind by <span class="badge bg-danger">${{ number_format(abs($diff), 0) }}</span>
                <span class="ms-2 text-muted">({{ $timeAheadStr }} behind schedule)</span>
            @endif
        </div>
    </div>
</div>
@endif
