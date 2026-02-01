<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

@php
    $trans = $transaction ?? null;
    $defaultType = $trans->type ?? 'PUR';
    $defaultStatus = $trans->status ?? 'P';
    $defaultValue = $trans->value ?? '';
    $defaultFlags = $trans->flags ?? null;
    $defaultTimestamp = $trans->timestamp ? \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') : '';
    $defaultAccountId = $trans->account_id ?? null;
    $defaultDescr = $trans->descr ?? '';
@endphp

<div class="row">
    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-tag me-1"></i> Type <span class="text-danger">*</span>
        </label>
        <select name="type" id="type" class="form-control form-select" required>
            @foreach($api['typeMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultType == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Transaction type (PUR=Purchase, SAL=Sale, etc.)</small>
    </div>

    <!-- Status Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="status" class="form-label">
            <i class="fa fa-info-circle me-1"></i> Status <span class="text-danger">*</span>
        </label>
        <select name="status" id="status" class="form-control form-select" required>
            @foreach($api['statusMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">P=Pending, C=Completed</small>
    </div>
</div>

<div class="row">
    <!-- Account Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="account_id" class="form-label">
            <i class="fa fa-user me-1"></i> Account <span class="text-danger">*</span>
        </label>
        <select name="account_id" id="account_id" class="form-control form-select" required>
            @foreach($api['accountMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultAccountId == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Account for this transaction</small>
    </div>

    <!-- Timestamp Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="timestamp" class="form-label">
            <i class="fa fa-calendar me-1"></i> Date <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="date" name="timestamp" id="timestamp" class="form-control"
                   value="{{ $defaultTimestamp }}" required>
            <button type="button" class="btn btn-outline-secondary" id="todayBtn" title="Set to today">
                Today
            </button>
        </div>
        <small class="text-body-secondary">Transaction date</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Value Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="value" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Value <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="value" id="value" class="form-control" step="any"
                   value="{{ $defaultValue }}" required>
        </div>
        <small class="text-body-secondary">Dollar amount of the transaction</small>
    </div>

    <!-- Flags Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="flags" class="form-label">
            <i class="fa fa-flag me-1"></i> Flags
        </label>
        <select name="flags" id="flags" class="form-control form-select">
            @foreach($api['flagsMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultFlags == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Optional transaction flags</small>
    </div>
</div>

<div class="row">
    <!-- Account Balance (read-only info) -->
    <div class="form-group col-md-6 mb-3">
        <label class="form-label">
            <i class="fa fa-balance-scale me-1"></i> Account Balance
        </label>
        <div class="input-group">
            <input type="text" id="__account_balance" class="form-control" readonly style="background-color: var(--bs-tertiary-bg);">
            <span class="input-group-text" id="__account_shares_display"></span>
        </div>
        <small class="text-body-secondary">Current balance as of selected date</small>
    </div>

    <!-- CALC Share Prices -->
    <div class="form-group col-md-6 mb-3">
        <label for="__share_price" class="form-label">
            <i class="fa fa-chart-line me-1"></i> Share Price
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="text" name="__share_price" id="__share_price" class="form-control" readonly style="background-color: var(--bs-tertiary-bg);">
        </div>
        <small class="text-body-secondary">Calculated share price</small>
    </div>
</div>

<div class="row">
    <!-- CALC Shares -->
    <div class="form-group col-md-6 mb-3">
        <label for="shares" class="form-label">
            <i class="fa fa-cubes me-1"></i> Shares
        </label>
        <input type="number" name="shares" id="shares" class="form-control" readonly style="background-color: var(--bs-tertiary-bg);">
        <small class="text-body-secondary">Calculated shares (editable for INI type)</small>
    </div>

    <!-- Descr Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="descr" class="form-label">
            <i class="fa fa-comment me-1"></i> Description
        </label>
        <input type="text" name="descr" id="descr" class="form-control" maxlength="255"
               value="{{ $defaultDescr }}">
        <small class="text-body-secondary">Optional transaction description</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    api = {!! json_encode($api) !!};

    function updateShareValue() {
        if ($('#type').val() === 'INI') {
            return;
        }
        $('#__share_price').val(0);
        $('#shares').val(0);

        account = $('#account_id').find(":selected").val();
        dt = $('#timestamp').val();
        console.log('Date chosen: "' + dt + '"');

        if (dt === '') return;
        myUrl = '/api/accounts/' + account + '/share_value_as_of/' + dt;
        console.log(myUrl);

        $.ajax({
            type: 'GET',
            url: myUrl,
            data: '_token = <?php echo csrf_token() ?>',
            success: function(data) {
                share_price = data['data']['share_price'];
                account_shares = parseFloat(data['data']['account_shares']) || 0;
                account_value = parseFloat(data['data']['account_value']) || 0;
                console.log(share_price);

                $('#__share_price').val(share_price);
                $('#__account_balance').val('$' + account_value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#__account_shares_display').text(account_shares.toFixed(4) + ' shares');
                updateSharePrice();
            }
        });
    }

    $("#account_id").change(function() {
        updateShareValue();
        if (!$('#timestamp').val()) {
            $('#__account_balance').val('');
            $('#__account_shares_display').text('');
        }
    });

    function updateSharePrice() {
        value = $('#value').val();
        if ($('#type').val() === 'INI') {
            shares = $('#shares').val();
            share_price = value / shares;
            $('#__share_price').val(share_price);
        } else {
            share_price = $('#__share_price').val();
            if (share_price === 0) {
                $('#shares').val(0);
            } else {
                $('#shares').val(value / share_price);
            }
        }
    }

    $("#value").change(function() {
        updateSharePrice();
    });

    $("#shares").change(function() {
        if ($('#type').val() === 'INI') {
            updateSharePrice();
        }
    });

    $("#type").change(function() {
        value = $('#type').val();
        if (value === 'INI') {
            $('#shares').prop('readonly', false).css('background-color', '');
        } else {
            $('#shares').prop('readonly', true).css('background-color', 'var(--bs-tertiary-bg)');
        }
    });

    $('#timestamp').on('change', function() {
        updateShareValue();
    });

    $('#todayBtn').on('click', function() {
        const today = new Date().toISOString().split('T')[0];
        $('#timestamp').val(today).trigger('change');
    });

    $(document).ready(function() {
        if ($('#account_id').val() && $('#timestamp').val()) {
            updateShareValue();
        }
    });
</script>
@endpush

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-eye me-1"></i> Preview
    </button>
</div>
