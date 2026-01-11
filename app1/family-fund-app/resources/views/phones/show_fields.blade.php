@php
    $person = $phone->person;
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
                    <span class="text-body-secondary">ID: {{ $phone->person_id }}</span>
                @endif
            </p>
        </div>

        <!-- Number Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-phone me-1"></i> Number:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $phone->number }}</p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ ucfirst($phone->type) }}</span>
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Is Primary Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-star me-1"></i> Primary:</label>
            <p class="mb-0">
                @if($phone->is_primary)
                    <span class="badge bg-success">Yes</span>
                @else
                    <span class="badge bg-secondary">No</span>
                @endif
            </p>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $phone->created_at?->format('M j, Y') ?: '-' }}</p>
        </div>

        <!-- Phone ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Phone ID:</label>
            <p class="mb-0">#{{ $phone->id }}</p>
        </div>
    </div>
</div>
