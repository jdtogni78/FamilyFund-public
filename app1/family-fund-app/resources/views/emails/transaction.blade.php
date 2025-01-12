@extends('layouts.email')

@section('content')
Dear {{$api['to']}},<br>
    Find the confirmation of the transaction below.<br>
<br>

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

@if(null !== $api1['newBal'])
    <h6>Balance Change for {{ $api1['newBal']['account']['nickname'] }}:</h6>
    <ul>
        <li>Share Balance: 
            <span class="text-muted">{{ number_format($api1['oldShares'], 4) }}</span>
            -> 
            <span class="text-success">{{ number_format($api1['newBal']['shares'], 4) }}</span>
            <span class="text-primary">({{ number_format($api1['newBal']['shares'] - $api1['oldShares'], 4) }})</span>
        </li>
        <li>Effective Date: {{ $api1['newBal']['start_dt'] }}</li>
    </ul>
@endif

@if(null !== $api1['mtch'])
    @foreach($api1['mtch'] as $mtch_arr)
    <h6>Matching Transaction:</h6>
    <ul>
        <li>Description: {{ $mtch_arr[1]['descr'] }}</li>
        <li>Value: ${{ number_format($mtch_arr[1]['value'], 2) }}</li>
        <li>Shares: {{ number_format($mtch_arr[1]['shares'], 4) }}
            <span class="text-primary">
                ({{ $mtch_arr[1]['shares'] }} * ${{ number_format($api1['shareValue'], 2) }} =
                ${{ number_format($mtch_arr[1]['shares'] * $api1['shareValue'], 2) }})</span>
        </li>
    </ul>

    <h6>Balance Change for {{ $mtch_arr[0][0]['account']['nickname'] }}:</h6>
    <ul>
        <li>Share Balance: 
            <span class="text-muted">{{ number_format($mtch_arr[0][1], 4) }}</span>
            -> 
            <span class="text-success">{{ number_format($mtch_arr[0][0]['shares'], 4) }}</span>
            <span class="text-primary">({{ number_format($mtch_arr[0][0]['shares'] - $mtch_arr[0][1], 4) }})</span>
        </li>
        <li>Effective Date: {{ $mtch_arr[0][0]['start_dt'] }}</li>
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
@endsection
