<div class="col-sm-12">
    <div class="form-group">
        <strong>Name:</strong>
        {{ $person->full_name }}
    </div>

    <div class="form-group">
        <strong>Email:</strong>
        {{ $person->email }}
    </div>

    <div class="form-group">
        <strong>Birthday:</strong>
        {{ $person->birthday->format('Y-m-d') }}
    </div>

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
                    - {{ $address->neighborhood }}, {{ $address->city }}/{{ $address->state }}
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
</div> 