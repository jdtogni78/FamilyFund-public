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

    @if($isAdmin)
    <div class="mb-3 d-flex flex-wrap gap-2">
        @if($line->status === 'active')
            <a href="{{ route('credit_lines.actions', ['line' => $line->id]) }}"
               class="btn btn-primary btn-sm">Admin actions</a>
        @endif
        <a href="{{ route('credit_lines.simulator', ['line' => $line->id]) }}"
           class="btn btn-outline-secondary btn-sm">Payment simulator</a>
        <a href="{{ route('credit_lines.edit', ['line' => $line->id]) }}"
           class="btn btn-outline-secondary btn-sm">Notification settings</a>
    </div>
    @endif

    @if(!empty($loansSummary ?? []))
        @include('account_credit_lines._loans_summary_card', ['loansSummary' => $loansSummary])
    @endif

    @php
        $statusBadge = [
            'active'     => ['bg-success',   'fa-circle-play'],
            'paid_off'   => ['bg-secondary', 'fa-circle-check'],
            'cancelled'  => ['bg-dark',      'fa-circle-xmark'],
        ][$line->status] ?? ['bg-info', 'fa-circle-info'];
        $statusLabel = \App\Models\AccountCreditLineExt::$statusMap[$line->status] ?? ucfirst(str_replace('_', ' ', $line->status));

        $principal   = (float) $line->principal_shares;
        $outstanding = (float) $line->outstanding_shares;
        $repaid      = max(0.0, $principal - $outstanding);
        $repaidPct   = $principal > 0 ? min(100, round($repaid / $principal * 100)) : 0;

        $sv = 0;
        try { $sv = (float) $account?->shareValueAsOf(now()->toDateString()); } catch (\Throwable $e) {}
    @endphp

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fa fa-credit-card me-2"></i>
                <strong>Credit Line #{{ $line->id }}</strong>
                @if($line->nickname)
                    <span class="text-body-secondary ms-2">{{ $line->nickname }}</span>
                @endif
            </div>
            <span class="badge {{ $statusBadge[0] }}">
                <i class="fa {{ $statusBadge[1] }} me-1"></i>{{ $statusLabel }}
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <small class="text-muted">Principal (shares)</small>
                    <div class="h5 mb-0">{{ number_format($principal, 4) }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted">Repaid (shares)</small>
                    <div class="h5 mb-0 text-success">{{ number_format($repaid, 4) }}</div>
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted">Outstanding (shares)</small>
                    <div class="h5 mb-0">{{ number_format($outstanding, 4) }}</div>
                    @if($sv > 0)
                        <small class="text-muted">≈ ${{ number_format($outstanding * $sv, 2) }} today</small>
                    @endif
                </div>
                <div class="col-md-3 mb-3">
                    <small class="text-muted">Term &middot; Frequency</small>
                    <div class="h5 mb-0">{{ $line->term_months }} mo</div>
                    <small class="text-muted">{{ $line->payment_frequency }}</small>
                </div>
            </div>

            <div class="mb-1 d-flex justify-content-between small text-muted">
                <span>Repayment progress</span>
                <span>{{ $repaidPct }}% &mdash; {{ number_format($repaid, 4) }} of {{ number_format($principal, 4) }} shares</span>
            </div>
            <div class="progress mb-3" style="height: 10px;" role="progressbar"
                 aria-valuenow="{{ $repaidPct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: {{ $repaidPct }}%;"></div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block"><i class="fa fa-calendar-plus me-1"></i>Origination</small>
                    {{ optional($line->origination_date)->format('Y-m-d') ?? '—' }}
                </div>
                <div class="col-md-3 mb-2">
                    <small class="text-muted d-block"><i class="fa fa-calendar-check me-1"></i>Maturity</small>
                    {{ optional($line->maturity_date)->format('Y-m-d') ?? '—' }}
                </div>
                @if($line->descr)
                <div class="col-md-6 mb-2">
                    <small class="text-muted d-block"><i class="fa fa-note-sticky me-1"></i>Description</small>
                    {{ $line->descr }}
                </div>
                @endif
            </div>

            <p class="text-muted small mt-2 mb-0 border-top pt-2">
                <i class="fa fa-circle-info me-1"></i>
                You owe {{ number_format($outstanding, 4) }} shares
                @if($sv > 0)
                    (currently valued at ${{ number_format($outstanding * $sv, 2) }})
                @endif. The share count is what you owe back &mdash; it doesn't change with the market.
                The dollar value will move up or down with the fund's share price.
            </p>
        </div>
    </div>

    @if(!empty($trajectory ?? []))
        @include('account_credit_lines._trajectory_chart', ['trajectory' => $trajectory, 'chartUrl' => null])
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong><i class="fa fa-calendar-days me-2"></i>Payment schedule</strong>
            @unless($schedule->isEmpty())
            <div class="btn-group btn-group-sm" role="group" aria-label="Filter schedule by status" id="schedule-status-filter">
                @php $statuses = ['all' => 'All', 'scheduled' => 'Scheduled', 'late' => 'Late', 'partial' => 'Partial', 'paid' => 'Paid', 'cancelled' => 'Cancelled']; @endphp
                @foreach($statuses as $value => $label)
                    <button type="button"
                            class="btn btn-outline-secondary {{ $value === 'all' ? 'active' : '' }}"
                            data-status-filter="{{ $value }}">{{ $label }}</button>
                @endforeach
            </div>
            @endunless
        </div>
        <div class="card-body">
            @if($schedule->isEmpty())
                <p class="text-muted mb-0">No schedule rows.</p>
            @else
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th><th>Due date</th><th>Shares</th><th>Status</th><th>Paid by tx</th>
                        @if($isAdmin)<th></th>@endif
                    </tr>
                </thead>
                <tbody>
                @foreach($schedule as $row)
                    <tr data-status="{{ $row->status }}">
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
                            @elseif($row->status === 'cancelled')
                                <span class="badge text-decoration-line-through" style="background-color:#6b7280;">cancelled</span>
                            @else
                                <span class="badge bg-primary">scheduled</span>
                            @endif
                        </td>
                        <td>
                            @if($row->paid_transaction_id)
                                <a href="{{ route('transactions.show', $row->paid_transaction_id) }}">#{{ $row->paid_transaction_id }}</a>
                            @endif
                        </td>
                        @if($isAdmin)
                        <td class="text-end">
                            @include('account_credit_lines._payment_row_actions', ['line' => $line, 'row' => $row])
                        </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <script>
        (function () {
            var bar = document.getElementById('schedule-status-filter');
            if (!bar) return;
            bar.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-status-filter]');
                if (!btn) return;
                var want = btn.getAttribute('data-status-filter');
                bar.querySelectorAll('[data-status-filter]').forEach(function (b) {
                    b.classList.toggle('active', b === btn);
                });
                document.querySelectorAll('tr[data-status]').forEach(function (tr) {
                    tr.style.display = (want === 'all' || tr.getAttribute('data-status') === want) ? '' : 'none';
                });
            });
        })();
    </script>

    <div class="card mb-3">
        <div class="card-header"><strong><i class="fa fa-clock-rotate-left me-2"></i>Adjustment history</strong></div>
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
                                    @if(!empty($data['effective_date']))
                                    <div class="col-sm-6">
                                        <strong class="text-muted">Schedule start:</strong>
                                        {{ optional($data['effective_date'])->format('Y-m-d') }}
                                    </div>
                                    @endif
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
