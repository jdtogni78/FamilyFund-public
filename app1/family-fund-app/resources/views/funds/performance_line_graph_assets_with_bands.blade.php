@php
    // Use passed portfolioSymbols or build from trade portfolios
    $portfolioSymbols = $portfolioSymbols ?? collect($api['tradePortfolios'] ?? [])
        ->flatMap(fn($tp) => collect($tp->items ?? $tp['items'] ?? [])->pluck('symbol'))
        ->unique()
        ->toArray();
@endphp
@foreach ($api['asset_monthly_bands'] as $symbol => $data)
    @if ($symbol != 'SP500' && $symbol != 'CASH' && in_array($symbol, $portfolioSymbols))
        @php
            $symbolStatus = collect($api['allocation_status']['symbols'] ?? [])
                ->firstWhere('symbol', $symbol);
        @endphp
        <div class="row mb-4" id="section-{{$symbol}}">
            <div class="col">
              <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: #1e293b; color: white;">
                    <div>
                        <strong><i class="fa fa-chart-line mr-2"></i>{{$symbol}}</strong>
                        @if($symbolStatus)
                            @if($symbolStatus['status'] === 'ok')
                                <span class="badge badge-success ml-2">OK</span>
                            @elseif($symbolStatus['status'] === 'under')
                                <span class="badge badge-danger ml-2">Under</span>
                            @else
                                <span class="badge badge-warning ml-2">Over</span>
                            @endif
                            <small class="ml-2" style="opacity: 0.8;">
                                {{ number_format($symbolStatus['current_pct'], 1) }}% / {{ number_format($symbolStatus['target_pct'], 1) }}%
                            </small>
                        @endif
                    </div>
                    <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapse{{$symbol}}"
                      role="button" aria-expanded="true" aria-controls="collapse{{$symbol}}">
                      <i class="fa fa-chevron-down"></i>
                    </a>
                </div>
                <div class="collapse show" id="collapse{{$symbol}}">
                    <div class="card-body">
                      {{-- Collapsible table section --}}
                      <div class="d-flex justify-content-between align-items-center mb-2">
                          <strong>Portfolio Details</strong>
                          <a class="btn btn-sm btn-outline-secondary" data-toggle="collapse" href="#collapseTable{{$symbol}}"
                             role="button" aria-expanded="true" aria-controls="collapseTable{{$symbol}}">
                              <i class="fa fa-chevron-down"></i>
                          </a>
                      </div>
                      <div class="collapse show" id="collapseTable{{$symbol}}">
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
                          <p class="mb-0">Target Value: {{ $value ?? 'N/A' }}</p>
                      </div>
                      <strong>Trading Bands</strong>
                      <div>
                        <canvas id="perfBands{{$symbol}}"></canvas>
                      </div>
                      {{-- Separate collapsible section for Asset Changes --}}
                      <div class="d-flex justify-content-between align-items-center mt-3">
                          <strong>Asset Changes</strong>
                          <a class="btn btn-sm btn-outline-secondary" data-toggle="collapse" href="#collapseAsset{{$symbol}}"
                             role="button" aria-expanded="true" aria-controls="collapseAsset{{$symbol}}">
                              <i class="fa fa-chevron-down"></i>
                          </a>
                      </div>
                      <div class="collapse show" id="collapseAsset{{$symbol}}">
                          <canvas id="assetsBands{{$symbol}}"></canvas>
                      </div>
                    </div>
                </div>
              </div>
            </div>
        </div>
      @endif
@endforeach


