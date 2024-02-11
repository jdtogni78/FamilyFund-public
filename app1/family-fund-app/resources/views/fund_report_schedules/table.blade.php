<div class="table-responsive-sm">
    <table class="table table-striped" id="fundReportSchedules-table">
        <thead>
            <tr>
                <th>Fund Report Id</th>
        <th>Schedule Id</th>
        <th>Start Dt</th>
        <th>End Dt</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($fundReportSchedules as $fundReportSchedule)
            <tr>
                <td>{{ $fundReportSchedule->fund_report_id }}</td>
            <td>{{ $fundReportSchedule->schedule_id }}</td>
            <td>{{ $fundReportSchedule->start_dt }}</td>
            <td>{{ $fundReportSchedule->end_dt }}</td>
                <td>
                    {!! Form::open(['route' => ['fundReportSchedules.destroy', $fundReportSchedule->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('fundReportSchedules.show', [$fundReportSchedule->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('fundReportSchedules.edit', [$fundReportSchedule->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>