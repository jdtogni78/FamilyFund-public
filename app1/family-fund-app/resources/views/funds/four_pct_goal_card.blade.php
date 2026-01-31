@php
    $targetValue = $fourPctGoal['target_value'];
    $yearlyExpenses = $fourPctGoal['yearly_expenses'];
    $adjustedValue = $fourPctGoal['adjusted_value'];
    $currentYield = $fourPctGoal['current_yield'];
    $progressPct = $fourPctGoal['progress_pct'];
    $netWorthPct = $fourPctGoal['net_worth_pct'];
    $isReached = $fourPctGoal['is_reached'] ?? false;
    $targetReach = $fourPctGoal['target_reach'] ?? [];
@endphp

<div class="row mb-4" id="section-four-pct-goal">
    <div class="col">
        <div class="card" style="border: 2px solid {{ $isReached ? '#22c55e' : '#0d9488' }};">
            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center" style="background: {{ $isReached ? '#22c55e' : '#0d9488' }}; color: white;">
                <strong><i class="fa fa-bullseye me-2"></i>4% Rule Retirement Goal</strong>
                @isset($api['admin'])
                <a href="{{ route('funds.four_pct_goal.edit', $fund->id) }}" class="btn btn-sm btn-outline-light">
                    <i class="fa fa-edit"></i> Edit
                </a>
                @endisset
            </div>

            <div class="card-body">
                {{-- Target Explanation --}}
                <div class="mb-3 p-3 rounded" style="background: #f0fdfa;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Target:</span>
                            <strong style="color: #0d9488; font-size: 1.25rem;">${{ number_format($targetValue, 0) }}</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-muted small">to generate</span>
                            <strong style="color: #0d9488;">${{ number_format($yearlyExpenses, 0) }}/year</strong>
                            <span class="text-muted small">at 4%</span>
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Progress</span>
                        <span class="badge {{ $isReached ? 'bg-success' : 'bg-info' }}">{{ number_format($progressPct, 1) }}%</span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar {{ $isReached ? 'bg-success' : 'bg-info' }}" role="progressbar"
                             style="width: {{ min(100, $progressPct) }}%;"
                             aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>

                {{-- Stats Row (reuse pattern from progress_details.blade.php) --}}
                <div class="row text-center g-3 mb-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-secondary bg-opacity-10 border-start border-secondary border-4">
                            <small class="text-body-secondary d-block">Current Value</small>
                            <h5 class="mb-0" style="color: {{ $isReached ? '#22c55e' : '#0d9488' }};">${{ number_format($adjustedValue, 0) }}</h5>
                            <small class="text-body-secondary">${{ number_format($currentYield, 0) }}/yr yield</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded bg-warning bg-opacity-25 border-start border-warning border-4">
                            <small class="text-body-secondary d-block">4% Yield/Year</small>
                            <h5 class="mb-0 text-warning">${{ number_format($currentYield, 0) }}</h5>
                            <small class="text-body-secondary">{{ number_format($currentYield / 12, 0) }}/mo</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded {{ $isReached ? 'bg-success' : 'bg-info' }} bg-opacity-25 border-start border-4 {{ $isReached ? 'border-success' : 'border-info' }}">
                            <small class="text-body-secondary d-block">Target Value</small>
                            <h5 class="mb-0 {{ $isReached ? 'text-success' : 'text-info' }}">${{ number_format($targetValue, 0) }}</h5>
                            <small class="text-body-secondary">${{ number_format($yearlyExpenses, 0) }}/yr needed</small>
                        </div>
                    </div>
                </div>

                {{-- Target Reach Projection --}}
                @if(!empty($targetReach))
                <div class="alert {{ $isReached ? 'alert-success' : 'alert-info' }} mb-0 d-flex align-items-center">
                    <i class="fa {{ $isReached ? 'fa-check-circle' : 'fa-clock' }} fa-2x me-3"></i>
                    <div>
                        @if($isReached || ($targetReach['already_reached'] ?? false))
                            <strong>Goal Reached!</strong> You have achieved your 4% retirement target.
                        @elseif(!($targetReach['reachable'] ?? true))
                            @if(($targetReach['reason'] ?? '') === 'negative_growth')
                                <strong>Growth Needed</strong> Current trend shows negative growth. Consider reviewing your strategy.
                            @else
                                <strong>Growth Needed</strong> No growth trend detected. Continue contributing to reach your goal.
                            @endif
                        @elseif($targetReach['distant'] ?? false)
                            <strong>Long-term Goal</strong> Based on current growth, target is {{ number_format($targetReach['years_from_now'], 0) }}+ years away.
                        @else
                            <strong>Estimated: {{ $targetReach['estimated_date_formatted'] }}</strong>
                            <span class="badge bg-info ms-2">{{ $targetReach['years_from_now'] }} years from now</span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Net Worth Adjustment Note --}}
                @if($netWorthPct < 100)
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fa fa-info-circle me-1"></i>
                        Using {{ number_format($netWorthPct, 0) }}% of fund value for this calculation (reflecting your allocation).
                    </small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
