<!-- Trade Portfolio Id Field -->
<div class="form-group">
    {!! Form::label('trade_portfolio_id', 'Trade Portfolio Id:') !!}
    <p>{{ $tradePortfolioItem->trade_portfolio_id }}</p>
</div>

<!-- Symbol Field -->
<div class="form-group">
    {!! Form::label('symbol', 'Symbol:') !!}
    <p>{{ $tradePortfolioItem->symbol }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $tradePortfolioItem->type }}</p>
</div>

<!-- Target Share Field -->
<div class="form-group">
    {!! Form::label('target_share', 'Target Share:') !!}
    <p>{{ $tradePortfolioItem->target_share }}</p>
</div>

<!-- Deviation trigger Field -->
<div class="form-group">
    {!! Form::label('deviation_trigger', 'Deviation trigger:') !!}
    <p>{{ $tradePortfolioItem->deviation_trigger }}</p>
</div>

