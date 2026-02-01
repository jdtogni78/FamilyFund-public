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
                <option value="{{ $value }}" {{ (isset($idDocument) && $idDocument->person_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Person this document belongs to</small>
    </div>

    <!-- Number Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="number" class="form-label">
            <i class="fa fa-id-card me-1"></i> Document Number <span class="text-danger">*</span>
        </label>
        <input type="text" name="number" id="number" class="form-control" maxlength="50"
               value="{{ $idDocument->number ?? old('number') }}" required>
        <small class="text-body-secondary">ID document number (SSN, passport, etc.)</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('id_documents.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
