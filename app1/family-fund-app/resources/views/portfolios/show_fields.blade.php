@php
    $fund = $portfolio->fund;
    $typeColors = \App\Models\PortfolioExt::TYPE_COLORS;
    $typeLabels = \App\Models\PortfolioExt::TYPE_LABELS;
    $categoryColors = \App\Models\PortfolioExt::CATEGORY_COLORS;
    $categoryLabels = \App\Models\PortfolioExt::CATEGORY_LABELS;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Fund Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-landmark me-1"></i> Fund:</label>
            <p class="mb-0">
                @if($fund)
                    <a href="{{ route('funds.show', $fund->id) }}" class="fw-bold">
                        {{ $fund->name }}
                    </a>
                @else
                    <span class="text-body-secondary">N/A</span>
                @endif
            </p>
        </div>

        <!-- Source Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-database me-1"></i> Source:</label>
            <p class="mb-0 fw-bold">{{ $portfolio->source }}</p>
        </div>

        <!-- Portfolio ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Portfolio ID:</label>
            <p class="mb-0">#{{ $portfolio->id }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                @if($portfolio->type)
                    <span class="badge" style="background: {{ $typeColors[$portfolio->type] ?? '#6b7280' }}; color: white;">
                        {{ $typeLabels[$portfolio->type] ?? ucfirst($portfolio->type) }}
                    </span>
                @else
                    <span class="text-body-secondary">Not set</span>
                @endif
            </p>
        </div>

        <!-- Category Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-folder me-1"></i> Category:</label>
            <p class="mb-0">
                @if($portfolio->category)
                    <span class="badge" style="background: {{ $categoryColors[$portfolio->category] ?? '#6b7280' }}; color: white;">
                        {{ $categoryLabels[$portfolio->category] ?? ucfirst($portfolio->category) }}
                    </span>
                @else
                    <span class="text-body-secondary">Not set</span>
                @endif
            </p>
        </div>

        <!-- Trade Portfolios Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-chart-pie me-1"></i> Trade Portfolios:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $portfolio->tradePortfolios()->count() }}</span>
            </p>
        </div>

        <!-- Portfolio Assets Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-coins me-1"></i> Portfolio Assets:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $portfolio->portfolioAssets()->where('end_dt', '9999-12-31')->count() }} current</span>
            </p>
        </div>
    </div>
</div>
