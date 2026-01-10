@php
    $isOnTrack = $goal->progress['current']['value'] >= $goal->progress['expected']['value'];
    $difference = abs($goal->progress['current']['value'] - $goal->progress['expected']['value']);
    $periodYears = $goal->progress['period'][0] / 365;
    $totalYears = $goal->progress['period'][1] / 365;
    $timePct = $goal->progress['period'][2];

    $isTargetTotal = $goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL;
    $targetValue = $isTargetTotal ? $goal->target_amount : ($goal->target_amount / $goal->target_pct);
    $targetYield = $isTargetTotal ? ($goal->target_amount * $goal->target_pct) : $goal->target_amount;
@endphp

<div class="row g-3">
    {{-- Goal Target --}}
    <div class="col-md-6">
        <div class="card h-100 bg-light">
            <div class="card-body">
                <h6 class="text-muted mb-2"><i class="fa fa-bullseye me-1"></i> Target</h6>
                @if($isTargetTotal)
                    <div class="mb-2">
                        <span class="text-muted">Target Value:</span>
                        <strong class="float-end" style="color: #2563eb;">${{ number_format($goal->target_amount, 0) }}</strong>
                    </div>
                    <div>
                        <span class="text-muted">Implied Yield:</span>
                        <strong class="float-end">${{ number_format($targetYield, 0) }}/yr</strong>
                    </div>
                @else
                    <div class="mb-2">
                        <span class="text-muted">Target Yield:</span>
                        <strong class="float-end" style="color: #2563eb;">${{ number_format($goal->target_amount, 0) }}/yr</strong>
                    </div>
                    <div>
                        <span class="text-muted">Implied Value:</span>
                        <strong class="float-end">${{ number_format($targetValue, 0) }}</strong>
                    </div>
                @endif
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span class="text-muted">End Date:</span>
                    <span class="badge bg-secondary">{{ $goal->end_dt->format('M d, Y') }}</span>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted">Yield Rate:</span>
                    <span class="badge bg-info">{{ $goal->target_pct * 100 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Time Progress --}}
    <div class="col-md-6">
        <div class="card h-100 bg-light">
            <div class="card-body">
                <h6 class="text-muted mb-2"><i class="fa fa-hourglass-half me-1"></i> Time Progress</h6>
                <div class="mb-2">
                    <span class="text-muted">Years Passed:</span>
                    <strong class="float-end">{{ number_format($periodYears, 1) }} / {{ number_format($totalYears, 1) }}</strong>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar bg-info" role="progressbar"
                         style="width: {{ min(100, $timePct) }}%;"
                         aria-valuenow="{{ $timePct }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-end">
                    <small class="text-muted">{{ number_format($timePct, 1) }}% of time elapsed</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Value Comparison --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted mb-3"><i class="fa fa-balance-scale me-1"></i> Value Comparison</h6>
                <div class="row text-center g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: #f1f5f9; border-left: 4px solid #64748b;">
                            <small class="text-muted d-block">Starting ({{ $goal->start_dt->format('Y-m-d') }})</small>
                            <h5 class="mb-0" style="color: #475569;">${{ number_format($goal->progress['start_value']['value'], 0) }}</h5>
                            <small class="text-muted">${{ number_format($goal->progress['start_value']['value_4pct'], 0) }}/yr yield</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: #fef3c7; border-left: 4px solid #d97706;">
                            <small class="text-muted d-block">Expected ({{ $goal->as_of }})</small>
                            <h5 class="mb-0" style="color: #d97706;">${{ number_format($goal->progress['expected']['value'], 0) }}</h5>
                            <small class="text-muted">${{ number_format($goal->progress['expected']['value_4pct'], 0) }}/yr yield</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded" style="background: {{ $isOnTrack ? '#dcfce7' : '#fee2e2' }}; border-left: 4px solid {{ $isOnTrack ? '#16a34a' : '#dc2626' }};">
                            <small class="text-muted d-block">Current ({{ $goal->as_of }})</small>
                            <h5 class="mb-0" style="color: {{ $isOnTrack ? '#16a34a' : '#dc2626' }};">${{ number_format($goal->progress['current']['value'], 0) }}</h5>
                            <small class="text-muted">${{ number_format($goal->progress['current']['value_4pct'], 0) }}/yr yield</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status --}}
    <div class="col-12">
        <div class="alert {{ $isOnTrack ? 'alert-success' : 'alert-danger' }} mb-0 d-flex align-items-center">
            <i class="fa {{ $isOnTrack ? 'fa-check-circle' : 'fa-exclamation-triangle' }} fa-2x me-3"></i>
            <div>
                @if($isOnTrack)
                    <strong>On Track!</strong> Currently ahead by <span class="badge bg-success">${{ number_format($difference, 0) }}</span>
                @else
                    <strong>Behind Schedule</strong> Currently behind by <span class="badge bg-danger">${{ number_format($difference, 0) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
