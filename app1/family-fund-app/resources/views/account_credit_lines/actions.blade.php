<x-app-layout>
@section('content')
<ol class="breadcrumb">
    @if($account)
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    @endif
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">Credit Line #{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">Admin actions</li>
</ol>

<div class="container-fluid">
    @include('coreui-templates.common.errors')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Credit Line #{{ $line->id }} &mdash; admin actions</strong>
            <span class="badge bg-info">{{ $line->status }}</span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Principal (shares)</dt>
                <dd class="col-sm-3">{{ number_format($line->principal_shares, 4) }}</dd>
                <dt class="col-sm-3">Outstanding (shares)</dt>
                <dd class="col-sm-3">{{ number_format($line->outstanding_shares, 4) }}</dd>
            </dl>
        </div>
    </div>

    @if($line->status === 'active')
    <div class="card mb-3">
        <div class="card-header"><strong>Repay / Readjust / Cancel</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <form method="POST" action="{{ route('credit_lines.repay', ['line' => $line->id]) }}">
                        @csrf
                        <input type="hidden" name="account_credit_line_id" value="{{ $line->id }}">
                        <label class="form-label">Repay (shares)</label>
                        <input type="number" step="0.0001" name="shares" required class="form-control mb-2">
                        <button type="submit" class="btn btn-success btn-sm">Record repayment</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="POST" action="{{ route('credit_lines.readjust', ['line' => $line->id]) }}">
                        @csrf
                        <input type="hidden" name="account_credit_line_id" value="{{ $line->id }}">
                        <label class="form-label">New term (months)</label>
                        <input type="number" name="new_term_months" class="form-control mb-2">
                        <label class="form-label">New frequency</label>
                        <select name="new_payment_frequency" class="form-select mb-2">
                            <option value="">— unchanged —</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                        <input type="text" name="reason" class="form-control mb-2" placeholder="Reason (optional)">
                        <button type="submit" class="btn btn-warning btn-sm">Readjust</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <form method="POST"
                          action="{{ route('credit_lines.cancel', ['line' => $line->id]) }}"
                          onsubmit="return confirm('Cancel this credit line?');">
                        @csrf
                        <input type="hidden" name="account_credit_line_id" value="{{ $line->id }}">
                        <label class="form-label">Danger zone</label>
                        <button type="submit" class="btn btn-danger btn-sm d-block">Cancel line</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Register a scheduled payment</strong></div>
        <div class="card-body">
            @php
                $openRowStatuses = ['scheduled', 'partial', 'late'];
                $openRows = $schedule->filter(fn($r) => in_array($r->status, $openRowStatuses, true));
            @endphp
            @if($openRows->isEmpty())
                <p class="text-muted mb-0">No open schedule rows.</p>
            @else
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>#</th><th>Due date</th><th>Shares</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($openRows as $row)
                    <tr>
                        <td>{{ $row->sequence_number ?? $row->id }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                        <td>{{ number_format($row->shares_due, 4) }}</td>
                        <td>
                            @if($row->status === 'late')
                                <span class="badge bg-danger">late</span>
                            @elseif($row->status === 'partial')
                                <span class="badge bg-warning text-dark">partial</span>
                            @else
                                {{ $row->status }}
                            @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-outline-success btn-sm"
                               href="{{ route('credit_lines.payments.register_form', ['line' => $line->id, 'payment' => $row->id]) }}">
                                Register payment
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
    @else
        <p class="text-muted">This credit line is <strong>{{ $line->status }}</strong> — no admin actions available.</p>
    @endif

    <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}" class="btn btn-secondary">Back to credit line</a>
</div>
</x-app-layout>
