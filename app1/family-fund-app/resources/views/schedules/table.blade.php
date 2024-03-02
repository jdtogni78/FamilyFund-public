<div class="table-responsive-sm">
    <table class="table table-striped" id="schedules-table">
        <thead>
            <tr>
                <th>Descr</th>
        <th>Type</th>
        <th>Value</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($schedules as $schedule)
            <tr>
                <td>{{ $schedule->descr }}</td>
            <td>{{ $schedule->type }}</td>
            <td>{{ $schedule->value }}</td>
                <td>
                    {!! Form::open(['route' => ['schedules.destroy', $schedule->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('schedules.show', [$schedule->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('schedules.edit', [$schedule->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>