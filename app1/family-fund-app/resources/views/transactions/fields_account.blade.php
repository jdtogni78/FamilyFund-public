@php
    $trans = $transaction ?? null;
    $defaultAccountId = $trans->account_id ?? null;
    $defaultFundId = $trans && $trans->account ? $trans->account->fund_id : null;
    $defaultTimestamp = $trans ? \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') : '';
@endphp

<div class="row mb-3">
    <div class="col-md-4 d-flex flex-column">
        <label for="fund_filter" class="form-label text-muted small text-uppercase mb-1">Fund (Filter)</label>
        <select class="form-select" id="fund_filter">
            @foreach($api['fundMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultFundId == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-flex flex-column">
        <label for="account_id" class="form-label text-muted small text-uppercase mb-1">Account</label>
        <select name="account_id" class="form-select" id="account_id" required>
            @foreach($api['accountMap'] as $value => $label)
                <option value="{{ $value }}" data-fund-id="{{ $api['accountFundMap'][$value] ?? '' }}" {{ $defaultAccountId == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-flex flex-column">
        <label for="timestamp" class="form-label text-muted small text-uppercase mb-1">Transaction Date</label>
        <div class="input-group">
            <input type="date" name="timestamp" value="{{ $defaultTimestamp }}"
                   class="form-control" id="timestamp" required>
            <button type="button" class="btn btn-outline-secondary" id="todayBtn" title="Set to today">
                Today
            </button>
            <button type="button" class="btn btn-outline-secondary" id="neverBtn" title="Set to 9999-12-31 (no specific date)">
                <i class="fa fa-infinity"></i>
            </button>
        </div>
    </div>
</div>

<!-- Account Info Panel -->
<div id="accountInfoPanel" style="display: none;">
    <div class="card bg-light border-0">
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col-md-4 border-end">
                    <div class="small text-muted text-uppercase">Account</div>
                    <div class="fw-bold" id="__account_nickname">-</div>
                    <div class="small text-muted" id="__user_info">-</div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="small text-muted text-uppercase">Balance</div>
                    <div class="fw-bold text-primary" id="__account_balance_lg">-</div>
                </div>
                <div class="col-md-3 border-end">
                    <div class="small text-muted text-uppercase">Shares</div>
                    <div class="fw-bold" id="__account_shares_sm">-</div>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted text-uppercase">Price</div>
                    <div class="fw-bold" id="__share_price_lg">-</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="accountPlaceholder">
    <div class="alert alert-light border mb-0 text-center py-2">
        <i class="fa fa-info-circle me-2 text-muted"></i>
        <span class="text-muted">Select account and date to see balance</span>
    </div>
</div>
