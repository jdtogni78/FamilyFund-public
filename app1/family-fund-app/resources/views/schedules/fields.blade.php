<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Descr Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="descr" class="form-label">
            <i class="fa fa-align-left me-1"></i> Description <span class="text-danger">*</span>
        </label>
        <input type="text" name="descr" id="descr" class="form-control" maxlength="255"
               value="{{ $schedule->descr ?? old('descr') }}" required>
        <small class="text-body-secondary">Human-readable description of the schedule</small>
    </div>

    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type <span class="text-danger">*</span>
        </label>
        <input type="text" name="type" id="type" class="form-control" maxlength="3"
               value="{{ $schedule->type ?? old('type') }}" required>
        <small class="text-body-secondary">Schedule type code (e.g., DOM, DOQ, DOY)</small>
    </div>
</div>

<div class="row">
    <!-- Value Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="value" class="form-label">
            <i class="fa fa-hashtag me-1"></i> Value <span class="text-danger">*</span>
        </label>
        <input type="text" name="value" id="value" class="form-control" maxlength="255"
               value="{{ $schedule->value ?? old('value') }}" required>
        <small class="text-body-secondary">Schedule value (e.g., day number, cron expression)</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
