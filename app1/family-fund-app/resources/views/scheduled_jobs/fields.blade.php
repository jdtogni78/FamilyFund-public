@php
    use App\Models\ScheduledJobExt;
@endphp

<div class="row">
    <!-- Schedule Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="schedule_id" class="form-label">
            <i class="fa fa-calendar-alt me-1"></i> Schedule <span class="text-danger">*</span>
        </label>
        <select name="schedule_id" id="schedule_id" class="form-control form-select" required>
            <option value="">-- Select Schedule --</option>
            @foreach($schedules ?? [] as $id => $descr)
                <option value="{{ $id }}" {{ (isset($scheduledJob) && $scheduledJob->schedule_id == $id) ? 'selected' : '' }}>
                    {{ $descr }}
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">Choose when this job should run (e.g., Day of Month, Day of Quarter)</small>
    </div>

    <!-- Entity Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="entity_descr" class="form-label">
            <i class="fa fa-tag me-1"></i> Entity Type <span class="text-danger">*</span>
        </label>
        <select name="entity_descr" id="entity_descr" class="form-control form-select" required>
            <option value="">-- Select Entity Type --</option>
            @foreach($entityTypes ?? ScheduledJobExt::$entityMap as $key => $label)
                <option value="{{ $key }}" {{ (isset($scheduledJob) && $scheduledJob->entity_descr == $key) ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">What type of report/action should be generated</small>
    </div>
</div>

<div class="row">
    <!-- Entity Selection - Fund (shown when entity_descr is fund_report) -->
    <div class="form-group col-md-6 mb-3" id="fund_select_group">
        <label for="fund_id" class="form-label">
            <i class="fa fa-landmark me-1"></i> Fund <span class="text-danger">*</span>
        </label>
        <select name="fund_id" id="fund_id" class="form-control form-select">
            <option value="">-- Select Fund --</option>
            @foreach($funds ?? [] as $id => $name)
                <option value="{{ $id }}" {{ (isset($scheduledJob) && $scheduledJob->entity_id == $id && $scheduledJob->entity_descr == 'fund_report') ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Entity Selection - Portfolio (shown when entity_descr is portfolio_report) -->
    <div class="form-group col-md-6 mb-3" id="portfolio_select_group" style="display: none;">
        <label for="portfolio_id" class="form-label">
            <i class="fa fa-briefcase me-1"></i> Portfolio <span class="text-danger">*</span>
        </label>
        <select name="portfolio_id" id="portfolio_id" class="form-control form-select">
            <option value="">-- Select Portfolio --</option>
            @foreach($portfolios ?? [] as $id => $name)
                <option value="{{ $id }}" {{ (isset($scheduledJob) && $scheduledJob->entity_id == $id && $scheduledJob->entity_descr == 'portfolio_report') ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Hidden entity_id field that gets populated by JS -->
    <input type="hidden" name="entity_id" id="entity_id" value="{{ $scheduledJob->entity_id ?? '' }}">
</div>

<div class="row">
    <!-- Start Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_dt" class="form-label">
            <i class="fa fa-play me-1"></i> Start Date <span class="text-danger">*</span>
        </label>
        <input type="date" name="start_dt" id="start_dt" class="form-control"
               value="{{ isset($scheduledJob) ? $scheduledJob->start_dt->format('Y-m-d') : now()->format('Y-m-d') }}" required>
        <small class="text-body-secondary">When should this scheduled job become active</small>
    </div>

    <!-- End Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_dt" class="form-label">
            <i class="fa fa-stop me-1"></i> End Date <span class="text-danger">*</span>
        </label>
        <input type="date" name="end_dt" id="end_dt" class="form-control"
               value="{{ isset($scheduledJob) ? $scheduledJob->end_dt->format('Y-m-d') : now()->addYears(10)->format('Y-m-d') }}" required>
        <small class="text-body-secondary">When should this scheduled job expire</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('scheduledJobs.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    function updateEntityFields() {
        var entityType = $('#entity_descr').val();

        // Hide all entity selects
        $('#fund_select_group').hide();
        $('#portfolio_select_group').hide();

        // Show the appropriate one and update entity_id
        if (entityType === 'fund_report') {
            $('#fund_select_group').show();
            $('#entity_id').val($('#fund_id').val());
        } else if (entityType === 'portfolio_report') {
            $('#portfolio_select_group').show();
            $('#entity_id').val($('#portfolio_id').val());
        } else if (entityType === 'transaction') {
            // Transaction might need fund selection too
            $('#fund_select_group').show();
            $('#entity_id').val($('#fund_id').val());
        }
    }

    // Update entity_id when fund/portfolio changes
    $('#fund_id').on('change', function() {
        $('#entity_id').val($(this).val());
    });

    $('#portfolio_id').on('change', function() {
        $('#entity_id').val($(this).val());
    });

    // Show/hide entity fields based on type
    $('#entity_descr').on('change', updateEntityFields);

    // Initialize on page load
    updateEntityFields();
});
</script>
@endpush
