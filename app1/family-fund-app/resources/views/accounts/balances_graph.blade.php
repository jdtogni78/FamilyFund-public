@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};
var all_labels   = api.transactions.map(function(e){return e.timestamp.substr(0,10);});
var all_balances = api.transactions.map(function(e){return e.balances.OWN;});
var all_values   = api.transactions.map(function(e){return e.balances.OWN * e.share_price;});

const balances_labels = [...new Set(all_labels)].sort();
var seen = {};
var minL="9999-12-31";
var maxL="0000-00-00";
balances_labels.forEach(function(l, p) {
    all_labels.forEach(function(e, p2) {
        if (l == e) {
            if (!seen[l]) seen[l] = all_balances[p2];
            seen[l] = Math.max(seen[l], all_balances[p2]);
            if (l > maxL) maxL = l;
            if (l < minL) minL = l;
        }
    })
});
var m = Object.keys(api.monthly_performance)[0];
if (!seen[m]) seen[m] = 0;
seen[api.as_of] = seen[maxL];
var balances = seen;
// seen = {};
// balances_labels.forEach(function(l, p) {
//     all_labels.forEach(function(e, p2) {
//         if (l == e) {
//             if (!seen[l]) seen[l] = all_values[p2];
//             seen[l] = Math.max(seen[l], all_values[p2]);
//         }
//     })
// });
// values = seen;

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
