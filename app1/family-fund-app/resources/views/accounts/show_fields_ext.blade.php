@php
    $balance = $account->balances['OWN'] ?? null;
    $shares = $balance->shares ?? 0;
    $marketValue = $balance->market_value ?? 0;
    $sharePrice = $shares > 0 ? $marketValue / $shares : 0;
    $matchingAvailable = $api['matching_available'] ?? 0;
@endphp

<div class="row g-4">
    {{-- Account Info Card --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="card-title mb-3" style="color: #2563eb;">
                    <i class="fa fa-user-circle me-2"></i>{{ $account->nickname }}
                </h4>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Fund:</span>
                    <a href="{{ route('funds.show', [$account->fund->id]) }}" class="badge bg-primary fs-6 text-decoration-none">
                        {{ $account->fund->name }}
                    </a>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">User:</span>
                    <span class="fw-bold">{{ $account->user->name }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Email:</span>
                    <span class="text-truncate" style="max-width: 200px;">{{ $account->email_cc }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">As of:</span>
                    <span class="badge bg-secondary fs-6">{{ $api['as_of'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Market Value Card --}}
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid #2563eb;">
            <div class="card-body d-flex align-items-center">
                <div class="row w-100 text-center">
                    <div class="col-4">
                        <small class="text-muted d-block">Market Value</small>
                        <h4 class="mb-0" style="color: #2563eb;">${{ number_format($marketValue, 2) }}</h4>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Shares</small>
                        <strong>{{ number_format($shares, 2) }}</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Share Price</small>
                        <strong>${{ number_format($sharePrice, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Matching Available Card --}}
    @if($matchingAvailable > 0)
    <div class="col-12">
        <div class="card" style="border-left: 4px solid #16a34a;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Matching Available</h6>
                        <h3 class="mb-0" style="color: #16a34a;">${{ number_format($matchingAvailable, 2) }}</h3>
                    </div>
                    <div>
                        <i class="fa fa-hand-holding-usd fa-3x" style="color: #16a34a; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
