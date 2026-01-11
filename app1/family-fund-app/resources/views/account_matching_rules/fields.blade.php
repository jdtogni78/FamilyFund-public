<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_id', $accountMatchingRule->account_id ?? null);
@endphp
@include('partials.fund_account_selector', [
    'selectedAccounts' => $selectedAccounts ? [$selectedAccounts] : [],
    'multiple' => false,
    'fieldName' => 'account_id'
])

<!-- Matching Rule Id Field -->
<div class="form-group col-sm-6">
    <label for="matching_rule_id">Matching Rule:</label>
    <select name="matching_rule_id" class="form-control">
        @foreach($api['mr'] as $value => $label)
            <option value="{{ $value }}" {{ old('matching_rule_id', $accountMatchingRule->matching_rule_id ?? null) == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
