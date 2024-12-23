<div class="col">
    <div class="card">
        <div class="card-header">
            <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPTA" 
            role="button" aria-expanded="false" aria-controls="collapseTPTA">
            Trade Portfolio Target %  {{ $extraTitle ?? '' }}
            </a>
        </div>
        <div class="collapse" id="collapseTPTA">
            <div class="card-body">
                @include('trade_portfolios.graph')
            </div>
        </div>
    </div>
</div>
<div class="col">
    <div class="card">
        <div class="card-header">
            <a class="btn btn-primary" data-toggle="collapse" href="#collapseTPGB" 
            role="button" aria-expanded="false" aria-controls="collapseTPGB">
            Trade Portfolio Group %  {{ $extraTitle ?? '' }}
            </a>
        </div>
        <div class="collapse" id="collapseTPGB">
            <div class="card-body">
                @include('trade_portfolios.group_graph')
            </div>
        </div>
    </div>
</div>