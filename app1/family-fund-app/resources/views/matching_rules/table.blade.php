<div class="table-responsive-sm">
    <table class="table table-striped" id="matchingRules-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Dollar Range Start</th>
                <th>Dollar Range End</th>
                <th>Date Start</th>
                <th>Date End</th>
                <th>Match Percent</th>
                <th>Account Nicknames</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($matchingRules as $matchingRule)
            <tr>
                <td>{{ $matchingRule->id }}</td>
                <td>{{ $matchingRule->name }}</td>
                <td>{{ $matchingRule->dollar_range_start }}</td>
                <td>{{ $matchingRule->dollar_range_end }}</td>
                <td>{{ $matchingRule->date_start }}</td>
                <td>{{ $matchingRule->date_end }}</td>
                <td>{{ $matchingRule->match_percent }}</td>
                <td>{{ $matchingRule->accountMatchingRules->pluck('account.nickname')->implode(', ') }}</td>
                <td>
                    <form action="{{ route('matchingRules.destroy', $matchingRule->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('matchingRules.show', [$matchingRule->id]) }}" class='btn btn-ghost-success' title="View"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('matchingRules.edit', [$matchingRule->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                            <a href="{{ route('matchingRules.clone', [$matchingRule->id]) }}" class='btn btn-ghost-warning' title="Clone"><i class="fa fa-copy"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this matching rule?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#matchingRules-table').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: -1 }  // Disable sorting on Action column
            ]
        });
    });
</script>
@endpush