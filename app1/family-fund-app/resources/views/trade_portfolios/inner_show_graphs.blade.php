<div class="col">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-nowrap">
            <strong class="text-truncate" style="min-width: 0;">
                <i class="fa fa-chart-pie me-2"></i>Trade Portfolio Target % {{ $extraTitle ?? '' }}
            </strong>
            <a class="btn btn-sm btn-outline-light flex-shrink-0 ms-2" data-bs-toggle="collapse" href="#collapseTPTA{{ $tradePortfolio->id }}"
               role="button" aria-expanded="false" aria-controls="collapseTPTA{{ $tradePortfolio->id }}">
                <i class="fa fa-chevron-down"></i>
            </a>
        </div>
        <div class="collapse" id="collapseTPTA{{ $tradePortfolio->id }}">
            <div class="card-body">
                @include('trade_portfolios.graph')
            </div>
        </div>
    </div>
</div>
<div class="col">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-nowrap">
            <strong class="text-truncate" style="min-width: 0;">
                <i class="fa fa-layer-group me-2"></i>Trade Portfolio Group % {{ $extraTitle ?? '' }}
            </strong>
            <a class="btn btn-sm btn-outline-light flex-shrink-0 ms-2" data-bs-toggle="collapse" href="#collapseTPGB{{ $tradePortfolio->id }}"
               role="button" aria-expanded="false" aria-controls="collapseTPGB{{ $tradePortfolio->id }}">
                <i class="fa fa-chevron-down"></i>
            </a>
        </div>
        <div class="collapse" id="collapseTPGB{{ $tradePortfolio->id }}">
            <div class="card-body">
                @include('trade_portfolios.group_graph')
            </div>
        </div>
    </div>
</div>
