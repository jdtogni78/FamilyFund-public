<div class="table-responsive-sm">
    <table class="table table-striped" id="phones-table">
        <thead>
            <tr>
                <th>Person Id</th>
        <th>Number</th>
        <th>Type</th>
        <th>Is Primary</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($phones as $phone)
            <tr>
                <td>{{ $phone->person_id }}</td>
            <td>{{ $phone->number }}</td>
            <td>{{ $phone->type }}</td>
            <td>{{ $phone->is_primary }}</td>
                <td>
                    {!! Form::open(['route' => ['phones.destroy', $phone->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('phones.show', [$phone->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('phones.edit', [$phone->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>