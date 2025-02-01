<div class="table-responsive-sm">
    <table class="table table-striped" id="cashDeposits-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Date</th>
                <th>Description</th>
                <th>Value</th>
                <th>Assigned</th>
                <th>Unassigned</th>
                <th>Status</th>
                <th>Fund Account Id</th>
                <th>Fund Account</th>
                <th>Transaction Id</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($cashDeposits as $cashDeposit)
            @php($unassigned = $cashDeposit->amount)
            @php($assigned = 0)
            @foreach($cashDeposit->depositRequests as $depositRequest)
                @php($unassigned -= $depositRequest->amount)
                @php($assigned += $depositRequest->amount)
            @endforeach
            <tr>
                <td>{{ $cashDeposit->id }}</td>
                <td>{{ $cashDeposit->date }}</td>
                <td>{{ $cashDeposit->description }}</td>
                <td>${{ number_format($cashDeposit->amount, 2) }}</td>
                <td>${{ number_format($assigned, 2) }}</td>
                <td>${{ number_format($unassigned, 2) }}</td>
                <td>{{ $cashDeposit->status_string() }}</td>
                <td>{{ $cashDeposit->account_id }}</td>
                <td>{{ $cashDeposit->account->nickname }}</td>
                <td>{{ $cashDeposit->transaction_id }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('cashDeposits.show', [$cashDeposit->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('cashDeposits.edit', [$cashDeposit->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        @if($cashDeposit->status != \App\Models\CashDepositExt::STATUS_COMPLETED && 
                        $cashDeposit->status != \App\Models\CashDepositExt::STATUS_CANCELLED)
                        <a href="{{ route('cashDeposits.assign', [$cashDeposit->id]) }}" class='btn btn-ghost-info'><i class="fa fa-link"></i></a>
                        @endif
                        <form action="{{ route('cashDeposits.destroy', $cashDeposit->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this cash deposit?')"><i class="fa fa-trash"></i></button>
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
        $('#cashDeposits-table').DataTable();
    });
</script>
@endpush