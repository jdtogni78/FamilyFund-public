<x-app-layout>

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('scheduledJobs.index') }}">Scheduled Job</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="card">
                     <div class="card-header">
                         <strong>Run Info</strong>
                     </div>
                     <div class="card-body">
                        @if($shouldRunBy)
                        <ul>
                            <li>Today: {{ $shouldRunBy['today']->toDateString() }}</li>
                            <li>Last Run: {{ $shouldRunBy['lastRun']?->toDateString() }}</li>
                            <li>Should Run By: {{ $shouldRunBy['shouldRunBy']->toDateString() }}</li>
                        </ul>
                        @else
                        <div class="alert alert-warning">
                            Job is outside its active date range (check start_dt/end_dt).
                        </div>
                        @endif
                     </div>
                 </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Scheduled Job Details</strong>
                            <div>
                                <form action="{{ route('scheduledJobs.run', ['id' => $scheduledJob->id, 'asOf' => $asOf]) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to run this scheduled job?')">
                                        <i class="fa fa-play me-1"></i> Run
                                    </button>
                                </form>
                                <form action="{{ route('scheduledJobs.force-run', ['id' => $scheduledJob->id, 'asOf' => $asOf]) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Force run will bypass schedule checks. Are you sure?')">
                                        <i class="fa fa-forward me-1"></i> Force Run
                                    </button>
                                </form>
                                <form action="{{ route('scheduledJobs.force-run', ['id' => $scheduledJob->id, 'asOf' => $asOf]) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="skip_data_check" value="1">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('This will create a report even without new data. Are you sure?')">
                                        <i class="fa fa-bolt me-1"></i> Force (No Data Check)
                                    </button>
                                </form>
                                <a href="{{ route('scheduledJobs.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('scheduled_jobs.table', ['scheduledJobs' => [$scheduledJob]])
                        </div>
                    </div>
                </div>
            </div>
            @isset($children)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Children</strong>
                        </div>
                        <div class="card-body">
                            @if($scheduledJob->entity_descr == \App\Models\ScheduledJobExt::ENTITY_FUND_REPORT)
                                @include('fund_reports.table', ['fundReports' => $children])
                            @elseif($scheduledJob->entity_descr == \App\Models\ScheduledJobExt::ENTITY_TRANSACTION)
                                @include('transactions.table', ['transactions' => $children])
                            @elseif($scheduledJob->entity_descr == \App\Models\ScheduledJobExt::ENTITY_TRADE_BAND_REPORT)
                                @include('trade_band_reports.table', ['tradeBandReports' => $children])
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endisset
        </div>
    </div>
</x-app-layout>
