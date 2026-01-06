<div class="col-lg-6 mb-4 mb-lg-0">
    <div class="card h-100">
        <div class="card-header">
            <strong><i class="fa fa-crosshairs me-2"></i>Trade Portfolio Target % {{ $extraTitle ?? '' }}</strong>
        </div>
        <div class="card-body">
            @include('trade_portfolios.graph')
        </div>
    </div>
</div>
<div class="col-lg-6">
    <div class="card h-100">
        <div class="card-header">
            <strong><i class="fa fa-object-group me-2"></i>Trade Portfolio Group % {{ $extraTitle ?? '' }}</strong>
        </div>
        <div class="card-body">
            @include('trade_portfolios.group_graph')
        </div>
    </div>
</div>