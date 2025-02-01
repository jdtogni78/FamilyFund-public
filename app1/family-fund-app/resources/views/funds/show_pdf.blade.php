@extends('layouts.pdf')

@section('content')
    <div class="row" style="margin-top: 30px">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Details</strong>
                </div>
                <div class="card-body">
                    @include('funds.show_fields_pdf')
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Monthly Value</strong>
                </div>
                <div class="card-body">
                    <img src="{{$files['monthly_performance.png']}}" alt="Monthly Value"/>
                    <div class="col-xs-12">
                        <ul>
                            <li><b>Monthly Value</b>: the performance of this fund</li>
                            <li><b>SP500</b>: the performance of a fund that would invest the same amount of funds 100% on SP500</li>
                            <li><b>Cash</b>: the performance of a fund that would invest the same amount of funds 100% on Cash</li>
                        </ul>
                    </div>
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
                    <img src="{{$files['yearly_performance.png']}}" alt="Yearly Value"/>
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
                        @php($i = array_search($group, array_keys($api['asset_monthly_performance'])))
                        <img src="{{$files['group' . $i . '_monthly_performance.png']}}" alt="Yearly Value"/>
                        <div class="col-xs-12">
                            <ul>
                                <li><b>SP500</b>: the performance of a fund that would invest the same amount of funds 100% on SP500</li>
                                <li><b>Others</b>: the performance of a fund that would invest the same amount of funds 100% on other stock</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="row new-page">
        @foreach($api['tradePortfolios'] as $tradePortfolio)
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <strong>Target Portfolio Target % {{ $tradePortfolio->id }}
                            [{{ $tradePortfolio->start_dt->format('Y-m-d') }} to
                            {{ $tradePortfolio->end_dt->format('Y-m-d') }}]</strong>
                    </div>
                    <div class="card-body">
                        <img src="{{$files['trade_portfolios_' . $tradePortfolio->id . '.png']}}" alt="Trade Portfolio"/>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <strong>Target Portfolio Group % {{ $tradePortfolio->id }}
                            [{{ $tradePortfolio->start_dt->format('Y-m-d') }} to
                            {{ $tradePortfolio->end_dt->format('Y-m-d') }}]</strong>
                    </div>
                    <div class="card-body">
                        <img src="{{$files['trade_portfolios_group' . $tradePortfolio->id . '.png']}}" alt="Trade Portfolio"/>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Current Assets</strong>
                </div>
                <div class="card-body">
                    <img src="{{$files['assets_allocation.png']}}" alt="Accounts Allocation"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        @isset($api['balances']) @isset($api['admin'])
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <strong>Fund Allocation (ADMIN)</strong>
                    </div>
                    <div class="card-body">
                        <img src="{{$files['shares_allocation.png']}}" alt="Fund Allocation"/>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <strong>Accounts Allocation</strong>
                    </div>
                    <div class="card-body">
                        <img src="{{$files['accounts_allocation.png']}}" alt="Accounts Allocation"/>
                    </div>
                </div>
            </div>
        @endisset @endisset
    </div>
    <div class="row new-page">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <strong>Yearly Value</strong>
                </div>
                <div class="card-body">
                    @php ($performance_key = 'yearly_performance')
                    @include('funds.performance_table')
                </div>
            </div>
        </div>
    </div>
    <div class="row new-page">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <strong>Monthly Value</strong>
                </div>
                <div class="card-body">
                    @php ($performance_key = 'monthly_performance')
                    @include('funds.performance_table')
                </div>
            </div>
        </div>
    </div>
    @foreach($api['tradePortfolios'] as $tradePortfolio)
        <div class="row new-page">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Target Portfolio {{ $tradePortfolio->id }}</strong>
                    </div>
                    <div class="card-body">
                        @include('trade_portfolios.show_fields')
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Target Portfolio Details {{ $tradePortfolio->id }}</strong>
                    </div>
                    <div class="card-body">
                        @if($tradePortfolioItems = $tradePortfolio->items)
                            @include('trade_portfolio_items.table')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="row new-page">
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
    @isset($api['balances']) @isset($api['admin'])
        <div class="row new-page">
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
</x-app-layout>
