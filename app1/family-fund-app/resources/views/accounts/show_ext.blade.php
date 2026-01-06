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

            {{-- Account Details --}}
            <div class="row mb-4" id="section-details">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Account Details</strong>
                                <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm ms-2">Back</a>
                            </div>
                            <div>
                                <a href="/accounts/{{ $account->id }}/pdf_as_of/{{ $api['asOf'] ?? now()->format('Y-m-d') }}"
                                   class="btn btn-outline-danger btn-sm" target="_blank" title="Download PDF Report">
                                    <i class="fa fa-file-pdf me-1"></i> PDF Report
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('accounts.show_fields_ext')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reusable Jump Bar --}}
            @include('partials.jump_bar', ['sections' => [
                ['id' => 'section-details', 'icon' => 'fa-user-circle', 'label' => 'Details'],
                ['id' => 'section-disbursement', 'icon' => 'fa-money-bill-wave', 'label' => 'Disbursement'],
                ['id' => 'section-goals', 'icon' => 'fa-bullseye', 'label' => 'Goals', 'condition' => $account->goals->count() > 0],
                ['id' => 'section-charts', 'icon' => 'fa-chart-line', 'label' => 'Charts'],
                ['id' => 'section-portfolios', 'icon' => 'fa-chart-bar', 'label' => 'Portfolios', 'condition' => isset($api['tradePortfolios']) && $api['tradePortfolios']->count() > 1],
                ['id' => 'section-shares', 'icon' => 'fa-chart-area', 'label' => 'Shares'],
                ['id' => 'section-performance', 'icon' => 'fa-table', 'label' => 'Performance'],
                ['id' => 'section-transactions', 'icon' => 'fa-exchange-alt', 'label' => 'Transactions'],
                ['id' => 'section-matching', 'icon' => 'fa-hand-holding-usd', 'label' => 'Matching', 'condition' => !empty($api['matching_rules'])],
            ]])

            {{-- Disbursement Eligibility --}}
            <div class="row mb-4" id="section-disbursement">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-money-bill-wave" style="margin-right: 8px;"></i>Disbursement Eligibility</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.disbursement')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Goals Section --}}
            @if($account->goals->count() > 0)
            <div class="row mb-4" id="section-goals">
                <div class="col">
                    <h5 class="mb-3"><i class="fa fa-bullseye" style="margin-right: 8px;"></i>Goals</h5>
                    @foreach($account->goals as $goal)
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #f8f9fa;">
                                <strong>{{ $goal->name }}</strong>
                                <span class="badge bg-secondary">ID: {{ $goal->id }}</span>
                            </div>
                            <div class="card-body">
                                @include('goals.progress_bar')
                                @include('goals.progress_details')
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Charts Row --}}
            <div class="row mb-4" id="section-charts">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-line" style="margin-right: 8px;"></i>Monthly Value</strong>
                        </div>
                        <div class="card-body">
                            @php($addSP500 = true)
                            @include('accounts.performance_line_graph')
                            @php($addSP500 = false)
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-bar" style="margin-right: 8px;"></i>Yearly Value</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.performance_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trade Portfolios Comparison --}}
            @if(isset($api['tradePortfolios']) && $api['tradePortfolios']->count() > 1)
            <div class="row mb-4" id="section-portfolios">
                <div class="col">
                    @include('trade_portfolios.stacked_bar_graph')
                </div>
            </div>
            @endif

            {{-- Shares Chart --}}
            <div class="row mb-4" id="section-shares">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-area" style="margin-right: 8px;"></i>Shares History</strong>
                        </div>
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

            {{-- Performance Tables Row --}}
            <div class="row mb-4" id="section-performance">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table" style="margin-right: 8px;"></i>Yearly Performance</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'yearly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table" style="margin-right: 8px;"></i>Monthly Performance</strong>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            @php ($performance_key = 'monthly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions --}}
            <div class="row mb-4" id="section-transactions">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-exchange-alt" style="margin-right: 8px;"></i>Transactions</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.transactions_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Matching Rules --}}
            @if(!empty($api['matching_rules']))
                <div class="row mb-4" id="section-matching">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong><i class="fa fa-hand-holding-usd" style="margin-right: 8px;"></i>Matching Rules</strong>
                            </div>
                            <div class="card-body">
                                @include('accounts.matching_rules_table')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
