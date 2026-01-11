<div class="table-responsive-sm">
    <table class="table table-striped" id="accountBalances-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Type</th>
                <th>Previous Id</th>
                <th>Previous Shares</th>
                <th>Shares</th>
                <th>Account Id</th>
                <th>Account Nickname</th>
                <th>Transaction Id</th>
                <th>Start Dt</th>
                <th>End Dt</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountBalances as $accountBalance)
            <tr>
                <td>{{ $accountBalance->id }}</td>
                <td>{{ $accountBalance->type }}</td>
                <td>{{ $accountBalance->previousBalance?->id }}</td>
                <td>{{ $accountBalance->previousBalance?->shares }}</td>
                <td>{{ $accountBalance->shares }}</td>
                <td>{{ $accountBalance->account_id }}</td>
                <td>{{ $accountBalance->account->nickname }}</td>
                <td>{{ $accountBalance->transaction_id }}</td>
                <td>{{ $accountBalance->start_dt->format('Y-m-d') }}</td>
                <td>{{ $accountBalance->end_dt->format('Y-m-d') }}</td>
                <td>
                    <form action="{{ route('accountBalances.destroy', $accountBalance->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('accountBalances.show', [$accountBalance->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('accountBalances.edit', [$accountBalance->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this account balance?')"><i class="fa fa-trash"></i></button>
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
        $('#accountBalances-table').DataTable();
    });
</script>
@endpush
