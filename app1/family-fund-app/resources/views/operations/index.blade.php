<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Operations</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            {{-- Quick Actions --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fa fa-calendar-check me-2"></i>Scheduled Jobs</h5>
                            <p class="card-text text-muted">Run all due scheduled jobs</p>
                            <form action="{{ route('operations.run_due_jobs') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Run all due scheduled jobs?')">
                                    <i class="fa fa-play me-1"></i> Run Due Jobs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fa fa-clock me-2"></i>Pending Transactions</h5>
                            <p class="card-text">
                                @if($pendingTransactions > 0)
                                    <span class="badge bg-warning text-dark fs-6">{{ $pendingTransactions }} pending</span>
                                @else
                                    <span class="text-muted">No pending transactions</span>
                                @endif
                            </p>
                            @if($pendingTransactions > 0)
                            <form action="{{ route('operations.process_pending') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning" onclick="return confirm('Process {{ $pendingTransactions }} pending transaction(s)?')">
                                    <i class="fa fa-play me-1"></i> Process All
                                </button>
                            </form>
                            @else
                            <button class="btn btn-secondary" disabled>
                                <i class="fa fa-check me-1"></i> All Clear
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-{{ $queueRunning ? 'success' : 'secondary' }}">
                        <div class="card-body text-center">
                            <h5 class="card-title"><i class="fa fa-cogs me-2"></i>Queue Worker</h5>
                            <p class="card-text">
                                @if($queueRunning)
                                    <span class="badge bg-success fs-6"><i class="fa fa-circle me-1"></i> Running</span>
                                @else
                                    <span class="badge bg-secondary fs-6"><i class="fa fa-circle me-1"></i> Stopped</span>
                                @endif
                            </p>
                            @if($queueRunning)
                            <form action="{{ route('operations.queue_stop') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Stop queue worker?')">
                                    <i class="fa fa-stop me-1"></i> Stop
                                </button>
                            </form>
                            @else
                            <form action="{{ route('operations.queue_start') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-play me-1"></i> Start
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduled Jobs Status --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-calendar me-2"></i>
                            <strong>Scheduled Jobs Status</strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Template</th>
                                            <th>Schedule</th>
                                            <th>Last Run</th>
                                            <th>Next Due</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($scheduledJobs as $item)
                                        @php
                                            $job = $item['job'];
                                            $templateName = match($job->entity_descr) {
                                                'fund_report' => $job->fundReportTemplate?->fund?->name ?? 'N/A',
                                                'transaction' => $job->transactionTemplate?->account?->nickname ?? 'N/A',
                                                'trade_band_report' => $job->tradeBandReportTemplate?->fund?->name ?? 'N/A',
                                                default => 'N/A'
                                            };
                                        @endphp
                                        <tr>
                                            <td>{{ $job->id }}</td>
                                            <td>
                                                <span class="badge bg-info">{{ \App\Models\ScheduledJobExt::$entityMap[$job->entity_descr] ?? $job->entity_descr }}</span>
                                            </td>
                                            <td>{{ $templateName }}</td>
                                            <td>{{ $job->schedule?->descr ?? 'N/A' }}</td>
                                            <td>{{ $item['lastRun']?->format('Y-m-d') ?? 'Never' }}</td>
                                            <td>{{ $item['shouldRunBy']['shouldRunBy']->format('Y-m-d') }}</td>
                                            <td>
                                                @if($item['isDue'])
                                                    <span class="badge bg-warning text-dark">Due</span>
                                                @else
                                                    <span class="badge bg-success">OK</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('scheduledJobs.preview', [$job->id, now()->format('Y-m-d')]) }}" class="btn btn-ghost-info btn-sm" title="Preview">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <form action="{{ route('scheduledJobs.run', [$job->id, now()->format('Y-m-d')]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-ghost-primary btn-sm" title="Run" onclick="return confirm('Run this job?')">
                                                        <i class="fa fa-play"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('scheduledJobs.force-run', [$job->id, now()->format('Y-m-d')]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-ghost-warning btn-sm" title="Force Run" onclick="return confirm('Force run this job (bypass schedule)?')">
                                                        <i class="fa fa-bolt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Failed Queue Jobs --}}
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-exclamation-triangle me-2 text-danger"></i>
                                <strong>Failed Queue Jobs</strong>
                                @if($failedJobsCount > 0)
                                    <span class="badge bg-danger ms-2">{{ $failedJobsCount }}</span>
                                @endif
                            </div>
                            @if($failedJobsCount > 0)
                            <div>
                                <form action="{{ route('operations.queue_retry_all') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Retry all {{ $failedJobsCount }} failed job(s)?')">
                                        <i class="fa fa-redo me-1"></i> Retry All
                                    </button>
                                </form>
                                <form action="{{ route('operations.queue_flush') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete all {{ $failedJobsCount }} failed job(s)? This cannot be undone.')">
                                        <i class="fa fa-trash me-1"></i> Flush All
                                    </button>
                                </form>
                            </div>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($failedJobs->isEmpty())
                                <p class="text-muted text-center mb-0"><i class="fa fa-check-circle me-1 text-success"></i> No failed jobs</p>
                            @else
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Failed At</th>
                                            <th>Job</th>
                                            <th>Error</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($failedJobs as $job)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($job->failed_at)->format('Y-m-d H:i') }}</td>
                                            <td><code>{{ $job->job_name }}</code></td>
                                            <td><small class="text-danger">{{ $job->exception }}</small></td>
                                            <td>
                                                <form action="{{ route('operations.queue_retry', $job->uuid) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-ghost-primary btn-sm" title="Retry">
                                                        <i class="fa fa-redo"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Operations History --}}
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-history me-2"></i>
                            <strong>Operations History</strong>
                        </div>
                        <div class="card-body">
                            @if($operationLogs->isEmpty())
                                <p class="text-muted text-center mb-0">No operations logged yet</p>
                            @else
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Operation</th>
                                            <th>Result</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($operationLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>{{ $log->user?->name ?? 'System' }}</td>
                                            <td>{{ $log->operationName() }}</td>
                                            <td>
                                                @if($log->result === 'success')
                                                    <span class="badge bg-success">Success</span>
                                                @elseif($log->result === 'warning')
                                                    <span class="badge bg-warning text-dark">Warning</span>
                                                @else
                                                    <span class="badge bg-danger">Error</span>
                                                @endif
                                            </td>
                                            <td><small>{{ $log->message }}</small></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
