@php
    $targetValue = $withdrawalGoal['target_value'];
    $yearlyExpenses = $withdrawalGoal['yearly_expenses'];
    $adjustedValue = $withdrawalGoal['adjusted_value'];
    $currentYield = $withdrawalGoal['current_yield'];
    $progressPct = $withdrawalGoal['progress_pct'];
    $netWorthPct = $withdrawalGoal['net_worth_pct'];
    $isReached = $withdrawalGoal['is_reached'] ?? false;
    $targetReach = $withdrawalGoal['target_reach'] ?? [];  // Without withdrawals (pure growth)
    $targetReachWithWithdrawals = $withdrawalGoal['target_reach_with_withdrawals'] ?? [];  // With withdrawals
    $withdrawalRate = $withdrawalGoal['withdrawal_rate'] ?? 4;
    $expectedGrowthRate = $withdrawalGoal['expected_growth_rate'] ?? 7;
    // Countdown mode fields
    $independenceMode = $withdrawalGoal['independence_mode'] ?? 'perpetual';
    $isCountdownMode = $independenceMode === 'countdown';
    $independenceTargetDate = $withdrawalGoal['independence_target_date'] ?? null;
    $yearsRemaining = $withdrawalGoal['years_remaining'] ?? null;
    $fundingPct = $withdrawalGoal['funding_pct'] ?? $progressPct;
    // Format target date for header
    $targetDateFormatted = $independenceTargetDate ? \Carbon\Carbon::parse($independenceTargetDate)->format('F Y') : null;
    // Calculate how much more is needed to be on track (only for countdown mode)
    $amountNeeded = max(0, $targetValue - $adjustedValue);
@endphp

