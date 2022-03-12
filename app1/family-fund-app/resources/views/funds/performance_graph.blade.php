<div>
    <canvas id="perfGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
const perf_labels = Object.keys(api.yearly_performance);
const perf_data = {
  labels: perf_labels,
  datasets: [{
    label: 'Performance',
    data: Object.values(api.yearly_performance).map(function(e) {return e.value;}),
    backgroundColor: [
      'gray',
      'gray',
      'green',
    ],
  }]
};
const perf_config = {
  type: 'bar',
  data: perf_data,
  options: {
    scales: {
      y: {
        beginAtZero: true
      }
    }
  },
};  
var myChart = new Chart(
    document.getElementById('perfGraph'),
    perf_config
  );
</script>
@endpush
