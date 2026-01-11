@php
    use App\Models\FundReportExt;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Fund Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-landmark me-1"></i> Fund:</label>
            <p class="mb-0">
                @if($fundReport->fund)
                    <a href="{{ route('funds.show', $fundReport->fund_id) }}" class="fw-bold">
                        {{ $fundReport->fund->name }}
                    </a>
                @else
                    <span class="text-muted">N/A</span>
                @endif
            </p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Report Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ FundReportExt::$typeMap[$fundReport->type] ?? $fundReport->type }}</span>
            </p>
        </div>

        <!-- As Of Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> As Of:</label>
            <p class="mb-0 fw-bold">{{ $fundReport->as_of->format('F j, Y') }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Scheduled Job Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Scheduled Job:</label>
            <p class="mb-0">
                @if($fundReport->scheduled_job_id && $fundReport->scheduledJob)
                    <a href="{{ route('scheduledJobs.show', $fundReport->scheduled_job_id) }}">
                        #{{ $fundReport->scheduled_job_id }} - {{ $fundReport->scheduledJob->schedule->descr ?? 'N/A' }}
                    </a>
                @elseif($fundReport->scheduled_job_id)
                    #{{ $fundReport->scheduled_job_id }}
                @else
                    <span class="text-muted">Manual</span>
                @endif
            </p>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $fundReport->created_at->format('F j, Y g:i A') }}</p>
        </div>

        <!-- Report ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Report ID:</label>
            <p class="mb-0">#{{ $fundReport->id }}</p>
        </div>
    </div>
</div>

<hr>

<!-- Quick Actions -->
<div class="d-flex gap-2">
    <a href="{{ route('funds.show', $fundReport->fund_id) }}?as_of={{ $fundReport->as_of->format('Y-m-d') }}"
       class="btn btn-outline-primary">
        <i class="fa fa-eye me-1"></i> View Fund as of {{ $fundReport->as_of->format('M j, Y') }}
    </a>
</div>
