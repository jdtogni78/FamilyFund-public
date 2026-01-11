@if(isset($api['matching_rules']) && count($api['matching_rules']) > 0)
@php
    $now = \Carbon\Carbon::now();
    $totalUsed = 0;
    $totalGranted = 0;
    $totalAvailable = 0;
    $totalMissed = 0;
@endphp
<div class="table-responsive-sm">
    <table class="table table-hover" id="matching-rules-table">
        <thead>
        <tr>
            <th style="width: 100px;">Status</th>
            <th>Period</th>
            <th class="text-end">Match %</th>
            <th class="text-end">Available</th>
            <th class="text-end">Used</th>
            <th class="text-end">Missed</th>
            <th class="text-end">Granted</th>
        </tr>
        </thead>
        <tbody>
        @foreach($api['matching_rules'] as $match)
            @php
                $startDate = \Carbon\Carbon::parse($match['date_start']);
                $endDate = \Carbon\Carbon::parse($match['date_end']);
                $isExpired = $now->gt($endDate);
                $isActive = $now->gte($startDate) && $now->lte($endDate);
                $isUpcoming = $now->lt($startDate);

                $available = $match['dollar_range_end'] ?? 0;
                $used = $match['used'] ?? 0;
                $granted = $match['granted'] ?? 0;
                $matchPercent = $match['match_percent'] ?? 0;

                // Calculate missed: for expired rules, what wasn't used
                $missed = 0;
                if ($isExpired) {
                    $missedUsage = $available - $used;
                    $missed = $missedUsage * ($matchPercent / 100);
                }

                // Determine status and styling (using Bootstrap classes for dark mode)
                if ($isActive) {
                    $status = 'Active';
                    $badgeClass = 'bg-success text-white';
                    $rowClass = 'table-success';
                    $textClass = '';
                    $totalAvailable += ($available - $used);
                } elseif ($isUpcoming) {
                    $status = 'Upcoming';
                    $badgeClass = 'bg-info text-white';
                    $rowClass = 'table-info';
                    $textClass = '';
                    $totalAvailable += $available;
                } elseif ($isExpired && $used == 0 && $available > 0) {
                    $status = 'Missed';
                    $badgeClass = 'bg-warning text-dark';
                    $rowClass = 'table-warning';
                    $textClass = 'text-body-secondary';
                } else {
                    $status = 'Expired';
                    $badgeClass = 'bg-secondary';
                    $rowClass = '';
                    $textClass = 'text-body-secondary';
                }

                // Format period - show date range
                $periodDisplay = $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y');

                $totalUsed += $used;
                $totalGranted += $granted;
                $totalMissed += $missed;
            @endphp
            <tr class="{{ $rowClass }}">
                <td>
                    <span class="badge {{ $badgeClass }}">
                        {{ $status }}
                    </span>
                </td>
                <td class="{{ $textClass }}" style="font-weight: {{ $isActive ? '700' : '400' }};">{{ $periodDisplay }}</td>
                <td class="text-end {{ $textClass }}">{{ number_format($matchPercent, 0) }}% up to ${{ number_format($available, 0) }}</td>
                <td class="text-end {{ $textClass }}">${{ number_format($available, 0) }}</td>
                <td class="text-end {{ $textClass }}">${{ number_format($used, 2) }}</td>
                <td class="text-end">
                    @if($missed > 0)
                        <span class="text-warning fw-medium">${{ number_format($missed, 2) }}</span>
                    @else
                        <span class="{{ $textClass }}">-</span>
                    @endif
                </td>
                <td class="text-end">
                    <strong class="{{ $granted > 0 ? 'text-success' : $textClass }}">${{ number_format($granted, 2) }}</strong>
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="table-total-row">
                <td colspan="3">
                    Totals
                    @if($totalAvailable > 0)
                        <span class="badge bg-success ms-2">${{ number_format($totalAvailable, 0) }} AVAILABLE</span>
                    @endif
                </td>
                <td class="text-end"></td>
                <td class="text-end">${{ number_format($totalUsed, 2) }}</td>
                <td class="text-end">
                    @if($totalMissed > 0)
                        <span class="text-warning">${{ number_format($totalMissed, 2) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-end">${{ number_format($totalGranted, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#matching-rules-table').DataTable({
            order: [[1, 'desc']], // Sort by period descending
            paging: false,
            searching: false,
            info: false
        });
    });
</script>
@else
<div class="text-body-secondary text-center p-4 rounded" style="background: var(--bs-tertiary-bg);">
    No matching rules configured.
</div>
@endif
