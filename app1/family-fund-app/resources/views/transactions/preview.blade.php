<x-app-layout>

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
                @include('coreui-templates.common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Preview Transaction</strong>
                            </div>
                            <div class="card-body">
                                @if(null !== $api1['transaction'])
                                @php($transaction = $api1['transaction'])
                                <h6>Transaction Details:</h6>
                                <ul>
                                    <li>Account: {{ $transaction->account->nickname }}</li>
                                    <li>Value: ${{ number_format($transaction->value, 2) }}</li>
                                    <li>Date: {{ $transaction->timestamp->format('Y-m-d') }}</li>
                                    <li>Shares: {{ number_format($transaction->shares, 4) }} 
                                        <span class="text-{{ $transaction->shares > 0 ? 'success' : 'danger' }}">(${{ number_format($api1['shareValue'], 2) }} * 
                                            {{ number_format($transaction->shares, 4) }} = {{ $transaction->shares > 0 ? '+' : '' }}${{ number_format($transaction->shares * $api1['shareValue'], 2) }})</span></li>
                                    <li>Share Value: ${{ number_format($api1['shareValue'], 2) }}</li>
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
                                    </li>
                                    <li>Effective Date: {{ $balance->start_dt }}</li>
                                </ul>
                                @endif


                                @if(null !== $api1['matches'])
                                    @foreach($api1['matches'] as $transaction)
                                    @php($balance = $transaction->balance)
                                    <h6>Matching Transaction:</h6>
                                    <ul>
                                        <li>Description: {{ $transaction->descr }}</li>
                                        <li>Value: ${{ number_format($transaction->value, 2) }}</li>
                                        <li>Shares: {{ number_format($transaction->shares, 4) }}
                                            <span class="text-{{ $transaction->shares > 0 ? 'success' : 'danger' }}">
                                                ({{ $transaction->shares }} * ${{ number_format($api1['shareValue'], 2) }} =
                                                {{ $transaction->shares > 0 ? '+' : '' }}${{ number_format($transaction->shares * $api1['shareValue'], 2) }})</span>
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

                                @if(null !== $api1['fundCash'])
                                    <h6>Fund Cash Position for {{ $transaction->account->nickname }}:</h6>
                                    <ul>
                                        <li>Cash Balance: 
                                            <span class="text-muted">${{ number_format($api1['fundCash'][1], 2) }}</span>
                                            -> 
                                            @php($delta = $api1['fundCash'][0]['position'] - $api1['fundCash'][1])
                                            <span class="text-{{ $delta < 0 ? 'danger' : 'success' }}">${{ number_format($api1['fundCash'][0]['position'], 2) }}
                                            ({{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 2) }})</span>
                                        </li>
                                        <li>Effective Date: {{ $api1['fundCash'][0]['start_dt'] }}</li>
                                    </ul>
                                @endif
                                @isset($api1['shares_today'])
                                    <h6>Current Account Value as of {{ $api1['today']->format('Y-m-d') }}:</h6>
                                    <ul>
                                        <li>Share Value: <strong>${{ number_format($api1['share_value_today'], 2) }}</strong></li>
                                        <li>Total Shares: <strong>{{ number_format($api1['shares_today'], 4) }}</strong></li>
                                        <li>Total Value: <strong>${{ number_format($api1['value_today'], 2) }}</strong></li>
                                    </ul>
                                @endisset
                                <div class="form-group row mb-3">
                                    @php($transaction = $api1['transaction'])
                                    @if($transaction->id !== null)
                                    <form method="POST" action="{{ route('transactions.process_pending', $transaction->id) }}" class="form-horizontal">
                                    @else
                                    <form method="POST" action="{{ route('transactions.store') }}" class="form-horizontal">
                                    @endif
                                        @csrf
                                        <div class="form-group col-sm-12">
                                            @include('transactions.preview_fields')
                                            <div class="form-group">
                                                <div class="col-sm-offset-2 col-sm-10">
                                                    <button type="submit" class="btn btn-primary">Save</button>
                                                    <button type="reset" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
