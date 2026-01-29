<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolios.index') }}">Portfolios</a>
        </li>
        <li class="breadcrumb-item active">{{ $portfolio->display_name ?? $portfolio->source }}</li>
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
                                <strong>{{ $portfolio->display_name ?? $portfolio->source }}</strong>
                                @if($portfolio->display_name)
                                    <code class="ms-2 small">{{ $portfolio->source }}</code>
                                @endif
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

            <!-- Portfolio Assets Section -->
            @php
                $asOf = request()->get('as_of', now()->format('Y-m-d'));
                $portfolioAssets = $portfolio->portfolioAssets()
                    ->where('start_dt', '<=', $asOf)
                    ->where('end_dt', '>=', $asOf)
                    ->with('asset')
                    ->orderBy('position', 'desc')
                    ->get();
            @endphp
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-coins me-2"></i>
                                <strong>Assets</strong>
                                <span class="badge bg-primary ms-2">{{ $portfolioAssets->count() }}</span>
                                <span class="text-body-secondary ms-2 small">as of {{ $asOf }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($portfolioAssets->count() > 0)
                                <div class="table-responsive-sm">
                                    <table class="table table-striped table-sm" id="portfolio-assets-table">
                                        <thead>
                                            <tr>
                                                <th>Symbol</th>
                                                <th>Type</th>
                                                <th class="text-end">Position</th>
                                                <th class="text-end">Price</th>
                                                <th class="text-end">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @php $totalValue = 0; @endphp
                                        @foreach($portfolioAssets as $pa)
                                            @php
                                                $asset = $pa->asset;
                                                $priceRecord = $asset ? $asset->priceAsOf($asOf)?->first() : null;
                                                $price = $priceRecord?->price ?? 0;
                                                $value = $pa->position * $price;
                                                $totalValue += $value;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if($asset)
                                                        <a href="{{ route('assets.show', $asset->id) }}"><strong>{{ $asset->name }}</strong></a>
                                                    @else
                                                        <strong>N/A</strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $asset->type ?? 'N/A' }}</span>
                                                </td>
                                                <td class="text-end">{{ number_format($pa->position, 4) }}</td>
                                                <td class="text-end">${{ number_format($price, 2) }}</td>
                                                <td class="text-end">${{ number_format($value, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-dark">
                                                <th colspan="4" class="text-end">Total Value:</th>
                                                <th class="text-end">${{ number_format($totalValue, 2) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <p class="text-body-secondary mb-0">No assets in this portfolio as of {{ $asOf }}</p>
                            @endif
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
