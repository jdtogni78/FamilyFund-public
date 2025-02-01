<div class="table-responsive-sm">
    <table class="table table-striped" id="accountMatchingRules-table">
        <thead>
            <tr>
                <th>Account Id</th>
                <th>Account Nickname</th>
                <th>MR Id</th>
                <th>MR Name</th>
                <th>MR Period</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountMatchingRules as $accountMatchingRule)
            <tr>
                <td>{{ $accountMatchingRule->account_id }}</td>
                <td>{{ $accountMatchingRule->account->nickname }}</td>
                <td>{{ $accountMatchingRule->matching_rule_id }}</td>
                <td>{{ $accountMatchingRule->matchingRule->name }}</td>
                <td>{{ $accountMatchingRule->matchingRule->date_start }} - {{ $accountMatchingRule->matchingRule->date_end }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('accountMatchingRules.show', [$accountMatchingRule->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accountMatchingRules.edit', [$accountMatchingRule->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('accountMatchingRules.destroy', $accountMatchingRule->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this account matching rule?')"><i class="fa fa-trash"></i></button>
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
        $('#accountMatchingRules-table').DataTable();
    });
</script>
@endpush