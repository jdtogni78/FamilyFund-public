@php
    $account = $cashDeposit->account;
    $transaction = $cashDeposit->transaction;
    $unassigned = $cashDeposit->amount - $cashDeposit->depositRequests->sum('amount');

    $statusColors = [
        'PEN' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309'],
        'DEP' => ['bg' => '#cffafe', 'border' => '#0891b2', 'text' => '#0e7490'],
        'ALL' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d'],
        'COM' => ['bg' => '#f3e8ff', 'border' => '#9333ea', 'text' => '#7c3aed'],
        'CAN' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#b91c1c'],
    ];
    $sc = $statusColors[$cashDeposit->status] ?? ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569'];
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
            <p class="mb-0 fw-bold">{{ $cashDeposit->date }}</p>
        </div>

        <!-- Description Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-align-left me-1"></i> Description:</label>
            <p class="mb-0">{{ $cashDeposit->description ?: '-' }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Amount Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-dollar-sign me-1"></i> Amount:</label>
            <p class="mb-0">
                <span class="fs-4 fw-bold text-success">${{ number_format($cashDeposit->amount, 2) }}</span>
            </p>
        </div>

        <!-- Unassigned Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-exclamation-circle me-1"></i> Unassigned:</label>
            <p class="mb-0">
                <span class="fs-5 fw-bold {{ $unassigned == 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($unassigned, 2) }}
                </span>
                @if($unassigned == 0)
                    <span class="badge bg-success ms-2">Fully Assigned</span>
                @endif
            </p>
        </div>

        <!-- Status Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-info-circle me-1"></i> Status:</label>
            <p class="mb-0">
                <span class="badge" style="background: {{ $sc['bg'] }}; color: {{ $sc['text'] }}; border: 1px solid {{ $sc['border'] }}; font-size: 14px; padding: 6px 12px;">
                    {{ $cashDeposit->status_string() }}
                </span>
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
                @elseif($cashDeposit->transaction_id)
                    #{{ $cashDeposit->transaction_id }}
                @else
                    <span class="text-body-secondary">Not yet created</span>
                @endif
            </p>
        </div>

        <!-- Cash Deposit ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Cash Deposit ID:</label>
            <p class="mb-0">#{{ $cashDeposit->id }}</p>
        </div>
    </div>
</div>

@if($cashDeposit->depositRequests->count() > 0)
<hr class="my-3">
<div class="form-group mb-3">
    <label class="text-body-secondary"><i class="fa fa-hand-holding-usd me-1"></i> Deposit Requests ({{ $cashDeposit->depositRequests->count() }}):</label>
    <div class="table-responsive mt-2">
        <table class="table table-sm">
            <thead>
                <tr class="bg-slate-50 dark:bg-slate-700">
                    <th>ID</th>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cashDeposit->depositRequests as $dr)
                <tr>
                    <td><a href="{{ route('depositRequests.show', $dr->id) }}">#{{ $dr->id }}</a></td>
                    <td>
                        @if($dr->account)
                            <a href="{{ route('accounts.show', $dr->account_id) }}">{{ $dr->account->nickname }}</a>
                        @else
                            #{{ $dr->account_id }}
                        @endif
                    </td>
                    <td>${{ number_format($dr->amount, 2) }}</td>
                    <td>{{ $dr->status_string() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
