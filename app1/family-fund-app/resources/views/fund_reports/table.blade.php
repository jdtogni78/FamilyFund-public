<div class="table-responsive-sm">
    <table class="table table-striped" id="fundReports-table">
        <thead>
        <tr>
            <th>Id</th>
            <th>Fund</th>
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
                <td>{{ $fundReport->fund->name ?? 'N/A' }}</td>
                <td><span class="badge bg-{{ $fundReport->type === 'ADM' ? 'warning' : 'info' }}">{{ $fundReport->type }}</span></td>
                <td>
                    @if($fundReport->as_of->format('Y-m-d') === '9999-12-31')
                        <span class="badge bg-info text-white">Template</span>
                    @else
                        {{ $fundReport->as_of->format('Y-m-d') }}
                    @endif
                </td>
                <td>{{ $fundReport->scheduled_job_id }}</td>
                <td>{{ $fundReport->created_at }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('fundReports.show', [$fundReport->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('fundReports.edit', [$fundReport->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('fundReports.destroy', $fundReport->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this fund report?')"><i class="fa fa-trash"></i></button>
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
        $('#fundReports-table').DataTable();
    });
</script>
