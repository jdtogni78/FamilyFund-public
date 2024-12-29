<div class="table-responsive-sm">
    <table class="table table-striped" id="funds-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Name</th>
                <th class="no_mobile">Goal</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($funds as $fund)
            <tr>
                <td>{{ $fund->id }}</td>
                <td>{{ $fund->name }}</td>
                <td class="no_mobile">{{ $fund->goal }}</td>
                <td>
                    {!! Form::open(['route' => ['funds.destroy', $fund->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('funds.show', [$fund->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('funds.show_trade_bands', [$fund->id]) }}" class='btn btn-ghost-success'><i class="fa fa-wave-square"></i></a>
                        <a href="{{ route('funds.edit', [$fund->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('portfolios.show', [$fund->portfolios()->first()]) }}" class='btn btn-ghost-info'><i class="fa fa-eye"></i></a>
{{--                    {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}--}}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
