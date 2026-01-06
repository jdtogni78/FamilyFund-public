@php
    $disb = $api['disbursable'];
    $performance = floatval($disb['performance'] ?? 0);
    $limit = floatval($disb['limit'] ?? 0);
    $value = floatval($disb['value'] ?? 0);
    $year = $disb['year'] ?? '';
    $performanceColor = $performance >= 0 ? '#16a34a' : '#dc2626';
    $capUsed = $limit > 0 ? min(100, ($performance / $limit) * 100) : 0;
@endphp

<div class="row g-3">
    {{-- Eligible Value --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100" style="border-left: 4px solid #2563eb;">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">Eligible Value</small>
                <h4 class="mb-0" style="color: #2563eb;">${{ number_format($value, 2) }}</h4>
            </div>
        </div>
    </div>

    {{-- Performance --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100" style="border-left: 4px solid {{ $performanceColor }};">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">Performance</small>
                <h4 class="mb-0" style="color: {{ $performanceColor }};">
                    @if($performance >= 0)+@endif{{ number_format($performance, 2) }}%
                </h4>
            </div>
        </div>
    </div>

    {{-- Cap --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100" style="border-left: 4px solid #d97706;">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">Cap Limit</small>
                <h4 class="mb-1" style="color: #d97706;">{{ number_format($limit, 2) }}%</h4>
                @if($limit > 0)
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ min(100, $capUsed) }}%; background-color: {{ $performance > $limit ? '#dc2626' : '#d97706' }};"
                         aria-valuenow="{{ $capUsed }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Year --}}
    <div class="col-md-6 col-lg-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <small class="text-muted d-block mb-1">Year</small>
                <h4 class="mb-0">{{ $year }}</h4>
            </div>
        </div>
    </div>
</div>
