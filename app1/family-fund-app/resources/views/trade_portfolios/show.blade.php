@extends('layouts.app')

@section('content')
    <script type="text/javascript">
        var api = {!! json_encode($tradePortfolio) !!};
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
            @isset($split) @if($split==true)
                <div class="row">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Split Trade Portfolio</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::model($tradePortfolio, ['route' => ['tradePortfolios.split', $tradePortfolio->id], 'method' => 'patch']) !!}
                                @include('trade_portfolios.split_fields')
                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
            @endif @endisset
            @include("trade_portfolios.inner_show")
            <div class="row">
                @include("trade_portfolios.inner_show_graphs")
            </div>
        </div>
    </div>
@endsection
