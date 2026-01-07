@extends('layouts.pdf_modern')

@section('report-type', 'Account Quarterly Report')

@section('content')
    @php
        $account = $api['account'];
        $shares = $account->balances['OWN']->shares ?? 0;
        $marketValue = $account->balances['OWN']->market_value ?? 0;
        $sharePrice = $shares > 0 ? $marketValue / $shares : 0;
        $matchingAvailable = $api['matching_available'] ?? 0;
    @endphp

    <table width="100%" cellspacing="0" cellpadding="0" style="border: 2px solid #1e40af; margin-bottom: 16px;">
        <tr>
            <td style="padding: 12px; background-color: #1e40af;">
                <h2 style="margin: 0; color: #ffffff; font-size: 20px;">{{ $account->nickname }}</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px; background-color: #f8fafc;">
                <table width="100%" cellspacing="0" cellpadding="8">
                    <tr>
                        <td width="{{ $matchingAvailable > 0 ? '20%' : '25%' }}" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">${{ number_format($marketValue, 2) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Market Value</div>
                        </td>
                        <td width="{{ $matchingAvailable > 0 ? '20%' : '25%' }}" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ number_format($shares, 2) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Shares</div>
                        </td>
                        <td width="{{ $matchingAvailable > 0 ? '20%' : '25%' }}" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">${{ number_format($sharePrice, 2) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Share Price</div>
                        </td>
                        @if($matchingAvailable > 0)
                        <td width="20%" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #16a34a;">${{ number_format($matchingAvailable, 2) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Matching Available</div>
                        </td>
                        @endif
                        <td width="{{ $matchingAvailable > 0 ? '20%' : '25%' }}" align="center">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ count($account->goals) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Active Goals</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @if(count($account->goals) > 0)
        <tr>
            <td style="padding: 12px; background-color: #ffffff; border-top: 1px solid #e2e8f0;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 8px; font-weight: 600;">Goals Summary</div>
                <table width="100%" cellspacing="0" cellpadding="4">
                    @foreach($account->goals as $goal)
                        @php
                            $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
                            $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
                            $currentValue = $goal->progress['current']['value'] ?? 0;
                            $expectedValue = $goal->progress['expected']['value'] ?? 0;
                            $diff = $currentValue - $expectedValue;
                            $isOnTrack = $diff >= 0;

                            // Calculate status text
                            if ($isOnTrack) {
                                $statusText = '$' . number_format(abs($diff), 0) . ' ahead';
                                $statusColor = '#16a34a';
                                $statusBg = '#dcfce7';
                            } else {
                                $statusText = '$' . number_format(abs($diff), 0) . ' behind';
                                $statusColor = '#dc2626';
                                $statusBg = '#fef2f2';
                            }
                        @endphp
                        <tr>
                            <td style="width: 40%; padding: 6px 8px;">
                                <strong style="color: #1e40af;">{{ $goal->name }}</strong>
                            </td>
                            <td style="width: 30%; padding: 6px 8px; text-align: center;">
                                <span style="font-size: 14px; font-weight: 700; color: {{ $isOnTrack ? '#16a34a' : '#d97706' }};">
                                    {{ number_format($currentPct, 1) }}%
                                </span>
                                <span style="color: #64748b; font-size: 11px;"> complete</span>
                            </td>
                            <td style="width: 30%; padding: 6px 8px; text-align: right;">
                                <span style="background: {{ $statusBg }}; color: {{ $statusColor }}; padding: 3px 10px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                                    {{ $statusText }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        @endif
    </table>

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
                    @if(isset($goal->progress['current']['completed_pct']))
                        @php
                            $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
                            $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
                        @endphp
                        <span class="badge {{ $currentPct >= $expectedPct ? 'badge-success' : 'badge-warning' }}">
                            {{ number_format($currentPct, 1) }}% Complete
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

    <!-- Shares Holdings Chart -->
    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Shares Holdings Over Time</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['shares.png'] }}" alt="Shares Holdings"/>
            </div>
            <div class="mt-2 text-sm text-muted">
                Historical view of your shares holdings in the fund
            </div>
        </div>
    </div>

    <!-- Portfolio Allocations Comparison -->
    @if(isset($files['portfolio_comparison.png']))
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Portfolio Allocations Comparison</h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['portfolio_comparison.png'] }}" alt="Portfolio Comparison" style="width: 100%;"/>
                </div>
            </div>
        </div>
    @endif

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
