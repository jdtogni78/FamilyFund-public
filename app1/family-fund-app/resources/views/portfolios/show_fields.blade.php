@php
    $fund = $portfolio->fund;
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
