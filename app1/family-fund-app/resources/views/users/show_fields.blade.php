<div class="row">
    <div class="col-md-6">
        <!-- Name Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Name:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $user->name }}</p>
        </div>

        <!-- Email Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-envelope me-1"></i> Email:</label>
            <p class="mb-0">{{ $user->email }}</p>
        </div>

        <!-- Email Verified At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-check-circle me-1"></i> Email Verified:</label>
            <p class="mb-0">
                @if($user->email_verified_at)
                    <span class="badge bg-success">Verified</span>
                    <span class="text-body-secondary ms-2">{{ $user->email_verified_at->format('M j, Y') }}</span>
                @else
                    <span class="badge bg-warning">Not Verified</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Accounts Count -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-users me-1"></i> Accounts:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $user->accounts()->count() }}</span>
            </p>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-clock me-1"></i> Created:</label>
            <p class="mb-0">{{ $user->created_at?->format('M j, Y') ?: '-' }}</p>
        </div>

        <!-- User ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> User ID:</label>
            <p class="mb-0">#{{ $user->id }}</p>
        </div>
    </div>
</div>
