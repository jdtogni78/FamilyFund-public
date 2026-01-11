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
               value="{{ old('date', isset($cashDeposit->date) ? \Carbon\Carbon::parse($cashDeposit->date)->format('Y-m-d') : '') }}" required>
        <small class="text-body-secondary">Date of the cash deposit (YYYY-MM-DD)</small>
    </div>

    <!-- Fund Account Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="account_id" class="form-label">
            <i class="fa fa-landmark me-1"></i> Fund Account <span class="text-danger">*</span>
        </label>
        <select name="account_id" id="account_id" class="form-control form-select" required>
            @foreach($api['fundAccountMap'] as $value => $label)
                <option value="{{ $value }}" {{ old('account_id', $cashDeposit->account_id ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Fund account receiving the deposit</small>
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
            <input type="number" name="amount" id="amount" class="form-control" min="0" step="0.01"
                   value="{{ old('amount', $cashDeposit->amount ?? '') }}" required>
        </div>
        <small class="text-body-secondary">Dollar amount of the deposit</small>
    </div>

    <!-- Status Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="status" class="form-label">
            <i class="fa fa-info-circle me-1"></i> Status <span class="text-danger">*</span>
        </label>
        <select name="status" id="status" class="form-control form-select" required>
            @foreach($api['statusMap'] as $value => $label)
                <option value="{{ $value }}" {{ old('status', $cashDeposit->status ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Current status of the deposit</small>
    </div>
</div>

<div class="row">
    <!-- Description Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="description" class="form-label">
            <i class="fa fa-comment me-1"></i> Description
        </label>
        <input type="text" name="description" id="description" class="form-control"
               value="{{ old('description', $cashDeposit->description ?? '') }}">
        <small class="text-body-secondary">Optional description of the deposit</small>
    </div>

    @if ($isEdit ?? false)
    <!-- Transaction Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="transaction_id" class="form-label">
            <i class="fa fa-link me-1"></i> Transaction ID
        </label>
        <input type="text" name="transaction_id" id="transaction_id" class="form-control"
               value="{{ old('transaction_id', $cashDeposit->transaction_id ?? '') }}">
        <small class="text-body-secondary">Linked transaction (if processed)</small>
    </div>
    @else
    <div class="col-md-6"></div>
    @endif
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('cashDeposits.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
