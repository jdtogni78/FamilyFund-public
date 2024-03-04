<div class="table-responsive-sm">
    <table class="table table-striped" id="fundReports-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Fund Id</th>
            <th>Type</th>
            <th>As Of</th>
            <th>Scheduled Job Id</th>
            <th>Created At</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($fundReports as $fundReport)
            <tr>
                <td>{{ $fundReport->id }}</td>
                <td>{{ $fundReport->fund_id }}</td>
                <td>{{ $fundReport->type }}</td>
                <td>{{ $fundReport->as_of }}</td>
                <td>{{ $fundReport->scheduled_job_id }}</td>
                <td>{{ $fundReport->created_at }}</td>
                <td>
                    {!! Form::open(['route' => ['fundReports.destroy', $fundReport->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('fundReports.show', [$fundReport->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('fundReports.edit', [$fundReport->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
