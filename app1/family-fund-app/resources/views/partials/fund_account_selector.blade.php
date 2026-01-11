{{-- Fund-filtered Account Selector Component --}}
{{-- Required variables: $api['fundMap'], $api['accountsWithFund'] --}}
{{-- Optional: $selectedAccounts (array), $multiple (bool, default true), $fieldName, $selectedFund --}}
@php
    $multiple = $multiple ?? true;
    $fieldName = $fieldName ?? ($multiple ? 'account_ids[]' : 'account_id');
    $selectedAccounts = $selectedAccounts ?? [];
    $selectedFund = $selectedFund ?? '';
@endphp

<div class="row">
    <!-- Fund Filter -->
    <div class="form-group col-md-6 mb-3">
        <label for="fund_filter" class="form-label">
            <i class="fa fa-filter me-1"></i> Filter by Fund
        </label>
        <select id="fund_filter" class="form-control form-select">
            <option value="">-- All Funds --</option>
            @foreach($api['fundMap'] as $fundId => $fundName)
                <option value="{{ $fundId }}" {{ $selectedFund == $fundId ? 'selected' : '' }}>{{ $fundName }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Filter the account list by fund</small>
    </div>

    <!-- Accounts Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="account_selector" class="form-label">
            <i class="fa fa-users me-1"></i> {{ $multiple ? 'Accounts' : 'Account' }} {{ $multiple ? '' : '<span class="text-danger">*</span>' }}
        </label>
        <select name="{{ $fieldName }}" class="form-control form-select" id="account_selector"
                {{ $multiple ? 'multiple="multiple"' : '' }}
                style="min-height: {{ $multiple ? '150px' : 'auto' }};">
            @foreach($api['accountsWithFund'] as $account)
                <option value="{{ $account['id'] }}"
                        data-fund-id="{{ $account['fund_id'] }}"
                        {{ in_array($account['id'], (array)$selectedAccounts) ? 'selected' : '' }}>
                    {{ $account['label'] ?? $account['nickname'] }}
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">{{ $multiple ? 'Hold Ctrl/Cmd to select multiple accounts' : 'Select the account' }}</small>
    </div>
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
