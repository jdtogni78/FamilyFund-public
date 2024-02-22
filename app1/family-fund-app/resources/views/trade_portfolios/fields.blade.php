<!-- Account Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_name', 'Account Name:') !!}
    {!! Form::text('account_name', null, ['class' => 'form-control','maxlength' => 50]) !!}
</div>

<!-- Fund Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('portfolio_id', 'Portfolio Id:') !!}
    {!! Form::number('portfolio_id', $portfolio_id, ['class' => 'form-control']) !!}
</div>

@include('trade_portfolios.edit_dates')

<!-- Cash Target Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cash_target', 'Cash Target:') !!}
    {!! Form::number('cash_target', null, ['class' => 'form-control', 'step' => 0.01]) !!}
</div>

<!-- Cash Reserve Target Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cash_reserve_target', 'Cash Reserve Target:') !!}
    {!! Form::number('cash_reserve_target', null, ['class' => 'form-control', 'step' => 0.01]) !!}
</div>

<!-- Max Single Order Field -->
<div class="form-group col-sm-6">
    {!! Form::label('max_single_order', 'Max Single Order:') !!}
    {!! Form::number('max_single_order', null, ['class' => 'form-control', 'step' => 0.01]) !!}
</div>

<!-- Minimum Order Field -->
<div class="form-group col-sm-6">
    {!! Form::label('minimum_order', 'Minimum Order:') !!}
    {!! Form::number('minimum_order', null, ['class' => 'form-control', 'step' => 0.01]) !!}
</div>

<!-- Rebalance Period Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rebalance_period', 'Rebalance Period:') !!}
    {!! Form::number('rebalance_period', null, ['class' => 'form-control', 'step' => 1]) !!}
</div>

<!-- Mode Field -->
<div class="form-group col-sm-6">
    {!! Form::label('mode', 'Mode:') !!}
    {!! Form::text('mode', null, ['class' => 'form-control','maxlength' => 3]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-secondary">Cancel</a>
</div>
