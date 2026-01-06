@php $tableId = 'perf-table-' . str_replace('_', '-', $performance_key); @endphp
<div class="table-responsive-sm">
    <table class="table table-striped" id="{{ $tableId }}">
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
                <td class="{{ $perfClass }}" style="font-weight: 600;" data-order="{{ $perfValue }}">
                    {{ $perfValue >= 0 ? '+' : '' }}{{ number_format($perfValue, 2) }}%
                </td>
                <td data-order="{{ $perf['shares'] }}">{{ number_format($perf['shares'], 2) }}</td>
                <td data-order="{{ $perf['value'] }}">${{ number_format($perf['value'], 2) }}</td>
                <td data-order="{{ $perf['share_value'] }}">${{ number_format($perf['share_value'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#{{ $tableId }}').DataTable({
        order: [[0, 'desc']], // Sort by Period descending
        paging: false,
        searching: false,
        info: false
    });
});
</script>
@endpush
