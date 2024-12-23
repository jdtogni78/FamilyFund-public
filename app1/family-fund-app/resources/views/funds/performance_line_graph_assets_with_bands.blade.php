@foreach ($api['asset_monthly_bands'] as $symbol => $data)
    @if ($symbol != 'SP500' && $symbol != 'CASH') 
        <!-- && $api['tradePortfolios'].some(p => p.items.some(i => i.symbol == $symbol))) -->
        <!-- TODO ignore if not in trade portfolio, using php -->
        <div class="row">
            <div class="col">
              <div class="card">
                <div class="card-header">
                <a class="btn btn-primary" data-toggle="collapse" href="#collapse{{$symbol}}" 
                  role="button" aria-expanded="false" aria-controls="collapse{{$symbol}}">
                  {{$symbol}}
                </a>
                </div>
                <div class="collapse" id="collapse{{$symbol}}">
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
                      <br><strong>Trading Bands</strong>
                      <div>
                        <canvas id="perfBands{{$symbol}}"></canvas>
                      </div>
                      <strong>Assets</strong>
                      <div>
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
        for (let symbol in api.asset_monthly_bands) {
          symbols.push(symbol);
          symbolData[symbol] = api.asset_monthly_bands[symbol];
          symbolShares[symbol] = Object.fromEntries(
            Object.entries(api.asset_monthly_bands[symbol]).map(([key, value]) => [key, value.shares])
          );
        }
        labels = Object.keys(symbolData['CASH']);
        colors = ['red', 'blue', 'green', 'orange', 'cyan', 'magenta', 'brown', 'purple',
          // create darker variations of the colors
          'darkred', 'darkblue', 'darkgreen', 'darkorange', 'darkcyan', 'darkmagenta', 'darkbrown', 'darkpurple',
        ];

        sumData = [];
        for (let symbol in symbolData) {
          for (let date in symbolData[symbol]) {
            sumData[date] = (sumData[date] || 0) + symbolData[symbol][date].value;
            console.log(symbol, date, symbolData[symbol][date].value, sumData[date]);
          }
          console.log(symbol, sumData);
        }
        
        shadingArea = {
            id: 'shadingArea',
            beforeDatasetsDraw: function(chart, args, options) {
              const { ctx, chartArea: {top, bottom, left, right, width, height},
                scales: {x, y} } = chart;

              ctx.save();
              ctx.beginPath();
              ctx.fillStyle = chart.data.datasets[0].backgroundColor;
              ctx.globalAlpha = 0.2;
              if (chart.getDatasetMeta(1).data[0] == undefined || chart.getDatasetMeta(2).data[0] == undefined) {
                ctx.restore();
                return;
              }
              ctx.moveTo(chart.getDatasetMeta(0).data[0].x, chart.getDatasetMeta(1).data[0].y);
              for (let j = 1; j < chart.getDatasetMeta(0).data.length; j++) {
                if (chart.getDatasetMeta(1).data[j] == undefined) continue;
                ctx.lineTo(chart.getDatasetMeta(0).data[j].x, chart.getDatasetMeta(1).data[j].y);
              }
              for (let z = chart.getDatasetMeta(0).data.length - 1; 0 <= z; z--) {
                if (chart.getDatasetMeta(2).data[z] == undefined) continue;
                ctx.lineTo(chart.getDatasetMeta(0).data[z].x, chart.getDatasetMeta(2).data[z].y);
              }
              ctx.closePath();
              ctx.fill();
              ctx.restore();
            }
          };

        ind = 0;
        for (let symbol of symbols) {
          if (symbol == 'CASH') continue;
          // skip if no sumData or no symbolData
          if (Object.keys(sumData).length == 0 || Object.keys(symbolData).length == 0) {
            continue;
          }

          datasets = [];
          let data = Object.values(symbolData[symbol]).map(function(e) {return e.value;});
          // find = undefined 
          upData = {};
          downData = {};
          targetData = {};
          for (let key in sumData) {
            // find trade portfolio item for this symbol & timeframe
            ports = api.tradePortfolios.filter(p => p.start_dt <= key && key <= p.end_dt);
            port = ports[0];
            port = port.items.find(i => i.symbol == symbol);
            if (port == undefined) {
              console.log("no port found for " + symbol + " at " + key);
              continue;
            }
            up = parseFloat(port.target_share) + parseFloat(port.deviation_trigger);
            down = parseFloat(port.target_share) - parseFloat(port.deviation_trigger);
            target = parseFloat(port.target_share);
            upData[key] = sumData[key] * up;
            downData[key] = sumData[key] * down;
            targetData[key] = sumData[key] * target;
          }
          console.log(symbol, targetData, sumData);

          datasets.push({
              label: symbol,
              data: data,
              backgroundColor: [colors[ind]],
              borderColor: [colors[ind]],
          });
          datasets.push({
              label: symbol + " max",
              data: upData,
              // showLine: false,
              // pointRadius: 0,
          });
          datasets.push({
              label: symbol + " min",
              data: downData,
              // showLine: false,
              // pointRadius: 0,
          });
          datasets.push({
              label: symbol + " target",
              data: targetData,
              backgroundColor: ['lightgray'],
              borderColor: ['lightgray'],
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
                  scales: {
                    y: {
                      beginAtZero: false,
                    }
                  },
                },
                plugins: [shadingArea]
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
                  scales: {
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