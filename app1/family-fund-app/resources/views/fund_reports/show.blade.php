<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('fundReports.index') }}">Fund Reports</a>
        </li>
        <li class="breadcrumb-item active">Report #{{ $fundReport->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-file-alt me-2"></i>
                                <strong>Fund Report #{{ $fundReport->id }}</strong>
                                @if($fundReport->fund)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('funds.show', $fundReport->fund_id) }}">{{ $fundReport->fund->name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('fundReports.edit', $fundReport->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('fundReports.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('fund_reports.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            @if($fundReport->scheduledJobs()->count() > 0)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fa fa-clock me-2"></i>
                                    <strong>Scheduled Jobs</strong>
                                    <span class="badge bg-primary ms-2">{{ $fundReport->scheduledJobs()->count() }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                @php($scheduledJobs = $fundReport->scheduledJobs())
                                @include('scheduled_jobs.table')
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
