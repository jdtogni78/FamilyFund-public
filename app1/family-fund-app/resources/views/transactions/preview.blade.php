@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
    <li class="breadcrumb-item">
         <a href="{!! route('transactions.index') !!}">Transaction</a>
      </li>
      <li class="breadcrumb-item">
         <a href="{!! route('transactions.create') !!}">Create</a>
      </li>
      <li class="breadcrumb-item active">Preview</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Preview Transaction</strong>
                            </div>
                            <div class="card-body">
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
                                    @php($balance = $api1['newBal'])
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
                                <div class="form-group row mb-3">
                                    {!! Form::open(['route' => 'transactions.store']) !!}
                                        <div class="form-group col-sm-12">
                                            @php($transaction = $api1['transaction'])
                                            @include('transactions.preview_fields')
                                            {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                                            {!! Form::button('Cancel', ['class' => 'btn btn-secondary', 'onclick' => 'window.history.back()']) !!}
                                        </div>
                                    {!! Form::close() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
