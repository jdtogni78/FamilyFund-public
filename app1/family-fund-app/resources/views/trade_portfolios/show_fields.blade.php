<div class="row">

    <!-- Account Name Field -->
    <div class="form-group  col-sm-6">
<label for="account_name">Account Name:</label>
        <p>{{ $tradePortfolio->account_name }}</p>
    </div>

    <!-- Fund Id Field -->
    <div class="form-group  col-sm-6">
<label for="portfolio_id">Portfolio Id:</label>
        <p>{{ $tradePortfolio->portfolio_id }}</p>
    </div>

    <!-- Fund Name Field -->
    <div class="form-group  col-sm-6">
<label for="portfolio_name">Portfolio Source:</label>
        <p>{{ $api['portfolio']['source'] }}</p>
    </div>

    <!-- TWS Query Id Field -->
    <div class="form-group  col-sm-6">
<label for="tws_query_id">TWS Query Id:</label>
        <p>{{ $tradePortfolio->tws_query_id }}</p>
    </div>

    <!-- TWS Token Field -->
    <div class="form-group  col-sm-6">
<label for="tws_token">TWS Token:</label>
        <p>{{ $tradePortfolio->tws_token }}</p>
    </div>

    @include('trade_portfolios.show_dates')

    <!-- Cash Target Field -->
    <div class="form-group  col-sm-6">
<label for="cash_target">Cash Target:</label>
        <p>{{ $tradePortfolio->cash_target * 100 }}%</p>
    </div>

    <!-- Cash Reserve Target Field -->
    <div class="form-group  col-sm-6">
<label for="cash_reserve_target">Cash Reserve Target:</label>
        <p>{{ $tradePortfolio->cash_reserve_target * 100}}%</p>
    </div>

    <!-- Max Single Order Field -->
    <div class="form-group  col-sm-6">
<label for="max_single_order">Max Single Order:</label>
        <p>{{ $tradePortfolio->max_single_order * 100}}%</p>
    </div>

    <!-- Minimum Order Field -->
    <div class="form-group  col-sm-6">
<label for="minimum_order">Minimum Order:</label>
        <p>${{ $tradePortfolio->minimum_order }}</p>
    </div>

    <!-- Rebalance Period Field -->
    <div class="form-group  col-sm-6">
<label for="rebalance_period">Rebalance Period:</label>
        <p>{{ $tradePortfolio->rebalance_period }} days</p>
    </div>

    <!-- Total Share Field: calculate cash target plus sum of all item shares -->
    <div class="form-group col-sm-6 font-weight-bold {{ $tradePortfolio->total_shares - 100 == 0 ? 'text-success' : 'text-danger' }}">
<label for="total_share">Total Shares:</label>
        <p>{{ $tradePortfolio->total_shares }}%</p>
    </div>
</div>
