<!-- Portfolio Id Field -->
<div class="form-group col-sm-6">
    <label for="portfolio_id">Portfolio:</label>
    <select name="portfolio_id" class="form-control">
        @foreach($api['portfolios'] as $id => $name)
            <option value="{{ $id }}" {{ (isset($portfolioReport) && $portfolioReport->portfolio_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
</div>

<!-- Report Type Field -->
<div class="form-group col-sm-6">
    <label for="report_type">Report Type:</label>
    <select name="report_type" class="form-control" id="report_type">
        @foreach($api['typeMap'] as $value => $label)
            <option value="{{ $value }}" {{ (isset($portfolioReport) && $portfolioReport->report_type == $value) ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <small class="form-text text-muted">
        For scheduled reports: Quarterly covers previous quarter, Annual covers previous year
    </small>
</div>

<!-- Start Date Field -->
<div class="form-group col-sm-6">
    <label for="start_date">Start Date:</label>
    <input type="date" name="start_date" class="form-control" id="start_date" value="{{ isset($portfolioReport) ? $portfolioReport->start_date->format('Y-m-d') : now()->subMonths(3)->format('Y-m-d') }}">
    <small class="form-text text-muted">For custom type only; quarterly/annual calculate automatically</small>
</div>

<!-- End Date Field -->
<div class="form-group col-sm-6">
    <label for="end_date">End Date:</label>
    <input type="date" name="end_date" class="form-control" id="end_date" value="{{ isset($portfolioReport) ? $portfolioReport->end_date->format('Y-m-d') : now()->format('Y-m-d') }}">
    <small class="form-text text-muted">For custom type only; quarterly/annual calculate automatically</small>
</div>

<!-- Scheduled Job Id Field -->
<div class="form-group col-sm-6">
    <label for="scheduled_job_id">Scheduled Job Id (optional):</label>
    <input type="number" name="scheduled_job_id" class="form-control" value="{{ isset($portfolioReport) ? $portfolioReport->scheduled_job_id : '' }}">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <a href="{{ route('portfolioReports.index') }}" class="btn btn-secondary">Cancel</a>
</div>
