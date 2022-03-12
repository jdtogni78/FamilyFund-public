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
        @foreach($api['monthly_performance'] as $period => $perf)
            <tr>
                <td>{{ $period }}</td>
                <td>{{ $perf['performance'] }} %</td>
                <td>{{ $perf['shares'] }}</td>
                <td>$ {{ $perf['value'] }}</td>
                <td>$ {{ $perf['share_value'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
