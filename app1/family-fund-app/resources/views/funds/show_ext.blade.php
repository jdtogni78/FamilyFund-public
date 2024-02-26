@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Fund</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Details</strong>
                            <a href="{{ route('funds.index') }}" class="btn btn-light">Back</a>
                        </div>
                        <div class="card-body">
                            {!! Form::open(['route' => ['funds.update', 1]]) !!}
                            @include('funds.show_fields_ext')
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Monthly Performance</strong>
                        </div>
                        <div class="card-body">
                            @php($addSP500 = true)
                            @include('funds.performance_line_graph')
                            @php($addSP500 = false)
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Yearly Performance</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.performance_graph')
                        </div>
                    </div>
                </div>
            </div>
            @foreach($api['asset_monthly_performance'] as $group => $perf)
                <div class="row">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Group {{$group}}</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.performance_line_graph_assets')
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="row">
                @foreach($api['tradePortfolios'] as $tradePortfolio)
                    @php($extraTitle = '' . $tradePortfolio->id . ' [' .
                            $tradePortfolio->start_dt->format('Y-m-d') . ' to ' .
                            $tradePortfolio->end_dt->format('Y-m-d') . ']')
                    @include('trade_portfolios.inner_show_graphs')
                @endforeach
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Current Assets</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.assets_graph')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                @isset($api['balances'])@isset($api['admin'])
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Fund Allocation (ADMIN)</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.allocation_graph')
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Accounts Allocation (ADMIN)</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.accounts_graph')
                        </div>
                    </div>
                </div>
                @endisset @endisset
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Yearly Performance</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'yearly_performance')
                            @include('funds.performance_table')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Monthly Performance</strong>
                        </div>
                        <div class="card-body">
                            @php ($performance_key = 'monthly_performance')
                            @include('funds.performance_table')
                        </div>
                    </div>
                </div>
            </div>
            @foreach($api['tradePortfolios'] as $tradePortfolio)
                @php($extraTitle = '' . $tradePortfolio->id)
                @php($tradePortfolioItems = $tradePortfolio->items)
                @include('trade_portfolios.inner_show')
            @endforeach
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Assets</strong>
                        </div>
                        <div class="card-body">
                            @include('funds.assets_table')
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
            @isset($api['balances']) @isset($api['admin'])
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Accounts (ADMIN)</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.accounts_table')
                            </div>
                        </div>
                    </div>
                </div>
            @endisset @endisset
        </div>
    </div>
@endsection
