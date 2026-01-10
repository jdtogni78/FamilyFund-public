@php
    $trans = $transaction ?? null;
    $defaultAccountId = $trans->account_id ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <label for="account_id" class="form-label fw-bold">
            <i class="fa fa-user-circle me-1"></i>Account
        </label>
        <select name="account_id" class="form-select form-select-lg" id="account_id" required>
            @foreach($api['accountMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultAccountId == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label for="timestamp" class="form-label fw-bold">
            <i class="fa fa-calendar me-1"></i>As of Date
        </label>
        <input type="date" name="timestamp" value="{{ $trans ? \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') : '' }}"
               class="form-control form-control-lg" id="timestamp" required>
    </div>
</div>

<!-- Account Info Panel - Shows after selection -->
<div id="accountInfoPanel" class="mt-3" style="display: none;">
    <div class="card bg-light border-0">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-4 border-end">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                            <i class="fa fa-user fa-lg"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Account</div>
                            <div class="fw-bold" id="__account_name">-</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center border-end">
                    <div class="text-muted small text-uppercase">Balance</div>
                    <div class="fs-4 fw-bold text-primary" id="__account_balance_lg">-</div>
                    <div class="text-muted small" id="__account_shares_sm">-</div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="text-muted small text-uppercase">Share Price</div>
                    <div class="fs-4 fw-bold" id="__share_price_lg">-</div>
                    <div class="text-muted small" id="__price_date">-</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Placeholder when no account selected -->
<div id="accountPlaceholder" class="mt-3">
    <div class="alert alert-light border text-center mb-0">
        <i class="fa fa-info-circle me-2 text-muted"></i>
        <span class="text-muted">Select an account and date to see balance information</span>
    </div>
</div>
