<!-- Trade Portfolio Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('trade_portfolio_id', 'Trade Portfolio Id:') !!}
    {!! Form::number('trade_portfolio_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Symbol Field -->
<div class="form-group col-sm-6">
    {!! Form::label('symbol', 'Symbol:') !!}
    {!! Form::text('symbol', null, ['class' => 'form-control','maxlength' => 50]) !!}
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::text('type', null, ['class' => 'form-control','maxlength' => 50]) !!}
</div>

<!-- Target Share Field -->
<div class="form-group col-sm-6">
    {!! Form::label('target_share', 'Target Share:') !!}
    {!! Form::number('target_share', null, ['class' => 'form-control', 'step' => 0.001]) !!}
</div>

<!-- Deviation Trigger Field -->
<div class="form-group col-sm-6">
    {!! Form::label('deviation_trigger', 'Deviation Trigger:') !!}
    {!! Form::number('deviation_trigger', null, ['class' => 'form-control', 'step' => 0.0001]) !!}
</div>

<!-- Display Category Field -->
<div class="form-group col-sm-6">
    {!! Form::label('display_category', 'Display Category:') !!}
    {!! Form::text('display_category', null, ['class' => 'form-control','maxlength' => 50]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('tradePortfolioItems.index') }}" class="btn btn-secondary">Cancel</a>
</div>
