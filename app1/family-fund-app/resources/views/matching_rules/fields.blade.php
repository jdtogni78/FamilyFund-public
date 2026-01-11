<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="name" class="form-label">
            <i class="fa fa-tag me-1"></i> Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="name" class="form-control" maxlength="50"
               value="{{ old('name', $matchingRule->name ?? '') }}" required>
        <small class="text-body-secondary">Name for this matching rule (max 50 characters)</small>
    </div>

    <!-- Match Percent Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="match_percent" class="form-label">
            <i class="fa fa-percentage me-1"></i> Match Percent <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <input type="number" name="match_percent" id="match_percent" class="form-control" step="0.01" min="0" max="100"
                   value="{{ old('match_percent', $matchingRule->match_percent ?? '') }}" required>
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Percentage of contribution to match (e.g., 50 = 50%)</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Dollar Range Start Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="dollar_range_start" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Dollar Range Start
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="dollar_range_start" id="dollar_range_start" class="form-control"
                   value="{{ old('dollar_range_start', $matchingRule->dollar_range_start ?? '') }}">
        </div>
        <small class="text-body-secondary">Minimum contribution amount to qualify</small>
    </div>

    <!-- Dollar Range End Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="dollar_range_end" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Dollar Range End
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="dollar_range_end" id="dollar_range_end" class="form-control"
                   value="{{ old('dollar_range_end', $matchingRule->dollar_range_end ?? '') }}">
        </div>
        <small class="text-body-secondary">Maximum contribution amount to match</small>
    </div>
</div>

<div class="row">
    <!-- Date Start Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="date_start" class="form-label">
            <i class="fa fa-calendar me-1"></i> Date Start
        </label>
        <input type="text" name="date_start" id="date_start" class="form-control"
               value="{{ old('date_start', isset($matchingRule->date_start) ? \Carbon\Carbon::parse($matchingRule->date_start)->format('Y-m-d') : '') }}">
        <small class="text-body-secondary">When this rule becomes active (YYYY-MM-DD)</small>
    </div>

    <!-- Date End Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="date_end" class="form-label">
            <i class="fa fa-calendar-check me-1"></i> Date End
        </label>
        <input type="text" name="date_end" id="date_end" class="form-control"
               value="{{ old('date_end', isset($matchingRule->date_end) ? \Carbon\Carbon::parse($matchingRule->date_end)->format('Y-m-d') : '') }}">
        <small class="text-body-secondary">When this rule expires (YYYY-MM-DD)</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#date_start').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
    $('#date_end').datetimepicker({
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

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('matchingRules.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
