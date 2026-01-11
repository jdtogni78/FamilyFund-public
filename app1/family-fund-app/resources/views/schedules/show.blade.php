<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('schedules.index') }}">Schedules</a>
        </li>
        <li class="breadcrumb-item active">{{ $schedule->descr }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-calendar-alt me-2"></i>
                                <strong>{{ $schedule->descr }}</strong>
                            </div>
                            <div>
                                <a href="{{ route('schedules.edit', $schedule->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('schedules.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('schedules.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            @if($schedule->scheduledJobs()->count() > 0)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-clock me-2"></i>
                                <strong>Scheduled Jobs</strong>
                                <span class="badge bg-primary ms-2">{{ $schedule->scheduledJobs()->count() }}</span>
                            </div>
                            <a href="{{ route('scheduledJobs.create') }}?schedule_id={{ $schedule->id }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus me-1"></i> New Job
                            </a>
                        </div>
                        <div class="card-body">
                            @php($scheduledJobs = $schedule->scheduledJobs()->get())
                            @include('scheduled_jobs.table')
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
