<!-- Id Field -->
<div class="form-group">
    <label for="id">Id:</label>
    <p>{{ $portfolioReport->id }}</p>
</div>

<!-- Portfolio Field -->
<div class="form-group">
    <label for="portfolio_id">Portfolio:</label>
    <p>
        @if($portfolioReport->portfolio)
            <a href="{{ route('portfolios.show', $portfolioReport->portfolio_id) }}">
                {{ $portfolioReport->portfolio->name }}
            </a>
        @else
            {{ $portfolioReport->portfolio_id }}
        @endif
    </p>
</div>

<!-- Start Date Field -->
<div class="form-group">
    <label for="start_date">Start Date:</label>
    <p>{{ $portfolioReport->start_date->format('Y-m-d') }}</p>
</div>

<!-- End Date Field -->
<div class="form-group">
    <label for="end_date">End Date:</label>
    <p>{{ $portfolioReport->end_date->format('Y-m-d') }}</p>
</div>

<!-- Scheduled Job Field -->
<div class="form-group">
    <label for="scheduled_job_id">Scheduled Job:</label>
    <p>
        @if($portfolioReport->scheduled_job_id)
            <a href="{{ route('scheduledJobs.show', $portfolioReport->scheduled_job_id) }}">
                {{ $portfolioReport->scheduled_job_id }}
            </a>
        @else
            -
        @endif
    </p>
</div>

<!-- Created At Field -->
<div class="form-group">
    <label for="created_at">Created At:</label>
    <p>{{ $portfolioReport->created_at }}</p>
</div>

<!-- View PDF Link -->
<div class="form-group">
    <a href="{{ route('portfolios.showRebalancePDF', [$portfolioReport->portfolio_id, $portfolioReport->start_date->format('Y-m-d'), $portfolioReport->end_date->format('Y-m-d')]) }}" class="btn btn-primary">
        <i class="fa fa-file-pdf-o"></i> View PDF
    </a>
</div>
