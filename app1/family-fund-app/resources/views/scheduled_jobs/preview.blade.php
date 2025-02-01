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
                        <ul>
                            <li>Today: {{ $shouldRunBy['today']->toDateString() }}</li>
                            <li>Last Run: {{ $shouldRunBy['lastRun']?->toDateString() }}</li>
                            <li>Should Run By: {{ $shouldRunBy['shouldRunBy']->toDateString() }}</li>
                        </ul>
                     </div>
                 </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong>Scheduled Job Details</strong>
                            <a href="{{ route('scheduledJobs.index') }}" class="btn btn-light">Back</a>
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
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endisset
        </div>
    </div>
</x-app-layout>
