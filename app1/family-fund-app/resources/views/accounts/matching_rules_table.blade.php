<div class="table-responsive-sm">
    <table class="table table-striped table-hover" id="matching-rules-table">
        <thead class="table-light">
        <tr>
            <th>Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th class="text-end">Start $</th>
            <th class="text-end">End $</th>
            <th class="text-end">Match %</th>
            <th class="text-end">Used</th>
            <th class="text-end">Granted</th>
        </tr>
        </thead>
        <tbody>
        @foreach($api['matching_rules'] as $match)
            <tr>
                <td>{{ $match['name'] }}</td>
                <td>{{ $match['date_start'] }}</td>
                <td>{{ $match['date_end'] }}</td>
                <td class="text-end">${{ number_format($match['dollar_range_start'], 2) }}</td>
                <td class="text-end">${{ number_format($match['dollar_range_end'], 2) }}</td>
                <td class="text-end">{{ $match['match_percent'] }}%</td>
                <td class="text-end">${{ number_format($match['used'], 2) }}</td>
                <td class="text-end" style="color: #16a34a; font-weight: bold;">${{ number_format($match['granted'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#matching-rules-table').DataTable({
            order: [[1, 'desc']], // Sort by start date descending
            paging: false,
            searching: false,
            info: false
        });
    });
</script>
