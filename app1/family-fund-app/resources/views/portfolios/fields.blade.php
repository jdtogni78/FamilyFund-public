<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Fund Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="fund_id" class="form-label">
            <i class="fa fa-landmark me-1"></i> Fund <span class="text-danger">*</span>
        </label>
        <select name="fund_id" id="fund_id" class="form-control form-select" required>
            @foreach($api['fundMap'] as $value => $label)
                <option value="{{ $value }}" {{ ($portfolio->fund_id ?? old('fund_id')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Fund this portfolio belongs to</small>
    </div>

    <!-- Source Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="source" class="form-label">
            <i class="fa fa-database me-1"></i> Source <span class="text-danger">*</span>
        </label>
        <input type="text" name="source" id="source" class="form-control" maxlength="30"
               value="{{ $portfolio->source ?? old('source') }}" required>
        <small class="text-body-secondary">Identifier for the portfolio source (max 30 characters)</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ redirect()->back()->getTargetUrl() }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
