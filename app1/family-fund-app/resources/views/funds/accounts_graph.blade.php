<div>
    <canvas id="accountGraph"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};
        var _labels = api.balances.map(function(e) {return e.nickname;});
        _labels.push('Unallocated');
        var _shares = api.balances.map(function(e) {return e.shares;});
        _shares.push(api.summary.unallocated_shares);
        const acct_data = {
            labels: _labels,
            datasets: [{
                label: 'My First Dataset',
                data: _shares,
                backgroundColor: graphColors,
                hoverOffset: 3
            }]
        };
        const acct_config = {
            type: 'doughnut',
            data: acct_data,
        };
        var myChart = new Chart(
            document.getElementById('accountGraph'),
            acct_config
        );
    </script>
@endpush
