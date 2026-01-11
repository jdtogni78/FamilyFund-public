<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Account Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="account_id" class="form-label">
            <i class="fa fa-user me-1"></i> Account ID <span class="text-danger">*</span>
        </label>
        <input type="number" name="account_id" id="account_id" class="form-control"
               value="{{ $accountBalance->account_id ?? old('account_id') }}" required>
        <small class="text-body-secondary">ID of the account</small>
    </div>

    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type <span class="text-danger">*</span>
        </label>
        <input type="text" name="type" id="type" class="form-control" maxlength="3"
               value="{{ $accountBalance->type ?? old('type') }}" required>
        <small class="text-body-secondary">Balance type code (max 3 characters)</small>
    </div>
</div>

<div class="row">
    <!-- Shares Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="shares" class="form-label">
            <i class="fa fa-cubes me-1"></i> Shares <span class="text-danger">*</span>
        </label>
        <input type="number" name="shares" id="shares" class="form-control" step="0.0001"
               value="{{ $accountBalance->shares ?? old('shares') }}" required>
        <small class="text-body-secondary">Number of shares in this balance</small>
    </div>

    <!-- Transaction Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="transaction_id" class="form-label">
            <i class="fa fa-exchange-alt me-1"></i> Transaction ID
        </label>
        <input type="number" name="transaction_id" id="transaction_id" class="form-control"
               value="{{ $accountBalance->transaction_id ?? old('transaction_id') }}">
        <small class="text-body-secondary">Linked transaction (if applicable)</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Previous Balance Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="previous_balance_id" class="form-label">
            <i class="fa fa-history me-1"></i> Previous Balance ID
        </label>
        <input type="number" name="previous_balance_id" id="previous_balance_id" class="form-control"
               value="{{ $accountBalance->previous_balance_id ?? old('previous_balance_id') }}">
        <small class="text-body-secondary">Link to previous balance record</small>
    </div>

    <div class="col-md-6"></div>
</div>

<div class="row">
    <!-- Start Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_dt" class="form-label">
            <i class="fa fa-calendar me-1"></i> Start Date <span class="text-danger">*</span>
        </label>
        <input type="text" name="start_dt" id="start_dt" class="form-control"
               value="{{ $accountBalance->start_dt ?? old('start_dt') }}" required>
        <small class="text-body-secondary">When this balance period started</small>
    </div>

    <!-- End Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_dt" class="form-label">
            <i class="fa fa-calendar-check me-1"></i> End Date
        </label>
        <input type="text" name="end_dt" id="end_dt" class="form-control"
               value="{{ $accountBalance->end_dt ?? old('end_dt') }}">
        <small class="text-body-secondary">When this balance period ended</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#start_dt').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
    $('#end_dt').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
</script>
@endpush

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('accountBalances.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
