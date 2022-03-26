<div>
    <canvas id="perfGraph2"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
var myChart = new Chart(
    document.getElementById('perfGraph2'),
    {
      type: 'line',
      data: {
        labels: Object.keys(api.monthly_performance),
        datasets: [{
          label: 'Monthly Performance',
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
            beginAtZero: false
          }
        }
      },
    }
  );
</script>
@endpush
