@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.index') }}">Trade Portfolio</a>
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
                            <a href="{{ route('tradePortfolios.index') }}" class="btn btn-light">Back</a>
                        </div>
                        <div class="card-body">
                            @include('trade_portfolios.show_fields')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                   <div class="card">
                       <div class="card-header">
                           <strong>Target %</strong>
                       </div>
                       <div class="card-body">
                           @if($tradePortfolio = $api['tradePortfolio'])
                           @include('trade_portfolios.graph')
                           @endif
                       </div>
                   </div>
               </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Trade Portfolio Items</strong>
                        </div>
                        <div class="card-body">
                            @include('trade_portfolio_items.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
