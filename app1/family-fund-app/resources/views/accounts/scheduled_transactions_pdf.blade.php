{{-- Scheduled Transactions Section for PDF --}}
@php
    $scheduledJobs = $api['scheduledTransactionJobs'] ?? collect();
@endphp
@if($scheduledJobs->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h4 class="card-header-title"><img src="{{ public_path('images/icons/calendar.svg') }}" class="header-icon">Scheduled Transactions</h4>
    </div>
    <div class="card-body">
        <table width="100%" cellspacing="0" cellpadding="4" style="font-size: 11px;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px;">Type</th>
                    <th style="text-align: right; border-bottom: 1px solid #e2e8f0; padding: 8px;">Amount</th>
                    <th style="text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px;">Schedule</th>
                    <th style="text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px;">Active Period</th>
                    <th style="text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px;">Last Run</th>
                </tr>
            </thead>
            <tbody>
            @foreach($scheduledJobs as $job)
                @php
                    $tran = $job->transactionTemplate;
                    $typeName = \App\Models\TransactionExt::$typeMap[$tran->type] ?? $tran->type;
                    $lastRun = $job->lastGeneratedReportDate();
                    $scheduleType = \App\Models\ScheduleExt::$typeMap[$job->schedule->type] ?? $job->schedule->type;
                @endphp
                <tr style="background: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                    <td style="padding: 6px 8px;">
                        <span style="background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">{{ $typeName }}</span>
                    </td>
                    <td style="text-align: right; padding: 6px 8px; font-weight: 600;">${{ number_format($tran->value, 2) }}</td>
                    <td style="padding: 6px 8px;">
                        <strong>{{ $job->schedule->descr ?? 'N/A' }}</strong><br>
                        <span style="font-size: 9px; color: #64748b;">{{ $scheduleType }}: {{ $job->schedule->value }}</span>
                    </td>
                    <td style="padding: 6px 8px;">
                        {{ $job->start_dt->format('M j, Y') }} -
                        @if($job->end_dt->year >= 9999)
                            <span style="color: #64748b;">Never</span>
                        @else
                            {{ $job->end_dt->format('M j, Y') }}
                        @endif
                    </td>
                    <td style="padding: 6px 8px;">
                        @if($lastRun)
                            {{ $lastRun->format('M j, Y') }}
                        @else
                            <span style="color: #64748b;">Never</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
