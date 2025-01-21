<div class="table-responsive-sm">
    <table class="table table-striped" id="accountGoals-table">
        <thead>
            <tr>
                <th>Account Id</th>
        <th>Goal Id</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountGoals as $accountGoal)
            <tr>
                <td>{{ $accountGoal->account_id }}</td>
            <td>{{ $accountGoal->goal_id }}</td>
                <td>
                    {!! Form::open(['route' => ['accountGoals.destroy', $accountGoal->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('accountGoals.show', [$accountGoal->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accountGoals.edit', [$accountGoal->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>