<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Portfolio Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="portfolio_id" class="form-label">
            <i class="fa fa-briefcase me-1"></i> Portfolio <span class="text-danger">*</span>
        </label>
        <select name="portfolio_id" id="portfolio_id" class="form-control form-select" required>
            @foreach($api['portfolios'] as $id => $name)
                <option value="{{ $id }}" {{ (isset($portfolioReport) && $portfolioReport->portfolio_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Portfolio to generate report for</small>
    </div>

    <!-- Report Type Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="report_type" class="form-label">
            <i class="fa fa-file-alt me-1"></i> Report Type <span class="text-danger">*</span>
        </label>
        <select name="report_type" id="report_type" class="form-control form-select" required>
            @foreach($api['typeMap'] as $value => $label)
                <option value="{{ $value }}" {{ (isset($portfolioReport) && $portfolioReport->report_type == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Quarterly covers previous quarter, Annual covers previous year</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Start Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_date" class="form-label">
            <i class="fa fa-calendar me-1"></i> Start Date
        </label>
        <input type="date" name="start_date" id="start_date" class="form-control"
               value="{{ isset($portfolioReport) ? $portfolioReport->start_date->format('Y-m-d') : now()->subMonths(3)->format('Y-m-d') }}">
        <small class="text-body-secondary">For custom type only; quarterly/annual calculate automatically</small>
    </div>

    <!-- End Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_date" class="form-label">
            <i class="fa fa-calendar-check me-1"></i> End Date
        </label>
        <input type="date" name="end_date" id="end_date" class="form-control"
               value="{{ isset($portfolioReport) ? $portfolioReport->end_date->format('Y-m-d') : now()->format('Y-m-d') }}">
        <small class="text-body-secondary">For custom type only; quarterly/annual calculate automatically</small>
    </div>
</div>

<div class="row">
    <!-- Scheduled Job Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="scheduled_job_id" class="form-label">
            <i class="fa fa-clock me-1"></i> Scheduled Job ID
        </label>
        <input type="number" name="scheduled_job_id" id="scheduled_job_id" class="form-control"
               value="{{ isset($portfolioReport) ? $portfolioReport->scheduled_job_id : '' }}">
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
    <a href="{{ route('portfolioReports.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
