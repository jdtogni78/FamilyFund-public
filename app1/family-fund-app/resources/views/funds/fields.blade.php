<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="name" class="form-label">
            <i class="fa fa-landmark me-1"></i> Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="name" class="form-control" maxlength="30"
               value="{{ $fund->name ?? old('name') }}" required>
        <small class="text-body-secondary">Short name for the fund (max 30 characters)</small>
    </div>

    <!-- Goal Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="goal" class="form-label">
            <i class="fa fa-bullseye me-1"></i> Goal
        </label>
        <input type="text" name="goal" id="goal" class="form-control" maxlength="1024"
               value="{{ $fund->goal ?? old('goal') }}">
        <small class="text-body-secondary">Description of the fund's investment goal</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('funds.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
