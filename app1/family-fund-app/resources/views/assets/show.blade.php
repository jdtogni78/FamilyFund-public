<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('assets.index') }}">Assets</a>
        </li>
        <li class="breadcrumb-item active">{{ $asset->name }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-coins me-2"></i>
                                <strong>{{ $asset->name }}</strong>
                                <span class="badge bg-info ms-2">{{ $asset->type }}</span>
                            </div>
                            <div>
                                <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('assets.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Prices Section -->
            @php
                $assetPrices = $asset->assetPrices()
                    ->orderBy('start_dt', 'desc')
                    ->limit(20)
                    ->get();
                $dataWarnings = ['overlappingIds' => [], 'gapIds' => [], 'longSpanIds' => []];
            @endphp
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-chart-line me-2"></i>
                                <strong>Price History</strong>
                                <span class="badge bg-primary ms-2">{{ $asset->assetPrices()->count() }}</span>
                            </div>
                            <a href="{{ route('assetPrices.index', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-list me-1"></i> View All
                            </a>
                        </div>
                        <div class="card-body">
                            @if($assetPrices->count() > 0)
                                @include('asset_prices.table')
                            @else
                                <p class="text-body-secondary mb-0">No price history for this asset</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
