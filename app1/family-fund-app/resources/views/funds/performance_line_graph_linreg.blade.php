<div>
    <canvas id="perfGraphLinReg"></canvas>
</div>
<div class="col-xs-12">
    <ul>
        <li><b>Predicted Value</b>: the predicted value of this fund</li>
    </ul>
</div>

@push('scripts')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};

        labels = Object.keys(api.linear_regression.predictions);
        data = Object.values(api.linear_regression.predictions);
        conservative = Object.values(api.linear_regression.predictions).map(value => value * 0.8);
        aggressive = Object.values(api.linear_regression.predictions).map(value => value * 1.2);
        datasets = [{
            label: 'Conservative',
            data: conservative,
            backgroundColor: [graphColors[0]],
            borderColor: [graphColors[0]],
        },{
            label: 'Predictied Value',
            data: data,
            backgroundColor: [graphColors[1]],
            borderColor: [graphColors[1]],
        },{
            label: 'Aggressive',
            data: aggressive,
            backgroundColor: [graphColors[2]],
            borderColor: [graphColors[2]],
        }];

        var myChart = new Chart(
            document.getElementById('perfGraphLinReg'),
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
