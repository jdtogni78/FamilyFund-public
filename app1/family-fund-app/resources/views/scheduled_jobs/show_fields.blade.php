@php
    use App\Models\ScheduledJobExt;
    use App\Models\ScheduleExt;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Schedule Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar-alt me-1"></i> Schedule:</label>
            <p class="mb-0">
                @if($scheduledJob->schedule)
                    <span class="fw-bold">{{ $scheduledJob->schedule->descr ?? 'N/A' }}</span>
                    <br>
                    <small class="text-body-secondary">
                        {{ ScheduleExt::$typeMap[$scheduledJob->schedule->type] ?? $scheduledJob->schedule->type }}:
                        {{ $scheduledJob->schedule->value }}
                    </small>
                @else
                    <span class="text-muted">N/A</span>
                @endif
            </p>
        </div>

        <!-- Entity Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Entity Type:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ ScheduledJobExt::$entityMap[$scheduledJob->entity_descr] ?? $scheduledJob->entity_descr }}</span>
            </p>
        </div>

        <!-- Entity ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-link me-1"></i> Entity:</label>
            <p class="mb-0">
                @if($scheduledJob->entity_descr == 'fund_report' && $scheduledJob->fundReportTemplate)
                    @php
                        $template = $scheduledJob->fundReportTemplate;
                        $typeName = \App\Models\FundReportExt::$typeMap[$template->type] ?? $template->type;
                    @endphp
                    @include('partials.view_link', ['route' => route('fundReports.show', $template->id), 'text' => $template->fund->name . ' - ' . $typeName])
                    <span class="badge bg-info ms-1">Template</span>
                @elseif($scheduledJob->entity_descr == 'trade_band_report' && $scheduledJob->tradeBandReportTemplate)
                    @include('partials.view_link', ['route' => route('tradeBandReports.show', $scheduledJob->tradeBandReportTemplate->id), 'text' => $scheduledJob->tradeBandReportTemplate->fund->name])
                    <span class="badge bg-info ms-1">Template</span>
                @elseif($scheduledJob->entity_descr == 'transaction' && $scheduledJob->transactionTemplate)
                    @php
                        $tran = $scheduledJob->transactionTemplate;
                        $typeName = \App\Models\TransactionExt::$typeMap[$tran->type] ?? $tran->type;
                        $accountName = $tran->account->nickname ?? 'Acct#' . $tran->account_id;
                    @endphp
                    @include('partials.view_link', ['route' => route('transactions.show', $tran->id), 'text' => $accountName . ' - ' . $typeName . ' - $' . number_format($tran->value, 0)])
                    <span class="badge bg-info ms-1">Template</span>
                @elseif($scheduledJob->entity_descr == 'matching_reminder')
                    <span class="text-body-secondary">
                        <i class="fa fa-bell me-1"></i>
                        Sends reminders for matching rules expiring within 45 days
                    </span>
                @else
                    #{{ $scheduledJob->entity_id }}
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Date Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Active Period:</label>
            <p class="mb-0">
                {{ $scheduledJob->start_dt->format('M j, Y') }} - {{ $scheduledJob->end_dt->format('M j, Y') }}
                @php
                    $now = now();
                    $isActive = $now->gte($scheduledJob->start_dt) && $now->lte($scheduledJob->end_dt);
                    $isExpired = $now->gt($scheduledJob->end_dt);
                    $isUpcoming = $now->lt($scheduledJob->start_dt);
                @endphp
                @if($isActive)
                    <span class="badge bg-success ms-2">Active</span>
                @elseif($isExpired)
                    <span class="badge bg-secondary ms-2">Expired</span>
                @else
                    <span class="badge bg-info ms-2">Upcoming</span>
                @endif
            </p>
        </div>

        <!-- Last Run Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-history me-1"></i> Last Generated:</label>
            <p class="mb-0">
                @php
                    $lastRun = $scheduledJob->lastGeneratedReportDate();
                @endphp
                @if($lastRun)
                    {{ $lastRun->format('M j, Y') }}
                @else
                    <span class="text-muted">Never</span>
                @endif
            </p>
        </div>

        <!-- Job ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Job ID:</label>
            <p class="mb-0">#{{ $scheduledJob->id }}</p>
        </div>
    </div>
</div>

