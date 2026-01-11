<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Object Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="object" class="form-label">
            <i class="fa fa-cube me-1"></i> Object <span class="text-danger">*</span>
        </label>
        <input type="text" name="object" id="object" class="form-control" maxlength="50"
               value="{{ $changeLog->object ?? old('object') }}" required>
        <small class="text-body-secondary">Object type that was changed (e.g., Account, Fund)</small>
    </div>

    <div class="col-md-6"></div>
</div>

<div class="row">
    <!-- Content Field -->
    <div class="form-group col-md-12 mb-3">
        <label for="content" class="form-label">
            <i class="fa fa-file-alt me-1"></i> Content <span class="text-danger">*</span>
        </label>
        <textarea name="content" id="content" class="form-control" rows="6" required>{{ $changeLog->content ?? old('content') }}</textarea>
        <small class="text-body-secondary">JSON or text content describing the change details</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('changeLogs.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
