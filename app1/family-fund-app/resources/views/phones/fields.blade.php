<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Person Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="person_id" class="form-label">
            <i class="fa fa-user me-1"></i> Person <span class="text-danger">*</span>
        </label>
        <select name="person_id" id="person_id" class="form-control form-select" required>
            @foreach($api['personMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($phone) && $phone->person_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Person this phone belongs to</small>
    </div>

    <!-- Number Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="number" class="form-label">
            <i class="fa fa-phone me-1"></i> Number <span class="text-danger">*</span>
        </label>
        <input type="text" name="number" id="number" class="form-control" maxlength="20"
               value="{{ $phone->number ?? old('number') }}" required>
        <small class="text-body-secondary">Phone number with area code</small>
    </div>
</div>

<div class="row">
    <!-- Is Primary Field -->
    <div class="form-group col-md-6 mb-3">
        <label class="form-label">
            <i class="fa fa-star me-1"></i> Primary Phone
        </label>
        <div class="form-check mt-2">
            <input type="hidden" name="is_primary" value="0">
            <input type="checkbox" name="is_primary" id="is_primary" class="form-check-input" value="1"
                   {{ (isset($phone) && $phone->is_primary) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_primary">Set as primary contact number</label>
        </div>
        <small class="text-body-secondary">Check if this is the main phone number</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('phones.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
