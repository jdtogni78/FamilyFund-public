{{-- Fund-filtered Account Selector Component --}}
{{-- Required variables: $api['fundMap'], $api['accountsWithFund'], $selectedAccounts (array), $multiple (bool, default true), $fieldName (default 'account_ids[]') --}}
@php
    $multiple = $multiple ?? true;
    $fieldName = $fieldName ?? ($multiple ? 'account_ids[]' : 'account_id');
    $selectedAccounts = $selectedAccounts ?? [];
    $selectedFund = $selectedFund ?? '';
@endphp

<!-- Fund Filter -->
<div class="form-group col-sm-6">
    <label for="fund_filter">Filter by Fund:</label>
    <select id="fund_filter" class="form-control">
        <option value="">-- All Funds --</option>
        @foreach($api['fundMap'] as $fundId => $fundName)
            <option value="{{ $fundId }}" {{ $selectedFund == $fundId ? 'selected' : '' }}>{{ $fundName }}</option>
        @endforeach
    </select>
</div>

<!-- Accounts Field -->
<div class="form-group col-sm-6">
    <label for="account_selector">{{ $multiple ? 'Accounts' : 'Account' }}:</label>
    <select name="{{ $fieldName }}" class="form-control" id="account_selector" {{ $multiple ? 'multiple="multiple"' : '' }} style="min-height: {{ $multiple ? '150px' : 'auto' }};">
        @foreach($api['accountsWithFund'] as $account)
            <option value="{{ $account['id'] }}"
                    data-fund-id="{{ $account['fund_id'] }}"
                    {{ in_array($account['id'], (array)$selectedAccounts) ? 'selected' : '' }}>
                {{ $account['label'] ?? $account['nickname'] }}
            </option>
        @endforeach
    </select>
</div>

@push('scripts')
<script type="text/javascript">
(function() {
    const fundFilter = document.getElementById('fund_filter');
    const accountSelector = document.getElementById('account_selector');
    const allOptions = Array.from(accountSelector.options);

    function filterAccounts() {
        const selectedFundId = fundFilter.value;
        const currentSelections = Array.from(accountSelector.selectedOptions).map(o => o.value);

        // Clear all options
        accountSelector.innerHTML = '';

        // Re-add filtered options
        allOptions.forEach(function(option) {
            const optionFundId = option.getAttribute('data-fund-id');
            if (!selectedFundId || optionFundId === selectedFundId) {
                const newOption = option.cloneNode(true);
                if (currentSelections.includes(newOption.value)) {
                    newOption.selected = true;
                }
                accountSelector.appendChild(newOption);
            }
        });
    }

    fundFilter.addEventListener('change', filterAccounts);

    // Initial filter if fund is pre-selected
    if (fundFilter.value) {
        filterAccounts();
    }
})();
</script>
@endpush
