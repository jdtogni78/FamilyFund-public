@extends('layouts.pdf_modern')

@section('report-type', 'Account Quarterly Report')

@section('content')
    @php ($account = $api['account'])

    <!-- Account Summary Section -->
    <div class="summary-box">
        <h2>{{ $account->nickname }}</h2>
        <div class="stat-grid">
            @isset($api['balances'][0])
                <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                    <div class="stat-value">${{ number_format($account->balances['OWN']->market_value ?? 0, 2) }}</div>
                    <div class="stat-label">Market Value</div>
                </div>
                <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                    <div class="stat-value">{{ number_format($account->balances['OWN']->shares ?? 0, 2) }}</div>
                    <div class="stat-label">Shares</div>
                </div>
            @endisset
            @if($api['matching_available'] > 0)
                <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                    <div class="stat-value">${{ number_format($api['matching_available'], 2) }}</div>
                    <div class="stat-label">Matching Available</div>
                </div>
            @endif
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">{{ count($account->goals) }}</div>
                <div class="stat-label">Active Goals</div>
            </div>
        </div>
    </div>

    <!-- Account Details Card -->
    <div class="card mb-5">
        <div class="card-header">
            <h4 class="card-header-title">Account Details</h4>
        </div>
        <div class="card-body">
            @include('accounts.show_fields_pdf')
        </div>
    </div>

    <!-- Goals Section -->
    @if(count($account->goals) > 0)
        <h3 class="section-title">Goals Progress</h3>

        @foreach($account->goals as $goal)
            <div class="goal-item avoid-break mb-4">
                <div class="goal-header">
                    <span class="goal-name">{{ $goal->name }}</span>
                    @if(isset($goal->progress))
                        <span class="badge {{ ($goal->progress['current_pct'] ?? 0) >= ($goal->progress['expected_pct'] ?? 0) ? 'badge-success' : 'badge-warning' }}">
                            {{ number_format($goal->progress['current_pct'] ?? 0, 1) }}% Complete
                        </span>
                    @endif
                </div>
                <div class="chart-container mt-3">
                    <img src="{{ $files['goals_progress_' . $goal->id . '.png'] }}" alt="{{ $goal->name }} Progress"/>
                </div>
                <div class="mt-3">
                    @include('goals.progress_details_pdf')
                </div>
            </div>
        @endforeach
    @endif

    <!-- Performance Charts -->
    <div class="page-break"></div>
    <h3 class="section-title">Performance Analysis</h3>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Monthly Performance</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['monthly_performance.png'] }}" alt="Monthly Performance"/>
            </div>
            <div class="mt-3 text-sm text-muted">
                <strong>Legend:</strong>
                <span><strong>Monthly Value</strong> - Account performance</span> |
                <span><strong>SP500</strong> - S&P 500 benchmark</span> |
                <span><strong>Cash</strong> - Cash equivalent</span>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Yearly Performance</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['yearly_performance.png'] }}" alt="Yearly Performance"/>
            </div>
        </div>
    </div>

    <!-- Shares Chart -->
    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Share Value Over Time</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['shares.png'] }}" alt="Shares"/>
            </div>
            <div class="mt-2 text-sm text-muted">
                Historical view of your share value in the fund
            </div>
        </div>
    </div>

    <!-- Performance Tables -->
    <div class="page-break"></div>
    <h3 class="section-title">Performance Data</h3>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Yearly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'yearly_performance')
            @include('accounts.performance_table_pdf')
        </div>
    </div>

    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Monthly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'monthly_performance')
            @include('accounts.performance_table_pdf')
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Transaction History</h4>
        </div>
        <div class="card-body">
            @include('accounts.transactions_table_pdf')
        </div>
    </div>

    <!-- Matching Rules (if available) -->
    @if($api['matching_available'] != 0)
        <div class="page-break"></div>
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Matching Rules</h4>
                <span class="badge badge-success">${{ number_format($api['matching_available'], 2) }} Available</span>
            </div>
            <div class="card-body">
                @include('accounts.matching_rules_table_pdf')
            </div>
        </div>
    @endif
@endsection
