@php
    $person = $idDocument->person;
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
                    <span class="text-body-secondary">ID: {{ $idDocument->person_id }}</span>
                @endif
            </p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-id-card me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ strtoupper($idDocument->type) }}</span>
            </p>
        </div>

        <!-- Number Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Number:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $idDocument->number }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $idDocument->created_at?->format('M j, Y') ?: '-' }}</p>
        </div>

        <!-- ID Document ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> ID Document ID:</label>
            <p class="mb-0">#{{ $idDocument->id }}</p>
        </div>
    </div>
</div>
