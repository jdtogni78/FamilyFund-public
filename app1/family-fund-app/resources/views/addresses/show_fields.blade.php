@php
    $person = $address->person;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Person Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Person:</label>
            <p class="mb-0">
                @if($person)
                    <a href="{{ route('people.show', $person->id) }}" class="fw-bold">
                        {{ $person->first_name }} {{ $person->last_name }}
                    </a>
                @else
                    <span class="text-body-secondary">ID: {{ $address->person_id }}</span>
                @endif
            </p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ ucfirst($address->type) }}</span>
                @if($address->is_primary)
                    <span class="badge bg-success ms-1">Primary</span>
                @endif
            </p>
        </div>

        <!-- Full Address Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-map-marker-alt me-1"></i> Address:</label>
            <p class="mb-0">
                <span class="fw-bold">{{ $address->street }}, {{ $address->number }}</span>
                @if($address->complement)
                    <span class="text-body-secondary">({{ $address->complement }})</span>
                @endif
                <br>
                {{ $address->city }}, {{ $address->state }} {{ $address->zip_code }}
                @if($address->county)
                    <br><small class="text-body-secondary">County: {{ $address->county }}</small>
                @endif
                @if($address->country)
                    <br><small class="text-body-secondary">{{ $address->country }}</small>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Street & Number Fields -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-road me-1"></i> Street:</label>
            <p class="mb-0">{{ $address->street }}</p>
        </div>

        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-home me-1"></i> Number:</label>
            <p class="mb-0">{{ $address->number }}</p>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $address->created_at?->format('M j, Y') ?: '-' }}</p>
        </div>

        <!-- Address ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Address ID:</label>
            <p class="mb-0">#{{ $address->id }}</p>
        </div>
    </div>
</div>
