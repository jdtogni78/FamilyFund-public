<div class="table-responsive-sm">
    <table class="table table-striped" id="accountMatchingRules-table">
        <thead>
            <tr>
                <th>Fund</th>
                <th>Account</th>
                <th>Matching Rule</th>
                <th>Match %</th>
                <th>Period</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountMatchingRules as $accountMatchingRule)
            <tr>
                <td>
                    @if($accountMatchingRule->account && $accountMatchingRule->account->fund)
                        <a href="{{ route('funds.show', $accountMatchingRule->account->fund_id) }}">
                            {{ $accountMatchingRule->account->fund->name }}
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('accounts.show', $accountMatchingRule->account_id) }}">
                        {{ $accountMatchingRule->account->nickname }}
                    </a>
                    <br><small class="text-muted">{{ $accountMatchingRule->account->code }}</small>
                </td>
                <td>{{ $accountMatchingRule->matchingRule->name }}</td>
                <td>
                    <span class="badge bg-purple" style="background: #9333ea; color: white;">
                        {{ $accountMatchingRule->matchingRule->match_percent }}%
                    </span>
                </td>
                <td>
                    <small>
                        {{ \Carbon\Carbon::parse($accountMatchingRule->matchingRule->date_start)->format('M j, Y') }}
                        &mdash;
                        {{ $accountMatchingRule->matchingRule->date_end ? \Carbon\Carbon::parse($accountMatchingRule->matchingRule->date_end)->format('M j, Y') : 'Ongoing' }}
                    </small>
                </td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('accountMatchingRules.show', [$accountMatchingRule->id]) }}" class='btn btn-sm btn-ghost-success' title="View"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accountMatchingRules.edit', [$accountMatchingRule->id]) }}" class='btn btn-sm btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('accountMatchingRules.destroy', $accountMatchingRule->id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this account matching rule?')"><i class="fa fa-trash"></i></button>
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
        $('#accountMatchingRules-table').DataTable({
            order: [[0, 'asc'], [1, 'asc']],
            pageLength: 25
        });
    });
</script>
@endpush