@if(isset($api['matching_rules']) && count($api['matching_rules']) > 0)
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Name</th>
            <th>Period</th>
            <th class="col-number">Range</th>
            <th class="col-number">Match %</th>
            <th class="col-number">Used</th>
            <th class="col-number">Granted</th>
        </tr>
    </thead>
    <tbody>
    @foreach($api['matching_rules'] as $match)
        <tr>
            <td><strong>{{ $match['name'] }}</strong></td>
            <td>{{ $match['date_start'] }} - {{ $match['date_end'] }}</td>
            <td class="col-number">${{ number_format($match['dollar_range_start'] ?? 0, 0) }} - ${{ number_format($match['dollar_range_end'] ?? 0, 0) }}</td>
            <td class="col-number">{{ number_format($match['match_percent'] ?? 0, 0) }}%</td>
            <td class="col-number">${{ number_format($match['used'] ?? 0, 2) }}</td>
            <td class="col-number text-success"><strong>${{ number_format($match['granted'] ?? 0, 2) }}</strong></td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #f1f5f9; font-weight: 600;">
            <td colspan="4">Total Matching</td>
            <td class="col-number">${{ number_format(array_sum(array_column($api['matching_rules'], 'used')), 2) }}</td>
            <td class="col-number text-success">${{ number_format(array_sum(array_column($api['matching_rules'], 'granted')), 2) }}</td>
        </tr>
    </tfoot>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No matching rules configured.
</div>
@endif
