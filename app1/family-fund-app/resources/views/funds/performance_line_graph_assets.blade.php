<div>
    <canvas id="perfGraph{{$group}}"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
        group = '{{$group}}'
        perf = {!! json_encode($perf) !!};
        colors = ['red', 'blue', 'green', 'orange', 'cyan', 'magenta', 'pink'];
        datasets = [];

        ind = 0;
        for (let symbol in perf) {
            let data = Object.values(perf[symbol]).map(function(e) {return e.value;});
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
