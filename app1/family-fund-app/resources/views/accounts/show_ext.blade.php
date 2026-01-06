<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('accounts.index') }}">Account</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Details</strong>
                                <a href="{{ route('accounts.index') }}" class="btn btn-light btn-sm ml-2">Back</a>
                            </div>
                            <div>
                                <a href="/accounts/{{ $account->id }}/pdf_as_of/{{ $api['asOf'] ?? now()->format('Y-m-d') }}"
                                   class="btn btn-outline-danger btn-sm" target="_blank" title="Download PDF Report">
                                    <i class="fa fa-file-pdf mr-1"></i> PDF Report
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('accounts.update', 1) }}" method="PUT">
                                @csrf
                                @include('accounts.show_fields_ext')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Disbursement Eligibility</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.disbursement')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Goals</strong>
                        </div>
                        <div class="card-body">
                            @foreach($account->goals as $goal)
                                <h3>{{ $goal->name }} ({{ $goal->id }})</h3>
                                @include('goals.progress_bar')
                                @include('goals.progress_details')
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Monthly Value</strong>
                        </div>
                        <div class="card-body">
                            @php($addSP500 = true)
                            @include('accounts.performance_line_graph')
                            @php($addSP500 = false)
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Yearly Value</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.performance_graph')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Shares</strong>
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
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Yearly Value</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'yearly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Monthly Value</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'monthly_performance')
                            @include('accounts.performance_table')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Transactions</strong>
                        </div>
                        <div class="card-body">
                            @include('accounts.transactions_table')
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($api['matching_rules']))
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Matching Rules</strong>
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
