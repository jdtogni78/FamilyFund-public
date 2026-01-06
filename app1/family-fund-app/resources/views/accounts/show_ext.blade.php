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
            <div class="row mb-4">
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

            {{-- Disbursement Eligibility --}}
            <div class="row mb-4">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-money-bill-wave me-2"></i>Disbursement Eligibility</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.disbursement')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Goals Section --}}
            @if($account->goals->count() > 0)
            <div class="row mb-4">
                <div class="col">
                    <h5 class="mb-3"><i class="fa fa-bullseye me-2"></i>Goals</h5>
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
            <div class="row mb-4">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-line me-2"></i>Monthly Value</strong>
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
                            <strong><i class="fa fa-chart-bar me-2"></i>Yearly Value</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.performance_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Shares Chart --}}
            <div class="row mb-4">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-chart-area me-2"></i>Shares History</strong>
                        </div>
                        <div class="card-body">
                            <div>
                                <canvas id="balancesGraph"></canvas>
                            </div>
                            @include('accounts.balances_graph')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Tables Row --}}
            <div class="row mb-4">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong><i class="fa fa-table me-2"></i>Yearly Performance</strong>
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
                            <strong><i class="fa fa-table me-2"></i>Monthly Performance</strong>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            @php ($performance_key = 'monthly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions --}}
            <div class="row mb-4">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong><i class="fa fa-exchange-alt me-2"></i>Transactions</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.transactions_table')
                        </div>
                    </div>
                </div>
            </div>

            {{-- Matching Rules --}}
            @if(!empty($api['matching_rules']))
                <div class="row mb-4">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong><i class="fa fa-hand-holding-usd me-2"></i>Matching Rules</strong>
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
