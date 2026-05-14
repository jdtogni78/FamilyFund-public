@php
    $fund = $account->fund;
    $user = $account->user;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Nickname Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Nickname:</label>
            <p class="mb-0 fs-5 fw-bold">{{ $account->nickname }}</p>
        </div>

        <!-- Code Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-key me-1"></i> Code:</label>
            <p class="mb-0">{{ $account->code ?: '-' }}</p>
        </div>

        <!-- Fund Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-landmark me-1"></i> Fund:</label>
            <p class="mb-0">
                @if($fund)
                    @include('partials.view_link', ['route' => route('funds.show', $fund->id), 'text' => $fund->name, 'class' => 'fw-bold'])
                @else
                    <span class="text-body-secondary">N/A</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- User Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user-circle me-1"></i> User:</label>
            <p class="mb-0">
                @if($user)
                    @include('partials.view_link', ['route' => route('users.show', $user->id), 'text' => $user->name, 'class' => 'fw-bold'])
                    @if($user->email)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-envelope me-1"></i>{{ $user->email }}
                        </small>
                    @endif
                @else
                    <span class="badge bg-info">Fund Account</span>
                @endif
            </p>
        </div>

        <!-- Email CC Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-at me-1"></i> Email CC:</label>
            <p class="mb-0">{{ $account->email_cc ?: '-' }}</p>
        </div>

        <!-- Account ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Account ID:</label>
            <p class="mb-0">#{{ $account->id }}</p>
        </div>
    </div>
</div>
