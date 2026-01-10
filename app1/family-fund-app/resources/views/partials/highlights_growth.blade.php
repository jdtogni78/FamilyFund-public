{{--
    Reusable growth stats for highlights card (shows prev year + current year)

    Usage:
    @include('partials.highlights_growth', ['yearlyPerf' => $api['yearly_performance'] ?? []])
--}}
@php
    $yearlyPerf = $yearlyPerf ?? [];
    $currentYear = date('Y');

    // Get all years sorted
    $years = array_keys($yearlyPerf);
    rsort($years); // Most recent first

    // Current year (YTD)
    $currentYearKey = null;
    $currentYearGrowth = 0;
    foreach ($years as $y) {
        if (substr($y, 0, 4) == $currentYear) {
            $currentYearKey = $y;
            $currentYearGrowth = $yearlyPerf[$y]['performance'] ?? 0;
            break;
        }
    }

    // Previous year (complete)
    $prevYearKey = null;
    $prevYearGrowth = 0;
    $prevYear = $currentYear - 1;
    foreach ($years as $y) {
        if (substr($y, 0, 4) == $prevYear) {
            $prevYearKey = $y;
            $prevYearGrowth = $yearlyPerf[$y]['performance'] ?? 0;
            break;
        }
    }

    // All-time performance (compound all years)
    $allTimeGrowth = 0;
    if (!empty($yearlyPerf)) {
        $compound = 1.0;
        foreach ($yearlyPerf as $y => $data) {
            $perf = ($data['performance'] ?? 0) / 100;
            $compound *= (1 + $perf);
        }
        $allTimeGrowth = ($compound - 1) * 100;
    }
@endphp

{{-- Previous Year Growth --}}
@if($prevYearKey)
<div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $prevYearGrowth >= 0 ? '#16a34a' : '#dc2626' }};">
        @if($prevYearGrowth >= 0)+@endif{{ number_format($prevYearGrowth, 1) }}%
    </div>
    <div class="text-muted text-uppercase small">{{ $prevYear }} Growth</div>
</div>
@endif

{{-- Current Year Growth (YTD) --}}
@if($currentYearKey)
<div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $currentYearGrowth >= 0 ? '#16a34a' : '#dc2626' }};">
        @if($currentYearGrowth >= 0)+@endif{{ number_format($currentYearGrowth, 1) }}%
    </div>
    <div class="text-muted text-uppercase small">{{ $currentYear }} YTD</div>
</div>
@endif

{{-- All-Time Growth --}}
@if(!empty($yearlyPerf))
<div class="col mb-3 mb-md-0">
    <div style="font-size: 1.25rem; font-weight: 700; color: {{ $allTimeGrowth >= 0 ? '#16a34a' : '#dc2626' }};">
        @if($allTimeGrowth >= 0)+@endif{{ number_format($allTimeGrowth, 1) }}%
    </div>
    <div class="text-muted text-uppercase small">All-Time</div>
</div>
@endif
