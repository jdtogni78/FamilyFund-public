<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolios.index') }}">Portfolios</a>
        </li>
        <li class="breadcrumb-item active">{{ $portfolio->source }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-briefcase me-2"></i>
                                <strong>{{ $portfolio->source }}</strong>
                                @if($portfolio->fund)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('funds.show', $portfolio->fund_id) }}">{{ $portfolio->fund->name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                @include('portfolios.actions', ['portfolio' => $portfolio])
                                <a href="{{ route('portfolios.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('portfolios.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade Portfolios Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-chart-pie me-2"></i>
                                <strong>Trade Portfolios</strong>
                                <span class="badge bg-primary ms-2">{{ $portfolio->tradePortfolios()->count() }}</span>
                            </div>
                            <a href="{{ route('tradePortfolios.create') }}?portfolio_id={{ $portfolio->id }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus me-1"></i> New Trade Portfolio
                            </a>
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
