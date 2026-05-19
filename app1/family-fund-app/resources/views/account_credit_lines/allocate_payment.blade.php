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
                allocations. The total may not exceed {{ $tranShares }} shares;
                any unallocated remainder is treated as a principal prepayment.
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
                            <th>#</th><th>Due date</th><th>Shares due</th>
                            <th>Status</th><th>This txn now</th><th>Allocate</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $row)
                        @php $now = $currentAlloc[$row->id] ?? null; @endphp
                        <tr>
                            <td>{{ $row->sequence_number ?? $row->id }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                            <td>{{ number_format($row->shares_due, 4) }}</td>
                            <td>@include('account_credit_lines._payment_status_badge', ['status' => $row->status])</td>
                            <td>{{ $now !== null ? number_format($now, 4) : '—' }}</td>
                            <td style="max-width:9rem">
                                <input type="number" step="0.0001" min="0"
                                       class="form-control form-control-sm"
                                       name="allocations[{{ $row->id }}]"
                                       value="{{ old('allocations.' . $row->id, $now !== null ? number_format($now, 4, '.', '') : '') }}">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Apply allocation</button>
                <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}"
                   class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
