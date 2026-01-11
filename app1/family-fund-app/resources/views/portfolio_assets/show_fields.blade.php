@php
    $portfolio = $portfolioAsset->portfolio;
    $asset = $portfolioAsset->asset;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Portfolio Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-briefcase me-1"></i> Portfolio:</label>
            <p class="mb-0">
                @if($portfolio)
                    <a href="{{ route('portfolios.show', $portfolio->id) }}" class="fw-bold">
                        {{ $portfolio->source }}
                    </a>
                    @if($portfolio->fund)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-landmark me-1"></i>
                            <a href="{{ route('funds.show', $portfolio->fund_id) }}">{{ $portfolio->fund->name }}</a>
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $portfolioAsset->portfolio_id }}</span>
                @endif
            </p>
        </div>

        <!-- Asset Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-coins me-1"></i> Asset:</label>
            <p class="mb-0">
                @if($asset)
                    <a href="{{ route('assets.show', $asset->id) }}" class="fw-bold">
                        {{ $asset->name }}
                    </a>
                    <span class="badge bg-secondary ms-1">{{ $asset->type }}</span>
                    @if($asset->source)
                        <br><small class="text-body-secondary">Source: {{ $asset->source }}</small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $portfolioAsset->asset_id }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Position Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-chart-bar me-1"></i> Position:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold">{{ number_format($portfolioAsset->position, 4) }}</span>
            </p>
        </div>

        <!-- Date Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Effective Period:</label>
            <p class="mb-0">
                {{ $portfolioAsset->start_dt }} <i class="fa fa-arrow-right mx-2 text-body-secondary"></i> {{ $portfolioAsset->end_dt }}
                @if($portfolioAsset->end_dt == '9999-12-31')
                    <span class="badge bg-success ms-2">Current</span>
                @endif
            </p>
        </div>

        <!-- Portfolio Asset ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Portfolio Asset ID:</label>
            <p class="mb-0">#{{ $portfolioAsset->id }}</p>
        </div>
    </div>
</div>
