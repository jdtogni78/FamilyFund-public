<!-- Account Name Field -->
<div class="form-group col-sm-6">
<label for="account_name">Account Name:</label>
<input type="text" name="account_name" class="form-control" maxlength="50">
</div>

<!-- Fund Id Field -->
<div class="form-group col-sm-6">
<label for="portfolio_id">Portfolio Id:</label>
<input type="number" name="portfolio_id" value="{ $portfolio_id ?? null }" class="form-control">
</div>

<!-- TWS Query Id Field -->
<div class="form-group col-sm-6">
<label for="tws_query_id">TWS Query Id:</label>
<input type="text" name="tws_query_id" class="form-control" maxlength="50">
</div>

<!-- TWS Token Field -->
<div class="form-group col-sm-6">
<label for="tws_token">TWS Token:</label>
<input type="text" name="tws_token" class="form-control" maxlength="100">
</div>

@include('trade_portfolios.edit_dates')

<!-- Cash Target Field -->
<div class="form-group col-sm-6">
<label for="cash_target">Cash Target:</label>
<input type="number" name="cash_target" class="form-control" step="0.01">
</div>

<!-- Cash Reserve Target Field -->
<div class="form-group col-sm-6">
<label for="cash_reserve_target">Cash Reserve Target:</label>
<input type="number" name="cash_reserve_target" class="form-control" step="0.01">
</div>

<!-- Max Single Order Field -->
<div class="form-group col-sm-6">
<label for="max_single_order">Max Single Order:</label>
<input type="number" name="max_single_order" class="form-control" step="0.01">
</div>

<!-- Minimum Order Field -->
<div class="form-group col-sm-6">
<label for="minimum_order">Minimum Order:</label>
<input type="number" name="minimum_order" class="form-control" step="0.01">
</div>

<!-- Rebalance Period Field -->
<div class="form-group col-sm-6">
<label for="rebalance_period">Rebalance Period:</label>
<input type="number" name="rebalance_period" class="form-control" step="1">
</div>

<!-- Mode Field -->
<div class="form-group col-sm-6">
<label for="mode">Mode:</label>
<input type="text" name="mode" class="form-control" maxlength="3">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-secondary">Cancel</a>
</div>
