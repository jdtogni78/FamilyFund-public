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

            <!-- Related Assets Section (via linked_asset_id) -->
            @php
                // Get assets linked TO this asset (e.g., mortgages pointing to this property)
                $linkedFrom = $asset->linkedFrom;
                // Get the asset this one links TO (e.g., property this mortgage points to)
                $linkedTo = $asset->linkedAsset;
                // Combine into related assets collection
                $relatedAssets = collect();
                if ($linkedTo) {
                    $relatedAssets->push($linkedTo);
                    // Also get other assets linked to the same parent
                    $siblings = $linkedTo->linkedFrom->where('id', '!=', $asset->id);
                    $relatedAssets = $relatedAssets->merge($siblings);
                }
                $relatedAssets = $relatedAssets->merge($linkedFrom);
            @endphp
            @if($relatedAssets->count() > 0)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-link me-2"></i>
                            <strong>Related Assets</strong>
                            <span class="badge bg-secondary ms-2">{{ $relatedAssets->count() }}</span>
                        </div>
                        <div class="card-body">
                            <p class="text-body-secondary small mb-3">
                                Assets linked to {{ $linkedTo ? '"' . $linkedTo->name . '"' : 'this asset' }}
                            </p>
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Latest Price</th>
                                        <th>As Of</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($relatedAssets as $related)
                                    @php
                                        $latestPrice = $related->assetPrices()->orderBy('start_dt', 'desc')->first();
                                        $isLiability = in_array($related->type, ['MORTGAGE', 'LOAN', 'CREDIT_CARD']);
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge {{ $isLiability ? 'bg-danger' : 'bg-success' }}">
                                                {{ ucfirst(str_replace('_', ' ', $related->type)) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($latestPrice)
                                                @if($isLiability)
                                                    <span class="text-danger">-${{ number_format($latestPrice->price, 2) }}</span>
                                                @else
                                                    ${{ number_format($latestPrice->price, 2) }}
                                                @endif
                                            @else
                                                <span class="text-body-secondary">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $latestPrice ? $latestPrice->start_dt : '-' }}
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('assets.show', $related->id) }}" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $totalValue = 0;
                                    foreach($relatedAssets as $related) {
                                        $price = $related->assetPrices()->orderBy('start_dt', 'desc')->first();
                                        if ($price) {
                                            $isLiability = in_array($related->type, ['MORTGAGE', 'LOAN', 'CREDIT_CARD']);
                                            $totalValue += $isLiability ? -$price->price : $price->price;
                                        }
                                    }
                                    // Add current asset
                                    $currentPrice = $asset->assetPrices()->orderBy('start_dt', 'desc')->first();
                                    if ($currentPrice) {
                                        $isCurrentLiability = in_array($asset->type, ['MORTGAGE', 'LOAN', 'CREDIT_CARD']);
                                        $totalValue += $isCurrentLiability ? -$currentPrice->price : $currentPrice->price;
                                    }
                                @endphp
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="2">
                                            <strong>Net Equity (including this asset)</strong>
                                        </td>
                                        <td colspan="2">
                                            <strong class="{{ $totalValue >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($totalValue, 2) }}
                                            </strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
