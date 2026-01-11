<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Asset Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="asset_id" class="form-label">
            <i class="fa fa-coins me-1"></i> Asset <span class="text-danger">*</span>
        </label>
        <select name="asset_id" id="asset_id" class="form-control form-select" required>
            @foreach($api['assetMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($assetChangeLog) && $assetChangeLog->asset_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Asset this change log belongs to</small>
    </div>

    <!-- Action Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="action" class="form-label">
            <i class="fa fa-bolt me-1"></i> Action <span class="text-danger">*</span>
        </label>
        <input type="text" name="action" id="action" class="form-control" maxlength="255"
               value="{{ $assetChangeLog->action ?? old('action') }}" required>
        <small class="text-body-secondary">Type of change action (e.g., update, create)</small>
    </div>
</div>

<div class="row">
    <!-- Field Field -->
    <div class="form-group col-md-12 mb-3">
        <label for="field" class="form-label">
            <i class="fa fa-columns me-1"></i> Field
        </label>
        <textarea name="field" id="field" class="form-control" rows="2">{{ $assetChangeLog->field ?? old('field') }}</textarea>
        <small class="text-body-secondary">Field name that was changed</small>
    </div>
</div>

<div class="row">
    <!-- Content Field -->
    <div class="form-group col-md-12 mb-3">
        <label for="content" class="form-label">
            <i class="fa fa-file-alt me-1"></i> Content
        </label>
        <textarea name="content" id="content" class="form-control" rows="4">{{ $assetChangeLog->content ?? old('content') }}</textarea>
        <small class="text-body-secondary">Details of the change (old/new values)</small>
    </div>
</div>

<div class="row">
    <!-- Datetime Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="datetime" class="form-label">
            <i class="fa fa-clock me-1"></i> Datetime <span class="text-danger">*</span>
        </label>
        <input type="text" name="datetime" id="datetime" class="form-control"
               value="{{ $assetChangeLog->datetime ?? old('datetime') }}" required>
        <small class="text-body-secondary">When the change occurred</small>
    </div>

    <div class="col-md-6"></div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#datetime').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
</script>
@endpush

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('assetChangeLogs.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
