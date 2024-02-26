<div class="row">

    <!-- Account Name Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('account_name', 'Account Name:') !!}
        <p>{{ $tradePortfolio->account_name }}</p>
    </div>

    <!-- Fund Id Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('portfolio_id', 'Portfolio Id:') !!}
        <p>{{ $tradePortfolio->portfolio_id }}</p>
    </div>

    <!-- Fund Name Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('portfolio_name', 'Portfolio Source:') !!}
        <p>{{ $api['portfolio']['source'] }}</p>
    </div>

    @include('trade_portfolios.show_dates')

    <!-- Cash Target Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('cash_target', 'Cash Target:') !!}
        <p>{{ $tradePortfolio->cash_target * 100 }}%</p>
    </div>

    <!-- Cash Reserve Target Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('cash_reserve_target', 'Cash Reserve Target:') !!}
        <p>{{ $tradePortfolio->cash_reserve_target * 100}}%</p>
    </div>

    <!-- Max Single Order Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('max_single_order', 'Max Single Order:') !!}
        <p>{{ $tradePortfolio->max_single_order * 100}}%</p>
    </div>

    <!-- Minimum Order Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('minimum_order', 'Minimum Order:') !!}
        <p>${{ $tradePortfolio->minimum_order }}</p>
    </div>

    <!-- Rebalance Period Field -->
    <div class="form-group  col-sm-6">
        {!! Form::label('rebalance_period', 'Rebalance Period:') !!}
        <p>{{ $tradePortfolio->rebalance_period }} days</p>
    </div>

    <!-- Total Share Field: calculate cash target plus sum of all item shares -->
    <div class="form-group col-sm-6 font-weight-bold {{ $tradePortfolio->total_shares == 100 ? 'text-success' : 'text-danger' }}">
        {!! Form::label('total_share', 'Total Shares:') !!}
        <p>{{ $tradePortfolio->total_shares }}%</p>
    </div>
</div>
