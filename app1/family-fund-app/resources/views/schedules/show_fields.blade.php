@php
    use App\Models\ScheduleExt;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Description Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar-alt me-1"></i> Description:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $schedule->descr }}</p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ ScheduleExt::$typeMap[$schedule->type] ?? $schedule->type }}</span>
            </p>
        </div>

        <!-- Value Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Value:</label>
            <p class="mb-0 fw-bold">{{ $schedule->value }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Scheduled Jobs Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Scheduled Jobs:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $schedule->scheduledJobs()->count() }}</span>
            </p>
        </div>

        <!-- Schedule ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Schedule ID:</label>
            <p class="mb-0">#{{ $schedule->id }}</p>
        </div>
    </div>
</div>
