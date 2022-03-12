<div class="table-responsive-sm">
    <table class="table table-striped" id="accountMatchingRules-table">
        <thead>
            <tr>
                <th>Account Id</th>
        <th>Matching Rule Id</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountMatchingRules as $accountMatchingRule)
            <tr>
                <td>{{ $accountMatchingRule->account_id }}</td>
            <td>{{ $accountMatchingRule->matching_rule_id }}</td>
                <td>
                    {!! Form::open(['route' => ['accountMatchingRules.destroy', $accountMatchingRule->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('accountMatchingRules.show', [$accountMatchingRule->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accountMatchingRules.edit', [$accountMatchingRule->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>