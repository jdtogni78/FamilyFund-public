@extends('layouts.pdf_modern')

@section('report-type', isset($api['admin']) ? 'Admin Fund Report' : 'Fund Report')

@section('content')
    {{-- Data Staleness Warning Banner --}}
    @if(isset($api['data_staleness']) && $api['data_staleness']['is_stale'])
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px; border: 2px solid #f59e0b; border-radius: 6px; overflow: hidden; background: #fffbeb;">
        <tr>
            <td style="padding: 12px 16px;">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="32" style="vertical-align: middle;">
                            <span style="font-size: 20px;">⚠️</span>
                        </td>
                        <td style="vertical-align: middle; padding-left: 8px;">
                            <span style="color: #92400e; font-weight: 600; font-size: 13px;">Data Warning:</span>
                            <span style="color: #78350f; font-size: 12px;">{{ $api['data_staleness']['message'] ?? 'Portfolio data may be stale' }}</span>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <span style="background: #f59e0b; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                {{ $api['data_staleness']['trading_days_stale'] }} TRADING DAY{{ $api['data_staleness']['trading_days_stale'] > 1 ? 'S' : '' }} DELAYED
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    @php
        $totalShares = $api['summary']['shares'];
        $shareValue = $api['summary']['share_value'];
        $totalValue = $api['summary']['value'];
        $allocatedPct = $api['summary']['allocated_shares_percent'];
        $unallocatedPct = $api['summary']['unallocated_shares_percent'] ?? (100 - $allocatedPct);
        $allocatedShares = $totalShares * $allocatedPct / 100;
        $unallocatedShares = $totalShares - $allocatedShares;
        $allocatedValue = $allocatedShares * $shareValue;
        $unallocatedValue = $unallocatedShares * $shareValue;

        // Growth calculations
        $yearlyPerf = $api['yearly_performance'] ?? [];
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;
        $years = array_keys($yearlyPerf);

        // Previous year growth
        $prevYearKey = null;
        $prevYearGrowth = 0;
        foreach ($years as $y) {
            if (substr($y, 0, 4) == $prevYear) {
                $prevYearKey = $y;
                $prevYearGrowth = $yearlyPerf[$y]['performance'] ?? 0;
                break;
            }
        }

        // Current year YTD
        $currentYearKey = null;
        $currentYearGrowth = 0;
        foreach ($years as $y) {
            if (substr($y, 0, 4) == $currentYear) {
                $currentYearKey = $y;
                $currentYearGrowth = $yearlyPerf[$y]['performance'] ?? 0;
                break;
            }
        }

        // All-time growth (compound)
        $allTimeGrowth = 0;
        if (!empty($yearlyPerf)) {
            $compound = 1.0;
            foreach ($yearlyPerf as $y => $data) {
                $perf = ($data['performance'] ?? 0) / 100;
                $compound *= (1 + $perf);
            }
            $allTimeGrowth = ($compound - 1) * 100;
        }

        $accountsCount = count($api['balances'] ?? []);
        $asOf = $api['as_of'] ?? date('Y-m-d');
        $source = $api['portfolio']['source'] ?? 'N/A';
    @endphp

    <!-- Fund Highlights Card -->
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px; border: 2px solid #0d9488; border-radius: 6px; overflow: hidden;">
        <!-- Header -->
        <tr>
            <td colspan="7" style="padding: 12px 16px; background-color: #0d9488;">
                <span style="color: #ffffff; font-size: 18px; font-weight: 700;">{{ $api['name'] }}</span>
                @isset($api['admin'])
                    <span style="background: #d97706; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px; font-weight: 600;">ADMIN</span>
                @endisset
            </td>
        </tr>
        <!-- Stats Row -->
        <tr>
            <td style="background: #f0fdfa; padding: 12px 8px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #0d9488;">${{ number_format($totalValue, 0) }}</div>
                <div style="font-size: 10px; color: #0f766e; text-transform: uppercase;">Total Value</div>
            </td>
            <td style="background: #f0fdfa; padding: 12px 8px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #0d9488;">${{ number_format($shareValue, 2) }}</div>
                <div style="font-size: 10px; color: #0f766e; text-transform: uppercase;">Share Price</div>
            </td>
            @if($prevYearKey)
            <td style="background: #f0fdfa; padding: 12px 8px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: {{ $prevYearGrowth >= 0 ? '#2563eb' : '#dc2626' }};">@if($prevYearGrowth >= 0)+@endif{{ number_format($prevYearGrowth, 1) }}%</div>
                <div style="font-size: 10px; color: #0f766e; text-transform: uppercase;">{{ $prevYear }} Growth</div>
            </td>
            @endif
            @if($currentYearKey)
            <td style="background: #f0fdfa; padding: 12px 8px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: {{ $currentYearGrowth >= 0 ? '#2563eb' : '#dc2626' }};">@if($currentYearGrowth >= 0)+@endif{{ number_format($currentYearGrowth, 1) }}%</div>
                <div style="font-size: 10px; color: #0f766e; text-transform: uppercase;">{{ $currentYear }} YTD</div>
            </td>
            @endif
            @if(!empty($yearlyPerf))
            <td style="background: #f0fdfa; padding: 12px 8px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: {{ $allTimeGrowth >= 0 ? '#2563eb' : '#dc2626' }};">@if($allTimeGrowth >= 0)+@endif{{ number_format($allTimeGrowth, 1) }}%</div>
                <div style="font-size: 10px; color: #0f766e; text-transform: uppercase;">All-Time</div>
            </td>
            @endif
            @isset($api['admin'])
            <td style="background: #fffbeb; padding: 12px 8px; text-align: center; border-radius: 0;">
                <div style="font-size: 18px; font-weight: 700; color: #d97706;">{{ $accountsCount }}</div>
                <div style="font-size: 10px; color: #92400e; text-transform: uppercase;">Accounts <span style="background: #d97706; color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 8px; vertical-align: top;">ADMIN</span></div>
            </td>
            @endisset
        </tr>
        <!-- Admin: Share Allocation Section -->
        @isset($api['admin'])
        <tr>
            <td colspan="7" style="background: #fffbeb; padding: 12px 16px; border-top: 1px solid #99f6e4;">
                <div style="margin-bottom: 8px;">
                    <span style="background: #d97706; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-right: 8px;">ADMIN</span>
                    <span style="font-size: 12px; color: #0f766e; font-weight: 600;">Share Allocation</span>
                </div>
                <!-- Progress Bar -->
                <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 10px;">
                    <tr>
                        <td width="{{ $allocatedPct }}%" style="background-color: #22c55e; padding: 6px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 12px; {{ $allocatedPct > 0 ? 'border-radius: 4px 0 0 4px;' : '' }}">
                            {{ number_format($allocatedPct, 1) }}%
                        </td>
                        <td width="{{ $unallocatedPct }}%" style="background-color: #d97706; padding: 6px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 12px; {{ $unallocatedPct > 0 ? 'border-radius: 0 4px 4px 0;' : '' }}">
                            {{ number_format($unallocatedPct, 1) }}%
                        </td>
                    </tr>
                </table>
                <!-- Allocated / Unallocated Boxes -->
                <table width="100%" cellspacing="8" cellpadding="0">
                    <tr>
                        <td width="50%" style="background-color: #22c55e; padding: 10px 12px; border-radius: 4px; vertical-align: top;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color: #ffffff;">
                                        <span style="font-size: 12px; font-weight: 700;">Allocated</span>
                                        <span style="background-color: rgba(255,255,255,0.2); padding: 1px 4px; border-radius: 3px; font-size: 10px; margin-left: 4px;">{{ number_format($allocatedPct, 1) }}%</span>
                                    </td>
                                    <td style="text-align: right; color: #ffffff;">
                                        <span style="font-size: 11px; font-weight: 600;">{{ number_format($allocatedShares, 2) }} shares</span><br>
                                        <span style="font-size: 16px; font-weight: 700;">${{ number_format($allocatedValue, 0) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" style="background-color: #d97706; padding: 10px 12px; border-radius: 4px; vertical-align: top;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color: #ffffff;">
                                        <span style="font-size: 12px; font-weight: 700;">Unallocated</span>
                                        <span style="background-color: rgba(255,255,255,0.2); padding: 1px 4px; border-radius: 3px; font-size: 10px; margin-left: 4px;">{{ number_format($unallocatedPct, 1) }}%</span>
                                    </td>
                                    <td style="text-align: right; color: #ffffff;">
                                        <span style="font-size: 11px; font-weight: 600;">{{ number_format($unallocatedShares, 2) }} shares</span><br>
                                        <span style="font-size: 16px; font-weight: 700;">${{ number_format($unallocatedValue, 0) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endisset
        <!-- As of / Source Row -->
        <tr>
            <td colspan="7" style="background: #ffffff; padding: 8px 16px; border-top: 1px solid #99f6e4;">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="font-size: 11px; color: #374151;"><strong>As of:</strong> {{ $asOf }}</td>
                        <td style="font-size: 11px; color: #374151; text-align: center;"><strong>Source:</strong> {{ $source }}</td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Monthly Performance Chart -->
    <h3 class="section-title">Performance Analysis</h3>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/chart-line.svg') }}" class="header-icon">Monthly Value</h4>
        </div>
        <div class="card-body">
            @if(isset($files['monthly_performance.png']) && file_exists($files['monthly_performance.png']))
                <div class="chart-container">
                    <img src="{{ $files['monthly_performance.png'] }}" alt="Monthly Value"/>
                </div>
                <p class="text-sm text-muted" style="margin-top: 8px;">
                    <strong>Legend:</strong> Fund vs S&P 500 vs Cash equivalent
                </p>
            @else
                <div class="text-muted" style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 6px;">
                    <p>Chart not available</p>
                    <p class="text-sm">Monthly performance data: {{ count($api['monthly_performance'] ?? []) }} records</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Yearly Value Chart -->
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/chart-bar.svg') }}" class="header-icon">Yearly Value</h4>
        </div>
        <div class="card-body">
            @if(isset($files['yearly_performance.png']) && file_exists($files['yearly_performance.png']))
                <div class="chart-container">
                    <img src="{{ $files['yearly_performance.png'] }}" alt="Yearly Value"/>
                </div>
            @else
                <div class="text-muted" style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 6px;">
                    <p>Chart not available</p>
                    <p class="text-sm">Yearly performance data: {{ count($api['yearly_performance'] ?? []) }} records</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Forecast (Linear Regression) -->
    @if(isset($api['linear_regression']['predictions']) && count($api['linear_regression']['predictions']) > 0)
    <h3 class="section-title">Forecast (Linear Regression)</h3>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/chart-area.svg') }}" class="header-icon">10-Year Projection</h4>
        </div>
        <div class="card-body">
            @if(isset($files['forecast.png']) && file_exists($files['forecast.png']))
                <div class="chart-container">
                    <img src="{{ $files['forecast.png'] }}" alt="Forecast"/>
                </div>
                <p class="text-sm text-muted" style="margin-top: 8px;">
                    <strong>Legend:</strong> Predicted Value based on linear regression | Conservative (80%) | Aggressive (120%)
                </p>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/table.svg') }}" class="header-icon">Projection Table</h4>
        </div>
        <div class="card-body">
            <table class="table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th style="text-align: right;">Conservative (80%)</th>
                        <th style="text-align: right;">Predicted Value</th>
                        <th style="text-align: right;">Aggressive (120%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($api['linear_regression']['predictions'] as $year => $value)
                        @php
                            $numValue = floatval(str_replace(['$', ','], '', $value));
                        @endphp
                        <tr>
                            <td>{{ substr($year, 0, 4) }}</td>
                            <td style="text-align: right;">${{ number_format($numValue * 0.8, 2) }}</td>
                            <td style="text-align: right; font-weight: bold;">${{ number_format($numValue, 2) }}</td>
                            <td style="text-align: right;">${{ number_format($numValue * 1.2, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Asset Group Performance -->
    @foreach($api['asset_monthly_performance'] as $group => $perf)
        <div class="card mb-3 avoid-break">
            <div class="card-header">
                <h4 class="card-header-title"><img src="{{ public_path('images/icons/layer-group.svg') }}" class="header-icon">{{ $group }} Group Performance</h4>
            </div>
            <div class="card-body">
                @php($i = array_search($group, array_keys($api['asset_monthly_performance'])))
                <div class="chart-container">
                    <img src="{{ $files['group' . $i . '_monthly_performance.png'] }}" alt="{{ $group }} Performance"/>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Trade Portfolios Section -->
    <h3 class="section-title">Portfolio Allocation</h3>

    @if(isset($files['portfolio_group_comparison.png']))
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title"><img src="{{ public_path('images/icons/layer-group.svg') }}" class="header-icon">Portfolio Allocations by Group</h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['portfolio_group_comparison.png'] }}" alt="Portfolio Group Comparison" style="width: 100%;"/>
                </div>
            </div>
        </div>
    @endif

    @if(isset($files['portfolio_comparison.png']))
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title"><img src="{{ public_path('images/icons/chart-bar.svg') }}" class="header-icon">Portfolio Allocations by Symbol</h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['portfolio_comparison.png'] }}" alt="Portfolio Comparison" style="width: 100%;"/>
                </div>
            </div>
        </div>
    @else
        @foreach($api['tradePortfolios']->sortByDesc('start_dt') as $tradePortfolio)
            <table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 16px;">
                <tr>
                    <td width="50%" valign="top">
                        <div class="card" style="margin-bottom: 0;">
                            <div class="card-header">
                                <h4 class="card-header-title"><img src="{{ public_path('images/icons/bullseye.svg') }}" class="header-icon">Target Allocation</h4>
                                <div class="text-sm text-muted">
                                    {{ $tradePortfolio->start_dt->format('M j, Y') }} - {{ $tradePortfolio->end_dt->format('M j, Y') }}
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="margin: 8px 0;">
                                    <img src="{{ $files['trade_portfolios_' . $tradePortfolio->id . '.png'] }}" alt="Target Allocation"/>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td width="50%" valign="top">
                        <div class="card" style="margin-bottom: 0;">
                            <div class="card-header">
                                <h4 class="card-header-title"><img src="{{ public_path('images/icons/layer-group.svg') }}" class="header-icon">Group Allocation</h4>
                                <div class="text-sm text-muted">
                                    {{ $tradePortfolio->start_dt->format('M j, Y') }} - {{ $tradePortfolio->end_dt->format('M j, Y') }}
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="margin: 8px 0;">
                                    <img src="{{ $files['trade_portfolios_group' . $tradePortfolio->id . '.png'] }}" alt="Group Allocation"/>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        @endforeach
    @endif

    <!-- Trade Portfolios Comparison -->
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/exchange.svg') }}" class="header-icon">Trade Portfolios Comparison</h4>
        </div>
        <div class="card-body">
            @include('trade_portfolios.inner_show_alt_pdf')
        </div>
    </div>

    <!-- Trade Portfolio Details -->
    @foreach($api['tradePortfolios']->sortByDesc('start_dt') as $tradePortfolio)
        @include('trade_portfolios.inner_show_pdf')
    @endforeach

    <!-- Admin Only: Accounts Allocation -->
    @isset($api['balances']) @isset($api['admin'])
        <div class="card mb-3">
            <div class="card-header admin-header">
                <h4 class="card-header-title"><img src="{{ public_path('images/icons/users.svg') }}" class="header-icon">Accounts Allocation <span class="badge badge-warning" style="margin-left: 8px;">ADMIN</span></h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['accounts_allocation.png'] }}" alt="Accounts Allocation" style="width: 100%;"/>
                </div>
            </div>
        </div>
    @endisset @endisset

    <!-- Performance Tables -->
    <h3 class="section-title">Performance Data</h3>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/calendar.svg') }}" class="header-icon">Yearly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'yearly_performance')
            @include('funds.performance_table_pdf')
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/table.svg') }}" class="header-icon">Monthly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'monthly_performance')
            @include('funds.performance_table_pdf')
        </div>
    </div>

    <!-- Assets Table -->
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/coins.svg') }}" class="header-icon">Assets</h4>
        </div>
        <div class="card-body">
            @include('funds.assets_table_pdf')
        </div>
    </div>

    <!-- Admin Only: Transactions Table -->
    @isset($api['admin'])
    @isset($api['transactions'])
        <div class="card mb-3">
            <div class="card-header admin-header">
                <h4 class="card-header-title"><img src="{{ public_path('images/icons/exchange.svg') }}" class="header-icon">Transaction History <span class="badge badge-warning" style="margin-left: 8px;">ADMIN</span></h4>
            </div>
            <div class="card-body">
                @include('accounts.transactions_table_pdf')
            </div>
        </div>
    @endisset
    @endisset

    <!-- Admin Only: Accounts Table -->
    @isset($api['balances']) @isset($api['admin'])
        <div class="page-break"></div>
        <div class="card mb-3">
            <div class="card-header admin-header">
                <h4 class="card-header-title"><img src="{{ public_path('images/icons/users.svg') }}" class="header-icon">Accounts <span class="badge badge-warning" style="margin-left: 8px;">ADMIN</span></h4>
            </div>
            <div class="card-body">
                @include('funds.accounts_table_pdf')
            </div>
        </div>
    @endisset @endisset
@endsection
