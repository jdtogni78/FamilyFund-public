<div class="col">
    <div class="card">
        <div class="card-header">
            <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPTA{{ $tradePortfolio->id }}" 
            role="button" aria-expanded="false" aria-controls="collapseTPTA{{ $tradePortfolio->id }}">
            Trade Portfolio Target %  {{ $extraTitle ?? '' }}
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
    <div class="card">
        <div class="card-header">
            <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPGB{{ $tradePortfolio->id }}" 
            role="button" aria-expanded="false" aria-controls="collapseTPGB{{ $tradePortfolio->id }}">
            Trade Portfolio Group %  {{ $extraTitle ?? '' }}
            </a>
        </div>
        <div class="collapse" id="collapseTPGB{{ $tradePortfolio->id }}">
            <div class="card-body">
                @include('trade_portfolios.group_graph')
            </div>
        </div>
    </div>
</div>