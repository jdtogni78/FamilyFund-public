@if(isset($api[$performance_key]) && count($api[$performance_key]) > 0)
<table class="table-striped" style="width: 100%;">
    <thead>
        <tr>
            <th>Period</th>
            <th class="col-number">Performance</th>
            <th class="col-number">Shares</th>
            <th class="col-number">Total Value</th>
            <th class="col-number">Share Price</th>
        </tr>
    </thead>
    <tbody>
    @foreach($api[$performance_key] as $period => $perf)
        <tr>
            <td>{{ $period }}</td>
            <td class="col-number {{ ($perf['performance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                {{ number_format($perf['performance'] ?? 0, 2) }}%
            </td>
            <td class="col-number">{{ number_format($perf['shares'] ?? 0, 2) }}</td>
            <td class="col-number">${{ number_format($perf['value'] ?? 0, 2) }}</td>
            <td class="col-number">${{ number_format($perf['share_value'] ?? 0, 4) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No performance data available for this period.
</div>
@endif
