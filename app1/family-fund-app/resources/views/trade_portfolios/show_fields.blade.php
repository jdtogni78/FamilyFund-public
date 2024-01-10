<div class="row">

<!-- Account Name Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('account_name', 'Account Name:') !!}
    <p>{{ $tradePortfolio->account_name }}</p>
</div>

<!-- Fund Id Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('fund_id', 'Fund Id:') !!}
    <p>{{ $tradePortfolio->fund_id }}</p>
</div>

<!-- Fund Name Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('fund_name', 'Fund Name:') !!}
    <p>{{ $fund->name }}</p>
</div>

<!-- Start Date Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('start_dt', 'Start Date:') !!}
    <p>{{ $tradePortfolio->start_dt }}</p>
</div>

<!-- create end date field -->
<div class="form-group  col-sm-6">
    {!! Form::label('end_dt', 'End Date:') !!}
    <p>{{ $tradePortfolio->end_dt }}</p>
</div>

<!-- Cash Target Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('cash_target', 'Cash Target:') !!}
    <p>{{ $tradePortfolio->cash_target }}</p>
</div>

<!-- Cash Reserve Target Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('cash_reserve_target', 'Cash Reserve Target:') !!}
    <p>{{ $tradePortfolio->cash_reserve_target }}</p>
</div>

<!-- Max Single Order Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('max_single_order', 'Max Single Order:') !!}
    <p>{{ $tradePortfolio->max_single_order }}</p>
</div>

<!-- Minimum Order Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('minimum_order', 'Minimum Order:') !!}
    <p>{{ $tradePortfolio->minimum_order }}</p>
</div>

<!-- Rebalance Period Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('rebalance_period', 'Rebalance Period:') !!}
    <p>{{ $tradePortfolio->rebalance_period }}</p>
</div>

</div>
