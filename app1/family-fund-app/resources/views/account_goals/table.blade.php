<div class="table-responsive-sm">
    <table class="table table-striped" id="accountGoals-table">
        <thead>
            <tr>
                <th>Account Id</th>
                <th>Account Name</th>
                <th>Goal Id</th>
                <th>Goal Name</th>
                <th>Target Type</th>
                <th>Target Amount</th>
                <th>Target Percentage</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountGoals as $accountGoal)
            <tr>
                <td>{{ $accountGoal->account_id }}</td>
                <td>{{ $accountGoal->account->name }}</td>
                <td>{{ $accountGoal->goal_id }}</td>
                <td>{{ $accountGoal->goal->name }}</td>
                <td>{{ $accountGoal->goal->target_type }}</td>
                <td>{{ $accountGoal->goal->target_amount }}</td>
                <td>{{ $accountGoal->goal->target_percentage }}</td>
                <td>{{ $accountGoal->goal->start_dt }}</td>
                <td>{{ $accountGoal->goal->end_dt }}</td>
                <td>
                    <form action="{{ route('accountGoals.destroy', $accountGoal->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('accountGoals.show', [$accountGoal->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('accountGoals.edit', [$accountGoal->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this account goal?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#accountGoals-table').DataTable();
    });
</script>