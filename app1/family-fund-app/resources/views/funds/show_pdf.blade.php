@extends('layouts.pdf_modern')

@section('report-type', isset($api['admin']) ? 'Admin Fund Report' : 'Fund Report')

@section('content')
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
    @endphp

    <!-- Fund Name Header -->
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px;">
        <tr>
            <td style="padding: 12px; background-color: #1e40af; border-radius: 6px;">
                <h2 style="margin: 0; color: #ffffff; font-size: 20px;">{{ $api['name'] }}</h2>
            </td>
        </tr>
    </table>

    <!-- Fund Summary Visual -->
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 6px;">
        <tr>
            <td style="padding: 16px;">
                <!-- Total Bar -->
                <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
                    <tr>
                        <td style="background-color: #2563eb; padding: 12px 16px; border-radius: 6px;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color: #ffffff;">
                                        <span style="font-size: 16px; font-weight: 700;">Total</span>
                                        <span style="background-color: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">${{ number_format($shareValue, 2) }}/share</span>
                                    </td>
                                    <td style="text-align: right; color: #ffffff;">
                                        <span style="font-size: 12px; opacity: 0.9;">{{ number_format($totalShares, 2) }} shares</span><br>
                                        <span style="font-size: 20px; font-weight: 700;">${{ number_format($totalValue, 2) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Progress Bar -->
                <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
                    <tr>
                        <td width="{{ $allocatedPct }}%" style="background-color: #22c55e; padding: 8px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px; {{ $allocatedPct > 0 ? 'border-radius: 6px 0 0 6px;' : '' }}">
                            {{ number_format($allocatedPct, 1) }}%
                        </td>
                        <td width="{{ $unallocatedPct }}%" style="background-color: #d97706; padding: 8px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px; {{ $unallocatedPct > 0 ? 'border-radius: 0 6px 6px 0;' : '' }}">
                            {{ number_format($unallocatedPct, 1) }}%
                        </td>
                    </tr>
                </table>

                <!-- Allocated / Unallocated Boxes -->
                <table width="100%" cellspacing="8" cellpadding="0">
                    <tr>
                        <td width="50%" style="background-color: #22c55e; padding: 12px 16px; border-radius: 6px; vertical-align: top;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color: #ffffff;">
                                        <span style="font-size: 14px; font-weight: 700;">Allocated</span>
                                        <span style="background-color: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 6px;">{{ number_format($allocatedPct, 1) }}%</span>
                                    </td>
                                    <td style="text-align: right; color: #ffffff;">
                                        <span style="font-size: 11px; opacity: 0.9;">{{ number_format($allocatedShares, 2) }} shares</span><br>
                                        <span style="font-size: 18px; font-weight: 700;">${{ number_format($allocatedValue, 2) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td width="50%" style="background-color: #d97706; padding: 12px 16px; border-radius: 6px; vertical-align: top;">
                            <table width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="color: #ffffff;">
                                        <span style="font-size: 14px; font-weight: 700;">Unallocated</span>
                                        <span style="background-color: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 6px;">{{ number_format($unallocatedPct, 1) }}%</span>
                                    </td>
                                    <td style="text-align: right; color: #ffffff;">
                                        <span style="font-size: 11px; opacity: 0.9;">{{ number_format($unallocatedShares, 2) }} shares</span><br>
                                        <span style="font-size: 18px; font-weight: 700;">${{ number_format($unallocatedValue, 2) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Fund Details Card -->
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Fund Details</h4>
        </div>
        <div class="card-body">
            @include('funds.show_fields_pdf')
        </div>
    </div>

    <!-- Monthly Performance Chart -->
    <div class="page-break"></div>
    <h3 class="section-title">Performance Analysis</h3>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title">Monthly Performance</h4>
        </div>
        <div class="card-body">
            @if(isset($files['monthly_performance.png']) && file_exists($files['monthly_performance.png']))
                <div class="chart-container">
                    <img src="{{ $files['monthly_performance.png'] }}" alt="Monthly Performance"/>
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

    <!-- Yearly Performance Chart -->
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title">Yearly Performance</h4>
        </div>
        <div class="card-body">
            @if(isset($files['yearly_performance.png']) && file_exists($files['yearly_performance.png']))
                <div class="chart-container">
                    <img src="{{ $files['yearly_performance.png'] }}" alt="Yearly Performance"/>
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
    <div class="page-break"></div>
    <h3 class="section-title">Forecast (Linear Regression)</h3>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title">10-Year Projection</h4>
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
            <h4 class="card-header-title">Projection Table</h4>
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
                <h4 class="card-header-title">{{ $group }} Group Performance</h4>
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
    <div class="page-break"></div>
    <h3 class="section-title">Portfolio Allocation</h3>

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
    @else
        @foreach($api['tradePortfolios']->sortByDesc('start_dt') as $tradePortfolio)
            <table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 16px;">
                <tr>
                    <td width="50%" valign="top">
                        <div class="card" style="margin-bottom: 0;">
                            <div class="card-header">
                                <h4 class="card-header-title">Target Allocation</h4>
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
                                <h4 class="card-header-title">Group Allocation</h4>
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

    <!-- Admin Only: Fund Allocation -->
    @isset($api['balances']) @isset($api['admin'])
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="card-header-title">Fund Allocation</h4>
                <span class="badge badge-warning">Admin</span>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['shares_allocation.png'] }}" alt="Fund Allocation"/>
                </div>
            </div>
        </div>

        <!-- Accounts Allocation - Full Width -->
        <div class="page-break"></div>
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="card-header-title">Accounts Allocation</h4>
                <span class="badge badge-warning">Admin</span>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['accounts_allocation.png'] }}" alt="Accounts Allocation" style="width: 100%;"/>
                </div>
            </div>
        </div>
    @endisset @endisset

    <!-- Performance Tables -->
    <div class="page-break"></div>
    <h3 class="section-title">Performance Data</h3>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title">Yearly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'yearly_performance')
            @include('funds.performance_table_pdf')
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title">Monthly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'monthly_performance')
            @include('funds.performance_table_pdf')
        </div>
    </div>

    <!-- Trade Portfolio Details -->
    @foreach($api['tradePortfolios']->sortByDesc('start_dt') as $tradePortfolio)
        <div class="page-break"></div>
        @include('trade_portfolios.inner_show_pdf')
    @endforeach

    <!-- Assets Table -->
    <div class="page-break"></div>
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-header-title">Assets</h4>
        </div>
        <div class="card-body">
            @include('funds.assets_table_pdf')
        </div>
    </div>

    <!-- Admin Only: Accounts Table -->
    @isset($api['balances']) @isset($api['admin'])
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="card-header-title">Accounts</h4>
                <span class="badge badge-warning">Admin</span>
            </div>
            <div class="card-body">
                @include('funds.accounts_table_pdf')
            </div>
        </div>
    @endisset @endisset
@endsection
