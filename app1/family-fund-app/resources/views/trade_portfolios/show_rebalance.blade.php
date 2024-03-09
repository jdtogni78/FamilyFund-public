@extends('layouts.app')

@section('content')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};
    </script>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.index') }}">Trade Portfolio</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            @foreach($api['tradePortfolio']->tradePortfolioItems()->get() as $item)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Symbol {{ $item->symbol }}</strong>
                            </div>
                            <div class="card-body">
                                @include("trade_portfolio_items.rebalance_line_graph")
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
