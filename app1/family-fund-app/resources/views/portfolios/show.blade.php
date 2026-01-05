<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolios.index') }}">Portfolio</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Details</strong>
                            <a href="{{ route('portfolios.index') }}" class="btn btn-light">Back</a>
                            @include('portfolios.actions', ['portfolio' => $portfolio])
                        </div>
                        <div class="card-body">
                            @include('portfolios.show_fields')
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Trade Portfolios</strong>
                        </div>
                        <div class="card-body">
                            @php($tradePortfolios = $portfolio->tradePortfolios()->get()->sortByDesc('end_dt'))
                            @include('trade_portfolios.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
