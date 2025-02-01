<div class="table-responsive-sm">
    <table class="table table-striped" id="changeLogs-table">
        <thead>
            <tr>
                <th>Object</th>
        <th>Content</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($changeLogs as $changeLog)
            <tr>
                <td>{{ $changeLog->object }}</td>
            <td>{{ $changeLog->content }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('changeLogs.show', [$changeLog->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('changeLogs.edit', [$changeLog->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('changeLogs.destroy', $changeLog->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this change log?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>