{{--
    Reusable Index Filter Bar Component

    Required variables:
    - $filterRoute: string - route name for form action
    - $api['fundMap']: array - fund id => name map

    Optional variables:
    - $api['accountsWithFund']: array - for account filter (each with id, label, fund_id)
    - $api['matchingRuleMap']: array - for matching rule filter
    - $filters: array - current filter values (fund_id, account_id, matching_rule_id)
    - $showFund: bool - show fund filter (default: true)
    - $showAccount: bool - show account filter (default: true)
    - $showMatchingRule: bool - show matching rule filter (default: false)
    - $showDates: bool - show date range filters (default: false)
    - $clearRoute: string - route for clear button (default: same as filterRoute)
--}}

@php
    $showFund = $showFund ?? true;
    $showAccount = $showAccount ?? true;
    $showMatchingRule = $showMatchingRule ?? false;
    $showDates = $showDates ?? false;
    $clearRoute = $clearRoute ?? $filterRoute;
    $filters = $filters ?? [];
@endphp

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route($filterRoute) }}" id="filterForm" class="row g-2 align-items-end">
            @if($showFund)
            <div class="col-md-{{ $showAccount ? '3' : '4' }}">
                <label for="filter_fund_id" class="form-label small mb-1">
                    <i class="fa fa-landmark mr-1"></i> Fund
                </label>
                <select name="fund_id" id="filter_fund_id" class="form-control form-control-sm" onchange="filterAccounts(); this.form.submit();">
                    <option value="">-- All Funds --</option>
                    @foreach($api['fundMap'] ?? [] as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['fund_id'] ?? '') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showAccount && isset($api['accountsWithFund']))
            <div class="col-md-3">
                <label for="filter_account_id" class="form-label small mb-1">
                    <i class="fa fa-user mr-1"></i> Account
                </label>
                <select name="account_id" id="filter_account_id" class="form-control form-control-sm">
                    <option value="">-- All Accounts --</option>
                    @foreach($api['accountsWithFund'] ?? [] as $account)
                        <option value="{{ $account['id'] }}"
                                data-fund-id="{{ $account['fund_id'] }}"
                                {{ ($filters['account_id'] ?? '') == $account['id'] ? 'selected' : '' }}>
                            {{ $account['label'] ?? $account['nickname'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showMatchingRule && isset($api['matchingRuleMap']))
            <div class="col-md-3">
                <label for="filter_matching_rule_id" class="form-label small mb-1">
                    <i class="fa fa-gift mr-1"></i> Matching Rule
                </label>
                <select name="matching_rule_id" id="filter_matching_rule_id" class="form-control form-control-sm">
                    <option value="">-- All Rules --</option>
                    @foreach($api['matchingRuleMap'] ?? [] as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['matching_rule_id'] ?? '') == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($showDates)
            <div class="col-md-2">
                <label for="filter_start_dt" class="form-label small mb-1">
                    <i class="fa fa-calendar mr-1"></i> From
                </label>
                <input type="date" name="start_dt" id="filter_start_dt" class="form-control form-control-sm"
                       value="{{ $filters['start_dt'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label for="filter_end_dt" class="form-label small mb-1">
                    <i class="fa fa-calendar mr-1"></i> To
                </label>
                <input type="date" name="end_dt" id="filter_end_dt" class="form-control form-control-sm"
                       value="{{ $filters['end_dt'] ?? '' }}">
            </div>
            @endif

            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fa fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route($clearRoute) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times mr-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

@if($showFund && $showAccount)
@push('scripts')
<script type="text/javascript">
(function() {
    var fundFilter = document.getElementById('filter_fund_id');
    var accountSelector = document.getElementById('filter_account_id');
    if (!fundFilter || !accountSelector) return;

    var allOptions = Array.from(accountSelector.options);

    window.filterAccounts = function() {
        var selectedFundId = fundFilter.value;
        var currentValue = accountSelector.value;

        // Clear all options except the first (All Accounts)
        accountSelector.innerHTML = '';
        var defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.text = '-- All Accounts --';
        accountSelector.appendChild(defaultOption);

        // Re-add filtered options
        allOptions.forEach(function(option) {
            if (option.value === '') return; // Skip the default option
            var optionFundId = option.getAttribute('data-fund-id');
            if (!selectedFundId || optionFundId === selectedFundId) {
                var newOption = option.cloneNode(true);
                if (newOption.value === currentValue) {
                    newOption.selected = true;
                }
                accountSelector.appendChild(newOption);
            }
        });

        // If current selection is no longer valid, reset to all
        if (currentValue && !accountSelector.querySelector('option[value="' + currentValue + '"]')) {
            accountSelector.value = '';
        }
    };

    // Initial filter
    filterAccounts();
})();
</script>
@endpush
@endif
