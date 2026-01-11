<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Account Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="account_id" class="form-label">
            <i class="fa fa-user me-1"></i> Account <span class="text-danger">*</span>
        </label>
        <select name="account_id" id="account_id" class="form-control form-select" required>
            @foreach($api['accountMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($accountGoal) && $accountGoal->account_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Account to link with goal</small>
    </div>

    <!-- Goal Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="goal_id" class="form-label">
            <i class="fa fa-bullseye me-1"></i> Goal <span class="text-danger">*</span>
        </label>
        <select name="goal_id" id="goal_id" class="form-control form-select" required>
            @foreach($api['goalMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($accountGoal) && $accountGoal->goal_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Goal to assign to account</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('accountGoals.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
