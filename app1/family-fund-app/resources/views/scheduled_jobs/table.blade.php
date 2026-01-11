@php
    use App\Models\ScheduledJobExt;
    use App\Models\ScheduleExt;
@endphp

<div class="table-responsive-sm">
    <table class="table table-hover" id="scheduledJobs-table">
        <thead>
        <tr>
            <th>Status</th>
            <th>Schedule</th>
            <th>Entity</th>
            <th>Active Period</th>
            <th>Last Run</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($scheduledJobs as $scheduledJob)
            @php
                $now = now();
                $isActive = $now->gte($scheduledJob->start_dt) && $now->lte($scheduledJob->end_dt);
                $isExpired = $now->gt($scheduledJob->end_dt);
                $isUpcoming = $now->lt($scheduledJob->start_dt);

                if ($isActive) {
                    $statusBadge = 'bg-success';
                    $statusText = 'Active';
                    $rowClass = '';
                } elseif ($isExpired) {
                    $statusBadge = 'bg-secondary';
                    $statusText = 'Expired';
                    $rowClass = 'text-body-secondary';
                } else {
                    $statusBadge = 'bg-info';
                    $statusText = 'Upcoming';
                    $rowClass = '';
                }

                // Get entity name
                $entityName = '#' . $scheduledJob->entity_id;
                if ($scheduledJob->entity_descr == 'fund_report' && $scheduledJob->fund) {
                    $entityName = $scheduledJob->fund->name;
                } elseif ($scheduledJob->entity_descr == 'portfolio_report' && $scheduledJob->portfolio) {
                    $entityName = $scheduledJob->portfolio->source;
                }
            @endphp
            <tr class="{{ $rowClass }}">
                <td>
                    <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
                </td>
                <td>
                    <strong>{{ $scheduledJob->schedule->descr ?? 'N/A' }}</strong>
                    <br>
                    <small class="text-body-secondary">
                        {{ ScheduleExt::$typeMap[$scheduledJob->schedule->type] ?? $scheduledJob->schedule->type }}:
                        {{ $scheduledJob->schedule->value }}
                    </small>
                </td>
                <td>
                    <span class="badge bg-primary">{{ ScheduledJobExt::$entityMap[$scheduledJob->entity_descr] ?? $scheduledJob->entity_descr }}</span>
                    <br>
                    <small>{{ $entityName }}</small>
                </td>
                <td>
                    {{ $scheduledJob->start_dt->format('M j, Y') }} - {{ $scheduledJob->end_dt->format('M j, Y') }}
                </td>
                <td>
                    @php
                        $lastRun = $scheduledJob->lastGeneratedReportDate();
                    @endphp
                    @if($lastRun)
                        {{ $lastRun->format('M j, Y') }}
                    @else
                        <span class="text-body-secondary">Never</span>
                    @endif
                </td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('scheduledJobs.show', [$scheduledJob->id]) }}" class='btn btn-ghost-success' title="View"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('scheduledJobs.edit', [$scheduledJob->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                        <a href="{{ route('scheduledJobs.preview', ['id' => $scheduledJob->id, 'asOf' => now()->format('Y-m-d')]) }}" class="btn btn-ghost-warning" title="Preview"><i class="fa fa-eye"></i></a>
                        <form action="{{ route('scheduledJobs.force-run', ['id' => $scheduledJob->id, 'asOf' => now()->format('Y-m-d')]) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" title="Force Run" onclick="return confirm('Force run will bypass schedule checks. Are you sure?')"><i class="fa fa-forward"></i></button>
                        </form>
                        <form action="{{ route('scheduledJobs.destroy', $scheduledJob->id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this scheduled job?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#scheduledJobs-table').DataTable({
            order: [[0, 'asc']],
            paging: true,
            pageLength: 25,
            searching: true,
            info: true
        });
    });
</script>
