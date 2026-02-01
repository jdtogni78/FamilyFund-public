<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Fund(s) Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="fund_ids" class="form-label">
            <i class="fa fa-landmark me-1"></i> Fund(s) <span class="text-danger">*</span>
        </label>
        @php
            $selectedFundIds = old('fund_ids', isset($portfolio) ? $portfolio->funds->pluck('id')->toArray() : []);
        @endphp
        <select name="fund_ids[]" id="fund_ids" class="form-control form-select" multiple required>
            @foreach($api['fundMap'] as $value => $label)
                <option value="{{ $value }}" {{ in_array($value, $selectedFundIds) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Hold Ctrl/Cmd to select multiple funds</small>
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

@php
    $typeLabels = \App\Models\PortfolioExt::TYPE_LABELS;
    $categoryLabels = \App\Models\PortfolioExt::CATEGORY_LABELS;
@endphp

<div class="row">
    <!-- Display Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="display_name" class="form-label">
            <i class="fa fa-tag me-1"></i> Display Name
        </label>
        <input type="text" name="display_name" id="display_name" class="form-control" maxlength="100"
               value="{{ $portfolio->display_name ?? old('display_name') }}">
        <small class="text-body-secondary">Optional friendly name (shown instead of source if set)</small>
    </div>
</div>

<div class="row">
    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type
        </label>
        <select name="type" id="type" class="form-control form-select">
            <option value="">-- Select Type --</option>
            @foreach($typeLabels as $value => $label)
                <option value="{{ $value }}" {{ ($portfolio->type ?? old('type')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Account type (e.g., Brokerage, 401k, Real Estate)</small>
    </div>

    <!-- Category Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="category" class="form-label">
            <i class="fa fa-folder me-1"></i> Category
        </label>
        <select name="category" id="category" class="form-control form-select">
            <option value="">-- Select Category --</option>
            @foreach($categoryLabels as $value => $label)
                <option value="{{ $value }}" {{ ($portfolio->category ?? old('category')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Tax classification (Retirement, Taxable, etc.)</small>
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
