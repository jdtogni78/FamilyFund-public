@php
    $trans = $transaction ?? null;
    $defaultAccountId = $trans->account_id ?? null;
    $defaultTimestamp = $trans ? \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') : '';
@endphp

<div class="row mb-3">
    <div class="col-md-6">
        <label for="account_id" class="form-label">Account</label>
        <select name="account_id" class="form-select" id="account_id" required>
            @foreach($api['accountMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultAccountId == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="timestamp" class="form-label">Transaction Date</label>
        <input type="date" name="timestamp" value="{{ $defaultTimestamp }}"
               class="form-control" id="timestamp" required>
    </div>
</div>

<!-- Account Info Panel -->
<div id="accountInfoPanel" style="display: none;">
    <div class="card bg-light border-0">
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col-md-4 border-end">
                    <div class="small text-muted">ACCOUNT</div>
                    <div class="fw-bold" id="__account_nickname">-</div>
                    <div class="small text-muted" id="__user_info">-</div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="small text-muted">BALANCE</div>
                    <div class="fw-bold text-primary" id="__account_balance_lg">-</div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="small text-muted">SHARES</div>
                    <div class="fw-bold" id="__account_shares_sm">-</div>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">PRICE</div>
                    <div class="fw-bold" id="__share_price_lg">-</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="accountPlaceholder">
    <div class="alert alert-secondary mb-0 text-center py-2">
        <i class="fa fa-info-circle me-2"></i>Select account and date to see balance
    </div>
</div>
