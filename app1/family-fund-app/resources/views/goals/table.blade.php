<div class="table-responsive-sm">
    <table class="table table-striped" id="goals-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Start Dt</th>
                <th>End Dt</th>
                <th>Target Type</th>
                <th>Target Amount</th>
                <th>Target Percentage</th>
                <th>Accounts</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($goals as $goal)
                <tr>
                    <td>{{ $goal->name }}</td>
                    <td>{{ $goal->description }}</td>
                    <td>{{ $goal->start_dt }}</td>
                    <td>{{ $goal->end_dt }}</td>
                    <td>{{ $goal->target_type }}</td>
                    <td>{{ $goal->target_amount }}</td>
                    <td>{{ $goal->target_pct }}</td>
                    <td>{{ $goal->accounts->pluck('nickname')->join(', ') }}</td>
                    <td>
                        <div class='btn-group'>
                            <a href="{{ route('goals.show', [$goal->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('goals.edit', [$goal->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <form action="{{ route('goals.destroy', $goal->id) }}" method="DELETE">
                                <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this goal?')"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#goals-table').DataTable();
        });
    </script>
@endpush