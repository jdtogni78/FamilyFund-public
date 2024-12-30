<div>
    <canvas id="perfGraph{{$group}}"></canvas>
</div>
<div class="col-xs-12">
    <ul>
        <li><b>SP500</b>: the value of a fund that would invest the same amount of funds 100% on SP500</li>
        <li><b>Others</b>: the value of a fund that would invest the same amount of funds 100% on other stock</li>
    </ul>
</div>

@push('scripts')
    <script type="text/javascript">
        group = '{{$group}}'
        perf = {!! json_encode($perf) !!};
        labels = Object.keys(perf.SP500);
        colors = ['red', 'blue', 'green', 'orange', 'cyan', 'magenta', 'pink'];
        datasets = [];

        ind = 0;
        for (let symbol in perf) {
            d1 = Object.keys(perf[symbol])[0];
            v1 = perf[symbol][d1].price;
            data = Object.values(perf[symbol]).map(function(e) {return e.price/v1;});
            datasets.push({
                label: symbol,
                data: data,
                backgroundColor: [colors[ind]],
                borderColor: [colors[ind]],
            });
            ind++;
        }

        var myChart = new Chart(
            document.getElementById('perfGraph' + group),
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
