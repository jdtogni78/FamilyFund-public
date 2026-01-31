<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_id', $accountReport->account_id ?? null);
@endphp
@include('partials.fund_account_selector', [
    'selectedAccounts' => $selectedAccounts ? [$selectedAccounts] : [],
    'multiple' => false,
    'fieldName' => 'account_id'
])

<hr class="my-3">

<div class="row">
    <!-- Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="type" class="form-label">
            <i class="fa fa-file-alt me-1"></i> Report Type <span class="text-danger">*</span>
        </label>
        <select name="type" id="type" class="form-control form-select" required>
            @foreach($api['typeMap'] as $label => $value)
                <option value="{{ $value }}" {{ (isset($accountReport) ? $accountReport->type : 'ALL') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Type of report to generate</small>
    </div>

    <!-- As Of Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="as_of" class="form-label">
            <i class="fa fa-calendar me-1"></i> Report Date
        </label>
        <div class="input-group">
            <input type="text" name="as_of" id="as_of" class="form-control"
                   value="{{ isset($accountReport) ? $accountReport->as_of->format('Y-m-d') : '' }}">
            <button type="button" class="btn btn-outline-secondary" id="makeTemplate" title="Make this a template for scheduling">
                <i class="fa fa-calendar-alt me-1"></i> Template
            </button>
        </div>
        <small class="text-body-secondary">Leave empty or click "Template" to create a scheduling template</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#as_of').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        maxDate: moment(),
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        }
    });

    function updateSubmitButton() {
        const isTemplate = $('#as_of').val() === '9999-12-31';
        const $btn = $('#submitBtn');
        if (isTemplate) {
            $btn.html('<i class="fa fa-save me-1"></i> Save Template');
            $('#makeTemplate').removeClass('btn-outline-secondary').addClass('btn-info');
        } else {
            $btn.html('<i class="fa fa-paper-plane me-1"></i> Generate & Send Report');
            $('#makeTemplate').removeClass('btn-info').addClass('btn-outline-secondary');
        }
    }

    $('#makeTemplate').click(function() {
        $('#as_of').val('9999-12-31');
        updateSubmitButton();
    });

    $('#as_of').on('dp.change', updateSubmitButton);
    updateSubmitButton();
</script>
@endpush

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="fa fa-paper-plane me-1"></i> Generate & Send Report
    </button>
    <a href="{{ route('accountReports.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
