<div>
    <canvas id="perfGraphMonthly"></canvas>
</div>
<div class="col-xs-12">
    <ul>
        <li><b>Monthly Performance</b>: the performance of this fund</li>
        <li><b>SP500</b>: the performance of a fund that would invest the same amount of funds 100% on SP500</li>
        <li><b>Cash</b>: the performance of a fund that would invest the same amount of funds 100% on Cash</li>
    </ul>
</div>

@push('scripts')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};

        let labels = Object.keys(api.monthly_performance);
        let data = Object.values(api.monthly_performance).map(function(e) {return e.value;});
        let datasets = [{
            label: 'Monthly Performance',
            data: data,
            backgroundColor: [graphColors[0]],
            borderColor: [graphColors[0]],
        }];

        let addSP500 = {!! $addSP500 !!};
        if (addSP500) {
            let sp500 = Object.values(api.sp500_monthly_performance).map(function(e) {return e.value;});
            datasets.push({
                label: 'S&P 500',
                data: sp500,
                backgroundColor: [graphColors[1]],
                borderColor: [graphColors[1]],
            });
        }

        datasets.push({
            label: 'Cash',
            data: Object.values(api.cash).map(function(e) {return e.value;}),
            backgroundColor: [graphColors[2]],
            borderColor: [graphColors[2]],
        });

        var myChart = new Chart(
            document.getElementById('perfGraphMonthly'),
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
