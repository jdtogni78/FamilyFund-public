<div class="table-responsive-sm">
    <table class="table table-striped" id="assets-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Source</th>
                <th>Name</th>
                <th>Type</th>
                <th>Display Group</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assets as $asset)
            <tr>
                <td>{{ $asset->id }}</td>
                <td>{{ $asset->source }}</td>
                <td>{{ $asset->name }}</td>
                <td>{{ $asset->type }}</td>
                <td>{{ $asset->display_group }}</td>
                <td>
                    <form action="{{ route('assets.destroy', $asset->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('assets.show', [$asset->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('assets.edit', [$asset->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this asset?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#assets-table').DataTable();
    });
</script>
@endpush