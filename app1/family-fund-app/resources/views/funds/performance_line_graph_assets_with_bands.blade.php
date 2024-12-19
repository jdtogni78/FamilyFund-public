@foreach ($api['asset_monthly_performance'] as $group => $perf)
    @foreach ($perf as $symbol => $data)
        @if ($symbol != 'SP500' && $symbol != 'CASH')
        <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>{{$symbol}}</strong>
                        </div>
                        <div class="card-body">
                            <strong>Trading Bands</strong>
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
        @endif
    @endforeach
@endforeach


@push('scripts')
    <script type="text/javascript">
        api = {!! json_encode($api) !!};
        tport = {!! json_encode($tport) !!};
        trade_portfolios = api.tradePortfolios;
        trade_portfolio = trade_portfolios.find(p => p.id == tport);
        console.log(trade_portfolio);

        symbolData = {};
        symbolShares = {};
        symbols = []; 
        for (let group in api.asset_monthly_performance) {
          for (let symbol in api.asset_monthly_performance[group]) {
            if (trade_portfolio.items.find(p => p.symbol == symbol) || symbol == 'SP500' || symbol == 'CASH') {
              symbols.push(symbol);
              symbolData[symbol] = api.asset_monthly_performance[group][symbol];
              symbolShares[symbol] = Object.fromEntries(
                Object.entries(api.asset_monthly_performance[group][symbol]).map(([key, value]) => [key, value.shares])
              );
            }
          }
        }
        labels = Object.keys(symbolData['SP500']);
        colors = ['red', 'blue', 'green', 'orange', 'cyan', 'magenta', 'pink'];

        sumData = [];
        for (let symbol of symbols) {
          // delete SP500
          if (symbol == 'SP500') continue;
          // add all values
          for (let key in symbolData[symbol]) {
            sumData[key] = (sumData[key] || 0) + symbolData[symbol][key].value;
          }
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
              ctx.moveTo(chart.getDatasetMeta(0).data[0].x, chart.getDatasetMeta(1).data[0].y );
              for (let j = 1; j < chart.getDatasetMeta(0).data.length; j++) {
                  ctx.lineTo(chart.getDatasetMeta(0).data[j].x, chart.getDatasetMeta(1).data[j].y);
              }
              for (let z = chart.getDatasetMeta(0).data.length - 1; 0 <= z; z--) {
                ctx.lineTo(chart.getDatasetMeta(0).data[z].x, chart.getDatasetMeta(2).data[z].y);
              }
              ctx.closePath();
              ctx.fill();
              ctx.restore();
            }
          };

        ind = 0;
        for (let symbol of symbols) {
          if (symbol == 'SP500') continue;
          if (symbol == 'CASH') continue;
          
          datasets = [];
          let data = Object.values(symbolData[symbol]).map(function(e) {return e.value;});
          // find = undefined 
          let port = trade_portfolio.items.find(p => p.symbol == symbol);
          console.log(symbol, port);
          const up = parseFloat(port.target_share) + parseFloat(port.deviation_trigger);
          const down = parseFloat(port.target_share) - parseFloat(port.deviation_trigger);

          upData = {};
          downData = {};
          for (let key in sumData) {
            upData[key] = sumData[key] * up;
            downData[key] = sumData[key] * down;
          }

          datasets.push({
              label: symbol,
              data: data,
              backgroundColor: [colors[ind]],
              borderColor: [colors[ind]],
          });
          datasets.push({
              label: symbol+"up",
              data: upData,
              // showLine: false,
              pointRadius: 0,
          });
          datasets.push({
              label: symbol+"down",
              data: downData,
              // showLine: false,
              pointRadius: 0,
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