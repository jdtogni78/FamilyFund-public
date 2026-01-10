<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('cashDeposits.index') }}">Cash Deposits</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('cashDeposits.show', $cashDeposit->id) }}">Deposit #{{ $cashDeposit->id }}</a>
        </li>
        <li class="breadcrumb-item active">Assign</li>
    </ol>

    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            @include('layouts.flash-messages')

            @php
                $currentlyAssigned = $cashDeposit->depositRequests->sum('amount');
                $unassigned = $cashDeposit->amount - $currentlyAssigned;
                $assignedPercent = $cashDeposit->amount > 0 ? ($currentlyAssigned / $cashDeposit->amount) * 100 : 0;
            @endphp

            <!-- Header Card -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="fa fa-user-plus me-2"></i>
                    <strong>Assign Cash Deposit</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Amount Display -->
                        <div class="col-md-3 text-center border-end">
                            <div class="text-muted small text-uppercase mb-1">Total Amount</div>
                            <div class="fs-2 fw-bold text-success">${{ number_format($cashDeposit->amount, 2) }}</div>
                            <div class="text-muted small">{{ $cashDeposit->date ? $cashDeposit->date->format('M j, Y') : '' }}</div>
                        </div>

                        <!-- Account Info -->
                        <div class="col-md-3 text-center border-end">
                            <div class="text-muted small text-uppercase mb-1">Source Account</div>
                            <div class="fs-5 fw-bold">{{ $cashDeposit->account->nickname ?? 'N/A' }}</div>
                            @if($cashDeposit->account?->fund)
                                <span class="badge bg-secondary">{{ $cashDeposit->account->fund->name }}</span>
                            @endif
                        </div>

                        <!-- Currently Assigned -->
                        <div class="col-md-3 text-center border-end">
                            <div class="text-muted small text-uppercase mb-1">Assigned</div>
                            <div class="fs-3 fw-bold text-info">${{ number_format($currentlyAssigned, 2) }}</div>
                            <div class="text-muted small">{{ $cashDeposit->depositRequests->count() }} request(s)</div>
                        </div>

                        <!-- Unassigned -->
                        <div class="col-md-3 text-center">
                            <div class="text-muted small text-uppercase mb-1">Unassigned</div>
                            <div class="fs-3 fw-bold text-{{ $unassigned > 0 ? 'warning' : 'success' }}" id="header-unassigned">
                                ${{ number_format($unassigned, 2) }}
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: {{ $assignedPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Deposit Requests -->
            @if($cashDeposit->depositRequests->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="fa fa-list me-2"></i>
                    <strong>Existing Assignments</strong>
                    <span class="badge bg-info ms-2">{{ $cashDeposit->depositRequests->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Account</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cashDeposit->depositRequests as $dr)
                            <tr>
                                <td>
                                    <strong>{{ $dr->account->nickname ?? 'N/A' }}</strong>
                                    @if($dr->description)
                                        <br><small class="text-muted">{{ $dr->description }}</small>
                                    @endif
                                </td>
                                <td class="text-end text-success fw-bold">${{ number_format($dr->amount, 2) }}</td>
                                <td><span class="badge bg-{{ $dr->status == 'COMPLETED' ? 'success' : 'info' }}">{{ $dr->status_string() }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td class="fw-bold">Total Assigned</td>
                                <td class="text-end fw-bold text-success">${{ number_format($currentlyAssigned, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            <!-- Add New Assignments Form -->
            <form action="{{ route('cashDeposits.do_assign', $cashDeposit->id) }}" method="POST" id="assign-form">
                @csrf

                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa fa-plus-circle me-2"></i>
                            <strong>Add New Assignments</strong>
                        </div>
                        <div>
                            <span class="badge bg-light text-success fs-6" id="new-total">$0.00</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Pending Deposit Requests (if any) -->
                        @if(isset($api['depositRequests']) && count($api['depositRequests']) > 0)
                        <div class="mb-4">
                            <h6 class="text-muted text-uppercase small mb-3">
                                <i class="fa fa-clock me-1"></i> Pending Requests to Include
                            </h6>
                            <div class="row">
                                @foreach($api['depositRequests'] as $pendingDr)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check border rounded p-3 bg-light">
                                        <input class="form-check-input pending-checkbox" type="checkbox"
                                               name="deposit_ids[]" value="{{ $pendingDr->id }}"
                                               id="pending_{{ $pendingDr->id }}"
                                               data-amount="{{ $pendingDr->amount }}">
                                        <label class="form-check-label w-100" for="pending_{{ $pendingDr->id }}">
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $api['accountMap'][$pendingDr->account_id] ?? 'Unknown' }}</strong>
                                                <span class="text-success">${{ number_format($pendingDr->amount, 2) }}</span>
                                            </div>
                                            @if($pendingDr->description)
                                                <small class="text-muted">{{ $pendingDr->description }}</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <hr>
                        @endif

                        <!-- New Deposit Requests -->
                        <h6 class="text-muted text-uppercase small mb-3">
                            <i class="fa fa-plus me-1"></i> Create New Assignments
                        </h6>

                        <table class="table" id="new-requests-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%">Account</th>
                                    <th style="width: 25%">Amount</th>
                                    <th style="width: 30%">Description</th>
                                    <th style="width: 10%"></th>
                                </tr>
                            </thead>
                            <tbody id="requests-body">
                                <!-- Template row (hidden) -->
                                <tr id="row-template" style="display: none;">
                                    <td>
                                        <select name="_template[account_id]" class="form-select">
                                            <option value="">Select Account</option>
                                            @foreach($api['accountMap'] as $accountId => $accountName)
                                                @if($accountId)
                                                <option value="{{ $accountId }}">{{ $accountName }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="_template[amount]" class="form-control amount-input" step="0.01" placeholder="0.00">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="_template[description]" class="form-control" placeholder="Optional">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-row">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <button type="button" class="btn btn-outline-success" id="add-row-btn">
                            <i class="fa fa-plus me-1"></i> Add Row
                        </button>

                        <!-- Unassigned Amount (keep remainder) -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label mb-0">
                                        <i class="fa fa-piggy-bank me-1"></i>
                                        Keep Unassigned (remainder)
                                    </label>
                                    <small class="text-muted d-block">Amount to leave unallocated</small>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="unassigned" id="unassigned-input"
                                               class="form-control" step="0.01" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary & Actions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex gap-4">
                                    <div>
                                        <span class="text-muted">Available:</span>
                                        <span class="fw-bold text-success">${{ number_format($unassigned, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted">New Assignments:</span>
                                        <span class="fw-bold text-primary" id="summary-new">$0.00</span>
                                    </div>
                                    <div>
                                        <span class="text-muted">Remaining:</span>
                                        <span class="fw-bold" id="summary-remaining">${{ number_format($unassigned, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('cashDeposits.show', $cashDeposit->id) }}" class="btn btn-outline-secondary me-2">
                                    <i class="fa fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-success" id="submit-btn">
                                    <i class="fa fa-check me-1"></i>Assign
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    var availableAmount = {{ $unassigned }};
    var rowIndex = 0;

    // Add new row
    $('#add-row-btn').click(function() {
        var template = $('#row-template').clone();
        template.removeAttr('id').show();
        template.attr('data-row-index', rowIndex);

        // Update field names
        template.find('select, input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('_template', 'deposits[' + rowIndex + ']'));
            }
        });

        $('#requests-body').append(template);
        rowIndex++;
        updateTotals();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        updateTotals();
    });

    // Update totals on any change
    $(document).on('input change', '.amount-input, .pending-checkbox, #unassigned-input', updateTotals);

    function updateTotals() {
        var newTotal = 0;

        // Sum new row amounts
        $('#requests-body tr:visible').each(function() {
            var amount = parseFloat($(this).find('.amount-input').val()) || 0;
            newTotal += amount;
        });

        // Sum checked pending requests
        $('.pending-checkbox:checked').each(function() {
            newTotal += parseFloat($(this).data('amount')) || 0;
        });

        // Add unassigned amount
        var keepUnassigned = parseFloat($('#unassigned-input').val()) || 0;

        var remaining = availableAmount - newTotal - keepUnassigned;

        // Update displays
        $('#new-total').text('$' + newTotal.toFixed(2));
        $('#summary-new').text('$' + newTotal.toFixed(2));
        $('#summary-remaining').text('$' + remaining.toFixed(2));

        // Color the remaining based on value
        if (remaining < 0) {
            $('#summary-remaining').removeClass('text-success text-warning').addClass('text-danger');
        } else if (remaining > 0) {
            $('#summary-remaining').removeClass('text-success text-danger').addClass('text-warning');
        } else {
            $('#summary-remaining').removeClass('text-warning text-danger').addClass('text-success');
        }

        // Disable submit if over-allocated
        $('#submit-btn').prop('disabled', remaining < 0);
    }

    // Add first row automatically if no pending requests
    @if(!isset($api['depositRequests']) || count($api['depositRequests']) == 0)
    $('#add-row-btn').click();
    @endif
});
</script>
@endpush

</x-app-layout>
