<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="date" class="form-label">
            <i class="fa fa-calendar me-1"></i> Date <span class="text-danger">*</span>
        </label>
        <input type="text" name="date" id="date" class="form-control"
               value="{{ old('date', isset($depositRequest->date) ? \Carbon\Carbon::parse($depositRequest->date)->format('Y-m-d') : '') }}" required>
        <small class="text-body-secondary">Date of the deposit request (YYYY-MM-DD)</small>
    </div>

    <!-- Status Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="status" class="form-label">
            <i class="fa fa-info-circle me-1"></i> Status <span class="text-danger">*</span>
        </label>
        <select name="status" id="status" class="form-control form-select" required>
            @foreach($api['statusMap'] ?? ['P' => 'Pending', 'A' => 'Approved', 'R' => 'Rejected'] as $value => $label)
                <option value="{{ $value }}" {{ old('status', $depositRequest->status ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">P=Pending, A=Approved, R=Rejected</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#date').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
</script>
@endpush

<div class="row">
    <!-- Amount Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="amount" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Amount <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="amount" id="amount" class="form-control" step="0.01"
                   value="{{ old('amount', $depositRequest->amount ?? '') }}" required>
        </div>
        <small class="text-body-secondary">Requested deposit amount</small>
    </div>

    <!-- Description Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="description" class="form-label">
            <i class="fa fa-comment me-1"></i> Description
        </label>
        <input type="text" name="description" id="description" class="form-control"
               value="{{ old('description', $depositRequest->description ?? '') }}">
        <small class="text-body-secondary">Optional description of the request</small>
    </div>
</div>

<hr class="my-3">

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_id', $depositRequest->account_id ?? null);
@endphp
@include('partials.fund_account_selector', [
    'selectedAccounts' => $selectedAccounts ? [$selectedAccounts] : [],
    'multiple' => false,
    'fieldName' => 'account_id'
])

@if ($isEdit ?? false)
<hr class="my-3">

<div class="row">
    <!-- Cash Deposit Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="cash_deposit_id" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Cash Deposit ID
        </label>
        <input type="text" name="cash_deposit_id" id="cash_deposit_id" class="form-control"
               value="{{ old('cash_deposit_id', $depositRequest->cash_deposit_id ?? '') }}">
        <small class="text-body-secondary">Linked cash deposit (if processed)</small>
    </div>

    <!-- Transaction Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="transaction_id" class="form-label">
            <i class="fa fa-exchange-alt me-1"></i> Transaction ID
        </label>
        <input type="text" name="transaction_id" id="transaction_id" class="form-control"
               value="{{ old('transaction_id', $depositRequest->transaction_id ?? '') }}">
        <small class="text-body-secondary">Linked transaction (if processed)</small>
    </div>
</div>
@endif

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('depositRequests.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
