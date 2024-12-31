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
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseMV" 
                            role="button" aria-expanded="false" aria-controls="collapseMV">
                            Monthly Value
                            </a>
                        </div>
                        <div class="collapse" id="collapseMV">
                            <div class="card-body">
                                @php($addSP500 = true)
                                @include('funds.performance_line_graph')
                                @php($addSP500 = false)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseYV" 
                            role="button" aria-expanded="false" aria-controls="collapseYV">
                            Yearly Value
                            </a>
                        </div>
                        <div class="collapse" id="collapseYV">
                            <div class="card-body">
                                @include('funds.performance_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseLR" 
                            role="button" aria-expanded="false" aria-controls="collapseLR">
                            Linear Regression
                            </a>
                        </div>
                        <div class="collapse" id="collapseLR">
                            <div class="card-body">
                                @include('funds.performance_line_graph_linreg')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseLRTable" 
                            role="button" aria-expanded="false" aria-controls="collapseLRTable">
                            Linear Regression Table
                            </a>
                        </div>
                        <div class="collapse" id="collapseLRTable">
                            <div class="card-body">
                                @include('funds.linreg_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @foreach($api['asset_monthly_performance'] as $group => $perf)
                <div class="row">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <a class="btn btn-primary" data-toggle="collapse" href="#collapse{{$group}}" 
                                role="button" aria-expanded="false" aria-controls="collapse{{$group}}">
                                Group {{$group}}
                                </a>
                            </div>
                            <div class="collapse" id="collapse{{$group}}">
                                <div class="card-body">
                                    @include('funds.performance_line_graph_assets')
                                </div>
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
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseCA" 
                            role="button" aria-expanded="false" aria-controls="collapseCA">
                            Current Assets
                            </a>
                        </div>
                        <div class="collapse" id="collapseCA">
                            <div class="card-body">
                                @include('funds.assets_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                @isset($api['balances'])@isset($api['admin'])
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseFA" 
                            role="button" aria-expanded="false" aria-controls="collapseFA">
                            Fund Allocation (ADMIN)
                            </a>
                        </div>
                        <div class="collapse" id="collapseFA">
                            <div class="card-body">
                                @include('funds.allocation_graph')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseAA" 
                            role="button" aria-expanded="false" aria-controls="collapseAA">
                            Accounts Allocation (ADMIN)
                            </a>
                        </div>
                        <div class="collapse" id="collapseAA">
                            <div class="card-body">
                                @include('funds.accounts_graph')
                            </div>
                        </div>
                    </div>
                </div>
                @endisset @endisset
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseYV2" 
                            role="button" aria-expanded="false" aria-controls="collapseYV2">
                            Yearly Value
                            </a>
                        </div>
                        <div class="collapse" id="collapseYV2">
                            <div class="card-body">
                                @php ($performance_key = 'yearly_performance')
                                @include('funds.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseMV2" 
                            role="button" aria-expanded="false" aria-controls="collapseMV2">
                            Monthly Value
                            </a>
                        </div>
                        <div class="collapse" id="collapseMV2">
                            <div class="card-body">
                                @php ($performance_key = 'monthly_performance')
                                @include('funds.performance_table')
                            </div>
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
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseAT" 
                            role="button" aria-expanded="false" aria-controls="collapseAT">
                            Assets
                            </a>
                        </div>
                        <div class="collapse" id="collapseAT">
                            <div class="card-body">
                                @include('funds.assets_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <a class="btn btn-primary" data-toggle="collapse" href="#collapseATrans" 
                            role="button" aria-expanded="false" aria-controls="collapseATrans">
                            Transactions
                            </a>
                        </div>
                        <div class="collapse" id="collapseATrans">
                            <div class="card-body">
                                @include('accounts.transactions_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @isset($api['balances']) @isset($api['admin'])
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <a class="btn btn-primary" data-toggle="collapse" href="#collapseAccounts" 
                                role="button" aria-expanded="false" aria-controls="collapseAccounts">
                                Accounts (ADMIN)
                                </a>
                            </div>
                            <div class="collapse" id="collapseAccounts">
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
@endsection
