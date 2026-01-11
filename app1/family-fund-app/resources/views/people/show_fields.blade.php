@php
    $guardian = $person->legalGuardian;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Full Name Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Name:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $person->first_name }} {{ $person->last_name }}</p>
        </div>

        <!-- Email Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-envelope me-1"></i> Email:</label>
            <p class="mb-0">{{ $person->email ?: '-' }}</p>
        </div>

        <!-- Birthday Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-birthday-cake me-1"></i> Birthday:</label>
            <p class="mb-0">{{ $person->birthday ?: '-' }}</p>
        </div>

        <!-- Legal Guardian Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user-shield me-1"></i> Legal Guardian:</label>
            <p class="mb-0">
                @if($guardian)
                    <a href="{{ route('people.show', $guardian->id) }}">
                        {{ $guardian->first_name }} {{ $guardian->last_name }}
                    </a>
                @else
                    <span class="text-body-secondary">None</span>
                @endif
            </p>
        </div>

        <!-- Person ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Person ID:</label>
            <p class="mb-0">#{{ $person->id }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Phones Section -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-phone me-1"></i> Phones:</label>
            @if($person->phones->count() > 0)
                <ul class="list-unstyled mb-0">
                    @foreach($person->phones as $phone)
                        <li class="mb-1">
                            <span class="badge bg-secondary">{{ ucfirst($phone->type) }}</span>
                            {{ $phone->number }}
                            @if($phone->is_primary)
                                <span class="badge bg-success ms-1">Primary</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0 text-body-secondary">No phones</p>
            @endif
        </div>

        <!-- Addresses Section -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-map-marker-alt me-1"></i> Addresses:</label>
            @if($person->addresses->count() > 0)
                <ul class="list-unstyled mb-0">
                    @foreach($person->addresses as $address)
                        <li class="mb-2">
                            <span class="badge bg-secondary">{{ ucfirst($address->type) }}</span>
                            @if($address->is_primary)
                                <span class="badge bg-success">Primary</span>
                            @endif
                            <br>
                            <small>
                                {{ $address->street }}, {{ $address->number }}
                                {{ $address->complement ? ', '.$address->complement : '' }}
                                <br>
                                {{ $address->city }}, {{ $address->state }} {{ $address->zip_code }}
                            </small>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0 text-body-secondary">No addresses</p>
            @endif
        </div>

        <!-- ID Documents Section -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-id-card me-1"></i> ID Documents:</label>
            @if($person->idDocuments->count() > 0)
                <ul class="list-unstyled mb-0">
                    @foreach($person->idDocuments as $doc)
                        <li class="mb-1">
                            <span class="badge bg-secondary">{{ strtoupper($doc->type) }}</span>
                            {{ $doc->number }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-0 text-body-secondary">No documents</p>
            @endif
        </div>
    </div>
</div>
