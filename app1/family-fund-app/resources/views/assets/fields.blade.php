<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="name" class="form-label">
            <i class="fa fa-coins me-1"></i> Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="name" class="form-control" maxlength="128"
               value="{{ $asset->name ?? old('name') }}" required>
        <small class="text-body-secondary">Full name of the asset (max 128 characters)</small>
    </div>

    <!-- Source Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="source" class="form-label">
            <i class="fa fa-database me-1"></i> Source <span class="text-danger">*</span>
        </label>
        <input type="text" name="source" id="source" class="form-control" maxlength="30"
               value="{{ $asset->source ?? old('source') }}" required>
        <small class="text-body-secondary">Ticker symbol or identifier (e.g., AAPL, BTC)</small>
    </div>
</div>

<div class="row">
    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type <span class="text-danger">*</span>
        </label>
        <input type="text" name="type" id="type" class="form-control" maxlength="20"
               value="{{ $asset->type ?? old('type') }}" required>
        <small class="text-body-secondary">Asset type (e.g., stock, crypto, real_estate)</small>
    </div>

    <!-- Display Group Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="display_group" class="form-label">
            <i class="fa fa-layer-group me-1"></i> Display Group
        </label>
        <input type="text" name="display_group" id="display_group" class="form-control" maxlength="50"
               value="{{ $asset->display_group ?? old('display_group') }}">
        <small class="text-body-secondary">Grouping for display purposes (e.g., Tech, Energy)</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('assets.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
