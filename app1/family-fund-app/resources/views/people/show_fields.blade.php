<div class="col-md-6">
    <!-- First Name Field -->
    <div class="form-group">
        {!! Form::label('first_name', 'First Name:') !!}
        <p>{{ $person->first_name }}</p>
    </div>

    <!-- Last Name Field -->
    <div class="form-group">
        {!! Form::label('last_name', 'Last Name:') !!}
        <p>{{ $person->last_name }}</p>
    </div>

    <!-- Email Field -->
    <div class="form-group">
        {!! Form::label('email', 'Email:') !!}
        <p>{{ $person->email }}</p>
    </div>

    <!-- Birthday Field -->
    <div class="form-group">
        {!! Form::label('birthday', 'Birthday:') !!}
        <p>{{ $person->birthday }}</p>
    </div>

    <!-- Legal Guardian Id Field -->
    <div class="form-group">
        {!! Form::label('legal_guardian_id', 'Legal Guardian:') !!}
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
        {!! Form::label('created_at', 'Created At:') !!}
        <p>{{ $person->created_at }}</p>
    </div>

    <div class="form-group">
        {!! Form::label('updated_at', 'Updated At:') !!}
        <p>{{ $person->updated_at }}</p>
    </div>
</div>

