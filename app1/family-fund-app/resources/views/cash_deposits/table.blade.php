<div class="table-responsive-sm">
    <table class="table table-striped" id="cashDeposits-table">
        <thead>
            <tr>
                <th>Date</th>
        <th>Description</th>
        <th>Value</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($cashDeposits as $cashDeposit)
            <tr>
                <td>{{ $cashDeposit->date }}</td>
            <td>{{ $cashDeposit->description }}</td>
            <td>{{ $cashDeposit->value }}</td>
                <td>
                    {!! Form::open(['route' => ['cashDeposits.destroy', $cashDeposit->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('cashDeposits.show', [$cashDeposit->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('cashDeposits.edit', [$cashDeposit->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>