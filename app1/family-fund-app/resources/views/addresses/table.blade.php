<div class="table-responsive-sm">
    <table class="table table-striped" id="addresses-table">
        <thead>
            <tr>
                <th>Person Id</th>
        <th>Type</th>
        <th>Is Primary</th>
        <th>Street</th>
        <th>Number</th>
        <th>County</th>
        <th>City</th>
        <th>State</th>
        <th>Zip Code</th>
        <th>Country</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($addresses as $address)
            <tr>
                <td>{{ $address->person_id }}</td>
            <td>{{ $address->type }}</td>
            <td>{{ $address->is_primary }}</td>
            <td>{{ $address->street }}</td>
            <td>{{ $address->number }}</td>
            <td>{{ $address->county }}</td>
            <td>{{ $address->city }}</td>
            <td>{{ $address->state }}</td>
            <td>{{ $address->zip_code }}</td>
            <td>{{ $address->country }}</td>
                <td>
                    <form action="{{ route('addresses.destroy', $address->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class='btn-group'>
                            <a href="{{ route('addresses.show', [$address->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                            <a href="{{ route('addresses.edit', [$address->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                            <button type="submit" class="btn btn-ghost-danger" onclick="return confirm('Are you sure you want to delete this address?')"><i class="fa fa-trash"></i></button>
                        </div>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>