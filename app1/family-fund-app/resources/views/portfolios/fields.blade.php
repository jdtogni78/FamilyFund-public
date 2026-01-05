<!-- Fund Id Field -->
<div class="form-group col-sm-6">
    <label for="fund_id">Fund Id:</label>
    <select name="fund_id" class="form-control">
        @foreach($api['fundMap'] as $value => $label)
            <option value="{{ $value }}" {{ $portfolio?->fund_id == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<!-- Source Field -->
<div class="form-group col-sm-6">
    <label for="source">Source:</label>
    <input type="text" name="source" value="{{ $portfolio?->source }}" class="form-control" maxlength="30">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ redirect()->back()->getTargetUrl() }}" class="btn btn-secondary">Cancel</a>
</div>
