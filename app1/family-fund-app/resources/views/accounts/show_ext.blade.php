<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('accounts.index') }}">Accounts</a>
        </li>
        <li class="breadcrumb-item active">{{ $account->nickname }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Account Highlights Card --}}
            @php
                $balance = $account->balances['OWN'] ?? null;
                $shares = $balance->shares ?? 0;
                $marketValue = $balance->market_value ?? 0;
                $sharePrice = $shares > 0 ? $marketValue / $shares : 0;
                $matchingAvailable = $api['matching_available'] ?? 0;
                $goalsCount = $account->goals->count();
                $disbursableValue = $api['disbursable']['value'] ?? 0;
            @endphp
            <div class="row mb-4" id="section-details">
                <div class="col">
                    <div class="card" style="border: 2px solid #1e40af; overflow: hidden;">
                        {{-- Header --}}
                        <div class="card-header py-3" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border: none;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="mb-1" style="color: #ffffff; font-weight: 700;">{{ $account->nickname }}</h4>
                                    <div style="color: #bfdbfe; font-size: 14px;">
                                        {{ $account->fund->name }} &bull; {{ $account->user->name }}
                                        @if($account->email_cc)
                                            &bull; {{ $account->email_cc }}
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm me-2">Back</a>
                                    <a href="/accounts/{{ $account->id }}/pdf_as_of/{{ $api['asOf'] ?? now()->format('Y-m-d') }}"
                                       class="btn btn-outline-light btn-sm" target="_blank" title="Download PDF Report">
                                        <i class="fa fa-file-pdf me-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Stats Row --}}
                        <div class="card-body py-3" style="background: #eff6ff;">
                            <div class="row text-center">
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #1e40af;">${{ number_format($marketValue, 2) }}</div>
                                    <div class="text-muted text-uppercase small">Market Value</div>
                                </div>
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #1e40af;">{{ number_format($shares, 2) }}</div>
                                    <div class="text-muted text-uppercase small">Shares</div>
                                </div>
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #1e40af;">${{ number_format($sharePrice, 2) }}</div>
                                    <div class="text-muted text-uppercase small">Share Price</div>
                                </div>
                                @if($account->disbursement_cap !== 0.0)
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #059669;">${{ number_format($disbursableValue, 0) }}</div>
                                    <div class="text-muted text-uppercase small">Eligible Disbursement</div>
                                </div>
                                @endif
                                @if($matchingAvailable > 0)
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #bfdbfe;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #16a34a;">${{ number_format($matchingAvailable, 2) }}</div>
                                    <div class="text-muted text-uppercase small">Matching Available</div>
                                </div>
                                @endif
                                @include('partials.highlights_growth', ['yearlyPerf' => $api['yearly_performance'] ?? []])
                            </div>
                        </div>

                        {{-- Goals Summary --}}
                        @if($goalsCount > 0)
                        <div class="card-body pt-0 pb-3" style="background: #ffffff; border-top: 1px solid #bfdbfe;">
                            <div class="text-muted text-uppercase small mb-2 mt-2" style="font-weight: 600;">Goals Summary</div>
                            @foreach($account->goals as $goal)
                                @php
                                    $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
                                    $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
                                    $currentValue = $goal->progress['current']['value'] ?? 0;
                                    $expectedValue = $goal->progress['expected']['value'] ?? 0;
                                    $diff = $currentValue - $expectedValue;
                                    $isOnTrack = $diff >= 0;

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
                                <div class="d-flex align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div style="flex: 1; color: #1e40af; font-weight: 600;">{{ $goal->name }}</div>
                                    <div style="flex: 1; text-align: center;">
                                        <span style="font-size: 1rem; font-weight: 700; color: {{ $isOnTrack ? '#16a34a' : '#d97706' }};">
                                            {{ number_format($currentPct, 1) }}%
                                        </span>
                                        <span class="text-muted ms-1 small">complete</span>
                                    </div>
                                    <div style="flex: 1; text-align: right;">
                                        <span class="px-2 py-1 rounded small" style="background: {{ $isOnTrack ? '#dcfce7' : '#fef2f2' }}; color: {{ $isOnTrack ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                                            ${{ number_format(abs($diff), 0) }} or {{ $timeAheadStr }} {{ $isOnTrack ? 'ahead' : 'behind' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Reusable Jump Bar --}}
            @include('partials.jump_bar', ['sections' => [
                ['id' => 'section-details', 'icon' => 'fa-user-circle', 'label' => 'Details'],
                ['id' => 'section-disbursement', 'icon' => 'fa-money-bill-wave', 'label' => 'Disbursement', 'condition' => $account->disbursement_cap !== 0.0],
                ['id' => 'section-goals', 'icon' => 'fa-bullseye', 'label' => 'Goals', 'condition' => $account->goals->count() > 0],
                ['id' => 'section-charts', 'icon' => 'fa-chart-line', 'label' => 'Charts'],
                ['id' => 'section-forecast', 'icon' => 'fa-chart-area', 'label' => 'Forecast', 'condition' => !empty($api['linear_regression']['predictions']) && $goalsCount > 0],
                ['id' => 'section-portfolios', 'icon' => 'fa-chart-bar', 'label' => 'Portfolios', 'condition' => isset($api['tradePortfolios']) && $api['tradePortfolios']->count() >= 1],
                ['id' => 'section-shares', 'icon' => 'fa-chart-area', 'label' => 'Shares'],
                ['id' => 'section-performance', 'icon' => 'fa-table', 'label' => 'Performance'],
                ['id' => 'section-transactions', 'icon' => 'fa-exchange-alt', 'label' => 'Transactions'],
                ['id' => 'section-matching', 'icon' => 'fa-hand-holding-usd', 'label' => 'Matching', 'condition' => !empty($api['matching_rules'])],
            ]])

            {{-- Disbursement Eligibility --}}
            @if($account->disbursement_cap !== 0.0)
            <div class="row mb-4" id="section-disbursement">
                <div class="col">
                    @include('accounts.disbursement')
                </div>
            </div>
            @endif

            {{-- Goals Section (Collapsible, start expanded) --}}
            @if($account->goals->count() > 0)
            <div class="row mb-4" id="section-goals">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-bullseye" style="margin-right: 8px;"></i>Goals</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseGoals"
                               role="button" aria-expanded="true" aria-controls="collapseGoals">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseGoals">
                            <div class="card-body">
                                @foreach($account->goals as $goal)
                                    <div class="card mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                                            <strong><i class="fa fa-bullseye" style="margin-right: 8px;"></i>{{ $goal->name }}</strong>
                                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white;">ID: {{ $goal->id }}</span>
                                        </div>
                                        <div class="card-body">
                                            @include('goals.progress_summary', ['goal' => $goal, 'format' => 'web'])
                                            @include('goals.progress_details_unified', ['goal' => $goal, 'format' => 'web'])
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Charts Row (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-charts">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-chart-line" style="margin-right: 8px;"></i>Monthly Value</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMonthlyValue"
                               role="button" aria-expanded="true" aria-controls="collapseMonthlyValue">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseMonthlyValue">
                            <div class="card-body">
                                @php($addSP500 = true)
                                @include('accounts.performance_line_graph')
                                @php($addSP500 = false)
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-chart-bar" style="margin-right: 8px;"></i>Yearly Value</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseYearlyValue"
                               role="button" aria-expanded="true" aria-controls="collapseYearlyValue">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseYearlyValue">
                            <div class="card-body">
                                @include('accounts.performance_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Forecast (Linear Regression) (Collapsible, start expanded) - only show if account has goals --}}
            @if(!empty($api['linear_regression']['predictions']) && $goalsCount > 0)
            <div class="row mb-4" id="section-forecast">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-chart-area" style="margin-right: 8px;"></i>Forecast (Linear Regression)</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseForecast"
                               role="button" aria-expanded="true" aria-controls="collapseForecast">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseForecast">
                            <div class="card-body">
                                @include('accounts.performance_line_graph_linreg')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-table" style="margin-right: 8px;"></i>Projection Table</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseProjection"
                               role="button" aria-expanded="true" aria-controls="collapseProjection">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseProjection">
                            <div class="card-body">
                                @include('accounts.linreg_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Shares Chart (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-shares">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-chart-area" style="margin-right: 8px;"></i>Shares History</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseShares"
                               role="button" aria-expanded="true" aria-controls="collapseShares">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseShares">
                            <div class="card-body">
                                <div>
                                    <canvas id="balancesGraph"></canvas>
                                    <div id="balancesGraphNoData" class="text-center text-muted py-5" style="display: none;">
                                        <i class="fa fa-chart-area fa-3x mb-3" style="color: #cbd5e1;"></i>
                                        <p>No shares history data available</p>
                                    </div>
                                </div>
                                @include('accounts.balances_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Tables Row (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-performance">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-table" style="margin-right: 8px;"></i>Yearly Performance</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseYearlyPerf"
                               role="button" aria-expanded="true" aria-controls="collapseYearlyPerf">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseYearlyPerf">
                            <div class="card-body">
                                @php ($performance_key = 'yearly_performance')
                                @include('accounts.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-table" style="margin-right: 8px;"></i>Monthly Performance</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMonthlyPerf"
                               role="button" aria-expanded="true" aria-controls="collapseMonthlyPerf">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseMonthlyPerf">
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                @php ($performance_key = 'monthly_performance')
                                @include('accounts.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-transactions">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                            <strong><i class="fa fa-exchange-alt" style="margin-right: 8px;"></i>Transactions</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseTransactions"
                               role="button" aria-expanded="true" aria-controls="collapseTransactions">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseTransactions">
                            <div class="card-body">
                                @include('accounts.transactions_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Matching Rules (Collapsible, start expanded) --}}
            @if(!empty($api['matching_rules']))
                <div class="row mb-4" id="section-matching">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: #ffffff;">
                                <strong><i class="fa fa-hand-holding-usd" style="margin-right: 8px;"></i>Matching Rules</strong>
                                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMatching"
                                   role="button" aria-expanded="true" aria-controls="collapseMatching">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse show" id="collapseMatching">
                                <div class="card-body">
                                    @include('accounts.matching_rules_table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
