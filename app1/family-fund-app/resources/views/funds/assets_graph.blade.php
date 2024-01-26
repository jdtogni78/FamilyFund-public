<div>
    <canvas id="assetsGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
var assets_labels = api.portfolio.assets.map(function(e) {return e.name;});
var assets_shares = api.portfolio.assets.map(function(e) {return e.value;});

  var myChart = new Chart(
    document.getElementById('assetsGraph'),
    {
  type: 'doughnut',
  data: {
  labels: assets_labels,
  datasets: [{
    // label: 'My First Dataset',
    data: assets_shares,
    backgroundColor: [
        'blue',
        'red',
        'green',
        'yellow',
        'cyan',
        'orange',
        'gray',
        'magenta',
        'lime',
        'teal',
        'maroon',
        'silver',
    ],
    hoverOffset: 3
  }]
},
}  );
</script>
@endpush
