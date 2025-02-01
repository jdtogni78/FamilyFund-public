<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('fundReports.index') }}">Fund Report</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Details</strong>
                            <a href="{{ route('fundReports.index') }}" class="btn btn-light">Back</a>
                        </div>
                        <div class="card-body">
                            @include('fund_reports.show_fields')
                        </div>
                    </div>
                </div>
            </div>
            @if($fundReport->scheduledJobs()->count() > 0)
                @php($scheduledJobs = $fundReport->scheduledJobs())
                @include('scheduled_jobs.table')
            @endif
        </div>
    </div>
</x-app-layout>
