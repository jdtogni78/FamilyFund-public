<!-- Trade Portfolio Id Field -->
<div class="form-group col-sm-6">
<label for="trade_portfolio_id">Trade Portfolio Id:</label>
<select name="trade_portfolio_id" class="form-control">
    @foreach($api['portMap'] as $value => $label)
        <option value="{ $value }" { isset($api['tradePortfolioId']) ? $api['tradePortfolioId'] : null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Symbol Field -->
<div class="form-group col-sm-6">
<label for="symbol">Symbol:</label>
<select name="symbol" class="form-control" maxlength="50">
    @foreach($api['assetMap'] as $value => $label)
        <option value="{ $value }" { isset($api['symbol']) ? $api['symbol'] : null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<select name="type" class="form-control">
    @foreach($api['typeMap'] as $value => $label)
        <option value="{ $value }" { isset($api['type']) ? $api['type'] : null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Target Share Field -->
<div class="form-group col-sm-6">
<label for="target_share">Target Share:</label>
<input type="number" name="target_share" value="{ isset($api['targetShare']) ? $api['targetShare'] : null }" class="form-control" step="0.001">
</div>

<!-- Deviation Trigger Field -->
<div class="form-group col-sm-6">
<label for="deviation_trigger">Deviation Trigger:</label>
<input type="number" name="deviation_trigger" value="{ isset($api['deviationTrigger']) ? $api['deviationTrigger'] : null }" class="form-control" step="0.0001">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('tradePortfolioItems.index') }}" class="btn btn-secondary">Cancel</a>
</div>
