<x-app-layout>

@section('content')
    <script type="text/javascript">
        var api = {!! json_encode($tradePortfolio) !!};
    </script>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.index') }}">Trade Portfolios</a>
        </li>
        <li class="breadcrumb-item active">Portfolio #{{ $tradePortfolio->id }}</li>
    </ol>

    <!-- Action Bar -->
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('portfolios.show', $tradePortfolio->portfolio_id) }}" class="btn btn-sm btn-outline-primary me-2">
            <i class="fa fa-briefcase me-1"></i> View Portfolio
        </a>
        <a href="{{ route('tradePortfolios.index') }}" class="btn btn-sm btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            @isset($split) @if($split==true)
                <div class="row">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Split Trade Portfolio</strong>
                            </div>
                            <div class="card-body">
<form method="POST" action="{{ route('tradePortfolios.split', $tradePortfolio->id) }}">
                                    @csrf
                                    @method('PATCH')
                                @include('trade_portfolios.split_fields')
</form>
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
</x-app-layout>
