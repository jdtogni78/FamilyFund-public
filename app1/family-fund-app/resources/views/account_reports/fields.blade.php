<!-- Account Field -->
<div class="form-group col-sm-6">
    <label for="account_id">Account:</label>
    <select name="account_id" id="account_id" class="form-control custom-select" required>
        <option value="">-- Select Account --</option>
        @foreach($api['accounts'] as $id => $name)
            <option value="{{ $id }}" {{ (isset($accountReport) && $accountReport->account_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    <label for="type">Report Type:</label>
    <select name="type" id="type" class="form-control custom-select" required>
        @foreach($api['typeMap'] as $label => $value)
            <option value="{{ $value }}" {{ (isset($accountReport) ? $accountReport->type : 'ALL') == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<!-- As Of Field -->
<div class="form-group col-sm-6">
    <label for="as_of">Report Date:</label>
    <div class="input-group">
        <input type="text" name="as_of" class="form-control" id="as_of"
               value="{{ isset($accountReport) ? $accountReport->as_of->format('Y-m-d') : '' }}">
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary" id="makeTemplate" title="Make this a template for scheduling">
                <i class="fa fa-calendar-alt mr-1"></i> Template
            </button>
        </div>
    </div>
    <small class="form-text text-muted">Leave empty or click "Template" to create a scheduling template</small>
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
            $btn.html('<i class="fa fa-save mr-1"></i> Save Template');
            $('#makeTemplate').removeClass('btn-outline-secondary').addClass('btn-info');
        } else {
            $btn.html('<i class="fa fa-paper-plane mr-1"></i> Generate & Send Report');
            $('#makeTemplate').removeClass('btn-info').addClass('btn-outline-secondary');
        }
    }

    $('#makeTemplate').click(function() {
        $('#as_of').val('9999-12-31');
        updateSubmitButton();
    });

    $('#as_of').on('dp.change', updateSubmitButton);

    // Initial state
    updateSubmitButton();
</script>
@endpush

<!-- Submit Field -->
<div class="form-group col-sm-12 mt-4">
    <button type="submit" class="btn btn-primary" id="submitBtn">
        <i class="fa fa-paper-plane mr-1"></i> Generate & Send Report
    </button>
    <a href="{{ route('accountReports.index') }}" class="btn btn-secondary">Cancel</a>
</div>
