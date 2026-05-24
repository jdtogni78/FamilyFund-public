<x-app-layout>
@section('content')
<ol class="breadcrumb">
    @if($account)
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    @endif
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">Loan Share #{{ $line->id }}</a>
    </li>
    <li class="breadcrumb-item active">Admin actions</li>
</ol>

<div class="container-fluid">
    @include('coreui-templates.common.errors')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Loan Share #{{ $line->id }} &mdash; admin actions</strong>
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
    <div class="row">
        {{-- Register payment — no inline form here. Opens the dedicated
             create-payment page (settlement date + shares; overpayment cascades
             to later rows). That page is per schedule row, so we target the next
             open installment (earliest scheduled/partial/late row); when the
             schedule is fully settled there is nothing to register. --}}
        @php
            $nextOpenRow = $schedule->first(fn ($r) => in_array($r->status, [
                \App\Models\CreditLinePayment::STATUS_SCHEDULED,
                \App\Models\CreditLinePayment::STATUS_PARTIAL,
                \App\Models\CreditLinePayment::STATUS_LATE,
            ], true));
        @endphp
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="card-header"><strong>Register payment</strong></div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small flex-grow-1 mb-3">
                        Opens the payment page for the next open installment, where you
                        set the settlement date and shares paid. Overpayment cascades
                        to later open rows.
                    </p>
                    @if($nextOpenRow)
                        <a href="{{ route('credit_lines.payments.register_form', ['line' => $line->id, 'payment' => $nextOpenRow->id]) }}"
                           class="btn btn-success btn-sm align-self-start">
                            <i class="fa fa-money-bill me-1"></i>Register payment
                        </a>
                    @else
                        <span class="text-muted small align-self-start">
                            <i class="fa fa-circle-check me-1"></i>No open installments to settle.
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Readjust — its own section with the form. --}}
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="card-header"><strong>Readjust loan</strong></div>
                <div class="card-body">
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
                        <label class="form-label">Start date</label>
                        <input type="date" name="effective_date"
                               value="{{ old('effective_date') }}"
                               placeholder="defaults to today"
                               class="form-control mb-1">
                        <small class="text-muted d-block mb-2">
                            Optional — leave blank to start today. The new schedule is
                            anchored here; first payment falls one period after. Prior
                            schedule rows are kept (cancelled) for history.
                        </small>
                        <input type="text" name="reason" class="form-control mb-2" placeholder="Reason (optional)">
                        <button type="submit" class="btn btn-warning btn-sm">Readjust</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Delete — button only, behaviour unchanged. --}}
        <div class="col-md-4">
            <div class="card mb-3 h-100 border-danger">
                <div class="card-header text-danger"><strong>Danger zone</strong></div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small flex-grow-1 mb-3">
                        Cancelling closes this loan share. Prior schedule rows are
                        kept (cancelled) for history.
                    </p>
                    <form method="POST"
                          action="{{ route('credit_lines.cancel', ['line' => $line->id]) }}"
                          onsubmit="return confirm('Cancel this loan share?');">
                        @csrf
                        <input type="hidden" name="account_credit_line_id" value="{{ $line->id }}">
                        <button type="submit" class="btn btn-danger btn-sm align-self-start d-block">Cancel line</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Registered payments</strong></div>
        <div class="card-body">
            @php
                // Only paid rows surface here — they're the ones admins can
                // edit or reverse. New payments go through the "Make payment"
                // button on the loan-share show page (oldest-first cascade);
                // cross-row reallocation is reached via the per-allocation
                // slider on the show page.
                $shownRows = $schedule->filter(fn($r) => $r->status === 'paid');
            @endphp
            @if($shownRows->isEmpty())
                <p class="text-muted mb-0">No registered payments.</p>
            @else
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>#</th><th>Due date</th><th>Shares</th><th class="text-end">$ Amount</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($shownRows as $row)
                    <tr>
                        <td>{{ $row->sequence_number ?? $row->id }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                        <td>{{ number_format($row->shares_due, 4) }}</td>
                        <td class="text-end">
                            @if(isset($scheduleDollars[$row->id]))
                                ${{ number_format($scheduleDollars[$row->id], 2) }}
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            @include('account_credit_lines._payment_status_badge', ['status' => $row->status])
                        </td>
                        <td class="text-end">
                            @include('account_credit_lines._payment_row_actions', ['line' => $line, 'row' => $row])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
    @else
        <p class="text-muted">This loan share is <strong>{{ $line->status }}</strong> — no admin actions available.</p>
    @endif

    <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}" class="btn btn-secondary">Back to loan share</a>
</div>
</x-app-layout>
