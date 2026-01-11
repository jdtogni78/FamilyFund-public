<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolioItems.index') }}">Trade Portfolio Items</a>
        </li>
        <li class="breadcrumb-item active">Item #{{ $tradePortfolioItem->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-exchange-alt me-2"></i>
                                <strong>Trade Portfolio Item #{{ $tradePortfolioItem->id }}</strong>
                                @if($tradePortfolioItem->tradePortfolio)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('tradePortfolios.show', $tradePortfolioItem->trade_portfolio_id) }}">Trade Portfolio #{{ $tradePortfolioItem->trade_portfolio_id }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('tradePortfolioItems.edit', $tradePortfolioItem->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('tradePortfolioItems.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('trade_portfolio_items.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
