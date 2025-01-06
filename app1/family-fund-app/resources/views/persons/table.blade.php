<div class="table-responsive">
    <table class="table table-striped" id="persons-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Birthday</th>
                <th>Primary Phone</th>
                <th>Primary Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($persons as $person)
            <tr>
                <td>{{ $person->full_name }}</td>
                <td>{{ $person->email }}</td>
                <td>{{ $person->birthday ? $person->birthday->format('Y-m-d') : '' }}</td>
                <td>{{ optional($person->phones->where('is_primary', true)->first())->number }}</td>
                <td>{{ optional($person->primaryAddress)->street }}</td>
                <td>
                    {!! Form::open(['route' => ['persons.destroy', $person->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('persons.show', [$person->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('persons.edit', [$person->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div> 

@push('scripts')
<script>
    $(document).ready(function() {
        $('#persons-table').DataTable();
    });
</script>
@endpush