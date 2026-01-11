@php
    $matchingRule = $transactionMatching->matchingRule;
    $transaction = $transactionMatching->transaction;
    $refTransaction = $transactionMatching->referenceTransaction;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Matching Rule Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-link me-1"></i> Matching Rule:</label>
            <p class="mb-0">
                @if($matchingRule)
                    <a href="{{ route('matchingRules.show', $matchingRule->id) }}" class="fw-bold">
                        {{ $matchingRule->name }}
                    </a>
                    <br><small class="text-body-secondary">
                        {{ number_format($matchingRule->match_percent * 100, 0) }}% match
                    </small>
                @else
                    <span class="text-body-secondary">ID: {{ $transactionMatching->matching_rule_id }}</span>
                @endif
            </p>
        </div>

        <!-- Transaction Field (Matching Transaction) -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-exchange-alt me-1"></i> Matching Transaction:</label>
            <p class="mb-0">
                @if($transaction)
                    <a href="{{ route('transactions.show', $transaction->id) }}">
                        #{{ $transaction->id }}
                    </a>
                    <span class="badge ms-1" style="background: {{ $transaction->value >= 0 ? '#28a745' : '#dc3545' }}; color: white;">
                        ${{ number_format(abs($transaction->value), 2) }}
                    </span>
                    @if($transaction->account)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-user me-1"></i>
                            <a href="{{ route('accounts.show', $transaction->account_id) }}">{{ $transaction->account->nickname }}</a>
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $transactionMatching->transaction_id }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Reference Transaction Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-reply me-1"></i> Reference Transaction:</label>
            <p class="mb-0">
                @if($refTransaction)
                    <a href="{{ route('transactions.show', $refTransaction->id) }}">
                        #{{ $refTransaction->id }}
                    </a>
                    <span class="badge ms-1" style="background: {{ $refTransaction->value >= 0 ? '#28a745' : '#dc3545' }}; color: white;">
                        ${{ number_format(abs($refTransaction->value), 2) }}
                    </span>
                    @if($refTransaction->account)
                        <br><small class="text-body-secondary">
                            <i class="fa fa-user me-1"></i>
                            <a href="{{ route('accounts.show', $refTransaction->account_id) }}">{{ $refTransaction->account->nickname }}</a>
                        </small>
                    @endif
                @else
                    <span class="text-body-secondary">ID: {{ $transactionMatching->reference_transaction_id }}</span>
                @endif
            </p>
        </div>

        <!-- Transaction Matching ID Field -->
        <div class="form-group mb-3">
            <label class="text-body-secondary"><i class="fa fa-hashtag me-1"></i> Transaction Matching ID:</label>
            <p class="mb-0">#{{ $transactionMatching->id }}</p>
        </div>
    </div>
</div>
