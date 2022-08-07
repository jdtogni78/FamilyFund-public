<div class="table-responsive-sm">
    <table class="table table-striped" id="funds-table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Start $</th>
            <th>End $</th>
            <th>Match %</th>
            <th>Used</th>
            <th>Granted</th>
        </tr>
        </thead>
        <tbody>
        @foreach($api['matching_rules'] as $match)
            <tr>
                <td>{{ $match['name'] }}</td>
                <td>{{ $match['date_start'] }}</td>
                <td>{{ $match['date_end'] }}</td>
                <td>$ {{ $match['dollar_range_start'] }}</td>
                <td>$ {{ $match['dollar_range_end'] }}</td>
                <td>{{ $match['match_percent'] }}%</td>
                <td>$ {{ $match['used'] }}</td>
                <td>$ {{ $match['granted'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
