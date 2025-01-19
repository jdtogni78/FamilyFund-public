@if(null !== $api1['transaction'])
<h6>Transaction Details:</h6>
<ul>
    <li>Account: {{ $api1['transaction']['account']['nickname'] }}</li>
    <li>Value: ${{ number_format($api1['transaction']['value'], 2) }}</li>
    <li>Shares: {{ number_format($api1['transaction']['shares'], 4) }} 
        <span class="text-primary">(${{ number_format($api1['shareValue'], 2) }} * 
            {{ number_format($api1['transaction']['shares'], 4) }} = 
            ${{ number_format($api1['transaction']['shares'] * $api1['shareValue'], 2) }})</span></li>
    <li>Share Value: ${{ number_format($api1['shareValue'], 2) }}</li>
</ul>
@endif

@if(null !== $api1['balance'])
    @php($balance = $api1['balance'])
    <h6>Balance Change for {{ $balance->account->nickname }}:</h6>
    <ul>
        <li>Share Balance: 
            <span class="text-muted">{{ number_format($balance['oldShares'], 4) }}</span>
            -> 
            <span class="text-success">{{ number_format($balance->shares, 4) }}</span>
            <span class="text-primary">({{ number_format($balance->shares - $balance['oldShares'], 4) }})</span>
        </li>
        <li>Effective Date: {{ $balance->start_dt }}</li>
    </ul>
@endif

@if(null !== $api1['matches'])
    @foreach($api1['matches'] as $match)
    @php($transaction = $match['transaction'])
    @php($balance = $match['balance'])
    <h6>Matching Transaction:</h6>
    <ul>
        <li>Description: {{ $transaction['descr'] }}</li>
        <li>Value: ${{ number_format($transaction['value'], 2) }}</li>
        <li>Shares: {{ number_format($transaction['shares'], 4) }}
            <span class="text-primary">
                ({{ $transaction['shares'] }} * ${{ number_format($api1['shareValue'], 2) }} =
                ${{ number_format($transaction['shares'] * $api1['shareValue'], 2) }})</span>
        </li>
    </ul>

    <h6>Balance Change for {{ $balance['account']['nickname'] }}:</h6>
    <ul>
        <li>Share Balance: 
            <span class="text-muted">{{ number_format($match['oldShares'], 4) }}</span>
            -> 
            <span class="text-success">{{ number_format($balance['shares'], 4) }}</span>
            <span class="text-primary">({{ number_format($balance['shares'] - $match['oldShares'], 4) }})</span>
        </li>
        <li>Effective Date: {{ $balance['start_dt'] }}</li>
    </ul>
    @endforeach
@endif

@if(null !== $api1['fundCash'])
    <h6>Fund Cash Position for {{ $api1['transaction']['account']['nickname'] }}:</h6>
    <ul>
        <li>Cash Balance: 
            <span class="text-muted">${{ number_format($api1['fundCash'][1], 2) }}</span>
            -> 
            <span class="text-success">${{ number_format($api1['fundCash'][0]['position'], 2) }}</span>
            <span class="text-primary">({{ number_format($api1['fundCash'][0]['position'] - $api1['fundCash'][1], 2) }})</span>
        </li>
        <li>Effective Date: {{ $api1['fundCash'][0]['start_dt'] }}</li>
    </ul>
@endif