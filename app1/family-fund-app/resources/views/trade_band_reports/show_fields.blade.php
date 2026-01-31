@php
    $fund = $tradeBandReport->fund;
    $scheduledJob = $tradeBandReport->scheduledJob;
    $isTemplate = $tradeBandReport->as_of && $tradeBandReport->as_of->format('Y') === '9999';
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Fund Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-landmark me-1"></i> Fund:</label>
            <p class="mb-0">
                @if($fund)
                    @include('partials.view_link', ['route' => route('funds.show', $fund->id), 'text' => $fund->name, 'class' => 'fw-bold'])
                @else
                    <span class="text-body-secondary">ID: {{ $tradeBandReport->fund_id }}</span>
                @endif
            </p>
        </div>

        <!-- As Of Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> As Of:</label>
            <p class="mb-0">
                @if($isTemplate)
                    <span class="badge bg-info">Template</span>
                @else
                    <span class="fw-bold">{{ $tradeBandReport->as_of->format('M j, Y') }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Scheduled Job Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Scheduled Job:</label>
            <p class="mb-0">
                @if($scheduledJob)
                    @include('partials.view_link', ['route' => route('scheduledJobs.show', $scheduledJob->id), 'text' => '#' . $scheduledJob->id])
                    @if($scheduledJob->schedule)
                        <span class="text-body-secondary">- {{ $scheduledJob->schedule->descr }}</span>
                    @endif
                @elseif($tradeBandReport->scheduled_job_id)
                    #{{ $tradeBandReport->scheduled_job_id }}
                @else
                    <span class="text-body-secondary">Manual</span>
                @endif
            </p>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $tradeBandReport->created_at?->format('M j, Y g:i A') ?: '-' }}</p>
        </div>

        <!-- Trade Band Report ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Report ID:</label>
            <p class="mb-0">#{{ $tradeBandReport->id }}</p>
        </div>
    </div>
</div>
