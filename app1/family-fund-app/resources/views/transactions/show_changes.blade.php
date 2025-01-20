@if(null !== $api['transaction'])
    <h6>Transaction Details:</h6>
    @php($transaction = $api['transaction'])
    <ul>
        <li>Account: {{ $transaction->account->nickname }}</li>
        <li>Value: ${{ number_format($transaction->value, 2) }}</li>
        <li>Date: {{ $transaction->timestamp->format('Y-m-d') }}</li>
        <li>Shares: {{ number_format($transaction->shares, 4) }} 
            <span class="text-{{ $transaction->shares < 0 ? 'danger' : 'success' }}">(${{ number_format($api['shareValue'], 2) }} * 
                {{ number_format($transaction->shares, 4) }} = 
                {{ $transaction->shares < 0 ? '' : '+' }}${{ number_format($transaction->shares * $api['shareValue'], 2) }})</span></li>
        <li>Share Value: ${{ number_format($api['shareValue'], 2) }}</li>
    </ul>
    @php($balance = $transaction->balance)
    <h6>Balance Change for {{ $balance->account->nickname }}:</h6>
    <ul>
        <li>Share Balance: 
            <span class="text-muted">{{ number_format($balance->previousBalance?->shares, 4) }}</span>
            -> 
            @php($delta = $balance->shares - $balance->previousBalance?->shares)
            <span class="text-{{ $delta < 0 ? 'danger' : 'success' }}">
                {{ number_format($balance->shares, 4) }}
                ({{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 4) }})</span>
            </span>
        </li>
        <li>Effective Date: {{ $balance->start_dt }}</li>
    </ul>
@endif

@if(null !== $api['matches'])
    @foreach($api['matches'] as $transaction)
        @php($balance = $transaction->balance)
        <h6>Matching Transaction:</h6>
        <ul>
            <li>Description: {{ $transaction->descr }}</li>
            <li>Value: ${{ number_format($transaction->value, 2) }}</li>
            <li>Shares: {{ number_format($transaction->shares, 4) }}
                <span class="text-{{ $transaction->shares < 0 ? 'danger' : 'success' }}">
                    ({{ $transaction->shares }} * ${{ number_format($api['shareValue'], 2) }} =
                    {{ $transaction->shares < 0 ? '' : '+' }}${{ number_format($transaction->shares * $api['shareValue'], 2) }})</span>
            </li>
        </ul>

        <h6>Balance Change for {{ $balance->account->nickname }}:</h6>
        <ul>
            <li>Share Balance: 
                <span class="text-muted">{{ number_format($balance->previousBalance?->shares, 4) }}</span>
                -> 
                @php($delta = $balance->shares - $balance->previousBalance?->shares)
                <span class="text-{{ $delta < 0 ? 'danger' : 'success' }}">
                    {{ number_format($balance->shares, 4) }}
                    ({{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 4) }})</span>
            </li>
            <li>Effective Date: {{ $balance->start_dt }}</li>
        </ul>
    @endforeach
@endif

@if(null !== $api['fundCash'])
    @php($fundCash = $api['fundCash'])
    <h6>Fund Cash Position for {{ $api['transaction']->account->nickname }}:</h6>
    <ul>
        <li>Cash Balance: 
            <span class="text-muted">${{ number_format($fundCash[1], 2) }}</span>
            -> 
            @php($delta = $fundCash[0]->position - $fundCash[1])
            <span class="{{ $delta < 0 ? 'text-danger' : 'text-success' }}">
                ${{ number_format($fundCash[0]->position, 2) }}
                ({{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }})</span>
        </li>
        <li>Effective Date: {{ $fundCash[0]->start_dt }}</li>
    </ul>
@endif

@if(null !== $api['today'])
    <h6>Current Account Value as of {{ $api['today']->format('Y-m-d') }}:</h6>
    <ul>
        <li>Share Value: ${{ number_format($api['share_value_today'], 2) }}</li>
        <li>Total Shares: {{ number_format($api['shares_today'], 4) }}</li>
        <li>Total Value: ${{ number_format($api['value_today'], 2) }}</li>
    </ul>
@endif
