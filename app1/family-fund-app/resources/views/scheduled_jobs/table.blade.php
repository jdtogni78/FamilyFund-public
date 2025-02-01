<div class="table-responsive-sm">
    <table class="table table-striped" id="scheduledJobs-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Schedule Id</th>
            <th>Type</th>
            <th>Value</th>
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
                <td>{{ $scheduledJob->id }}</td>
                <td>{{ $scheduledJob->schedule_id }}</td>
                <td>{{ $scheduledJob->schedule()->first()->type }}</td>
                <td>{{ $scheduledJob->schedule()->first()->value }}</td>
                <td>{{ $scheduledJob->entity_descr }}</td>
                <td>{{ $scheduledJob->entity_id }}</td>
                <td>{{ $scheduledJob->start_dt }}</td>
                <td>{{ $scheduledJob->end_dt }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('scheduledJobs.show', [$scheduledJob->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('scheduledJobs.edit', [$scheduledJob->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('scheduledJobs.preview', ['id' => $scheduledJob->id, 'asOf' => new Carbon\Carbon()]) }}" class="btn btn-ghost-success"><i class="fa fa-play"></i></a>
                        <form action="{{ route('scheduledJobs.destroy', $scheduledJob->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this scheduled job?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
