@php
    use App\Models\TradePortfolioItemExt;
    $tradePortfolio = $tradePortfolioItem->tradePortfolio;
    $typeMap = TradePortfolioItemExt::typeMap();
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Trade Portfolio Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-chart-pie me-1"></i> Trade Portfolio:</label>
            <p class="mb-0">
                @if($tradePortfolio)
                    <a href="{{ route('tradePortfolios.show', $tradePortfolio->id) }}" class="fw-bold">
                        {{ $tradePortfolio->account_name }}
                    </a>
                    @if($tradePortfolio->portfolio)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-briefcase me-1"></i>
                            <a href="{{ route('portfolios.show', $tradePortfolio->portfolio_id) }}">{{ $tradePortfolio->portfolio->source }}</a>
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $tradePortfolioItem->trade_portfolio_id }}</span>
                @endif
            </p>
        </div>

        <!-- Symbol Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Symbol:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $tradePortfolioItem->symbol }}</p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-cube me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ $typeMap[$tradePortfolioItem->type] ?? $tradePortfolioItem->type }}</span>
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Target Share Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-crosshairs me-1"></i> Target Share:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold text-primary">{{ number_format($tradePortfolioItem->target_share * 100, 2) }}%</span>
            </p>
        </div>

        <!-- Deviation Trigger Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-exclamation-triangle me-1"></i> Deviation Trigger:</label>
            <p class="mb-0">
                <span class="fs-5 fw-bold">{{ number_format($tradePortfolioItem->deviation_trigger * 100, 2) }}%</span>
            </p>
        </div>

        <!-- Trade Portfolio Item ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Item ID:</label>
            <p class="mb-0">#{{ $tradePortfolioItem->id }}</p>
        </div>
    </div>
</div>
