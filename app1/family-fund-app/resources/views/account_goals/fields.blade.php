<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_id', $accountGoal->account_id ?? null);
@endphp
@include('partials.fund_account_selector', [
    'selectedAccounts' => $selectedAccounts ? [$selectedAccounts] : [],
    'multiple' => false,
    'fieldName' => 'account_id'
])

<hr class="my-3">

<div class="row">
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

    <div class="col-md-6"></div>
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
