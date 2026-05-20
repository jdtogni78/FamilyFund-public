<x-app-layout>
@section('content')
@php
    $editTran   = $editTran ?? null;
    $isEdit     = (bool) $editTran;
    $formAction = $isEdit
        ? route('credit_lines.payments.update', ['line' => $line->id, 'payment' => $row->id])
        : route('credit_lines.payments.register', ['line' => $line->id, 'payment' => $row->id]);
    $defaultShares = $isEdit
        ? number_format((float) $editTran->shares, 4, '.', '')
        : number_format($row->shares_due, 4, '.', '');
    $defaultDate = $isEdit && $editTran->timestamp
        ? \Illuminate\Support\Carbon::parse($editTran->timestamp)->toDateString()
        : \Illuminate\Support\Carbon::today()->toDateString();
@endphp
<ol class="breadcrumb">
    @if($account)
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    @endif
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">Loan Share #{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit payment' : 'Register payment' }}</li>
</ol>

<div class="container-fluid">
    @include('coreui-templates.common.errors')

    <div class="card mb-3">
        <div class="card-header">
            <strong>{{ $isEdit ? 'Edit payment' : 'Register payment' }} &mdash; row #{{ $row->sequence_number ?? $row->id }}</strong>
            @if($isEdit)
                <span class="badge bg-info ms-2">editing txn #{{ $editTran->id }}</span>
            @endif
            @if($account)
                <span class="text-body-secondary ms-2">
                    &mdash; <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
                    @if($account->fund)
                        (<a href="{{ route('funds.show', $account->fund_id) }}">{{ $account->fund->name }}</a>)
                    @endif
                </span>
            @endif
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Credit line</dt>
                <dd class="col-sm-3">
                    <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">#{{ $line->id }}</a>
                    ({{ $line->status }})
                </dd>
                <dt class="col-sm-3">Due date</dt>
                <dd class="col-sm-3">{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</dd>

                <dt class="col-sm-3">Scheduled (shares)</dt>
                <dd class="col-sm-3">
                    {{ number_format($row->shares_due, 4) }}
                    @if($shareValue > 0)
                        <span class="text-body-secondary">
                            (${{ number_format($row->shares_due * $shareValue, 2) }})
                        </span>
                    @endif
                </dd>
                <dt class="col-sm-3">Row status</dt>
                <dd class="col-sm-3">{{ $row->status }}</dd>
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Payment details</strong></div>
        <form method="POST" action="{{ $formAction }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="date">Settlement date</label>
                    <input type="date" name="date" id="date" class="form-control"
                           max="{{ \Illuminate\Support\Carbon::today()->toDateString() }}"
                           value="{{ old('date', $defaultDate) }}">
                    <div class="form-text">
                        The real date the external system settled this payment.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="shares">Shares paid</label>
                    <input type="number" step="0.0001" min="0.0001" name="shares" id="shares"
                           class="form-control"
                           value="{{ old('shares', $defaultShares) }}" required>
                    <div class="form-text">
                        Defaults to the scheduled amount. Edit for an under/over payment;
                        overflow cascades to later open rows.
                        @if($shareValue > 0)
                            &mdash; approx <strong id="shares-dollar">$0.00</strong>
                            at ${{ number_format($shareValue, 4) }}/share.
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">{{ $isEdit ? 'Save changes' : 'Register payment' }}</button>
                <a href="{{ $isEdit ? route('credit_lines.show', ['line' => $line->id]) : route('credit_lines.global_payments') }}"
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@if($shareValue > 0)
<script>
    (function () {
        var shares    = document.getElementById('shares');
        var out       = document.getElementById('shares-dollar');
        var shareVal  = {{ (float) $shareValue }};
        if (!shares || !out) return;
        function sync() {
            var n = parseFloat(shares.value);
            out.textContent = '$' + (isNaN(n) ? 0 : n * shareVal)
                .toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        shares.addEventListener('input', sync);
        sync();
    })();
</script>
@endif
</x-app-layout>
