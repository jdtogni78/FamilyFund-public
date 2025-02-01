<div class="table-responsive-sm">
    <table class="table table-striped" id="people-table">
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Birthday</th>
                <th>Primary Phone</th>
                <th>Primary Address</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($people as $person)
            <tr>
                <td>{{ $person->first_name }}</td>
                <td>{{ $person->last_name }}</td>
                <td>{{ $person->email }}</td>
                <td>{{ $person->birthday }}</td>
                <td>{{ optional($person->primaryPhone())->number_format }}</td>
                <td>{{ optional($person->primaryAddress())->street }}</td>
                <td>
                    <div class='btn-group'>
                        <a href="{{ route('people.show', [$person->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('people.edit', [$person->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        <form action="{{ route('people.destroy', $person->id) }}" method="DELETE">
                            @csrf
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this person?')" class="btn btn-ghost-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div> 

@push('scripts')
<script>
    $(document).ready(function() {
        $('#people-table').DataTable();
    });
</script>
@endpush