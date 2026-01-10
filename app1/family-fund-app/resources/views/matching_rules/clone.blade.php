<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{!! route('matchingRules.index') !!}">Matching Rules</a>
        </li>
        <li class="breadcrumb-item active">Clone</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-copy fa-lg"></i>
                            <strong>Clone Matching Rule: {{ $matchingRule->name }}</strong>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('matchingRules.store_clone') }}">
                                @csrf

                                <!-- Name Field -->
                                <div class="form-group col-sm-6">
                                    <label for="name">Name:</label>
                                    <input type="text" name="name" class="form-control" maxlength="50"
                                           value="{{ old('name', $matchingRule->name . ' (Copy)') }}">
                                </div>

                                <!-- Dollar Range Start Field -->
                                <div class="form-group col-sm-6">
                                    <label for="dollar_range_start">Dollar Range Start:</label>
                                    <input type="number" name="dollar_range_start" class="form-control" step="0.01"
                                           value="{{ old('dollar_range_start', $matchingRule->dollar_range_start) }}">
                                </div>

                                <!-- Dollar Range End Field -->
                                <div class="form-group col-sm-6">
                                    <label for="dollar_range_end">Dollar Range End:</label>
                                    <input type="number" name="dollar_range_end" class="form-control" step="0.01"
                                           value="{{ old('dollar_range_end', $matchingRule->dollar_range_end) }}">
                                </div>

                                <!-- Date Start Field -->
                                <div class="form-group col-sm-6">
                                    <label for="date_start">Date Start:</label>
                                    <input type="date" name="date_start" class="form-control" id="date_start"
                                           value="{{ old('date_start', $matchingRule->date_start?->format('Y-m-d')) }}">
                                </div>

                                <!-- Date End Field -->
                                <div class="form-group col-sm-6">
                                    <label for="date_end">Date End:</label>
                                    <input type="date" name="date_end" class="form-control" id="date_end"
                                           value="{{ old('date_end', $matchingRule->date_end?->format('Y-m-d')) }}">
                                </div>

                                <!-- Match Percent Field -->
                                <div class="form-group col-sm-6">
                                    <label for="match_percent">Match Percent:</label>
                                    <input type="number" name="match_percent" class="form-control" step="0.01"
                                           value="{{ old('match_percent', $matchingRule->match_percent) }}">
                                </div>

                                <hr class="col-sm-12">

                                <!-- Fund Filter -->
                                <div class="form-group col-sm-4">
                                    <label for="fund_filter">Filter by Fund:</label>
                                    <select id="fund_filter" class="form-control">
                                        <option value="">All Funds</option>
                                        @foreach($funds as $fundId => $fundName)
                                            <option value="{{ $fundId }}">{{ $fundName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Quick Actions -->
                                <div class="form-group col-sm-4">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">Select All Visible</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="selectNone">Clear Selection</button>
                                    </div>
                                </div>

                                <!-- Account Selection Field -->
                                <div class="form-group col-sm-8">
                                    <label for="account_ids">Assign to Accounts: <span id="accountCount" class="text-muted"></span></label>
                                    <select name="account_ids[]" id="account_ids" class="form-control" multiple size="12">
                                        @foreach($accounts as $account)
                                            <option value="{{ $account['id'] }}" data-fund-id="{{ $account['fund_id'] }}"
                                                {{ in_array($account['id'], old('account_ids', $assignedAccountIds)) ? 'selected' : '' }}>
                                                [{{ $account['fund_name'] }}] {{ $account['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Hold Ctrl/Cmd to select multiple accounts.
                                        Previously assigned accounts are pre-selected.
                                    </small>
                                </div>

                                @push('scripts')
                                <script>
                                $(document).ready(function() {
                                    const $fundFilter = $('#fund_filter');
                                    const $accountSelect = $('#account_ids');
                                    const $accountCount = $('#accountCount');

                                    function updateCount() {
                                        const visible = $accountSelect.find('option:not([style*="display: none"])').length;
                                        const selected = $accountSelect.find('option:selected').length;
                                        $accountCount.text('(' + selected + ' selected, ' + visible + ' visible)');
                                    }

                                    $fundFilter.on('change', function() {
                                        const fundId = $(this).val();
                                        $accountSelect.find('option').each(function() {
                                            const optionFundId = $(this).data('fund-id');
                                            if (fundId === '' || optionFundId == fundId) {
                                                $(this).show();
                                            } else {
                                                $(this).hide();
                                            }
                                        });
                                        updateCount();
                                    });

                                    $('#selectAll').on('click', function() {
                                        $accountSelect.find('option:not([style*="display: none"])').prop('selected', true);
                                        updateCount();
                                    });

                                    $('#selectNone').on('click', function() {
                                        $accountSelect.find('option').prop('selected', false);
                                        updateCount();
                                    });

                                    $accountSelect.on('change', updateCount);
                                    updateCount();
                                });
                                </script>
                                @endpush

                                <!-- Submit Field -->
                                <div class="form-group col-sm-12">
                                    <button type="submit" class="btn btn-primary">Clone & Assign</button>
                                    <a href="{{ route('matchingRules.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
