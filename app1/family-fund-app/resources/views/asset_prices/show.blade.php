<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('assetPrices.index') }}">Asset Prices</a>
        </li>
        <li class="breadcrumb-item active">Price #{{ $assetPrice->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-dollar-sign me-2"></i>
                                <strong>Asset Price #{{ $assetPrice->id }}</strong>
                                @if($assetPrice->asset)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('assets.show', $assetPrice->asset_id) }}">{{ $assetPrice->asset->name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('assetPrices.edit', $assetPrice->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('assetPrices.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('asset_prices.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
