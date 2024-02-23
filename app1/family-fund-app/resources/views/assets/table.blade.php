<div class="table-responsive-sm">
    <table class="table table-striped" id="assets-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Source</th>
                <th>Name</th>
                <th>Type</th>
                <th>Display Group</th>
                <th colspan="3">Action</th>
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
                    {!! Form::open(['route' => ['assets.destroy', $asset->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('assets.show', [$asset->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('assets.edit', [$asset->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
