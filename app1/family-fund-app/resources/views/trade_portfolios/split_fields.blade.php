@include('trade_portfolios.show_dates')
@include('trade_portfolios.edit_dates')

<div class="form-group col-sm-12">
    {!! Form::submit('Split', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-secondary">Cancel</a>
</div>
