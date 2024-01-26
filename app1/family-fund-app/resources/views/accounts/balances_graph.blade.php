@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
var all_labels   = api.transactions.map(function(e){return e.timestamp.substr(0,10);});
var all_balances = api.transactions.map(function(e){return e.balances.OWN;});
var all_values   = api.transactions.map(function(e){return e.balances.OWN * e.share_price;});

const balances_labels = [...new Set(all_labels)].sort();
let balances = {};
for (let i=0; i<=balances_labels.length; i++) {
    balances[all_labels[i]] = all_balances[i];
}

function createGraphConfig(d, l, c) {
  const _data = {
    datasets: [{
      label: l,
      data: d,
      fill: false,
      borderColor: c,
      stepped: true,
      tension: 0.1
    }]
  };

    let _config = {
        type: 'line',
        data: _data,
        options: {
            scales: {
                x: {
                    type: 'time',
                    time: {unit: 'month'}
                },
                y: {
                    beginAtZero: true
                }
            }
        }
    };
    return _config;
}

var myChart = new Chart(
  document.getElementById('balancesGraph'),
  createGraphConfig(balances, 'Balance', 'gray')
);
</script>
@endpush
