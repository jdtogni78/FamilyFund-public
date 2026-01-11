<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Fund Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="fund_id" class="form-label">
            <i class="fa fa-landmark me-1"></i> Fund <span class="text-danger">*</span>
        </label>
        <select name="fund_id" id="fund_id" class="form-control form-select" required>
            <option value="">-- Select Fund --</option>
            @foreach($api['fundMap'] as $id => $name)
                <option value="{{ $id }}" {{ (old('fund_id', $tradeBandReport->fund_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Fund to generate trade band report for</small>
    </div>

    <!-- As Of Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="as_of" class="form-label">
            <i class="fa fa-calendar me-1"></i> As Of Date
        </label>
        <div class="input-group">
            <input type="text" name="as_of" id="as_of" class="form-control"
                   value="{{ old('as_of', isset($tradeBandReport) && $tradeBandReport->as_of ? $tradeBandReport->as_of->format('Y-m-d') : '') }}">
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
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });

    // Template button functionality
    function updateTemplateState() {
        const isTemplate = $('#as_of').val() === '';
        const $btn = $('button[type="submit"]');
        if (isTemplate) {
            $btn.html('<i class="fa fa-save me-1"></i> Save Template');
            $('#makeTemplate').removeClass('btn-outline-secondary').addClass('btn-info');
        } else {
            $btn.html('<i class="fa fa-save me-1"></i> Save');
            $('#makeTemplate').removeClass('btn-info').addClass('btn-outline-secondary');
        }
    }
    $('#makeTemplate').click(function() {
        $('#as_of').val('');
        updateTemplateState();
    });
    $('#as_of').on('change dp.change', updateTemplateState);
    updateTemplateState();
</script>
@endpush

<div class="row">
    <!-- Scheduled Job Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="scheduled_job_id" class="form-label">
            <i class="fa fa-clock me-1"></i> Scheduled Job ID
        </label>
        <input type="number" name="scheduled_job_id" id="scheduled_job_id" class="form-control"
               value="{{ old('scheduled_job_id', $tradeBandReport->scheduled_job_id ?? '') }}">
        <small class="text-body-secondary">Optional link to a scheduled job</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('tradeBandReports.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
