<div class="table-responsive-sm">
    <table class="table table-striped" id="depositRequests-table">
        <thead>
            <tr>
                <th>Date</th>
        <th>Description</th>
        <th>Status</th>
        <th>Account Id</th>
        <th>Cash Deposit Id</th>
        <th>Transaction Id</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($depositRequests as $depositRequest)
            <tr>
                <td>{{ $depositRequest->date }}</td>
            <td>{{ $depositRequest->description }}</td>
            <td>{{ $depositRequest->status }}</td>
            <td>{{ $depositRequest->account_id }}</td>
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