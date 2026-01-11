<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_id', $accountMatchingRule->account_id ?? null);
@endphp
@include('partials.fund_account_selector', [
    'selectedAccounts' => $selectedAccounts ? [$selectedAccounts] : [],
    'multiple' => false,
    'fieldName' => 'account_id'
])

<hr class="my-3">

<div class="row">
    <!-- Matching Rule Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="matching_rule_id" class="form-label">
            <i class="fa fa-link me-1"></i> Matching Rule <span class="text-danger">*</span>
        </label>
        <select name="matching_rule_id" id="matching_rule_id" class="form-control form-select" required>
            @foreach($api['mr'] as $value => $label)
                <option value="{{ $value }}" {{ old('matching_rule_id', $accountMatchingRule->matching_rule_id ?? null) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Matching rule to apply to this account</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
