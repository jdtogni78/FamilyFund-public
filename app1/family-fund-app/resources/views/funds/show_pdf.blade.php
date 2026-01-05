@extends('layouts.pdf_modern')

@section('report-type', isset($api['admin']) ? 'Admin Fund Report' : 'Fund Report')

@section('content')
    <!-- Fund Summary Section -->
    <div class="summary-box">
        <h2>{{ $api['name'] }}</h2>
        <div class="stat-grid">
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">${{ number_format($api['summary']['value'], 2) }}</div>
                <div class="stat-label">Total Value</div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">${{ number_format($api['summary']['share_value'], 4) }}</div>
                <div class="stat-label">Share Price</div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">{{ number_format($api['summary']['shares'], 2) }}</div>
                <div class="stat-label">Total Shares</div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">{{ number_format($api['summary']['allocated_shares_percent'], 1) }}%</div>
                <div class="stat-label">Allocated</div>
            </div>
        </div>
    </div>

    <!-- Fund Details Card -->
    <div class="card mb-5">
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
                <span class="ml-3"><strong>Monthly Value</strong> - Fund performance</span> |
                <span><strong>SP500</strong> - S&P 500 benchmark</span> |
                <span><strong>Cash</strong> - Cash equivalent</span>
            </div>
        </div>
    </div>

    <!-- Yearly Performance Chart -->
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

    <!-- Asset Group Performance -->
    @foreach($api['asset_monthly_performance'] as $group => $perf)
        <div class="card mb-4 avoid-break">
            <div class="card-header">
                <h4 class="card-header-title">{{ $group }} Group Performance</h4>
            </div>
            <div class="card-body">
                @php($i = array_search($group, array_keys($api['asset_monthly_performance'])))
                <div class="chart-container">
                    <img src="{{ $files['group' . $i . '_monthly_performance.png'] }}" alt="{{ $group }} Performance"/>
                </div>
                <div class="mt-2 text-sm text-muted">
                    Comparison of {{ $group }} assets against S&P 500 benchmark
                </div>
            </div>
        </div>
    @endforeach

    <!-- Trade Portfolios Section -->
    <div class="page-break"></div>
    <h3 class="section-title">Portfolio Allocation</h3>

    <div class="row mb-4">
        @foreach($api['tradePortfolios'] as $tradePortfolio)
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title">Target Allocation</h4>
                        <div class="text-sm text-muted">
                            {{ $tradePortfolio->start_dt->format('M j, Y') }} - {{ $tradePortfolio->end_dt->format('M j, Y') }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <img src="{{ $files['trade_portfolios_' . $tradePortfolio->id . '.png'] }}" alt="Target Allocation"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title">Group Allocation</h4>
                        <div class="text-sm text-muted">
                            {{ $tradePortfolio->start_dt->format('M j, Y') }} - {{ $tradePortfolio->end_dt->format('M j, Y') }}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <img src="{{ $files['trade_portfolios_group' . $tradePortfolio->id . '.png'] }}" alt="Group Allocation"/>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Current Assets Allocation -->
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Current Assets Allocation</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['assets_allocation.png'] }}" alt="Assets Allocation"/>
            </div>
        </div>
    </div>

    <!-- Admin Only: Fund & Account Allocation -->
    @isset($api['balances']) @isset($api['admin'])
        <div class="row mb-4">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title">Fund Allocation</h4>
                        <span class="badge badge-warning">Admin Only</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <img src="{{ $files['shares_allocation.png'] }}" alt="Fund Allocation"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-header-title">Accounts Allocation</h4>
                        <span class="badge badge-warning">Admin Only</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <img src="{{ $files['accounts_allocation.png'] }}" alt="Accounts Allocation"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endisset @endisset

    <!-- Performance Tables -->
    <div class="page-break"></div>
    <h3 class="section-title">Performance Data</h3>

    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Yearly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'yearly_performance')
            @include('funds.performance_table')
        </div>
    </div>

    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Monthly Performance Data</h4>
        </div>
        <div class="card-body">
            @php ($performance_key = 'monthly_performance')
            @include('funds.performance_table')
        </div>
    </div>

    <!-- Trade Portfolio Details -->
    @foreach($api['tradePortfolios'] as $tradePortfolio)
        <div class="page-break"></div>
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Trade Portfolio #{{ $tradePortfolio->id }}</h4>
                <div class="text-sm text-muted">
                    {{ $tradePortfolio->start_dt->format('M j, Y') }} - {{ $tradePortfolio->end_dt->format('M j, Y') }}
                </div>
            </div>
            <div class="card-body">
                @include('trade_portfolios.show_fields')
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Portfolio Holdings</h4>
            </div>
            <div class="card-body">
                @if($tradePortfolioItems = $tradePortfolio->items)
                    @include('trade_portfolio_items.table')
                @endif
            </div>
        </div>
    @endforeach

    <!-- Assets Table -->
    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Assets</h4>
        </div>
        <div class="card-body">
            @include('funds.assets_table')
        </div>
    </div>

    <!-- Admin Only: Accounts Table -->
    @isset($api['balances']) @isset($api['admin'])
        <div class="page-break"></div>
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Accounts</h4>
                <span class="badge badge-warning">Admin Only</span>
            </div>
            <div class="card-body">
                @include('funds.accounts_table')
            </div>
        </div>
    @endisset @endisset
@endsection
