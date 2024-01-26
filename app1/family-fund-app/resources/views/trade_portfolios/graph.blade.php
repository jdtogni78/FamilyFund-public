<div>
    <canvas id="tradePortfolioGraph{{ $tradePortfolio->id }}"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($tradePortfolio->items) !!};
var assets_labels = api.map(function(e) {return e.symbol;});
var assets_shares = api.map(function(e) {return e.target_share * 100.0;});

assets_labels.push('Cash');
assets_shares.push({{ $tradePortfolio['cash_target'] * 100.0 }});

var myChart = new Chart(
    document.getElementById('tradePortfolioGraph{{ $tradePortfolio->id }}'),
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