@push('scripts')
    <script type="text/javascript">
        api = {!! json_encode($api) !!};

        symbolData = {};
        symbolShares = {};
        symbols = [];
        labels = [];
        for (let symbol in api.asset_monthly_bands) {
          symbols.push(symbol);
          symbolData[symbol] = api.asset_monthly_bands[symbol];
          symbolShares[symbol] = Object.fromEntries(
            Object.entries(api.asset_monthly_bands[symbol]).map(([key, value]) => [key, value.shares])
          );
          // Use first symbol's keys as labels if not set yet
          if (labels.length === 0) {
            labels = Object.keys(api.asset_monthly_bands[symbol]);
          }
        }
        colors = [
          'rgb(255, 99, 132)',   // red
          'rgb(54, 162, 235)',   // blue
          'rgb(75, 192, 192)',   // green
          'rgb(255, 159, 64)',   // orange
          'rgb(0, 255, 255)',    // cyan
          'rgb(255, 0, 255)',    // magenta
          'rgb(139, 69, 19)',    // brown
          'rgb(128, 0, 128)',    // purple
        ];
        colorsFill = [
          'rgba(255, 99, 132, 0.2)',
          'rgba(54, 162, 235, 0.2)',
          'rgba(75, 192, 192, 0.2)',
          'rgba(255, 159, 64, 0.2)',
          'rgba(0, 255, 255, 0.2)',
          'rgba(255, 0, 255, 0.2)',
          'rgba(139, 69, 19, 0.2)',
          'rgba(128, 0, 128, 0.2)',
        ];

        sumData = [];
        for (let symbol in symbolData) {
          for (let date in symbolData[symbol]) {
            sumData[date] = (sumData[date] || 0) + symbolData[symbol][date].value;
          }
        }
        

        ind = 0;
        for (let symbol of symbols) {
          if (symbol == 'CASH' || symbol == 'SP500') continue;
          // skip if canvas element doesn't exist (symbol not in portfolio)
          let canvasEl = document.getElementById('perfBands' + symbol);
          if (!canvasEl) continue;
          // skip if no sumData or no symbolData
          if (Object.keys(sumData).length == 0 || Object.keys(symbolData).length == 0) {
            continue;
          }

          datasets = [];
          let data = Object.values(symbolData[symbol]).map(function(e) {return e.value;});

          // Build band arrays aligned with labels (null for gaps)
          upData = [];
          downData = [];
          targetData = [];
          for (let label of labels) {
            // find trade portfolio item for this symbol & timeframe
            // Normalize dates to YYYY-MM-DD format (strip time portion if present)
            let ports = api.tradePortfolios.filter(p => {
              let startDt = (p.start_dt || '').substring(0, 10);
              let endDt = (p.end_dt || '').substring(0, 10);
              return startDt <= label && label <= endDt;
            });
            if (!ports || ports.length == 0 || !ports[0].items) {
              upData.push(null);
              downData.push(null);
              targetData.push(null);
              continue;
            }
            let portItem = ports[0].items.find(i => i.symbol == symbol);
            if (!portItem) {
              upData.push(null);
              downData.push(null);
              targetData.push(null);
              continue;
            }
            let up = parseFloat(portItem.target_share) + parseFloat(portItem.deviation_trigger);
            let down = parseFloat(portItem.target_share) - parseFloat(portItem.deviation_trigger);
            let target = parseFloat(portItem.target_share);
            let totalValue = sumData[label] || 0;
            upData.push(totalValue * up);
            downData.push(totalValue * down);
            targetData.push(totalValue * target);
          }

          // Band fill (max to min)
          datasets.push({
              label: symbol + " max",
              data: upData,
              borderColor: 'rgba(150, 150, 150, 0.5)',
              backgroundColor: colorsFill[ind % colorsFill.length],
              fill: '+1', // fill to next dataset (min)
              pointRadius: 2,
              spanGaps: false,
          });
          datasets.push({
              label: symbol + " min",
              data: downData,
              borderColor: 'rgba(150, 150, 150, 0.5)',
              fill: false,
              pointRadius: 2,
              spanGaps: false,
          });
          // Actual value line (on top)
          datasets.push({
              label: symbol,
              data: data,
              backgroundColor: colors[ind],
              borderColor: colors[ind],
              borderWidth: 2,
              pointRadius: 3,
              fill: false,
          });
          // Target line
          datasets.push({
              label: symbol + " target",
              data: targetData,
              backgroundColor: 'lightgray',
              borderColor: 'lightgray',
              borderDash: [5, 5],
              pointRadius: 2,
              spanGaps: false,
              fill: false,
          });


          myChart = new Chart(
              document.getElementById('perfBands' + symbol),
              {
                type: 'line',
                data: {
                  labels: labels,
                  datasets: datasets
                },
                options: {
                  plugins: {
                    datalabels: { display: false },
                    filler: {
                      propagate: false
                    }
                  },
                  scales: {
                    x: {
                      ticks: {
                        maxRotation: 45,
                        minRotation: 45
                      }
                    },
                    y: {
                      beginAtZero: false,
                    }
                  },
                }
              }
          );
          datasets = [];
          datasets.push({
                label: symbol,
                data: symbolShares[symbol],
                backgroundColor: [colors[ind]],
                borderColor: [colors[ind]],
            });
          var myChart = new Chart(
              document.getElementById('assetsBands' + symbol),
              {
                type: 'line',
                data: {
                  labels: labels,
                  datasets: datasets
                },
                options: {
                  plugins: {
                    datalabels: { display: false }
                  },
                  scales: {
                    x: {
                      ticks: {
                        maxRotation: 45,
                        minRotation: 45
                      }
                    },
                    y: {
                      beginAtZero: false
                    }
                  }
                },
              }
            );

            ind++;
        }
    </script>

@endpush