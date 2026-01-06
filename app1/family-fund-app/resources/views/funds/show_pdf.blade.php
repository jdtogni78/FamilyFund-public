@extends('layouts.pdf_modern')

@section('report-type', isset($api['admin']) ? 'Admin Fund Report' : 'Fund Report')

@section('content')
    <!-- Fund Summary Section -->
    <table width="100%" cellspacing="0" cellpadding="0" style="border: 2px solid #1e40af; margin-bottom: 16px;">
        <tr>
            <td style="padding: 12px; background-color: #1e40af;">
                <h2 style="margin: 0; color: #ffffff; font-size: 20px;">{{ $api['name'] }}</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px; background-color: #f8fafc;">
                <table width="100%" cellspacing="0" cellpadding="8">
                    <tr>
                        <td width="25%" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">${{ number_format($api['summary']['value'], 2) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Total Value</div>
                        </td>
                        <td width="25%" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">${{ number_format($api['summary']['share_value'], 4) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Share Price</div>
                        </td>
                        <td width="25%" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ number_format($api['summary']['shares'], 2) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Total Shares</div>
                        </td>
                        <td width="25%" align="center">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ number_format($api['summary']['allocated_shares_percent'], 2) }}%</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Allocated</div>
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
        <table width="100%" cellspacing="0" cellpadding="0" style="border: 2px solid #1e40af; margin-bottom: 16px;">
            <tr>
                <td style="padding: 12px; background-color: #1e40af;">
                    <h3 style="margin: 0; color: #ffffff; font-size: 18px;">Trade Portfolio #{{ $tradePortfolio->id }}: {{ $tradePortfolio->name ?? 'Portfolio' }}</h3>
                    <div style="color: #bfdbfe; font-size: 12px; margin-top: 4px;">
                        {{ $tradePortfolio->start_dt->format('M j, Y') }} - {{ $tradePortfolio->end_dt->format('M j, Y') }}
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding: 16px; background-color: #f8fafc;">
                    <h4 style="margin: 0 0 12px 0; color: #1e40af; font-size: 14px;">Portfolio Details</h4>
                    @include('trade_portfolios.show_fields_pdf')

                    <h4 style="margin: 20px 0 12px 0; color: #1e40af; font-size: 14px;">Holdings - Portfolio #{{ $tradePortfolio->id }}</h4>
                    @if($tradePortfolioItems = $tradePortfolio->items)
                        @include('trade_portfolio_items.table_pdf')
                    @endif
                </td>
            </tr>
        </table>
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
