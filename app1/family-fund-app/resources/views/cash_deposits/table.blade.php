<div class="table-responsive-sm">
    <table class="table table-striped" id="cashDeposits-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Date</th>
        <th>Description</th>
        <th>Value</th>
        <th>Status</th>
        <th>Account Id</th>
        <th>Fund Account</th>
        <th>Transaction Id</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($cashDeposits as $cashDeposit)
            <tr>
                <td>{{ $cashDeposit->id }}</td>
                <td>{{ $cashDeposit->date }}</td>
            <td>{{ $cashDeposit->description }}</td>
            <td>{{ $cashDeposit->amount }}</td>
            <td>{{ $cashDeposit->status }}</td>
            <td>{{ $cashDeposit->account_id }}</td>
            <td>{{ $api['fundAccountMap'][$cashDeposit->account_id] }}</td>
            <td>{{ $cashDeposit->transaction_id }}</td>
                <td>
                    {!! Form::open(['route' => ['cashDeposits.destroy', $cashDeposit->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('cashDeposits.show', [$cashDeposit->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('cashDeposits.edit', [$cashDeposit->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        @if($cashDeposit->status != CashDepositExt::STATUS_COMPLETED && $cashDeposit->status != CashDepositExt::STATUS_CANCELLED)
                        <a href="{{ route('cashDeposits.assign', [$cashDeposit->id]) }}" class='btn btn-ghost-info'><i class="fa fa-link"></i></a>
                        @endif
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>