@php
    $asset = $assetPrice->asset;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Asset Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-coins me-1"></i> Asset:</label>
            <p class="mb-0">
                @if($asset)
                    <a href="{{ route('assets.show', $asset->id) }}" class="fw-bold">
                        {{ $asset->name }}
                    </a>
                    <span class="badge bg-secondary ms-1">{{ $asset->type }}</span>
                @else
                    <span class="text-body-secondary">ID: {{ $assetPrice->asset_id }}</span>
                @endif
            </p>
        </div>

        <!-- Price Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Price:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold text-success">${{ number_format($assetPrice->price, 4) }}</span>
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Date Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Effective Period:</label>
            <p class="mb-0">
                {{ $assetPrice->start_dt }} <i class="fa fa-arrow-right mx-2 text-body-secondary"></i> {{ $assetPrice->end_dt }}
                @if($assetPrice->end_dt == '9999-12-31')
                    <span class="badge bg-success ms-2">Current</span>
                @endif
            </p>
        </div>

        <!-- Asset Price ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Asset Price ID:</label>
            <p class="mb-0">#{{ $assetPrice->id }}</p>
        </div>
    </div>
</div>
