<div class="table-responsive-sm">
    <table class="table table-striped" id="reportSchedules-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Descr</th>
            <th>Type</th>
            <th>Value</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($reportSchedules as $reportSchedule)
            <tr>
                <td>{{ $reportSchedule->id }}</td>
                <td>{{ $reportSchedule->descr }}</td>
                <td>{{ $reportSchedule->type }}</td>
                <td>{{ $reportSchedule->value }}</td>
                <td>
                    {!! Form::open(['route' => ['reportSchedules.destroy', $reportSchedule->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('reportSchedules.show', [$reportSchedule->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('reportSchedules.edit', [$reportSchedule->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
