<div class="table-responsive-sm">
    <table class="table table-striped" id="funds-table">
        <thead>
            <tr>
                <th>Period</th>
                <th>Performance</th>
                <th>Shares</th>
                <th>Total Value</th>
                <th>Share Price</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api[$performance_key] as $period => $perf)
            @php
                $perfValue = floatval($perf['performance']);
                $perfClass = $perfValue >= 0 ? 'text-success' : 'text-danger';
            @endphp
            <tr>
                <td>{{ $period }}</td>
                <td class="{{ $perfClass }}" style="font-weight: 600;">
                    {{ $perfValue >= 0 ? '+' : '' }}{{ number_format($perfValue, 2) }}%
                </td>
                <td>{{ number_format($perf['shares'], 2) }}</td>
                <td>${{ number_format($perf['value'], 2) }}</td>
                <td>${{ number_format($perf['share_value'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
