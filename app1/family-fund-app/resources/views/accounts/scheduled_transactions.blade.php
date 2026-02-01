{{-- Scheduled Transactions Section --}}
@if(isset($scheduledTransactionJobs) && $scheduledTransactionJobs->count() > 0)
<div class="row mb-4" id="section-scheduled">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: #ffffff;">
                <strong><i class="fa fa-calendar-alt" style="margin-right: 8px;"></i>Scheduled Transactions</strong>
                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseScheduled"
                   role="button" aria-expanded="true" aria-controls="collapseScheduled">
                    <i class="fa fa-chevron-down"></i>
                </a>
            </div>
            <div class="collapse show" id="collapseScheduled">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Schedule</th>
                                    <th>Active Period</th>
                                    <th>Last Run</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduledTransactionJobs as $job)
                                    @php
                                        $tran = $job->transactionTemplate;
                                        $typeName = \App\Models\TransactionExt::$typeMap[$tran->type] ?? $tran->type;
                                        $lastRun = $job->lastGeneratedReportDate();
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge bg-warning text-dark">{{ $typeName }}</span>
                                        </td>
                                        <td>${{ number_format($tran->value, 2) }}</td>
                                        <td>
                                            <strong>{{ $job->schedule->descr ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">
                                                {{ \App\Models\ScheduleExt::$typeMap[$job->schedule->type] ?? $job->schedule->type }}:
                                                {{ $job->schedule->value }}
                                            </small>
                                        </td>
                                        <td>
                                            {{ $job->start_dt->format('M j, Y') }} -
                                            @if($job->end_dt->year >= 9999)
                                                <span class="text-muted">Never</span>
                                            @else
                                                {{ $job->end_dt->format('M j, Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($lastRun)
                                                {{ $lastRun->format('M j, Y') }}
                                            @else
                                                <span class="text-muted">Never</span>
                                            @endif
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
</div>
@endif
