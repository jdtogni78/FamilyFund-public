@php
    $account = $accountBalance->account;
    $transaction = $accountBalance->transaction;
    $prevBalance = $accountBalance->previousBalance;
    $sharesDelta = $accountBalance->shares - ($prevBalance?->shares ?? 0);
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Account Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Account:</label>
            <p class="mb-0">
                @if($account)
                    <a href="{{ route('accounts.show', $account->id) }}" class="fw-bold">
                        {{ $account->nickname }}
                    </a>
                    @if($account->code)
                        <span class="text-body-secondary">({{ $account->code }})</span>
                    @endif
                    @if($account->fund)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-landmark me-1"></i>
                            <a href="{{ route('funds.show', $account->fund_id) }}">{{ $account->fund->name }}</a>
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $accountBalance->account_id }}</span>
                @endif
            </p>
        </div>

        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-tag me-1"></i> Type:</label>
            <p class="mb-0">
                <span class="badge bg-primary">{{ $accountBalance->type }}</span>
            </p>
        </div>

        <!-- Transaction Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-exchange-alt me-1"></i> Transaction:</label>
            <p class="mb-0">
                @if($transaction)
                    <a href="{{ route('transactions.show', $transaction->id) }}">
                        #{{ $transaction->id }}
                    </a>
                    <span class="badge ms-1" style="background: {{ $transaction->value >= 0 ? '#28a745' : '#dc3545' }}; color: white;">
                        ${{ number_format(abs($transaction->value), 2) }}
                    </span>
                    <br><small class="text-body-secondary">
                        {{ \App\Models\TransactionExt::$typeMap[$transaction->type] ?? $transaction->type }}
                        - {{ $transaction->timestamp->format('M j, Y') }}
                    </small>
                @else
                    <span class="text-body-secondary">N/A</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Shares Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-chart-pie me-1"></i> Shares:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold">{{ number_format($accountBalance->shares, 4) }}</span>
                @if($sharesDelta != 0)
                    <span class="badge ms-2" style="background: {{ $sharesDelta >= 0 ? '#28a745' : '#dc3545' }}; color: white;">
                        {{ $sharesDelta >= 0 ? '+' : '' }}{{ number_format($sharesDelta, 4) }}
                    </span>
                @endif
            </p>
        </div>

        <!-- Previous Balance Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-history me-1"></i> Previous Balance:</label>
            <p class="mb-0">
                @if($prevBalance)
                    <a href="{{ route('accountBalances.show', $prevBalance->id) }}">
                        #{{ $prevBalance->id }}
                    </a>
                    <span class="text-body-secondary">({{ number_format($prevBalance->shares, 4) }} shares)</span>
                @else
                    <span class="text-body-secondary">Initial balance</span>
                @endif
            </p>
        </div>

        <!-- Date Range Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Effective Period:</label>
            <p class="mb-0">
                {{ $accountBalance->start_dt }} <i class="fa fa-arrow-right mx-2 text-body-secondary"></i> {{ $accountBalance->end_dt }}
                @if($accountBalance->end_dt == '9999-12-31')
                    <span class="badge bg-success ms-2">Current</span>
                @endif
            </p>
        </div>

        <!-- Balance ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Balance ID:</label>
            <p class="mb-0">#{{ $accountBalance->id }}</p>
        </div>
    </div>
</div>
