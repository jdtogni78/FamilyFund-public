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
        <thead class="table-light">
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

                // Determine status and styling
                if ($isActive) {
                    $status = 'Active';
                    $statusColor = '#16a34a';
                    $statusBg = '#dcfce7';
                    $rowBg = '#f0fdf4';
                    $textColor = '#000000';
                    $totalAvailable += ($available - $used);
                } elseif ($isUpcoming) {
                    $status = 'Upcoming';
                    $statusColor = '#2563eb';
                    $statusBg = '#dbeafe';
                    $rowBg = '#eff6ff';
                    $textColor = '#000000';
                    $totalAvailable += $available;
                } elseif ($isExpired && $used == 0 && $available > 0) {
                    $status = 'Missed';
                    $statusColor = '#d97706';
                    $statusBg = '#fef3c7';
                    $rowBg = '#fffbeb';
                    $textColor = '#78716c';
                } else {
                    $status = 'Expired';
                    $statusColor = '#64748b';
                    $statusBg = '#f1f5f9';
                    $rowBg = '#ffffff';
                    $textColor = '#64748b';
                }

                // Format period - show year
                $periodDisplay = $startDate->format('Y');

                $totalUsed += $used;
                $totalGranted += $granted;
                $totalMissed += $missed;
            @endphp
            <tr style="background: {{ $rowBg }};">
                <td>
                    <span class="badge" style="background-color: {{ $statusBg }}; color: {{ $statusColor }}; font-weight: 600;">
                        {{ $status }}
                    </span>
                </td>
                <td style="color: {{ $textColor }}; font-weight: {{ $isActive ? '700' : '400' }};">{{ $periodDisplay }}</td>
                <td class="text-end" style="color: {{ $textColor }};">{{ number_format($matchPercent, 0) }}% up to ${{ number_format($available, 0) }}</td>
                <td class="text-end" style="color: {{ $textColor }};">${{ number_format($available, 0) }}</td>
                <td class="text-end" style="color: {{ $textColor }};">${{ number_format($used, 2) }}</td>
                <td class="text-end">
                    @if($missed > 0)
                        <span style="color: #d97706; font-weight: 500;">${{ number_format($missed, 2) }}</span>
                    @else
                        <span style="color: {{ $textColor }};">-</span>
                    @endif
                </td>
                <td class="text-end">
                    <strong style="color: {{ $granted > 0 ? '#16a34a' : $textColor }};">${{ number_format($granted, 2) }}</strong>
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: #ffffff; font-weight: 600;">
                <td colspan="3" style="padding: 10px;">
                    Totals
                    @if($totalAvailable > 0)
                        <span class="badge ms-2" style="background: #16a34a; color: white;">${{ number_format($totalAvailable, 0) }} AVAILABLE</span>
                    @endif
                </td>
                <td class="text-end" style="padding: 10px;"></td>
                <td class="text-end" style="padding: 10px;">${{ number_format($totalUsed, 2) }}</td>
                <td class="text-end" style="padding: 10px;">
                    @if($totalMissed > 0)
                        <span style="color: #fbbf24;">${{ number_format($totalMissed, 2) }}</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-end" style="padding: 10px;">${{ number_format($totalGranted, 2) }}</td>
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
<div class="text-muted text-center p-4" style="background: #f8fafc; border-radius: 6px;">
    No matching rules configured.
</div>
@endif
