<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Trade Portfolio Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="trade_portfolio_id" class="form-label">
            <i class="fa fa-chart-pie me-1"></i> Trade Portfolio <span class="text-danger">*</span>
        </label>
        <select name="trade_portfolio_id" id="trade_portfolio_id" class="form-control form-select" required>
            @foreach($api['portMap'] as $value => $label)
                <option value="{{ $value }}" {{ (isset($api['tradePortfolioId']) && $api['tradePortfolioId'] == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Portfolio this item belongs to</small>
    </div>

    <!-- Symbol Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="symbol" class="form-label">
            <i class="fa fa-coins me-1"></i> Symbol <span class="text-danger">*</span>
        </label>
        <select name="symbol" id="symbol" class="form-control form-select" required>
            @foreach($api['assetMap'] as $value => $label)
                <option value="{{ $value }}" {{ (isset($api['symbol']) && $api['symbol'] == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Asset symbol/ticker</small>
    </div>
</div>

<div class="row">
    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type <span class="text-danger">*</span>
        </label>
        <select name="type" id="type" class="form-control form-select" required>
            @foreach($api['typeMap'] as $value => $label)
                <option value="{{ $value }}" {{ (isset($api['type']) && $api['type'] == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Item type classification</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Target Share Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="target_share" class="form-label">
            <i class="fa fa-bullseye me-1"></i> Target Share <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number" name="target_share" id="target_share" class="form-control" step="0.001" min="0" max="1"
                   value="{{ $api['targetShare'] ?? old('target_share') }}" required>
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Target allocation as decimal (e.g., 0.10 = 10%)</small>
    </div>

    <!-- Deviation Trigger Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="deviation_trigger" class="form-label">
            <i class="fa fa-exclamation-triangle me-1"></i> Deviation Trigger
        </label>
        <div class="input-group">
            <input type="number" name="deviation_trigger" id="deviation_trigger" class="form-control" step="0.0001" min="0" max="1"
                   value="{{ $api['deviationTrigger'] ?? old('deviation_trigger') }}">
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Threshold deviation to trigger rebalance (e.g., 0.05 = 5%)</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('tradePortfolioItems.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
