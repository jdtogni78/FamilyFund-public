<div class="table-responsive-sm">
    <table class="table table-striped" id="assetChangeLogs-table">
        <thead>
            <tr>
                <th>Action</th>
        <th>Asset Id</th>
        <th>Field</th>
        <th>Content</th>
        <th>Datetime</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assetChangeLogs as $assetChangeLog)
            <tr>
                <td>{{ $assetChangeLog->action }}</td>
            <td>{{ $assetChangeLog->asset_id }}</td>
            <td>{{ $assetChangeLog->field }}</td>
            <td>{{ $assetChangeLog->content }}</td>
            <td>{{ $assetChangeLog->datetime }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('assetChangeLogs.show', [$assetChangeLog->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('assetChangeLogs.edit', [$assetChangeLog->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('assetChangeLogs.destroy', $assetChangeLog->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this asset change log?')"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>