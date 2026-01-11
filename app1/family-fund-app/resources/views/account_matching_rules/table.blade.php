<div class="table-responsive-sm">
    <table class="table table-striped" id="accountMatchingRules-table">
        <thead>
            <tr>
                <th>Account Id</th>
                <th>Matching Rule Id</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountMatchingRules as $accountMatchingRule)
            <tr>
                <td>{{ $accountMatchingRule->account_id }}</td>
                <td>{{ $accountMatchingRule->matching_rule_id }}</td>
                <td>
                    <form action="{{ route('accountMatchingRules.destroy', $accountMatchingRule->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('accountMatchingRules.show', [$accountMatchingRule->id]) }}" class='btn btn-ghost-success' title="View"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('accountMatchingRules.edit', [$accountMatchingRule->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                            <a href="{{ route('accountMatchingRules.resend-email', [$accountMatchingRule->id]) }}" class='btn btn-ghost-primary' title="Resend Email" onclick="return confirm('Send email notification to {{ $accountMatchingRule->account->email_cc ?? "account" }}?')"><i class="fa fa-envelope"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this account matching rule?')"><i class="fa fa-trash"></i></button>
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
        $('#accountMatchingRules-table').DataTable();
    });
</script>
@endpush