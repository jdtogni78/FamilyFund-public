@php
    use App\Models\GoalExt;
    $targetTypeMap = GoalExt::targetTypeMap();
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Name Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-bullseye me-1"></i> Name:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $goal->name }}</p>
        </div>

        <!-- Description Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-align-left me-1"></i> Description:</label>
            <p class="mb-0">{{ $goal->description ?: '-' }}</p>
        </div>

        <!-- Date Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Active Period:</label>
            <p class="mb-0">
                {{ $goal->start_dt }} <i class="fa fa-arrow-right mx-2 text-body-secondary"></i> {{ $goal->end_dt }}
                @php
                    $now = now()->format('Y-m-d');
                    $isActive = $now >= $goal->start_dt && $now <= $goal->end_dt;
                @endphp
                @if($isActive)
                    <span class="badge bg-success ms-2">Active</span>
                @else
                    <span class="badge bg-secondary ms-2">Inactive</span>
                @endif
            </p>
        </div>

        <!-- Goal ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Goal ID:</label>
            <p class="mb-0">#{{ $goal->id }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Target Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-crosshairs me-1"></i> Target Type:</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ $targetTypeMap[$goal->target_type] ?? $goal->target_type }}</span>
            </p>
        </div>

        <!-- Target Amount Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Target Amount:</label>
            <p class="mb-0">
                @if($goal->target_amount)
                    <span class="fs-4 fw-bold text-success">${{ number_format($goal->target_amount, 2) }}</span>
                @else
                    <span class="text-body-secondary">-</span>
                @endif
            </p>
        </div>

        <!-- Target Percentage Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-percent me-1"></i> Target Percentage:</label>
            <p class="mb-0">
                @if($goal->target_pct)
                    <span class="fs-4 fw-bold">{{ number_format($goal->target_pct * 100, 2) }}%</span>
                @else
                    <span class="text-body-secondary">-</span>
                @endif
            </p>
        </div>

        <!-- Linked Accounts Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-users me-1"></i> Linked Accounts:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $goal->accountGoals()->count() }}</span>
            </p>
        </div>
    </div>
</div>
