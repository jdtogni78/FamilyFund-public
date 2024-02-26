<div class="col">
    <div class="card">
        <div class="card-header">
            <strong>Trade Portfolio Target %  {{ $extraTitle ?? '' }}</strong>
        </div>
        <div class="card-body">
            @include('trade_portfolios.graph')
        </div>
    </div>
</div>
<div class="col">
    <div class="card">
        <div class="card-header">
            <strong>Trade Portfolio Group %  {{ $extraTitle ?? '' }}</strong>
        </div>
        <div class="card-body">
            @include('trade_portfolios.group_graph')
        </div>
    </div>
</div>
