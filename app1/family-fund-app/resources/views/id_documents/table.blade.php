<div class="table-responsive-sm">
    <table class="table table-striped" id="idDocuments-table">
        <thead>
            <tr>
                <th>Person Id</th>
        <th>Type</th>
        <th>Number</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($idDocuments as $idDocument)
            <tr>
                <td>{{ $idDocument->person_id }}</td>
            <td>{{ $idDocument->type }}</td>
            <td>{{ $idDocument->number }}</td>
                <td>
                    <form action="{{ route('idDocuments.destroy', $idDocument->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('idDocuments.show', [$idDocument->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('idDocuments.edit', [$idDocument->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this id document?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>