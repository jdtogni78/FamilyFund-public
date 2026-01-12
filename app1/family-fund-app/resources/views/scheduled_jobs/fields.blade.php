@php
    use App\Models\ScheduledJobExt;
@endphp

<style>
    .form-select, .form-control {
        font-size: 0.8rem;
    }
    .form-label {
        font-size: 0.85rem;
    }
    .text-body-secondary {
        font-size: 0.75rem;
    }
</style>

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
    <!-- Entity Selection - Fund Report Template (shown when entity_descr is fund_report) -->
    <div class="form-group col-md-6 mb-3" id="fund_report_template_group">
        <label for="fund_report_template_id" class="form-label">
            <i class="fa fa-file-alt me-1"></i> Report Template <span class="text-danger">*</span>
        </label>
        <select name="fund_report_template_id" id="fund_report_template_id" class="form-control form-select">
            <option value="">-- Select Report Template --</option>
            @foreach($fundReportTemplates ?? [] as $id => $name)
                <option value="{{ $id }}" {{ (isset($scheduledJob) && $scheduledJob->entity_id == $id && $scheduledJob->entity_descr == 'fund_report') ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">Select a fund report template to use for scheduled generation</small>
    </div>

    <!-- Entity Selection - Trade Band Report Template (shown when entity_descr is trade_band_report) -->
    <div class="form-group col-md-6 mb-3" id="trade_band_template_group" style="display: none;">
        <label for="trade_band_template_id" class="form-label">
            <i class="fa fa-chart-line me-1"></i> Trade Band Template <span class="text-danger">*</span>
        </label>
        <select name="trade_band_template_id" id="trade_band_template_id" class="form-control form-select">
            <option value="">-- Select Template --</option>
            @foreach($tradeBandReportTemplates ?? [] as $id => $name)
                <option value="{{ $id }}" {{ (isset($scheduledJob) && $scheduledJob->entity_id == $id && $scheduledJob->entity_descr == 'trade_band_report') ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">Select a trade band report template</small>
    </div>

    <!-- Entity Selection - Transaction Template (shown when entity_descr is transaction) -->
    <div class="form-group col-md-6 mb-3" id="transaction_template_group" style="display: none;">
        <label for="transaction_template_id" class="form-label">
            <i class="fa fa-exchange-alt me-1"></i> Transaction Template <span class="text-danger">*</span>
        </label>
        <select name="transaction_template_id" id="transaction_template_id" class="form-control form-select">
            <option value="">-- Select Transaction --</option>
            @foreach($transactionTemplates ?? [] as $id => $name)
                <option value="{{ $id }}" {{ (isset($scheduledJob) && $scheduledJob->entity_id == $id && $scheduledJob->entity_descr == 'transaction') ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
        <small class="text-body-secondary">Select a transaction to duplicate on schedule</small>
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
        <div class="input-group">
            <input type="date" name="end_dt" id="end_dt" class="form-control"
                   value="{{ isset($scheduledJob) ? $scheduledJob->end_dt->format('Y-m-d') : now()->addYears(10)->format('Y-m-d') }}" required>
            <button type="button" class="btn btn-outline-secondary" id="set_never_end" title="Set to never expire">
                <i class="fa fa-infinity"></i> Never
            </button>
        </div>
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
        $('#fund_report_template_group').hide();
        $('#trade_band_template_group').hide();
        $('#transaction_template_group').hide();

        // Show the appropriate one and update entity_id
        if (entityType === 'fund_report') {
            $('#fund_report_template_group').show();
            $('#entity_id').val($('#fund_report_template_id').val());
        } else if (entityType === 'trade_band_report') {
            $('#trade_band_template_group').show();
            $('#entity_id').val($('#trade_band_template_id').val());
        } else if (entityType === 'transaction') {
            $('#transaction_template_group').show();
            $('#entity_id').val($('#transaction_template_id').val());
        }
    }

    // Update entity_id when template changes
    $('#fund_report_template_id').on('change', function() {
        $('#entity_id').val($(this).val());
    });

    $('#trade_band_template_id').on('change', function() {
        $('#entity_id').val($(this).val());
    });

    $('#transaction_template_id').on('change', function() {
        $('#entity_id').val($(this).val());
    });

    // Show/hide entity fields based on type
    $('#entity_descr').on('change', updateEntityFields);

    // Initialize on page load
    updateEntityFields();

    // Set end date to "never" (9999-12-31)
    $('#set_never_end').on('click', function() {
        $('#end_dt').val('9999-12-31');
    });
});
</script>
@endpush
