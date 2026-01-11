<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item active">{{ $api['name'] }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Fund Highlights Card --}}
            @php
                $totalValueRaw = $api['portfolio']['total_value'] ?? 0;
                // Remove $ and commas if it's a string, then format properly
                if (is_string($totalValueRaw)) {
                    $totalValueRaw = floatval(str_replace(['$', ','], '', $totalValueRaw));
                }
                $totalValue = '$' . number_format($totalValueRaw, 0);
                $sharePrice = $api['summary']['share_value'] ?? 0;
                $accountsCount = count($api['balances'] ?? []);
            @endphp
            <div class="row mb-4" id="section-details">
                <div class="col">
                    <div class="card" style="border: 2px solid #0d9488;">
                        {{-- Header --}}
                        <div class="card-header card-header-dark d-flex justify-content-between align-items-center flex-wrap py-3" style="gap: 8px;">
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0" style="font-weight: 700;">{{ $api['name'] }}</h4>
                                @isset($api['admin'])
                                    <span class="badge ms-2" style="background: #d97706; font-size: 0.7rem;">ADMIN</span>
                                @endisset
                            </div>
                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                <a href="{{ route('funds.index') }}" class="btn btn-sm btn-primary">Back</a>
                                <a href="/funds/{{ $api['id'] }}/trade_bands" class="btn btn-sm btn-primary" title="Trading Bands">
                                    <i class="fa fa-chart-bar"></i>
                                </a>
                                @isset($api['admin'])
                                    <a href="/funds/{{ $api['id'] }}/as_of/{{ $asOf }}?admin=0" class="btn btn-sm btn-primary" title="Switch to User View">
                                        <i class="fa fa-user"></i>
                                    </a>
                                @else
                                    @if(in_array(Auth::user()->email ?? '', ['admin@dev.familyfund.local', 'claude@test.local']))
                                        <a href="/funds/{{ $api['id'] }}/as_of/{{ $asOf }}" class="btn btn-sm btn-warning" title="Switch to Admin View">
                                            <i class="fa fa-user-shield"></i>
                                        </a>
                                    @endif
                                @endisset
                                <a href="/funds/{{ $api['id'] }}/pdf_as_of/{{ $asOf }}{{ isset($api['admin']) ? '' : '?admin=0' }}" class="btn btn-sm btn-primary" target="_blank" title="Download PDF">
                                    <i class="fa fa-file-pdf"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Stats Row --}}
                        <div class="card-body py-3" style="background: #f0fdfa;">
                            <div class="row text-center">
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #99f6e4;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0d9488;">{{ $totalValue }}</div>
                                    <div class="text-muted text-uppercase small">Total Value</div>
                                </div>
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #99f6e4;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0d9488;">${{ number_format($sharePrice, 2) }}</div>
                                    <div class="text-muted text-uppercase small">Share Price</div>
                                </div>
                                @include('partials.highlights_growth', ['yearlyPerf' => $api['yearly_performance'] ?? [], 'showBorder' => isset($api['admin']) && $accountsCount > 0])
                                @if(isset($api['admin']) && $accountsCount > 0)
                                <div class="col" style="background: #fffbeb; border-radius: 6px; padding: 8px; margin: -8px 0;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #d97706;">{{ $accountsCount }}</div>
                                    <div class="text-uppercase small" style="color: #92400e;">
                                        Accounts <span class="badge" style="background: #d97706; color: #fff; font-size: 0.6rem; vertical-align: top;">ADMIN</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Admin: Allocated/Unallocated Visual (only show if there are user accounts) --}}
                        @if(isset($api['admin']) && $accountsCount > 0)
                        @php
                            $allocatedPct = $api['summary']['allocated_shares_percent'] ?? 0;
                            $unallocatedPct = $api['summary']['unallocated_shares_percent'] ?? (100 - $allocatedPct);
                            $allocatedShares = ($api['summary']['shares'] ?? 0) * $allocatedPct / 100;
                            $unallocatedShares = ($api['summary']['shares'] ?? 0) - $allocatedShares;
                            $allocatedValueCalc = $allocatedShares * ($api['summary']['share_value'] ?? 0);
                            $unallocatedValueCalc = $unallocatedShares * ($api['summary']['share_value'] ?? 0);
                        @endphp
                        <div class="card-body py-3" style="background: #fffbeb; border-top: 1px solid #99f6e4;">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge" style="background: #d97706; color: #fff; font-size: 0.7rem; margin-right: 8px;">ADMIN</span>
                                <strong class="text-muted small">Share Allocation</strong>
                            </div>
                            {{-- Progress Bar --}}
                            <div class="d-flex mb-3" style="border-radius: 6px; overflow: hidden;">
                                <div style="width: {{ $allocatedPct }}%; background-color: #22c55e; padding: 8px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px;">
                                    {{ number_format($allocatedPct, 1) }}%
                                </div>
                                <div style="width: {{ $unallocatedPct }}%; background-color: #d97706; padding: 8px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px;">
                                    {{ number_format($unallocatedPct, 1) }}%
                                </div>
                            </div>
                            {{-- Allocated / Unallocated Boxes --}}
                            <div class="row">
                                <div class="col-6">
                                    <div style="background-color: #22c55e; padding: 12px 16px; border-radius: 6px; color: #ffffff;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span style="font-size: 14px; font-weight: 700;">Allocated</span>
                                                <span style="background-color: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 6px;">{{ number_format($allocatedPct, 1) }}%</span>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="font-size: 11px; opacity: 0.9;">{{ number_format($allocatedShares, 2) }} shares</span><br>
                                                <span style="font-size: 18px; font-weight: 700;">${{ number_format($allocatedValueCalc, 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div style="background-color: #d97706; padding: 12px 16px; border-radius: 6px; color: #ffffff;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span style="font-size: 14px; font-weight: 700;">Unallocated</span>
                                                <span style="background-color: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 6px;">{{ number_format($unallocatedPct, 1) }}%</span>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="font-size: 11px; opacity: 0.9;">{{ number_format($unallocatedShares, 2) }} shares</span><br>
                                                <span style="font-size: 18px; font-weight: 700;">${{ number_format($unallocatedValueCalc, 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Fund Details --}}
                        <div class="card-body pt-0 pb-3" style="background: #ffffff; border-top: 1px solid #99f6e4;">
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>As of:</strong> {{ $asOf }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Source:</strong> {{ $api['portfolio']['source'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reusable Jump Bar --}}
            @include('partials.jump_bar', ['sections' => [
                ['id' => 'section-details', 'icon' => 'fa-info-circle', 'label' => 'Details'],
                ['id' => 'section-charts', 'icon' => 'fa-chart-line', 'label' => 'Charts'],
                ['id' => 'section-regression', 'icon' => 'fa-chart-area', 'label' => 'Forecast'],
                ['id' => 'section-portfolios', 'icon' => 'fa-chart-bar', 'label' => 'Portfolios'],
                ['id' => 'section-allocation', 'icon' => 'fa-users', 'label' => 'Acct Alloc', 'condition' => isset($api['admin']) && $accountsCount > 0],
                ['id' => 'section-performance', 'icon' => 'fa-table', 'label' => 'Performance'],
                ['id' => 'section-trade-portfolios', 'icon' => 'fa-briefcase', 'label' => 'Trade Portfolios'],
                ['id' => 'section-assets-table', 'icon' => 'fa-coins', 'label' => 'Assets'],
                ['id' => 'section-transactions', 'icon' => 'fa-exchange-alt', 'label' => 'Transaction History', 'condition' => isset($api['admin'])],
                ['id' => 'section-accounts', 'icon' => 'fa-user-friends', 'label' => 'Accounts', 'condition' => isset($api['admin']) && $accountsCount > 0],
            ]])

            {{-- Main Charts Row (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-charts">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-chart-line mr-2"></i>Monthly Value</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMonthlyValue"
                               role="button" aria-expanded="true" aria-controls="collapseMonthlyValue">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseMonthlyValue">
                            <div class="card-body">
                                @php($addSP500 = true)
                                @include('funds.performance_line_graph')
                                @php($addSP500 = false)
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-chart-bar mr-2"></i>Yearly Value</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseYearlyValue"
                               role="button" aria-expanded="true" aria-controls="collapseYearlyValue">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseYearlyValue">
                            <div class="card-body">
                                @include('funds.performance_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Forecast (Linear Regression) (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-regression">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-chart-area mr-2"></i>Forecast (Linear Regression)</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseForecast"
                               role="button" aria-expanded="true" aria-controls="collapseForecast">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseForecast">
                            <div class="card-body">
                                @include('funds.performance_line_graph_linreg')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-table mr-2"></i>Projection Table</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseProjection"
                               role="button" aria-expanded="true" aria-controls="collapseProjection">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseProjection">
                            <div class="card-body">
                                @include('funds.linreg_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Asset Performance by Group (Collapsible, start expanded) --}}
            @foreach($api['asset_monthly_performance'] as $group => $perf)
                <div class="row mb-4" id="section-group-{{ Str::slug($group) }}">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                                <strong><i class="fa fa-layer-group mr-2"></i>Group {{ $group }} Performance</strong>
                                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseGroup{{$group}}"
                                   role="button" aria-expanded="true" aria-controls="collapseGroup{{$group}}">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse show" id="collapseGroup{{$group}}">
                                <div class="card-body">
                                    @include('funds.performance_line_graph_assets')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Trade Portfolios Comparison by Group --}}
            <div id="section-portfolios-groups">
                @include('trade_portfolios.stacked_bar_groups_graph')
            </div>

            {{-- Trade Portfolios Comparison by Symbol --}}
            <div id="section-portfolios">
                @include('trade_portfolios.stacked_bar_graph')
            </div>

            {{-- Admin Accounts Allocation Chart (Collapsible, start expanded) --}}
            @isset($api['balances'])@isset($api['admin'])
            <div class="row mb-4" id="section-allocation">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #ffffff; border-bottom: 3px solid #b45309;">
                            <strong><i class="fa fa-users mr-2"></i>Accounts Allocation <span class="badge" style="background: #fff; color: #b45309; font-size: 0.7rem; font-weight: 600;">ADMIN</span></strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAcctAlloc"
                               role="button" aria-expanded="true" aria-controls="collapseAcctAlloc">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseAcctAlloc">
                            <div class="card-body">
                                @include('funds.accounts_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endisset @endisset

            {{-- Performance Tables (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-performance">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-table mr-2"></i>Yearly Performance</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseYearlyPerf"
                               role="button" aria-expanded="true" aria-controls="collapseYearlyPerf">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseYearlyPerf">
                            <div class="card-body">
                                @php ($performance_key = 'yearly_performance')
                                @include('funds.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-table mr-2"></i>Monthly Performance</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMonthlyPerf"
                               role="button" aria-expanded="true" aria-controls="collapseMonthlyPerf">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseMonthlyPerf">
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                @php ($performance_key = 'monthly_performance')
                                @include('funds.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trade Portfolios Comparison (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-portfolios-alt">
            <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                    <strong><i class="fa fa-columns mr-2"></i>Trade Portfolios Comparison</strong>
                    <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseTradePortfoliosAlt"
                       role="button" aria-expanded="true" aria-controls="collapseTradePortfoliosAlt">
                        <i class="fa fa-chevron-down"></i>
                    </a>
                </div>
                <div class="collapse show" id="collapseTradePortfoliosAlt">
                    <div class="card-body">
                        @include('trade_portfolios.inner_show_alt')
                    </div>
                </div>
            </div>
            </div>
            </div>

            {{-- Trade Portfolios Details (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-trade-portfolios">
            <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                    <strong><i class="fa fa-briefcase mr-2"></i>Trade Portfolios</strong>
                    <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseTradePortfolios"
                       role="button" aria-expanded="true" aria-controls="collapseTradePortfolios">
                        <i class="fa fa-chevron-down"></i>
                    </a>
                </div>
                <div class="collapse show" id="collapseTradePortfolios">
                    <div class="card-body">
                        @foreach($api['tradePortfolios'] as $tradePortfolio)
                            @php($extraTitle = '' . $tradePortfolio->id)
                            @php($tradePortfolioItems = $tradePortfolio->items)
                            @include('trade_portfolios.inner_show_compact')
                        @endforeach
                    </div>
                </div>
            </div>
            </div>
            </div>

            {{-- Assets Table (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-assets-table">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-coins mr-2"></i>Assets</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAssets"
                               role="button" aria-expanded="true" aria-controls="collapseAssets">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseAssets">
                            <div class="card-body">
                                @include('funds.assets_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions Table - Admin Only (Collapsible, start expanded) --}}
            @isset($api['admin'])
            <div class="row mb-4" id="section-transactions">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #b45309; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-exchange-alt mr-2"></i>Transaction History <span class="badge badge-warning ml-2">ADMIN</span></strong>
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
            @endisset

            {{-- Accounts Table - Admin Only (Collapsible, start expanded) --}}
            @isset($api['balances']) @isset($api['admin'])
                <div class="row mb-4" id="section-accounts">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #ffffff; border-bottom: 3px solid #b45309;">
                                <strong><i class="fa fa-user-friends mr-2"></i>Accounts <span class="badge" style="background: #fff; color: #b45309; font-size: 0.7rem; font-weight: 600;">ADMIN</span></strong>
                                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAccounts"
                                   role="button" aria-expanded="true" aria-controls="collapseAccounts">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse show" id="collapseAccounts">
                                <div class="card-body">
                                    @include('funds.accounts_table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endisset @endisset
        </div>
    </div>
</x-app-layout>
