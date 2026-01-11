<div class="table-responsive-sm">
    <table class="table table-striped" id="accountReports-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Account</th>
                <th>Fund</th>
                <th>User</th>
                <th>Type</th>
                <th>As Of</th>
                <th>Created</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountReports as $accountReport)
            @php
                $account = $accountReport->account;
                $fund = $account?->fund;
                $user = $account?->user;
            @endphp
            <tr>
                <td>{{ $accountReport->id }}</td>
                <td>
                    @if($account)
                        <a href="{{ route('accounts.show', $account->id) }}">
                            {{ $account->nickname ?? $account->code }}
                        </a>
                    @else
                        {{ $accountReport->account_id }}
                    @endif
                </td>
                <td>
                    @if($fund)
                        <a href="{{ route('funds.show', $fund->id) }}">
                            {{ $fund->name }}
                        </a>
                    @else
                        -
                    @endif
                </td>
                <td>{{ $user?->name ?? '-' }}</td>
                <td>
                    <span class="badge bg-info">{{ $accountReport->type }}</span>
                </td>
                <td>{{ $accountReport->as_of?->format('Y-m-d') }}</td>
                <td>{{ $accountReport->created_at?->format('Y-m-d H:i') }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('accountReports.show', [$accountReport->id]) }}" class='btn btn-ghost-success' title="View Details"><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accountReports.edit', [$accountReport->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('accountReports.destroy', $accountReport->id) }}" method="DELETE" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this account report?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
