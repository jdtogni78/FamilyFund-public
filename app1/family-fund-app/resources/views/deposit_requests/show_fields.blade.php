@php
    $account = $depositRequest->account;
    $cashDeposit = $depositRequest->cashDeposit;
    $transaction = $depositRequest->transaction;

    $statusClasses = [
        'PEN' => 'badge-dr-pending',
        'APP' => 'badge-dr-approved',
        'REJ' => 'badge-dr-rejected',
        'COM' => 'badge-dr-completed',
    ];
    $sc = $statusClasses[$depositRequest->status] ?? 'badge-gray';
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Account Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-user me-1"></i> Account:</label>
            <p class="mb-0">
                @if($account)
                    @include('partials.view_link', ['route' => route('accounts.show', $account->id), 'text' => $account->nickname, 'class' => 'fw-bold'])
                    @if($account->code)
                        <span class="text-body-secondary">({{ $account->code }})</span>
                    @endif
                    @if($account->fund)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-landmark me-1"></i>
                            @include('partials.view_link', ['route' => route('funds.show', $account->fund_id), 'text' => $account->fund->name])
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
                <span class="{{ $sc }} text-sm px-3 py-1">
                    {{ $depositRequest->status_string() }}
                </span>
            </p>
        </div>

        <!-- Cash Deposit Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Cash Deposit:</label>
            <p class="mb-0">
                @if($cashDeposit)
                    @include('partials.view_link', ['route' => route('cashDeposits.show', $cashDeposit->id), 'text' => '#' . $cashDeposit->id])
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
                    @include('partials.view_link', ['route' => route('transactions.show', $transaction->id), 'text' => '#' . $transaction->id])
                    <span class="{{ $transaction->value >= 0 ? 'badge-positive' : 'badge-negative' }} ms-1">
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
