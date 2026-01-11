<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="name" class="form-label">
            <i class="fa fa-bullseye me-1"></i> Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="name" class="form-control" maxlength="30"
               value="{{ old('name', $goal->name ?? '') }}" required>
        <small class="text-body-secondary">Short name for the goal (max 30 characters)</small>
    </div>

    <!-- Description Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="description" class="form-label">
            <i class="fa fa-align-left me-1"></i> Description
        </label>
        <input type="text" name="description" id="description" class="form-control" maxlength="1024"
               value="{{ old('description', $goal->description ?? '') }}">
        <small class="text-body-secondary">Detailed description of the goal</small>
    </div>
</div>

<div class="row">
    <!-- Start Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_dt" class="form-label">
            <i class="fa fa-calendar me-1"></i> Start Date <span class="text-danger">*</span>
        </label>
        <input type="text" name="start_dt" id="start_dt" class="form-control"
               value="{{ old('start_dt', isset($goal->start_dt) ? \Carbon\Carbon::parse($goal->start_dt)->format('Y-m-d') : '') }}" required>
        <small class="text-body-secondary">When the goal tracking begins (YYYY-MM-DD)</small>
    </div>

    <!-- End Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_dt" class="form-label">
            <i class="fa fa-calendar-check me-1"></i> End Date <span class="text-danger">*</span>
        </label>
        <input type="text" name="end_dt" id="end_dt" class="form-control"
               value="{{ old('end_dt', isset($goal->end_dt) ? \Carbon\Carbon::parse($goal->end_dt)->format('Y-m-d') : '') }}" required>
        <small class="text-body-secondary">Target date to achieve the goal (YYYY-MM-DD)</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#start_dt').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
    $('#end_dt').datetimepicker({
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

<hr class="my-3">

<div class="row">
    <!-- Target Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="target_type" class="form-label">
            <i class="fa fa-cog me-1"></i> Target Type <span class="text-danger">*</span>
        </label>
        <select name="target_type" id="target_type" class="form-control form-select" required>
            @foreach($api['targetTypeMap'] as $value => $label)
                <option value="{{ $value }}" {{ old('target_type', $goal->target_type ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">How the target is measured (amount or percentage)</small>
    </div>

    <!-- Target Amount Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="target_amount" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Target Amount
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="target_amount" id="target_amount" class="form-control"
                   value="{{ old('target_amount', $goal->target_amount ?? '') }}">
        </div>
        <small class="text-body-secondary">Target dollar amount (used when type is 'amount')</small>
    </div>
</div>

<div class="row">
    <!-- Target Pct Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="target_pct" class="form-label">
            <i class="fa fa-percentage me-1"></i> Target Percentage
        </label>
        <div class="input-group">
            <input type="number" name="target_pct" id="target_pct" class="form-control" step="0.01"
                   value="{{ old('target_pct', $goal->target_pct ?? '') }}">
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Target growth percentage (used when type is 'percentage')</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-3">

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_ids', isset($goal) ? $goal->accounts->pluck('id')->toArray() : []);
@endphp
@include('partials.fund_account_selector', ['selectedAccounts' => $selectedAccounts, 'multiple' => true])

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('goals.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
