<div>
    <canvas id="rebalanceGraph{{$item->id}}"></canvas>
</div>
{{--<div class="col-xs-12">--}}
{{--    <ul>--}}
{{--        <li><b>SP500</b>: the performance of a fund that would invest the same amount of funds 100% on SP500</li>--}}
{{--        <li><b>Others</b>: the performance of a fund that would invest the same amount of funds 100% on other stock</li>--}}
{{--    </ul>--}}
{{--</div>--}}

@push('scripts')
    <script type="text/javascript">
        colors = ['red', 'blue', 'green', 'orange', 'cyan', 'magenta', 'pink'];
        datasets = [];

        perf = api['rebalance'];
        labels = Object.keys(perf);
        item = "{!! $item->symbol !!}";
        itemId = {!! $item->id !!};
        ind = 0;

        function rebalanceData(what, perf, datasets, item, ind) {
            mydata = 0;
            let data = Object.values(perf).map(function(e) {
                if (e[item] === undefined)
                    return mydata;
                mydata = e[item][what];
                return mydata;
            });
            return {
                label: what,
                data: data,
                // backgroundColor: [colors[ind]],
                borderColor: [colors[ind]],
            };
        }

        datasets.push(rebalanceData('target', perf, datasets, item, ind++));
        datasets.push(rebalanceData('min', perf, datasets, item, ind++));
        datasets.push(rebalanceData('max', perf, datasets, item, ind++));
        datasets.push(rebalanceData('perc', perf, datasets, item, ind++));

        var myChart = new Chart(
            document.getElementById('rebalanceGraph' + itemId),
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
    </script>
@endpush
