<div class="table-responsive-sm">
    <table class="table table-striped" id="tradeBandReports-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Fund</th>
            <th>As Of</th>
            <th>Scheduled Job Id</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tradeBandReports as $tradeBandReport)
            <tr>
                <td>{{ $tradeBandReport->id }}</td>
                <td>{{ $tradeBandReport->fund->name ?? $tradeBandReport->fund_id }}</td>
                <td>
                    @if($tradeBandReport->as_of && $tradeBandReport->as_of->format('Y') !== '9999')
                        {{ $tradeBandReport->as_of->format('Y-m-d') }}
                    @else
                        <span class="badge bg-info text-white">Template</span>
                    @endif
                </td>
                <td>{{ $tradeBandReport->scheduled_job_id }}</td>
                <td>{{ $tradeBandReport->created_at }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('tradeBandReports.show', [$tradeBandReport->id]) }}" class='btn btn-ghost-success' title="View Details"><i class="fa fa-eye"></i></a>
                        @if($tradeBandReport->as_of && $tradeBandReport->as_of->format('Y') !== '9999')
                            <a href="{{ route('tradeBandReports.viewPdf', [$tradeBandReport->id]) }}" class='btn btn-ghost-primary' title="View PDF"><i class="fa fa-file-pdf"></i></a>
                            <form action="{{ route('tradeBandReports.resend', $tradeBandReport->id) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-ghost-warning" title="Resend email" onclick="return confirm('Resend this report via email?')"><i class="fa fa-paper-plane"></i></button>
                            </form>
                        @else
                            <button class='btn btn-ghost-secondary' disabled title="No valid date for PDF"><i class="fa fa-file-pdf"></i></button>
                        @endif
                        <a href="{{ route('tradeBandReports.edit', [$tradeBandReport->id]) }}" class='btn btn-ghost-info' title="Edit"><i class="fa fa-edit"></i></a>
                        <form action="{{ route('tradeBandReports.destroy', $tradeBandReport->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this trade band report?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function() {
        $('#tradeBandReports-table').DataTable();
    });
</script>
