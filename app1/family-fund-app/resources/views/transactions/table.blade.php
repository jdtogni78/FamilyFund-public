<div class="table-responsive-sm">
    <table class="table table-striped" id="transactions-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Type</th>
            <th>Status</th>
            <th>Value</th>
            <th>Shares</th>
            <th>Timestamp</th>
            <th>Account Id</th>
            <th>Account Nickname</th>
            <th>Descr</th>
            <th>Flags</th>
            <th>Matched by</th>
            <th>Scheduled Job Id</th>

            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->type_string() }}</td>
                <td>{{ $transaction->status_string() }}</td>
                <td>{{ $transaction->value }}</td>
                <td>{{ $transaction->shares }}</td>
                <td>{{ $transaction->timestamp }}</td>
                <td>{{ $transaction->account_id }}</td>
                <td>
                    <a href="{{ route('accounts.show', $transaction->account_id) }}" class="text-success">
                        <i class="fa fa-eye me-1"></i>{{ $transaction->account->nickname }}
                    </a>
                </td>
                <td>{{ $transaction->descr }}</td>
                <td>{{ $transaction->flags }}</td>
                <td>{{ $transaction->referenceTransactionMatching?->transaction_id }}</td>
                <td>{{ $transaction->scheduled_job_id }}</td>
                <td>
                    <form action="{{ route('transactions.destroy', $transaction->id) }}" method="DELETE">
                        <div class='btn-group'>
                            <a href="{{ route('transactions.show', [$transaction->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('transactions.edit', [$transaction->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <a href="{{ route('transactions.clone', [$transaction->id]) }}" class='btn btn-ghost-warning' title="Clone"><i class="fa fa-copy"></i></a>
                            @if($transaction->status == \App\Models\TransactionExt::STATUS_PENDING)
                                <a href="{{ route('transactions.preview_pending', [$transaction->id]) }}" class='btn btn-ghost-primary'><i class="fa fa-play"></i></a>
                            @endif
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this transaction?')"><i class="fa fa-trash"></i></button>
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
        $('#transactions-table').DataTable({
            order: [[5, 'desc']]  // Sort by Timestamp column descending
        });
    });
</script>
@endpush
