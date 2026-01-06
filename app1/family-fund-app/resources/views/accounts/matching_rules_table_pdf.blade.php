@if(isset($api['matching_rules']) && count($api['matching_rules']) > 0)
@php
    $now = \Carbon\Carbon::now();
    $totalUsed = 0;
    $totalGranted = 0;
    $totalMissed = 0;
@endphp
<table style="width: 100%; font-size: 11px;">
    <thead>
        <tr>
            <th style="width: 80px;">Status</th>
            <th>Period</th>
            <th class="col-number">Match %</th>
            <th class="col-number">Available</th>
            <th class="col-number">Used</th>
            <th class="col-number">Missed</th>
            <th class="col-number">Granted</th>
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
            } elseif ($isUpcoming) {
                $status = 'Upcoming';
                $statusColor = '#2563eb';
                $statusBg = '#dbeafe';
                $rowBg = '#eff6ff';
                $textColor = '#000000';
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

            // Format period - show year if within same year
            $periodDisplay = $startDate->format('Y');

            $totalUsed += $used;
            $totalGranted += $granted;
            $totalMissed += $missed;
        @endphp
        <tr style="background: {{ $rowBg }}; color: {{ $textColor }};">
            <td style="padding: 8px;">
                <span style="background: {{ $statusBg }}; color: {{ $statusColor }}; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 10px;">
                    {{ $status }}
                </span>
            </td>
            <td style="padding: 8px; font-weight: {{ $isActive ? '700' : '400' }};">{{ $periodDisplay }}</td>
            <td class="col-number" style="padding: 8px;">{{ number_format($match['match_percent'] ?? 0, 0) }}% up to ${{ number_format($available, 0) }}</td>
            <td class="col-number" style="padding: 8px;">${{ number_format($available, 0) }}</td>
            <td class="col-number" style="padding: 8px;">${{ number_format($used, 2) }}</td>
            <td class="col-number" style="padding: 8px;">
                @if($missed > 0)
                    <span style="color: #d97706;">${{ number_format($missed, 2) }}</span>
                @else
                    -
                @endif
            </td>
            <td class="col-number" style="padding: 8px;">
                <strong style="color: {{ $granted > 0 ? '#16a34a' : $textColor }};">${{ number_format($granted, 2) }}</strong>
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #1e40af; color: #ffffff; font-weight: 600;">
            <td colspan="4" style="padding: 10px;">Total Matching Received</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($totalUsed, 2) }}</td>
            <td class="col-number" style="padding: 10px;">
                @if($totalMissed > 0)
                    <span style="color: #fbbf24;">${{ number_format($totalMissed, 2) }}</span>
                @else
                    -
                @endif
            </td>
            <td class="col-number" style="padding: 10px;">${{ number_format($totalGranted, 2) }}</td>
        </tr>
    </tfoot>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No matching rules configured.
</div>
@endif
