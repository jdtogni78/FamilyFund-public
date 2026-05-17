<x-app-layout>
@section('content')
@php $isAdmin = (bool) (auth()->user()?->is_admin()); @endphp
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a>
    </li>
    <li class="breadcrumb-item">
        <a href="{{ route('credit_lines.index', ['account' => $account->id]) }}">Credit Lines</a>
    </li>
    <li class="breadcrumb-item active">#{{ $line->id }}</li>
</ol>

<div class="container-fluid">
    @include('coreui-templates.common.errors')

    @if(!empty($loansSummary ?? []))
        @include('account_credit_lines._loans_summary_card', ['loansSummary' => $loansSummary])
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Credit Line #{{ $line->id }}</strong>
            <span class="badge bg-info">{{ $line->status }}</span>
        </div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Principal (shares)</dt>
                <dd class="col-sm-3">{{ number_format($line->principal_shares, 4) }}</dd>
                <dt class="col-sm-3">Outstanding (shares)</dt>
                <dd class="col-sm-3">{{ number_format($line->outstanding_shares, 4) }}</dd>

                <dt class="col-sm-3">Term</dt>
                <dd class="col-sm-3">{{ $line->term_months }} months</dd>
                <dt class="col-sm-3">Frequency</dt>
                <dd class="col-sm-3">{{ $line->payment_frequency }}</dd>

                <dt class="col-sm-3">Origination</dt>
                <dd class="col-sm-3">{{ optional($line->origination_date)->format('Y-m-d') }}</dd>
                <dt class="col-sm-3">Maturity</dt>
                <dd class="col-sm-3">{{ optional($line->maturity_date)->format('Y-m-d') }}</dd>

                @if($line->descr)
                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $line->descr }}</dd>
                @endif
            </dl>
            <p class="text-muted small mt-3 mb-0">
                You owe {{ number_format($line->outstanding_shares, 4) }} shares
                @php
                    $sv = 0;
                    try { $sv = (float) $account?->shareValueAsOf(now()->toDateString()); } catch (\Throwable $e) {}
                @endphp
                @if($sv > 0)
                    (currently valued at ${{ number_format($line->outstanding_shares * $sv, 2) }})
                @endif. The share count is what you owe back &mdash; it doesn't change with the market.
                The dollar value will move up or down with the fund's share price.
            </p>
        </div>
    </div>

    @if(!empty($trajectory ?? []))
        @include('account_credit_lines._trajectory_chart', ['trajectory' => $trajectory, 'chartUrl' => null])
    @endif

    @if($isAdmin && $line->status === 'active')
    <div class="card mb-3">
        <div class="card-header"><strong>Admin actions</strong></div>
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
    @endif

    <div class="card mb-3">
        <div class="card-header"><strong>Payment schedule</strong></div>
        <div class="card-body">
            @if($schedule->isEmpty())
                <p class="text-muted mb-0">No schedule rows.</p>
            @else
            @php
                $openRowStatuses = ['scheduled', 'partial', 'late'];
                $canRegister = $isAdmin && $line->status === 'active';
            @endphp
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th><th>Due date</th><th>Shares</th><th>Status</th><th>Paid by tx</th>
                        @if($canRegister)<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                @foreach($schedule as $row)
                    @php $isOpen = in_array($row->status, $openRowStatuses, true); @endphp
                    <tr>
                        <td>{{ $row->sequence_number ?? $row->id }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                        <td>{{ number_format($row->shares_due, 4) }}</td>
                        <td>
                            @if($row->status === 'late')
                                <span class="badge bg-danger">late</span>
                            @elseif($row->status === 'partial')
                                <span class="badge bg-warning text-dark">partial</span>
                            @elseif($row->status === 'paid')
                                <span class="badge bg-success">paid</span>
                            @else
                                {{ $row->status }}
                            @endif
                        </td>
                        <td>{{ $row->paid_transaction_id }}</td>
                        @if($canRegister)
                        <td class="text-end">
                            @if($isOpen)
                            <button type="button" class="btn btn-outline-success btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#register-payment-{{ $row->id }}">
                                Register payment
                            </button>
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($canRegister)
                @foreach($schedule as $row)
                    @if(in_array($row->status, $openRowStatuses, true))
                    <div class="modal fade" id="register-payment-{{ $row->id }}" tabindex="-1"
                         aria-labelledby="register-payment-{{ $row->id }}-label" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST"
                                      action="{{ route('credit_lines.payments.register', ['line' => $line->id, 'payment' => $row->id]) }}">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="register-payment-{{ $row->id }}-label">
                                            Register payment &mdash; row #{{ $row->sequence_number ?? $row->id }}
                                            (due {{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }})
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Shares paid</label>
                                            <input type="number" step="0.0001" min="0.0001" name="shares"
                                                   class="form-control"
                                                   value="{{ number_format($row->shares_due, 4, '.', '') }}" required>
                                            <div class="form-text">
                                                Defaults to the scheduled amount. Edit for an under/over payment;
                                                overflow cascades to later open rows.
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Settlement date</label>
                                            <input type="date" name="date" class="form-control"
                                                   max="{{ \Illuminate\Support\Carbon::today()->toDateString() }}"
                                                   value="{{ \Illuminate\Support\Carbon::today()->toDateString() }}">
                                            <div class="form-text">
                                                The real date the external system settled this payment.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Register payment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            @endif
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Adjustment history</strong></div>
        <div class="card-body">
            @php
                $items = $history ?? [];
                $hasAdjustments = collect($items)->contains(fn($e) => ($e['kind'] ?? null) === 'adjustment');
            @endphp
            @if(empty($items))
                <p class="text-muted mb-0">No history.</p>
            @else
                <div class="timeline">
                    @foreach($items as $entry)
                        @php
                            $kind = $entry['kind'] ?? null;
                            $date = $entry['date'] ?? null;
                            $data = $entry['data'] ?? [];
                            $diff = $data['diff'] ?? [];
                        @endphp
                        @if($kind === 'adjustment')
                            <div class="border-start border-3 border-warning ps-3 mb-3">
                                <div class="small text-muted d-flex justify-content-between align-items-center">
                                    <span>
                                        {{ optional($date)->format('Y-m-d') }}
                                        @if(!empty($data['adjusted_by']))
                                            &mdash; by {{ $data['adjusted_by']->email ?? $data['adjusted_by']->name ?? 'admin' }}
                                        @else
                                            &mdash; system
                                        @endif
                                    </span>
                                    @if(!empty($data['id']))
                                        <span>
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0 me-2"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#schedule-snapshot-{{ $data['id'] }}">
                                                View schedule at this point
                                            </button>
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#trajectory-through-{{ $data['id'] }}">
                                                View trajectory through this point
                                            </button>
                                        </span>
                                    @endif
                                </div>
                                <div class="row mt-1">
                                    <div class="col-sm-6">
                                        <strong class="{{ in_array('term_months', $diff) ? 'text-danger' : 'text-muted' }}">
                                            Term:
                                        </strong>
                                        {{ $data['old_term_months'] ?? '' }} &rarr; {{ $data['new_term_months'] ?? '' }} mo
                                    </div>
                                    <div class="col-sm-6">
                                        <strong class="{{ in_array('payment_frequency', $diff) ? 'text-danger' : 'text-muted' }}">
                                            Frequency:
                                        </strong>
                                        {{ $data['old_payment_frequency'] ?? '' }} &rarr; {{ $data['new_payment_frequency'] ?? '' }}
                                    </div>
                                    <div class="col-sm-6">
                                        <strong class="{{ in_array('maturity_date', $diff) ? 'text-danger' : 'text-muted' }}">
                                            Maturity:
                                        </strong>
                                        {{ optional($data['old_maturity_date'] ?? null)->format('Y-m-d') }}
                                        &rarr; {{ optional($data['new_maturity_date'] ?? null)->format('Y-m-d') }}
                                    </div>
                                    <div class="col-sm-6">
                                        <strong class="{{ in_array('planned_payoff_date', $diff) ? 'text-danger' : 'text-muted' }}">
                                            Planned payoff:
                                        </strong>
                                        {{ optional($data['old_planned_payoff_date'] ?? null)->format('Y-m-d') }}
                                        &rarr; {{ optional($data['new_planned_payoff_date'] ?? null)->format('Y-m-d') }}
                                    </div>
                                </div>
                                <div class="small text-muted mt-1">
                                    Outstanding at change: {{ number_format($data['outstanding_shares_at_adjustment'] ?? 0, 4) }} shares
                                    @if(!empty($data['reason']))
                                        &mdash; <em>"{{ $data['reason'] }}"</em>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="border-start border-3 border-secondary ps-3 mb-3">
                                <div class="small text-muted">
                                    {{ optional($date)->format('Y-m-d') }} &mdash; origination
                                </div>
                                <div>
                                    Principal {{ number_format($data['principal_shares'] ?? 0, 4) }} shares
                                    &middot; {{ $data['term_months'] ?? '' }} mo
                                    &middot; {{ $data['payment_frequency'] ?? '' }}
                                    &middot; matures {{ optional($data['maturity_date'] ?? null)->format('Y-m-d') }}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if(!$hasAdjustments)
                    <p class="text-muted small mb-0">(no adjustments yet)</p>
                @endif
            @endif
        </div>
    </div>

    {{-- Schedule-snapshot and trajectory-truncated modals per adjustment (Phase 4). --}}
    @foreach($items as $entry)
        @php
            $kind = $entry['kind'] ?? null;
            $data = $entry['data'] ?? [];
            $adjId = $data['id'] ?? null;
            $adjAt = $data['adjusted_at'] ?? $entry['date'] ?? null;
            $adjAtStr = $adjAt ? \Illuminate\Support\Carbon::parse($adjAt)->format('Y-m-d') : '';
            $snapshot = ($adjId && isset($scheduleSnapshots[$adjId])) ? $scheduleSnapshots[$adjId] : collect();
        @endphp
        @if($kind === 'adjustment' && $adjId)
            <div class="modal fade"
                 id="schedule-snapshot-{{ $adjId }}"
                 tabindex="-1"
                 aria-labelledby="schedule-snapshot-{{ $adjId }}-label"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="schedule-snapshot-{{ $adjId }}-label">
                                Schedule as of {{ $adjAtStr }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @if($snapshot->isEmpty())
                                <p class="text-muted mb-0">No schedule rows existed at this point.</p>
                            @else
                                <table class="table table-sm">
                                    <thead>
                                        <tr><th>#</th><th>Due date</th><th>Shares</th><th>Status (then)</th></tr>
                                    </thead>
                                    <tbody>
                                    @foreach($snapshot as $row)
                                        <tr>
                                            <td>{{ $row->sequence_number ?? $row->id }}</td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                                            <td>{{ number_format($row->shares_due, 4) }}</td>
                                            <td>{{ $row->status }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade"
                 id="trajectory-through-{{ $adjId }}"
                 tabindex="-1"
                 aria-labelledby="trajectory-through-{{ $adjId }}-label"
                 aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="trajectory-through-{{ $adjId }}-label">
                                Trajectory through {{ $adjAtStr }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            @include('account_credit_lines._trajectory_chart', [
                                'trajectory' => $trajectory ?? [],
                                'chartUrl' => null,
                                'truncateAt' => $adjAtStr,
                            ])
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
</x-app-layout>
