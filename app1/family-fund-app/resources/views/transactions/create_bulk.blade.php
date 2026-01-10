<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.index') }}">Transactions</a>
        </li>
        <li class="breadcrumb-item active">Bulk Create</li>
    </ol>

    <div class="container-fluid">
        @include('coreui-templates.common.errors')
        @if (Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>{{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('transactions.preview_bulk') }}" id="bulkTransactionForm">
            @csrf

            <div class="row">
                <!-- Left Column - Account Selection & Details -->
                <div class="col-lg-8">
                    <!-- Account Selection Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fa fa-users me-2"></i>
                            <strong>Select Accounts</strong>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="fund_filter" class="form-label text-muted small text-uppercase mb-1">Fund</label>
                                    <select class="form-select" id="fund_filter">
                                        <option value="">All Funds</option>
                                        @foreach($api['fundMap'] as $value => $label)
                                            @if($value)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label for="account_ids" class="form-label text-muted small text-uppercase mb-1">
                                        Accounts
                                        <span class="badge bg-primary ms-2" id="selectedCount">0 selected</span>
                                    </label>
                                    <select name="account_ids[]" id="account_ids" class="form-select" multiple size="10" required>
                                        @foreach($api['accountsByFund'] as $fundName => $accounts)
                                            @foreach($accounts as $account)
                                                <option value="{{ $account['id'] }}"
                                                        data-fund-id="{{ $account['fund_id'] ?? '' }}"
                                                        data-fund-name="{{ $fundName }}">
                                                    {{ $account['label'] }}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fa fa-info-circle me-1"></i>
                                        Hold Ctrl/Cmd to select multiple accounts
                                    </small>
                                </div>
                            </div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="selectAll">
                                    <i class="fa fa-check-square me-1"></i>Select All Visible
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="selectNone">
                                    <i class="fa fa-square me-1"></i>Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Details Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <i class="fa fa-exchange me-2"></i>
                            <strong>Transaction Details</strong>
                            <span class="text-muted ms-2">(Applied to all selected accounts)</span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6 d-flex flex-column">
                                    <label for="type" class="form-label text-muted small text-uppercase mb-1">Transaction Type</label>
                                    <select name="type" class="form-select" id="type" required>
                                        @foreach($api['typeMap'] as $value => $label)
                                            <option value="{{ $value }}" {{ $value == 'PUR' ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text small" id="type_legend">
                                        <span class="text-success"><i class="fa fa-arrow-right"></i> Cash to Fund, Shares to Account</span>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex flex-column">
                                    <label for="value" class="form-label text-muted small text-uppercase mb-1">Amount (per account)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="value" class="form-control" step="0.01"
                                               id="value" placeholder="0.00" required>
                                    </div>
                                    <div class="form-text small">
                                        <span class="text-success">+ Deposit</span> | <span class="text-danger">- Withdrawal</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4 d-flex flex-column">
                                    <label for="timestamp" class="form-label text-muted small text-uppercase mb-1">Transaction Date</label>
                                    <input type="date" name="timestamp" class="form-control" id="timestamp" required>
                                </div>
                                <div class="col-md-4 d-flex flex-column">
                                    <label for="status" class="form-label text-muted small text-uppercase mb-1">Status</label>
                                    <select name="status" class="form-select" id="status">
                                        @foreach($api['statusMap'] as $value => $label)
                                            <option value="{{ $value }}" {{ $value == 'P' ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex flex-column">
                                    <label for="flags" class="form-label text-muted small text-uppercase mb-1">Flags</label>
                                    <select name="flags" class="form-select" id="flags">
                                        @foreach($api['flagsMap'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <label for="descr" class="form-label text-muted small text-uppercase mb-1">Description</label>
                                    <input type="text" name="descr" class="form-control" id="descr"
                                           maxlength="255" placeholder="Optional note...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary -->
                <div class="col-lg-4">
                    <!-- Summary Card -->
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <i class="fa fa-calculator me-2"></i>
                            <strong>Summary</strong>
                        </div>
                        <div class="card-body text-center">
                            <div class="row mb-3">
                                <div class="col-6 border-end">
                                    <div class="text-muted small text-uppercase">Accounts</div>
                                    <div class="fs-2 fw-bold text-primary" id="summaryAccounts">0</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small text-uppercase">Per Account</div>
                                    <div class="fs-4 fw-bold" id="summaryPerAccount">$0.00</div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-muted small text-uppercase">Total Amount</div>
                            <div class="fs-2 fw-bold text-success" id="summaryTotal">$0.00</div>
                        </div>
                    </div>

                    <!-- Submit Card -->
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="previewBtn" disabled>
                                    <i class="fa fa-eye me-2"></i>Preview Transactions
                                </button>
                                <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Store all account options for filtering
        var allAccountOptions = [];
        $('#account_ids option').each(function() {
            allAccountOptions.push({
                value: $(this).val(),
                text: $(this).text(),
                fundId: $(this).data('fund-id'),
                fundName: $(this).data('fund-name')
            });
        });

        function filterAccountsByFund() {
            var selectedFund = $('#fund_filter').val();
            var $accountSelect = $('#account_ids');
            var currentSelections = $accountSelect.val() || [];

            // Clear and rebuild options
            $accountSelect.empty();

            allAccountOptions.forEach(function(opt) {
                // Show all if no fund selected, or options matching fund filter
                if (!selectedFund || opt.fundId == selectedFund) {
                    var $option = $('<option>')
                        .val(opt.value)
                        .text(opt.text)
                        .data('fund-id', opt.fundId)
                        .data('fund-name', opt.fundName);

                    // Keep selections if they match filter
                    if (currentSelections.includes(opt.value)) {
                        $option.prop('selected', true);
                    }

                    $accountSelect.append($option);
                }
            });

            updateSummary();
        }

        function updateSummary() {
            var count = $('#account_ids').val()?.length || 0;
            var value = parseFloat($('#value').val()) || 0;
            var total = count * value;

            $('#selectedCount').text(count + ' selected');
            $('#summaryAccounts').text(count);
            $('#summaryPerAccount').text('$' + value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#summaryTotal').text('$' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            // Enable/disable preview button
            $('#previewBtn').prop('disabled', count === 0 || value === 0);
        }

        // Fund filter change
        $('#fund_filter').change(filterAccountsByFund);

        // Account selection change
        $('#account_ids').change(updateSummary);

        // Value input change
        $('#value').on('input', updateSummary);

        // Select All visible
        $('#selectAll').click(function() {
            $('#account_ids option').prop('selected', true);
            updateSummary();
        });

        // Clear selection
        $('#selectNone').click(function() {
            $('#account_ids option').prop('selected', false);
            updateSummary();
        });

        // Type change - update legend
        $('#type').change(function() {
            var type = $(this).val();
            var legends = {
                'PUR': '<span class="text-success"><i class="fa fa-arrow-right"></i> Cash to Fund, Shares to Account</span>',
                'SAL': '<span class="text-danger"><i class="fa fa-arrow-left"></i> Shares from Account, Cash from Fund</span>',
                'DIV': '<span class="text-info"><i class="fa fa-gift"></i> Dividend distribution</span>',
                'INI': '<span class="text-primary"><i class="fa fa-play"></i> Initial deposit</span>',
                'MAT': '<span class="text-purple" style="color: #9333ea;"><i class="fa fa-handshake"></i> Matching contribution</span>'
            };
            $('#type_legend').html(legends[type] || '');
        });
    });
</script>
@endpush

</x-app-layout>
