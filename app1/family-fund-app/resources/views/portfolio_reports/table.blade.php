<div class="table-responsive-sm">
    <table class="table table-striped" id="portfolioReports-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Portfolio</th>
            <th>Type</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Scheduled Job Id</th>
            <th>Created At</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($portfolioReports as $portfolioReport)
            <tr>
                <td>{{ $portfolioReport->id }}</td>
                <td>
                    @if($portfolioReport->portfolio)
                        <a href="{{ route('portfolios.show', $portfolioReport->portfolio_id) }}">
                            {{ $portfolioReport->portfolio->name }}
                        </a>
                    @else
                        {{ $portfolioReport->portfolio_id }}
                    @endif
                </td>
                <td>{{ $portfolioReport->report_type ?? 'custom' }}</td>
                <td>{{ $portfolioReport->start_date->format('Y-m-d') }}</td>
                <td>{{ $portfolioReport->end_date->format('Y-m-d') }}</td>
                <td>{{ $portfolioReport->scheduled_job_id }}</td>
                <td>{{ $portfolioReport->created_at }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('portfolioReports.show', [$portfolioReport->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('portfolioReports.edit', [$portfolioReport->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <a href="{{ route('portfolios.showRebalancePDF', [$portfolioReport->portfolio_id, $portfolioReport->start_date->format('Y-m-d'), $portfolioReport->end_date->format('Y-m-d')]) }}" class='btn btn-ghost-warning' title="View PDF"><i class="fa fa-file-pdf-o"></i></a>
                        <form action="{{ route('portfolioReports.destroy', $portfolioReport->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this portfolio report?')"><i class="fa fa-trash"></i></button>
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
        $('#portfolioReports-table').DataTable();
    });
</script>
