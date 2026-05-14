@php
    $account = $cashDeposit->account;
    $transaction = $cashDeposit->transaction;
    $unassigned = $cashDeposit->amount - $cashDeposit->depositRequests->sum('amount');

    $statusClasses = [
        'PEN' => 'badge-cd-pending',
        'DEP' => 'badge-cd-deposited',
        'ALL' => 'badge-cd-allocated',
        'COM' => 'badge-cd-completed',
        'CAN' => 'badge-cd-cancelled',
    ];
    $sc = $statusClasses[$cashDeposit->status] ?? 'badge-gray';
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
                <span class="{{ $sc }} text-sm px-3 py-1">
                    {{ $cashDeposit->status_string() }}
                </span>
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
                    <td>@include('partials.view_link', ['route' => route('depositRequests.show', $dr->id), 'text' => '#' . $dr->id])</td>
                    <td>
                        @if($dr->account)
                            @include('partials.view_link', ['route' => route('accounts.show', $dr->account_id), 'text' => $dr->account->nickname])
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
