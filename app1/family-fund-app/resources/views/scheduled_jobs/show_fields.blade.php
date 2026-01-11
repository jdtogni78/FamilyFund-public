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
                @if($scheduledJob->entity_descr == 'fund_report')
                    <a href="{{ route('funds.show', $scheduledJob->entity_id) }}">
                        Fund #{{ $scheduledJob->entity_id }}
                    </a>
                @elseif($scheduledJob->entity_descr == 'trade_band_report')
                    <a href="{{ route('funds.show', $scheduledJob->entity_id) }}">
                        Fund #{{ $scheduledJob->entity_id }} (Trading Bands)
                    </a>
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

@php
    $entities = $scheduledJob->entities();
@endphp

@if($entities->count() > 0)
<hr>
<h6 class="text-body-secondary mb-3"><i class="fa fa-list me-1"></i> Generated Reports ({{ $entities->count() }})</h6>
<div class="table-responsive-sm">
    <table class="table table-sm table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entities->sortByDesc(function($e) {
                return $e->as_of ?? $e->timestamp ?? $e->end_date ?? $e->created_at;
            })->take(10) as $entity)
            <tr>
                <td>
                    @if($scheduledJob->entity_descr == 'fund_report')
                        <a href="{{ route('fundReports.show', $entity->id) }}">#{{ $entity->id }}</a>
                    @elseif($scheduledJob->entity_descr == 'trade_band_report')
                        <a href="{{ route('tradeBandReports.show', $entity->id) }}">#{{ $entity->id }}</a>
                    @else
                        #{{ $entity->id }}
                    @endif
                </td>
                <td>
                    @if(isset($entity->as_of))
                        {{ $entity->as_of->format('M j, Y') }}
                    @elseif(isset($entity->timestamp))
                        {{ $entity->timestamp->format('M j, Y') }}
                    @elseif(isset($entity->end_date))
                        {{ $entity->end_date->format('M j, Y') }}
                    @endif
                </td>
                <td>{{ $entity->created_at->format('M j, Y g:i A') }}</td>
                <td>
                    @if($scheduledJob->entity_descr == 'fund_report')
                        <a href="{{ route('fundReports.show', $entity->id) }}" class="btn btn-sm btn-ghost-success" title="View Fund Report">
                            <i class="fa fa-eye"></i>
                        </a>
                    @elseif($scheduledJob->entity_descr == 'trade_band_report')
                        <a href="{{ route('tradeBandReports.show', $entity->id) }}" class="btn btn-sm btn-ghost-success" title="View Trade Band Report">
                            <i class="fa fa-eye"></i>
                        </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($entities->count() > 10)
        <small class="text-body-secondary">Showing 10 of {{ $entities->count() }} records</small>
    @endif
</div>
@endif
