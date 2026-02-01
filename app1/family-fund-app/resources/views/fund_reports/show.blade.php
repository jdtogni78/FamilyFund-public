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
                                <form action="{{ route('fundReports.resend', $fundReport->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Resend this report email?')">
                                        <i class="fa fa-paper-plane me-1"></i> Resend
                                    </button>
                                </form>
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

            {{-- Email Status --}}
            @if($fundReport->as_of->format('Y-m-d') !== '9999-12-31')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-envelope me-2"></i>
                                <strong>Email Status</strong>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Fund Report Email:</strong>
                                @if($emailStatus['fundEmailSent'])
                                    <span class="badge bg-success"><i class="fa fa-check me-1"></i> Sent</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="fa fa-clock me-1"></i> Pending</span>
                                @endif
                            </div>

                            @if(count($emailStatus['accountReports']) > 0)
                            <strong>Account Report Emails:</strong>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Account</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($emailStatus['accountReports'] as $ar)
                                        <tr>
                                            <td>{{ $ar['id'] }}</td>
                                            <td>{{ $ar['account'] }}</td>
                                            <td>
                                                @if($ar['sent'])
                                                    <span class="badge bg-success"><i class="fa fa-check me-1"></i> Sent</span>
                                                @else
                                                    <span class="badge bg-warning text-dark"><i class="fa fa-clock me-1"></i> Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted mb-0">No account reports generated yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
