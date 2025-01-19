<div class="table-responsive-sm">
    <table class="table table-striped" id="depositRequests-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Account Id</th>
                <th>Account</th>
                <th>Cash Deposit Id</th>
                <th>Transaction Id</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($depositRequests as $depositRequest)
            <tr>
            <td>{{ $depositRequest->date ? $depositRequest->date->format('Y-m-d') : 'N/A' }}</td>
            <td>{{ $depositRequest->description }}</td>
            <td>${{ number_format($depositRequest->amount, 2) }}</td>
            <td>{{ $depositRequest->status_string() }}</td>
            <td>{{ $depositRequest->account_id }}</td>
            <td>{{ $depositRequest->account->nickname }}</td>
            <td>{{ $depositRequest->cash_deposit_id }}</td>
            <td>{{ $depositRequest->transaction_id }}</td>
                <td>
                    {!! Form::open(['route' => ['depositRequests.destroy', $depositRequest->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('depositRequests.show', [$depositRequest->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('depositRequests.edit', [$depositRequest->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#depositRequests-table').DataTable();
        });
    </script>
@endpush
