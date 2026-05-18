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

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Credit Line #{{ $line->id }} — {{ $line->nickname }}</strong>
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

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong>Payment schedule</strong>
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
                            @include('account_credit_lines._payment_status_badge', ['status' => $row->status])
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
        <div class="card-header"><strong>Adjustment history</strong></div>
        <div class="card-body">
            @php
                $items = $history ?? [];
                $hasAdjustments = collect($items)->contains(fn($e) => ($e['kind'] ?? null) === 'adjustment');
            @endphp
            @if(empty($items))
                <p class="text-muted mb-0">No history.</p>
            @else
                <div class="table-responsive-sm timeline">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event</th>
                            <th>Changes</th>
                            <th class="text-end">Outstanding</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $entry)
                        @php
                            $kind = $entry['kind'] ?? null;
                            $date = $entry['date'] ?? null;
                            $data = $entry['data'] ?? [];
                            $diff = $data['diff'] ?? [];
                        @endphp
                        @if($kind === 'adjustment')
                            <tr data-kind="adjustment">
                                <td class="text-nowrap">
                                    {{ optional($date)->format('Y-m-d') }}
                                    <div class="small text-muted">
                                        @if(!empty($data['adjusted_by']))
                                            by {{ $data['adjusted_by']->email ?? $data['adjusted_by']->name ?? 'admin' }}
                                        @else
                                            system
                                        @endif
                                    </div>
                                </td>
                                <td class="text-nowrap">Adjustment</td>
                                <td>
                                    <div>
                                        <span class="{{ in_array('term_months', $diff) ? 'text-danger fw-bold' : 'text-muted' }}">Term:</span>
                                        {{ $data['old_term_months'] ?? '' }} &rarr; {{ $data['new_term_months'] ?? '' }} mo
                                    </div>
                                    <div>
                                        <span class="{{ in_array('payment_frequency', $diff) ? 'text-danger fw-bold' : 'text-muted' }}">Frequency:</span>
                                        {{ $data['old_payment_frequency'] ?? '' }} &rarr; {{ $data['new_payment_frequency'] ?? '' }}
                                    </div>
                                    <div>
                                        <span class="{{ in_array('maturity_date', $diff) ? 'text-danger fw-bold' : 'text-muted' }}">Maturity:</span>
                                        {{ optional($data['old_maturity_date'] ?? null)->format('Y-m-d') }}
                                        &rarr; {{ optional($data['new_maturity_date'] ?? null)->format('Y-m-d') }}
                                    </div>
                                    <div>
                                        <span class="{{ in_array('planned_payoff_date', $diff) ? 'text-danger fw-bold' : 'text-muted' }}">Planned payoff:</span>
                                        {{ optional($data['old_planned_payoff_date'] ?? null)->format('Y-m-d') }}
                                        &rarr; {{ optional($data['new_planned_payoff_date'] ?? null)->format('Y-m-d') }}
                                    </div>
                                    @if(!empty($data['effective_date']))
                                    <div>
                                        <span class="text-muted">Schedule start:</span>
                                        {{ optional($data['effective_date'])->format('Y-m-d') }}
                                    </div>
                                    @endif
                                    @if(!empty($data['reason']))
                                        <div class="small text-muted fst-italic">"{{ $data['reason'] }}"</div>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    {{ number_format($data['outstanding_shares_at_adjustment'] ?? 0, 4) }}
                                </td>
                                <td class="text-end text-nowrap">
                                    @if(!empty($data['id']))
                                        <button type="button"
                                                class="btn btn-link btn-sm p-0 d-block ms-auto"
                                                data-bs-toggle="modal"
                                                data-bs-target="#schedule-snapshot-{{ $data['id'] }}">
                                            View schedule at this point
                                        </button>
                                        <button type="button"
                                                class="btn btn-link btn-sm p-0 d-block ms-auto"
                                                data-bs-toggle="modal"
                                                data-bs-target="#trajectory-through-{{ $data['id'] }}">
                                            View trajectory through this point
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @else
                            <tr data-kind="origination">
                                <td class="text-nowrap">{{ optional($date)->format('Y-m-d') }}</td>
                                <td class="text-nowrap text-muted">Origination</td>
                                <td>
                                    Principal {{ number_format($data['principal_shares'] ?? 0, 4) }} shares
                                    &middot; {{ $data['term_months'] ?? '' }} mo
                                    &middot; {{ $data['payment_frequency'] ?? '' }}
                                    &middot; matures {{ optional($data['maturity_date'] ?? null)->format('Y-m-d') }}
                                </td>
                                <td class="text-end text-nowrap">
                                    {{ number_format($data['principal_shares'] ?? 0, 4) }}
                                </td>
                                <td></td>
                            </tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
                </div>
                @if(!$hasAdjustments)
                    <p class="text-muted small mt-2 mb-0">(no adjustments yet)</p>
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
