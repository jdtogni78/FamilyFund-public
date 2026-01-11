@php
    $account = $depositRequest->account;
    $cashDeposit = $depositRequest->cashDeposit;
    $transaction = $depositRequest->transaction;

    $statusColors = [
        'PEN' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309'],
        'APP' => ['bg' => '#cffafe', 'border' => '#0891b2', 'text' => '#0e7490'],
        'REJ' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#b91c1c'],
        'COM' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d'],
    ];
    $sc = $statusColors[$depositRequest->status] ?? ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569'];
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
                    <span class="text-body-secondary">N/A</span>
                @endif
            </p>
        </div>

        <!-- Date Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-calendar me-1"></i> Date:</label>
            <p class="mb-0 fw-bold">{{ $depositRequest->date }}</p>
        </div>

        <!-- Description Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-align-left me-1"></i> Description:</label>
            <p class="mb-0">{{ $depositRequest->description ?: '-' }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Amount Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Amount:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold text-success">${{ number_format($depositRequest->amount, 2) }}</span>
            </p>
        </div>

        <!-- Status Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-info-circle me-1"></i> Status:</label>
            <p class="mb-0">
                <span class="badge" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; border: 1px solid {{ $sc['border'] }}; font-size: 14px; padding: 6px 12px;">
                    {{ $depositRequest->status_string() }}
                </span>
            </p>
        </div>

        <!-- Cash Deposit Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Cash Deposit:</label>
            <p class="mb-0">
                @if($cashDeposit)
                    <a href="{{ route('cashDeposits.show', $cashDeposit->id) }}">
                        #{{ $cashDeposit->id }}
                    </a>
                    <span class="text-body-secondary">(${{ number_format($cashDeposit->amount, 2) }} on {{ $cashDeposit->date }})</span>
                @elseif($depositRequest->cash_deposit_id)
                    #{{ $depositRequest->cash_deposit_id }}
                @else
                    <span class="text-body-secondary">Not linked</span>
                @endif
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
                @elseif($depositRequest->transaction_id)
                    #{{ $depositRequest->transaction_id }}
                @else
                    <span class="text-body-secondary">Not yet created</span>
                @endif
            </p>
        </div>

        <!-- Deposit Request ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Deposit Request ID:</label>
            <p class="mb-0">#{{ $depositRequest->id }}</p>
        </div>
    </div>
</div>
