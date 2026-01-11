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
                <option value="{{ $value }}" {{ (isset($address) && $address->person_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Person this address belongs to</small>
    </div>

    <!-- Is Primary Field -->
    <div class="form-group col-md-6 mb-3">
        <label class="form-label">
            <i class="fa fa-star me-1"></i> Primary Address
        </label>
        <div class="form-check mt-2">
            <input type="hidden" name="is_primary" value="0">
            <input type="checkbox" name="is_primary" id="is_primary" class="form-check-input" value="1"
                   {{ (isset($address) && $address->is_primary) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_primary">Set as primary address</label>
        </div>
        <small class="text-body-secondary">Check if this is the main address</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Street Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="street" class="form-label">
            <i class="fa fa-road me-1"></i> Street <span class="text-danger">*</span>
        </label>
        <input type="text" name="street" id="street" class="form-control" maxlength="255"
               value="{{ $address->street ?? old('street') }}" required>
        <small class="text-body-secondary">Street name</small>
    </div>

    <!-- Number Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="number" class="form-label">
            <i class="fa fa-hashtag me-1"></i> Number
        </label>
        <input type="text" name="number" id="number" class="form-control" maxlength="20"
               value="{{ $address->number ?? old('number') }}">
        <small class="text-body-secondary">Street number</small>
    </div>
</div>

<div class="row">
    <!-- Complement Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="complement" class="form-label">
            <i class="fa fa-building me-1"></i> Complement
        </label>
        <input type="text" name="complement" id="complement" class="form-control" maxlength="255"
               value="{{ $address->complement ?? old('complement') }}">
        <small class="text-body-secondary">Apartment, suite, unit, etc.</small>
    </div>

    <!-- County Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="county" class="form-label">
            <i class="fa fa-map me-1"></i> County
        </label>
        <input type="text" name="county" id="county" class="form-control" maxlength="255"
               value="{{ $address->county ?? old('county') }}">
        <small class="text-body-secondary">County or district</small>
    </div>
</div>

<div class="row">
    <!-- City Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="city" class="form-label">
            <i class="fa fa-city me-1"></i> City <span class="text-danger">*</span>
        </label>
        <input type="text" name="city" id="city" class="form-control" maxlength="255"
               value="{{ $address->city ?? old('city') }}" required>
        <small class="text-body-secondary">City name</small>
    </div>

    <!-- State Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="state" class="form-label">
            <i class="fa fa-flag me-1"></i> State <span class="text-danger">*</span>
        </label>
        <input type="text" name="state" id="state" class="form-control" maxlength="2"
               value="{{ $address->state ?? old('state') }}" required>
        <small class="text-body-secondary">State abbreviation (e.g., CA, NY)</small>
    </div>
</div>

<div class="row">
    <!-- Zip Code Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="zip_code" class="form-label">
            <i class="fa fa-envelope me-1"></i> Zip Code <span class="text-danger">*</span>
        </label>
        <input type="text" name="zip_code" id="zip_code" class="form-control" maxlength="10"
               value="{{ $address->zip_code ?? old('zip_code') }}" required>
        <small class="text-body-secondary">Postal/ZIP code</small>
    </div>

    <!-- Country Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="country" class="form-label">
            <i class="fa fa-globe me-1"></i> Country
        </label>
        <input type="text" name="country" id="country" class="form-control" maxlength="255"
               value="{{ $address->country ?? old('country', 'USA') }}">
        <small class="text-body-secondary">Country name (default: USA)</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('addresses.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
