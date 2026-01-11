@php
    $data = $api[$performance_key] ?? [];
    $periods = array_keys($data);
    $lastPeriod = end($periods);
@endphp

<div class="table-responsive-sm">
    <table class="table table-hover" id="performance-table-{{ $performance_key }}">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-end">Performance</th>
                <th class="text-end">Value Chg</th>
                <th class="text-end">Shares</th>
                <th class="text-end">Total Value</th>
                <th class="text-end">Share Price</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $period => $perf)
            @php
                $perfValue = floatval($perf['performance'] ?? 0);
                $perfClass = $perfValue >= 0 ? 'text-success' : 'text-danger';
                $valueChange = floatval($perf['value_change'] ?? 0);
                $valueChangeClass = $valueChange >= 0 ? 'text-success' : 'text-danger';
                $isLast = $period === $lastPeriod;
            @endphp
            <tr class="{{ $isLast ? 'table-info' : '' }}">
                <td>
                    @if($isLast)
                        <strong>{{ $period }}</strong>
                    @else
                        {{ $period }}
                    @endif
                </td>
                <td class="text-end">
                    <span class="{{ $perfClass }}" style="font-weight: {{ $isLast ? 'bold' : 'normal' }};">
                        @if($perfValue >= 0)+@endif{{ number_format($perfValue, 2) }}%
                    </span>
                </td>
                <td class="text-end">
                    <span class="{{ $valueChangeClass }}">
                        @if($valueChange >= 0)+@endif{{ number_format($valueChange, 2) }}%
                    </span>
                </td>
                <td class="text-end">{{ number_format($perf['shares'], 2) }}</td>
                <td class="text-end">
                    <strong>${{ number_format($perf['value'], 2) }}</strong>
                </td>
                <td class="text-end">${{ number_format($perf['share_value'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#performance-table-{{ $performance_key }}').DataTable({
            order: [[0, 'desc']], // Sort by period descending
            paging: false,
            searching: false,
            info: false
        });
    });
</script>
