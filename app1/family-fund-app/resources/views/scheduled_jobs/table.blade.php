<div class="table-responsive-sm">
    <table class="table table-striped" id="scheduledJobs-table">
        <thead>
            <tr>
                <th>Schedule Id</th>
        <th>Entity Descr</th>
        <th>Entity Id</th>
        <th>Start Dt</th>
        <th>End Dt</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($scheduledJobs as $scheduledJob)
            <tr>
                <td>{{ $scheduledJob->schedule_id }}</td>
            <td>{{ $scheduledJob->entity_descr }}</td>
            <td>{{ $scheduledJob->entity_id }}</td>
            <td>{{ $scheduledJob->start_dt }}</td>
            <td>{{ $scheduledJob->end_dt }}</td>
                <td>
                    {!! Form::open(['route' => ['scheduledJobs.destroy', $scheduledJob->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('scheduledJobs.show', [$scheduledJob->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('scheduledJobs.edit', [$scheduledJob->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>