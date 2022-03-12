<div>
    <canvas id="perfGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};

var myChart = new Chart(
    document.getElementById('perfGraph'),
    {
      type: 'line',
      data: {
        labels: Object.keys(api.monthly_performance),
        datasets: [{
          label: 'Performance',
          data: Object.values(api.monthly_performance).map(function(e) {return e.value;}),
          backgroundColor: [
            'gray',
            'gray',
            'green',
          ],
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }}
      },
    }
  );
</script>
@endpush
