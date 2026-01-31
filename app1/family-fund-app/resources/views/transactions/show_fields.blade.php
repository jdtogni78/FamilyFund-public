@php
    $isDebit = $transaction->value < 0;
    $typeClasses = [
        'PUR' => ['class' => 'badge-tx-purchase', 'label' => 'Purchase'],
        'INI' => ['class' => 'badge-tx-initial', 'label' => 'Initial'],
        'SAL' => ['class' => 'badge-tx-sale', 'label' => 'Sale'],
        'MAT' => ['class' => 'badge-tx-matching', 'label' => 'Matching'],
        'BOR' => ['class' => 'badge-tx-borrow', 'label' => 'Borrow'],
        'REP' => ['class' => 'badge-tx-repay', 'label' => 'Repay'],
    ];
    $tc = $typeClasses[$transaction->type] ?? ['class' => 'badge-gray', 'label' => $transaction->type];

    $statusClasses = [
        'P' => ['class' => 'badge-status-pending', 'label' => 'Pending'],
        'C' => ['class' => 'badge-status-cleared', 'label' => 'Cleared'],
        'S' => ['class' => 'badge-status-scheduled', 'label' => 'Scheduled'],
    ];
    $sc = $statusClasses[$transaction->status] ?? ['class' => 'badge-gray', 'label' => $transaction->status];
@endphp

<div class="row">
    <!-- Transaction Summary -->
    <div class="col-md-6 mb-4">
        <div class="card h-100 {{ $isDebit ? 'card-border-debit' : 'card-border-credit' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="{{ $tc['class'] }} me-2 text-sm px-3 py-1">
                        {{ $tc['label'] }}
                    </span>
                    <span class="{{ $sc['class'] }} text-sm px-3 py-1">
                        {{ $sc['label'] }}
                    </span>
                </div>
                <span class="text-muted">#{{ $transaction->id }}</span>
            </div>
            <div class="card-body">
                <!-- Amount -->
                <div class="text-center mb-4">
                    <span class="{{ $isDebit ? 'badge-negative' : 'badge-positive' }} badge-value-lg">
                        {{ $isDebit ? '-' : '+' }}${{ number_format(abs($transaction->value), 2) }}
                    </span>
                </div>

                <!-- Details Grid -->
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="text-muted small text-uppercase">Date</div>
                        <div class="fs-5 fw-bold">{{ $transaction->timestamp->format('M j, Y') }}</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-muted small text-uppercase">Shares</div>
                        <div class="fs-5 fw-bold {{ $transaction->shares >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $transaction->shares >= 0 ? '+' : '' }}{{ number_format($transaction->shares, 4) }}
                        </div>
                    </div>
                </div>

                @if($transaction->descr)
                <div class="rounded p-2 mt-2 bg-slate-100 dark:bg-slate-700">
                    <div class="text-muted small">Description</div>
                    <div class="text-body">{{ $transaction->descr }}</div>
                </div>
                @endif

                @if($transaction->flags)
                <div class="mt-2">
                    <span class="badge bg-primary">{{ \App\Models\TransactionExt::$flagsMap[$transaction->flags] ?? $transaction->flags }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Account Info -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-light">
                <i class="fa fa-user me-2"></i><strong>Account</strong>
            </div>
            <div class="card-body">
                @php
                    $account = $transaction->account;
                @endphp
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1">
                            @include('partials.view_link', ['route' => route('accounts.show', $account->id), 'text' => $account->nickname])
                        </h5>
                        @if($account->code)
                            <span class="text-muted">Code: {{ $account->code }}</span>
                        @endif
                    </div>
                    <span class="badge bg-primary">{{ $account->fund->name ?? 'Fund' }}</span>
                </div>

                @if($account->user)
                <div class="mb-3">
                    <div class="text-muted small">Owner</div>
                    <div><i class="fa fa-user-circle me-1"></i>{{ $account->user->name }}</div>
                    @if($account->user->email)
                        <div class="small text-muted"><i class="fa fa-envelope me-1"></i>{{ $account->user->email }}</div>
                    @endif
                </div>
                @endif

                @if($account->email_cc)
                <div class="mb-2">
                    <div class="text-muted small">Notification Email</div>
                    <div><i class="fa fa-at me-1"></i>{{ $account->email_cc }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Balance Change -->
@if($transaction->balance)
@php
    $balance = $transaction->balance;
    $prevShares = $balance->previousBalance?->shares ?? 0;
    $delta = $balance->shares - $prevShares;
@endphp
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <i class="fa fa-chart-pie me-2"></i><strong>Balance Change</strong>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center py-3">
                    <div class="text-center px-4">
                        <div class="text-muted small text-uppercase">Before</div>
                        <div class="fs-3 text-muted">{{ number_format($prevShares, 4) }}</div>
                        <div class="small text-muted">shares</div>
                    </div>
                    <div class="mx-4">
                        <i class="fa fa-long-arrow-right fa-2x {{ $delta >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}"></i>
                    </div>
                    <div class="text-center px-4">
                        <div class="text-muted small text-uppercase">After</div>
                        <div class="fs-3 fw-bold {{ $delta >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($balance->shares, 4) }}
                        </div>
                        <div class="small fw-bold">shares</div>
                    </div>
                    <div class="ms-4">
                        <span class="{{ $delta >= 0 ? 'badge-positive' : 'badge-negative' }} text-lg px-3 py-2">
                            {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 4) }}
                        </span>
                    </div>
                </div>
                <div class="text-center text-muted small">
                    <i class="fa fa-calendar me-1"></i>Effective: {{ $balance->start_dt }}
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Related IDs (compact) -->
@if($transaction->cashDeposit || $transaction->depositRequest || $transaction->scheduled_job_id)
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-light">
                <i class="fa fa-link me-2"></i><strong>Related Records</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    @if($transaction->cashDeposit)
                    <div class="col-md-4">
                        <div class="text-muted small">Cash Deposit</div>
                        @include('partials.view_link', ['route' => route('cashDeposits.show', $transaction->cashDeposit->id), 'text' => '#' . $transaction->cashDeposit->id])
                    </div>
                    @endif
                    @if($transaction->depositRequest)
                    <div class="col-md-4">
                        <div class="text-muted small">Deposit Request</div>
                        @include('partials.view_link', ['route' => route('depositRequests.show', $transaction->depositRequest->id), 'text' => '#' . $transaction->depositRequest->id])
                    </div>
                    @endif
                    @if($transaction->scheduled_job_id)
                    @php
                        $sj = $transaction->scheduledJob;
                    @endphp
                    <div class="col-md-4">
                        <div class="text-muted small">Scheduled Job</div>
                        @include('partials.view_link', ['route' => route('scheduledJobs.show', $transaction->scheduled_job_id), 'text' => '#' . $transaction->scheduled_job_id])
                        @if($sj)
                            <span class="badge bg-primary ms-1">{{ \App\Models\ScheduledJobExt::$entityMap[$sj->entity_descr] ?? $sj->entity_descr }}</span>
                            @if($sj->schedule)
                                <br><small class="text-muted">{{ $sj->schedule->descr }}</small>
                            @endif
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
