<x-app-layout>
@section('content')
@php
    $tranShares = number_format((float) $tran->shares, 4, '.', '');
@endphp
<ol class="breadcrumb">
    @if($account)
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    @endif
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">Credit Line #{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">Allocate payment</li>
</ol>

<div class="container-fluid">
    @include('coreui-templates.common.errors')

    <div class="card mb-3">
        <div class="card-header">
            <strong>Allocate payment &mdash; txn #{{ $tran->id }}</strong>
            @if($account)
                <span class="text-body-secondary ms-2">
                    &mdash; <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
                </span>
            @endif
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Transaction</dt>
                <dd class="col-sm-3">
                    <a href="{{ route('transactions.show', $tran->id) }}">#{{ $tran->id }}</a>
                </dd>
                <dt class="col-sm-3">Shares</dt>
                <dd class="col-sm-3">{{ number_format($tran->shares, 4) }}</dd>
                <dt class="col-sm-3">Date</dt>
                <dd class="col-sm-3">
                    {{ $tran->timestamp ? \Illuminate\Support\Carbon::parse($tran->timestamp)->toDateString() : '—' }}
                </dd>
                <dt class="col-sm-3">Credit line</dt>
                <dd class="col-sm-3">#{{ $line->id }} ({{ $line->status }})</dd>
            </dl>
            <p class="text-body-secondary mb-0 mt-2">
                Enter how many of this transaction's shares apply to each row.
                Submitting <strong>replaces</strong> this transaction's existing
                allocations. The total <strong>must equal {{ $tranShares }} shares</strong> —
                use <em>Fill remainders</em> to auto-distribute oldest-first.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Per-row allocation</strong></div>
        <form method="POST"
              action="{{ route('credit_lines.payments.allocate', ['line' => $line->id, 'transaction' => $tran->id]) }}">
            @csrf
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th><th>Due date</th>
                            <th title="Shares this row still needs to be fully paid, ignoring this txn">Remainder</th>
                            <th>Status</th><th>This txn now</th><th>Allocate</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $row)
                        @php
                            $now       = $currentAlloc[$row->id] ?? null;
                            $other     = $otherAlloc[$row->id] ?? 0.0;
                            $remainder = round(max(0.0, (float) $row->shares_due - $other), 4);
                        @endphp
                        @if($remainder <= 0 && $now === null)
                            @continue
                        @endif
                        <tr>
                            <td>{{ $row->sequence_number ?? $row->id }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                            <td>
                                {{ number_format($remainder, 4) }}
                                @if($other > 0)
                                    <div class="small text-body-secondary">
                                        of {{ number_format($row->shares_due, 4) }} due
                                        ({{ number_format($other, 4) }} from other txns)
                                    </div>
                                @endif
                            </td>
                            <td>@include('account_credit_lines._payment_status_badge', ['status' => $row->status])</td>
                            <td>{{ $now !== null ? number_format($now, 4) : '—' }}</td>
                            <td style="max-width:9rem">
                                <input type="number" step="0.0001" min="0"
                                       max="{{ number_format($remainder, 4, '.', '') }}"
                                       data-remainder="{{ number_format($remainder, 4, '.', '') }}"
                                       class="form-control form-control-sm allocate-input"
                                       name="allocations[{{ $row->id }}]"
                                       value="{{ old('allocations.' . $row->id, $now !== null ? number_format($now, 4, '.', '') : '') }}">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex align-items-center gap-2 flex-wrap">
                <button type="button" id="fill-remainders" class="btn btn-outline-primary">
                    Fill remainders
                </button>
                <button type="submit" id="apply-allocation" class="btn btn-success">Apply allocation</button>
                <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}"
                   class="btn btn-secondary">Cancel</a>
                <span id="allocate-total" class="ms-auto small" data-tran-shares="{{ $tranShares }}">
                    Allocated <strong data-role="sum">0.0000</strong> / {{ $tranShares }}
                    <span data-role="diff" class="ms-1"></span>
                </span>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const tranShares = parseFloat(document.getElementById('allocate-total').dataset.tranShares);
    const inputs = Array.from(document.querySelectorAll('.allocate-input'));
    const sumEl = document.querySelector('#allocate-total [data-role="sum"]');
    const diffEl = document.querySelector('#allocate-total [data-role="diff"]');
    const submitBtn = document.getElementById('apply-allocation');
    const EPS = 1e-4;

    function round4(n) { return Math.round(n * 10000) / 10000; }

    function refresh() {
        let sum = 0;
        for (const i of inputs) sum += parseFloat(i.value || '0') || 0;
        sum = round4(sum);
        sumEl.textContent = sum.toFixed(4);
        const diff = round4(tranShares - sum);
        if (Math.abs(diff) < EPS) {
            diffEl.textContent = '✓';
            diffEl.className = 'ms-1 text-success';
            submitBtn.disabled = false;
        } else if (diff > 0) {
            diffEl.textContent = '(' + diff.toFixed(4) + ' short)';
            diffEl.className = 'ms-1 text-warning';
            submitBtn.disabled = true;
        } else {
            diffEl.textContent = '(' + Math.abs(diff).toFixed(4) + ' over)';
            diffEl.className = 'ms-1 text-danger';
            submitBtn.disabled = true;
        }
    }

    document.getElementById('fill-remainders').addEventListener('click', function () {
        let left = tranShares;
        for (const i of inputs) {
            const cap = parseFloat(i.dataset.remainder || '0') || 0;
            const give = round4(Math.min(left, cap));
            i.value = give > 0 ? give.toFixed(4) : '';
            left = round4(left - give);
            if (left <= EPS) left = 0;
        }
        // Any leftover capacity beyond what rows can take stays unfilled; the
        // user sees a "short" indicator and can either reduce the txn or add
        // more rows.
        refresh();
    });

    for (const i of inputs) i.addEventListener('input', refresh);
    refresh();
})();
</script>
</x-app-layout>
