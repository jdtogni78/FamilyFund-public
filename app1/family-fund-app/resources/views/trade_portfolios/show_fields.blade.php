<!-- Account Name Field -->
<div class="form-group">
    {!! Form::label('account_name', 'Account Name:') !!}
    <p>{{ $tradePortfolio->account_name }}</p>
</div>

<!-- Cash Target Field -->
<div class="form-group">
    {!! Form::label('cash_target', 'Cash Target:') !!}
    <p>{{ $tradePortfolio->cash_target }}</p>
</div>

<!-- Cash Reserve Target Field -->
<div class="form-group">
    {!! Form::label('cash_reserve_target', 'Cash Reserve Target:') !!}
    <p>{{ $tradePortfolio->cash_reserve_target }}</p>
</div>

<!-- Max Single Order Field -->
<div class="form-group">
    {!! Form::label('max_single_order', 'Max Single Order:') !!}
    <p>{{ $tradePortfolio->max_single_order }}</p>
</div>

<!-- Minimum Order Field -->
<div class="form-group">
    {!! Form::label('minimum_order', 'Minimum Order:') !!}
    <p>{{ $tradePortfolio->minimum_order }}</p>
</div>

<!-- Rebalance Period Field -->
<div class="form-group">
    {!! Form::label('rebalance_period', 'Rebalance Period:') !!}
    <p>{{ $tradePortfolio->rebalance_period }}</p>
</div>

