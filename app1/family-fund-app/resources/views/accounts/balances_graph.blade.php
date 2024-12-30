@push('scripts')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};
        var all_labels   = api.transactions.map(function(e){return e.timestamp.substr(0,10);});
        var all_balances = api.transactions.map(function(e){return e.balances.OWN;});
        var all_values   = api.transactions.map(function(e){return e.balances.OWN * e.share_price;});

        var balances_labels = [...new Set(all_labels)].sort();
        // sum all balances for each label
        var balances = {};
        var last_key = '';
        for (let i=0; i<all_labels.length; i++) {
            balances[all_labels[i]] = Math.max(balances[all_labels[i]] || 0, all_balances[i]);
            last_key = all_labels[i];
        }
        balances[api.as_of] = balances[last_key];

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
            createGraphConfig(balances, 'Shares Balance', 'green')
        );
    </script>
@endpush
