<div class="table-responsive-sm">
    <table class="table table-striped" id="tradeBandReports-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Fund</th>
            <th>As Of</th>
            <th>Scheduled Job Id</th>
            <th>Created At</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tradeBandReports as $tradeBandReport)
            <tr>
                <td>{{ $tradeBandReport->id }}</td>
                <td>{{ $tradeBandReport->fund->name ?? $tradeBandReport->fund_id }}</td>
                <td>{{ $tradeBandReport->as_of->format('Y-m-d') }}</td>
                <td>{{ $tradeBandReport->scheduled_job_id }}</td>
                <td>{{ $tradeBandReport->created_at }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('tradeBandReports.show', [$tradeBandReport->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('tradeBandReports.viewPdf', [$tradeBandReport->id]) }}" class='btn btn-ghost-primary' title="View PDF"><i class="fa fa-file-pdf-o"></i></a>
                        <a href="{{ route('tradeBandReports.edit', [$tradeBandReport->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('tradeBandReports.destroy', $tradeBandReport->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this trade band report?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
