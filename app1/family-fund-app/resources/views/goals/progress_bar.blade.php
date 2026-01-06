@php
    $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
    $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
    $isOnTrack = $currentPct >= $expectedPct;
@endphp

<div class="mb-3">
    {{-- Expected Progress --}}
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="text-muted">
            <i class="fa fa-clock me-1"></i> Expected Progress
        </span>
        <span class="badge" style="background-color: #d97706;">{{ number_format($expectedPct, 1) }}%</span>
    </div>
    <div class="progress mb-3" style="height: 20px;">
        <div class="progress-bar" role="progressbar"
             style="width: {{ min(100, $expectedPct) }}%; background-color: #d97706;"
             aria-valuenow="{{ $expectedPct }}" aria-valuemin="0" aria-valuemax="100">
            @if($expectedPct > 15)
                <span class="fw-bold">{{ number_format($expectedPct, 1) }}%</span>
            @endif
        </div>
    </div>

    {{-- Current Progress --}}
    <div class="d-flex justify-content-between align-items-center mb-1">
        <span class="text-muted">
            <i class="fa fa-chart-line me-1"></i> Current Progress
        </span>
        <span class="badge" style="background-color: {{ $isOnTrack ? '#16a34a' : '#dc2626' }};">{{ number_format($currentPct, 1) }}%</span>
    </div>
    <div class="progress" style="height: 20px;">
        <div class="progress-bar" role="progressbar"
             style="width: {{ min(100, $currentPct) }}%; background-color: {{ $isOnTrack ? '#16a34a' : '#dc2626' }};"
             aria-valuenow="{{ $currentPct }}" aria-valuemin="0" aria-valuemax="100">
            @if($currentPct > 15)
                <span class="fw-bold">{{ number_format($currentPct, 1) }}%</span>
            @endif
        </div>
    </div>
</div>