<div class="row mb-4" id="section-withdrawal-goal">
    <div class="col">
        <div class="card" style="border: 2px solid {{ $isReached ? '#22c55e' : '#0d9488' }};">
            {{-- Header - different for perpetual vs countdown mode --}}
            <div class="card-header d-flex justify-content-between align-items-center" style="background: {{ $isReached ? '#22c55e' : '#0d9488' }}; color: white;">
                @if($isCountdownMode && $targetDateFormatted)
                    <strong><i class="fa fa-calendar-check me-2"></i>Independence until {{ $targetDateFormatted }}</strong>
                @else
                    <strong><i class="fa fa-bullseye me-2"></i>Financial Independence Goal</strong>
                @endif
                @isset($api['admin'])
                <a href="{{ route('funds.withdrawal_goal.edit', $fund->id) }}" class="btn btn-sm btn-outline-light">
                    <i class="fa fa-edit"></i> Edit
                </a>
                @endisset
            </div>

            <div class="card-body">
                @if($isCountdownMode)
                    {{-- ========== COUNTDOWN MODE DISPLAY ========== --}}

                    {{-- Prominent Funding Status --}}
                    <div class="text-center mb-4">
                        @if($isReached)
                            <div class="display-4 fw-bold text-success">
                                <i class="fa fa-check-circle me-2"></i>Funded!
                            </div>
                            <p class="text-muted mb-0">You're on track for {{ $targetDateFormatted }}</p>
                        @else
                            <div class="display-4 fw-bold" style="color: {{ $fundingPct >= 100 ? '#22c55e' : ($fundingPct >= 75 ? '#0d9488' : '#f59e0b') }};">
                                {{ number_format($fundingPct, 1) }}% Funded
                            </div>
                            @if($yearsRemaining !== null)
                                <p class="text-muted mb-0">{{ number_format($yearsRemaining, 1) }} years remaining</p>
                            @endif
                        @endif
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-4">
                        <div class="progress" style="height: 24px;">
                            <div class="progress-bar {{ $fundingPct >= 100 ? 'bg-success' : 'bg-info' }}" role="progressbar"
                                 style="width: {{ min(100, $fundingPct) }}%;"
                                 aria-valuenow="{{ $fundingPct }}" aria-valuemin="0" aria-valuemax="100">
                                @if($fundingPct >= 20)
                                    <span class="fw-bold">{{ number_format($fundingPct, 1) }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Stats Row for Countdown Mode --}}
                    <div class="row text-center g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded bg-secondary bg-opacity-10 border-start border-secondary border-4">
                                <small class="text-body-secondary d-block">Current Value</small>
                                <h5 class="mb-0" style="color: {{ $isReached ? '#22c55e' : '#0d9488' }};">${{ number_format($adjustedValue, 0) }}</h5>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded border-start border-4" style="background: #f0fdfa; border-color: #0d9488 !important;">
                                <small class="text-body-secondary d-block">Required Today</small>
                                <h5 class="mb-0" style="color: #0d9488;">${{ number_format($targetValue, 0) }}</h5>
                            </div>
                        </div>
                    </div>

                    {{-- Shortfall/Surplus Message --}}
                    @if(!$isReached && $amountNeeded > 0)
                        <div class="alert mb-0 d-flex align-items-center" style="background: #fef3c7; border-color: #f59e0b;">
                            <i class="fa fa-exclamation-triangle me-3" style="color: #f59e0b; font-size: 1.5rem;"></i>
                            <div>
                                <strong>Need ${{ number_format($amountNeeded, 0) }} more</strong> to be on track for {{ $targetDateFormatted }}
                                <br><small class="text-muted">Based on ${{ number_format($yearlyExpenses, 0) }}/year for {{ number_format($yearsRemaining, 1) }} years at {{ $expectedGrowthRate }}% growth</small>
                            </div>
                        </div>
                    @elseif($isReached)
                        <div class="alert alert-success mb-0 d-flex align-items-center">
                            <i class="fa fa-check-circle fa-2x me-3"></i>
                            <div>
                                <strong>On Track!</strong> Your current savings can fund ${{ number_format($yearlyExpenses, 0) }}/year through {{ $targetDateFormatted }}.
                            </div>
                        </div>
                    @endif

                @else
                    {{-- ========== PERPETUAL MODE DISPLAY (existing) ========== --}}

                    {{-- Target Explanation --}}
                    <div class="mb-3 p-3 rounded" style="background: #f0fdfa;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted">Target:</span>
                                <strong style="color: #0d9488; font-size: 1.25rem;">${{ number_format($targetValue, 0) }}</strong>
                                @if($netWorthPct < 100)
                                    <span class="badge bg-secondary ms-2" title="Using {{ number_format($netWorthPct, 0) }}% of fund value (reflecting your allocation)">{{ number_format($netWorthPct, 0) }}% of fund</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="text-muted small">to generate</span>
                                <strong style="color: #0d9488;">${{ number_format($yearlyExpenses, 0) }}/year</strong>
                                <span class="text-muted small">at {{ $withdrawalRate }}%</span>
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
                            <div class="p-3 rounded bg-warning border-start border-warning border-4">
                                <small class="d-block text-dark">{{ $withdrawalRate }}% Yield/Year</small>
                                <h5 class="mb-0 text-dark">${{ number_format($currentYield, 0) }}</h5>
                                <small class="text-dark">{{ number_format($currentYield / 12, 0) }}/mo</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded {{ $isReached ? 'bg-success' : 'bg-info' }} border-start border-4 {{ $isReached ? 'border-success' : 'border-info' }}">
                                <small class="d-block text-white">Target Value</small>
                                <h5 class="mb-0 text-white">${{ number_format($targetValue, 0) }}</h5>
                                <small class="text-white">${{ number_format($yearlyExpenses, 0) }}/yr needed</small>
                            </div>
                        </div>
                    </div>

                    {{-- Target Reach Projections --}}
                    @if(!empty($targetReach) || !empty($targetReachWithWithdrawals))
                    @if($isReached || ($targetReach['already_reached'] ?? false))
                        {{-- Goal already reached - single message --}}
                        <div class="alert alert-success mb-0 d-flex align-items-center">
                            <i class="fa fa-check-circle fa-2x me-3"></i>
                            <div>
                                <strong>Goal Reached!</strong> You have achieved your {{ $withdrawalRate }}% retirement target.
                            </div>
                        </div>
                    @else
                        {{-- Two projection columns --}}
                        <div class="row g-3 mb-0">
                            {{-- Without Withdrawals (Pure Growth) --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded border" style="background: #f0f9ff; border-color: #3b82f6 !important;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa fa-chart-line me-2" style="color: #3b82f6;"></i>
                                        <strong style="color: #1e40af;">Without Withdrawals</strong>
                                    </div>
                                    @if(!empty($targetReach))
                                        @if(!($targetReach['reachable'] ?? true))
                                            @if(($targetReach['reason'] ?? '') === 'no_growth')
                                                <span class="text-muted">Set growth rate to see projection</span>
                                            @else
                                                <span class="text-muted">Cannot calculate projection</span>
                                            @endif
                                        @elseif($targetReach['distant'] ?? false)
                                            <div class="fs-5 fw-bold" style="color: #1e40af;">{{ number_format($targetReach['years_from_now'], 0) }}+ years</div>
                                            <small class="text-muted">at {{ $expectedGrowthRate }}% growth</small>
                                        @else
                                            <div class="fs-5 fw-bold" style="color: #1e40af;">{{ $targetReach['estimated_date_formatted'] }}</div>
                                            <div>
                                                <span class="badge" style="background: #3b82f6;">{{ $targetReach['years_from_now'] }} yrs</span>
                                                <small class="text-muted ms-1">at {{ $expectedGrowthRate }}% growth</small>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">No projection available</span>
                                    @endif
                                </div>
                            </div>

                            {{-- With Withdrawals (Net Growth) --}}
                            <div class="col-md-6">
                                <div class="p-3 rounded border" style="background: #fef3c7; border-color: #f59e0b !important;">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa fa-minus-circle me-2" style="color: #f59e0b;"></i>
                                        <strong style="color: #92400e;">With {{ $withdrawalRate }}% Withdrawals</strong>
                                    </div>
                                    @if(!empty($targetReachWithWithdrawals))
                                        @php
                                            $netGrowthRate = $targetReachWithWithdrawals['net_growth_rate'] ?? ($expectedGrowthRate - $withdrawalRate);
                                        @endphp
                                        @if(!($targetReachWithWithdrawals['reachable'] ?? true))
                                            @if(($targetReachWithWithdrawals['reason'] ?? '') === 'withdrawals_exceed_growth')
                                                <div class="text-danger fw-bold">Cannot reach target</div>
                                                <small class="text-muted">Withdrawals exceed growth ({{ number_format($netGrowthRate, 1) }}% net)</small>
                                            @else
                                                <span class="text-muted">Cannot calculate projection</span>
                                            @endif
                                        @elseif($targetReachWithWithdrawals['distant'] ?? false)
                                            <div class="fs-5 fw-bold" style="color: #92400e;">{{ number_format($targetReachWithWithdrawals['years_from_now'], 0) }}+ years</div>
                                            <small class="text-muted">at {{ number_format($netGrowthRate, 1) }}% net growth</small>
                                        @else
                                            <div class="fs-5 fw-bold" style="color: #92400e;">{{ $targetReachWithWithdrawals['estimated_date_formatted'] }}</div>
                                            <div>
                                                <span class="badge" style="background: #f59e0b;">{{ $targetReachWithWithdrawals['years_from_now'] }} yrs</span>
                                                <small class="text-muted ms-1">at {{ number_format($netGrowthRate, 1) }}% net growth</small>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">No projection available</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    @endif
                @endif

                {{-- Allocation note now surfaced in the fund header —
                     see funds/show_ext.blade.php "Total Value" tile. --}}
            </div>
        </div>
    </div>
</div>
