<x-app-layout>
@section('content')
<ol class="breadcrumb">
    @if($account)
        <li class="breadcrumb-item"><a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('credit_lines.index', ['account' => $account->id]) }}">Credit Lines</a></li>
    @else
        <li class="breadcrumb-item"><a href="{{ route('credit_lines.global_index') }}">Credit Lines</a></li>
    @endif
    <li class="breadcrumb-item active">New</li>
</ol>
<div class="container-fluid">
    <div class="card">
        <div class="card-header"><strong>Open new credit line</strong></div>
        <div class="card-body">
            @include('coreui-templates.common.errors')
            <form method="POST" action="{{ $account ? route('credit_lines.store', ['account' => $account->id]) : route('credit_lines.global_store') }}">
                @csrf
                @if($account)
                    <input type="hidden" name="account_id" value="{{ $account->id }}">
                @else
                <div class="mb-3">
                    <label class="form-label">Account</label>
                    <select class="form-select" name="account_id" id="account_id" required>
                        <option value="">— select an account —</option>
                        @foreach($accounts as $acct)
                            <option value="{{ $acct->id }}" @selected(old('account_id') == $acct->id)>{{ $acct->nickname }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="mb-3">
                    <label class="form-label">Origination date</label>
                    <input type="date" class="form-control" name="origination_date"
                           id="origination_date"
                           value="{{ old('origination_date', \Illuminate\Support\Carbon::today()->toDateString()) }}"
                           max="{{ \Illuminate\Support\Carbon::today()->toDateString() }}">
                    <div class="form-text" id="backdate-warning" style="display:none;">
                        <strong class="text-warning">⚠️ Note on Backdating:</strong>
                        This generates the expected payment rows from the chosen date;
                        any already past their due date are flagged <strong>late</strong>.
                        Register any payments that already happened manually at
                        <strong>Credit Lines &rarr; (the line) &rarr; Schedule</strong>.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Principal (shares)</label>
                    <input type="number" step="0.0001" class="form-control" name="principal_shares"
                           id="principal_shares" required>
                    <div class="form-text">
                        Available to borrow as of <span id="available-date">{{ \Illuminate\Support\Carbon::today()->toDateString() }}</span>:
                        <strong id="available-shares">{{ number_format($available, 4) }}</strong> shares
                        <span id="available-loading" class="text-muted" style="display:none;">(updating…)</span>
                    </div>
                    <div class="invalid-feedback d-block" id="over-borrow-warning" style="display:none;">
                        Requested principal exceeds the available-to-borrow balance for this date.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Term (months)</label>
                    <input type="number" class="form-control" name="term_months" value="12" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment frequency</label>
                    <select class="form-select" name="payment_frequency" required>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="descr" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Imputed interest rate (decimal, optional)</label>
                    <input type="number" step="0.0001" min="0" max="1" class="form-control" name="imputed_interest_rate">
                </div>
                <button type="submit" class="btn btn-primary" id="submit-btn">Open</button>
                <a href="{{ $account ? route('credit_lines.index', ['account' => $account->id]) : route('credit_lines.global_index') }}"
                   class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        var input      = document.getElementById('origination_date');
        var warn       = document.getElementById('backdate-warning');
        var principal  = document.getElementById('principal_shares');
        var availEl    = document.getElementById('available-shares');
        var availDate  = document.getElementById('available-date');
        var loading    = document.getElementById('available-loading');
        var overWarn   = document.getElementById('over-borrow-warning');
        var submitBtn  = document.getElementById('submit-btn');
        var accountSel = document.getElementById('account_id'); // null in per-account mode
        var today      = '{{ \Illuminate\Support\Carbon::today()->toDateString() }}';
        @if($account)
        var availUrl   = '{{ route('credit_lines.available_shares', ['account' => $account->id]) }}';
        var globalMode = false;
        @else
        var availUrl   = '{{ route('credit_lines.global_available_shares') }}';
        var globalMode = true;
        @endif
        var available  = {{ (float) $available }};

        function accountId() {
            return accountSel ? accountSel.value : '{{ $account?->id }}';
        }

        function syncWarning() {
            if (!input || !warn) return;
            warn.style.display = (input.value && input.value < today) ? '' : 'none';
        }

        function syncOverBorrow() {
            if (!principal) return;
            var req = parseFloat(principal.value);
            var over = !isNaN(req) && req > available;
            principal.classList.toggle('is-invalid', over);
            if (overWarn) overWarn.style.display = over ? '' : 'none';
            if (submitBtn) submitBtn.disabled = over;
        }

        function fetchAvailable() {
            if (!input || !input.value) return;
            var acct = accountId();
            if (globalMode && !acct) return; // no account chosen yet
            if (loading) loading.style.display = '';
            var url = availUrl + '?as_of=' + encodeURIComponent(input.value);
            if (globalMode) url += '&account=' + encodeURIComponent(acct);
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    available = parseFloat(d.available);
                    if (availEl) availEl.textContent = available.toFixed(4);
                    if (availDate) availDate.textContent = d.as_of;
                    syncOverBorrow();
                })
                .catch(function () { /* keep last known value */ })
                .finally(function () { if (loading) loading.style.display = 'none'; });
        }

        if (input) {
            input.addEventListener('change', function () { syncWarning(); fetchAvailable(); });
            input.addEventListener('input', syncWarning);
        }
        if (principal) {
            principal.addEventListener('input', syncOverBorrow);
        }
        if (accountSel) {
            accountSel.addEventListener('change', fetchAvailable);
        }
        syncWarning();
        syncOverBorrow();
        if (!globalMode || accountId()) fetchAvailable();
    })();
</script>
</x-app-layout>
