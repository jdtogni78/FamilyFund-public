@php
    $isDebit = $transaction->value < 0;
    $typeColors = [
        'PUR' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d', 'label' => 'Purchase'],
        'INI' => ['bg' => '#ccfbf1', 'border' => '#0d9488', 'text' => '#0f766e', 'label' => 'Initial'],
        'SAL' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#b91c1c', 'label' => 'Sale'],
        'MAT' => ['bg' => '#f3e8ff', 'border' => '#9333ea', 'text' => '#7c3aed', 'label' => 'Matching'],
        'BOR' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309', 'label' => 'Borrow'],
        'REP' => ['bg' => '#cffafe', 'border' => '#0891b2', 'text' => '#0e7490', 'label' => 'Repay'],
    ];
    $tc = $typeColors[$transaction->type] ?? ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569', 'label' => $transaction->type];

    $statusColors = [
        'P' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309', 'label' => 'Pending'],
        'C' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d', 'label' => 'Cleared'],
        'S' => ['bg' => '#cffafe', 'border' => '#0891b2', 'text' => '#0e7490', 'label' => 'Scheduled'],
    ];
    $sc = $statusColors[$transaction->status] ?? ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569', 'label' => $transaction->status];
@endphp

<div class="row">
    <!-- Transaction Summary -->
    <div class="col-md-6 mb-4">
        <div class="card h-100" style="border-left: 4px solid {{ $isDebit ? '#dc3545' : '#28a745' }};">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge me-2" style="background: {{ $tc['bg'] }}; color: {{ $tc['text'] }}; border: 1px solid {{ $tc['border'] }}; font-size: 14px; padding: 6px 12px;">
                        {{ $tc['label'] }}
                    </span>
                    <span class="badge" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; border: 1px solid {{ $sc['border'] }}; font-size: 14px; padding: 6px 12px;">
                        {{ $sc['label'] }}
                    </span>
                </div>
                <span class="text-muted">#{{ $transaction->id }}</span>
            </div>
            <div class="card-body">
                <!-- Amount -->
                <div class="text-center mb-4">
                    <span class="badge fs-3 px-4 py-2" style="background-color: {{ $isDebit ? '#dc3545' : '#28a745' }}; color: white;">
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
                        <div class="fs-5 fw-bold" style="color: {{ $transaction->shares >= 0 ? '#28a745' : '#dc3545' }};">
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
                            <a href="{{ route('accounts.show', $account->id) }}" class="text-decoration-none">
                                {{ $account->nickname }}
                            </a>
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
                        <i class="fa fa-long-arrow-right fa-2x" style="color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }};"></i>
                    </div>
                    <div class="text-center px-4">
                        <div class="text-muted small text-uppercase">After</div>
                        <div class="fs-3 fw-bold" style="color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }};">
                            {{ number_format($balance->shares, 4) }}
                        </div>
                        <div class="small fw-bold">shares</div>
                    </div>
                    <div class="ms-4">
                        <span class="badge fs-5 px-3 py-2" style="background-color: {{ $delta >= 0 ? '#28a745' : '#dc3545' }}; color: white;">
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
                        <a href="{{ route('cashDeposits.show', $transaction->cashDeposit->id) }}">#{{ $transaction->cashDeposit->id }}</a>
                    </div>
                    @endif
                    @if($transaction->depositRequest)
                    <div class="col-md-4">
                        <div class="text-muted small">Deposit Request</div>
                        <a href="{{ route('depositRequests.show', $transaction->depositRequest->id) }}">#{{ $transaction->depositRequest->id }}</a>
                    </div>
                    @endif
                    @if($transaction->scheduled_job_id)
                    <div class="col-md-4">
                        <div class="text-muted small">Scheduled Job</div>
                        <a href="{{ route('scheduledJobs.show', $transaction->scheduled_job_id) }}">#{{ $transaction->scheduled_job_id }}</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
