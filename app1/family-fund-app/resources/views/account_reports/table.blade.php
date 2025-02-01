<div class="table-responsive-sm">
    <table class="table table-striped" id="accountReports-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Account Id</th>
                <th>Type</th>
                <th>As Of</th>
                <th>Created At</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($accountReports as $accountReport)
            <tr>
                <td>{{ $accountReport->id }}</td>
                <td>{{ $accountReport->account_id }}</td>
                <td>{{ $accountReport->type }}</td>
                <td>{{ $accountReport->as_of }}</td>
                <td>{{ $accountReport->created_at }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('accountReports.show', [$accountReport->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('accountReports.edit', [$accountReport->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('accountReports.destroy', $accountReport->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this account report?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
