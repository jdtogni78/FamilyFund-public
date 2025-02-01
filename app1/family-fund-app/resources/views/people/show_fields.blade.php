<div class="col-md-6">
    <!-- First Name Field -->
    <div class="form-group">
<label for="first_name">First Name:</label>
        <p>{{ $person->first_name }}</p>
    </div>

    <!-- Last Name Field -->
    <div class="form-group">
<label for="last_name">Last Name:</label>
        <p>{{ $person->last_name }}</p>
    </div>

    <!-- Email Field -->
    <div class="form-group">
<label for="email">Email:</label>
        <p>{{ $person->email }}</p>
    </div>

    <!-- Birthday Field -->
    <div class="form-group">
<label for="birthday">Birthday:</label>
        <p>{{ $person->birthday }}</p>
    </div>

    <!-- Legal Guardian Id Field -->
    <div class="form-group">
<label for="legal_guardian_id">Legal Guardian:</label>
        <p>{{ $person->legal_guardian_id?->full_name }}</p>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <strong>Phones:</strong>
        <ul>
            @foreach($person->phones as $phone)
                <li>{{ $phone->type }}: {{ $phone->number }} {{ $phone->is_primary ? '(Primary)' : '' }}</li>
            @endforeach
        </ul>
    </div>

    <div class="form-group">
        <strong>Addresses:</strong>
        <ul>
            @foreach($person->addresses as $address)
                <li>
                    {{ $address->type }}: {{ $address->street }}, {{ $address->number }}
                    {{ $address->complement ? ', '.$address->complement : '' }}
                    - {{ $address->county }}, {{ $address->city }}/{{ $address->state }}
                    {{ $address->is_primary ? '(Primary)' : '' }}
                </li>
            @endforeach
        </ul>
    </div>

    <div class="form-group">
        <strong>ID Documents:</strong>
        <ul>
            @foreach($person->idDocuments as $doc)
                <li>{{ $doc->type }}: {{ $doc->number }}</li>
            @endforeach
        </ul>
    </div>

    <!-- Created/Updated At Fields -->
    <div class="form-group">
<label for="created_at">Created At:</label>
        <p>{{ $person->created_at }}</p>
    </div>

    <div class="form-group">
<label for="updated_at">Updated At:</label>
        <p>{{ $person->updated_at }}</p>
    </div>
</div>

