<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolioAssets.index') }}">Portfolio Assets</a>
        </li>
        <li class="breadcrumb-item active">Position #{{ $portfolioAsset->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-chart-bar me-2"></i>
                                <strong>Portfolio Asset #{{ $portfolioAsset->id }}</strong>
                                @if($portfolioAsset->asset)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('assets.show', $portfolioAsset->asset_id) }}">{{ $portfolioAsset->asset->name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('portfolioAssets.edit', $portfolioAsset->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('portfolioAssets.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('portfolio_assets.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
