@include('trade_portfolios.show_dates')
@include('trade_portfolios.edit_dates')

<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Split</button>
    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-secondary">Cancel</a>
</div>
