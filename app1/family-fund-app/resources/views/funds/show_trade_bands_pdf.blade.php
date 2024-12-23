@extends('layouts.pdf')

@section('content')
    <div class="row" style="margin-top: 30px">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <strong>Fund Details</strong>
                </div>
                <div class="card-body">
                    @include('funds.show_fields_pdf')
                </div>
            </div>
        </div>
    </div>
    @foreach ($api['asset_monthly_bands'] as $symbol => $data)
        @if ($symbol != 'SP500' && $symbol != 'CASH') 
        <!-- && $api['tradePortfolios'].some(p => p.items.some(i => i.symbol == $symbol))) -->
        <!-- TODO ignore if not in trade portfolio, using php -->
        <div class="row">
            <div class="col">
              <div class="card">
                <div class="card-header">
                  <strong>{{$symbol}}</strong>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Target Share</th>
                            <th>Deviation Trigger</th>
                        </tr>
                        @foreach ($api['tradePortfolios'] as $i => $tp)
                          @php
                            $tpi = null;
                            foreach ($tp['items'] as $i) {
                              if ($i['symbol'] == $symbol) {
                                  $tpi = $i;
                                  break;
                              }
                            }
                            if ($tpi) {
                              $target_share = $tpi['target_share'];
                              $deviation_trigger = $tpi['deviation_trigger'];
                              $value = $api['summary']['value'] * $target_share;
                            }
                          @endphp
                          @if ($tpi)
                            <tr>
                                <td>{{ $tp['start_dt'] }}</td>
                                <td>{{ $tp['end_dt'] }}</td>
                                <td>{{ $target_share ?? 'N/A' }}</td>
                                <td>{{ $deviation_trigger ?? 'N/A' }}</td>
                            </tr>
                          @endif
                        @endforeach
                      </table>
                      <br>Target Value: {{ $value ?? 'N/A' }}
                      <br><strong>{{$symbol}} Trading Bands</strong>
                      <div>
                        <img src="{{$files['trade_bands_'.$symbol.'.png']}}" alt="{{$symbol}} Trading Bands"/>
                      </div>
                      <strong>{{$symbol}} Assets</strong>
                      <div>
                        <img src="{{$files['asset_positions_'.$symbol.'.png']}}" alt="{{$symbol}} Asset Positions"/>
                      </div>
                    </div>
                </div>
            </div>
        </div>
      @endif
@endforeach
@endsection
