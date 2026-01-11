<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Account Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="account_name" class="form-label">
            <i class="fa fa-user me-1"></i> Account Name
        </label>
        <input type="text" name="account_name" id="account_name" class="form-control" maxlength="50"
               value="{{ $tradePortfolio->account_name ?? old('account_name') }}">
        <small class="text-body-secondary">Optional identifier for the trading account</small>
    </div>

    <!-- Portfolio Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="portfolio_id" class="form-label">
            <i class="fa fa-briefcase me-1"></i> Portfolio Id
        </label>
        <input type="number" name="portfolio_id" id="portfolio_id" class="form-control"
               value="{{ $tradePortfolio->portfolio_id ?? $portfolio_id ?? old('portfolio_id') }}">
        <small class="text-body-secondary">Link to an existing portfolio</small>
    </div>
</div>

<div class="row">
    <!-- TWS Query Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="tws_query_id" class="form-label">
            <i class="fa fa-search me-1"></i> TWS Query Id
        </label>
        <input type="text" name="tws_query_id" id="tws_query_id" class="form-control" maxlength="50"
               value="{{ $tradePortfolio->tws_query_id ?? old('tws_query_id') }}">
        <small class="text-body-secondary">Interactive Brokers TWS flex query identifier</small>
    </div>

    <!-- TWS Token Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="tws_token" class="form-label">
            <i class="fa fa-key me-1"></i> TWS Token
        </label>
        <input type="text" name="tws_token" id="tws_token" class="form-control" maxlength="100"
               value="{{ $tradePortfolio->tws_token ?? old('tws_token') }}">
        <small class="text-body-secondary">Interactive Brokers TWS flex query token</small>
    </div>
</div>

@include('trade_portfolios.edit_dates')

<hr class="my-3">

<div class="row">
    <!-- Cash Target Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="cash_target" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Cash Target <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number" name="cash_target" id="cash_target" class="form-control" step="0.01" min="0" max="1"
                   value="{{ $tradePortfolio->cash_target ?? old('cash_target', '0.05') }}" required>
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Target cash allocation as decimal (e.g., 0.05 = 5%)</small>
    </div>

    <!-- Cash Reserve Target Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="cash_reserve_target" class="form-label">
            <i class="fa fa-piggy-bank me-1"></i> Cash Reserve Target
        </label>
        <div class="input-group">
            <input type="number" name="cash_reserve_target" id="cash_reserve_target" class="form-control" step="0.01" min="0" max="1"
                   value="{{ $tradePortfolio->cash_reserve_target ?? old('cash_reserve_target', '0') }}">
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Additional cash buffer as decimal</small>
    </div>
</div>

<div class="row">
    <!-- Max Single Order Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="max_single_order" class="form-label">
            <i class="fa fa-arrow-up me-1"></i> Max Single Order
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="max_single_order" id="max_single_order" class="form-control" step="0.01" min="0"
                   value="{{ $tradePortfolio->max_single_order ?? old('max_single_order') }}">
        </div>
        <small class="text-body-secondary">Maximum dollar amount for a single trade order</small>
    </div>

    <!-- Minimum Order Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="minimum_order" class="form-label">
            <i class="fa fa-arrow-down me-1"></i> Minimum Order
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="minimum_order" id="minimum_order" class="form-control" step="0.01" min="0"
                   value="{{ $tradePortfolio->minimum_order ?? old('minimum_order') }}">
        </div>
        <small class="text-body-secondary">Minimum dollar amount for a trade order</small>
    </div>
</div>

<div class="row">
    <!-- Rebalance Period Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="rebalance_period" class="form-label">
            <i class="fa fa-sync me-1"></i> Rebalance Period
        </label>
        <div class="input-group">
            <input type="number" name="rebalance_period" id="rebalance_period" class="form-control" step="1" min="0"
                   value="{{ $tradePortfolio->rebalance_period ?? old('rebalance_period') }}">
            <span class="input-group-text">days</span>
        </div>
        <small class="text-body-secondary">Days between automatic rebalancing checks</small>
    </div>

    <!-- Mode Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="mode" class="form-label">
            <i class="fa fa-cog me-1"></i> Mode <span class="text-danger">*</span>
        </label>
        <select name="mode" id="mode" class="form-control form-select" required>
            <option value="STD" {{ (isset($tradePortfolio) && $tradePortfolio->mode == 'STD') || old('mode') == 'STD' ? 'selected' : (!isset($tradePortfolio) && old('mode') === null ? 'selected' : '') }}>
                STD - Standard
            </option>
            <option value="MAX" {{ (isset($tradePortfolio) && $tradePortfolio->mode == 'MAX') || old('mode') == 'MAX' ? 'selected' : '' }}>
                MAX - Maximum
            </option>
        </select>
        <small class="text-body-secondary">Trading mode: STD for standard rebalancing, MAX for aggressive</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
